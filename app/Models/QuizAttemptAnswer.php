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

    /**
     * Gom câu sai (kể cả câu hết giờ) của 5 lượt đã hoàn thành gần nhất, bỏ trùng theo câu hỏi.
     */
    public static function recentMistakesFor(User $user, int $attempts = 5)
    {
        // 5 lượt ĐÃ HOÀN THÀNH gần nhất
        $ids = QuizAttempt::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')->orderByDesc('id')
            ->limit($attempts)->pluck('id');

        return static::with(['question.quizSet', 'attempt'])
            ->whereIn('quiz_attempt_id', $ids)
            ->where('is_correct', false)          // gồm cả câu hết giờ
            ->orderByDesc('id')
            ->get()->unique('question_id')->values();
    }
}
