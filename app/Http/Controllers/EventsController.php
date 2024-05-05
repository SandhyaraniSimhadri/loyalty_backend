<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App;
use Response;
use Maatwebsite\Excel\Facades\Excel;


use App\Services\UserDataService;


class EventsController extends Controller{
    public function __construct()
    {
    }
    

    public function add_event(Request $request)
    {
        $image=null;
        if ($request->hasFile('image')) {
            // return $request->hasFile('homeTeamLogo')
            $image = $request->file('image')->store('images', 'public');
            $image = 'storage/'.$image;
        }
        $date = date('Y-m-d H:i:s');
        $data = array(
          
            'title' => $request->title,
            'description' => $request->description,
            'terms_conditions'=> $request->terms_conditions
            );

            $aid= DB::table('events')->insertGetId($data);

        if ($aid) { 
                $data = array('status' => true, 'msg' => 'Event added successfully');
                return response()->json($data);

        } else {
            // return true;
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }

    }
    public function get_events(REQUEST $request){


        $query=DB::table('events as e')
        ->where('e.deleted','=',0)
        ->select('e.*',DB::raW('e.image as avatar'))
        ->orderBy('e.created_at','DESC');
    
        // if ($request['logged_user_type'] == 1) {
          
        // }
        $events_info = $query->get();
        return response()->json(['status' => true, 'data' => $events_info]);
           
    }

    public function get_single_event(REQUEST $request){
        $event_info=DB::table('events as e')
        ->where('e.id','=',$request->id)
        ->where('e.deleted','=',0)
        ->select('e.*',DB::raw('e.image as avatar'))
        ->first();
        $data = array('status' => true, 'data' => $event_info);
        return response()->json($data);
    }
    
    public function update_event(REQUEST $request){
        $image=null;
        if ($request->hasFile('image')) {
            // return $request->hasFile('homeTeamLogo')
            $image = $request->file('image')->store('images', 'public');
            $image = 'storage/'.$image;
        }
        $update_data=DB::table('events')
        ->where('id','=',$request->id)
        ->update([
            
            'title' => $request->title,
            'description' => $request->description,
            'terms_conditions'=> $request->terms_conditions

        ]);
        if($update_data){
            $data = array('status' => true, 'msg' => 'Event details updated successfully');
            return response()->json($data);
            } 
        else {
            // return true;
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }
    }
    public function delete_event(REQUEST $request){
        $deleted_info=DB::table('events')
        ->where('id','=',$request->id)
        ->update([
            'deleted'=>1,
        ]);
    
        if($deleted_info){
            $data = array('status' => true, 'msg' => 'Event deleted successfully');
            return response()->json($data);
            } 
        else {
            // return true;
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }
    }
    
}