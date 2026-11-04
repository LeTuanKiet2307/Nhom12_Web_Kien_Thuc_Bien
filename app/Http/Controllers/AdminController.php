<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\QuizSet;
use App\Services\WikipediaService;
use Illuminate\Http\Request;
use RuntimeException;

class AdminController extends Controller
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
            }
            if (! empty($data['page_id'])) {
                $preview = $wiki->article($data['page_id']);
            }
        } catch (RuntimeException $e) {
            $apiError = $e->getMessage();
        }

        return view('admin.index', [
            'quizSets' => QuizSet::withCount('questions', 'attempts')->latest()->get(),
            'reviewSet' => QuizSet::with('questions')->latest('id')->first(),
            'articles' => Article::orderBy('title')->get(['id', 'title']),
            'configured' => (bool) config('services.gemini.key'),
            'results' => $results, 'preview' => $preview, 'apiError' => $apiError,
        ]);
    }
}
