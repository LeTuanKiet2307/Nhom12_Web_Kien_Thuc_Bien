<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttemptAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'selected_option_index',
        'is_correct',
        'time_taken',
        'points_earned',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'selected_option_index' => 'integer',
        'time_taken' => 'float',
        'points_earned' => 'integer',
    ];

    public function attempt()
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}