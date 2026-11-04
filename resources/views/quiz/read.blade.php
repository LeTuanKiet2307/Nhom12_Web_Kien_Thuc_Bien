@extends('layouts.app')

@section('title', 'Đọc tư liệu: ' . $quizSet->title)

@section('content')
@php
    $questionCount = $quizSet->questions->count();
    $maxTimeLimit = (int) ($quizSet->questions->max('time_limit') ?? 20);
    $paragraphs = preg_split('/\R/u', (string) $quizSet->reading_passage) ?: [];
    $remainingSeconds = max(0, (int) floor((float) $remaining));
    $remainingMinutes = intdiv($remainingSeconds, 60);
@endphp

<style>
    .read-progress-fill {
        width: var(--from, 100%);
        animation: read-countdown var(--dur, 180s) linear forwards;
    }

    @keyframes read-countdown {
        from { width: var(--from, 100%); }
        to { width: 0; }
    }
</style>

<div class="container">
    <div class="reading-journal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="zone-depth-pill">BƯỚC 1 / 2 • ĐỌC TƯ LIỆU KHOA HỌC</span>
                <span style="margin-left: 0.65rem; font-size: 0.85rem; color: var(--text-muted);">🌊 {{ $quizSet->ocean_zone }}</span>
            </div>
            <div style="font-family: 'JetBrains Mono', monospace; font-size: 1.25rem; font-weight: 700; color: var(--cyan-glow);">
                ⏳ còn {{ $remainingMinutes }} phút {{ $remainingSeconds % 60 }} giây
            </div>
        </div>

        <div class="timer-progress-wrapper">
            <div class="timer-progress-bar read-progress-fill"
                 style="--from: {{ $remainingPercent }}%; --dur: {{ max(1, (int) ceil((float) $remaining)) }}s"></div>
        </div>

        <h1 style="font-size: 2rem; margin-top: 1.2rem;">{{ $quizSet->title }}</h1>

        <article class="journal-passage">
            @foreach ($paragraphs as $paragraph)
                @if (trim($paragraph) !== '')
                    <p>{{ trim($paragraph) }}</p>
                @endif
            @endforeach
        </article>

        <div class="stat-grid-2">
            <div class="stat-tile">
                <div style="font-size: 0.8rem; color: var(--text-muted);">SỐ CÂU HỎI</div>
                <div class="value">{{ $questionCount }}</div>
            </div>
            <div class="stat-tile">
                <div style="font-size: 0.8rem; color: var(--text-muted);">THỜI GIAN MỖI CÂU</div>
                <div class="value">{{ $maxTimeLimit }} giây</div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-top: 1px solid rgba(255,255,255,0.12); padding-top: 1.5rem; margin-top: 1.5rem;">
            <div style="font-size: 0.88rem; color: var(--text-muted);">
                📌 Hết thời gian đọc, trang sẽ tự chuyển sang phần câu hỏi.
            </div>
            <form method="POST" action="{{ route('quiz.start', $attempt) }}">
                @csrf
                <button type="submit" class="btn btn-primary" style="font-size: 1.05rem; padding: 0.9rem 1.8rem;">
                    ⚡ Bắt đầu làm bài
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
