<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Option;
use Illuminate\Http\Request;

class OptionController extends Controller
{
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