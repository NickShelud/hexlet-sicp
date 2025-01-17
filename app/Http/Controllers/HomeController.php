<?php

namespace App\Http\Controllers;

use App\Helpers\ChartHelper;
use App\Models\Activity;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\ExerciseMember;
use App\Models\Exercise;
use App\Models\ReadChapter;
use App\Models\User;
use App\Services\PointsCalculator;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Request;

class HomeController extends Controller
{
    public function index(): View
    {

        if (auth()->guest()) {
            $countComments = Comment::count();
            $countExercises = Exercise::count();
            $countChapters = Chapter::count();
            $countUsers = User::count();

            return view('home.landing', compact(
                'countComments',
                'countExercises',
                'countChapters',
                'countUsers'
            ));
        }

        $filter = Request::query('filter', '');
        $statisticTable = $this->getStatisticTable($filter);
        $logItems = Activity::latest()->has('subject')->has('causer')->with('causer')->limit(10)->get();
        $chart = ChartHelper::getChart();
        $comments = Comment::latest()->has('user')->with('user')->with('commentable')->limit(10)->get();

        return view('home.index', compact(
            'logItems',
            'chart',
            'comments',
            'statisticTable',
        ));
    }

    private function getStatisticTable(string $filter): array
    {
        $periodsForFilter = ['week', 'month'];

        if (!in_array($filter, $periodsForFilter, true)) {
            $countReadChapter = ReadChapter::count();
            $countCompletedExercise = ExerciseMember::count();
            $filter = 'all';
        } else {
            $subPeriod = "sub{$filter}";
            $period = Carbon::today()->$subPeriod(1);

            $countReadChapter = ReadChapter::where('created_at', '>', $period)->count();
            $countCompletedExercise = ExerciseMember::where('created_at', '>', $period)->count();
        }

        return [
            'countReadChapter' => $countReadChapter,
            'countCompletedExercise' => $countCompletedExercise,
            'countPoints' => PointsCalculator::calculate($countReadChapter, $countCompletedExercise),
            'filterForQuery' => $filter,
        ];
    }
}
