@extends('layouts.app')

@section('title', 'Đấu Trường Quiz: ' . $quizSet->title)

@section('content')
<div class="container">

    <!-- BƯỚC 1: GIAI ĐOẠN ĐỌC HIỂU TƯ LIỆU (3 PHÚT) -->
    <div id="readingPhaseContainer" class="reading-journal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="zone-depth-pill">BƯỚC 1 / 2 • ĐỌC TƯ LIỆU KHOA HỌC</span>
                <span style="margin-left: 0.65rem; font-size: 0.85rem; color: var(--text-muted);">🌊 {{ $quizSet->ocean_zone }}</span>
            </div>
            <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.25rem; font-weight: 700; color: var(--cyan-glow);">
                ⏳ <span id="readingTimeText">03:00</span>
            </div>
        </div>

        <div class="timer-progress-wrapper">
            <div id="readingProgressBar" class="timer-progress-bar"></div>
        </div>

        <h1 style="font-size: 2rem; margin-top: 1.2rem;">{{ $quizSet->title }}</h1>

        <article class="journal-passage">{{ $quizSet->reading_passage }}</article>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.12); padding-top: 1.5rem; margin-top: 1.5rem;">
            <div style="font-size: 0.88rem; color: var(--text-muted);">
                📌 Gồm <strong>{{ $quizSet->questions->count() }} câu hỏi tính điểm tốc độ</strong> dựa trên bài đọc này.
            </div>
            <button type="button" id="startQuizEarlyBtn" class="btn btn-primary" style="font-size: 1.05rem; padding: 0.9rem 1.8rem;">
                ⚡ Bắt Đầu Trả Lời Sớm →
            </button>
        </div>
    </div>

    <!-- BƯỚC 2: GIAO DIỆN THI KAHOOT -->
    <div id="kahootPhaseContainer" class="kahoot-arena" style="display: none;">

        <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(12, 25, 48, 0.85); padding: 1rem 1.5rem; border-radius: 16px; border: 1px solid var(--border-glass);">
            <div style="font-weight: 700;">
                Câu hỏi <span id="qCurrentIndex" style="color: var(--cyan-glow);">1</span> / {{ $quizSet->questions->count() }}
            </div>

            <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.45rem; font-weight: 800; color: #fbbf24;">
                ⏱️ <span id="questionTimerDisplay">20.0</span>s
            </div>

            <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.2rem; font-weight: 800; color: #4ade80;">
                🏆 <span id="liveScoreDisplay">0</span> điểm
            </div>
        </div>

        <div class="timer-progress-wrapper" style="margin: 0;">
            <div id="questionProgressBar" class="timer-progress-bar"></div>
        </div>

        <div class="kahoot-question-banner" id="kahootQuestionText">
            Đang tải câu hỏi...
        </div>

        <div id="instantFeedbackBanner" style="display: none; padding: 1rem 1.5rem; border-radius: 14px; text-align: center; font-weight: 700; font-size: 1.15rem;"></div>

        <div class="kahoot-options-grid">
            <button type="button" class="kahoot-btn opt-0" onclick="selectKahootAnswer(0)">
                <span class="kahoot-shape">▲</span>
                <span id="optText0">Đáp án A</span>
            </button>
            <button type="button" class="kahoot-btn opt-1" onclick="selectKahootAnswer(1)">
                <span class="kahoot-shape">◆</span>
                <span id="optText1">Đáp án B</span>
            </button>
            <button type="button" class="kahoot-btn opt-2" onclick="selectKahootAnswer(2)">
                <span class="kahoot-shape">●</span>
                <span id="optText2">Đáp án C</span>
            </button>
            <button type="button" class="kahoot-btn opt-3" onclick="selectKahootAnswer(3)">
                <span class="kahoot-shape">■</span>
                <span id="optText3">Đáp án D</span>
            </button>
        </div>
    </div>

    <!-- MÀN HÌNH TỔNG KẾT ĐIỂM -->
    <div id="quizCompletionBox" class="reading-journal-box" style="display: none; text-align: center;">
        <div style="font-size: 4rem; margin-bottom: 0.5rem;">🏆</div>
        <h2 style="font-size: 2.2rem;">Hoàn Thành Chuyến Thám Hiểm!</h2>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Kết quả của bạn đã được lưu và cập nhật lên Bảng Xếp Hạng Toàn Cầu.</p>

        <div class="stat-grid-2" style="max-width: 480px; margin: 1.5rem auto;">
            <div class="stat-tile">
                <div style="font-size: 0.8rem; color: var(--text-muted);">ĐIỂM ĐẠT ĐƯỢC</div>
                <div class="value" id="finalScoreValue">0</div>
            </div>
            <div class="stat-tile">
                <div style="font-size: 0.8rem; color: var(--text-muted);">SỐ CÂU ĐÚNG</div>
                <div class="value" id="finalAccuracyValue">0/0</div>
            </div>
        </div>

        <div style="display: flex; justify-content: center; gap: 1rem; margin-top: 2rem;">
            <a href="{{ route('dashboard') }}" class="btn btn-primary">📊 Xem Chi Tiết Đúng/Sai</a>
            <a href="{{ route('leaderboard') }}" class="btn btn-outline">🌍 Bảng Xếp Hạng</a>
        </div>
    </div>

</div>

