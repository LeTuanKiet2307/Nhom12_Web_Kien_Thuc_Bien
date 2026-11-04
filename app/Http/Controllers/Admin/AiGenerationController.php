<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGenerationLog;
use App\Models\Article;
use App\Models\QuizSet;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AiGenerationController extends Controller
{
    public function index()
    {
        return view('admin.ai', ['articles' => Article::orderBy('title')->get(), 'logs' => AiGenerationLog::with(['article', 'quizSet'])->latest()->paginate(15), 'configured' => (bool) config('services.gemini.key'), 'model' => config('services.gemini.model')]);
    }

    public function store(Request $request, GeminiService $gemini)
    {
        $data = $request->validate(['article_id' => 'required|integer|exists:articles,id', 'title' => 'required|string|max:200', 'ocean_zone' => 'required|string|max:255']);
        $article = Article::findOrFail($data['article_id']);
        $log = AiGenerationLog::create(['article_id' => $article->id, 'user_id' => $request->user()->id, 'model' => config('services.gemini.model'), 'status' => 'pending']);
        try {
            if (PHP_SAPI !== 'cli') {
                set_time_limit(max(120, (int) ini_get('max_execution_time')));
            }
            $raw = $gemini->generate($article->content);
            $key = config('services.gemini.key');
            $safe = $key ? str_replace([$key, rawurlencode($key)], '[REDACTED]', $raw) : $raw;
            $log->update(['response_text' => mb_substr($safe, 0, 30000)]);
            $questions = $gemini->validateQuestions($raw);
            $set = DB::transaction(function () use ($article, $data, $questions, $log, $request) {
                $current = Article::whereKey($article->id)->lockForUpdate()->firstOrFail();
                if ($current->content !== $article->content) {
                    throw new RuntimeException('Bài vừa thay đổi trong lúc sinh câu hỏi. Hãy thử lại.');
                }
                $set = QuizSet::create(['article_id' => $current->id, 'title' => $data['title'], 'topic' => $current->title, 'ocean_zone' => $data['ocean_zone'], 'reading_passage' => $current->content, 'source_url' => $current->source_url, 'is_active' => false, 'source' => 'ai', 'created_by' => $request->user()->id]);
                foreach ($questions as $q) {
                    $set->questions()->create(['question_text' => trim($q['content']), 'options' => array_map('trim', $q['options']), 'correct_option_index' => $q['correct_index'], 'explanation' => trim($q['explanation']), 'time_limit' => 20]);
                }
                $log->update(['status' => 'success', 'quiz_set_id' => $set->id]);

                return $set;
            });
        } catch (\Throwable $e) {
            $message = ($e instanceof ValidationException || $e instanceof \JsonException) ? 'AI trả dữ liệu không hợp lệ. Không lưu bộ câu hỏi.' : ($e instanceof RuntimeException ? $e->getMessage() : 'Không thể hoàn tất sinh câu hỏi. Hãy thử lại.');
            $log->update(['status' => 'failed', 'error_message' => $message]);

            return back()->withInput()->withErrors(['gemini' => $message]);
        }

        return to_route('admin.question-sets.edit', $set)->with('success','Đã tạo đúng 5 câu nháp. Kiểm tra trước khi xuất bản.');
    }
}
