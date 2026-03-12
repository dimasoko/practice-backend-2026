<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role'     => 'sometimes|in:author,respondent',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'] ?? 'respondent',
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Неверный email или пароль'], 401);
        }

        return response()->json(['token' => $token, 'user' => auth('api')->user()]);
    }

    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Выход выполнен']);
    }

    public function me()
    {
        return response()->json(auth('api')->user());
    }
}