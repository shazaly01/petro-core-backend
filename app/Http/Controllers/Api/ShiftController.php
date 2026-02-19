<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Http\Requests\Shift\StoreShiftRequest;
use App\Http\Requests\Shift\UpdateShiftRequest;
use App\Http\Resources\ShiftResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Shift::class, 'shift');
    }

    public function index()
    {
        // عرض الورديات مرتبة من الأحدث للأقدم
        $shifts = Shift::with('supervisor')->latest()->paginate(10);
        return ShiftResource::collection($shifts);
    }

   public function store(StoreShiftRequest $request)
{
    // 1. تجهيز البيانات التي تم التحقق منها
    $data = $request->validated();

    // 2. ضبط المشرف
    if (!isset($data['supervisor_id'])) {
        $data['supervisor_id'] = Auth::id();
    }

    // 3. ضبط وقت البدء
    if (!isset($data['start_at'])) {
        $data['start_at'] = now();
    }

    // 🛑 الإضافة المطلوبة هنا:
    // نحدد الحالة "مفتوحة" يدوياً لضمان ظهورها في الـ Resource فوراً
    $data['status'] = 'open';

    // 4. إنشاء الوردية
    $shift = Shift::create($data);

    // الآن الـ Resource سيجد قيمة 'open' داخل الـ $shift
    return new ShiftResource($shift);
}

    public function show(Shift $shift)
    {
        // عند عرض الوردية، نعرض المشرف والتكليفات المرتبطة بها
        $shift->load(['supervisor', 'assignments.user', 'assignments.nozzle']);
        return new ShiftResource($shift);
    }

    public function update(UpdateShiftRequest $request, Shift $shift)
    {
        $data = $request->validated();

        // منطق إغلاق الوردية
        if (isset($data['status']) && $data['status'] === 'closed' && $shift->status === 'open') {
            // إذا لم يحدد وقت الإغلاق، نضعه الآن
            if (!isset($data['end_at'])) {
                $data['end_at'] = now();
            }

            // هنا يمكن إضافة منطق للتحقق من أن جميع التكليفات (Assignments) مغلقة أيضاً
            // if ($shift->assignments()->where('status', 'active')->exists()) { ... }
        }

        $shift->update($data);

        return new ShiftResource($shift);
    }

    public function destroy(Shift $shift)
    {
        // حماية الورديات التي تحتوي على عمليات مالية
        if ($shift->assignments()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف وردية تحتوي على سجلات عمل ومبيعات.'
            ], 422);
        }

        $shift->delete();
        return response()->noContent();
    }
}
