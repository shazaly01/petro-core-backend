<?php

namespace App\Services;

use App\Models\Safe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class SafeService
{
    /**
     * إيداع مبلغ في الخزينة (وارد)
     */
    public function deposit(float $amount, $transactionable = null, ?int $shiftId = null, string $description = 'إيداع نقدي'): Safe
    {
        if ($amount <= 0) {
            throw new Exception('يجب أن يكون مبلغ الإيداع أكبر من الصفر.');
        }

        return DB::transaction(function () use ($amount, $transactionable, $shiftId, $description) {
            // الاعتماد على الخزينة رقم 1 كخزينة رئيسية للنظام
            $safe = Safe::findOrFail(1);

            $balanceAfter = $safe->balance + $amount;

            $safe->transactions()->create([
                'transaction_no' => $this->generateTransactionNo(),
                'user_id' => Auth::id(), // المشرف الذي قام بالعملية
                'shift_id' => $shiftId,  // الوردية (إن وجدت)
                'type' => 'in',
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'transactionable_type' => $transactionable ? get_class($transactionable) : null,
                'transactionable_id' => $transactionable ? $transactionable->id : null,
                'description' => $description,
            ]);

            $safe->update(['balance' => $balanceAfter]);

            return $safe;
        });
    }

    /**
     * سحب مبلغ من الخزينة (صادر / مصروف / تصفير)
     */
    /**
     * سحب مبلغ من الخزينة (صادر / مصروف / تصفير / تسوية)
     */
    public function withdraw(float $amount, $transactionable = null, ?int $shiftId = null, string $description = 'سحب نقدي'): Safe
    {
        if ($amount <= 0) {
            throw new Exception('يجب أن يكون مبلغ السحب أكبر من الصفر.');
        }

        return DB::transaction(function () use ($amount, $transactionable, $shiftId, $description) {
            $safe = Safe::findOrFail(1);

            // 🛑 تم إيقاف هذا الشرط بناءً على مبدأ المرونة المحاسبية للسماح بالقيود العكسية
            // if ($safe->balance < $amount) {
            //     throw new Exception('الرصيد المتوفر في الخزينة لا يكفي لإتمام العملية.');
            // }

            $balanceAfter = $safe->balance - $amount;

            $safe->transactions()->create([
                'transaction_no' => $this->generateTransactionNo(),
                'user_id' => Auth::id(),
                'shift_id' => $shiftId,
                'type' => 'out',
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'transactionable_type' => $transactionable ? get_class($transactionable) : null,
                'transactionable_id' => $transactionable ? $transactionable->id : null,
                'description' => $description,
            ]);

            $safe->update(['balance' => $balanceAfter]);

            return $safe;
        });
    }

    /**
     * توليد رقم حركة فريد مكون من 18 رقم تماماً (يتوافق مع DECIMAL 18,0)
     */
    private function generateTransactionNo(): string
    {
        // السنة+الشهر+اليوم+الساعة+الدقيقة+الثانية = 14 رقم (مثال: 20260306123045)
        $timestamp = date('YmdHis');

        // توليد 4 أرقام عشوائية إضافية لضمان عدم التكرار في نفس الثانية
        $random = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return $timestamp . $random; // المجموع 18 رقم
    }
}
