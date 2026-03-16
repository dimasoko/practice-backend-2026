<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/surveys/{survey}/questions",
     *     summary="Создать вопрос в опросе (только автор, черновик)",
     *     tags={"Questions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="survey", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"text","type","order"},
     *             @OA\Property(property="text",  type="string", example="Какой ваш любимый язык программирования?"),
     *             @OA\Property(property="type",  type="string", enum={"single","multiple","text"}, example="single"),
     *             @OA\Property(property="order", type="integer", example=1, description="Порядок вопроса")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Вопрос создан",
     *         @OA\JsonContent(ref="#/components/schemas/Question")
     *     ),
     *     @OA\Response(response=403, description="Это не ваш опрос"),
     *     @OA\Response(response=422, description="Нельзя добавлять вопросы к опубликованному опросу")
     * )
     */
    public function store(Request $request, Survey $survey)
    {
        if ($survey->user_id !== auth('api')->id()) {
            abort(403, 'Это не ваш опрос');
        }
        if (!$survey->isDraft()) {
            abort(422, 'Нельзя добавлять вопросы к опубликованному опросу');
        }

        $data = $request->validate([
            'text'  => 'required|string',
            'type'  => 'required|in:single,multiple,text',
            'order' => 'required|integer|min:1',
        ]);

        $question = $survey->questions()->create($data);

        return response()->json($question, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/surveys/{survey}/questions/{question}",
     *     summary="Обновить вопрос (только автор, черновик)",
     *     tags={"Questions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="survey", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="question", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="text",  type="string", example="Обновленный текст вопроса"),
     *             @OA\Property(property="type",  type="string", enum={"single","multiple","text"}),
     *             @OA\Property(property="order", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Вопрос обновлен",
     *         @OA\JsonContent(ref="#/components/schemas/Question")
     *     ),
     *     @OA\Response(response=403, description="Это не ваш опрос"),
     *     @OA\Response(response=404, description="Вопрос не принадлежит опросу"),
     *     @OA\Response(response=422, description="Нельзя редактировать вопросы опубликованного опроса")
     * )
     */
    public function update(Request $request, Survey $survey, Question $question)
    {
        if ($survey->user_id !== auth('api')->id()) abort(403, 'Это не ваш опрос');
        if (!$survey->isDraft()) abort(422, 'Нельзя редактировать вопросы опубликованного опроса');
        if ($question->survey_id !== $survey->id) abort(404);

        $data = $request->validate([
            'text'  => 'sometimes|string',
            'type'  => 'sometimes|in:single,multiple,text',
            'order' => 'sometimes|integer|min:1',
        ]);

        $question->update($data);
        return response()->json($question);
    }

    /**
     * @OA\Delete(
     *     path="/api/surveys/{survey}/questions/{question}",
     *     summary="Удалить вопрос (только автор, черновик)",
     *     tags={"Questions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="survey", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="question", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Вопрос удален",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Это не ваш опрос"),
     *     @OA\Response(response=422, description="Нельзя удалять вопросы опубликованного опроса")
     * )
     */
    public function destroy(Survey $survey, Question $question)
    {
        if ($survey->user_id !== auth('api')->id()) abort(403, 'Это не ваш опрос');
        if (!$survey->isDraft()) abort(422, 'Нельзя удалять вопросы опубликованного опроса');
        if ($question->survey_id !== $survey->id) abort(404);

        $question->delete();
        return response()->json(['message' => 'Вопрос удалён']);
    }
}