<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Imports\UsersImport;

use App;
use Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersReportExport;


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
        $md5_password = md5('123456');
        $date = date('Y-m-d H:i:s');
        $data = array(
          
            'user_name' => $request->name,
            'email' => $request->email,
            'mobile_no' => $request->phone,
            'company_id' => $request->company_id,
            'city' => $request->city,
            'password'=>$md5_password,
            'user_type'=>3,
            'is_active'=>1,
            'last_login'=>$date,

            );

            $aid= DB::table('users')->insertGetId($data);

        // $campaigns_data = DB::table('campaigns')
        // ->where('company_id','=',$request->company_id)
        // ->get();
        // foreach($campaigns_data as $campaign){
        //     $data = array(
          
        //         'user_id' => $aid,
        //         'campaign_id' => $campaign->id,
        //         );
        //         $pid= DB::table('campaign_participants')->insertGetId($data);
        // }
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

        $query=DB::table('users as u')
        ->join('company as c','u.company_id', '=','c.id')
        ->where('u.deleted','=',0)
        ->where('c.deleted','=',0)
        ->where('u.user_type','=',3)
        ->where('u.is_active','=',1)
        ->select('u.*','c.company_name',DB::raW('u.avatar as avatar'))
        ->orderBy('u.created_at','DESC');

        // if ($request['logged_user_type'] == 1) {
            $users_info = $query->get();
        // } 

        return response()->json(['status' => true, 'data' => $users_info]);
           
    }

    public function get_single_user(REQUEST $request){
        $user_info=DB::table('users as u')
        ->join('company as c','u.company_id', '=','c.id')
        ->where('u.id','=',$request->id)
        ->where('u.deleted','=',0)
        ->where('c.deleted','=',0)
        ->select('u.*','c.company_name',DB::raw('u.avatar as avatar'))
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
        $update_data=DB::table('users')
        ->where('id','=',$request->id)
        ->update([
            'company_id' => $request->company_id,
            'user_name' => $request->user_name,
            'email' => $request->email,
            'mobile_no' => $request->mobile_no,
            'city' => $request->city,
            'avatar'=> $image
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
        $deleted_info=DB::table('users')
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
    public function users_file_import(Request $request) 
    {
    $collection = Excel::toCollection(new UsersImport, $request->file('file'))->toArray();
    // $collection = Excel::toCollection(new UsersImport, $request->file('file'), null, 'csv')->toArray();
    $data1 = $collection[0];
    // return $data1;
    $date = date('Y-m-d H:i:s');
    $count=0;
    $md5_password = md5('123456');
    $date = date('Y-m-d H:i:s');
    foreach ($data1 as $user) {
    
        $company_info = DB::table('company as c')
        ->where('c.company_name','=',$user['company_name']) 
        ->first();

        $user_info=DB::table('users')
        ->where('email','=',$user['email'])
        ->first();
        if($user_info){
            continue;

        }
   else{
    if($company_info && $user['user_name'] && $user['email'] && $user['mobile_number'] && $user['city'])
    {
        // return true;
       $count= $count+1;
  
        $data = array(
            'company_id' => $company_info->id,
            'user_name' => $user['user_name'],
            'email' => $user['email'],
            'mobile_no' => $user['mobile_number'],
            'city' => $user['city'],
            'password'=>$md5_password,
            'user_type'=>3,
            'last_login'=>$date,
            'is_active'=>1

            );
          
            $aid= DB::table('users')->insertGetId($data);}
            else{
                continue;
            }
        }
        }
                return json_encode(array('status' => true, 'msg' => 'Users data uploaded successfully','count'=>$count));
            
    }
    public function download_users_sample()
    {
        $filepath = public_path('samples/users_sample.csv');
        // return $filepath;
        return Response::download($filepath);
    }
    public function get_users_report(Request $request)
    {
        // Assuming 'rows' is an array in the request
        $rows = $request->input('rows');
        // return $rows;
        // Additional validation if needed
        if (!is_array($rows)) {
            return response()->json(['error' => 'Invalid data format'], 400);
        }
    
        return Excel::download(new UsersReportExport($rows), 'reports' . '.csv');
    }
}