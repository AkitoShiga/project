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
        $value = $member::all();
        return $value;
    }
    public function deleteMembers(Request $request) {
        $member = new Member;
        $id = $request->input('id');
        $member = $member->find($id);
        $member->is_deleted = 1;
        $member->save();
    }
    public function addMembers(Request $request) {
        $member = new Member;
        $sei = $request->input('sei');
        $mei = $request->input('mei');
        $updated_at = date('Y-m-d H:i:s');
        $created_at = date('Y-m-d H:i:s');
        $member->create([
           // compact('sei', 'mei', 'updated_at', 'created_at')
            'mei' => $request->input('mei'),
            'sei' => $request->input('sei'),
            'is_deleted' => 0,
            'updated_at' => date("Y-m-d H:i:s"),
            'created_at' => date("Y-m-d H:i:s"),
        ]);
        $member->save();
        return response()->json(['message' => 'Added a Member'], 200);
    }
}
