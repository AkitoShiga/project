<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
     protected $fillable  = [ 'sei', 'mei',  'is_deleted', 'is_shifted', 'shift_count', 'last_worked_at', 'updated_at', 'created_at'];
     protected $guarded   = [ 'member_id' ];
     protected $primaryKey = 'member_id';
}
