<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\WikipediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class WikipediaController extends Controller
{
    public function index(Request $request, WikipediaService $wiki)
    {
        $data = $request->validate(['search' => 'nullable|string|max:100', 'page_id' => 'nullable|integer|min:1']);
        $results = [];
        $preview = null;
        $apiError = null;
        try {
            if (! empty($data['search'])) {
                $results = $wiki->search($data['search']);
            }if (! empty($data['page_id'])) {
                $preview = $wiki->article($data['page_id']);
            }
        } catch (RuntimeException $e) {
            $apiError = $e->getMessage();
        }

        return view('admin.wikipedia', compact('results', 'preview', 'apiError'));
    }

    public function store(Request $request, WikipediaService $wiki)
    {
        $data = $request->validate(['page_id' => 'required|integer|min:1']);
        try {
            $content = $wiki->article($data['page_id']);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['wikipedia' => $e->getMessage()]);
        }
        $article = DB::transaction(fn () => Article::firstOrCreate(['wiki_page_id' => $content['wiki_page_id']], $content));

        return to_route('admin.articles.edit', $article)->with('success', 'Đã lưu bài và nguồn Wikipedia. Bài đã lưu trước đó được giữ nguyên.');
    }
}
