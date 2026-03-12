<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\OptionController;

// Публичные маршруты
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Защищённые маршруты (нужен JWT)
Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Опросы
    Route::get('/surveys',                    [SurveyController::class, 'index']);
    Route::post('/surveys',                   [SurveyController::class, 'store']);
    Route::get('/surveys/{survey}',           [SurveyController::class, 'show']);
    Route::put('/surveys/{survey}',           [SurveyController::class, 'update']);
    Route::delete('/surveys/{survey}',        [SurveyController::class, 'destroy']);
    Route::post('/surveys/{survey}/publish',  [SurveyController::class, 'publish']);
    Route::post('/surveys/{survey}/close',    [SurveyController::class, 'close']);

    // Вопросы
    Route::post('/surveys/{survey}/questions',              [QuestionController::class, 'store']);
    Route::put('/surveys/{survey}/questions/{question}',    [QuestionController::class, 'update']);
    Route::delete('/surveys/{survey}/questions/{question}', [QuestionController::class, 'destroy']);

    // Варианты ответов
    Route::post('/questions/{question}/options',           [OptionController::class, 'store']);
    Route::delete('/questions/{question}/options/{option}',[OptionController::class, 'destroy']);
});
