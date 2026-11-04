@extends('layouts.app')
@section('title', 'Kiểm duyệt bộ câu hỏi · OceanQuiz')
@section('content')
<div class="container screen-page admin-page">
    <h1>{{ $set->title }}</h1><p class="page-intro">{{ $set->is_active?'Đã xuất bản':'Bản nháp' }} · {{ $set->attempts()->count() }} lượt làm. Kiểm chứng từng câu với bài đọc trước khi xuất bản.</p>@include('admin.partials.nav')
    @php($editable=!$set->is_active && !$set->attempts()->exists())
    @if(!$editable)<div class="flash success">Nội dung đang khóa. Bộ có lượt làm không được sửa hoặc xóa.</div>@endif
    <form method="post" action="{{ route('admin.question-sets.update',$set) }}">@csrf @method('patch')
        <div class="admin-grid"><section class="panel"><div class="panel-title"><span class="step-number">1</span><h2>Bài đọc tham khảo</h2></div><p class="panel-description">Đối chiếu nội dung câu hỏi với tư liệu.</p>
            <div class="reading-preview">{{ $set->reading_passage }}</div>@if($set->source_url)<p class="source-note">Nguồn: <a href="{{ $set->source_url }}" target="_blank" rel="noopener noreferrer">{{ parse_url($set->source_url,PHP_URL_HOST) }}</a></p>@endif
            <hr class="admin-separator"><fieldset @disabled(!$editable) class="stack-form"><div class="field-group"><label for="title">Tiêu đề bộ đề</label><input class="form-control" id="title" name="title" required maxlength="200" value="{{ old('title',$set->title) }}"></div><div class="field-group">@include('admin.partials.zone')</div></fieldset>
        </section><section class="panel"><div class="panel-title"><span class="step-number">2</span><h2>Duyệt câu hỏi</h2></div><p class="panel-description">Mỗi câu có 4 lựa chọn và một đáp án đúng.</p>@include('admin.partials.question-editor')@if($editable)<div class="review-actions"><button class="btn btn-primary">Lưu 5 câu nháp</button></div>@endif</section></div>
    </form>
    <section class="panel"><h2>Xuất bản</h2><p class="panel-description">Lưu thay đổi trước khi xuất bản. Chỉ bộ đủ 5 câu hợp lệ mới được đưa vào trò chơi.</p><div class="review-actions"><form method="post" action="{{ $set->is_active?route('admin.question-sets.unpublish',$set):route('admin.question-sets.publish',$set) }}">@csrf<button class="btn btn-primary">{{ $set->is_active?'Gỡ xuất bản':'Kiểm tra & xuất bản' }}</button></form>@if($editable)<form method="post" action="{{ route('admin.question-sets.destroy',$set) }}" onsubmit="return confirm('Xóa bộ nháp chưa có lượt làm này?')">@csrf @method('delete')<button class="btn btn-danger">Xóa bộ nháp</button></form>@endif<a class="btn btn-outline" href="{{ route('admin.index') }}">Về quản trị</a></div></section>
</div>
@endsection
