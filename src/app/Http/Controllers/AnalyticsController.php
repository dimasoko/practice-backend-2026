<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Answer;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
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
