<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Shift;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function __construct()
    {
        // تفعيل البوليسي لحماية المسارات
        $this->authorizeResource(Expense::class, 'expense');
    }

    /**
     * عرض قائمة المصروفات مع التحميل المسبق للعلاقات
     */
    public function index()
    {
        $expenses = Expense::with(['shift', 'user'])
            ->latest()
            ->paginate(10);

        return ExpenseResource::collection($expenses);
    }

    /**
     * تخزين مصروف جديد وربطه بالوردية المفتوحة
     */
    public function store(StoreExpenseRequest $request)
    {
        // 1. البحث عن الوردية المفتوحة حالياً
        $openShift = Shift::where('status', 'open')->first();

        // 2. إذا لم تكن هناك وردية مفتوحة، نرفض العملية
        if (!$openShift) {
            return response()->json([
                'message' => 'لا يمكن تسجيل مصروف حالياً، لا توجد وردية مفتوحة في النظام.'
            ], 422);
        }

        // 3. إنشاء المصروف وربطه بالوردية والمستخدم الحالي
        $expense = Expense::create([
            ...$request->validated(),
            'shift_id' => $openShift->id,
            'user_id'  => Auth::id(),
        ]);

        return new ExpenseResource($expense->load(['shift', 'user']));
    }

    /**
     * عرض تفاصيل مصروف محدد
     */
    public function show(Expense $expense)
    {
        return new ExpenseResource($expense->load(['shift', 'user']));
    }

    /**
     * تحديث بيانات المصروف
     */
    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        $expense->update($request->validated());
        return new ExpenseResource($expense->load(['shift', 'user']));
    }

    /**
     * حذف المصروف (حذف ناعم)
     */
    public function destroy(Expense $expense)
    {
        $expense->delete();
        return response()->json(['message' => 'تم حذف المصروف بنجاح.']);
    }
}
