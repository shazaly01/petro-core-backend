<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Pump;
use App\Models\Shift;
use App\Models\Tank;
use App\Http\Requests\Assignment\StoreAssignmentRequest;
use App\Http\Requests\Assignment\UpdateAssignmentRequest;
use App\Http\Resources\AssignmentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Assignment::class, 'assignment');
    }

    public function index()
    {
        $activeShift = Shift::where('supervisor_id', Auth::id())
                            ->where('status', 'open')
                            ->first();

        if (!$activeShift) {
            return AssignmentResource::collection(Assignment::where('id', 0)->paginate(15));
        }

        $assignments = Assignment::with(['user', 'pump.island', 'pump.tank.fuelType'])
            ->where('shift_id', $activeShift->id)
            ->latest()
            ->paginate(15);

        return AssignmentResource::collection($assignments);
    }

    public function store(StoreAssignmentRequest $request)
    {
        $data = $request->validated();

        $activeShift = Shift::where('supervisor_id', Auth::id())
                            ->where('status', 'open')
                            ->first();

        if (!$activeShift) {
            return response()->json([
                'message' => 'عفواً، ليس لديك وردية مفتوحة حالياً. يرجى فتح وردية للبدء بإضافة التكليفات.'
            ], 422);
        }

        $data['shift_id'] = $activeShift->id;
        $data['supervisor_id'] = Auth::id();

        $pump = Pump::with('tank.fuelType')->findOrFail($data['pump_id']);

        $activeAssignment = Assignment::where('pump_id', $pump->id)
            ->where('status', 'active')
            ->exists();

        if ($activeAssignment) {
            return response()->json(['message' => 'هذه المضخة مشغولة حالياً في تكليف آخر لم يتم إغلاقه.'], 422);
        }

        $data['start_counter_1'] = $data['start_counter_1'] ?? $pump->current_counter_1;
        $data['start_counter_2'] = $data['start_counter_2'] ?? $pump->current_counter_2;
        $data['start_at'] = now();
        $data['status'] = 'active';

        $data['unit_price'] = $pump->tank && $pump->tank->fuelType
            ? round($pump->tank->fuelType->current_price, 3)
            : 0;

        $assignment = Assignment::create($data);

        return new AssignmentResource($assignment);
    }

    public function show(Assignment $assignment)
    {
        $assignment->load(['user', 'pump.tank.fuelType', 'shift']);
        return new AssignmentResource($assignment);
    }

    /**
     * إنهاء التكليف أو تعديل تكليف مغلق مسبقاً (مع تسجيل حركة المخزون)
     */
    public function update(UpdateAssignmentRequest $request, Assignment $assignment)
    {
        $data = $request->validated();

        $isClosing = (isset($data['status']) && $data['status'] === 'completed' && $assignment->status === 'active');
        $isUpdatingClosed = ($assignment->status === 'completed');

        if ($isClosing || $isUpdatingClosed) {
            DB::beginTransaction();
            try {
                // 1. حساب اللترات المباعة
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

                // 3. تحديث الخزان وحركة المخزون (دفتر الأستاذ)
                $tank = Tank::find($assignment->pump->tank_id);

                if ($tank) {
                    // إذا كان التكليف مغلقاً مسبقاً ونحن نعدله الآن (إلغاء العملية القديمة)
                    if ($isUpdatingClosed) {
                        $oldTotalSold = ($assignment->end_counter_1 - $assignment->start_counter_1) + ($assignment->end_counter_2 - $assignment->start_counter_2);

                        // إرجاع الكمية القديمة للخزان
                        $tank->increment('current_stock', $oldTotalSold);

                        // حذف السطر القديم من دفتر حركة المخزون
                        if ($assignment->stockMovement) {
                            $assignment->stockMovement()->delete();
                        }
                    }

                    // 🛑 تسجيل العملية الجديدة النظيفة
                    $balanceBefore = $tank->fresh()->current_stock;
                    $balanceAfter = $balanceBefore - $totalSoldLiters;

                    // خصم الكمية الجديدة من الخزان
                    $tank->update(['current_stock' => $balanceAfter]);

                    // كتابة سطر جديد تماماً في دفتر المخزون
                    $assignment->stockMovement()->create([
                        'tank_id' => $tank->id,
                        'type' => 'out', // نوع الحركة: خروج (مبيعات)
                        'quantity' => $totalSoldLiters,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'user_id' => Auth::id(),
                        'notes' => 'مبيعات وردية - تكليف رقم: ' . $assignment->id . ($isUpdatingClosed ? ' (معدل)' : ''),
                    ]);
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
            // تحديث عادي للبيانات الوصفية (إذا لم يكن إغلاق)
            $assignment->update($data);
        }

        return new AssignmentResource($assignment->fresh(['user', 'pump.tank.fuelType', 'shift']));
    }

    public function destroy(Assignment $assignment)
    {
        if ($assignment->status === 'completed') {
            return response()->json(['message' => 'لا يمكن حذف تكليف مكتمل ومحسوب مالياً. قم بتعديل العدادات لتصفيره بدلاً من ذلك.'], 422);
        }

        $assignment->delete();
        return response()->noContent();
    }
}