<script>
    const quizSetId = {{ $quizSet->id }};
    const questions = @json($quizSet->questions);
    const submitUrl = "{{ route('quiz.submit') }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const READING_TOTAL_SECONDS = 180;
    let readingRemaining = READING_TOTAL_SECONDS;
    let readingInterval = null;

    function startReadingTimer() {
        readingInterval = setInterval(() => {
            readingRemaining--;
            const mins = String(Math.floor(readingRemaining / 60)).padStart(2, '0');
            const secs = String(readingRemaining % 60).padStart(2, '0');
            document.getElementById('readingTimeText').textContent = `${mins}:${secs}`;

            const pct = (readingRemaining / READING_TOTAL_SECONDS) * 100;
            document.getElementById('readingProgressBar').style.width = `${pct}%`;

            if (readingRemaining <= 0) {
                clearInterval(readingInterval);
                launchKahootPhase();
            }
        }, 1000);
    }

    document.getElementById('startQuizEarlyBtn').addEventListener('click', () => {
        clearInterval(readingInterval);
        launchKahootPhase();
    });

    let currentQIndex = 0;
    let liveTotalScore = 0;
    let questionStartTime = 0;
    let questionTimerInterval = null;
    let recordedAnswers = [];
    let quizStartTimestamp = 0;
    let isLocked = false;

    function launchKahootPhase() {
        document.getElementById('readingPhaseContainer').style.display = 'none';
        document.getElementById('kahootPhaseContainer').style.display = 'flex';
        quizStartTimestamp = Date.now();
        loadQuestion(0);
    }

    function loadQuestion(index) {
        if (index >= questions.length) {
            finishAndSubmitQuiz();
            return;
        }

        isLocked = false;
        currentQIndex = index;
        const q = questions[index];

        document.getElementById('qCurrentIndex').textContent = index + 1;
        document.getElementById('kahootQuestionText').textContent = q.question_text;
        document.getElementById('instantFeedbackBanner').style.display = 'none';

        const buttons = document.querySelectorAll('.kahoot-btn');
        buttons.forEach((btn, idx) => {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.outline = 'none';
            document.getElementById(`optText${idx}`).textContent = q.options[idx] || '';
        });

        const timeLimit = q.time_limit || 20;
        let timeLeft = timeLimit;
        questionStartTime = performance.now();

        document.getElementById('questionProgressBar').style.width = '100%';
        document.getElementById('questionTimerDisplay').textContent = timeLeft.toFixed(1);

        clearInterval(questionTimerInterval);
        questionTimerInterval = setInterval(() => {
            const elapsed = (performance.now() - questionStartTime) / 1000;
            timeLeft = Math.max(0, timeLimit - elapsed);
            document.getElementById('questionTimerDisplay').textContent = timeLeft.toFixed(1);
            document.getElementById('questionProgressBar').style.width = `${(timeLeft / timeLimit) * 100}%`;

            if (timeLeft <= 0) {
                clearInterval(questionTimerInterval);
                selectKahootAnswer(null);
            }
        }, 100);
    }

    function selectKahootAnswer(selectedIndex) {
        if (isLocked) return;
        isLocked = true;
        clearInterval(questionTimerInterval);

        const q = questions[currentQIndex];
        const timeLimit = q.time_limit || 20;
        const elapsedSeconds = Math.min((performance.now() - questionStartTime) / 1000, timeLimit);
        const isCorrect = (selectedIndex !== null && selectedIndex === Number(q.correct_option_index));

        let earnedPoints = 0;
        if (isCorrect) {
            const speedFactor = 1 - ((elapsedSeconds / timeLimit) * 0.5);
            earnedPoints = Math.round(1000 * Math.max(speedFactor, 0.5));
            liveTotalScore += earnedPoints;
            document.getElementById('liveScoreDisplay').textContent = liveTotalScore.toLocaleString();
        }

        const buttons = document.querySelectorAll('.kahoot-btn');
        buttons.forEach((btn, idx) => {
            btn.disabled = true;
            if (idx === Number(q.correct_option_index)) {
                btn.style.outline = '4px solid #ffffff';
                btn.style.opacity = '1';
            } else {
                btn.style.opacity = '0.32';
            }
        });

        const banner = document.getElementById('instantFeedbackBanner');
        banner.style.display = 'block';
        if (isCorrect) {
            banner.style.background = 'rgba(34, 197, 94, 0.25)';
            banner.style.border = '1px solid #4ade80';
            banner.style.color = '#4ade80';
            banner.innerHTML = `✅ CHÍNH XÁC! +${earnedPoints} Điểm Tốc Độ (Phản xạ: ${elapsedSeconds.toFixed(1)}s)`;
        } else {
            banner.style.background = 'rgba(239, 68, 68, 0.25)';
            banner.style.border = '1px solid #f87171';
            banner.style.color = '#fca5a5';
            banner.innerHTML = `❌ ${selectedIndex === null ? "HẾT GIỜ!" : "CHƯA CHÍNH XÁC!"} Đáp án đúng là: ${q.options[q.correct_option_index]}`;
        }

        recordedAnswers.push({
            question_id: q.id,
            selected_option_index: selectedIndex,
            time_taken: Number(elapsedSeconds.toFixed(2))
        });

        setTimeout(() => {
            loadQuestion(currentQIndex + 1);
        }, 2200);
    }

    async function finishAndSubmitQuiz() {
        document.getElementById('kahootPhaseContainer').style.display = 'none';
        document.getElementById('quizCompletionBox').style.display = 'block';

        const durationSeconds = Math.max(1, Math.round((Date.now() - quizStartTimestamp) / 1000));

        try {
            const response = await fetch(submitUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    quiz_set_id: quizSetId,
                    duration_seconds: durationSeconds,
                    answers: recordedAnswers
                })
            });

            const data = await response.json();
            if (data.success) {
                document.getElementById('finalScoreValue').textContent = data.score.toLocaleString() + ' điểm';
                document.getElementById('finalAccuracyValue').textContent = `${data.correct_count} / ${data.total_questions}`;
            }
        } catch (err) {
            console.error('Lỗi lưu kết quả:', err);
        }
    }

    startReadingTimer();
</script>
@endsection