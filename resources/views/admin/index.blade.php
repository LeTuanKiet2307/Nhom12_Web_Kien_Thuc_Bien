@extends('layouts.app')
@section('title', 'Quản trị · OceanQuiz')
@section('content')
<div class="container screen-page admin-page">
    <h1>Quản trị</h1><p class="page-intro">Tạo bộ đề mới từ bài viết Wikipedia và câu hỏi do Gemini AI soạn. Mỗi bước là một biểu mẫu riêng.</p>
    @include('admin.partials.nav')
    <div class="admin-grid">
        <section class="panel">
            <div class="panel-title"><span class="step-number">1</span><h2>Lấy bài viết từ Wikipedia</h2></div><p class="panel-description">Nhập chủ đề đại dương bằng tiếng Việt, ví dụ: Cá voi xanh, Rãnh Mariana, San hô.</p>
            <form method="get" action="{{ route('admin.index') }}" class="stack-form"><div class="field-group"><label for="search">Chủ đề</label><div class="search-row"><input class="form-control" id="search" name="search" placeholder="Ví dụ: Cá voi xanh" value="{{ request('search') }}" required maxlength="100"><button class="btn btn-outline">Lấy bài viết</button></div></div></form>
            @if($apiError)<div class="flash error" role="alert">{{ $apiError }}</div>@endif
            @if($results)<details class="search-results" @if(!$preview) open @endif><summary>{{ count($results) }} bài liên quan — chọn bài để xem trước</summary>@foreach($results as $result)<article class="search-result"><strong>{{ $result['title'] }}</strong><p>{{ $result['snippet'] }}</p><a href="{{ route('admin.index',['search'=>request('search'),'page_id'=>$result['id']]) }}">Xem bài này</a></article>@endforeach</details>@endif
            @if(request('search') && !$results && !$apiError)<p class="muted small">Không tìm thấy bài phù hợp. Hãy thử từ khóa khác.</p>@endif
            @if($preview)<div class="flash success" role="status">Đã lấy bài viết: {{ $preview['title'] }} ({{ number_format(mb_strlen($preview['content']),0,',','.') }} ký tự).</div><h3 class="review-title">{{ $preview['title'] }}</h3><div class="reading-preview">{{ $preview['content'] }}</div><p class="source-note">Nguồn: <a href="{{ $preview['source_url'] }}" target="_blank" rel="noopener noreferrer">Wikipedia</a> · {{ $preview['license'] }}</p><form method="post" action="{{ route('admin.wikipedia.store') }}">@csrf<input type="hidden" name="page_id" value="{{ $preview['wiki_page_id'] }}"><button class="btn btn-outline">Lưu bài vào thư viện</button></form>@endif
            <hr class="admin-separator">
            <form method="post" action="{{ route('admin.ai.store') }}" class="stack-form" data-generation-form>@csrf
                <div class="field-group"><label for="article_id">Bài đọc đã lưu</label><select class="form-control" id="article_id" name="article_id" required><option value="">Chọn bài từ thư viện</option>@foreach($articles as $article)<option value="{{ $article->id }}" @selected((string)old('article_id',request('article_id'))===(string)$article->id)>{{ $article->title }}</option>@endforeach</select><p class="source-note">Có thể <a href="{{ route('admin.articles.index') }}">xem và chỉnh sửa bài đọc</a> trước khi tạo câu hỏi.</p></div>
                <div class="field-group"><label for="title">Tiêu đề bộ đề</label><input class="form-control" id="title" name="title" value="{{ old('title') }}" placeholder="Ví dụ: Cá voi xanh và đại dương" required maxlength="200"></div>
                <div class="field-group">@include('admin.partials.zone')</div>
                <button class="btn btn-primary" @disabled(!$configured || $articles->isEmpty())>Tạo 5 câu hỏi bằng Gemini AI</button>
                <p class="source-note">{{ $configured?'Gemini đã sẵn sàng. Chỉ lưu khi đủ 5 câu hợp lệ.':'Chưa cấu hình khóa Gemini. Bạn vẫn có thể tạo bộ thủ công.' }}</p>
            </form>
        </section>
        <section class="panel" id="review-panel">
            <div class="panel-title"><span class="step-number">2</span><h2>Duyệt câu hỏi do AI tạo</h2></div><p class="panel-description">Kiểm tra nội dung, đáp án và giải thích trước khi xuất bản.</p>
            @if($reviewSet)
                @php($set=$reviewSet)
                @php($editable=!$set->is_active && !$set->attempts()->exists())
                <p class="review-title"><strong>{{ $set->title }}</strong> · <span class="status-badge {{ $set->is_active?'published':'' }}">{{ $set->is_active?'Đã xuất bản':'Bản nháp' }}</span></p>
                @if(!$editable)<p class="source-note">Nội dung đang khóa. <a href="{{ route('admin.question-sets.edit',$set) }}">Mở bộ đề để xem hoặc gỡ xuất bản.</a></p>@endif
                <form method="post" action="{{ route('admin.question-sets.update',$set) }}">@csrf @method('patch')<input type="hidden" name="title" value="{{ $set->title }}"><input type="hidden" name="ocean_zone" value="{{ $set->ocean_zone }}">@include('admin.partials.question-editor')
                    @if($editable)<div class="review-actions"><a class="btn btn-outline" href="{{ route('admin.question-sets.create') }}">Tạo bộ thủ công</a><button class="btn btn-primary">Lưu bộ đề (5 câu)</button></div>@endif
                </form>
                <div class="review-actions"><a href="{{ route('admin.question-sets.edit',$set) }}">Kiểm chứng bài đọc & xuất bản</a></div>
            @else<div class="empty-state"><h3>Chưa có bộ đề để duyệt</h3><p>Lưu bài viết và tạo câu hỏi ở bên trái. Bộ mới sẽ được lưu nháp để bạn kiểm tra.</p><a class="btn btn-outline" href="{{ route('admin.question-sets.create') }}">Tạo bộ thủ công</a></div>@endif
        </section>
    </div>
    <section class="panel panel-table"><h2>Danh sách bộ đề</h2><div class="table-wrap" tabindex="0" role="region" aria-label="Danh sách bộ đề"><table class="data-table"><thead><tr><th>Mã</th><th>Tiêu đề và nguồn</th><th>Tầng đại dương</th><th>Số câu</th><th>Lượt làm bài</th><th>Thao tác</th></tr></thead><tbody>
    @forelse($quizSets as $item)<tr><td>#{{ $item->id }}</td><td><span class="table-title">{{ $item->title }}</span><div class="table-source">@if($item->source_url)<a href="{{ $item->source_url }}" target="_blank" rel="noopener noreferrer">{{ parse_url($item->source_url,PHP_URL_HOST) }}</a>@endif</div><span class="status-badge {{ $item->is_active?'published':'' }}">{{ $item->is_active?'Đã xuất bản':'Bản nháp' }}</span></td><td><span class="zone-dot"></span>{{ $item->ocean_zone }}</td><td>{{ $item->questions_count }} câu</td><td>{{ $item->attempts_count }} lượt</td><td><a class="btn btn-outline" href="{{ route('admin.question-sets.edit',$item) }}">{{ $item->is_active?'Xem':'Biên tập' }}</a></td></tr>@empty<tr><td colspan="6">Chưa có bộ đề. Bắt đầu bằng cách tìm bài viết Wikipedia.</td></tr>@endforelse
    </tbody></table></div></section>
</div>
@endsection
@push('scripts')<script>document.querySelector('[data-generation-form]').addEventListener('submit',function(){const button=this.querySelector('button');button.disabled=true;button.textContent='Đang tạo câu hỏi, vui lòng chờ…';});</script>@endpush
