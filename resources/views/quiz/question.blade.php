@extends('layouts.app')

@section('title', 'Câu ' . $number . ' / ' . $total . ': ' . $quizSet->title)

@section('content')
@php
    $timeLimit = max(1, (int) $timeLimit);
    $remainingSeconds = max(0, (int) ceil((float) $remaining));
    $remainingPercent = max(0, min(100, (int) round($remainingSeconds / $timeLimit * 100)));
    $shapes = ['▲', '◆', '●', '■'];
@endphp

<style>
    .quiz-timer-fill {
        width: var(--from, 100%);
        animation: quiz-answer-countdown var(--dur, 20s) linear forwards;
    }

    @keyframes quiz-answer-countdown {
        from { width: var(--from, 100%); }
        to { width: 0; }
    }

    .kahoot-btn:has(.option-radio:checked) {
        outline: 4px solid #ffffff;
        filter: brightness(1.12);
    }

    .option-radio {
        position: absolute;
        inset: 0;
        opacity: 0;
        margin: 0;
        cursor: pointer;
    }

    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
</style>

<div class="container">
    <div class="kahoot-arena">
        <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(12, 25, 48, 0.85); padding: 1rem 1.5rem; border-radius: 16px; border: 1px solid var(--border-glass);">
            <div style="font-weight: 700;">
                Câu <span style="color: var(--cyan-glow);">{{ $number }}</span> / {{ $total }}
            </div>
            <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.2rem; font-weight: 800; color: #4ade80;">
                🏆 {{ number_format((int) $currentPoints, 0, ',', '.') }} điểm
            </div>
            <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.45rem; font-weight: 800; color: #fbbf24;">
                ⏱️ {{ $remainingSeconds }}s<span class="sr-only">còn {{ $remainingSeconds }} giây</span>
            </div>
        </div>

        <div class="timer-progress-wrapper" style="margin: 0;">
            <div class="timer-progress-bar quiz-timer-fill"
                 style="--from: {{ $remainingPercent }}%; --dur: {{ max(1, $remainingSeconds) }}s"></div>
        </div>

        <div class="kahoot-question-banner">
            {{ $question->question_text }}
        </div>

        <form method="POST" action="{{ route('quiz.answer', $attempt) }}">
            @csrf
            <input type="hidden" name="question_id" value="{{ $question->id }}">

            <div class="kahoot-options-grid">
                @foreach ($question->options as $index => $option)
                    <label class="kahoot-btn opt-{{ $index }}" style="position: relative;">
                        <input class="option-radio" type="radio" name="selected_option_index" value="{{ $index }}" required>
                        <span class="kahoot-shape">{{ $shapes[$index] ?? '◆' }}</span>
                        <span>{{ $option }}</span>
                    </label>
                @endforeach
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="font-size: 1.05rem; padding: 0.9rem 2rem;">
                    Trả lời
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
