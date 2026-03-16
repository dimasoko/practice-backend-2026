<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Option;
use Illuminate\Http\Request;

class OptionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/questions/{question}/options",
     *     summary="Создать вариант ответа (только для choice-типов: single, multiple)",
     *     tags={"Options"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="question", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"text"},
     *             @OA\Property(property="text", type="string", example="PHP")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Вариант ответа создан",
     *         @OA\JsonContent(ref="#/components/schemas/Option")
     *     ),
     *     @OA\Response(response=403, description="Это не ваш опрос"),
     *     @OA\Response(response=422, description="Нельзя добавить варианты к текстовому вопросу или опубликованному опросу")
     * )
     */
    public function store(Request $request, Question $question)
    {
        $survey = $question->survey;

        if ($survey->user_id !== auth('api')->id()) abort(403, 'Это не ваш опрос');
        if (!$survey->isDraft()) abort(422, 'Нельзя добавлять варианты к опубликованному опросу');

        // ключевая валидация: нельзя добавить вариант к текстовому вопросу
        if (!$question->isChoiceType()) {
            return response()->json([
                'message' => 'Нельзя добавить варианты ответа к текстовому вопросу'
            ], 422);
        }

        $data = $request->validate(['text' => 'required|string']);

        $option = $question->options()->create($data);

        return response()->json($option, 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/questions/{question}/options/{option}",
     *     summary="Удалить вариант ответа (только автор, черновик)",
     *     tags={"Options"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="question", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="option", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Вариант удален",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Вариант удалён")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Это не ваш опрос"),
     *     @OA\Response(response=404, description="Вариант не принадлежит вопросу"),
     *     @OA\Response(response=422, description="Нельзя удалять варианты опубликованного опроса")
     * )
     */
    public function destroy(Question $question, Option $option)
    {
        $survey = $question->survey;

        if ($survey->user_id !== auth('api')->id()) abort(403);
        if (!$survey->isDraft()) abort(422, 'Нельзя удалять варианты опубликованного опроса');
        if ($option->question_id !== $question->id) abort(404);

        $option->delete();
        return response()->json(['message' => 'Вариант удалён']);
    }
}