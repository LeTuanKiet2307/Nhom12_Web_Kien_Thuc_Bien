<?php

// [Thành viên 2 - Contributor A] app/Http/Controllers/QuizController.php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuizController extends Controller
{
    /**
     * Bốc ngẫu nhiên 1 bộ đề đang kích hoạt kèm danh sách câu hỏi
     */
    public function play()
    {
        $quizSet = QuizSet::with('questions')
            ->where('is_active', true)
            ->has('questions')
            ->inRandomOrder()
            ->first();

        if (! $quizSet) {
            return redirect()->route('dashboard')
                ->with('error', 'No active quiz sets found in the sonar database.');
        }

        return view('quiz.play', compact('quizSet'));
    }

    /**
     * Nhận kết quả từ Kahoot UI qua AJAX, tính điểm và cập nhật Global Leaderboard
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'quiz_set_id' => ['required', Rule::exists('quiz_sets', 'id')->where('is_active', true)],
            'duration_seconds' => ['required', 'integer', 'min:1'],
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'exists:questions,id'],
            'answers.*.selected_option_index' => ['nullable', 'integer'],
            'answers.*.time_taken' => ['required', 'numeric', 'min:0'],
        ]);

        $user = Auth::user();
        $totalScore = 0;
        $correctCount = 0;
        $totalQuestions = count($validated['answers']);

        DB::beginTransaction();
        try {
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_set_id' => $validated['quiz_set_id'],
                'score' => 0,
                'correct_count' => 0,
                'total_questions' => $totalQuestions,
                'duration_seconds' => $validated['duration_seconds'],
            ]);

            foreach ($validated['answers'] as $ansData) {
                $question = Question::find($ansData['question_id']);
                $selectedIdx = $ansData['selected_option_index'];
                $timeTaken = min((float) $ansData['time_taken'], (float) $question->time_limit);

                $isCorrect = ($selectedIdx !== null && (int) $selectedIdx === (int) $question->correct_option_index);

                // Công thức tính điểm chuẩn Kahoot: Tối đa 1000 điểm/câu, giảm dần theo thời gian phản xạ (tối thiểu 500 điểm nếu đúng)
                $pointsEarned = 0;
                if ($isCorrect) {
                    $correctCount++;
                    $timeLimit = max($question->time_limit, 1);
                    $speedFactor = 1 - (($timeTaken / $timeLimit) * 0.5);
                    $pointsEarned = (int) round(1000 * max($speedFactor, 0.5));
                    $totalScore += $pointsEarned;
                }

                QuizAttemptAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_option_index' => $selectedIdx,
                    'is_correct' => $isCorrect,
                    'time_taken' => $timeTaken,
                    'points_earned' => $pointsEarned,
                ]);
            }

            $attempt->update([
                'score' => $totalScore,
                'correct_count' => $correctCount,
            ]);

            // Cập nhật điểm tích lũy và số bài đã hoàn thành của User cho Global Leaderboard
            $user->increment('total_score', $totalScore);
            $user->increment('quizzes_completed', 1);

            DB::commit();

            return response()->json([
                'success' => true,
                'score' => $totalScore,
                'correct_count' => $correctCount,
                'total_questions' => $totalQuestions,
                'redirect_url' => route('dashboard'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to save attempt: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tính năng Review Mistakes: Tự động gom các câu trả lời sai trong 5 lần làm bài gần nhất
     */
    public function mistakes()
    {
        $user = Auth::user();

        // Lấy ID của 5 lần làm bài gần nhất
        $lastFiveAttemptIds = QuizAttempt::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->pluck('id');

        // Lấy tất cả câu trả lời sai trong 5 lần đó, kèm thông tin Question và QuizSet
        $wrongAnswers = QuizAttemptAnswer::with(['question.quizSet', 'attempt'])
            ->whereIn('quiz_attempt_id', $lastFiveAttemptIds)
            ->where('is_correct', false)
            ->latest()
            ->get()
            ->unique('question_id')
            ->values();

        return view('quiz.mistakes', compact('wrongAnswers'));
    }
}
