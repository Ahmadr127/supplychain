<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        // Support multiple permissions with | separator (user needs ANY one of them)
        $permissions = explode('|', $permission);
        foreach ($permissions as $perm) {
            if (Auth::user()->hasPermission(trim($perm))) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized action.');
    }
}
