<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Response;
use App\Models\Answer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

Log::info('Survey passed', ['survey_id' => $survey->id, 'user_id' => $user->id]);
Log::warning('Survey repeat attempt blocked', ['survey_id' => $survey->id, 'user_id' => $user->id]);

class ResponseController extends Controller
{
    public function store(Request $request, Survey $survey)
    {
        $user = auth('api')->user();

        // 1. Опрос должен быть опубликован
        if (!$survey->isPublished()) {
            return response()->json([
                'message' => 'Опрос недоступен для прохождения'
            ], 422);
        }

        // 2. Защита от повторного прохождения
        $alreadyPassed = Response::where('survey_id', $survey->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyPassed) {
            return response()->json([
                'message' => 'Вы уже проходили этот опрос'
            ], 422);
        }

        // 3. Валидация входных данных
        $request->validate([
            'answers'                => 'required|array',
            'answers.*.question_id'  => 'required|integer|exists:questions,id',
            'answers.*.option_ids'   => 'nullable|array',
            'answers.*.option_ids.*' => 'integer|exists:options,id',
            'answers.*.text_value'   => 'nullable|string',
        ]);

        $questions = $survey->questions()->with('options')->get()->keyBy('id');

        // 4. Валидация ответов по типу каждого вопроса
        foreach ($request->answers as $answerData) {
            $question = $questions->get($answerData['question_id']);

            if (!$question) {
                return response()->json([
                    'message' => "Вопрос {$answerData['question_id']} не принадлежит этому опросу"
                ], 422);
            }

            $error = $this->validateAnswer($question, $answerData);
            if ($error) {
                return response()->json(['message' => $error], 422);
            }
        }

        // 5. Сохраняем прохождение и ответы
        $response = Response::create([
            'survey_id' => $survey->id,
            'user_id'   => $user->id,
        ]);

        foreach ($request->answers as $answerData) {
            $question = $questions->get($answerData['question_id']);

            if ($question->type === 'text') {
                Answer::create([
                    'response_id' => $response->id,
                    'question_id' => $question->id,
                    'option_id'   => null,
                    'text_value'  => $answerData['text_value'],
                ]);
            } else {
                // single / multiple — сохраняем каждый выбранный вариант отдельной записью
                foreach ($answerData['option_ids'] as $optionId) {
                    Answer::create([
                        'response_id' => $response->id,
                        'question_id' => $question->id,
                        'option_id'   => $optionId,
                        'text_value'  => null,
                    ]);
                }
            }
        }

        return response()->json([
            'message'     => 'Ответы приняты',
            'response_id' => $response->id,
        ], 201);
    }

    // --- Валидация ответа по типу вопроса ---

    private function validateAnswer($question, array $data): ?string
    {
        $optionIds   = $data['option_ids'] ?? [];
        $textValue   = $data['text_value'] ?? null;
        $validOptIds = $question->options->pluck('id')->toArray();

        switch ($question->type) {

            case 'single':
                if (count($optionIds) !== 1) {
                    return "Вопрос «{$question->text}»: нужно выбрать ровно один вариант";
                }
                if (!in_array($optionIds[0], $validOptIds)) {
                    return "Вопрос «{$question->text}»: недопустимый вариант ответа";
                }
                break;

            case 'multiple':
                if (count($optionIds) < 1) {
                    return "Вопрос «{$question->text}»: нужно выбрать хотя бы один вариант";
                }
                foreach ($optionIds as $id) {
                    if (!in_array($id, $validOptIds)) {
                        return "Вопрос «{$question->text}»: недопустимый вариант ответа";
                    }
                }
                break;

            case 'text':
                if (empty(trim($textValue ?? ''))) {
                    return "Вопрос «{$question->text}»: текстовый ответ не может быть пустым";
                }
                break;
        }

        return null;
    }
}