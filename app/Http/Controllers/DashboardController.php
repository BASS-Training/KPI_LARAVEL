<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = session('user');

        if (!$user) {
            return redirect()->route('login');
        }

        $tasks = Task::where('user_id', $user->id)->get();
        $kpi = $user->calculateKpiScore();
        
        $recentTasks = $tasks->sortByDesc('created_at')->take(5);
        
        $rankedEmployees = User::whereNotIn('jabatan', ['Direktur Utama', 'Direktur'])
            ->get()
            ->map(function($emp) {
                $kpi = $emp->calculateKpiScore();
                return (object)[
                    'employee' => $emp,
                    'score' => $kpi->total,
                    'predikat' => $kpi->predikat
                ];
            })
            ->sortByDesc('score')
            ->take(3);

        return view('dashboard.beranda', compact('tasks', 'kpi', 'recentTasks', 'rankedEmployees'));
    }
}
