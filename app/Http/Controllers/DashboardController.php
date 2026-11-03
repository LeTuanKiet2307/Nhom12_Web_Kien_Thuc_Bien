<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Tính thứ hạng toàn cầu (Global Rank)
        $higherScorersCount = User::where('total_score', '>', $user->total_score)->count();
        $globalRank = $higherScorersCount + 1;

        // Lấy 5 lần làm bài gần nhất kèm chi tiết từng câu hỏi & câu trả lời
        $recentAttempts = QuizAttempt::with(['quizSet', 'answers.question'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // Đếm tổng số câu trả lời sai trong 5 lần làm bài gần nhất
        $mistakeCount = 0;
        foreach ($recentAttempts as $attempt) {
            $mistakeCount += $attempt->answers->where('is_correct', false)->count();
        }

        return view('dashboard.index', compact('user', 'globalRank', 'recentAttempts', 'mistakeCount'));
    }
}