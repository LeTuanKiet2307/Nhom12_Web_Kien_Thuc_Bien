@extends('layouts.app')

@section('title', 'Ôn tập câu sai | OceanQuiz')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/learner.css') }}">
@endpush

@section('content')
<div class="container learner-page">
    <div class="page-head">
        <div class="page-head__text">
            <h1 class="page-title">Ôn tập câu sai</h1>
            <p class="page-subtitle">Các câu bạn trả lời sai hoặc hết giờ trong 5 lần làm bài gần nhất.</p>
        </div>
        <a class="btn btn--ghost" href="{{ route('dashboard') }}">Về bảng điều khiển</a>
    </div>

    @forelse ($wrongAnswers as $wrongItem)
        @php
            $question = $wrongItem->question;
            $quizSet = $question->quizSet ?? null;
            $options = $question->options ?? [];
            $zoneRaw = (string) ($quizSet->ocean_zone ?? '');
            $zoneLabel = $zoneRaw !== '' ? $zoneRaw : 'Đại dương';
            $zoneLower = \Illuminate\Support\Str::lower($zoneRaw);
            $zoneLevel = \Illuminate\Support\Str::contains($zoneLower, 'hố thẳm')
                ? 5
                : (\Illuminate\Support\Str::contains($zoneLower, 'đáy')
                    ? 4
                    : (\Illuminate\Support\Str::contains($zoneLower, 'nửa đêm')
                        ? 3
                        : (\Illuminate\Support\Str::contains($zoneLower, 'chạng vạng') ? 2 : 1)));
            $selectedIndex = $wrongItem->selected_option_index;
            $isTimeout = $selectedIndex === null;
        @endphp

        @if ($question)
            <article class="card mistake-card">
                <div class="mistake__head">
                    <span class="zone mistake__zone">
                        <span class="zone__dot zone__dot--{{ $zoneLevel }}" aria-hidden="true"></span>
                        {{ $zoneLabel }} · {{ $quizSet->title ?? 'Bộ đề không còn tồn tại' }}
                    </span>
                    <span class="mistake__time">Làm sai {{ $wrongItem->created_at->locale('vi')->diffForHumans() }}</span>
                </div>

                <h2 class="mistake__question">Câu {{ $loop->iteration }}: {{ $question->question_text }}</h2>

                <div class="mistake__answers">
                    @foreach ($options as $optionIndex => $option)
                        @php
                            $index = (int) $optionIndex;
                            $isCorrectOption = $index === (int) $question->correct_option_index;
                            $isChosenOption = ! $isTimeout && $index === (int) $selectedIndex;
                        @endphp

                        <div class="answer-option {{ $isCorrectOption ? 'is-correct' : ($isChosenOption ? 'is-wrong' : '') }}">
                            <span class="answer-option__letter" aria-hidden="true">{{ chr(65 + $index) }}</span>
                            <span class="answer-option__text">{{ $option }}</span>
                            @if ($isCorrectOption)
                                <span class="answer-option__tag">Đúng</span>
                            @elseif ($isChosenOption)
                                <span class="answer-option__tag">Bạn chọn</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($isTimeout)
                    <p class="mistake__timeout">Bạn không chọn đáp án (hết giờ)</p>
                @endif

                <div class="explanation">
                    <p class="explanation__title">Giải thích</p>
                    <p>{{ $question->explanation ?: 'Chưa có giải thích cho câu hỏi này.' }}</p>
                    <p class="explanation__source">Nguồn: Wikipedia tiếng Việt — {{ $quizSet->topic ?? $quizSet->title ?? 'Đại dương' }}</p>
                </div>
            </article>

            @if (! $loop->last)
                <p class="mistake-more">Còn {{ $wrongAnswers->count() - $loop->iteration }} câu nữa bên dưới.</p>
            @endif
        @endif
    @empty
        <div class="empty-state mistakes-empty">
            <p class="empty-state__title">Bạn chưa có câu trả lời sai nào</p>
            <p>Bạn chưa có câu trả lời sai nào trong 5 lượt gần nhất. Hãy tiếp tục làm bài để luyện tập thêm.</p>
            <a class="btn btn--primary btn--sm" href="{{ route('quiz.play') }}">Làm bài ngay</a>
        </div>
    @endforelse
</div>
@endsection
