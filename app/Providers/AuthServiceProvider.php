<?php

namespace App\Providers;

// --- بداية الإضافات ---
use App\Models\User;
use App\Policies\UserPolicy;
use Spatie\Permission\Models\Role;
use App\Policies\RolePolicy;
// --- نهاية الإضافات ---

use App\Models\Company;
use App\Policies\CompanyPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // تسجيل الـ Policies التي كانت موجودة
        Company::class => CompanyPolicy::class,
        // --- بداية التعديل: تسجيل الـ Policies الجديدة ---
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        // --- نهاية التعديل ---
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // هذا الكود يمنح الـ Super Admin صلاحية كاملة على كل شيء
        // يجب أن يأتي بعد registerPolicies
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
