<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function welcome()
    {
        return view('welcome');
    }

    public function leaderboard()
    {
        $leaders = User::orderByDesc('total_score')
            ->orderByDesc('quizzes_completed')
            ->take(50)
            ->get();

        return view('leaderboard.index', compact('leaders'));
    }
}