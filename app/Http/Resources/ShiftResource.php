<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // الإضافة الجديدة: تصدير الاسم الواضح للوردية
            'name' => $this->name,

            'status' => $this->status, // open, closed

            // التواريخ بتنسيق واضح
            'start_at' => $this->start_at ? $this->start_at->format('Y-m-d H:i') : null,
            'end_at' => $this->end_at ? $this->end_at->format('Y-m-d H:i') : null,

            // بيانات المشرف
            'supervisor' => $this->whenLoaded('supervisor', function () {
                return [
                    'id' => $this->supervisor->id,
                    'name' => $this->supervisor->full_name ?? $this->supervisor->name,
                ];
            }),

            // الأرقام المالية (تظهر فقط للمدراء أو عند الإغلاق)
            'total_expected_cash' => (float) $this->total_expected_cash,
            'total_actual_cash' => (float) $this->total_actual_cash,
            'difference' => (float) $this->difference,

            // ملاحظات التسليم
            'handover_notes' => $this->handover_notes,

            // إحصائيات سريعة (مثلاً: عدد التكليفات في هذه الوردية)
            'assignments_count' => $this->whenCounted('assignments'),
        ];
    }
}
