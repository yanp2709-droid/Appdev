<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        $user = $request->user(); // get authenticated user

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // allow multiple roles separated by '|', e.g., 'admin|teacher'
        $allowedRoles = explode('|', $role);

        if (!in_array($user->role->name, $allowedRoles)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}