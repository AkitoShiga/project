<?php

namespace App\Http\Controllers;

//use http\Message;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;

class MemberController extends Controller
{
    //
    public function getMembers() {
        $member = new Member;
        $value = $member::where( 'is_deleted', 0 )->get( [ 'member_id', 'sei', 'mei', 'is_deleted' ] );
        return $value;
    }
    public function deleteMembers(Request $request) {
       $members = new Member;
        $id = $request->input('id');
        /*
        $member = $member->where('member_id', $id);
        $member->is_deleted = 1;
        $member->save();
        */
        $member = $members->where('member_id', $id)->first();
        $member->is_deleted = 1;
        $member->save();

    }
    public function addMembers(Request $request) {
        $member = new Member;
        $sei = $request->input('sei');
        $mei = $request->input('mei');
        $updated_at = date('Y-m-d H:i:s');
        $created_at = date('Y-m-d H:i:s');
        $member->sei            =  $sei;
        $member->mei            =  $mei;
        $member->is_deleted     =  0;
        $member->is_shifted     =  0;
        $member->shift_count    =  0;
        $member->last_worked_at =  null;
        $member->updated_at     =  $updated_at;
        $member->created_at     =  $created_at;
        $member->save();
        return response()->json(['message' => 'Added a Member'], 200);
    }
}
