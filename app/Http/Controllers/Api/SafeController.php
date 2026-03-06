<?php

namespace App\Http\Controllers;

use App\Models\Safe;
use App\Http\Requests\Safe\StoreSafeRequest;
use App\Http\Requests\Safe\UpdateSafeRequest;
use App\Http\Resources\SafeResource;
use Illuminate\Http\Request;

class SafeController extends Controller
{
    /**
     * عرض جميع الخزائن مع رصيدها
     */
    public function index()
    {
        $this->authorize('viewAny', Safe::class);

        $safes = Safe::all();
        return SafeResource::collection($safes);
    }

    /**
     * إنشاء خزينة جديدة
     */
    public function store(StoreSafeRequest $request)
    {
        $this->authorize('create', Safe::class);

        $safe = Safe::create($request->validated());

        return response()->json([
            'message' => 'تم إنشاء الخزينة بنجاح',
            'data' => new SafeResource($safe)
        ], 201);
    }

    /**
     * عرض خزينة محددة (مع إمكانية جلب حركاتها)
     */
    public function show(Safe $safe)
    {
        $this->authorize('view', $safe);

        // جلب الخزينة مع آخر 50 حركة مالية مثلاً
        $safe->load(['transactions' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(50);
        }]);

        return new SafeResource($safe);
    }

    /**
     * تعديل بيانات الخزينة (الاسم فقط)
     */
    public function update(UpdateSafeRequest $request, Safe $safe)
    {
        $this->authorize('update', $safe);

        $safe->update($request->validated());

        return response()->json([
            'message' => 'تم تحديث بيانات الخزينة بنجاح',
            'data' => new SafeResource($safe)
        ]);
    }

    /**
     * حذف الخزينة (يجب التأكد من تصفيرها أولاً)
     */
    public function destroy(Safe $safe)
    {
        $this->authorize('delete', $safe);

        if ($safe->balance > 0) {
            return response()->json([
                'message' => 'لا يمكن حذف الخزينة لأن بها رصيد. قم بتصفيرها أولاً.'
            ], 422);
        }

        if ($safe->transactions()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف الخزينة لوجود حركات مالية مسجلة عليها.'
            ], 422);
        }

        $safe->delete();

        return response()->json([
            'message' => 'تم حذف الخزينة بنجاح'
        ]);
    }
}
