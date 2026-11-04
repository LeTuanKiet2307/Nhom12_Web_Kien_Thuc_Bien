@extends('layouts.app')
@section('title','Biên soạn bài kiến thức')
@section('content')<div class="container screen-page admin-page"><h1>{{ $article->exists?'Bài đã lưu':'Thêm bài kiến thức' }}</h1>@include('admin.partials.nav')
@if($article->exists && $article->isLocked())<div class="bento-card">Bài đã gắn với bộ xuất bản hoặc có lượt làm. Tạo bài mới nếu cần thay đổi.</div>@endif
<form class="bento-card" method="post" action="{{ $article->exists?route('admin.articles.update',$article):route('admin.articles.store') }}">@csrf @if($article->exists)@method('patch')@endif
<label for="title">Tiêu đề</label><input class="form-control" id="title" name="title" required maxlength="200" value="{{ old('title',$article->title) }}">
<label for="content">Nội dung</label><textarea class="form-control" id="content" name="content" rows="18" minlength="80" maxlength="100000" required>{{ old('content',$article->content) }}</textarea>
@foreach(['source_name'=>'Tên nguồn','source_url'=>'URL nguồn','license'=>'Giấy phép'] as $field=>$label)<label for="{{ $field }}">{{ $label }}</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" type="{{ $field==='source_url'?'url':'text' }}" value="{{ old($field,$article->$field) }}">@endforeach
@if(!$article->exists || !$article->isLocked())<button class="btn btn-primary">Lưu bài</button>@endif</form>
@if($article->exists)<a class="btn btn-warning" href="{{ route('admin.ai.index',['article_id'=>$article->id]) }}">Sinh câu hỏi từ bài này</a>@endif</div>@endsection