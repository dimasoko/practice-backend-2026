<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Survey API",
 *     version="1.0.0",
 *     description="REST API сервиса опросов и голосований",
 *     contact={
 *         "name": "API Support",
 *         "email": "support@survey.local"
 *     },
 *     license={
 *         "name": "MIT"
 *     }
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="JWT токен в формате: Authorization: Bearer {token}"
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     title="Пользователь",
 *     description="Модель пользователя системы",
 *     @OA\Property(property="id", type="integer", example=1, description="ID пользователя"),
 *     @OA\Property(property="name", type="string", example="Дима", description="Имя пользователя"),
 *     @OA\Property(property="email", type="string", format="email", example="dima@test.com"),
 *     @OA\Property(property="role", type="string", enum={"author","respondent"}, example="author", description="Роль пользователя"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Survey",
 *     title="Опрос",
 *     description="Опрос с вопросами",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1, description="ID автора"),
 *     @OA\Property(property="title", type="string", example="Опрос о языках программирования"),
 *     @OA\Property(property="description", type="string", example="Выберите ваш любимый язык"),
 *     @OA\Property(property="status", type="string", enum={"draft","published","closed"}, example="draft"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="questions", type="array", description="Вопросы опроса",
 *         @OA\Items(ref="#/components/schemas/Question")
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="Question",
 *     title="Вопрос",
 *     description="Вопрос в опросе",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="survey_id", type="integer", example=1),
 *     @OA\Property(property="text", type="string", example="Какой ваш любимый язык?"),
 *     @OA\Property(property="type", type="string", enum={"single","multiple","text"}, example="single", description="Тип вопроса: single (одиночный выбор), multiple (множественный выбор), text (текстовый)"),
 *     @OA\Property(property="order", type="integer", example=1, description="Порядок вопроса"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="options", type="array", description="Варианты ответов (только для single/multiple)",
 *         @OA\Items(ref="#/components/schemas/Option")
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="Option",
 *     title="Вариант ответа",
 *     description="Вариант ответа на вопрос",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="question_id", type="integer", example=1),
 *     @OA\Property(property="text", type="string", example="PHP"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Response",
 *     title="Прохождение опроса пользователем",
 *     description="Запись о прохождении пользователем опроса",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="survey_id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="answers", type="array", description="Ответы пользователя",
 *         @OA\Items(ref="#/components/schemas/Answer")
 *     ),
 *     @OA\Property(property="user", ref="#/components/schemas/User", description="Информация о пользователе")
 * )
 * 
 * @OA\Schema(
 *     schema="Answer",
 *     title="Ответ на вопрос",
 *     description="Ответ пользователя на конкретный вопрос",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="response_id", type="integer", example=1),
 *     @OA\Property(property="question_id", type="integer", example=1),
 *     @OA\Property(property="option_id", type="integer", nullable=true, example=5, description="ID выбранного варианта (null для текстовых ответов)"),
 *     @OA\Property(property="text_value", type="string", nullable=true, example="Другой язык", description="Текстовый ответ (null для выбора вариантов)"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="question", ref="#/components/schemas/Question"),
 *     @OA\Property(property="option", ref="#/components/schemas/Option", description="Выбранный вариант (если есть)")
 * )
 * 
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     title="Ошибка",
 *     @OA\Property(property="message", type="string", example="Ошибка валидации"),
 *     @OA\Property(property="errors", type="object", description="Детали ошибок валидации")
 * )
 */
abstract class Controller {}