<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Nozzle;
use App\Models\FuelType; // نحتاجه لجلب السعر الحالي
use App\Http\Requests\Assignment\StoreAssignmentRequest;
use App\Http\Requests\Assignment\UpdateAssignmentRequest;
use App\Http\Resources\AssignmentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Shift;

class AssignmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Assignment::class, 'assignment');
    }

    public function index()
    {
        // عرض التكليفات مع الموظف والمسدس والمدفوعات
        $assignments = Assignment::with(['user', 'nozzle.pump.island', 'transactions'])
            ->latest()
            ->paginate(15);

        return AssignmentResource::collection($assignments);
    }

   public function store(StoreAssignmentRequest $request)
    {
        $data = $request->validated();

        // 🛑 1. البحث عن الوردية المفتوحة حالياً
        // نجلب أول وردية حالتها 'open'
        $activeShift = Shift::where('status', 'open')->first();

        if (!$activeShift) {
            return response()->json([
                'message' => 'لا توجد وردية مفتوحة حالياً. يرجى فتح وردية أولاً للتمكن من إضافة تكليفات.'
            ], 422);
        }

        // ربط التكليف الجديد تلقائياً بمعرف الوردية المفتوحة
        $data['shift_id'] = $activeShift->id;

        // 2. جلب بيانات المسدس للتأكد من حالته
        $nozzle = Nozzle::with('tank.fuelType')->findOrFail($data['nozzle_id']);

        // التحقق: هل المسدس مشغول حالياً؟
        $activeAssignment = Assignment::where('nozzle_id', $nozzle->id)
            ->where('status', 'active')
            ->exists();

        if ($activeAssignment) {
            return response()->json(['message' => 'هذا المسدس مشغول حالياً مع عامل آخر.'], 422);
        }

        // 3. تحديد قراءة البداية تلقائياً من المسدس
        if (!isset($data['start_counter'])) {
            $data['start_counter'] = $nozzle->current_counter;
        }

        // 4. تحديد وقت البدء
        if (!isset($data['start_at'])) {
            $data['start_at'] = now();
        }

        $data['status'] = 'active';


        // 🛑 التعديل الجديد: إجبار النظام على أخذ السعر الرسمي من نوع الوقود (3 خانات عشرية)
        $data['unit_price'] = $nozzle->tank && $nozzle->tank->fuelType
            ? round($nozzle->tank->fuelType->current_price, 3)
            : 0;

        $assignment = Assignment::create($data);

        return new AssignmentResource($assignment);
    }


    public function show(Assignment $assignment)
    {
        $assignment->load(['user', 'nozzle', 'transactions']);
        return new AssignmentResource($assignment);
    }

    /**
     * إنهاء التكليف (استلام العهدة والمحاسبة)
     */
    public function update(UpdateAssignmentRequest $request, Assignment $assignment)
    {
        $data = $request->validated();

        // إذا كان الطلب يتضمن إغلاق التكليف (completed)
        if (isset($data['status']) && $data['status'] === 'completed' && $assignment->status === 'active') {

            DB::beginTransaction(); // حماية البيانات لضمان تنفيذ كل العمليات أو لا شيء
            try {
                // 1. حساب اللترات المباعة
                $endCounter = $data['end_counter'];
                $startCounter = $assignment->start_counter;
                $soldLiters = $endCounter - $startCounter;

                if ($soldLiters < 0) {
                    throw new \Exception('قراءة العداد النهائية لا يمكن أن تكون أقل من البداية.');
                }

                // 2. جلب السعر الحالي للوقود
                // المسدس -> الخزان -> نوع الوقود -> السعر
                $fuelType = $assignment->nozzle->tank->fuelType;
                $currentPrice = $fuelType->current_price;

                // 3. حساب المبلغ الإجمالي
                $totalAmount = $soldLiters * $currentPrice;

                // 4. تحديث بيانات التكليف
                $assignment->update([
                    'end_counter' => $endCounter,
                    'sold_liters' => $soldLiters,
                    'unit_price' => $currentPrice,
                    'total_amount' => $totalAmount,
                    'end_at' => $data['end_at'] ?? now(),
                    'status' => 'completed',
                ]);

                // 5. تحديث قراءة المسدس للعملية القادمة
                $assignment->nozzle->update(['current_counter' => $endCounter]);

                // 6. خصم الكمية من المخزون (الخزان)
                $tank = $assignment->nozzle->tank;
                $tank->decrement('current_stock', $soldLiters);

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'حدث خطأ أثناء الإغلاق: ' . $e->getMessage()], 422);
            }
        } else {
            // تحديث عادي (بدون إغلاق)
            $assignment->update($data);
        }

        return new AssignmentResource($assignment);
    }

    public function destroy(Assignment $assignment)
    {
        if ($assignment->status === 'completed') {
            return response()->json(['message' => 'لا يمكن حذف تكليف مكتمل ومحسوب مالياً.'], 422);
        }
        $assignment->delete();
        return response()->noContent();
    }
}
