<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\QuizSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionSetController extends Controller
{
    public function index()
    {
        return to_route('admin.index');
    }

    public function create()
    {
        return view('admin.question-sets.create', ['articles' => Article::orderBy('title')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['article_id' => 'required|exists:articles,id', 'title' => 'required|string|max:200', 'ocean_zone' => 'required|string|max:255']);
        $set = DB::transaction(function () use ($data, $request) {
            $a = Article::whereKey($data['article_id'])->lockForUpdate()->firstOrFail();

            return QuizSet::create(['article_id' => $a->id, 'title' => $data['title'], 'topic' => $a->title, 'ocean_zone' => $data['ocean_zone'], 'reading_passage' => $a->content, 'source_url' => $a->source_url, 'is_active' => false, 'source' => 'manual', 'created_by' => $request->user()->id]);
        });

        return to_route('admin.question-sets.edit', $set);
    }

    public function edit(QuizSet $questionSet)
    {
        return view('admin.question-sets.form', ['set' => $questionSet->load('questions', 'article')]);
    }

    private function lock(QuizSet $set): QuizSet
    {
        if ($set->article_id) {
            Article::whereKey($set->article_id)->lockForUpdate()->firstOrFail();
        }

        return QuizSet::whereKey($set->id)->lockForUpdate()->firstOrFail();
    }

    public function update(Request $request, QuizSet $questionSet)
    {
        $data = $request->validate(['title' => 'required|string|max:200', 'ocean_zone' => 'required|string|max:255', 'questions' => 'required|array|list|size:5', 'questions.*.question_text' => 'required|string|max:2000|distinct', 'questions.*.options' => 'required|array|list|size:4', 'questions.*.options.*' => 'required|string|max:1000', 'questions.*.correct_option_index' => 'required|integer|min:0|max:3', 'questions.*.explanation' => 'required|string|max:5000']);
        $this->validateChoices($data['questions']);
        DB::transaction(function () use ($questionSet, $data) {
            $set = $this->lock($questionSet);
            abort_if($set->is_active || $set->attempts()->exists(), 422, 'Bộ đã xuất bản hoặc có lượt làm; không sửa nội dung.');
            $set->update(['title' => $data['title'], 'ocean_zone' => $data['ocean_zone']]);
            $set->questions()->delete();
            foreach ($data['questions'] as $q) {
                $set->questions()->create($q + ['time_limit' => 20]);
            }
        });

        return back()->with('success', 'Đã lưu 5 câu hỏi nháp.');
    }

    private function validateChoices(array $questions): void
    {
        foreach ($questions as $q) {
            if (count(array_unique(array_map(fn ($v) => mb_strtolower(trim($v)), $q['options']))) !== 4) {
                throw ValidationException::withMessages(['questions' => 'Mỗi câu cần bốn lựa chọn khác nhau.']);
            }
        }
    }

    public function publish(QuizSet $questionSet)
    {
        DB::transaction(function () use ($questionSet) {
            $set = $this->lock($questionSet);
            $questions = $set->questions()->get();
            $valid = $questions->count() === 5 && $questions->every(fn ($q) => trim($q->question_text) !== '' && is_array($q->options) && count($q->options) === 4 && collect($q->options)->every(fn ($v) => is_string($v) && trim($v) !== '') && in_array($q->correct_option_index, [0, 1, 2, 3], true));
            if (! $valid) {
                throw ValidationException::withMessages(['questions' => 'Cần đúng 5 câu, mỗi câu 4 lựa chọn và một đáp án đúng.']);
            }
            $this->validateChoices($questions->toArray());
            $set->update(['is_active' => true]);
        });

        return back()->with('success', 'Đã xuất bản. Người học có thể gặp bộ đề này.');
    }

    public function unpublish(QuizSet $questionSet)
    {
        DB::transaction(fn () => $this->lock($questionSet)->update(['is_active' => false]));

        return back()->with('success', 'Đã gỡ xuất bản; lịch sử được giữ nguyên.');
    }

    public function destroy(QuizSet $questionSet)
    {
        DB::transaction(function () use ($questionSet) {
            $set = $this->lock($questionSet);
            abort_if($set->is_active || $set->attempts()->exists(), 422, 'Chỉ xóa bộ nháp chưa có lượt làm.');
            $set->delete();
        });

        return to_route('admin.index')->with('success','Đã xóa bộ nháp.');
    }
}
