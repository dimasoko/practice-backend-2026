<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
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

    public function destroy(Survey $survey, Question $question)
    {
        if ($survey->user_id !== auth('api')->id()) abort(403, 'Это не ваш опрос');
        if (!$survey->isDraft()) abort(422, 'Нельзя удалять вопросы опубликованного опроса');
        if ($question->survey_id !== $survey->id) abort(404);

        $question->delete();
        return response()->json(['message' => 'Вопрос удалён']);
    }
}