<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameAccount extends Model
{
    protected $fillable = ['user_email','game_title','username','password','status'];
}
