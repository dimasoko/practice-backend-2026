<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Регистрация нового пользователя",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name",     type="string",  example="Дима"),
     *             @OA\Property(property="email",    type="string",  example="dima@test.com"),
     *             @OA\Property(property="password", type="string",  example="password123"),
     *             @OA\Property(property="role",     type="string",  enum={"author","respondent"}, example="respondent", description="По умолчанию respondent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Пользователь создан и получен токен",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации - email занят или неивалидные данные")
     * )
     */
    public function register(Request $request)
    {
    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Авторизация — получить JWT токен",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email",    type="string", example="dima@test.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешная авторизация",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Неверный email или пароль")
     * )
     */
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
    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Выход — инвалидировать токен",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Успешный выход"),
     *     @OA\Response(response=401, description="Не авторизован")
     * )
     */
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Неверный email или пароль'], 401);
        }

        return response()->json(['token' => $token, 'user' => auth('api')->user()]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Выход — инвалидировать токен",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Успешный выход",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Выход выполнен")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Не авторизован")
     * )
     */
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Выход выполнен']);
    }

    /**
     * @OA\Get(
     *     path="/api/me",
     *     summary="Получить текущего авторизованного пользователя",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Данные пользователя",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(response=401, description="Не авторизован")
     * )
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }
}