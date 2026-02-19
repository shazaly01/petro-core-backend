<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nozzle;
use App\Http\Requests\Nozzle\StoreNozzleRequest;
use App\Http\Requests\Nozzle\UpdateNozzleRequest;
use App\Http\Resources\NozzleResource;
use Illuminate\Http\Request;

class NozzleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Nozzle::class, 'nozzle');
    }

    public function index()
    {
        // عرض المسدسات مع المضخة والخزان ونوع الوقود
        $nozzles = Nozzle::with(['pump', 'tank.fuelType'])->paginate(10);
        return NozzleResource::collection($nozzles);
    }

    public function store(StoreNozzleRequest $request)
    {
        $nozzle = Nozzle::create($request->validated());

        // تحميل العلاقات للعرض في الـ Resource
        $nozzle->load(['pump', 'tank.fuelType']);

        return new NozzleResource($nozzle);
    }

    public function show(Nozzle $nozzle)
    {
        $nozzle->load(['pump', 'tank.fuelType']);
        return new NozzleResource($nozzle);
    }

    public function update(UpdateNozzleRequest $request, Nozzle $nozzle)
    {
        $nozzle->update($request->validated());
        $nozzle->load(['pump', 'tank.fuelType']);
        return new NozzleResource($nozzle);
    }

    public function destroy(Nozzle $nozzle)
    {
        // التحقق مما إذا كان المسدس مرتبطاً بتكليفات سابقة (حتى لو كانت قديمة)
        // لا يجوز حذف مسدس له سجل مالي، بل يتم تعطيله (is_active = false)
        if ($nozzle->assignments()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف المسدس لوجود عمليات مالية سابقة عليه. يمكنك تعطيله بدلاً من حذفه.'
            ], 422);
        }

        $nozzle->delete();
        return response()->noContent();
    }
}
