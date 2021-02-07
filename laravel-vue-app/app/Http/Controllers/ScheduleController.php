<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\HolidayController;
use App\Models\Schedule;
use App\Models\Member;
use App\Models\Shift;

class ScheduleController extends Controller
{
    const ORIGIN_ALIGNER  =  1;
    const DAYS_ONE_WEEK   =  7;
    const MUST_REST_HOURS = 12;
    const ONE_DAY_HOURS   = 24;
    const WORKING_HOURS   =  8;
    const WEEK_ARRAY      = ['日', '月', '火', '水', '木', '金', '土'];
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

    function makeSchedule( $scheduleEndDay, $scheduleYearMonth, $request , $scheduleYear, $scheduleMonth )
    {
        $schedule     = new Schedule;
        $allShifts    = Shift::all();
        $allMembers   = Member::all();
        $members      = $allMembers->whereStrict( 'is_deleted', 0 )->values();
        $membersCount = $members->count();
        $shiftsCount  = $allShifts->count();
        $randomNum    = rand( 1, $membersCount );
        //そもそも人が足りるか計算
        $leastMembers         = Shift::sum('least_members' );
        $leastMembersForMonth = 0; //休日を含めて運用可能な最低人数;
        $daysOff              = 2;
        $daysWork             = 0;
        $isMemberWillEnough   = false;

        for (; $daysOff > 0 && !$isMemberWillEnough ; $daysOff-- )
        {
            $daysWork             = self::DAYS_ONE_WEEK - $daysOff;
            $oneCycle             = $daysWork + 1;//連勤の制約をクリアできるスケジュールのメンバー構成。連勤の制約の中で最大で稼働出来るメンバーは連勤数+1人;
            $oneCycleRemain       = $leastMembers % ( $oneCycle - $daysOff );
            $leastMembersForMonth = floor(( $leastMembers / ( $oneCycle - $daysOff ) )) * $oneCycle +  ( $oneCycleRemain + $daysOff );
            //シフトの一日の最低必要な人数を(連勤制限をクリアできる1まとまりのメンバー - 1日あたりの休日を取る最大値のメンバー）で割る。
            //その余りに休日を考慮した上で連勤の制約に引っかからないだけの人員をたす。
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
                $allShifts  = Shift::all();
                //最初に休みの日かどうか判断する。連勤の制約があるためメンバは一定の周期で休日を取得する。
                for($memberIndex = 0; $memberIndex < $membersCount; $memberIndex++) {
                    $insertMemberIndex = ( ($memberIndex + $randomNum ) % $membersCount );//恣意的なメンバーのIDからシフト作成を行うことで公平性を担保する。
                    $additionalDayOff  = $daysOff - self::ORIGIN_ALIGNER;                 //必ず休みにしなければ行けない1日+何日休みがあるかを出す。
                    $isTodayOff        = false;
                    while( $additionalDayOff > -1 && !$isTodayOff )
                    {
                    //メンバの休日の周期は1サイクルで決まるため円卓処理が必要。
                    $isTodayOff = ( $insertMemberIndex + self::ORIGIN_ALIGNER + $additionalDayOff ) % $oneCycle  === ( $insertDay ) % $oneCycle;
                    $additionalDayOff--;
                    }
                    if( $isTodayOff )
                    {
                        $members[ $insertMemberIndex ][ 'last_worked_at' ] = null;
                        continue;
                    }
                    else
                    {
                        //シフトの全ての時間帯の内、もっとも人メンバーがな時間帯のIDを割り出す。
                        //12時間の制約に引っかかる場合は+1番目に必要なシフトのIDを割り出していく。
                        for( $insertShiftIndex = 0 ; $insertShiftIndex < $shiftsCount; $insertShiftIndex++ )
                        {
                            $shiftIterator     = 0;
                            $allShifts         = $allShifts->sortByDesc('least_members')->values();
                            $insertForShiftId  = $allShifts[ $insertShiftIndex ][ 'id' ];
                            $shiftStartAt      = $allShifts[ $insertShiftIndex ][ 'start_at' ];
                            $shiftEndAt        = $allShifts[ $insertShiftIndex ][ 'end_at' ];
                            //12時間の制約に引っかかるか判定
                            $isEnoughRest = false;
                            $lastWorkedAt  = $members[ $insertMemberIndex ]['last_worked_at'];
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
                            {
                                if( $insertDay < 10 ){ $insertDayForDbFormat = str_pad( $insertDay, 2, 0, STR_PAD_LEFT ); }
                                else{ $insertDayForDbFormat = $insertDay; }
                                $insertForShiftDate = $scheduleYearMonth . '-' . $insertDayForDbFormat;
                                $insertForMemberId  = $members[ $insertMemberIndex ][ 'member_id' ];
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
                               $shiftIterator++;
                               $members[ $insertMemberIndex ][ 'last_worked_at' ]  = $shiftEndAt;
                               $members[ $insertMemberIndex ][ 'is_shifted' ]      = true;
                               $members[ $insertMemberIndex ][ 'shift_count' ]    += 1;
                               $allShifts[ $insertShiftIndex ][ 'least_members' ] -= ( 1 + $shiftIterator/100);
                               break;
                            }
                        }
                    }
                }
           }
           self:: adjustmentSchedule( $scheduleYear, $scheduleMonth );
           $scheduleData = self::getSchedule( $request );
       }
       else
       {
           $notEnoughMembers = $leastMembersForMonth - $membersCount;
           $scheduleData     = "シフト作成不可\n最低必要人数：あと".$notEnoughMembers.'人';
       }
       return $scheduleData;
    }
    function adjustmentSchedule( $scheduleYear, $scheduleMonth )
    {
       $yearMonth       = $scheduleYear.'-'.$scheduleMonth;
       $mostShifted     = 0;
       $leastShifted    = 0;
       $members         = new Member;
       $members         = $members->all();
       $startToEndDate  = self::getStartToEndDate( $scheduleYear, $scheduleMonth );
       $startDate       = $startToEndDate[ 0 ];
       $endDate         = $startToEndDate[ 1 ];
       $allSchedules    = Schedule::whereBetween( 'shift_date', [ $startDate, $endDate ] )->get();
       $shiftDates      = $allSchedules->pluck('shift_date')->unique()->sortBy('shift_date')->values();
       $memberInfo     = [];
        foreach( $members as $member )
        {
            $memberId       = $member[ 'member_id' ];
            $memberSchedule = $allSchedules->where( 'member_id', $memberId )->all();
            $memberSchedule = count( $memberSchedule );
            $memberInfo[$memberId] = $memberSchedule;
        }
        do
        {
            $mostShifted         = max( $memberInfo );
            $leastShifted        = min( $memberInfo );
            $mostShiftedId       = array_keys( $memberInfo, max( $memberInfo ) )[ 0 ];
            $leastShiftedId      = array_keys( $memberInfo, min( $memberInfo ) )[ 0 ];
            $shiftTimeDifference = $mostShifted - $leastShifted;
            if( $shiftTimeDifference > 1 )
            {
                foreach( $shiftDates as $shiftDate )
                {
                    $canBreak                = false;
                    $shiftDateInfo           = Schedule::whereBetween( 'shift_date', [ $shiftDate, $shiftDate ])->get();
                    $shiftedIdCount          = $shiftDateInfo->whereBetween( 'member_id' ,  [ $leastShiftedId, $leastShiftedId ] )->count();
                    $isNotExistsLeastShifted = $shiftedIdCount === 0;
                    if( $isNotExistsLeastShifted )
                    {
                        $shiftDay                   = substr( $shiftDate, -2, 2 );
                        $isSixConsecutiveWorkBefore = $shiftDay - 6 < 1;
                        if( !$isSixConsecutiveWorkBefore )
                        {
                           $sixDaysBeforeDate          = $yearMonth . '-'.( self::addZero($shiftDay - 6));
                           $consecutiveWorkTimesBefore = Schedule::whereBetween( 'shift_date', [ $sixDaysBeforeDate, $shiftDate ])
                                                                 ->where( 'member_id', $leastShiftedId )
                                                                 ->count();
                            echo( $consecutiveWorkTimesBefore);
                           $isSixConsecutiveWorkBefore = $consecutiveWorkTimesBefore < 6;
                        }
                        $endDay                    = substr( $endDate, -2, 2 );
                        $isSixConsecutiveWorkAfter = $shiftDay + 6 > $endDay;
                        if( !$isSixConsecutiveWorkAfter )
                        {
                            $sixDaysAfterDate          = $yearMonth . '-'. ( self::addZero($shiftDay + 6 ) );
                            $consecutiveWorkTimesAfter = Schedule::whereBetween( 'shift_date', [ $shiftDate, $sixDaysAfterDate])
                                                                 ->where( 'member_id', $leastShiftedId )
                                                                 ->count();
                            echo( $consecutiveWorkTimesAfter);
                            $isSixConsecutiveWorkAfter = $consecutiveWorkTimesAfter < 6;
                        }
                        if( $isSixConsecutiveWorkBefore && $isSixConsecutiveWorkAfter )
                        {
                            $restEnoughBefore        = false;
                            $restEnoughAfter         = false;
                            $shifts                  = new Shift;
                            $allShifts               = $shifts->all();
                            $shiftDayTomorrow        = $shiftDay + 1;
                            $shiftDayYesterday       = $shiftDay - 1;
                            $shiftDayTomorrow        = self::addZero( $shiftDayTomorrow );
                            $shiftDayYesterday       = self::addZero( $shiftDayYesterday );
                            $shiftDateTomorrow       = $yearMonth . '-' . $shiftDayTomorrow;
                            $shiftDateYesterday      = $yearMonth . '-' . $shiftDayYesterday;
                            $shiftDateTomorrowInfo   = Schedule::where( 'shift_date', $shiftDateTomorrow)
                                                               ->where( 'member_id' , $leastShiftedId )
                                                               ->get();
                            $shiftDateTomorrowInfo   = $shiftDateTomorrowInfo->pluck( 'shift_id')->all();
                            $shiftDateYesterdayInfo  = Schedule::where( 'shift_date', $shiftDateYesterday)
                                                               ->where('member_id', $leastShiftedId )
                                                               ->get();
                            $shiftDateYesterdayInfo  = $shiftDateYesterdayInfo->pluck('shift_id')->all();
                            if(  empty( $shiftDateTomorrowInfo ) ){ $restEnoughAfter = true; }
                            else
                            {
                                $shiftDateTomorrowInfo   = $shiftDateTomorrowInfo[ 0 ];
                                $tomorrowWillWorkedAt    = Shift::where( 'id', $shiftDateTomorrowInfo )->first()->pluck( 'start_at' );
                                $tomorrowWillWorkedAt    = substr( $tomorrowWillWorkedAt[ $shiftDateTomorrowInfo - 1 ], 0, 2  );
                                $tomorrowWillWorkedAt    = ( integer )$tomorrowWillWorkedAt;
                            }
                            if(  empty( $shiftDateYesterdayInfo ) ){ $restEnoughBefore = true; }
                            else
                            {
                                $shiftDateYesterdayInfo   = $shiftDateYesterdayInfo[ 0 ];
                                $yesterdayTillWorkedAt    = Shift::where( 'id', $shiftDateYesterdayInfo )->first()->pluck( 'end_at' );
                                $yesterdayTillWorkedAt    = substr( $yesterdayTillWorkedAt[$shiftDateYesterdayInfo - 1 ] , 0, 2);
                                $yesterdayTillWorkedAt    = ( integer )$yesterdayTillWorkedAt;
                            }
                            for( $i = 0; $i < 3; $i++)
                            {
                                $shiftStartAt = $allShifts->pluck( 'start_at' )->all()[$i];
                                $shiftEndAt   = $allShifts->pluck( 'end_at' )->all()[$i];
                                $shiftId      = $allShifts->pluck( 'id' )->all()[$i];
                                $shiftStartAt = substr( $shiftStartAt, 0, 2 );
                                $shiftEndAt   = substr( $shiftEndAt, 0, 2 );
                                if( !$restEnoughBefore )
                                {
                                    $yesterdayTillWorkedAt   = Shift::where( 'id', $shiftDateYesterdayInfo )->first()->pluck( 'end_at' );
                                    $yesterdayTillWorkedAt   = substr( $yesterdayTillWorkedAt[$shiftDateYesterdayInfo -1 ] , 0, 2);
                                    $shiftStartAt           +=  self::ONE_DAY_HOURS;
                                    $restEnoughBefore        = ( $shiftStartAt - $yesterdayTillWorkedAt >= 12 );
                                }
                                if( !$restEnoughAfter )
                                {
                                    $tomorrowWillWorkedAt    = Shift::where( 'id', $shiftDateTomorrowInfo )->first()->pluck( 'start_at' );
                                    $tomorrowWillWorkedAt    = substr( $tomorrowWillWorkedAt[$shiftDateTomorrowInfo -1 ], 0, 2  );
                                    $tomorrowWillWorkedAt   += self::ONE_DAY_HOURS;
                                    $restEnoughAfter = ( $tomorrowWillWorkedAt - $shiftEndAt >= 12 );
                                }
                                if( $restEnoughBefore && $restEnoughAfter )
                                {
                                    $updated_At = date( "Y-m-d H:i:s");
                                    $created_At = $updated_At;
                                    Schedule::create
                                    ([
                                        'shift_date' => $shiftDate,
                                        'shift_id'   => $shiftId,
                                        'member_id'  => $leastShiftedId,
                                        'updated_at' => $updated_At,
                                        'created_at' => $created_At
                                    ])
                                    ->save();
                                    $memberInfo[ $leastShiftedId ] += 1;
                                    $canBreak = true;
                                    break;
                                }
                            }
                        }
                    }
                    if( $canBreak ){ break; }
                }
            }
        }
        while( $shiftTimeDifference > 1 );
    }
    function convertFormat( $scheduleData )
    {
        //クライアント側で表示させやすくするためにデータ構造を変更する
        //エラーメッセージが引数に来ている場合はスルー
        if( gettype( $scheduleData ) === "string" ){ return $scheduleData; }
        else
        {
            $formattedScheduleData = [];
            $members               = Member::all();
            $shift                 = new Shift;
            $scheduleData          = $scheduleData->sortBy( 'shift_date' )->sortBy( 'shift_id' )->values();
            $allScheduleDates      = $scheduleData->pluck( 'shift_date' )->unique()->values()->toArray();
            $allShifts             = $scheduleData->pluck( 'shift_id' )->unique()->values()->toArray();

            foreach( $allScheduleDates as $scheduleDate )
            {
                $oneOfDate         = $scheduleDate;
                $oneOfDateSchedule = $scheduleData->where( 'shift_date', '=', $scheduleDate );
                $oneOfDateArray    = array();
                $oneDateShifts     = array();
                $weekChar          = '';
                $weekChar          = date("w", strtotime($scheduleDate));
                $holiday           = new HolidayController;
                $isHoliday         = false;
                $isHoliday         = $holiday->checkHoliday( $scheduleDate );
                foreach( $allShifts as $oneOfShift )
                {
                    $oneOfShiftTimeDatas  = $oneOfDateSchedule->where( 'shift_id', '=', $oneOfShift );
                    $oneOfShiftTime       = $shift->where( 'id', '=', $oneOfShift )->first();
                    $shiftStartAt         = substr( $oneOfShiftTime[ 'start_at' ], 0, -3 );
                    $shiftEndAt           = substr( $oneOfShiftTime[ 'end_at' ], 0, -3 );
                    $oneOfShiftTime       = $shiftStartAt.'~'.$shiftEndAt;
                    $oneOfShiftMemberIds  = $oneOfShiftTimeDatas->pluck( 'member_id' )->values();
                    $shiftMembers         = '';
                    foreach( $oneOfShiftMemberIds as $shiftMemberId )
                    {
                        $shiftMember   = $members->where( 'member_id', '=', $shiftMemberId )->first();
                        $shiftMember   = $shiftMember[ 'sei' ];
                        $shiftMembers .= $shiftMember.' ';
                    }
                    global $oneOfDateArray;
                    $oneOfDateArray[ 'time' ]    = $oneOfShiftTime;
                    $oneOfDateArray[ 'members' ] = $shiftMembers;
                    array_push($oneDateShifts, $oneOfDateArray);
                }
                $formattedSchedule[ 'date' ]    = $oneOfDate;
                $formattedSchedule[ 'week' ]    = $weekChar;
                $formattedSchedule['isHoliday'] = $isHoliday;
                $formattedSchedule[ 'shifts' ]  = $oneDateShifts;
                array_push( $formattedScheduleData, $formattedSchedule );
            }
            $formattedScheduleData =  json_encode( $formattedScheduleData, JSON_PRESERVE_ZERO_FRACTION );
            return $formattedScheduleData;
       }
    }
    function getSchedule( Request $request )
    {
        $scheduleYear       = $request->input( 'thisYear' );
        $scheduleMonth      = $request->input( 'thisMonth' );
        $startToEndDate     = self::getStartToEndDate( $scheduleYear, $scheduleMonth );
        $scheduleEndDay     = self::getEndDay( $scheduleYear, $scheduleMonth );
        $scheduleYearMonth  = $scheduleYear.'-'.$scheduleMonth;
        $scheduleStartDate  = $startToEndDate[0];
        $scheduleEndDate    = $startToEndDate[1];
        $scheduleData       = Schedule::whereBetween( 'shift_date', [ $scheduleStartDate, $scheduleEndDate ] )->get();
        $dataCount          = $scheduleData->count();
        $isExistSchedule    = $dataCount > 0;
        if( !$isExistSchedule )
        {
            //DBにシフトのデータなかったら作る
            $scheduleMonth = self::addZero( $scheduleMonth );
            $scheduleData = self::makeSchedule( $scheduleEndDay, $scheduleYearMonth, $request, $scheduleYear, $scheduleMonth);
        }
        $scheduleData = self::convertFormat( $scheduleData );
        return $scheduleData;
    }
    function addZero( $num )
    {
        //DBの日付データは数字が0埋めされているので、DBに問い合わせるときにこの処理がいる
        if( $num  < 10 ) { $num = str_pad( $num, 2, 0, STR_PAD_LEFT ); }
        return $num;
    }
    function getStartToEndDate( $thisYear, $thisMonth)
    {
        $EndDay         = self::getEndDay( $thisYear, $thisMonth );
        if( $thisMonth  < 10 ) { $month = str_pad( $thisMonth, 2, 0, STR_PAD_LEFT ); }
        $startDate      = $thisYear.'-'.$thisMonth.'-'.'01';
        $endDate        = $thisYear.'-'.$thisMonth.'-'.$EndDay;
        $startToEndDate = [ $startDate, $endDate ];
        return $startToEndDate;
    }
    function getTotalWorkingHours( Request $request )
    {
        $thisYear           = $request->input( 'thisYear' );
        $thisMonth          = $request->input( 'thisMonth' );
        $startToEndDate     = self::getStartToEndDate( $thisYear, $thisMonth );
        $startDate          = $startToEndDate[ 0 ];
        $endDate            = $startToEndDate[ 1 ];
        $allSchedules       = Schedule::whereBetween( 'shift_date', [ $startDate, $endDate ] )->get();
        $members            = new Member;
        $members            = $members->all();
        $totalWorkingHours  = [];
        $memberInfo         = [];
        foreach( $members as $member )
        {
            $memberId   =  $member[ 'member_id' ];
            $memberName =  $member[ 'sei' ];
            $memberSchedule = $allSchedules->where( 'member_id', $memberId )->all();
            $memberSchedule = count( $memberSchedule );
            if( $memberSchedule > 0 )
            {
                $memberInfo[ 'name' ]  = $memberName;
                $memberInfo[ 'hours' ] = $memberSchedule * self::WORKING_HOURS;
                array_push( $totalWorkingHours, $memberInfo );
            }
        }
        $totalWorkingHours = json_encode( $totalWorkingHours, JSON_PRESERVE_ZERO_FRACTION );
        return $totalWorkingHours;
    }
}
