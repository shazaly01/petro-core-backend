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
    public function index()
    {
        // جلب المضخات مع اسم الجزيرة وعدد المسدسات
        $pumps = Pump::with(['island'])->withCount('nozzles')->paginate(10);
        return PumpResource::collection($pumps);
    }

    /**
     * إضافة مضخة جديدة
     */
    public function store(StorePumpRequest $request)
    {
        $pump = Pump::create($request->validated());
        $pump->load('island');
        return new PumpResource($pump);
    }

    /**
     * عرض تفاصيل مضخة
     */
    public function show(Pump $pump)
    {
        // عند عرض المضخة، أهم شيء هو عرض المسدسات التي بداخلها
        $pump->load(['island', 'nozzles.tank.fuelType']);

        return new PumpResource($pump);
    }

    /**
     * تحديث بيانات المضخة
     */
    public function update(UpdatePumpRequest $request, Pump $pump)
    {
        $pump->update($request->validated());
        $pump->load('island');
        return new PumpResource($pump);
    }

    /**
     * حذف مضخة
     */
    public function destroy(Pump $pump)
    {
        if ($pump->nozzles()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف المضخة لأنها تحتوي على مسدسات.'
            ], 422);
        }

        $pump->delete();
        return response()->noContent();
    }
}
