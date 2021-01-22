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
    function makeSchedule($data, $endDay, $yearMonth) {
       $schedule = new Schedule;
       $Member = Member::all();
       $members  = $Member->whereStrict('is_deleted',0)->values();
        //$members = $Member->all();

       for($i = 1 ; $i < $endDay+1; $i++) {
           $shift    = new Shift;
           $shifts = $shift->all();
           $isFilled = false;
           $isWorked = false;
           while(!$isFilled  | !$isWorked) { //メンバーが全員がfalseかつシフトが足りていたら次の日に進む
               $shifts = $shifts->sortByDesc('least_members')->values();
               $max = $shifts->max('least_members');
               if($max < 1){
                    $isFilled = true;
               }
               $shiftId                    = $shifts[0]['id'];//shiftのIDに入れる。
               $shiftStartAt               = $shifts[0]['start_at'];
               $shiftEndAt                 = $shifts[0]['end_at'];//DBに登録した時にメンバーのlast_worked_atに入れる。
               $memberCount = $members->count();
               for ($n = 0; $n < $memberCount; $n++) {
                   //最後のシフトから12時間経過しているか
                   $isShifted = $members[$n]['is_shifted'];
                   $shiftCount = $members[$n]['shift_count'];
                   $isShiftCountLimited = $shiftCount === 6;
                   if(!$isShifted) {
                       $isHoursLeft = false;
                       $lastWorkedAt = $members[$n]['last_worked_at'];
                       if ($lastWorkedAt == null) {
                           global $isHoursLeft;
                           $isHoursLeft = true;
                       } else {
                           $lastWorkedAt = substr($lastWorkedAt, 0, 2);
                           $lastWorkedAt += 0;//phpマジックで数値に変換している。
                           $shiftStartAt = substr($shiftStartAt, 0, 2);
                           $shiftStartAt += 24;
                           $breakTime = $shiftStartAt - $lastWorkedAt;
                           if($breakTime >= 12) {
                               $isHoursLeft = true;
                           }
                           /*
                           if ($lastWorkedAt > 23) {
                               $lastWorkedAt -= 24;
                           }
                           $lastWorkedAt += 12;
                           $shiftStartAt = substr($shiftStartAt, 0, 2);
                           $shiftStartAt += 0;
                          if ($lastWorkedAt > 23) {//前日のてっぺん超えても前日の日付扱いなので当日にしている。
                              $lastWorkedAt -= 24;
                           }
                               if ($shiftStartAt - $lastWorkedAt >= 0) {
                                   $isHoursLeft = true;
                               }
                          // }
                          /* elseif ($lastWorkedAt <= 24) {//前日のシフトから12時間後が日をまたいでいなかったら計算を変える
                               $lastWorkedAt = 24 - $lastWorkedAt;
                               if ($lastWorkedAt + $shiftStartAt >= 0) {
                                   $isHoursLeft = true;
                               }
                           }
                          */
                       }
                       if (!$isShiftCountLimited && $isHoursLeft) {
                           $today = $i;
                           if ($i < 10) {
                               $insertday = str_pad($today, 2, 0, STR_PAD_LEFT);
                           }
                           $shiftDate = $yearMonth . '-' . $insertday;
                           $memberId = $members[$n]['member_id'];
                           $date = date("Y-m-d H:i:s");
                           //member id, shift id, 日付をScheduleに入れる。
                           //dbにシフトの情報を入力する→引っ駆らないために
                           //shiftのIDを入力する
                           $schedule->create([

                               'shift_date' => $shiftDate,
                               'shift_id' => $shiftId,
                               'member_id' => $memberId,
                               'updated_at' => $date,
                               'created_at' => $date
                           ])->save();
                           $members[$n]['shift_count'] += 1;
                           $test = $members[$n]['shift_count'];
                           $members[$n]['last_worked_at'] = $shiftEndAt;
                           $members[$n]['is_shifted'] = true;
                           $shifts[0]['least_members'] = --$max;
                           break;//ここでブレークしないと同じシフトにみんなはいりまくってしまう
                       } else {
                           if ($isShiftCountLimited) {
                               $members[$n]['is_shifted'] = true;
                               $members[$n]['shift_count'] = 0;
                               $members[$n]['last_worked_at'] = null;
                           }
                           //continue;
                       }
                   }
                   $isWorked = true;
                   for($j = 0; $j < $memberCount; $j++){
                       if(!$members[$j]['is_shifted']) {
                           $isWorked = false;
                           break;
                       }
                   }
               }
           }
           for($k = 0; $k < $memberCount; $k++){
               $members[$k]['is_shifted'] = false;
           }
       }
        $shiftStartDay = $yearMonth.'-'.'01';
        $shiftEndDay = $yearMonth.'-'.$endDay;
        $data = $schedule->whereBetween('shift_date',[$shiftStartDay, $shiftEndDay])->get();
        return $data;
    }
    function convertFormat($data) {}
    function getSchedule(Request $request) {
        $schedule = new Schedule;
         //日数を出す関数として分離させる;
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
