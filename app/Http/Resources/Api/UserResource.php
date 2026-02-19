<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'username' => $this->username,
            'email' => $this->email,
            'created_at' => $this->created_at->toDateTimeString(),

            // تحميل الأدوار فقط إذا تم طلبها مع العلاقة
            // `whenLoaded` يساعد في تجنب مشكلة N+1
            'roles' => RoleResource::collection($this->whenLoaded('roles')),

            // يمكننا أيضًا إضافة الصلاحيات المباشرة إذا احتجنا إليها
            // 'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
        ];
    }
}
