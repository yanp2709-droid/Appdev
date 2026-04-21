<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::select('id', 'name', 'first_name', 'last_name', 'email', 'role', 'student_id', 'section', 'year_level', 'course', 'created_at');
        
$query->orderBy('role', 'desc');
        $query->orderBy('name');
        
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('student_id', 'like', "%$search%");
            });
        }
        
        $perPage = $request->get('per_page', 20);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }


    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Admin-only check already by middleware, but double-check
        if (! $request->user()->isAdmin()) {
            abort(403, 'Admin access required');
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password updated successfully',
            'user_id' => $user->id,
        ]);
    }
}

