<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Mail\ForgotPassword;
use App\Imports\citiesImport;
use Illuminate\Support\Facades\Storage;
use App;
use Illuminate\Support\Facades\Http;

use Maatwebsite\Excel\Facades\Excel;

use App\Services\UserDataService;


class UsersController extends Controller{
    public function __construct()
    {
    }
   

    public function  check_user(Request $request)
    {
    //    dd($request);
        $this->validate($request, [
            'email' => 'required',
        
        ]);
        $date = date('Y-m-d H:i:s');
        $email = $request->input('email');

        
        // $md5_password = md5($request->input('password'));
        // return $email;
        // return $md5_password;

 
        $user_data = DB::table('users')
        ->where('email', '=', $email)
        ->select('id', 'user_name', 'email', 'avatar', 'mobile_no', 'token', 'is_active', 'user_type')
        ->first();

        // return $user_data;
        $md5_password = md5('123456');
        // return $user_data;
        
    
        if ($user_data) { 
            // return "hello";
            // $user_data=$user_data[0];
            $token = Str::random(60);
            $api_token = hash('sha256', $token);
            if($user_data->is_active){
                $update_data=DB::table('users')
                ->where('email','=',$email)
                ->update([
                    'last_login' => $date,
                    'token' => $api_token,
                    'password'=> $md5_password
                ]);
                $user_data = DB::table('users')
                ->where('email','=',$email)
                ->where('password','=',$md5_password)
                ->select('id','user_name','email','avatar','mobile_no','token','is_active','user_type')
                ->first();
                $data = array('status' => true, 'msg' => 'Login successfull!','user_status'=>'existed','data'=>$user_data);
                return response()->json($data);
            }
            else{
                if($request->input('password')=='123456'){
                    $update_data=DB::table('users')
                    ->where('email','=',$email)
                    ->where('password','=',$md5_password)
                    ->update([
                        'last_login' => $date,
                        'token' => $api_token,
                        'is_active'=> 1,
                        'password'=> $md5_password
                    ]);
                    $user_data = DB::table('users')
                    ->where('email','=',$email)
                    ->where('password','=',$md5_password)
                    ->select('id','user_name','email','avatar','mobile_no','token','is_active','user_type')
                    ->first();
                    $data = array('status' => true, 'msg' => 'Login successfull!','user_status'=>'existed','data'=>$user_data);
                    return response()->json($data);
                }
                else{
                $data = array('status' => false, 'msg' => 'Account is inactive. Please contact customer care!');
                return response()->json($data); }
            }

        } else {
            // return "hii";
            $name=  $request->input('name');
            $token = Str::random(60);
            $api_token = hash('sha256', $token);
            $data = array(
                'email' => $email,
                'user_name' => $name,
                'password' => $md5_password,
                'token' => $api_token,
                'user_type'=>3,
                'is_active'=>1
                );
    
                $gid= DB::table('users')->insertGetId($data);
                $data = array('status' => true, 'msg' => 'Registered successfull!','user_status'=>'new','data'=>$data);
                return response()->json($data);
            }
        }

    
    public function verify_user(Request $request)
    {
    //    dd($request);
        $this->validate($request, [
            'email' => 'required',
            'password' => 'required'
        ]);
        $date = date('Y-m-d H:i:s');
        $email = $request->input('email');

        
        $md5_password = md5($request->input('password'));
        // return $email;
        // return $md5_password;

      
        $user_data = DB::table('users')
        ->where('email','=',$email)
        ->where('password','=',$md5_password)
        ->select('is_active')
        ->get();
        // return $user_data;
        
        // return $user_data->is_active;
        if($user_data){
        $user_data=$user_data[0];
            
        }
        if ($user_data) { 
            $token = Str::random(60);
            $api_token = hash('sha256', $token);
            if($user_data->is_active){
                $update_data=DB::table('users')
                ->where('email','=',$email)
                ->where('password','=',$md5_password)
                ->update([
                    'last_login' => $date,
                    'token' => $api_token,
                ]);
                $user_data = DB::table('users')
                ->where('email','=',$email)
                ->where('password','=',$md5_password)
                ->select('id','user_name','email','avatar','mobile_no','token','is_active','user_type')
                ->first();
                $data = array('status' => true, 'msg' => 'Login successfull!','data'=>$user_data);
                return response()->json($data);
            }
            else{
                if($request->input('password')=='123456'){
                    $update_data=DB::table('users')
                    ->where('email','=',$email)
                    ->where('password','=',$md5_password)
                    ->update([
                        'last_login' => $date,
                        'token' => $api_token,
                    ]);
                    $user_data = DB::table('users')
                    ->where('email','=',$email)
                    ->where('password','=',$md5_password)
                    ->select('id','user_name','email','avatar','mobile_no','token','is_active','user_type')
                    ->first();
                    $data = array('status' => true, 'msg' => 'Login successfull!','data'=>$user_data);
                    return response()->json($data);
                }
                else{
                $data = array('status' => false, 'msg' => 'Account is inactive. Please contact customer care!');
                return response()->json($data); }
            }

        } else {
            // return true;
            $data = array('status' => false, 'msg' => 'Login Failed. Please enter correct credentials');
            return response()->json($data);
        }

    }

