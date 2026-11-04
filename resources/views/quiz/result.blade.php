@extends('layouts.app')

@section('title', 'Kết quả bài làm | OceanQuiz')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/quiz.css') }}">
@endpush

@section('content')
    @php
        $totalQuestions = max(1, (int) $attempt->total_questions);
        $correctCount = (int) $attempt->correct_count;
        $score = (int) $attempt->score;
        $durationSeconds = max(0, (int) $durationSeconds);
        $minutes = intdiv($durationSeconds, 60);
        $seconds = $durationSeconds % 60;
        $durationLabel = $minutes > 0 ? $minutes . ' phút ' . $seconds . ' giây' : $seconds . ' giây';
    @endphp

    <div class="container quiz-page">
        <header class="result-head">
            <p class="result-eyebrow">@include('quiz.partials.zone-label', ['zone' => $quizSet->ocean_zone, 'short' => true])</p>
            <h1 class="result-title">Kết quả bài làm</h1>
            <p class="result-subtitle">{{ $quizSet->title }}. Kết quả đã được lưu vào bảng xếp hạng.</p>
        </header>

        <div class="grid-3 result-tiles">
            <div class="tile">
                <p class="tile__label">Điểm đạt được</p>
                <p class="tile__value num">{{ number_format($score, 0, ',', '.') }}</p>
            </div>
            <div class="tile">
                <p class="tile__label">Số câu đúng</p>
                <p class="tile__value num">{{ $correctCount }} / {{ $totalQuestions }}</p>
            </div>
            <div class="tile">
                <p class="tile__label">Tổng thời gian</p>
                <p class="tile__value">{{ $durationLabel }}</p>
            </div>
        </div>

        <div class="table-wrap result-table-wrap">
            <table class="table result-table">
                <thead>
                    <tr>
                        <th scope="col" class="result-table__index">#</th>
                        <th scope="col">Câu hỏi</th>
                        <th scope="col">Kết quả</th>
                        <th scope="col">Thời gian</th>
                        <th scope="col">Điểm</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($answers as $answer)
                        <tr>
                            <td class="result-table__index">{{ $loop->iteration }}</td>
                            <td>{{ $answer->question->question_text }}</td>
                            <td>
                                @if ($answer->selected_option_index === null)
                                    <span class="result-status result-status--timeout">Hết giờ</span>
                                @elseif ($answer->is_correct)
                                    <span class="result-status result-status--correct">Đúng</span>
                                @else
                                    <span class="result-status result-status--wrong">Sai</span>
                                @endif
                            </td>
                            <td class="result-table__time">{{ number_format((float) $answer->time_taken, 1, ',', '.') }} giây</td>
                            <td class="result-table__points">
                                @if ($answer->is_correct)
                                    <span class="result-points result-points--gain">+{{ number_format((int) $answer->points_earned, 0, ',', '.') }}</span>
                                @else
                                    <span class="result-points result-points--zero">0</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="btn-row result-actions">
            <a class="btn btn--primary" href="{{ route('quiz.mistakes') }}">Ôn tập câu sai ({{ $mistakeCount }} câu)</a>
            <a class="btn btn--ghost" href="{{ route('quiz.play') }}">Làm bộ đề khác</a>
            <a class="btn btn--ghost" href="{{ route('leaderboard') }}">Xem bảng xếp hạng</a>
        </div>
    </div>
@endsection
