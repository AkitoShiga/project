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
     //protected $fillabele = ['updated_at', 'created_at'];
     protected $fillable = ['sei', 'mei','updated_at', 'created_at'];
     protected $guarded   = ['id'];
}
