<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGenerationLog;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    public function index()
    {
        return view('admin.articles.index', ['articles' => Article::withCount('quizSets')->latest()->paginate(15)]);
    }

    public function create()
    {
        return view('admin.articles.form', ['article' => new Article]);
    }

    public function edit(Article $article)
    {
        return view('admin.articles.form', compact('article'));
    }

    private function data(Request $request): array
    {
        return $request->validate(['title' => 'required|string|max:200', 'content' => 'required|string|min:80|max:100000', 'source_name' => 'nullable|string|max:255', 'source_url' => 'nullable|url:http,https|max:255', 'license' => 'nullable|string|max:255']);
    }

    public function store(Request $request)
    {
        $article = Article::create($this->data($request));

        return to_route('admin.articles.edit', $article)->with('success', 'Đã lưu bài kiến thức.');
    }

    public function update(Request $request, Article $article)
    {
        $data = $this->data($request);
        DB::transaction(function () use ($article, $data) {
            $article = Article::whereKey($article->id)->lockForUpdate()->firstOrFail();
            abort_if($article->isLocked(), 422, 'Bài đã xuất bản hoặc có lượt làm; hãy tạo bài mới.');
            $article->update($data);
        });

        return back()->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(Article $article)
    {
        DB::transaction(function () use ($article) {
            $article = Article::whereKey($article->id)->lockForUpdate()->firstOrFail();
            abort_if($article->quizSets()->exists() || $article->hasMany(AiGenerationLog::class)->exists(), 422, 'Bài đang được sử dụng; không thể xóa.');
            $article->delete();
        });

        return to_route('admin.articles.index')->with('success', 'Đã xóa bài chưa sử dụng.');
    }
}
