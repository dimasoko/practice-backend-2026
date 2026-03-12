<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['survey_id', 'text', 'type', 'order'];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function isChoiceType(): bool
    {
        return in_array($this->type, ['single', 'multiple']);
    }
}