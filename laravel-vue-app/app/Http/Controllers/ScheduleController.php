<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use App\Models\Schedule;
use App\Models\Member;
use App\Models\Shift;

class ScheduleController extends Controller
{
    const ORIGIN_ALIGNER  =  1;
    const DAYS_ONE_WEEK   =  7;
    const MUST_REST_HOURS = 12;
    const ONE_DAY_HOURS   = 24;
    function getEndDay( $year, $month )
    {
        $isUru = false;
        $day   = 0;
        if( $month ==  4 ||
            $month ==  6 ||
            $month ==  9 ||
            $month == 11 )
        {
            $day = 30;
        }
        elseif( $month == 2 )
        {
            if( $year % 400 == 0 &&
                 $year % 100 != 0 &&
                 $year %   4 == 0 )
            {
                global $isUru;
                $isUru = true;
            }
            if( $isUru ){ $day = 29; }
            else        { $day = 28; }
        }
        else
        {
            $day = 31;
        }
        return $day;
    }

    function makeSchedule( $scheduleEndDay, $scheduleYearMonth, $request )
    {
        $schedule   = new Schedule;
        $allShifts  = Shift::all();
        $allMembers = Member::all();
        $members    = $allMembers->whereStrict( 'is_deleted', 0 )->values();
        $membersCount  = $members->count();
        //そもそも人が足りるか計算
        $leastMembers         = $allShifts->sum('least_members' );
        $leastMembersForMonth = 0; //休日を含めて運用可能な最低人数;
        $daysOff              = 2;
        $daysWork             = 0;
        $isMemberWillEnough   = false;

        for (; $daysOff > 0 && !$isMemberWillEnough ; $daysOff-- )
        {
            $daysWork             = self::DAYS_ONE_WEEK - $daysOff;
            $oneCycle             = $daysWork + 1;//連勤の制約をクリアできるスケジュールのメンバーのひとかたまり。連勤の制約の中で最大で稼働出来るメンバーは連勤数+1;
            $oneCycleRemain       = $leastMembers % ( $oneCycle - $daysOff );
            $leastMembersForMonth = floor(( $leastMembers / ( $oneCycle - $daysOff ) )) * $oneCycle +  ( $oneCycleRemain + $daysOff );
            //シフトの一日の最低必要な人数を(連勤制限をクリアできる人まとまりのメンバー - 1日あたりの休日を取る最大値のメンバー）で割る。そのあまりに休日を加味して連勤の制約に引っかからないだけの人員をたす。
            if( $membersCount >= $leastMembersForMonth )
            {
                $isMemberWillEnough = true;
                break;
            }
        }
        if( $isMemberWillEnough )
        {
            for( $insertDay = 1; $insertDay <= $scheduleEndDay; $insertDay++ )
            {
                for($memberIndex = 0; $memberIndex < $membersCount; $memberIndex++) {
                    //最初に休みの日かどうか判断する。連勤の制約があるためメンバは一定の周期で休日を取得する。
                    $additionalDayOff = $daysOff - self::ORIGIN_ALIGNER;//必ず休みにしなければ行けない日+何日休みがあるかを出す。
                    $isTodayOff       = false;
                    while( $additionalDayOff > -1 && !$isTodayOff )
                    {
                    $isTodayOff = ( $memberIndex + self::ORIGIN_ALIGNER + $additionalDayOff ) % $oneCycle  === ( $insertDay ) % $oneCycle;//メンバの休日の周期は1サイクルで決まるため円卓処理が必要。
                    $additionalDayOff--;
                    }
                    if( $isTodayOff )
                    {
                        $members[ $memberIndex ][ 'last_worked_at' ] = null;
                        continue;
                    }
                    else
                    {
                        //ここでインサート処理
                        //シフトの全ての時間帯の内、もっとも人メンバー必要な時間帯のIDを割り出す。
                        //12時間の制約に引っかかる場合は+1番目に必要なシフトのIDを割り出していく。
                        $shiftsCount = $allShifts->count();
                        for( $insertShiftIndex = 0 ; $insertShiftIndex < $shiftsCount; $shiftsCount++ )
                        {
                            $allShifts->sortByDesc('least_members ');
                            $sortedShifts      = $allShifts->values();
                            $insertShift       = $sortedShifts;//[ $insertShiftIndex ];
                            $insertForShiftId  = $insertShift[ $insertShiftIndex ][ 'id' ];
                            $shiftStartAt      = $insertShift[ $insertShiftIndex ][ 'start_at' ];
                            $shiftEndAt        = $insertShift[ $insertShiftIndex ][ 'end_at' ];

                            //12時間の制約に引っかかるか判定
                            $isEnoughRest = false;
                            $lastWorkedAt  = $members[ $memberIndex ]['last_worked_at'];
                            if ( $lastWorkedAt === null ) {
                                global $isEnoughRest;
                                $isEnoughRest = true;
                            }
                            else
                            {
                                $shiftStartAt = substr($shiftStartAt, 0, 2);
                                $lastWorkedAt = substr($lastWorkedAt, 0, 2);
                                $shiftStartAt = ( integer )$shiftStartAt;
                                $lastWorkedAt = ( integer )$lastWorkedAt;
                                $shiftStartAt = $shiftStartAt + self::ONE_DAY_HOURS;//前日の終業時間と評価するため、時間軸をあわせる。
                                $restedTime   = $shiftStartAt - $lastWorkedAt;
                                if( $restedTime >= self::MUST_REST_HOURS ){ $isEnoughRest = true; }
                            }
                            if ( $isEnoughRest )
                            {//インサート
                                if ( $insertDay < 10 ){ $insertDayForDbFormat = str_pad( $insertDay, 2, 0, STR_PAD_LEFT ); }
                                $insertForShiftDate = $scheduleYearMonth . '-' . $insertDayForDbFormat;
                                $insertForMemberId  = $members[ $memberIndex ][ 'member_id' ];
                                $insertForUpdateAt  = date( "Y-m-d H:i:s" );
                                $insertForCreatedAt =  $insertForUpdateAt;
                                $schedule
                                ->create
                                ( [
                                   'shift_date' => $insertForShiftDate,
                                   'shift_id'   => $insertForShiftId,
                                   'member_id'  => $insertForMemberId,
                                   'updated_at' => $insertForUpdateAt,
                                   'created_at' => $insertForCreatedAt
                               ] )
                               ->save();
                               $members[ $memberIndex ][ 'shift_count' ]          += 1;
                               $members[ $memberIndex ][ 'last_worked_at' ]        = $shiftEndAt;
                               $members[ $memberIndex ][ 'is_shifted' ]            = true;
                               $allShifts[ $insertShiftIndex ][ 'least_members' ] -= 1;
                               break;//ここでブレークしないと同日の違うシフトにいれられそうになる。
                            }
                        }
                    }
                }
           }
           $scheduleData = self::getSchedule($request);
       }
       else
       {   global $membersCount;
           $notEnoughMembers = $leastMembersForMonth - $membersCount;
           $scheduleData     = '必要人数：あと'.$notEnoughMembers.'人';
       }
       return $scheduleData;
    }
    function convertFormat( $scheduleData ){}
    function getSchedule( Request $request )
    {
        //日にちの整形処理
        $schedule = new Schedule;
        $scheduleYear     = $request->input( 'thisYear' );
        $scheduleMonth    = $request->input( 'thisMonth' );
        $scheduleEndDay   = self::getEndDay( $scheduleYear, $scheduleMonth );
        if( $scheduleMonth < 10 ) { $month = str_pad( $scheduleMonth, 2, 0, STR_PAD_LEFT ); }
        $scheduleStartDate = $scheduleYear.'-'.$scheduleMonth.'-'.'01';
        $scheduleEndDate   = $scheduleYear.'-'.$scheduleMonth.'-'.$scheduleEndDay;
        $scheduleData      = $schedule->whereBetween( 'shift_date', [ $scheduleStartDate, $scheduleEndDate ])->get();
        $dataCount         = $scheduleData->count();
        $isExistSchedule = $dataCount > 0;
        if( !$isExistSchedule )
        {
            $scheduleYearMonth    = $scheduleYear.'-'.$scheduleMonth;
            $scheduleData = self::makeSchedule( $scheduleEndDay, $scheduleYearMonth, $request);
        }
        //レコードの加工
        //$data = self::convertFormat($scheduleData);
        return $scheduleData;
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
    }
}
