<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Holiday;

class HolidayController extends Controller
{
    function checkHoliday(  $checkDate ){
        $isHoliday           = false;
        $holiday             = Holiday::where( 'holiday_date', $checkDate )->count();
        $isHoliday           = $holiday === 1;
        return $isHoliday;
    }
}
