@extends('layouts.app')

@section('title', 'Ôn Tập Các Câu Làm Sai | AbyssalMind')

@section('content')
<div class="container" style="max-width: 960px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.75rem;">
        <div>
            <span class="zone-depth-pill">HỌC TẬP THÍCH ỨNG</span>
            <h1 style="font-size: 1.85rem; margin-top: 0.4rem;">🎯 Ôn Tập Câu Sai (5 Lần Thi Gần Nhất)</h1>
            <p style="color: var(--text-muted); font-size: 0.92rem;">
                Hệ thống tự động tổng hợp các câu bạn trả lời sai hoặc hết giờ trong 5 lượt làm bài gần nhất để bạn củng cố kiến thức.
            </p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline">← Quay lại Bảng Điều Khiển</a>
    </div>

    @forelse($wrongAnswers as $idx => $wrongItem)
        @php
            $q = $wrongItem->question;
            $options = $q ? $q->options : [];
        @endphp
        @if($q)
            <div class="bento-card" style="margin-bottom: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <span style="font-size: 0.8rem; color: var(--cyan-glow); font-weight: 700;">
                        📚 Chủ đề: {{ $q->quizSet->title ?? 'Bộ đề Đại dương' }} ({{ $q->quizSet->ocean_zone ?? '' }})
                    </span>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">
                        Làm sai {{ $wrongItem->created_at->locale('vi')->diffForHumans() }}
                    </span>
                </div>

                <h3 style="font-size: 1.2rem; margin-bottom: 1rem;">Câu {{ $idx + 1 }}: {{ $q->question_text }}</h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                    @foreach($options as $optIdx => $optText)
                        @php
                            $isCorrectOpt = ($optIdx === (int)$q->correct_option_index);
                            $wasSelectedWrong = ($wrongItem->selected_option_index !== null && $optIdx === (int)$wrongItem->selected_option_index);
                        @endphp
                        <div style="padding: 0.85rem 1rem; border-radius: 10px; border: 1px solid {{ $isCorrectOpt ? '#22c55e' : ($wasSelectedWrong ? '#ef4444' : 'rgba(255,255,255,0.1)') }}; background: {{ $isCorrectOpt ? 'rgba(34, 197, 94, 0.14)' : ($wasSelectedWrong ? 'rgba(239, 68, 68, 0.14)' : 'rgba(255,255,255,0.03)') }};">
                            <strong>{{ chr(65 + $optIdx) }}.</strong> {{ $optText }}
                            @if($isCorrectOpt)
                                <span style="float: right; color: #4ade80; font-weight: 700;">✓ Đáp án đúng</span>
                            @elseif($wasSelectedWrong)
                                <span style="float: right; color: #f87171; font-weight: 700;">✗ Bạn đã chọn</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($q->explanation)
                    <div style="background: rgba(0, 242, 254, 0.08); border-left: 3px solid var(--cyan-glow); padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem;">
                        <strong>💡 Giải thích khoa học:</strong> {{ $q->explanation }}
                    </div>
                @endif
            </div>
        @endif
    @empty
        <div class="bento-card" style="text-align: center; padding: 3.5rem 1.5rem;">
            <div style="font-size: 3.5rem; margin-bottom: 0.75rem;">🌟</div>
            <h2>Kỷ Lục Hoàn Hảo!</h2>
            <p style="color: var(--text-muted); margin: 0.5rem 0 1.5rem;">
                Bạn không có câu trả lời sai nào trong 5 lượt làm bài gần nhất.
            </p>
            <a href="{{ route('quiz.play') }}" class="btn btn-primary">🚀 Chinh Phục Bài Thi Tiếp Theo</a>
        </div>
    @endforelse
</div>
@endsection