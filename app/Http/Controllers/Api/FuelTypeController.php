<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelType;
use App\Http\Requests\FuelType\StoreFuelTypeRequest;
use App\Http\Requests\FuelType\UpdateFuelTypeRequest;
use App\Http\Resources\FuelTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FuelTypeController extends Controller
{
    public function __construct()
    {
        // تفعيل نظام الصلاحيات تلقائياً بناءً على FuelTypePolicy
        // index -> viewAny, show -> view, store -> create, etc.
        $this->authorizeResource(FuelType::class, 'fuel_type');
    }

    /**
     * عرض جميع أنواع الوقود
     */
    public function index()
    {
        // نستخدم Pagination لتنظيم البيانات
        $fuelTypes = FuelType::withCount('tanks')->paginate(10);
        return FuelTypeResource::collection($fuelTypes);
    }

    /**
     * إضافة نوع وقود جديد
     */
    public function store(StoreFuelTypeRequest $request)
    {
        // البيانات القادمة تم التحقق منها بالفعل في الـ Request
        $fuelType = FuelType::create($request->validated());

        return new FuelTypeResource($fuelType);
    }

    /**
     * عرض تفاصيل نوع وقود محدد
     */
    public function show(FuelType $fuelType)
    {
        // تحميل الخزانات المرتبطة لغرض العرض
        $fuelType->load('tanks');
        return new FuelTypeResource($fuelType);
    }

    /**
     * تعديل بيانات نوع الوقود
     */
    public function update(UpdateFuelTypeRequest $request, FuelType $fuelType)
    {
        $fuelType->update($request->validated());

        return new FuelTypeResource($fuelType);
    }

    /**
     * حذف نوع الوقود
     */
    public function destroy(FuelType $fuelType)
    {
        // التحقق من وجود علاقات قبل الحذف (اختياري، لأننا نستخدم SoftDelete)
        if ($fuelType->tanks()->exists()) {
             return response()->json([
                 'message' => 'لا يمكن حذف نوع الوقود لأنه مرتبط بخزانات نشطة.'
             ], 422);
        }

        $fuelType->delete();

        return response()->noContent();
    }
}
