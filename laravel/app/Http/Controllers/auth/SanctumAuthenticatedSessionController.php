<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class SanctumAuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     * @throws ValidationException
     */
    public function store(LoginRequest $request): JsonResponse
    {
        try {
            $request->validate([
                'username' => ['required', 'string', 'max:255'],
                'password' => ['required'],
            ]);
            $request->authenticate();

            $user = $request->user();

            return response()->json([
                'success' => true,
                'user' =>  $user,
            ], 200);
        }catch (Throwable $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 401);
        }

    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): JsonResponse

    {
        $user = $request->user();

        Auth::guard('api')->logout();
        $user->tokens()->delete();
        return new JsonResponse(['message' => 'Logged out']);
    }
}
