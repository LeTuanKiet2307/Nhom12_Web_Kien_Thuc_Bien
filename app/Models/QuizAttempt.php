<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    public const READING_SECONDS = 180;       // thời gian đọc tối đa

    public const ANSWER_GRACE_SECONDS = 2;    // dung sai mạng khi nhận đáp án

    public const RESUME_WINDOW_MINUTES = 120; // lượt dở quá hạn này coi như bỏ

    // Giữ phần mili giây cho các cột datetime(3): mốc giờ phải chính xác để tính thời gian đã trôi
    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $fillable = [
        'user_id',
        'quiz_set_id',
        'score',
        'correct_count',
        'total_questions',
        'duration_seconds',
        'reading_started_at',
        'answering_started_at',
        'current_question_id',
        'question_started_at',
        'completed_at',
    ];

    protected $casts = [
        'reading_started_at' => 'datetime',
        'answering_started_at' => 'datetime',
        'question_started_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function currentQuestion()
    {
        return $this->belongsTo(Question::class, 'current_question_id');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeUnfinished(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }
}
