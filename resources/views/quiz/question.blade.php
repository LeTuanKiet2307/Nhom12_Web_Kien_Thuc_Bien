@extends('layouts.app')

@section('title', 'Câu ' . $number . ' / ' . $total . ' | OceanQuiz')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/quiz.css') }}">
@endpush

@section('content')
    @php
        $timeLimit = max(1, (int) $timeLimit);
        $remainingSeconds = max(0, (int) ceil((float) $remaining));
        $remainingPercent = max(0, min(100, (int) round($remainingSeconds / $timeLimit * 100)));
    @endphp

    <div class="container quiz-page quiz-page--narrow">
        <div class="quiz-topbar">
            <p class="quiz-topbar__title">Câu {{ $number }} / {{ $total }}</p>
            <p class="quiz-topbar__score">
                Điểm hiện tại:
                <strong class="num">{{ number_format((int) $currentPoints, 0, ',', '.') }}</strong>
            </p>
        </div>

        <div class="quiz-timer">
            <p class="quiz-timer__label">Thời gian còn lại<span class="sr-only">: còn {{ $remainingSeconds }} giây</span></p>
            <div class="quiz-timer__track" aria-hidden="true">
                <div class="quiz-timer__fill" style="--from: {{ $remainingPercent }}%; --dur: {{ max(1, $remainingSeconds) }}s"></div>
            </div>
        </div>

        <h1 class="question-title">{{ $question->question_text }}</h1>

        <form method="POST" action="{{ route('quiz.answer', $attempt) }}" class="question-form">
            @csrf
            <input type="hidden" name="question_id" value="{{ $question->id }}">

            <fieldset class="option-list">
                <legend class="sr-only">Chọn một đáp án</legend>

                @foreach ($question->options as $index => $option)
                    <label class="option">
                        <input class="option__input" type="radio" name="selected_option_index" value="{{ $index }}"{{ $index === 0 ? ' required' : '' }}>
                        <span class="option__letter">{{ chr(65 + $index) }}</span>
                        <span class="option__text">{{ $option }}</span>
                    </label>
                @endforeach
            </fieldset>

            <div class="option-form__actions">
                <button type="submit" class="btn btn--primary">Trả lời</button>
            </div>
        </form>
    </div>
@endsection
