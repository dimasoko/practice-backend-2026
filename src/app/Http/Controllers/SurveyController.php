<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
/**
 * @OA\Get(
 *     path="/api/surveys",
 *     summary="Список опросов с пагинацией и фильтрами",
 *     tags={"Surveys"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="mine",      in="query", description="Только мои опросы", @OA\Schema(type="boolean")),
 *     @OA\Parameter(name="status",    in="query", description="Фильтр по статусу", @OA\Schema(type="string", enum={"draft","published","closed"})),
 *     @OA\Parameter(name="sort",      in="query", description="Поле сортировки",   @OA\Schema(type="string", enum={"created_at","responses_count"})),
 *     @OA\Parameter(name="direction", in="query", description="Направление",       @OA\Schema(type="string", enum={"asc","desc"})),
 *     @OA\Parameter(name="page",      in="query", description="Страница",          @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Список опросов"),
 *     @OA\Response(response=401, description="Не авторизован")
 * )
 */
    public function index(Request $request)
    {
/**
 * @OA\Post(
 *     path="/api/surveys",
 *     summary="Создать новый опрос (только для author)",
 *     tags={"Surveys"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"title"},
 *             @OA\Property(property="title",       type="string", example="Опрос о Laravel"),
 *             @OA\Property(property="description", type="string", example="Краткое описание")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Опрос создан"),
 *     @OA\Response(response=403, description="Недостаточно прав"),
 *     @OA\Response(response=422, description="Ошибка валидации")
 * )
 */
        $query = Survey::with('author:id,name')->withCount('responses');

        // только мои опросы
        if ($request->boolean('mine')) {
            $query->where('user_id', auth('api')->id());
        }

        // по статусу
        if ($request->filled('status')) {
            $request->validate(['status' => 'in:draft,published,closed']);
            $query->where('status', $request->status);
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir   = $request->get('direction', 'desc');

        if ($sortField === 'responses_count') {
            $query->orderBy('responses_count', $sortDir);
        } else {
            $query->orderBy('created_at', $sortDir);
        }

        $surveys = $query->paginate(10);

        return response()->json($surveys);
    }

    public function store(Request $request)
    {
    /**
     * @OA\Post(
     *     path="/api/surveys/{survey}/publish",
     *     summary="Опубликовать черновик",
     *     tags={"Surveys"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="survey", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Опрос опубликован"),
     *     @OA\Response(response=422, description="Уже не черновик"),
     *     @OA\Response(response=403, description="Чужой опрос")
     * )
     */
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
    /**
     * @OA\Get(
     *     path="/api/surveys/{survey}",
     *     summary="Получить опрос с вопросами и вариантами",
     *     tags={"Surveys"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="survey", in="path", required=true, @OA\Schema(type="integer"), description="ID опроса"),
     *     @OA\Response(
     *         response=200,
     *         description="Полная информация об опросе",
     *         @OA\JsonContent(ref="#/components/schemas/Survey")
     *     ),
     *     @OA\Response(response=401, description="Не авторизован"),
     *     @OA\Response(response=404, description="Опрос не найден")
     * )
     */
        $survey->load(['questions.options']);
        return response()->json($survey);
    }

    /**
     * @OA\Put(
     *     path="/api/surveys/{survey}",
     *     summary="Обновить опрос (только черновик и автор)",
     *     tags={"Surveys"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="survey", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title",       type="string", example="Обновленное название"),
     *             @OA\Property(property="description", type="string", example="Новое описание")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Опрос обновлен",
     *         @OA\JsonContent(ref="#/components/schemas/Survey")
     *     ),
     *     @OA\Response(response=403, description="Это не ваш опрос"),
     *     @OA\Response(response=422, description="Редактировать можно только черновик")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/surveys/{survey}",
     *     summary="Удалить опрос (только черновик и автор)",
     *     tags={"Surveys"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="survey", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Опрос удален",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Опрос удалён")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Это не ваш опрос"),
     *     @OA\Response(response=422, description="Редактировать можно только черновик")
     * )
     */
    public function destroy(Survey $survey)
    {
        $this->authorizeOwner($survey);
        $this->authorizeDraft($survey);

        $survey->delete();
        return response()->json(['message' => 'Опрос удалён'], 200);
    }

    public function publish(Survey $survey)
    {
    /**
     * @OA\Post(
     *     path="/api/surveys/{survey}/publish",
     *     summary="Опубликовать опрос (переведен из черновика)",
     *     tags={"Surveys"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="survey", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Опрос опубликован",
     *         @OA\JsonContent(ref="#/components/schemas/Survey")
     *     ),
     *     @OA\Response(response=403, description="Не автор опроса"),
     *     @OA\Response(response=422, description="Опубликовать можно только черновик")
     * )
     */
        $this->authorizeOwner($survey);

        if (!$survey->isDraft()) {
            return response()->json(['message' => 'Опубликовать можно только черновик'], 422);
        }

        $survey->update(['status' => 'published']);
        return response()->json($survey);
    }

    public function close(Survey $survey)
    {
    /**
     * @OA\Post(
     *     path="/api/surveys/{survey}/close",
     *     summary="Закрыть опрос (прекратить прием ответов)",
     *     tags={"Surveys"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="survey", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Опрос закрыт",
     *         @OA\JsonContent(ref="#/components/schemas/Survey")
     *     ),
     *     @OA\Response(response=403, description="Не автор опроса"),
     *     @OA\Response(response=422, description="Закрыть можно только опубликованный опрос")
     * )
     */
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