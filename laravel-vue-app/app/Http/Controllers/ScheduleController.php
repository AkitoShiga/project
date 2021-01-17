<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use App\Models\Schedule;
use App\Models\Member;
use App\Models\Shift;

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
    function makeSchedule($data, $day, $yearMonth) {
       $Member   = new Member;
       $schedule = new Schedule;
       $members  = $Member->whereIn('is_deleted', [false]);

       for($i = 1 ; $i < $day+1; $i++) {
           $shift    = new Shift;
           $shifts = $shift->all();
           $isFilled = false;
           $isWorked = false;
           while(!$isFilled  && !$isWorked) { //メンバーが全員がfalseかつシフトが足りていたら次の日に進む
               $shifts = $shifts->sortByDesc('least_members')->values();
               $max = $shifts->max('least_members');
               if($max < 1){
                    $isFilled = true;
               }
               $shifts[0]['least_members'] = --$max;
               $shiftId                    = $shifts[0]['id'];//shiftのIDに入れる。
               $shiftStartAt               = $shifts[0]['start_at'];
               $shiftEndAt                 = $shifts[0]['end_at'];//DBに登録した時にメンバーのlast_worked_atに入れる。
               foreach ($members as $member) {
                   //最後のシフトから12時間経過しているか
                   $isShifted           = $member['is_shifted'];
                   $shiftCount          = $member['shift_count'];
                   $isShiftCountLimited = $shiftCount === 6;
                   $isHoursLeft  = false;
                   $lastWorkedAt = $member['last_worked_at'];
                   if($lastWorkedAt == null ) {
                       global $isHoursLeft;
                       $isHoursLeft = true;
                   } else {
                       $lastWorkedAt = substr($lastWorkedAt, 0, 2);
                       $lastWorkedAt += 0;//phpマジックで数値に変換している。
                       if ($lastWorkedAt > 23) {
                           global $lastWorkedAt;
                           $lastWorkedAt = -24;
                       }
                       $lastWorkedAt += 12;
                       if ($lastWorkedAt > 23) {
                           global $lastWorkedAt;
                           $lastWorkedAt = -24;
                       }
                       $shiftStartAt = substr($shiftStartAt, 0, length, 2);
                       $shiftStartAt += 0;
                       if($shiftStartAt - $lastWorkedAt >= 0){
                          $isHoursLeft = true;
                       }
                   }
                   if(!$isShifted && !$isShiftCountLimited && $isHoursLeft) {
                       $day = 0;
                       if($i < 10) {
                          $day = str_pad($day, 2, 0, STR_PAD_LEFT);
                       }
                       $shiftDate = $yearMonth.'-'.$day;
                       $memberId = $member['id'];
                       $date = new Date();
                      //member id, shift id, 日付をScheduleに入れる。
                       //dbにシフトの情報を入力する→引っ駆らないために
                       //shiftのIDを入力する
                       $schedule->create([
                           'shift_date' => $shiftDate,
                           'shift_id'   => $shiftId,
                           'member_id'  => $memberId,
                           'updated_at' => $date,
                           'created_at' => $date
                       ])->save();
                       $member['shift_count']++;
                       $member['last_worked_at'] = $shiftEndAt;
                       $member['is_shifted'];
                       break;
                   } else {
                      if($isShiftCountLimited) {
                          $member['is_shifted']     = true;
                          $member['shift_count']    = 0;
                          $member['last_worked_at'] = null;
                      }
                       continue;
                   }
                   global $members;
                   $isWorked = true;
                   foreach($members as $mem){
                       if(!$mem['is_shifted']) {
                           $isWorked = false;
                       }
                   }
               }
           }
           foreach($members as $me){
               $me['is_shifted'] = false;
           }
       }
        $startDay = $yearMonth.'-'.'01';
        $endDay = $yearMonth.'-'.$day;
        $data = $schedule->whereBetween('shift_date',[$startDay, $endDay])->get();
        return $data;
    }
    function convertFormat($data) {}
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
        $data = $schedule->whereBetween('shift_date', [$start, $end])->get();
        //if判定、ある：ない
        $dataCount = $data->count();
        //if($dataCount < 1) {
            $yearMonth = $year.'-'.$month;
            $data = self::makeSchedule($data, $endDay,$yearMonth);
       // }
        //レコードの加工？
        //$data = self::convertFormat($data);
        return $data;
        /*
        data {
            日付: {
                シフト1:[名前、名前、名前]
                シフト2:[名前、名前、名前]
                シフト3:[名前、名前、名前]
            }
            日付: {
                シフト1:[名前、名前、名前]
                シフト2:[名前、名前、名前]
                シフト3:[名前、名前、名前]
            }
        }
         */
        // 対象の月の日付を取得
        // シフトごとのメンバーを取得してシフトの配列に代入
    }
}
