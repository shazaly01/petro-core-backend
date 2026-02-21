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
        // 🛑 العزل التام: عرض الورديات الخاصة بالمستخدم (المشرف) الحالي فقط
        $shifts = Shift::with('supervisor')
            ->where('supervisor_id', Auth::id())
            ->latest()
            ->paginate(10);

        return ShiftResource::collection($shifts);
    }

    public function store(StoreShiftRequest $request)
    {
        // 🛑 الحماية 1: التحقق من عدم وجود وردية مفتوحة مسبقاً لنفس المستخدم
        $hasOpenShift = Shift::where('supervisor_id', Auth::id())
                             ->where('status', 'open')
                             ->exists();

        if ($hasOpenShift) {
            return response()->json([
                'message' => 'عفواً، لديك وردية مفتوحة بالفعل. يجب إغلاقها وتصفية عهدتها أولاً قبل فتح وردية جديدة.'
            ], 422);
        }

        // 1. تجهيز البيانات التي تم التحقق منها
        $data = $request->validated();

        // 2. ضبط المشرف إجبارياً من النظام (أمان تام)
        $data['supervisor_id'] = Auth::id();

        // 3. ضبط وقت البدء
        if (!isset($data['start_at'])) {
            $data['start_at'] = now();
        }

        // 4. تحديد الحالة "مفتوحة" يدوياً
        $data['status'] = 'open';

        // 5. إنشاء الوردية
        $shift = Shift::create($data);

        return new ShiftResource($shift);
    }

    public function show(Shift $shift)
    {
        // 🛑 التعديل هنا: جلب بيانات المضخة بدلاً من المسدس (حسب الهيكلة الجديدة)
        $shift->load(['supervisor', 'assignments.user', 'assignments.pump']);
        return new ShiftResource($shift);
    }

    public function update(UpdateShiftRequest $request, Shift $shift)
    {
        $data = $request->validated();

        // منطق إغلاق الوردية
        if (isset($data['status']) && $data['status'] === 'closed' && $shift->status === 'open') {

            // 🛑 الحماية 2: منع إغلاق الوردية إذا كان هناك تكليفات (مضخات) لم يتم إغلاقها!
            if ($shift->assignments()->where('status', 'active')->exists()) {
                 return response()->json([
                     'message' => 'لا يمكن إغلاق الوردية لوجود تكليفات (مضخات) قيد العمل. الرجاء إغلاق جميع التكليفات وتسويتها أولاً.'
                 ], 422);
            }

            // إذا لم يحدد وقت الإغلاق، نضعه الآن
            if (!isset($data['end_at'])) {
                $data['end_at'] = now();
            }
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
