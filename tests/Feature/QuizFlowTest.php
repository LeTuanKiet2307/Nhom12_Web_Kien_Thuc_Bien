<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizSet;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Kiểm thử tự động luồng Quiz theo đặc tả sửa lỗi (mục 9.2, T01–T25).
 *
 * Dùng DatabaseMigrations (không dùng RefreshDatabase) vì T25 cần gọi
 * migration down()/up() ngay trong tiến trình test.
 */
class QuizFlowTest extends TestCase
{
    use DatabaseMigrations;

    /** Dung sai cho header Refresh (giây). */
    private const REFRESH_TOLERANCE = 1;

    /** Thời gian mặc định mỗi câu trong bộ đề kiểm thử. */
    private const TIME_LIMIT = 20;

    protected function setUp(): void
    {
        parent::setUp();

        // Mọi test bắt đầu từ một mốc thời gian cố định.
        $this->freezeTime();
    }

    // ------------------------------------------------------------------
    // Hàm hỗ trợ
    // ------------------------------------------------------------------

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    /**
     * Tạo một bộ đề kèm danh sách câu hỏi.
     *
     * @return array{0: QuizSet, 1: \Illuminate\Support\Collection<int, Question>}
     */
    private function makeSet(int $questions = 3, int $limit = self::TIME_LIMIT, array $setAttributes = []): array
    {
        $quizSet = QuizSet::create(array_merge([
            'title' => 'Bộ đề kiểm thử '.uniqid(),
            'topic' => 'Đại dương',
            'ocean_zone' => 'Sunlit Zone (0m - 200m)',
            'reading_passage' => "Đoạn đọc thứ nhất về đại dương.\nĐoạn đọc thứ hai về sinh vật biển.",
            'source_url' => null,
            'is_active' => true,
            'created_by' => null,
        ], $setAttributes));

        $list = collect();

        for ($i = 1; $i <= $questions; $i++) {
            $list->push(Question::create([
                'quiz_set_id' => $quizSet->id,
                'question_text' => "Câu hỏi kiểm thử số {$i}?",
                'options' => ['Đáp án A', 'Đáp án B', 'Đáp án C', 'Đáp án D'],
                'correct_option_index' => 0,
                'explanation' => "Giải thích bí mật của câu {$i}.",
                'time_limit' => $limit,
            ]));
        }

        return [$quizSet, $list];
    }

    /**
     * Tạo lượt làm bài thật qua GET /quiz/play.
     *
     * @return array{0: QuizAttempt, 1: \Illuminate\Support\Collection<int, Question>, 2: QuizSet}
     */
    private function startAttempt(User $user, int $questions = 3, int $limit = self::TIME_LIMIT): array
    {
        [$quizSet, $list] = $this->makeSet($questions, $limit);

        $this->actingAs($user)->get(route('quiz.play'))->assertRedirect();

        return [$this->lastAttempt($user), $list, $quizSet];
    }

    private function lastAttempt(User $user): QuizAttempt
    {
        return QuizAttempt::where('user_id', $user->id)->orderByDesc('id')->firstOrFail();
    }

    private function openAttemptPage(User $user, QuizAttempt $attempt): TestResponse
    {
        return $this->actingAs($user)->get(route('quiz.attempt', $attempt));
    }

    /**
     * Tua hết giờ đọc rồi mở trang câu hỏi đầu tiên.
     */
    private function showFirstQuestion(User $user, QuizAttempt $attempt): TestResponse
    {
        $this->travel(QuizAttempt::READING_SECONDS + 1)->seconds();

        $response = $this->openAttemptPage($user, $attempt);
        $response->assertOk();

        return $response;
    }

    private function postAnswer(User $user, QuizAttempt $attempt, int $questionId, mixed $selected): TestResponse
    {
        return $this->actingAs($user)->post(route('quiz.answer', $attempt), [
            'question_id' => $questionId,
            'selected_option_index' => $selected,
        ]);
    }

    private function answerOf(QuizAttempt $attempt, int $questionId): ?QuizAttemptAnswer
    {
        return QuizAttemptAnswer::where('quiz_attempt_id', $attempt->id)
            ->where('question_id', $questionId)
            ->first();
    }

