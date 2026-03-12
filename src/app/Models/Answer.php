<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = ['response_id', 'question_id', 'option_id', 'text_value'];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function option()
    {
        return $this->belongsTo(Option::class);
    }
}