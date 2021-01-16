<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use App\Models\Schedule;

class ScheduleController extends Controller
{
    //
    function getEndDay($year, $month)
    {
        $isUru = false;
        $day = 0;
        if ($month == 4 ||
            $month == 6 ||
            $month == 9 ||
            $month == 11
        ) {
            $day = 30;
        } elseif ($month == 2) {
            if ($year % 400 == 0 && $year % 100 != 0 && $year % 4 == 0) {
                global $isUru;
                $isUru = true;
            }
            if ($isUru) {
                $day = 29;
            } else {
                $day = 28;
            }
        } else {
            $day = 31;
        }
        return $day;
    }
    function getSchedule(Request $request) {
        $schedule = new Schedule;
        // 日数を出す関数として分離させる;
        $year  = $request->input('thisYear');
        $month = $request->input('thisMonth');
        $endDay = self::getEndDay($year, $month);
        if($month < 10) {
            $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        }
        $start = $year.'-'.$month.'-'.'01';
        $end   = $year.'-'.$month.'-'.$endDay;
        $keyWord = ['start' => $start, 'end' => $end];
        $keyWord = json_encode($keyWord);
        return $keyWord;
        // 対象の月の日付を取得
        // シフトごとのメンバーを取得してシフトの配列に代入
    }
}
