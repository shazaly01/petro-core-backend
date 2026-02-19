<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Island;
use App\Http\Requests\Island\StoreIslandRequest;
use App\Http\Requests\Island\UpdateIslandRequest;
use App\Http\Resources\IslandResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IslandController extends Controller
{
    public function __construct()
    {
        // ربط الصلاحيات بـ IslandPolicy
        $this->authorizeResource(Island::class, 'island');
    }

    /**
     * قائمة الجزر
     */
    public function index()
    {
        // نجلب الجزر مع عدد المضخات التي عليها
        $islands = Island::withCount('pumps')->paginate(10);
        return IslandResource::collection($islands);
    }

    /**
     * إنشاء جزيرة جديدة
     */
    public function store(StoreIslandRequest $request)
    {
        $island = Island::create($request->validated());
        return new IslandResource($island);
    }

    /**
     * عرض تفاصيل جزيرة
     */
    public function show(Island $island)
    {
        // هنا نقوم بتحميل المضخات والمسدسات التابعة لها لعرض الهيكل كاملاً للمشرف
        $island->load(['pumps.nozzles']);

        return new IslandResource($island);
    }

    /**
     * تحديث بيانات الجزيرة
     */
    public function update(UpdateIslandRequest $request, Island $island)
    {
        $island->update($request->validated());
        return new IslandResource($island);
    }

    /**
     * حذف الجزيرة
     */
    public function destroy(Island $island)
    {
        if ($island->pumps()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف الجزيرة لأنها تحتوي على مضخات.'
            ], 422);
        }

        $island->delete();
        return response()->noContent();
    }
}
