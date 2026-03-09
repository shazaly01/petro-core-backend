<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pump;
use App\Http\Requests\Pump\StorePumpRequest;
use App\Http\Requests\Pump\UpdatePumpRequest;
use App\Http\Resources\PumpResource;
use Illuminate\Http\Request;

class PumpController extends Controller
{
    public function __construct()
    {
        // تطبيق سياسات PumpPolicy
        $this->authorizeResource(Pump::class, 'pump');
    }

    /**
     * عرض جميع المضخات
     */
  public function index(Request $request) // 🛑 التعديل: إضافة Request هنا
    {
        $query = Pump::with(['island', 'tank.fuelType']);

        // 🛑 التعديل: التحقق إذا كان الطلب يحتوي على الفلتر "?available=1" أو "?available=true"
        if ($request->boolean('available')) {
            $query->available(); // استخدام الـ Scope الذي أنشأناه في الموديل
        }

        $pumps = $query->paginate(10);

        return PumpResource::collection($pumps);
    }

    /**
     * إضافة مضخة جديدة
     */
    public function store(StorePumpRequest $request)
    {
        $pump = Pump::create($request->validated());

        // تحميل العلاقات لإعادتها في الـ Response
        $pump->load(['island', 'tank']);

        return new PumpResource($pump);
    }

    /**
     * عرض تفاصيل مضخة
     */
    public function show(Pump $pump)
    {
        // عرض المضخة مع الخزان التابع لها ومعلومات الوقود
        $pump->load(['island', 'tank.fuelType']);

        return new PumpResource($pump);
    }

    /**
     * تحديث بيانات المضخة
     */
    public function update(UpdatePumpRequest $request, Pump $pump)
    {
        $pump->update($request->validated());

        $pump->load(['island', 'tank']);

        return new PumpResource($pump);
    }

    /**
     * حذف مضخة
     */
    public function destroy(Pump $pump)
    {
        // 🛑 التعديل هنا: منع الحذف إذا كانت المضخة مرتبطة بتكليفات (لحفظ التاريخ المالي)
        if ($pump->assignments()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف المضخة لارتباطها بسجلات تكليفات وحركات مالية سابقة.'
            ], 422);
        }

        $pump->delete();

        return response()->noContent();
    }
}
