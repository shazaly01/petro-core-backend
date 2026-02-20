<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Pump; // استخدمنا Pump بدلاً من Nozzle
use App\Models\Shift;
use App\Http\Requests\Assignment\StoreAssignmentRequest;
use App\Http\Requests\Assignment\UpdateAssignmentRequest;
use App\Http\Resources\AssignmentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Assignment::class, 'assignment');
    }

    public function index()
    {
        // جلب التكليفات مع الموظف والمضخة (بدون مسدسات ولا معاملات)
        $assignments = Assignment::with(['user', 'pump.island', 'pump.tank.fuelType'])
            ->latest()
            ->paginate(15);

        return AssignmentResource::collection($assignments);
    }

    /**
     * فتح وردية / إنشاء تكليف
     */
    public function store(StoreAssignmentRequest $request)
    {
        $data = $request->validated();

        // 1. البحث عن الوردية المفتوحة حالياً
        $activeShift = Shift::where('status', 'open')->first();

        if (!$activeShift) {
            return response()->json([
                'message' => 'لا توجد وردية مفتوحة حالياً. يرجى فتح وردية أولاً للتمكن من إضافة تكليفات.'
            ], 422);
        }

        $data['shift_id'] = $activeShift->id;

        // 2. جلب بيانات المضخة للتأكد من حالتها
        $pump = Pump::with('tank.fuelType')->findOrFail($data['pump_id']);

        // التحقق: هل المضخة مشغولة حالياً مع عامل آخر؟
        $activeAssignment = Assignment::where('pump_id', $pump->id)
            ->where('status', 'active')
            ->exists();

        if ($activeAssignment) {
            return response()->json(['message' => 'هذه المضخة مشغولة حالياً مع عامل آخر.'], 422);
        }

        // 3. تحديد قراءات البداية للمسدسين تلقائياً من جدول المضخة
        $data['start_counter_1'] = $data['start_counter_1'] ?? $pump->current_counter_1;
        $data['start_counter_2'] = $data['start_counter_2'] ?? $pump->current_counter_2;

        if (!isset($data['start_at'])) {
            $data['start_at'] = now();
        }

        $data['status'] = 'active';

        // 4. أخذ السعر الرسمي للوقود لحظة فتح الوردية وحفظه لمنع تأثير تغير الأسعار لاحقاً
        $data['unit_price'] = $pump->tank && $pump->tank->fuelType
            ? round($pump->tank->fuelType->current_price, 3)
            : 0;

        $assignment = Assignment::create($data);

        return new AssignmentResource($assignment);
    }

    public function show(Assignment $assignment)
    {
        $assignment->load(['user', 'pump', 'shift']);
        return new AssignmentResource($assignment);
    }

    /**
     * إنهاء التكليف (حساب العدادات، الفروقات، تحديث المضخة والخزان)
     */
    /**
     * إنهاء التكليف أو تعديل تكليف مغلق مسبقاً
     */
    public function update(UpdateAssignmentRequest $request, Assignment $assignment)
    {
        $data = $request->validated();

        // هل نحن نقوم بإغلاق التكليف الآن؟ أو نعدل على تكليف مغلق مسبقاً؟
        $isClosing = (isset($data['status']) && $data['status'] === 'completed' && $assignment->status === 'active');
        $isUpdatingClosed = ($assignment->status === 'completed');

        if ($isClosing || $isUpdatingClosed) {
            DB::beginTransaction();
            try {
                // 1. حساب اللترات
                $end1 = $data['end_counter_1'] ?? $assignment->end_counter_1;
                $end2 = $data['end_counter_2'] ?? $assignment->end_counter_2;

                $soldLiters1 = $end1 - $assignment->start_counter_1;
                $soldLiters2 = $end2 - $assignment->start_counter_2;

                if ($soldLiters1 < 0 || $soldLiters2 < 0) {
                    throw new \Exception('قراءة العداد النهائية لا يمكن أن تكون أقل من البداية.');
                }

                $totalSoldLiters = $soldLiters1 + $soldLiters2;

                // 2. الحسابات المالية
                $expectedAmount = $totalSoldLiters * $assignment->unit_price;
                $cashAmount = $data['cash_amount'] ?? $assignment->cash_amount ?? 0;
                $bankAmount = $data['bank_amount'] ?? $assignment->bank_amount ?? 0;

                $difference = ($cashAmount + $bankAmount) - $expectedAmount;

                // 3. تحديث الخزان (إرجاع الكمية القديمة وخصم الجديدة في حالة التعديل)
                $tank = $assignment->pump->tank;
                if ($tank) {
                    if ($isUpdatingClosed) {
                        $oldTotalSold = ($assignment->end_counter_1 - $assignment->start_counter_1) + ($assignment->end_counter_2 - $assignment->start_counter_2);
                        $tank->increment('current_stock', $oldTotalSold); // إرجاع القديم
                    }
                    $tank->decrement('current_stock', $totalSoldLiters); // خصم الجديد
                }

                // 4. تحديث العداد التراكمي للمضخة
                $assignment->pump->update([
                    'current_counter_1' => $end1,
                    'current_counter_2' => $end2,
                ]);

                // 5. حفظ التكليف النهائي
                $assignment->update([
                    'end_counter_1' => $end1,
                    'end_counter_2' => $end2,
                    'expected_amount' => $expectedAmount,
                    'cash_amount' => $cashAmount,
                    'bank_amount' => $bankAmount,
                    'difference' => $difference,
                    'end_at' => $data['end_at'] ?? $assignment->end_at ?? now(),
                    'status' => 'completed',
                ]);

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'حدث خطأ أثناء المعالجة: ' . $e->getMessage()], 422);
            }
        } else {
            // تحديث عادي للبيانات (إذا لم يكن إغلاق)
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
