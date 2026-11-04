@extends('layouts.app')

@section('title', 'Đọc tư liệu | OceanQuiz')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/quiz.css') }}">
@endpush

@section('content')
    @php
        $questionCount = $quizSet->questions->count();
        $maxTimeLimit = (int) ($quizSet->questions->max('time_limit') ?? 20);
        $paragraphs = preg_split('/\R/u', (string) $quizSet->reading_passage) ?: [];
        $remainingPercent = max(0, min(100, (int) round($remaining / \App\Models\QuizAttempt::READING_SECONDS * 100)));
        $remainingSeconds = max(0, (int) floor((float) $remaining));
        $remainingMinutes = intdiv($remainingSeconds, 60);
    @endphp

    <div class="container quiz-page">
        <div class="read-head">
            <p class="read-head__step">Bước 1/2 · Đọc tư liệu</p>
            <p class="read-head__time">Thời gian đọc tối đa: 3 phút</p>
        </div>

        <p class="read-head__time">Còn lại khi mở trang: {{ $remainingMinutes }} phút {{ $remainingSeconds % 60 }} giây</p>

        <div class="read-progress" aria-hidden="true">
            <div class="read-progress__fill" style="--from: {{ $remainingPercent }}%; --dur: {{ max(1, (int) ceil((float) $remaining)) }}s"></div>
        </div>

        <div class="read-grid">
            <div class="read-main">
                <p class="read-zone">@include('quiz.partials.zone-label', ['zone' => $quizSet->ocean_zone])</p>

                <h1 class="read-title">{{ $quizSet->title }}</h1>

                <div class="prose read-prose">
                    @foreach ($paragraphs as $paragraph)
                        @if (trim($paragraph) !== '')
                            <p>{{ trim($paragraph) }}</p>
                        @endif
                    @endforeach
                </div>
            </div>

            <aside class="read-aside" aria-label="Thông tin bộ đề">
                @include('quiz.partials.zone-image', ['quizSet' => $quizSet, 'variant' => 'read'])

                <ul class="read-facts">
                    <li class="read-fact">
                        <span class="read-fact__label">Số câu hỏi</span>
                        <span class="read-fact__value">{{ number_format($questionCount, 0, ',', '.') }}</span>
                    </li>
                    <li class="read-fact">
                        <span class="read-fact__label">Thời gian mỗi câu</span>
                        <span class="read-fact__value">{{ number_format($maxTimeLimit, 0, ',', '.') }} giây</span>
                    </li>
                    <li class="read-fact">
                        <span class="read-fact__label">Điểm tối đa mỗi câu</span>
                        <span class="read-fact__value">{{ number_format(1000, 0, ',', '.') }}</span>
                    </li>
                </ul>

                <div class="read-start">
                    <form method="POST" action="{{ route('quiz.start', $attempt) }}" class="read-cta">
                        @csrf
                        <button type="submit" class="btn btn--primary btn--block">Bắt đầu làm bài</button>
                    </form>

                    <p class="read-note">Hết thời gian đọc, trang sẽ tự chuyển sang phần câu hỏi.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection
