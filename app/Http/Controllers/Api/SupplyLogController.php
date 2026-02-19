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
     * تسجيل عملية توريد جديدة (تفريغ شاحنة)
     */
    public function store(StoreSupplyLogRequest $request)
    {
        $data = $request->validated();

        // تسجيل المشرف الحالي إذا لم يحدد
        if (!isset($data['supervisor_id'])) {
            $data['supervisor_id'] = Auth::id();
        }

        DB::beginTransaction();
        try {
            // 1. إنشاء سجل التوريد
            $supplyLog = SupplyLog::create($data);

            // 2. تحديث مخزون الخزان (زيادة الكمية)
            $tank = Tank::findOrFail($data['tank_id']);
            $tank->increment('current_stock', $data['quantity']);

            // (اختياري) تحديث قراءة المسطرة بعد التفريغ في السجل إذا لم تكن موجودة
            if (!isset($data['stock_after'])) {
                $supplyLog->update(['stock_after' => $tank->current_stock]);
            }

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
     * تعديل سجل التوريد (للتصحيح فقط)
     */
    public function update(UpdateSupplyLogRequest $request, SupplyLog $supplyLog)
    {
        // ملاحظة: تعديل الكمية هنا قد يتطلب منطقاً معقداً لتعديل المخزون بأثر رجعي
        // للتبسيط، سنسمح بتعديل البيانات الوصفية (مثل اسم السائق)
        // أما الكمية فنتركها كما هي أو نطلب حذف السجل وإعادة إنشائه لضمان سلامة المخزون.

        $supplyLog->update($request->validated());
        return new SupplyLogResource($supplyLog);
    }

    /**
     * حذف سجل توريد (يجب خصم الكمية من الخزان مرة أخرى)
     */
    public function destroy(SupplyLog $supplyLog)
    {
        DB::beginTransaction();
        try {
            // خصم الكمية التي أضيفت خطأً
            $tank = $supplyLog->tank;

            // التأكد من أن الخصم لن يجعل المخزون بالسالب (نظرياً)
            if ($tank->current_stock >= $supplyLog->quantity) {
                $tank->decrement('current_stock', $supplyLog->quantity);
            } else {
                // في حالة نادرة: تم بيع الوقود بالفعل!
                // هنا نقوم بتصفير المخزون أو تسجيل عجز، حسب السياسة.
                $tank->update(['current_stock' => 0]);
            }

            $supplyLog->delete();

            DB::commit();
            return response()->noContent();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء الحذف'], 500);
        }
    }
}
