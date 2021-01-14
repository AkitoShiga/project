<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use App\Models\Schedule;

class ScheduleController extends Controller
{
    //
    function getSchedule(Request $request) {
        $schedule = new Schedule;
        $month = $request->input('thisMonth');
        $date = $schedule::all("shift_date");
        return $date;
        //return response()->json(['message' => 'from getSchedule'], 200);
        // 対象の月の日付を取得
        // シフトごとのメンバーを取得してシフトの配列に代入
    }
}
