<?php

// [Thành viên 2 - Contributor A] app/Http/Controllers/QuizController.php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizSet;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    /**
     * Vào Quiz: tiếp tục lượt đang dở của người dùng, hoặc tạo lượt mới từ một bộ đề ngẫu nhiên.
     */
    public function play()
    {
        $attempt = QuizAttempt::where('user_id', Auth::id())
            ->whereNull('completed_at')
            ->where('updated_at', '>=', now()->subMinutes(QuizAttempt::RESUME_WINDOW_MINUTES))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if ($attempt) {
            return redirect()->route('quiz.attempt', $attempt);
        }

        $quizSet = QuizSet::where('is_active', true)
            ->has('questions')
            ->inRandomOrder()
            ->first();

        if (! $quizSet) {
            return redirect()->route('dashboard')
                ->with('error', 'Chưa có bộ đề nào được xuất bản. Hãy quay lại sau.');
        }

        $attempt = QuizAttempt::create([
            'user_id' => Auth::id(),
            'quiz_set_id' => $quizSet->id,
            'score' => 0,
            'correct_count' => 0,
            'total_questions' => $quizSet->questions()->count(),
            'duration_seconds' => 0,
            'reading_started_at' => now(),
        ]);

        return redirect()->route('quiz.attempt', $attempt);
    }

    /**
     * Địa chỉ cố định của một lượt: hiển thị theo trạng thái lưu trong database.
     */
    public function show(QuizAttempt $attempt)
    {
        $this->guardAttempt($attempt);

        $state = DB::transaction(function () use ($attempt) {
            $attempt = QuizAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($attempt->completed_at !== null) {
                return $this->redirectState('quiz.result', [$attempt]);
            }

            // Bước đọc tư liệu
            if ($attempt->answering_started_at === null) {
                $remaining = QuizAttempt::READING_SECONDS - $this->elapsedSeconds($attempt->reading_started_at);

                if ($remaining > 0) {
                    return [
                        'type' => 'read',
                        'attempt' => $attempt,
                        'quizSet' => $this->quizSetOf($attempt),
                        'remaining' => $remaining,
                    ];
                }

                // Hết thời gian đọc thì chuyển sang bước làm bài
                $attempt->answering_started_at = now();
                $attempt->save();
            }

            $quizSet = $this->quizSetOf($attempt);
            $question = $this->nextQuestion($attempt, $quizSet);

            if (! $question) {
                $this->complete($attempt);

                return $this->redirectState('quiz.result', [$attempt]);
            }

            if ((int) $attempt->current_question_id !== (int) $question->id) {
                $attempt->current_question_id = $question->id;
                $attempt->question_started_at = now();
                $attempt->save();
            }

            $timeLimit = $this->timeLimitOf($question);
            $elapsed = $this->elapsedSeconds($attempt->question_started_at);

            if ($elapsed >= $timeLimit) {
                $answer = $this->recordAnswer($attempt, $question, null, (float) $timeLimit);

                return $this->redirectState('quiz.answer-result', [$attempt, $answer]);
            }

            return [
                'type' => 'question',
                'attempt' => $attempt,
                'quizSet' => $quizSet,
                'question' => $question,
                'number' => $quizSet->questions->search(fn (Question $item) => $item->id === $question->id) + 1,
                'total' => $quizSet->questions->count(),
                'remaining' => $timeLimit - $elapsed,
                'timeLimit' => $timeLimit,
            ];
        });

        if ($state['type'] === 'redirect') {
            return redirect()->route($state['route'], $state['params']);
        }

        if ($state['type'] === 'read') {
            return $this->readingResponse($state);
        }

        return $this->questionResponse($state);
    }

    /**
     * Bấm "Bắt đầu làm bài": kết thúc bước đọc sớm. Gọi lại nhiều lần không có tác dụng phụ.
     */
    public function start(QuizAttempt $attempt)
    {
        $this->guardAttempt($attempt);

        $completed = DB::transaction(function () use ($attempt) {
            $attempt = QuizAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($attempt->completed_at !== null) {
                return true;
            }

            if ($attempt->answering_started_at === null) {
                $attempt->answering_started_at = now();
                $attempt->save();
            }

            return false;
        });

        return redirect()->route($completed ? 'quiz.result' : 'quiz.attempt', $attempt);
    }

    /**
     * Nhận đáp án của câu hiện tại. Mọi con số do server tính, không tin client.
     */
    public function answer(Request $request, QuizAttempt $attempt)
    {
        $this->guardAttempt($attempt);

        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'selected_option_index' => ['required', 'integer', 'min:0'],
        ]);

        return DB::transaction(function () use ($attempt, $data) {
            $attempt = QuizAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($attempt->completed_at !== null) {
                return redirect()->route('quiz.result', $attempt);
            }

            if ($attempt->answering_started_at === null) {
                return redirect()->route('quiz.attempt', $attempt);
            }

            $questionId = (int) $data['question_id'];

            // Tab cũ, nút Back, hoặc câu không phải câu đang hiển thị
            if ($attempt->current_question_id === null || $questionId !== (int) $attempt->current_question_id) {
                return redirect()->route('quiz.attempt', $attempt);
            }

            $question = $attempt->quizSet->questions()->whereKey($questionId)->first();

            if (! $question) {
                abort(404);
            }

            // Bấm đúp: đã có đáp án của câu này thì trả về đúng trang phản hồi đó
            $existing = $attempt->answers()->where('question_id', $question->id)->first();

            if ($existing) {
                return redirect()->route('quiz.answer-result', [$attempt, $existing]);
            }

            $selectedOptionIndex = (int) $data['selected_option_index'];

            if ($selectedOptionIndex >= count($question->options ?? [])) {
                throw ValidationException::withMessages([
                    'selected_option_index' => 'Đáp án không hợp lệ.',
                ]);
            }

            $timeLimit = $this->timeLimitOf($question);
            $elapsed = $this->elapsedSeconds($attempt->question_started_at);

            if ($elapsed > $timeLimit + QuizAttempt::ANSWER_GRACE_SECONDS) {
                // Quá dung sai: ghi hết giờ, không nhận đáp án
                $answer = $this->recordAnswer($attempt, $question, null, (float) $timeLimit);
            } else {
                $answer = $this->recordAnswer($attempt, $question, $selectedOptionIndex, min($elapsed, $timeLimit));
            }

            return redirect()->route('quiz.answer-result', [$attempt, $answer]);
        });
    }

    /**
     * Phản hồi từng câu: đúng/sai, đáp án đúng, giải thích.
     */
    public function answerResult(QuizAttempt $attempt, QuizAttemptAnswer $answer)
    {
        $this->guardAttempt($attempt);

        abort_unless((int) $answer->quiz_attempt_id === (int) $attempt->id, 404);

        $question = $answer->question;
        $quizSet = $attempt->quizSet;
        $nextUrl = route('quiz.attempt', $attempt);

        return view('quiz.answer-result', compact('attempt', 'answer', 'question', 'quizSet', 'nextUrl'));
    }

    /**
     * Kết quả cả bài: điểm, số câu đúng, tổng thời gian và bảng từng câu.
     */
    public function result(QuizAttempt $attempt)
    {
        $this->guardAttempt($attempt);

        if ($attempt->completed_at === null) {
            return redirect()->route('quiz.attempt', $attempt);
        }

        $answers = $attempt->answers()
            ->with('question')
            ->orderBy('question_id')
            ->get();

        $quizSet = $attempt->quizSet;
        $mistakeCount = QuizAttemptAnswer::recentMistakesFor(Auth::user())->count();
        $durationSeconds = (int) $attempt->duration_seconds;

        return view('quiz.result', compact('attempt', 'answers', 'quizSet', 'mistakeCount', 'durationSeconds'));
    }

    /**
     * Ôn tập câu sai: gom câu sai của 5 lượt đã hoàn thành gần nhất.
     */
    public function mistakes()
    {
        $wrongAnswers = QuizAttemptAnswer::recentMistakesFor(Auth::user());

        return view('quiz.mistakes', compact('wrongAnswers'));
    }

    /**
     * Ghi nhận một câu trả lời (kể cả hết giờ) và cộng điểm vào lượt. Luôn gọi trong transaction đang giữ khóa.
     */
    private function recordAnswer(
        QuizAttempt $attempt,
        Question $question,
        ?int $selectedOptionIndex,
        float $secondsTaken
    ): QuizAttemptAnswer {
        $timeLimit = $this->timeLimitOf($question);
        $secondsTaken = min(max($secondsTaken, 0), $timeLimit);
        $isCorrect = $selectedOptionIndex !== null
            && $selectedOptionIndex === (int) $question->correct_option_index;

        $pointsEarned = 0;

        if ($isCorrect) {
            $speedFactor = 1 - ($secondsTaken / $timeLimit) * 0.5;
            $pointsEarned = (int) round(1000 * max($speedFactor, 0.5));
        }

        $answer = QuizAttemptAnswer::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_index' => $selectedOptionIndex,
            'is_correct' => $isCorrect,
            'time_taken' => $secondsTaken,
            'points_earned' => $pointsEarned,
        ]);

        $attempt->score += $pointsEarned;
        $attempt->correct_count += $isCorrect ? 1 : 0;
        $attempt->save();

        if ($attempt->answers()->count() >= $attempt->total_questions) {
            $this->complete($attempt);
        }

        return $answer;
    }

    /**
     * Chốt lượt làm bài và cộng điểm tích lũy cho người dùng, chỉ một lần. Luôn gọi trong transaction đang giữ khóa.
     */
    private function complete(QuizAttempt $attempt): void
    {
        if ($attempt->completed_at !== null) {
            return;
        }

        $answers = $attempt->answers()->get();

        $attempt->score = (int) $answers->sum('points_earned');
        $attempt->correct_count = $answers->where('is_correct', true)->count();
        $attempt->duration_seconds = (int) round((float) $answers->sum('time_taken'));
        $attempt->completed_at = now();
        $attempt->save();

        $attempt->user->increment('total_score', $attempt->score);
        $attempt->user->increment('quizzes_completed', 1);
    }

    /**
     * Câu chưa có đáp án đầu tiên của bộ đề, sắp theo id.
     */
    private function nextQuestion(QuizAttempt $attempt, QuizSet $quizSet): ?Question
    {
        $answeredIds = $attempt->answers()->pluck('question_id')->map(fn ($id) => (int) $id)->all();

        return $quizSet->questions->first(
            fn (Question $question) => ! in_array((int) $question->id, $answeredIds, true)
        );
    }

    /**
     * Bộ đề của lượt kèm danh sách câu hỏi sắp theo id.
     */
    private function quizSetOf(QuizAttempt $attempt): QuizSet
    {
        return QuizSet::with(['questions' => fn ($query) => $query->orderBy('id')])
            ->findOrFail($attempt->quiz_set_id);
    }

    /**
     * Số giây đã trôi kể từ một mốc thời gian (số thực, không âm).
     */
    private function elapsedSeconds(?CarbonInterface $since): float
    {
        if ($since === null) {
            return 0.0;
        }

        $milliseconds = now()->getPreciseTimestamp(3) - $since->getPreciseTimestamp(3);

        return max(0.0, $milliseconds / 1000);
    }

    private function timeLimitOf(Question $question): int
    {
        return max(1, (int) $question->time_limit);
    }

    private function redirectState(string $route, array $params = []): array
    {
        return ['type' => 'redirect', 'route' => $route, 'params' => $params];
    }

    private function readingResponse(array $state)
    {
        $remaining = (float) $state['remaining'];
        $percent = (int) round(max(0, min(1, $remaining / QuizAttempt::READING_SECONDS)) * 100);

        return response()->view('quiz.read', [
            'attempt' => $state['attempt'],
            'quizSet' => $state['quizSet'],
            'remaining' => $remaining,
            'remainingPercent' => $percent,
        ])->withHeaders($this->refreshHeaders($state['attempt'], $remaining));
    }

    private function questionResponse(array $state)
    {
        return response()->view('quiz.question', [
            'attempt' => $state['attempt'],
            'quizSet' => $state['quizSet'],
            'question' => $state['question'],
            'number' => $state['number'],
            'total' => $state['total'],
            'remaining' => (float) $state['remaining'],
            'timeLimit' => (int) $state['timeLimit'],
            'currentPoints' => (int) $state['attempt']->score,
        ])->withHeaders($this->refreshHeaders($state['attempt'], (float) $state['remaining']));
    }

    /**
     * Trang đọc và trang câu hỏi tự tải lại khi hết giờ; luôn hỏi lại server thay vì dùng cache.
     */
    private function refreshHeaders(QuizAttempt $attempt, float $remaining): array
    {
        return [
            'Refresh' => max(1, (int) ceil($remaining) + 1).';url='.route('quiz.attempt', $attempt),
            'Cache-Control' => 'no-store, max-age=0',
        ];
    }

    private function guardAttempt(QuizAttempt $attempt): void
    {
        abort_unless($attempt->user_id === Auth::id(), 404);
    }
}
