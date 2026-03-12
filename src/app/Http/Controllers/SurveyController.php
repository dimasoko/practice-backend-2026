<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function index()
    {
        $surveys = Survey::with('author:id,name')
            ->withCount('responses')
            ->paginate(10);

        return response()->json($surveys);
    }

    public function store(Request $request)
    {
        $this->authorizeRole('author');

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $survey = Survey::create([
            'user_id'     => auth('api')->id(),
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'status'      => 'draft',
        ]);

        return response()->json($survey, 201);
    }

    public function show(Survey $survey)
    {
        $survey->load(['questions.options']);
        return response()->json($survey);
    }

    public function update(Request $request, Survey $survey)
    {
        $this->authorizeOwner($survey);
        $this->authorizeDraft($survey);

        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $survey->update($data);
        return response()->json($survey);
    }

    public function destroy(Survey $survey)
    {
        $this->authorizeOwner($survey);
        $this->authorizeDraft($survey);

        $survey->delete();
        return response()->json(['message' => 'Опрос удалён'], 200);
    }

    public function publish(Survey $survey)
    {
        $this->authorizeOwner($survey);

        if (!$survey->isDraft()) {
            return response()->json(['message' => 'Опубликовать можно только черновик'], 422);
        }

        $survey->update(['status' => 'published']);
        return response()->json($survey);
    }

    public function close(Survey $survey)
    {
        $this->authorizeOwner($survey);

        if (!$survey->isPublished()) {
            return response()->json(['message' => 'Закрыть можно только опубликованный опрос'], 422);
        }

        $survey->update(['status' => 'closed']);
        return response()->json($survey);
    }

    // --- вспомогательные методы ---

    private function authorizeRole(string $role)
    {
        if (auth('api')->user()->role !== $role) {
            abort(403, 'Недостаточно прав');
        }
    }

    private function authorizeOwner(Survey $survey)
    {
        if ($survey->user_id !== auth('api')->id()) {
            abort(403, 'Это не ваш опрос');
        }
    }

    private function authorizeDraft(Survey $survey)
    {
        if (!$survey->isDraft()) {
            abort(422, 'Редактировать можно только черновик');
        }
    }
}