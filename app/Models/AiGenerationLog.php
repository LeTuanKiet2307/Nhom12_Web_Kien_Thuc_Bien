<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGenerationLog extends Model
{
    protected $guarded = ['id'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quizSet()
    {
        return $this->belongsTo(QuizSet::class);
    }
}
