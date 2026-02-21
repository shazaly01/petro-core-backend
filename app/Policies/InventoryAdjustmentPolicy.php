<?php

namespace App\Policies;

use App\Models\InventoryAdjustment;
use App\Models\User;

class InventoryAdjustmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('inventory_adjustment.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return $user->can('inventory_adjustment.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('inventory_adjustment.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        // 🛑 أمنياً ومحاسبياً: التعديل مسموح فقط لمن يملك صلاحية التعديل صراحة (مثل المدير)
        return $user->can('inventory_adjustment.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        // 🛑 الحذف مسموح فقط لمن يملك صلاحية الحذف (مثل المدير)
        return $user->can('inventory_adjustment.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return $user->can('inventory_adjustment.delete');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InventoryAdjustment $inventoryAdjustment): bool
    {
        return $user->can('inventory_adjustment.delete');
    }
}
