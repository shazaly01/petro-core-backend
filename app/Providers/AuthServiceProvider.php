<?php

namespace App\Providers;

// 1. استدعاء الموديلات (Models)
use App\Models\Assignment;
use App\Models\Expense;
use App\Models\FuelType;
use App\Models\InventoryAdjustment;
use App\Models\Island;
use App\Models\Nozzle;
use App\Models\Pump;
use App\Models\Safe;
use App\Models\SafeTransaction;
use App\Models\Shift;
use App\Models\SupplyLog;
use App\Models\Tank;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use Spatie\Permission\Models\Role; // موديل الصلاحيات الخاص بحزمة Spatie

// 2. استدعاء السياسات (Policies)
use App\Policies\AssignmentPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\FuelTypePolicy;
use App\Policies\InventoryAdjustmentPolicy;
use App\Policies\IslandPolicy;
use App\Policies\NozzlePolicy;
use App\Policies\PumpPolicy;
use App\Policies\RolePolicy;
use App\Policies\SafePolicy;
use App\Policies\SafeTransactionPolicy;
use App\Policies\ShiftPolicy;
use App\Policies\SupplyLogPolicy;
use App\Policies\TankPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\UserPolicy;
use App\Policies\VoucherPolicy;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     * ربط كل موديل بالسياسة (Policy) الخاصة به
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Assignment::class          => AssignmentPolicy::class,
        Expense::class             => ExpensePolicy::class,
        FuelType::class            => FuelTypePolicy::class,
        InventoryAdjustment::class => InventoryAdjustmentPolicy::class,
        Island::class              => IslandPolicy::class,
        Nozzle::class              => NozzlePolicy::class,
        Pump::class                => PumpPolicy::class,
        Role::class                => RolePolicy::class,
        Safe::class                => SafePolicy::class,
        SafeTransaction::class     => SafeTransactionPolicy::class,
        Shift::class               => ShiftPolicy::class,
        SupplyLog::class           => SupplyLogPolicy::class,
        Tank::class                => TankPolicy::class,
        Transaction::class         => TransactionPolicy::class,
        User::class                => UserPolicy::class,
        Voucher::class             => VoucherPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // إعطاء صلاحيات مطلقة للسوبر أدمن (تجاوز جميع الفحوصات)
        // هذا السطر يضمن أن السوبر أدمن لن يواجه خطأ 403 أبداً
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
