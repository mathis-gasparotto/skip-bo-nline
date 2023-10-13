<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Throwable;
use function Sodium\compare;

class ResetInfosUsers extends Controller
{
    public function username (Request $request): JsonResponse{

        $request->validate(['username' => ['required', 'string', 'max:255','unique:'.User::class]]);
        $user = $request->user();
        try {
            $user->username = $request->username;
            $user->save();
            return response()->json([
                'success' => true,
                'user' =>  $user,
            ], 200);
        }catch (Throwable $e){
            return response()->json([
                'success' => false,
                'message' =>  $e->getMessage(),
            ], 500);
        }

    }
    public function email (Request $request): JsonResponse{

        $request->validate(
                [
                    'email' => ['required', 'string', 'email','unique:'.User::class],
                    'password' =>['required','string','max:255']
                ]);
        $userToUpdate = $request->user();
        $user = User::findOrFail($userToUpdate->id);
        if ($user && Hash::check($request->password, $user->password)) {
            try {
                $user->email = $request->email;
                $user->save();
                return response()->json([
                    'success' => true,
                    'user' =>  $user,
                ], 200);
            }catch (Throwable $e){
                return response()->json([
                    'success' => false,
                    'message' =>  $e->getMessage(),
                ], 500);
            }
        }else{
            return response()->json([
                'success' => false,
                'message' =>  "Password incorrect",
            ], 401);
        }


    }
    public function password (Request $request): JsonResponse{

        $request->validate(
            [
                'password' => ['required', 'string','confirmed', 'max:255'],
                'old_password' => ['required', Password::default()],
            ]);
        $userToUpdate = $request->user();
        $user = User::findOrFail($userToUpdate->id);
        if ($user && Hash::check($request->old_password, $user->password)) {
            try {
                $user->password = $request->password;
                $user->save();
                return response()->json([
                    'success' => true,
                    'user' =>  $user,
                ], 200);
            }catch (Throwable $e){
                return response()->json([
                    'success' => false,
                    'message' =>  $e->getMessage(),
                ], 500);
            }
        }else{
            return response()->json([
                'success' => false,
                'message' =>  "Password incorrect",
            ], 401);
        }


    }
}
