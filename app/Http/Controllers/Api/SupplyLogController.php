<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupplyLog;
use App\Models\Tank;
use App\Http\Requests\SupplyLog\StoreSupplyLogRequest;
use App\Http\Requests\SupplyLog\UpdateSupplyLogRequest;
use App\Http\Resources\SupplyLogResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SupplyLogController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(SupplyLog::class, 'supply_log');
    }

    public function index()
    {
        $logs = SupplyLog::with(['tank.fuelType', 'supervisor'])->latest()->paginate(10);
        return SupplyLogResource::collection($logs);
    }

    /**
     * تسجيل عملية توريد جديدة (تفريغ شاحنة) وإنشاء حركة مخزون
     */
    public function store(StoreSupplyLogRequest $request)
    {
        $data = $request->validated();

        // تسجيل المشرف الحالي إذا لم يحدد
        $data['supervisor_id'] = Auth::id();

        DB::beginTransaction();
        try {
            // 1. جلب الخزان لمعرفة الرصيد قبل التوريد
            $tank = Tank::findOrFail($data['tank_id']);
            $balanceBefore = $tank->current_stock;
            $quantity = $data['quantity'];
            $balanceAfter = $balanceBefore + $quantity;

            // 2. تحديث مخزون الخزان (زيادة الكمية)
            $tank->update(['current_stock' => $balanceAfter]);

            // 3. توثيق أرصدة المسطرة (قبل وبعد) في بيانات التوريد
            $data['stock_before'] = $data['stock_before'] ?? $balanceBefore;
            $data['stock_after'] = $data['stock_after'] ?? $balanceAfter;

            // 4. إنشاء سجل التوريد الأساسي
            $supplyLog = SupplyLog::create($data);

            // 5. 🛑 [السحر هنا] إنشاء حركة المخزون (دفتر الأستاذ) أوتوماتيكياً
            $supplyLog->stockMovement()->create([
                'tank_id' => $tank->id,
                'type' => 'in', // نوع الحركة: دخول
                'quantity' => $quantity,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'user_id' => Auth::id(),
                'notes' => 'توريد وقود - فاتورة رقم: ' . ($data['invoice_number'] ?? 'غير محدد'),
            ]);

            DB::commit();

            return new SupplyLogResource($supplyLog);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء تسجيل التوريد: ' . $e->getMessage()], 500);
        }
    }

    public function show(SupplyLog $supplyLog)
    {
        return new SupplyLogResource($supplyLog);
    }

    /**
     * تعديل سجل التوريد (للتصحيح الوصفي فقط)
     */
   /**
     * تعديل سجل التوريد (بطريقة الإلغاء وإعادة الإدخال - Reverse & Re-enter)
     */
  public function update(UpdateSupplyLogRequest $request, SupplyLog $supplyLog)
    {
        $data = $request->validated();

        // الحماية: منع تعديل هوية المشرف الأصلي
        unset($data['supervisor_id']);

        DB::beginTransaction();
        try {
            // 1. عكس العملية القديمة: خصم الكمية القديمة من الخزان القديم
            $oldTank = Tank::findOrFail($supplyLog->tank_id);
            $oldTank->decrement('current_stock', $supplyLog->quantity);

            // 2. حذف حركة المخزون القديمة
            if ($supplyLog->stockMovement) {
                $supplyLog->stockMovement()->delete();
            }

            // 3. 🛑 تحديث بيانات التوريد (هنا سيتم حفظ قراءات المسطرة الجديدة التي أدخلتها أنت بالواجهة)
            $supplyLog->update($data);

            // 4. تنفيذ العملية الجديدة محاسبياً
            $newTank = Tank::findOrFail($supplyLog->tank_id);
            $balanceBefore = $newTank->current_stock; // الرصيد الدفتري
            $balanceAfter = $balanceBefore + $supplyLog->quantity;

            // تحديث رصيد الخزان
            $newTank->update(['current_stock' => $balanceAfter]);

            // 5. إنشاء حركة مخزون جديدة تماماً في الدفتر (بالأرصدة المحاسبية الدقيقة للنظام)
            $supplyLog->stockMovement()->create([
                'tank_id' => $newTank->id,
                'type' => 'in',
                'quantity' => $supplyLog->quantity,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'user_id' => Auth::id(),
                'notes' => 'توريد وقود (معدل) - فاتورة رقم: ' . ($supplyLog->invoice_number ?? 'غير محدد'),
            ]);

            DB::commit();

            return new SupplyLogResource($supplyLog->fresh(['tank.fuelType', 'supervisor']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'عفواً، حدث خطأ أثناء التعديل: ' . $e->getMessage()], 422);
        }
    }

    /**
     * حذف سجل توريد (خصم الكمية وحذف الحركة)
     */
    public function destroy(SupplyLog $supplyLog)
    {
        DB::beginTransaction();
        try {
            $tank = $supplyLog->tank;

            // 1. خصم الكمية التي أضيفت خطأً من الخزان
            if ($tank->current_stock >= $supplyLog->quantity) {
                $tank->decrement('current_stock', $supplyLog->quantity);
            } else {
                $tank->update(['current_stock' => 0]);
            }

            // 2. 🛑 حذف حركة المخزون المرتبطة بهذا التوريد من دفتر الأستاذ
            if ($supplyLog->stockMovement) {
                $supplyLog->stockMovement()->delete();
            }

            // 3. حذف سجل التوريد نفسه
            $supplyLog->delete();

            DB::commit();
            return response()->noContent();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء الحذف'], 500);
        }
    }
}
