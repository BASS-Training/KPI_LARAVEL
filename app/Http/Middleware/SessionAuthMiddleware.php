<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class SessionAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $userId = session('user_id') ?? session('user')?->id;

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (!$user) {
            session()->forget(['user_id', 'user']);
            return redirect()->route('login');
        }

        session([
            'user_id' => $user->id,
            'user' => $user,
        ]);
        
        return $next($request);
    }
}
