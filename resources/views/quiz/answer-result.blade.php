@extends('layouts.app')

@php
    $shapes = ['▲', '◆', '●', '■'];
    $selectedIndex = $answer->selected_option_index;
    $timedOut = $selectedIndex === null;
    $isCorrect = (bool) $answer->is_correct;
    $questionIds = $quizSet->questions()->orderBy('id')->pluck('id');
    $position = $questionIds->search($question->id);
    $number = $position === false ? 1 : (int) $position + 1;
    $total = max(1, $questionIds->count());
@endphp

@section('title', 'Kết quả câu ' . $number . ' / ' . $total . ': ' . $quizSet->title)

@section('content')

<div class="container">
    <div class="kahoot-arena">
        <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(12, 25, 48, 0.85); padding: 1rem 1.5rem; border-radius: 16px; border: 1px solid var(--border-glass);">
            <div style="font-weight: 700;">Câu <span style="color: var(--cyan-glow);">{{ $number }}</span> / {{ $total }}</div>
            <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.2rem; font-weight: 800; color: #4ade80;">
                🏆 {{ number_format((int) $attempt->score, 0, ',', '.') }} điểm
            </div>
        </div>

        <div class="kahoot-question-banner">{{ $question->question_text }}</div>

        <div style="padding: 1rem 1.5rem; border-radius: 14px; text-align: center; font-weight: 700; font-size: 1.15rem;
                    background: {{ $isCorrect ? 'rgba(34, 197, 94, 0.25)' : 'rgba(239, 68, 68, 0.25)' }};
                    border: 1px solid {{ $isCorrect ? '#4ade80' : '#f87171' }};
                    color: {{ $isCorrect ? '#4ade80' : '#fca5a5' }};">
            @if ($isCorrect)
                ✅ CHÍNH XÁC! +{{ number_format((int) $answer->points_earned, 0, ',', '.') }} điểm
                ({{ number_format((float) $answer->time_taken, 1, ',', '.') }} giây)
            @elseif ($timedOut)
                ⏱️ HẾT GIỜ! · không trả lời
            @else
                ❌ CHƯA CHÍNH XÁC! ({{ number_format((float) $answer->time_taken, 1, ',', '.') }} giây)
            @endif
        </div>

        <div class="kahoot-options-grid">
            @foreach ($question->options as $index => $option)
                @php
                    $isCorrectOption = $index === (int) $question->correct_option_index;
                    $isSelectedOption = ! $timedOut && $index === (int) $selectedIndex;
                @endphp
                <div class="kahoot-btn opt-{{ $index }}"
                     style="cursor: default; opacity: {{ $isCorrectOption || $isSelectedOption ? '1' : '0.45' }};
                            outline: {{ $isCorrectOption ? '4px solid #ffffff' : ($isSelectedOption ? '4px solid #fca5a5' : 'none') }};">
                    <span class="kahoot-shape">{{ $shapes[$index] ?? '◆' }}</span>
                    <span>{{ $option }}</span>
                    @if ($isCorrectOption)
                        <span style="margin-left: auto; font-size: 0.85rem; background: rgba(0,0,0,0.35); padding: 0.25rem 0.6rem; border-radius: 999px;">Đáp án đúng</span>
                    @elseif ($isSelectedOption)
                        <span style="margin-left: auto; font-size: 0.85rem; background: rgba(0,0,0,0.35); padding: 0.25rem 0.6rem; border-radius: 999px;">Bạn đã chọn</span>
                    @endif
                </div>
            @endforeach
        </div>

        @if (filled($question->explanation))
            <div class="bento-card" style="border-left: 3px solid var(--cyan-glow);">
                <strong>💡 Giải thích:</strong> {{ $question->explanation }}
            </div>
        @endif

        <div style="display: flex; justify-content: flex-end;">
            <a class="btn btn-primary" href="{{ $nextUrl }}">
                {{ $attempt->completed_at !== null ? 'Xem kết quả' : 'Câu tiếp theo' }}
            </a>
        </div>
    </div>
</div>
@endsection
