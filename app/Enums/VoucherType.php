<?php

namespace App\Enums;

enum VoucherType: string
{
    case DEPOSIT = 'deposit';       // إيداع / رصيد افتتاحي
    case EXPENSE = 'expense';       // مصروف تشغيلي
    case WITHDRAWAL = 'withdrawal'; // سحب / تصفير / توريد للبنك
    case SETTLEMENT = 'settlement'; // تسوية جردية (عجز أو زيادة)

    /**
     * جلب الاسم الوصفي باللغة العربية لعرضه في الواجهة
     */
    public function label(): string
    {
        return match($this) {
            self::DEPOSIT => 'إيداع نقدي',
            self::EXPENSE => 'مصروف تشغيلي',
            self::WITHDRAWAL => 'سحب / توريد بنكي',
            self::SETTLEMENT => 'تسوية مالية',
        };
    }
}
