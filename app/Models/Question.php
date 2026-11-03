<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_set_id',
        'question_text',
        'options',
        'correct_option_index',
        'explanation',
        'time_limit',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_option_index' => 'integer',
        'time_limit' => 'integer',
    ];

    public function quizSet()
    {
        return $this->belongsTo(QuizSet::class);
    }
}