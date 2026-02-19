<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tank;
use App\Http\Requests\Tank\StoreTankRequest;
use App\Http\Requests\Tank\UpdateTankRequest;
use App\Http\Resources\TankResource;
use Illuminate\Http\Request;

class TankController extends Controller
{
    public function __construct()
    {
        // تطبيق سياسات TankPolicy
        $this->authorizeResource(Tank::class, 'tank');
    }

    /**
     * عرض جميع الخزانات
     */
    public function index()
    {
        // جلب الخزانات مع نوع الوقود الخاص بها
        // الترتيب حسب الخزانات التي أوشكت على النفاد (اختياري لتحسين تجربة المستخدم)
        $tanks = Tank::with('fuelType')
            ->orderBy('current_stock', 'asc')
            ->paginate(10);

        return TankResource::collection($tanks);
    }

    /**
     * إضافة خزان جديد
     */
    public function store(StoreTankRequest $request)
    {
        $tank = Tank::create($request->validated());

        // إعادة تحميل العلاقة لضمان ظهور نوع الوقود في الاستجابة
        $tank->load('fuelType');

        return new TankResource($tank);
    }

    /**
     * عرض تفاصيل خزان
     */
    public function show(Tank $tank)
    {
        $tank->load(['fuelType', 'nozzles']); // عرض المسدسات المتصلة بهذا الخزان
        return new TankResource($tank);
    }

    /**
     * تحديث بيانات الخزان
     */
    public function update(UpdateTankRequest $request, Tank $tank)
    {
        $tank->update($request->validated());

        // في حال تم تغيير نوع الوقود، نعيد تحميله
        $tank->load('fuelType');

        return new TankResource($tank);
    }

    /**
     * حذف خزان
     */
    public function destroy(Tank $tank)
    {
        // منع الحذف إذا كان مرتبطاً بمسدسات نشطة
        if ($tank->nozzles()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف الخزان لأنه يغذي مسدسات وقود قائمة.'
            ], 422);
        }

        $tank->delete();
        return response()->noContent();
    }
}
