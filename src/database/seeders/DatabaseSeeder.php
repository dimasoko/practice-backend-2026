<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Survey;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Автор
        $author = User::create([
            'name'     => 'Автор Тестовый',
            'email'    => 'author@test.com',
            'password' => Hash::make('password'),
            'role'     => 'author',
        ]);

        // Респондент
        User::create([
            'name'     => 'Респондент Тестовый',
            'email'    => 'respondent@test.com',
            'password' => Hash::make('password'),
            'role'     => 'respondent',
        ]);

        // Опрос с вопросами разных типов
        $survey = Survey::create([
            'user_id'     => $author->id,
            'title'       => 'Тестовый опрос',
            'description' => 'Опрос для демонстрации',
            'status'      => 'draft',
        ]);

        // Вопрос 1 — одиночный выбор
        $q1 = Question::create([
            'survey_id' => $survey->id,
            'text'      => 'Ваш любимый язык программирования?',
            'type'      => 'single',
            'order'     => 1,
        ]);
        Option::insert([
            ['question_id' => $q1->id, 'text' => 'PHP',        'created_at' => now(), 'updated_at' => now()],
            ['question_id' => $q1->id, 'text' => 'JavaScript', 'created_at' => now(), 'updated_at' => now()],
            ['question_id' => $q1->id, 'text' => 'Python',     'created_at' => now(), 'updated_at' => now()],
        ]);

        // Вопрос 2 — множественный выбор
        $q2 = Question::create([
            'survey_id' => $survey->id,
            'text'      => 'Какие инструменты вы используете?',
            'type'      => 'multiple',
            'order'     => 2,
        ]);
        Option::insert([
            ['question_id' => $q2->id, 'text' => 'Git',     'created_at' => now(), 'updated_at' => now()],
            ['question_id' => $q2->id, 'text' => 'Docker',  'created_at' => now(), 'updated_at' => now()],
            ['question_id' => $q2->id, 'text' => 'Postman', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Вопрос 3 — текстовый
        Question::create([
            'survey_id' => $survey->id,
            'text'      => 'Что вы ожидаете от этого курса?',
            'type'      => 'text',
            'order'     => 3,
        ]);
    }
}