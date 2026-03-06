<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\Shift;
use App\Enums\VoucherType;
use App\Http\Requests\Voucher\StoreVoucherRequest;
use App\Http\Requests\Voucher\UpdateVoucherRequest;
use App\Http\Resources\VoucherResource;
use App\Services\SafeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Voucher::class, 'voucher');
    }

    /**
     * عرض قائمة السندات
     */
    public function index()
    {
        $vouchers = Voucher::with(['shift', 'user'])
            ->latest()
            ->paginate(15);

        return VoucherResource::collection($vouchers);
    }

    /**
     * إنشاء سند جديد (إيداع، مصروف، تحويل، أو تسوية)
     */
    public function store(StoreVoucherRequest $request, SafeService $safeService)
    {
        // 1. جلب الوردية المفتوحة (إن وجدت) لربط السند بها تلقائياً
        $openShift = Shift::where('status', 'open')->first();

        try {
            DB::beginTransaction();

            $data = $request->validated();

            // 2. توليد رقم السند المكون من 18 رقم (سنة+شهر+يوم+ساعة+دقيقة+ثانية+4أرقام عشوائية)
            $voucherNo = date('YmdHis') . str_pad((string)rand(0, 9999), 4, '0', STR_PAD_LEFT);

            // 3. إنشاء السند
            $voucher = Voucher::create([
                ...$data,
                'voucher_no' => $voucherNo,
                'shift_id'   => $openShift?->id,
                'user_id'    => Auth::id(),
                'date'       => now(),
            ]);

            // 🛑 4. التأثير المالي على الخزينة (فقط إذا كان الدفع نقداً)
            if ($voucher->payment_method === 'cash') {
                $description = $voucher->type->label() . ' رقم: ' . $voucher->voucher_no;

                // إذا كان إيداعاً، نزيد الخزينة
                if ($voucher->type === VoucherType::DEPOSIT) {
                    $safeService->deposit($voucher->amount, $voucher, $voucher->shift_id, $description);
                }
                // بقية الأنواع (مصروف، تحويل للبنك، تسوية عجز) تخصم من الخزينة
                else {
                    $safeService->withdraw($voucher->amount, $voucher, $voucher->shift_id, $description);
                }
            }

            DB::commit();

            return new VoucherResource($voucher->load(['shift', 'user']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء حفظ السند: ' . $e->getMessage()], 422);
        }
    }

    /**
     * عرض تفاصيل سند محدد
     */
    public function show(Voucher $voucher)
    {
        return new VoucherResource($voucher->load(['shift', 'user']));
    }

    /**
     * 🛑 تعديل السند (مع التسوية المالية الأوتوماتيكية المرنة)
     */
    public function update(UpdateVoucherRequest $request, Voucher $voucher, SafeService $safeService)
    {
        try {
            DB::beginTransaction();

            // 1. الاحتفاظ بالحالة القديمة للسند
            $oldAmount = $voucher->amount;
            $oldType = $voucher->type;
            $oldPaymentMethod = $voucher->payment_method;

            // 2. تحديث بيانات السند
            $voucher->update($request->validated());
            $voucher->refresh(); // جلب البيانات الجديدة المحدثة

            // 3. القيود العكسية (فقط إذا كان التعامل نقدياً)
            if ($oldPaymentMethod === 'cash') {
                // أ. إلغاء تأثير السند القديم (قيد عكسي)
                $cancelDesc = 'قيد عكسي لتعديل ' . $oldType->label() . ' رقم: ' . $voucher->voucher_no;
                if ($oldType === VoucherType::DEPOSIT) {
                    $safeService->withdraw($oldAmount, $voucher, $voucher->shift_id, $cancelDesc);
                } else {
                    $safeService->deposit($oldAmount, $voucher, $voucher->shift_id, $cancelDesc);
                }
            }

            // 4. تطبيق التأثير الجديد للسند
            if ($voucher->payment_method === 'cash') {
                $applyDesc = 'تطبيق معدل لـ ' . $voucher->type->label() . ' رقم: ' . $voucher->voucher_no;
                if ($voucher->type === VoucherType::DEPOSIT) {
                    $safeService->deposit($voucher->amount, $voucher, $voucher->shift_id, $applyDesc);
                } else {
                    $safeService->withdraw($voucher->amount, $voucher, $voucher->shift_id, $applyDesc);
                }
            }

            DB::commit();

            return new VoucherResource($voucher->load(['shift', 'user']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء تعديل السند: ' . $e->getMessage()], 422);
        }
    }

    /**
     * حذف السند (وإرجاع الأموال لوضعها الطبيعي)
     */
    public function destroy(Voucher $voucher, SafeService $safeService)
    {
        try {
            DB::beginTransaction();

            if ($voucher->payment_method === 'cash') {
                $cancelDesc = 'إلغاء وحذف ' . $voucher->type->label() . ' رقم: ' . $voucher->voucher_no;

                // إذا كان إيداعاً وحذفناه، نسحب المبلغ من الخزينة
                if ($voucher->type === VoucherType::DEPOSIT) {
                    $safeService->withdraw($voucher->amount, $voucher, $voucher->shift_id, $cancelDesc);
                }
                // إذا كان مصروفاً/سحباً وحذفناه، نعيد المبلغ للخزينة
                else {
                    $safeService->deposit($voucher->amount, $voucher, $voucher->shift_id, $cancelDesc);
                }
            }

            $voucher->delete();

            DB::commit();

            return response()->json(['message' => 'تم حذف السند وإجراء التسوية المالية للخزينة بنجاح.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء حذف السند: ' . $e->getMessage()], 422);
        }
    }
}
