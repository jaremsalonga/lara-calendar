<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CalendarModel extends Model
{
    //
    protected $primaryKey = 'id';
    protected $table = 'calendar';
    protected $fillable = ['eventName', 'description', 'color','startDate','endDate'];
    public $timestamps = false;
}
