<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index()
    {
        $users = User::with('roles')->latest()->paginate(15);
        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // --- [بداية التعديل هنا] ---
            // تحضير بيانات المستخدم مع التأكد من وجود قيمة لـ email
            $userData = [
                'full_name' => $validated['full_name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null, // <-- الحل: استخدم null إذا لم يكن البريد الإلكتروني موجودًا
                'password' => Hash::make($validated['password']),
            ];

            $user = User::create($userData);
            // --- [نهاية التعديل هنا] ---

            $user->assignRole($validated['roles']);
            DB::commit();

            return new UserResource($user->load('roles'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create user.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(User $user)
    {
        return new UserResource($user->load('roles'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // --- [بداية التعديل هنا] ---
            // تحضير بيانات المستخدم للتحديث
            $userData = [
                'full_name' => $validated['full_name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? $user->email, // استخدم البريد الإلكتروني القديم إذا لم يتم إرسال الجديد
            ];

            // تحديث كلمة المرور فقط إذا تم إرسالها
            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $user->update($userData);
            // --- [نهاية التعديل هنا] ---

            // استخدام syncRoles لتحديث الأدوار
            $user->syncRoles($validated['roles']);
            DB::commit();

            return new UserResource($user->fresh()->load('roles'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update user.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(User $user)
    {
        if ($user->hasRole('Super Admin')) {
            abort(Response::HTTP_FORBIDDEN, 'Cannot delete a Super Admin user.');
        }

        $user->delete();
        return response()->noContent();
    }
}