    /**
     * Kiểm tra header Refresh theo công thức ở mục 5.7.
     */
    private function assertRefreshHeader(TestResponse $response, float $expectedSeconds, QuizAttempt $attempt): void
    {
        $response->assertHeader('Refresh');

        $header = (string) $response->headers->get('Refresh');
        $this->assertMatchesRegularExpression('/^\d+\s*;\s*url=/i', $header, "Header Refresh sai định dạng: '{$header}'.");

        $seconds = (int) strtok($header, ';');
        $this->assertLessThanOrEqual(
            self::REFRESH_TOLERANCE,
            abs($seconds - $expectedSeconds),
            "Header Refresh = '{$header}', mong đợi khoảng {$expectedSeconds} giây (±1)."
        );
        $this->assertStringContainsString(
            route('quiz.attempt', $attempt),
            $header,
            'Header Refresh phải trỏ về đúng trang lượt làm bài.'
        );

        // Trang đọc và trang câu hỏi phải cấm cache (mục 5.7).
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl, 'Thiếu Cache-Control: no-store.');
        $this->assertStringContainsString('max-age=0', $cacheControl, 'Thiếu Cache-Control: max-age=0.');
    }

    /**
     * So sánh hai mốc thời gian (nhận cả Carbon lẫn chuỗi từ DB).
     */
    private function assertSameInstant(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertNotNull($expected, $message !== '' ? $message : 'Mốc thời gian mong đợi bị null.');
        $this->assertNotNull($actual, $message !== '' ? $message : 'Mốc thời gian thực tế bị null.');
        $this->assertTrue(
            Carbon::parse($expected)->equalTo(Carbon::parse($actual)),
            $message !== '' ? $message : 'Hai mốc thời gian phải trùng nhau.'
        );
    }

    /**
     * Kiểm tra một mốc trong DB nằm sát thời điểm hiện tại (sai số do cột datetime(3)).
     */
    private function assertInstantIsNow(mixed $actual, string $message = ''): void
    {
        $this->assertNotNull($actual, $message !== '' ? $message : 'Mốc thời gian bị null.');
        $diffMs = abs(Carbon::parse($actual)->getPreciseTimestamp(3) - now()->getPreciseTimestamp(3));
        $this->assertLessThanOrEqual(50, $diffMs, $message !== '' ? $message : 'Mốc thời gian phải là thời điểm hiện tại.');
    }

    /**
     * Tạo lượt làm bài trực tiếp trong DB (dùng cho dữ liệu lịch sử).
     */
    private function createAttempt(User $user, QuizSet $quizSet, array $attributes = []): QuizAttempt
    {
        $attempt = new QuizAttempt();
        $attempt->forceFill(array_merge([
            'user_id' => $user->id,
            'quiz_set_id' => $quizSet->id,
            'score' => 0,
            'correct_count' => 0,
            'total_questions' => 8,
            'duration_seconds' => 0,
            'reading_started_at' => now(),
            'answering_started_at' => now(),
            'current_question_id' => null,
            'question_started_at' => now(),
            'completed_at' => now(),
        ], $attributes));
        $attempt->save();

        return $attempt;
    }

    /**
     * Tạo một câu trả lời sai (hoặc hết giờ) trực tiếp trong DB.
     */
    private function createWrongAnswer(QuizAttempt $attempt, Question $question, bool $timedOut = false): QuizAttemptAnswer
    {
        return QuizAttemptAnswer::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_index' => $timedOut ? null : 1,
            'is_correct' => false,
            'time_taken' => $timedOut ? $question->time_limit : 5,
            'points_earned' => 0,
        ]);
    }

    // ------------------------------------------------------------------
    // T01–T25
    // ------------------------------------------------------------------

    public function test_t01_play_creates_exactly_one_attempt_and_redirects(): void
    {
        $user = $this->makeUser();
        [$quizSet] = $this->makeSet(3);

        $response = $this->actingAs($user)->get(route('quiz.play'));

        $this->assertSame(1, QuizAttempt::where('user_id', $user->id)->count());

        $attempt = $this->lastAttempt($user);
        $response->assertRedirect(route('quiz.attempt', $attempt));

        $this->assertSame($quizSet->id, (int) $attempt->quiz_set_id);
        $this->assertSame(3, (int) $attempt->total_questions);
        $this->assertSame(0, (int) $attempt->score);
        $this->assertSame(0, (int) $attempt->correct_count);
        $this->assertSame(0, (int) $attempt->duration_seconds);

        // Lượt mới tạo ở bước đọc tư liệu.
        $this->assertNotNull($attempt->reading_started_at);
        $this->assertNull($attempt->answering_started_at);
        $this->assertNull($attempt->completed_at);
    }

    public function test_t02_play_twice_keeps_same_unfinished_attempt(): void
    {
        $user = $this->makeUser();
        $this->makeSet(2);

        $first = $this->actingAs($user)->get(route('quiz.play'));
        $attempt = $this->lastAttempt($user);
        $first->assertRedirect(route('quiz.attempt', $attempt));

        $this->travel(5)->seconds();

        $second = $this->actingAs($user)->get(route('quiz.play'));
        $second->assertRedirect(route('quiz.attempt', $attempt));

        // Gọi lại không tạo thêm lượt (Q2).
        $this->assertSame(1, QuizAttempt::count());
        $this->assertSame(1, QuizAttempt::where('user_id', $user->id)->count());
    }

    public function test_t03_reading_refresh_header_counts_down_from_real_start(): void
    {
        $user = $this->makeUser();
        [$attempt] = $this->startAttempt($user, 3);

        $first = $this->openAttemptPage($user, $attempt);
        $first->assertOk();
        $this->assertRefreshHeader($first, QuizAttempt::READING_SECONDS + 1, $attempt);

        $attempt->refresh();
        $readingStartedAt = $attempt->reading_started_at;
        $this->assertNull($attempt->answering_started_at, 'Ở bước đọc thì chưa được ghi answering_started_at.');

        $this->travel(60)->seconds();

        $second = $this->openAttemptPage($user, $attempt);
        $second->assertOk();
        // Còn 120 giây, cộng 1 giây đệm → 121.
        $this->assertRefreshHeader($second, 121, $attempt);

        // Mốc bắt đầu đọc không được đặt lại (Q3).
        $attempt->refresh();
        $this->assertSameInstant($readingStartedAt, $attempt->reading_started_at, 'reading_started_at bị đặt lại khi tải lại trang.');
        $this->assertNull($attempt->answering_started_at);
    }

    public function test_t04_after_reading_timeout_first_question_is_opened_and_started(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 3);

        $this->travel(181)->seconds();

        $response = $this->openAttemptPage($user, $attempt);
        $response->assertOk();
        $response->assertSee($questions[0]->question_text);
        $response->assertDontSee($questions[1]->question_text);

        $attempt->refresh();
        $this->assertNotNull($attempt->answering_started_at, 'Hết giờ đọc phải ghi answering_started_at.');
        $this->assertSame($questions[0]->id, (int) $attempt->current_question_id);
        $this->assertNotNull($attempt->question_started_at, 'Hiển thị câu hỏi phải ghi question_started_at.');
        $this->assertSame(0, QuizAttemptAnswer::where('quiz_attempt_id', $attempt->id)->count());
    }

    public function test_t05_start_ends_reading_early_and_is_idempotent(): void
    {
        $user = $this->makeUser();
        [$attempt] = $this->startAttempt($user, 2);

        $this->travel(181)->seconds();

        $response = $this->actingAs($user)->post(route('quiz.start', $attempt));
        $response->assertRedirect(route('quiz.attempt', $attempt));

        $attempt->refresh();
        $this->assertNotNull($attempt->answering_started_at, 'POST quiz.start phải kết thúc bước đọc.');
        $startedAt = $attempt->answering_started_at;

        // Gọi lại sau vài giây: không đổi mốc bắt đầu làm bài.
        $this->travel(5)->seconds();
        $again = $this->actingAs($user)->post(route('quiz.start', $attempt));
        $again->assertRedirect(route('quiz.attempt', $attempt));

        $attempt->refresh();
        $this->assertSameInstant($startedAt, $attempt->answering_started_at, 'Gọi lại quiz.start không được đổi answering_started_at.');

        // Route chỉ nhận POST.
        $this->actingAs($user)->get(route('quiz.start', $attempt))->assertStatus(405);
    }

    public function test_t06_reloading_question_keeps_same_question_and_clock(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 2);

        $this->travel(181)->seconds();
        $first = $this->openAttemptPage($user, $attempt);
        $first->assertOk();
        $this->assertRefreshHeader($first, self::TIME_LIMIT + 1, $attempt);

        $attempt->refresh();
        $questionId = (int) $attempt->current_question_id;
        $questionStartedAt = $attempt->question_started_at;
        $this->assertSame($questions[0]->id, $questionId);

        $this->travel(7)->seconds();

        $second = $this->openAttemptPage($user, $attempt);
        $second->assertOk();
        $second->assertSee($questions[0]->question_text);
        // Còn 20 − 7 = 13 giây, cộng 1 giây đệm → 14.
        $this->assertRefreshHeader($second, 14, $attempt);

        $attempt->refresh();
        $this->assertSame($questionId, (int) $attempt->current_question_id, 'Tải lại không được đổi câu hiện tại (Q1).');
        $this->assertSameInstant($questionStartedAt, $attempt->question_started_at, 'question_started_at không được chạy lại khi tải lại (Q1).');
    }

    public function test_t07_correct_answer_after_five_seconds_scores_875(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 2);

        $this->showFirstQuestion($user, $attempt);
        $this->travel(5)->seconds();

        $response = $this->postAnswer($user, $attempt, $questions[0]->id, $questions[0]->correct_option_index);

        $answer = $this->answerOf($attempt, $questions[0]->id);
        $this->assertNotNull($answer, 'Phải ghi một dòng đáp án.');

        $this->assertSame(875, (int) $answer->points_earned);
        $this->assertTrue((bool) $answer->is_correct);
        $this->assertEqualsWithDelta(5.0, (float) $answer->time_taken, 0.05);

        $response->assertRedirect(route('quiz.answer-result', [$attempt, $answer]));

        $attempt->refresh();
        $this->assertSame(875, (int) $attempt->score);
        $this->assertSame(1, (int) $attempt->correct_count);
    }

    public function test_t08_client_supplied_time_and_points_are_ignored(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 3);

        $this->showFirstQuestion($user, $attempt);
        $this->travel(5)->seconds();

        $this->actingAs($user)->post(route('quiz.answer', $attempt), [
            'question_id' => $questions[0]->id,
            'selected_option_index' => $questions[0]->correct_option_index,
            'time_taken' => 0,
            'points' => 9999,
            'points_earned' => 9999,
            'duration_seconds' => 1,
        ])->assertSessionHasNoErrors();

        $answer = $this->answerOf($attempt, $questions[0]->id);
        $this->assertNotNull($answer);
        $this->assertSame(875, (int) $answer->points_earned, 'Điểm phải do server tính, không tin trường points/points_earned.');
        $this->assertEqualsWithDelta(5.0, (float) $answer->time_taken, 0.05, 'Thời gian phải do server tính, không tin time_taken.');

        $attempt->refresh();
        $this->assertSame(875, (int) $attempt->score);
        $this->assertSame(0, (int) $attempt->duration_seconds, 'duration_seconds do client gửi phải bị bỏ qua.');
    }

    public function test_t09_duplicate_answer_is_recorded_once(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 3);

        $this->showFirstQuestion($user, $attempt);
        $this->travel(5)->seconds();

        $this->postAnswer($user, $attempt, $questions[0]->id, $questions[0]->correct_option_index);
        $answer = $this->answerOf($attempt, $questions[0]->id);
        $this->assertNotNull($answer);

        $this->travel(4)->seconds();
        $second = $this->postAnswer($user, $attempt, $questions[0]->id, $questions[0]->correct_option_index);

        // Lần hai vẫn về trang phản hồi của đáp án đã có (M5).
        $second->assertRedirect(route('quiz.answer-result', [$attempt, $answer]));

        $this->assertSame(1, QuizAttemptAnswer::where('quiz_attempt_id', $attempt->id)->count());
        $this->assertSame(875, (int) $attempt->fresh()->score, 'Không được cộng điểm hai lần.');
    }

    public function test_t10_too_late_answer_is_recorded_as_timeout(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 2);

        $this->showFirstQuestion($user, $attempt);
        $this->travel(self::TIME_LIMIT + 3)->seconds();

        $this->postAnswer($user, $attempt, $questions[0]->id, $questions[0]->correct_option_index);

        $answer = $this->answerOf($attempt, $questions[0]->id);
        $this->assertNotNull($answer, 'Hết giờ phải ghi một dòng đáp án.');

        $this->assertNull($answer->selected_option_index, 'Hết giờ phải ghi selected_option_index = null (M2).');
        $this->assertFalse((bool) $answer->is_correct);
        $this->assertSame(0, (int) $answer->points_earned);
        $this->assertEqualsWithDelta((float) self::TIME_LIMIT, (float) $answer->time_taken, 0.05);
    }

    public function test_t11_answer_within_grace_window_is_accepted_at_full_time(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 2);

        $this->showFirstQuestion($user, $attempt);
        $this->travel(self::TIME_LIMIT + 1)->seconds();

        $this->postAnswer($user, $attempt, $questions[0]->id, $questions[0]->correct_option_index);

        $answer = $this->answerOf($attempt, $questions[0]->id);
        $this->assertNotNull($answer);
        $this->assertTrue((bool) $answer->is_correct, 'Trong dung sai mạng phải được chấp nhận đáp án.');
        $this->assertEqualsWithDelta((float) self::TIME_LIMIT, (float) $answer->time_taken, 0.05);
        $this->assertSame(500, (int) $answer->points_earned, 't = time_limit phải được 500 điểm.');
    }

    public function test_t12_expired_question_shows_answer_result_instead_of_next_question(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 3);

        $this->showFirstQuestion($user, $attempt);
        $this->travel(self::TIME_LIMIT + 1)->seconds();

        $response = $this->openAttemptPage($user, $attempt);

        $answer = $this->answerOf($attempt, $questions[0]->id);
        $this->assertNotNull($answer, 'Quá hạn phải tự ghi câu trả lời hết giờ (M7).');
        $this->assertNull($answer->selected_option_index);
        $this->assertFalse((bool) $answer->is_correct);
        $this->assertSame(0, (int) $answer->points_earned);

        // Không nhảy thẳng sang câu sau: vẫn redirect tới trang phản hồi câu này.
        $response->assertRedirect(route('quiz.answer-result', [$attempt, $answer]));

        $attempt->refresh();
        $this->assertSame($questions[0]->id, (int) $attempt->current_question_id);
        $this->assertSame(1, QuizAttemptAnswer::where('quiz_attempt_id', $attempt->id)->count());
    }

    public function test_t13_answer_for_other_question_is_rejected(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 3);

        $this->showFirstQuestion($user, $attempt);
        $this->travel(3)->seconds();

        $response = $this->postAnswer($user, $attempt, $questions[1]->id, 0);

        $response->assertRedirect(route('quiz.attempt', $attempt));

        $this->assertSame(0, QuizAttemptAnswer::where('quiz_attempt_id', $attempt->id)->count(), 'Không được ghi đáp án cho câu không phải câu hiện tại (M3).');

        $attempt->refresh();
        $this->assertSame($questions[0]->id, (int) $attempt->current_question_id, 'current_question_id không đổi khi request sai.');
    }

    public function test_t14_invalid_selected_option_returns_validation_error_not_500(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 3);

        $this->showFirstQuestion($user, $attempt);
        $this->travel(3)->seconds();

        // Trình duyệt gửi Referer là trang câu hỏi hiện tại.
        $this->from(route('quiz.attempt', $attempt));

        foreach ([-1, 4, 500, 'abc'] as $invalid) {
            $response = $this->actingAs($user)->post(route('quiz.answer', $attempt), [
                'question_id' => $questions[0]->id,
                'selected_option_index' => $invalid,
            ]);

            $this->assertSame(
                302,
                $response->getStatusCode(),
                'Giá trị selected_option_index = '.json_encode($invalid).' phải trả 302 (redirect kèm lỗi), không được 500 (Q8).'
            );
            $response->assertRedirect(route('quiz.attempt', $attempt));
            $response->assertSessionHasErrors('selected_option_index');

            $content = (string) $response->getContent();
            $this->assertStringNotContainsString('SQLSTATE', $content, 'Phản hồi không được lộ chi tiết SQL (Q8).');
        }

        $this->assertSame(
            0,
            QuizAttemptAnswer::where('quiz_attempt_id', $attempt->id)->count(),
            'Dữ liệu sai không được ghi vào bảng đáp án.'
        );
    }

    public function test_t15_completing_quiz_saves_totals_once_and_matches_answers(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 3);

        $this->travel(181)->seconds();
        $this->openAttemptPage($user, $attempt)->assertOk();

        foreach ($questions as $index => $question) {
            if ($index > 0) {
                // Mở trang câu kế tiếp để đồng hồ câu đó bắt đầu.
                $this->openAttemptPage($user, $attempt)->assertOk();
            }

            $this->travel(5)->seconds();

            $this->postAnswer($user, $attempt, $question->id, $question->correct_option_index)
                ->assertSessionHasNoErrors();
        }

        $attempt->refresh();
        $answers = QuizAttemptAnswer::where('quiz_attempt_id', $attempt->id)->get();

        $this->assertCount(3, $answers);
        $this->assertNotNull($attempt->completed_at, 'Trả lời hết câu phải đặt completed_at.');

        $expectedScore = (int) $answers->sum('points_earned');
        $expectedCorrect = $answers->filter(fn ($answer) => (bool) $answer->is_correct)->count();
        $expectedDuration = (int) round((float) $answers->sum('time_taken'));

        $this->assertSame($expectedScore, (int) $attempt->score, 'score phải bằng tổng points_earned.');
        $this->assertSame($expectedCorrect, (int) $attempt->correct_count, 'correct_count phải bằng số câu đúng.');
        $this->assertSame($expectedDuration, (int) $attempt->duration_seconds, 'duration_seconds phải bằng tổng time_taken (làm tròn).');

        $this->assertSame(875 * 3, (int) $attempt->score);
        $this->assertSame(3, (int) $attempt->correct_count);
        $this->assertSame(15, (int) $attempt->duration_seconds);

        $user->refresh();
        $this->assertSame(2625, (int) $user->total_score, 'users.total_score phải cộng đúng một lần.');
        $this->assertSame(1, (int) $user->quizzes_completed, 'users.quizzes_completed phải tăng đúng một lần.');

        // POST thêm sau khi đã xong: không đổi gì, chỉ redirect tới trang kết quả.
        $this->travel(3)->seconds();
        $extra = $this->postAnswer($user, $attempt, $questions[0]->id, 1);
        $extra->assertRedirect(route('quiz.result', $attempt));

        $attempt->refresh();
        $user->refresh();

        $this->assertSame(2625, (int) $attempt->score);
        $this->assertSame(3, QuizAttemptAnswer::where('quiz_attempt_id', $attempt->id)->count());
        $this->assertSame(1, (int) $user->quizzes_completed, 'Nộp thêm sau khi xong không được tăng quizzes_completed.');
        $this->assertSame(2625, (int) $user->total_score, 'Nộp thêm sau khi xong không được cộng total_score.');

        // Vào lại trang lượt đã xong: chuyển sang trang kết quả.
        $this->actingAs($user)->get(route('quiz.attempt', $attempt))
            ->assertRedirect(route('quiz.result', $attempt));
    }

    public function test_t16_result_of_unfinished_attempt_redirects_to_attempt(): void
    {
        $user = $this->makeUser();
        [$attempt] = $this->startAttempt($user, 2);

        $this->actingAs($user)->get(route('quiz.result', $attempt))
            ->assertRedirect(route('quiz.attempt', $attempt));

        // Vẫn còn ở bước đọc, lượt chưa xong.
        $this->assertNull($attempt->fresh()->completed_at);
    }

    public function test_t17_other_users_attempt_is_not_accessible(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();

        [$attempt, $questions] = $this->startAttempt($owner, 2);

        // Chủ sở hữu trả lời câu 1 để có một bản ghi đáp án.
        $this->showFirstQuestion($owner, $attempt);
        $this->travel(3)->seconds();
        $this->postAnswer($owner, $attempt, $questions[0]->id, 0);

        $answer = $this->answerOf($attempt, $questions[0]->id);
        $this->assertNotNull($answer);

        $answersBefore = QuizAttemptAnswer::where('quiz_attempt_id', $attempt->id)->count();
        $scoreBefore = (int) $attempt->fresh()->score;

        $this->actingAs($other);

        $this->get(route('quiz.attempt', $attempt))->assertNotFound();
        $this->post(route('quiz.start', $attempt))->assertNotFound();
        $this->post(route('quiz.answer', $attempt), [
            'question_id' => $questions[1]->id,
            'selected_option_index' => 0,
        ])->assertNotFound();
        $this->get(route('quiz.answer-result', [$attempt, $answer]))->assertNotFound();
        $this->get(route('quiz.result', $attempt))->assertNotFound();

        $this->assertSame($answersBefore, QuizAttemptAnswer::where('quiz_attempt_id', $attempt->id)->count());
        $this->assertSame($scoreBefore, (int) $attempt->fresh()->score);
    }

    public function test_t18_resume_after_session_loss_continues_same_attempt(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 2);

        $this->showFirstQuestion($user, $attempt);
        $this->travel(5)->seconds();
        $this->postAnswer($user, $attempt, $questions[0]->id, $questions[0]->correct_option_index);

        $attempt->refresh();
        $this->assertSame($questions[0]->id, (int) $attempt->current_question_id, 'Sau khi trả lời câu 1, câu 2 chưa được mở.');
        $question1StartedAt = $attempt->question_started_at;

        // Mất phiên đăng nhập rồi đăng nhập lại cùng user (Q4, M1).
        $this->flushSession();
        $this->actingAs($user);
        $this->travel(7)->seconds();

        $resume = $this->actingAs($user)->get(route('quiz.play'));
        $resume->assertRedirect(route('quiz.attempt', $attempt));

        // Không tạo thêm lượt.
        $this->assertSame(1, QuizAttempt::where('user_id', $user->id)->count());

        // Chỉ mở /quiz/play thì chưa được bắt đầu câu 2, đồng hồ câu 1 phải giữ nguyên.
        $attempt->refresh();
        $this->assertSame($questions[0]->id, (int) $attempt->current_question_id, 'Mở lại /quiz/play không được bắt đầu câu 2 (M1).');
        $this->assertSameInstant($question1StartedAt, $attempt->question_started_at, 'Đồng hồ câu 1 không được chạy lại (M1).');

        // Mở trang: phải ở câu 2 và đồng hồ câu 2 bắt đầu từ lúc mở trang này.
        $question2Page = $this->openAttemptPage($user, $attempt);
        $question2Page->assertOk();
        $question2Page->assertSee($questions[1]->question_text);

        $attempt->refresh();
        $this->assertSame($questions[1]->id, (int) $attempt->current_question_id);
        $this->assertInstantIsNow($attempt->question_started_at, 'Đồng hồ câu 2 phải bắt đầu từ lần mở trang câu 2 (M1).');

        $question2StartedAt = $attempt->question_started_at;
        $this->travel(3)->seconds();

        $reload = $this->openAttemptPage($user, $attempt);
        $reload->assertOk();
        // Còn 20 − 3 = 17 giây, cộng 1 giây đệm → 18.
        $this->assertRefreshHeader($reload, 18, $attempt);

        $attempt->refresh();
        $this->assertSame($questions[1]->id, (int) $attempt->current_question_id);
        $this->assertSameInstant($question2StartedAt, $attempt->question_started_at, 'Đồng hồ câu 2 không được chạy lại khi tải lại (M1).');
    }

    public function test_t19_stale_unfinished_attempt_is_abandoned_and_new_one_created(): void
    {
        $user = $this->makeUser();
        [$oldAttempt] = $this->startAttempt($user, 2);

        $oldId = $oldAttempt->id;

        // Quá RESUME_WINDOW_MINUTES (120 phút).
        $this->travel(121)->minutes();

        $response = $this->actingAs($user)->get(route('quiz.play'));

        $this->assertSame(2, QuizAttempt::where('user_id', $user->id)->count(), 'Lượt dở quá hạn phải bị bỏ và tạo lượt mới (M6).');

        $newAttempt = $this->lastAttempt($user);
        $this->assertNotSame($oldId, $newAttempt->id);
        $response->assertRedirect(route('quiz.attempt', $newAttempt));

        // Lượt cũ vẫn còn trong DB nhưng không còn được tiếp tục.
        $this->assertDatabaseHas('quiz_attempts', ['id' => $oldId]);
    }

    public function test_t20_question_page_does_not_leak_answer_or_explanation(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 2);

        $response = $this->showFirstQuestion($user, $attempt);

        $question = $questions[0];

        $response->assertSee($question->question_text);
        $response->assertDontSee($question->explanation);
        $response->assertDontSee('correct_option_index');
    }

    public function test_t21_no_page_contains_javascript(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 2);

        // 1. Trang đọc tư liệu.
        $read = $this->openAttemptPage($user, $attempt);
        $read->assertOk();

        // 2. Trang câu hỏi.
        $this->travel(181)->seconds();
        $questionPage = $this->openAttemptPage($user, $attempt);
        $questionPage->assertOk();

        // 3. Trang phản hồi câu 1 (trả lời sai để trang ôn tập có dữ liệu).
        $this->travel(3)->seconds();
        $this->postAnswer($user, $attempt, $questions[0]->id, 1);
        $answer1 = $this->answerOf($attempt, $questions[0]->id);
        $this->assertNotNull($answer1);
        $answerResult = $this->actingAs($user)->get(route('quiz.answer-result', [$attempt, $answer1]));
        $answerResult->assertOk();

        // 4. Trả lời nốt câu 2 → xong lượt.
        $this->openAttemptPage($user, $attempt)->assertOk();
        $this->travel(2)->seconds();
        $this->postAnswer($user, $attempt, $questions[1]->id, $questions[1]->correct_option_index);

        $attempt->refresh();
        $this->assertNotNull($attempt->completed_at);

        $result = $this->actingAs($user)->get(route('quiz.result', $attempt));
        $result->assertOk();

        $mistakes = $this->actingAs($user)->get(route('quiz.mistakes'));
        $mistakes->assertOk();

        $pages = [
            'read' => $read,
            'question' => $questionPage,
            'answer-result' => $answerResult,
            'result' => $result,
            'mistakes' => $mistakes,
        ];

        foreach ($pages as $name => $response) {
            $response->assertDontSee('<script', false);

            $content = (string) $response->getContent();
            $this->assertDoesNotMatchRegularExpression(
                '/\son[a-z]+\s*=/i',
                $content,
                "Trang '{$name}' chứa thuộc tính sự kiện on…= (không được dùng JavaScript)."
            );
            $this->assertStringNotContainsString('fetch(', $content, "Trang '{$name}' dùng fetch(.");
            $this->assertStringNotContainsString('XMLHttpRequest', $content, "Trang '{$name}' dùng XMLHttpRequest.");
        }
    }

    public function test_question_page_keeps_kahoot_answer_structure(): void
    {
        $user = $this->makeUser();
        [$attempt, $questions] = $this->startAttempt($user, 2);

        $response = $this->showFirstQuestion($user, $attempt);
        $response->assertOk();

        $content = (string) $response->getContent();

        // Cấu trúc chọn đáp án giữ đúng kiểu Kahoot: 4 ô màu/shape, một ô là một lựa chọn.
        $this->assertSame(4, substr_count($content, 'class="kahoot-btn'), 'Trang câu hỏi phải có đúng 4 ô đáp án kiểu Kahoot.');
        foreach (['▲', '◆', '●', '■'] as $shape) {
            $this->assertStringContainsString($shape, $content, "Thiếu shape Kahoot '{$shape}'.");
        }
        $this->assertSame(
            4,
            substr_count($content, 'name="selected_option_index"'),
            'Mỗi ô Kahoot phải là một radio name="selected_option_index".'
        );
        $this->assertStringContainsString('method="POST"', $content, 'Form trả lời phải POST về server.');
        $this->assertStringContainsString(route('quiz.answer', $attempt), $content, 'Form phải trỏ đúng route quiz.answer.');
        $this->assertStringContainsString('name="question_id"', $content, 'Form phải gửi question_id để server đối chiếu.');
        $this->assertStringNotContainsString('<script', $content);
        $this->assertStringNotContainsString('onclick', $content);
    }

    public function test_t22_submit_route_is_removed(): void
    {
        $user = $this->makeUser();

        $this->assertFalse(Route::has('quiz.submit'), 'Route quiz.submit phải bị xóa (M8).');

        $status = $this->actingAs($user)->post('/quiz/submit', [])->getStatusCode();
        $this->assertContains($status, [404, 405], "POST /quiz/submit phải trả 404 hoặc 405, nhận {$status} (M8).");
    }

    public function test_t23_recent_mistakes_uses_latest_five_completed_attempts_unique_questions(): void
    {
        $user = $this->makeUser();
        [$quizSet, $questions] = $this->makeSet(8);

        // 7 lượt đã hoàn thành (completed_at tăng dần theo thời gian).
        $completed = [];
        for ($i = 1; $i <= 7; $i++) {
            $completed[$i] = $this->createAttempt($user, $quizSet, [
                'total_questions' => 8,
                'reading_started_at' => now()->subMinutes(300 - $i),
                'answering_started_at' => now()->subMinutes(300 - $i),
                'question_started_at' => now()->subMinutes(300 - $i),
                'completed_at' => now()->subMinutes(300 - $i),
            ]);
        }

        // Một lượt dở có câu sai: không được tính (M6).
        $unfinished = $this->createAttempt($user, $quizSet, [
            'total_questions' => 8,
            'completed_at' => null,
            'question_started_at' => now(),
            'updated_at' => now(),
        ]);

        // Hai lượt cũ nhất nằm ngoài 5 lượt gần nhất.
        $this->createWrongAnswer($completed[1], $questions[6]);
        $this->createWrongAnswer($completed[2], $questions[7]);

        // 5 lượt gần nhất: att3..att7.
        $this->createWrongAnswer($completed[3], $questions[2]);
        $this->createWrongAnswer($completed[3], $questions[0]);
        $this->createWrongAnswer($completed[4], $questions[3]);
        $this->createWrongAnswer($completed[4], $questions[1], true); // câu hết giờ
        $this->createWrongAnswer($completed[5], $questions[4]);
        $this->createWrongAnswer($completed[6], $questions[4]);       // trùng question_id
        $this->createWrongAnswer($completed[7], $questions[2]);       // trùng question_id
        $this->createWrongAnswer($completed[7], $questions[5]);

        // Câu sai của lượt dở cũng không được lọt vào.
        $this->createWrongAnswer($unfinished, $questions[7]);

        $recent = QuizAttemptAnswer::recentMistakesFor($user);

        // Chỉ 5 lượt gần nhất, bỏ trùng theo question_id, gồm câu hết giờ.
        $this->assertCount(6, $recent);
        $this->assertEqualsCanonicalizing(
            [
                $questions[0]->id,
                $questions[1]->id,
                $questions[2]->id,
                $questions[3]->id,
                $questions[4]->id,
                $questions[5]->id,
            ],
            $recent->pluck('question_id')->all(),
            'Phải là hợp các câu sai (bỏ trùng) của 5 lượt gần nhất.'
        );

        $allowedAttemptIds = [
            $completed[3]->id,
            $completed[4]->id,
            $completed[5]->id,
            $completed[6]->id,
            $completed[7]->id,
        ];
        foreach ($recent as $item) {
            $this->assertContains(
                (int) $item->quiz_attempt_id,
                $allowedAttemptIds,
                'recentMistakesFor không được lấy lượt ngoài 5 lượt đã hoàn thành gần nhất.'
            );
        }

        // Gồm câu hết giờ (selected_option_index null).
        $timeoutItem = $recent->firstWhere('question_id', $questions[1]->id);
        $this->assertNotNull($timeoutItem, 'Phải gồm cả câu hết giờ.');
        $this->assertNull($timeoutItem->selected_option_index);

        // Nhãn trên trang kết quả bằng đúng số mục trang Ôn tập.
        $resultPage = $this->actingAs($user)->get(route('quiz.result', $completed[7]));
        $resultPage->assertOk();
        $resultPage->assertSee('Ôn tập câu sai (6 câu)');

        $mistakesPage = $this->actingAs($user)->get(route('quiz.mistakes'));
        $mistakesPage->assertOk();
        $mistakesContent = (string) $mistakesPage->getContent();

        foreach (range(0, 5) as $index) {
            $this->assertStringContainsString(
                $questions[$index]->question_text,
                $mistakesContent,
                'Trang Ôn tập phải hiện câu sai '.($index + 1).'.'
            );
        }

        // Câu của lượt cũ / lượt dở không được hiện.
        $this->assertStringNotContainsString($questions[6]->question_text, $mistakesContent);
        $this->assertStringNotContainsString($questions[7]->question_text, $mistakesContent);

        // Số mục thực tế trên trang Ôn tập (mỗi mục có nhãn "Làm sai …").
        $this->assertSame(
            6,
            substr_count($mistakesContent, 'Làm sai'),
            'Số mục trang Ôn tập phải bằng số trên nhãn "Ôn tập câu sai (N câu)" (M9).'
        );
    }

    public function test_t24_mistakes_page_uses_vietnamese_relative_time(): void
    {
        $user = $this->makeUser();
        [$quizSet, $questions] = $this->makeSet(2);

        $attempt = $this->createAttempt($user, $quizSet, [
            'total_questions' => 2,
            'completed_at' => now(),
        ]);

        $answer = $this->createWrongAnswer($attempt, $questions[0]);

        // Đặt mốc sai cách đây 5 giây để nhãn thời gian có nội dung rõ ràng.
        DB::table('quiz_attempt_answers')
            ->where('id', $answer->id)
            ->update(['created_at' => now()->subSeconds(5)]);

        $response = $this->actingAs($user)->get(route('quiz.mistakes'));
        $response->assertOk();

        $content = (string) $response->getContent();

        $this->assertStringContainsString('trước', $content, 'Trang Ôn tập phải hiện thời gian tương đối tiếng Việt (Q9).');
        $this->assertStringNotContainsString('ago', $content, 'Trang Ôn tập không được hiện "ago" (Q9).');
    }

    public function test_t25_progress_migration_backfills_legacy_data_and_deduplicates_answers(): void
    {
        $migration = $this->loadProgressMigration();

        // Mô phỏng schema cũ bằng cách gọi down() của migration mới.
        $migration->down();

        $this->assertFalse(
            Schema::hasColumn('quiz_attempts', 'completed_at'),
            'down() phải bỏ cột completed_at để mô phỏng schema cũ.'
        );

        $user = $this->makeUser();
        [$quizSet, $questions] = $this->makeSet(2);

        $createdAt = now()->subMinutes(10);

        // Lượt làm bài theo SCHEMA CŨ (không có cột tiến trình).
        $attemptId = DB::table('quiz_attempts')->insertGetId([
            'user_id' => $user->id,
            'quiz_set_id' => $quizSet->id,
            'score' => 1000,
            'correct_count' => 1,
            'total_questions' => 2,
            'duration_seconds' => 7,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $answerBase = [
            'quiz_attempt_id' => $attemptId,
            'selected_option_index' => 1,
            'is_correct' => 0,
            'time_taken' => 5,
            'points_earned' => 0,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];

        // Hai dòng đáp án TRÙNG cặp (quiz_attempt_id, question_id).
        $keptId = DB::table('quiz_attempt_answers')->insertGetId(
            array_merge($answerBase, ['question_id' => $questions[0]->id])
        );
        $duplicateId = DB::table('quiz_attempt_answers')->insertGetId(
            array_merge($answerBase, ['question_id' => $questions[0]->id])
        );

        // Dòng của câu khác phải còn nguyên.
        $otherId = DB::table('quiz_attempt_answers')->insertGetId(
            array_merge($answerBase, [
                'question_id' => $questions[1]->id,
                'selected_option_index' => 0,
                'is_correct' => 1,
                'points_earned' => 1000,
            ])
        );

        $this->assertNotSame($keptId, $duplicateId);

        // Chạy migration trên dữ liệu cũ: không được ném lỗi.
        $migration->up();

        $this->assertTrue(Schema::hasColumn('quiz_attempts', 'completed_at'));
        $this->assertTrue(Schema::hasColumn('quiz_attempts', 'reading_started_at'));
        $this->assertTrue(Schema::hasColumn('quiz_attempts', 'answering_started_at'));
        $this->assertTrue(Schema::hasColumn('quiz_attempts', 'current_question_id'));
        $this->assertTrue(Schema::hasColumn('quiz_attempts', 'question_started_at'));

        // Mỗi cặp (lượt, câu) còn đúng 1 dòng, giữ id nhỏ nhất.
        $this->assertSame(
            1,
            DB::table('quiz_attempt_answers')
                ->where('quiz_attempt_id', $attemptId)
                ->where('question_id', $questions[0]->id)
                ->count(),
            'Migration phải gỡ dòng đáp án trùng.'
        );
        $this->assertTrue(DB::table('quiz_attempt_answers')->where('id', $keptId)->exists(), 'Phải giữ dòng có id nhỏ nhất.');
        $this->assertFalse(DB::table('quiz_attempt_answers')->where('id', $duplicateId)->exists(), 'Phải xóa dòng trùng có id lớn hơn.');
        $this->assertTrue(DB::table('quiz_attempt_answers')->where('id', $otherId)->exists(), 'Không được xóa dòng của câu khác.');

        // Lượt cũ: completed_at = created_at, answering_started_at = created_at.
        $legacy = DB::table('quiz_attempts')->where('id', $attemptId)->first();
        $this->assertNotNull($legacy);
        $this->assertNotNull($legacy->completed_at, 'Lượt cũ phải được đánh dấu đã hoàn thành.');
        $this->assertSameInstant($legacy->created_at, $legacy->completed_at);
        $this->assertSameInstant($legacy->created_at, $legacy->answering_started_at);

        // Insert lại dòng trùng phải vi phạm khóa duy nhất.
        $violated = false;

        try {
            DB::table('quiz_attempt_answers')->insert(
                array_merge($answerBase, ['question_id' => $questions[0]->id])
            );
        } catch (QueryException) {
            $violated = true;
        }

        $this->assertTrue($violated, 'Khóa duy nhất (quiz_attempt_id, question_id) chưa được tạo.');
    }

    /**
     * Tìm và nạp file migration thêm cột tiến trình vào quiz_attempts.
     */
    private function loadProgressMigration(): object
    {
        $candidates = glob(database_path('migrations/*_add_progress_to_quiz_attempts.php')) ?: [];

        if ($candidates === []) {
            // Tên file có thể khác: tìm migration nào thao tác cột reading_started_at.
            foreach (glob(database_path('migrations/*.php')) ?: [] as $file) {
                if (str_contains((string) file_get_contents($file), 'reading_started_at')) {
                    $candidates[] = $file;
                }
            }
        }

        $this->assertNotEmpty($candidates, 'Không tìm thấy migration thêm cột tiến trình vào quiz_attempts.');

        return require $candidates[0];
    }
}
