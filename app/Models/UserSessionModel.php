<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSessionModel extends Model
{
    protected $table        = 'tbl_user_session';
    protected $primaryKey   = 'user_session_id'; 
}
