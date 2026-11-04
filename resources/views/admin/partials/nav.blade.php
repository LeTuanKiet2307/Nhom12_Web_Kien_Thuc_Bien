<nav class="admin-tabs" aria-label="Chức năng quản trị">
@foreach(['admin.index'=>'Tổng quan','admin.wikipedia.index'=>'Wikipedia','admin.articles.index'=>'Bài đã lưu','admin.ai.index'=>'Gemini & nhật ký','admin.question-sets.create'=>'Tạo bộ thủ công'] as $route=>$label)<a href="{{ route($route) }}" @if(request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>@endforeach
</nav>
@if($errors->any())<div class="flash error" role="alert"><strong>Vui lòng kiểm tra lại:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
