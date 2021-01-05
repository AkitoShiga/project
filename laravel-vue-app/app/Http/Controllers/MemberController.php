<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\Controller;
use App\Models\Member;

class MemberController extends Controller
{
    //
    public function getMembers() {
        $member = new Member;
        $value = $member::all();
        return $value;
        //$member = Member::first();
       // return $member;
    }
    public function deleteMembers(Request $request) {
        $member = new Member;
        $member = $member->find($request->input('id'));
        $member->is_deleted = false;
        $member->save();
    }
}
