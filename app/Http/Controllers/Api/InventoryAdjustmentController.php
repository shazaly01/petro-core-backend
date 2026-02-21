<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustment;
use App\Models\Tank;
use App\Http\Requests\InventoryAdjustment\StoreInventoryAdjustmentRequest;
use App\Http\Requests\InventoryAdjustment\UpdateInventoryAdjustmentRequest;
use App\Http\Resources\InventoryAdjustmentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryAdjustmentController extends Controller
{
    /**
     * تفعيل حارس الصلاحيات (Policy)
     */
    public function __construct()
    {
        $this->authorizeResource(InventoryAdjustment::class, 'inventory_adjustment');
    }

    /**
     * عرض قائمة التسويات الجردية السابقة
     */
    public function index()
    {
        $adjustments = InventoryAdjustment::with(['tank.fuelType', 'user'])
            ->latest()
            ->paginate(15);

        return InventoryAdjustmentResource::collection($adjustments);
    }

    /**
     * إنشاء تسوية جردية جديدة (الجرد الفعلي)
     */
    public function store(StoreInventoryAdjustmentRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $tank = Tank::findOrFail($data['tank_id']);

            $systemStock = $tank->current_stock;
            $actualStock = $data['actual_stock'];

            // حساب الفارق
            $difference = $actualStock - $systemStock;

            // منع التسوية الصفرية
            if ($difference == 0) {
                return response()->json(['message' => 'الرصيد الفعلي يطابق تماماً رصيد النظام. لا توجد حاجة للتسوية.'], 422);
            }

            // 1. إنشاء سجل التسوية
            $adjustment = InventoryAdjustment::create([
                'tank_id' => $tank->id,
                'user_id' => Auth::id(), // المشرف الذي قام بالجرد
                'system_stock' => $systemStock,
                'actual_stock' => $actualStock,
                'difference' => $difference,
                'reason' => $data['reason'] ?? 'تسوية جردية يدوية',
            ]);

            // 2. تحديث رصيد الخزان ليطابق الجرد الفعلي
            $tank->update(['current_stock' => $actualStock]);

            // 3. كتابة السطر في دفتر الحركات (StockMovement)
            $adjustmentType = $difference > 0 ? 'زيادة جردية (+)' : 'عجز جردي (-)';

            $adjustment->stockMovement()->create([
                'tank_id' => $tank->id,
                'type' => 'adjustment',
                'quantity' => abs($difference), // الكمية تُسجل موجبة لتنظيم الدفتر
                'balance_before' => $systemStock,
                'balance_after' => $actualStock,
                'user_id' => Auth::id(),
                'notes' => "تسوية جردية: {$adjustmentType} بمقدار " . abs($difference) . " لتر. السبب: " . $adjustment->reason,
            ]);

            DB::commit();

            return new InventoryAdjustmentResource($adjustment->load(['tank.fuelType', 'user']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء تنفيذ التسوية: ' . $e->getMessage()], 500);
        }
    }

    /**
     * عرض تفاصيل تسوية جردية واحدة
     */
    public function show(InventoryAdjustment $inventoryAdjustment)
    {
        $inventoryAdjustment->load(['tank.fuelType', 'user']);
        return new InventoryAdjustmentResource($inventoryAdjustment);
    }

    /**
     * تعديل تسوية جردية (صلاحية المدير فقط - بطريقة الإلغاء وإعادة الإدخال)
     */
    public function update(UpdateInventoryAdjustmentRequest $request, InventoryAdjustment $inventoryAdjustment)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            // التحقق: هل قام المدير بتعديل الرصيد الفعلي؟
            if (isset($data['actual_stock']) && $data['actual_stock'] != $inventoryAdjustment->actual_stock) {

                $tank = Tank::findOrFail($inventoryAdjustment->tank_id);

                // 1. إلغاء التأثير القديم للتسوية من الخزان
                // (عن طريق خصم الفارق القديم. إذا كان الفارق سالباً سيتم جمعه، وإذا كان موجباً سيتم طرحه تلقائياً)
                $tank->decrement('current_stock', $inventoryAdjustment->difference);

                // 2. حذف سطر حركة المخزون القديمة
                if ($inventoryAdjustment->stockMovement) {
                    $inventoryAdjustment->stockMovement()->delete();
                }

                // 3. حساب الفارق الجديد بناءً على رصيد النظام الأصلي وقتها
                $systemStock = $inventoryAdjustment->system_stock;
                $newActualStock = $data['actual_stock'];
                $newDifference = $newActualStock - $systemStock;

                if ($newDifference == 0) {
                    throw new \Exception('التعديل الجديد يجعل الرصيد الفعلي مطابقاً للدفتري، يرجى حذف التسوية بالكامل بدلاً من تعديلها.');
                }

                // 4. تطبيق الفارق الجديد على الخزان
                $tank->increment('current_stock', $newDifference);

                // 5. تحديث بيانات سجل التسوية
                $inventoryAdjustment->update([
                    'actual_stock' => $newActualStock,
                    'difference' => $newDifference,
                    'reason' => $data['reason'] ?? $inventoryAdjustment->reason,
                ]);

                // 6. كتابة حركة مخزون جديدة تماماً
                $adjustmentType = $newDifference > 0 ? 'زيادة جردية (+)' : 'عجز جردي (-)';
                $inventoryAdjustment->stockMovement()->create([
                    'tank_id' => $tank->id,
                    'type' => 'adjustment',
                    'quantity' => abs($newDifference),
                    'balance_before' => $systemStock,
                    'balance_after' => $newActualStock,
                    'user_id' => Auth::id(),
                    'notes' => "تسوية جردية (معدلة من الإدارة): {$adjustmentType} بمقدار " . abs($newDifference) . " لتر. السبب: " . $inventoryAdjustment->reason,
                ]);

            } else {
                // تحديث البيانات الوصفية فقط (السبب) في حال لم تتغير الكمية
                $inventoryAdjustment->update($request->only('reason'));
            }

            DB::commit();
            return new InventoryAdjustmentResource($inventoryAdjustment->fresh(['tank.fuelType', 'user']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء التعديل: ' . $e->getMessage()], 422);
        }
    }

    /**
     * حذف تسوية جردية بالكامل (صلاحية المدير) وإرجاع الأرصدة
     */
    public function destroy(InventoryAdjustment $inventoryAdjustment)
    {
        DB::beginTransaction();
        try {
            $tank = Tank::findOrFail($inventoryAdjustment->tank_id);

            // عكس العملية على الخزان وإلغاء تأثيرها
            $tank->decrement('current_stock', $inventoryAdjustment->difference);

            // مسح السجل من دفتر حركة المخزون
            if ($inventoryAdjustment->stockMovement) {
                $inventoryAdjustment->stockMovement()->delete();
            }

            // حذف سجل التسوية
            $inventoryAdjustment->delete();

            DB::commit();
            return response()->noContent();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء الحذف: ' . $e->getMessage()], 500);
        }
    }
}
