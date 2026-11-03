<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_set_id',
        'score',
        'correct_count',
        'total_questions',
        'duration_seconds',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quizSet()
    {
        return $this->belongsTo(QuizSet::class);
    }

    public function answers()
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }
}