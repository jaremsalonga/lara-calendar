<?php

namespace App\Http\Controllers;
use App\CalendarModel;

use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(){
 
        $calendar = CalendarModel::all('name', 'description', 'color','start','end');
        return response()->json($calendar);
    }

    public function bulkStore(Request $request){
        CalendarModel::insert($request->all());
        return response()->json($request->all());
    }

}
