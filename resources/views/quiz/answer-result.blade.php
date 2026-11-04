@extends('layouts.app')

@section('title', 'Kết quả từng câu | OceanQuiz')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/quiz.css') }}">
@endpush

@section('content')
    @php
        $questionIds = $attempt->quizSet->questions()->orderBy('id')->pluck('id');
        $position = $questionIds->search($question->id);
        $number = $position === false ? 1 : $position + 1;
        $total = max(1, $questionIds->count());
        $selectedIndex = $answer->selected_option_index;
        $timedOut = $selectedIndex === null;
        $isCorrect = (bool) $answer->is_correct;
        $pointsLabel = number_format((int) $answer->points_earned, 0, ',', '.');
        $timeLabel = number_format((float) $answer->time_taken, 1, ',', '.');
    @endphp

    <div class="container quiz-page">
        <div class="quiz-topbar">
            <p class="quiz-topbar__title">Câu {{ $number }} / {{ $total }}</p>
            <p class="quiz-topbar__score">
                Điểm hiện tại:
                <strong class="num">{{ number_format((int) $attempt->score, 0, ',', '.') }}</strong>
            </p>
        </div>

        <h1 class="answer-title">{{ $question->question_text }}</h1>

        <div class="answer-banner {{ $isCorrect ? 'answer-banner--correct' : 'answer-banner--wrong' }}">
            <p class="answer-banner__title">{{ $isCorrect ? 'Chính xác' : 'Chưa chính xác' }}</p>
            <p class="answer-banner__meta">
                @if ($timedOut)
                    Hết giờ · không trả lời
                @else
                    {{ $pointsLabel }} điểm · trả lời sau {{ $timeLabel }} giây
                @endif
            </p>
        </div>

        <div class="answer-layout">
            <div class="answer-main">
                <div class="option-list">
                    @foreach ($question->options as $index => $option)
                        @php
                            $isCorrectOption = $index === (int) $question->correct_option_index;
                            $isSelectedOption = ! $timedOut && $index === (int) $selectedIndex;
                            $optionClass = $isCorrectOption
                                ? 'option--correct'
                                : ($isSelectedOption ? 'option--wrong' : 'option--muted');
                        @endphp

                        <div class="option option--static {{ $optionClass }}">
                            <span class="option__letter">{{ chr(65 + $index) }}</span>
                            <span class="option__text">{{ $option }}</span>
                            @if ($isCorrectOption)
                                <span class="option__mark">Đáp án đúng</span>
                            @elseif ($isSelectedOption)
                                <span class="option__mark">Bạn đã chọn</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if (filled($question->explanation))
                    <div class="explanation">
                        <p class="explanation__title">Giải thích</p>
                        <p class="explanation__text">{{ $question->explanation }}</p>
                        <p class="explanation__source">Nguồn: Wikipedia tiếng Việt — {{ $quizSet->topic }}</p>
                    </div>
                @endif

                <div class="answer-actions">
                    <a class="btn btn--primary" href="{{ $nextUrl }}">{{ $attempt->completed_at !== null ? 'Xem kết quả' : 'Câu tiếp theo' }}</a>
                </div>
            </div>

            <aside class="answer-aside" aria-label="Ảnh minh hoạ">
                @include('quiz.partials.zone-image', ['quizSet' => $quizSet, 'variant' => 'result'])
            </aside>
        </div>
    </div>
@endsection
