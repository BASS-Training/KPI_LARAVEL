<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends ApiController
{
    public function login(LoginRequest $request)
    {
        $key = 'api-login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $this->error(
                'Terlalu banyak percobaan login. Coba lagi setelah 15 menit.',
                ['retry_after' => RateLimiter::availableIn($key)],
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }

        $user = User::query()
            ->where('nip', $request->string('nip')->toString())
            ->where('nama', $request->string('nama')->toString())
            ->first();

        if (!$user) {
            RateLimiter::hit($key, 900);

            return $this->error(
                'NIP atau nama tidak valid.',
                ['attempts_left' => max(0, 5 - RateLimiter::attempts($key))],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        RateLimiter::clear($key);

        $expirationMinutes = (int) config('sanctum.expiration', 480);
        $cutoff = now()->subMinutes($expirationMinutes);

        $user->tokens()->where('created_at', '<', $cutoff)->delete();

        $activeTokens = $user->tokens()->orderBy('created_at')->get();
        $overflow = max(0, $activeTokens->count() - 2);

        if ($overflow > 0) {
            $activeTokens->take($overflow)->each->delete();
        }

        $deviceName = $request->input('device_name') ?: substr((string) $request->userAgent(), 0, 100) ?: 'unknown-device';
        $token = $user->createToken($deviceName)->plainTextToken;

        ActivityLog::record(
            $user,
            'auth.login',
            User::class,
            $user->id,
            ['device_name' => $deviceName],
            $request
        );

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => now()->addMinutes($expirationMinutes)->toISOString(),
            'user' => (new UserResource($user))->resolve(),
        ], 'Login berhasil.');
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $request->user()?->currentAccessToken()?->delete();

        ActivityLog::record(
            $user,
            'auth.logout',
            User::class,
            $user?->id,
            [],
            $request
        );

        return $this->success(null, 'Logout berhasil.');
    }

    public function me(Request $request)
    {
        return $this->resource(new UserResource($request->user()));
    }
}
