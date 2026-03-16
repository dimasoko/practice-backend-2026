<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Survey;
use App\Models\Question;
use App\Models\Option;
use App\Models\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyApiTest extends TestCase
{
    use RefreshDatabase;

    // Вспомогательный метод — создать автора и получить токен
    private function authAs(string $role = 'author'): array
    {
        $user = User::factory()->create(['role' => $role]);
        $token = auth('api')->login($user);
        return [$user, $token];
    }

    // Вспомогательный метод — создать опубликованный опрос с вопросами
    private function makePublishedSurvey(User $author): Survey
    {
        $survey = Survey::create([
            'user_id'     => $author->id,
            'title'       => 'Тест',
            'description' => null,
            'status'      => 'published',
        ]);

        $q1 = Question::create([
            'survey_id' => $survey->id,
            'text'      => 'Язык?',
            'type'      => 'single',
            'order'     => 1,
        ]);
        Option::create(['question_id' => $q1->id, 'text' => 'PHP']);
        Option::create(['question_id' => $q1->id, 'text' => 'JS']);

        $q2 = Question::create([
            'survey_id' => $survey->id,
            'text'      => 'Инструменты?',
            'type'      => 'multiple',
            'order'     => 2,
        ]);
        Option::create(['question_id' => $q2->id, 'text' => 'Git']);
        Option::create(['question_id' => $q2->id, 'text' => 'Docker']);

        Question::create([
            'survey_id' => $survey->id,
            'text'      => 'Комментарий?',
            'type'      => 'text',
            'order'     => 3,
        ]);

        return $survey->fresh(['questions.options']);
    }

    // --- ТЕСТ 1: Нельзя редактировать опубликованный опрос ---
    public function test_cannot_edit_published_survey(): void
    {
        [$author, $token] = $this->authAs('author');

        $survey = Survey::create([
            'user_id' => $author->id,
            'title'   => 'Опрос',
            'status'  => 'published',
        ]);

        $response = $this->withToken($token)
            ->putJson("/api/surveys/{$survey->id}", ['title' => 'Новый заголовок']);

        $response->assertStatus(422);
    }

    // --- ТЕСТ 2: Нельзя добавить вариант к текстовому вопросу ---
    public function test_cannot_add_option_to_text_question(): void
    {
        [$author, $token] = $this->authAs('author');

        $survey = Survey::create([
            'user_id' => $author->id,
            'title'   => 'Опрос',
            'status'  => 'draft',
        ]);
        $question = Question::create([
            'survey_id' => $survey->id,
            'text'      => 'Текстовый вопрос',
            'type'      => 'text',
            'order'     => 1,
        ]);

        $response = $this->withToken($token)
            ->postJson("/api/questions/{$question->id}/options", ['text' => 'Вариант']);

        $response->assertStatus(422);
    }

    // --- ТЕСТ 3: Защита от повторного прохождения ---
    public function test_cannot_submit_survey_twice(): void
    {
        [$author, $authorToken] = $this->authAs('author');
        [$respondent, $respondentToken] = $this->authAs('respondent');

        $survey = $this->makePublishedSurvey($author);
        $questions = $survey->questions;

        $answers = $this->buildAnswers($questions);

        // Первое прохождение — успех
        $this->withToken($respondentToken)
            ->postJson("/api/surveys/{$survey->id}/responses", ['answers' => $answers])
            ->assertStatus(201);

        // Второе прохождение — отказ
        $this->withToken($respondentToken)
            ->postJson("/api/surveys/{$survey->id}/responses", ['answers' => $answers])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Вы уже проходили этот опрос']);
    }

    // --- ТЕСТ 4: Нельзя пройти закрытый опрос ---
    public function test_cannot_submit_closed_survey(): void
    {
        [$author, $authorToken] = $this->authAs('author');
        [$respondent, $respondentToken] = $this->authAs('respondent');

        $survey = $this->makePublishedSurvey($author);
        $survey->update(['status' => 'closed']);

        $answers = $this->buildAnswers($survey->questions);

        $this->withToken($respondentToken)
            ->postJson("/api/surveys/{$survey->id}/responses", ['answers' => $answers])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Опрос недоступен для прохождения']);
    }

    // --- ТЕСТ 5: single-вопрос — нельзя выбрать два варианта ---
    public function test_single_question_rejects_multiple_options(): void
    {
        [$author, $authorToken] = $this->authAs('author');
        [$respondent, $respondentToken] = $this->authAs('respondent');

        $survey = $this->makePublishedSurvey($author);
        $singleQuestion = $survey->questions->where('type', 'single')->first();
        $optionIds = $singleQuestion->options->pluck('id')->toArray();

        $answers = [[
            'question_id' => $singleQuestion->id,
            'option_ids'  => $optionIds, // два варианта вместо одного
            'text_value'  => null,
        ]];

        $this->withToken($respondentToken)
            ->postJson("/api/surveys/{$survey->id}/responses", ['answers' => $answers])
            ->assertStatus(422);
    }

    // --- ТЕСТ 6: Успешное прохождение опроса ---
    public function test_can_submit_survey_successfully(): void
    {
        [$author, $authorToken] = $this->authAs('author');
        [$respondent, $respondentToken] = $this->authAs('respondent');

        $survey = $this->makePublishedSurvey($author);
        $answers = $this->buildAnswers($survey->questions);

        $this->withToken($respondentToken)
            ->postJson("/api/surveys/{$survey->id}/responses", ['answers' => $answers])
            ->assertStatus(201)
            ->assertJsonFragment(['message' => 'Ответы приняты']);
    }

    // Строит корректные ответы для всех вопросов опроса
    private function buildAnswers($questions): array
    {
        return $questions->map(function ($q) {
            if ($q->type === 'text') {
                return ['question_id' => $q->id, 'option_ids' => [], 'text_value' => 'Тестовый ответ'];
            }
            if ($q->type === 'single') {
                return ['question_id' => $q->id, 'option_ids' => [$q->options->first()->id], 'text_value' => null];
            }
            // multiple
            return ['question_id' => $q->id, 'option_ids' => [$q->options->first()->id], 'text_value' => null];
        })->toArray();
    }
}