@extends('layouts.app')

@section('title', 'Kết quả bài làm: ' . $quizSet->title)

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

<div class="container" style="max-width: 960px;">
    <div style="margin: 1.5rem 0;">
        <span class="zone-depth-pill">{{ $quizSet->ocean_zone }}</span>
        <h1 style="font-size: 2rem; margin-top: 0.5rem;">Kết quả bài làm</h1>
        <p style="color: var(--text-muted);">{{ $quizSet->title }}. Kết quả đã được lưu vào bảng xếp hạng.</p>
    </div>

    <div class="stat-grid-2" style="grid-template-columns: repeat(3, 1fr);">
        <div class="stat-tile">
            <div style="font-size: 0.8rem; color: var(--text-muted);">ĐIỂM ĐẠT ĐƯỢC</div>
            <div class="value">{{ number_format($score, 0, ',', '.') }}</div>
        </div>
        <div class="stat-tile">
            <div style="font-size: 0.8rem; color: var(--text-muted);">SỐ CÂU ĐÚNG</div>
            <div class="value">{{ $correctCount }} / {{ $totalQuestions }}</div>
        </div>
        <div class="stat-tile">
            <div style="font-size: 0.8rem; color: var(--text-muted);">TỔNG THỜI GIAN</div>
            <div class="value">{{ $durationLabel }}</div>
        </div>
    </div>

    <div class="bento-card" style="margin-top: 1.25rem;">
        <h2 style="font-size: 1.15rem; margin-bottom: 1rem;">Chi tiết từng câu</h2>

        @foreach ($answers as $answer)
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 0.75rem 0; border-top: 1px solid rgba(255,255,255,0.08);">
                <div style="flex: 1;">
                    <strong>Câu {{ $loop->iteration }}:</strong> {{ $answer->question->question_text }}
                </div>
                <div style="white-space: nowrap; font-size: 0.9rem;">
                    @if ($answer->selected_option_index === null)
                        <span style="color: #fbbf24; font-weight: 700;">Hết giờ</span>
                    @elseif ($answer->is_correct)
                        <span style="color: #4ade80; font-weight: 700;">Đúng · {{ number_format((float) $answer->time_taken, 1, ',', '.') }}s</span>
                    @else
                        <span style="color: #f87171; font-weight: 700;">Sai · {{ number_format((float) $answer->time_taken, 1, ',', '.') }}s</span>
                    @endif
                </div>
                <div style="width: 80px; text-align: right; font-weight: 700;">
                    {{ $answer->is_correct ? '+' . number_format((int) $answer->points_earned, 0, ',', '.') : '0' }}
                </div>
            </div>
        @endforeach
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin: 1.5rem 0;">
        <a class="btn btn-primary" href="{{ route('quiz.mistakes') }}">Ôn tập câu sai ({{ $mistakeCount }} câu)</a>
        <a class="btn btn-outline" href="{{ route('quiz.play') }}">Làm bộ đề khác</a>
        <a class="btn btn-outline" href="{{ route('leaderboard') }}">Xem bảng xếp hạng</a>
    </div>
</div>
@endsection