    public function register(Request $request)
    {
    //    dd($request);
    // return $request;
        $this->validate($request, [
            'email' => 'required',
            'password' => 'required',
            'username'=>'required',
            'phone'=>'required',
            'location' => 'required',
        ]);
        $date = date('Y-m-d H:i:s');
        $email = $request->input('email');
        $md5_password = md5($request->input('password'));

        $user_info=DB::table('super_admins')
        ->where('email','=',$email)
        ->first();
        if($user_info){
            $data = array('status' => false, 'msg' => 'Email already existed, try with another email.');

        }
     

   

    }
    public function sent_OTP(REQUEST $request){
        $otp = rand(1000, 9999);
        $user_data = DB::table('users')
        ->where('email','=',$request->email)
        ->first();
        if($user_data){
            $update_data=DB::table('users')
            ->where('email','=',$request->email)
            ->update([
                'OTP' => $otp,
            ]);
            $data = [
                'email' => $request->email,
                'otp' => $otp,
                'user_name' => $user_data->user_name
            ];        
            Mail::to($request->email)->send(new ForgotPassword($data));
    
            $response = array('status' => true, 'msg' => 'Otp sent to your registered email','data'=>$request->email);
            return json_encode($response);

        } else {
            $response = array('status' => false, 'msg' => 'Invalid email');
            return json_encode($response);
        }

        }
        public function verify_OTP(Request $request)
        {
            $this->validate($request, [
                'email' => 'required',
                'otp' => 'required'
            ]);
            $email = $request->input('email');
            $otp = $request->input('otp');
            $result = DB::table('users')
                ->where('email', '=', $email)
                ->where('otp', '=', $otp)
                ->first();
            if ($result) {
                $response = array('status' => true, 'msg' => 'Otp verified successfully!','data'=>$result->email);
                return json_encode($response);
            } else {
                $response = array('status' => false, 'msg' => 'Invalid OTP, please enter valid OTP');
                return json_encode($response);
            }
        }
        public function update_password(Request $request)
        {
    
            //validation
            $this->validate($request, [
                'email' => 'required',
                'otp' => 'required',
                'confirm_password' => 'required'
            ]);
            $current_date_time = date('Y-m-d H:i:s');
    
            $email = $request->input('email');
            $otp = $request->input('otp');
            $password = $request->input('confirm_password');
            $result = DB::table('users')
                ->where('email', '=', $email)
                ->where('otp', '=', $otp)
                ->first();
                $md5_password = md5($request->input('confirm_password'));
            if ($result) {
                if($result->password == $md5_password){
                    $response = array('status' => true, 'msg' => 'Duplicate');
                    return json_encode($response);    
                }
                else{
                DB::table('users')
                    ->where('email', $email)
                    ->where('otp', $otp)
                    ->update([
                        'password' =>$md5_password,
                        'otp' => 0,
                    ]);
                $response = array('status' => true, 'msg' => 'Password changed successfully');
                return json_encode($response);}
            } else {
                $response = array('status' => false, 'msg' => 'Invalid data');
                return json_encode($response);
            }
    
        }

        public function set_password(Request $request)
        {
    
            //validation
            $this->validate($request, [
                'email' => 'required',
                'password' => 'required',
                'confirm_password' => 'required'
            ]);
            $current_date_time = date('Y-m-d H:i:s');
    
            $email = $request->input('email');
            $password = $request->input('password');
            $confirm_password = $request->input('confirm_password');
            $result = DB::table('users')
                ->where('email', '=', $email)
                ->first();
                // $result=$result[0];
                $md5_password = md5($request->input('confirm_password'));
                // return $result->email;
            if ($result) {
            //    return "hii";
                DB::table('users')
                    ->where('email', $email)
                    ->update([
                        'password' =>$md5_password,
                    ]);
                $response = array('status' => true, 'msg' => 'Password changed successfully');
                return json_encode($response);
            } else {
                $response = array('status' => false, 'msg' => 'Invalid data');
                return json_encode($response);
            }
    
        }
        

        public function get_countries(Request $request)
        {
            $result = DB::table('countries')
                ->where('deleted', '=', 0)
                ->get();

                // https://backstage.taboola.com/backstage/api/1.0/resources/countries

            return response()->json(['status' => true, 'data' => $result]);
        }
     
    public function get_cities(REQUEST $request)
    {
        // $result = DB::table('countries')
        //         ->where('deleted', '=', 0)
        //         ->where('country','=',$request->)
        //         ->first();

        
        $result = DB::table('cities')
                ->where('deleted', '=', 0)
                ->where('country_id', '=', $request->countryCode)

                ->get();

                // https://backstage.taboola.com/backstage/api/1.0/resources/countries

            return response()->json(['status' => true, 'data' => $result]);
    }
    
    public function  set_registration(Request $request)
    {
    //    dd($request);
        $this->validate($request, [
            'email' => 'required',
        
        ]);
        $date = date('Y-m-d H:i:s');
        $email = $request->input('email');
        $campaign = DB::table('campaigns')
        ->where('id','=',$request->campaign_id)
        ->first();
    
            $password = $request->input('password');
            $confirm_password = $request->input('confirm_password');
            $result = DB::table('users')
                ->where('email', '=', $email)
                ->first();
                // $result=$result[0];
                $md5_password = md5($request->input('confirm_password'));
                // return $result->email;
            if ($result) {
            //    return "hii";
                DB::table('users')
                    ->where('email', $email)
                    ->update([
                        'user_name' => $request->input('user_name'),
                        'user_type'=>3,
                        'is_active'=>1,
                        'password' =>$md5_password,
                        'company_id'=>$campaign->company_id
                    ]);
                $response = array('status' => true, 'msg' => 'Information updated successfully');
                return json_encode($response);
        } else {
            $token = Str::random(60);
            $api_token = hash('sha256', $token);
            $data = array(
                'email' => $email,
                'user_name' => $request->input('user_name'),
                'password' => $md5_password,
                'token' => $api_token,
                'user_type'=>3,
                'is_active'=>1,
                'company_id'=>$campaign->company_id
                );
    
                $gid= DB::table('users')->insertGetId($data);
                $data = array('status' => true, 'msg' => 'Registered successfull!','user_status'=>'new','data'=>$data);
                return response()->json($data);
            }
        }
    
    }
