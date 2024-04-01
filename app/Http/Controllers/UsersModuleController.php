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


class UsersModuleController extends Controller{
    public function __construct()
    {
    }
    

    public function add_user(Request $request)
    {
        $image=null;
        if ($request->hasFile('image')) {
            // return $request->hasFile('homeTeamLogo')
            $image = $request->file('image')->store('images', 'public');
            $image = 'storage/'.$image;
        }
        $date = date('Y-m-d H:i:s');
        $data = array(
          
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'company_id' => $request->company_id,
            'city' => $request->city
            );

            $aid= DB::table('users_data')->insertGetId($data);

        if ($aid) { 
                $data = array('status' => true, 'msg' => 'User added successfully');
                return response()->json($data);

        } else {
            // return true;
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }

    }
    public function get_users(REQUEST $request){


        $query=DB::table('users_data as u')
        ->join('company as c','u.company_id', '=','c.id')
        ->where('u.deleted','=',0)
        ->where('c.deleted','=',0)
        ->select('u.*','c.company_name',DB::raW('u.image as avatar'))
        ->orderBy('u.created_at','DESC');

        if ($request['logged_user_type'] == 1) {
            $users_info = $query->get();
        } 

        return response()->json(['status' => true, 'data' => $users_info]);
           
    }

    public function get_single_user(REQUEST $request){
        $user_info=DB::table('users_data as u')
        ->join('company as c','u.company_id', '=','c.id')
        ->where('u.id','=',$request->id)
        ->where('u.deleted','=',0)
        ->where('c.deleted','=',0)
        ->select('u.*','c.company_name',DB::raw('u.image as avatar'))
        ->first();
        $data = array('status' => true, 'data' => $user_info);
        return response()->json($data);
    }
    
    public function update_user(REQUEST $request){
        $image=null;
        if ($request->hasFile('image')) {
            // return $request->hasFile('homeTeamLogo')
            $image = $request->file('image')->store('images', 'public');
            $image = 'storage/'.$image;
        }
        $update_data=DB::table('users_data')
        ->where('id','=',$request->id)
        ->update([
            'company_id' => $request->company_id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'city' => $request->city
        ]);
        if($update_data){
            $data = array('status' => true, 'msg' => 'User details updated successfully');
            return response()->json($data);
            } 
        else {
            // return true;
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }
    }
    public function delete_user(REQUEST $request){
        $deleted_info=DB::table('users_data')
        ->where('id','=',$request->id)
        ->update([
            'deleted'=>1,
        ]);
    
        if($deleted_info){
            $data = array('status' => true, 'msg' => 'User deleted successfully');
            return response()->json($data);
            } 
        else {
            // return true;
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }
    }
    
}