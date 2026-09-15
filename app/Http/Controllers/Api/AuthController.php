<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Login with email/phone + password, return Sanctum token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($loginField, $request->login)
            ->where('status', 'active')
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials.', 401);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken(
            'mobile-app',
            ['*'],
            now()->addDays(30)
        )->plainTextToken;

        return $this->success([
            'token' => $token,
            'user'  => new UserResource($user->load('company')),
        ], 'Login successful.');
    }

    /**
     * Revoke the current token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * Return authenticated user profile.
     */
    public function me(Request $request)
    {
        return $this->success(
            new UserResource($request->user()->load('company')),
            'Profile retrieved.'
        );
    }

    /**
     * Update name, phone.
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
        ]);

        $user = $request->user();
        $user->update($request->only(['name', 'phone']));

        return $this->success(
            new UserResource($user->fresh()->load('company')),
            'Profile updated.'
        );
    }
}
