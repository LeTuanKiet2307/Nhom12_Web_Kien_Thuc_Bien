@extends('layouts.app')

@section('title', 'Bảng Xếp Hạng Toàn Cầu | AbyssalMind')

@section('content')
<div class="container" style="max-width: 960px;">
    <div class="bento-card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.75rem;">
            <div>
                <h1 style="font-size: 1.85rem;">🏆 Bảng Xếp Hạng Đại Dương Toàn Cầu</h1>
                <p style="color: var(--text-muted); font-size: 0.92rem;">Xếp hạng các nhà thám hiểm có tổng điểm cao nhất và số bài hoàn thành nhiều nhất</p>
            </div>
            <a href="{{ route('quiz.play') }}" class="btn btn-primary">🚀 Thi Đua Điểm Số Ngay</a>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Hạng</th>
                    <th>Tên Nhà Thám Hiểm</th>
                    <th>Số Bài Đã Hoàn Thành</th>
                    <th style="text-align: right;">Tổng Điểm</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leaders as $idx => $leader)
                    <tr style="{{ auth()->id() === $leader->id ? 'background: rgba(0, 242, 254, 0.12);' : '' }}">
                        <td style="font-family: 'JetBrains Mono', monospace; font-weight: 700;">
                            @if($idx === 0) 🥇 #1
                            @elseif($idx === 1) 🥈 #2
                            @elseif($idx === 2) 🥉 #3
                            @else #{{ $idx + 1 }}
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 700;">
                                {{ $leader->name }}
                                @if(auth()->id() === $leader->id)
                                    <span class="zone-depth-pill" style="font-size:0.7rem; padding:0.15rem 0.5rem; margin-left:0.4rem;">BẠN</span>
                                @endif
                            </div>
                        </td>
                        <td>{{ $leader->quizzes_completed }} lượt thi</td>
                        <td style="text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 800; color: var(--cyan-glow); font-size: 1.1rem;">
                            {{ number_format($leader->total_score) }} điểm
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection