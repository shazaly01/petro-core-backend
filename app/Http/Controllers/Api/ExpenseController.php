<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Shift;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Services\SafeService; // 🛑 1. استيراد خدمة الخزينة
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // 🛑 2. استيراد واجهة قاعدة البيانات للـ Transactions

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
     * تخزين مصروف جديد وربطه بالوردية المفتوحة وخصمه من الخزينة
     */
    public function store(StoreExpenseRequest $request, SafeService $safeService) // 🛑 3. حقن الخدمة
    {
        // 1. البحث عن الوردية المفتوحة حالياً
        $openShift = Shift::where('status', 'open')->first();

        // 2. إذا لم تكن هناك وردية مفتوحة، نرفض العملية
        if (!$openShift) {
            return response()->json([
                'message' => 'لا يمكن تسجيل مصروف حالياً، لا توجد وردية مفتوحة في النظام.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // 3. إنشاء المصروف وربطه بالوردية والمستخدم الحالي
            $expense = Expense::create([
                ...$request->validated(),
                'shift_id' => $openShift->id,
                'user_id'  => Auth::id(),
            ]);

            // 🛑 4. خصم المبلغ من الخزينة (إذا كان الدفع نقداً)
            if ($expense->payment_method === 'cash') {
                $description = 'تسجيل مصروف: ' . ($expense->description ?? $expense->category);
                $safeService->withdraw($expense->amount, $expense, $openShift->id, $description);
            }

            DB::commit();

            return new ExpenseResource($expense->load(['shift', 'user']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء حفظ المصروف: ' . $e->getMessage()], 422);
        }
    }

    /**
     * عرض تفاصيل مصروف محدد
     */
    public function show(Expense $expense)
    {
        return new ExpenseResource($expense->load(['shift', 'user']));
    }

    /**
     * تحديث بيانات المصروف (مع التسوية المالية في الخزينة)
     */
    public function update(UpdateExpenseRequest $request, Expense $expense, SafeService $safeService) // 🛑 5. حقن الخدمة
    {
        try {
            DB::beginTransaction();

            // 🛑 1. الاحتفاظ بالبيانات القديمة قبل التعديل
            $oldAmount = $expense->amount;
            $oldPaymentMethod = $expense->payment_method;

            // 2. تحديث بيانات المصروف
            $expense->update($request->validated());

            // 🛑 3. التسوية المالية في الخزينة
            // أ. استرجاع المبلغ القديم للخزينة (قيد عكسي) إذا كان مدفوعاً كاش
            if ($oldPaymentMethod === 'cash' && $oldAmount > 0) {
                $safeService->deposit($oldAmount, $expense, $expense->shift_id, 'قيد عكسي لتعديل مصروف رقم: ' . $expense->id);
            }

            // ب. خصم المبلغ الجديد من الخزينة إذا كان الدفع الجديد كاش
            if ($expense->payment_method === 'cash' && $expense->amount > 0) {
                $safeService->withdraw($expense->amount, $expense, $expense->shift_id, 'خصم نقدي لمصروف معدل رقم: ' . $expense->id);
            }

            DB::commit();

            return new ExpenseResource($expense->load(['shift', 'user']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء تعديل المصروف: ' . $e->getMessage()], 422);
        }
    }

    /**
     * حذف المصروف (حذف ناعم وإرجاع المبلغ للخزينة)
     */
    public function destroy(Expense $expense, SafeService $safeService) // 🛑 6. حقن الخدمة
    {
        try {
            DB::beginTransaction();

            // 🛑 1. إرجاع المبلغ للخزينة قبل الحذف (إذا كان الدفع كاش)
            if ($expense->payment_method === 'cash' && $expense->amount > 0) {
                $safeService->deposit($expense->amount, $expense, $expense->shift_id, 'استرداد نقدي لإلغاء/حذف المصروف رقم: ' . $expense->id);
            }

            // 2. حذف المصروف
            $expense->delete();

            DB::commit();

            return response()->json(['message' => 'تم حذف المصروف واسترداد قيمته للخزينة بنجاح.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء حذف المصروف: ' . $e->getMessage()], 422);
        }
    }
}
