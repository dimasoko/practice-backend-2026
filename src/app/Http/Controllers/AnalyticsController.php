<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Answer;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/surveys/{survey}/analytics",
     *     summary="Получить аналитику по опросу (статистика ответов)",
     *     tags={"Analytics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="survey", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Аналитика опроса",
     *         @OA\JsonContent(
     *             @OA\Property(property="survey_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Опрос о языках"),
     *             @OA\Property(property="total_respondents", type="integer", example=42),
     *             @OA\Property(property="questions", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="question_id", type="integer"),
     *                     @OA\Property(property="question", type="string"),
     *                     @OA\Property(property="type", type="string", enum={"single","multiple","text"}),
     *                     @OA\Property(property="answers", type="array",
     *                         @OA\Items(type="string"),
     *                         description="Для text вопросов"
     *                     ),
     *                     @OA\Property(property="options", type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="option_id", type="integer"),
     *                             @OA\Property(property="option", type="string"),
     *                             @OA\Property(property="count", type="integer"),
     *                             @OA\Property(property="percent", type="number", example=23.8)
     *                         ),
     *                         description="Для choice вопросов"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Доступ запрещен - не автор опроса"),
     *     @OA\Response(response=404, description="Опрос не найден")
     * )
     */
    public function show(Survey $survey)
    {
        if ($survey->user_id !== auth('api')->id()) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $totalRespondents = $survey->responses()->count();

        $responseIds = $survey->responses()->pluck('id');

        $questions = $survey->questions()->with('options')->get();

        $questionsData = $questions->map(function ($question) use ($responseIds) {

            if ($question->type === 'text') {
                $textAnswers = Answer::whereIn('response_id', $responseIds)
                    ->where('question_id', $question->id)
                    ->pluck('text_value');

                return [
                    'question_id' => $question->id,
                    'question'    => $question->text,
                    'type'        => 'text',
                    'answers'     => $textAnswers,
                ];
            }

            $totalAnswersForQuestion = Answer::whereIn('response_id', $responseIds)
                ->where('question_id', $question->id)
                ->count();

            $options = $question->options->map(function ($option) use ($question, $responseIds, $totalAnswersForQuestion) {
                $count = Answer::whereIn('response_id', $responseIds)
                    ->where('question_id', $question->id)
                    ->where('option_id', $option->id)
                    ->count();

                $percent = $totalAnswersForQuestion > 0
                    ? round($count / $totalAnswersForQuestion * 100, 1)
                    : 0;

                return [
                    'option_id' => $option->id,
                    'option'    => $option->text,
                    'count'     => $count,
                    'percent'   => $percent,
                ];
            });

            return [
                'question_id' => $question->id,
                'question'    => $question->text,
                'type'        => $question->type,
                'options'     => $options,
            ];
        });

        return response()->json([
            'survey_id'         => $survey->id,
            'title'             => $survey->title,
            'total_respondents' => $totalRespondents,
            'questions'         => $questionsData,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/surveys/{survey}/export",
     *     summary="Экспортировать полные данные опроса",
     *     tags={"Analytics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="survey", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Полная структура опроса с ответами пользователей",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="status", type="string", enum={"draft","published","closed"}),
     *             @OA\Property(property="questions", type="array",
     *                 @OA\Items(ref="#/components/schemas/Question")
     *             ),
     *             @OA\Property(property="responses", type="array",
     *                 @OA\Items(ref="#/components/schemas/Response")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Доступ запрещен"),
     *     @OA\Response(response=404, description="Опрос не найден")
     * )
     */
    public function export(Survey $survey)
    {
        if ($survey->user_id !== auth('api')->id()) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $data = $survey->load([
            'questions.options',
            'responses.answers.question',
            'responses.answers.option',
            'responses.user:id,name,email',
        ]);

        return response()->json($data);
    }
}
