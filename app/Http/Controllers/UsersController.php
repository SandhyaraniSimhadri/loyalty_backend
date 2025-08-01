<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
// use App\Mail\ForgotPassword;
use App\Imports\citiesImport;
use Illuminate\Support\Facades\Storage;
use App;
use Illuminate\Support\Facades\Http;
use App\Mail\ForgotPasswordMail;

use Maatwebsite\Excel\Facades\Excel;

use App\Services\UserDataService;


class UsersController extends Controller{
    public function __construct()
    {
    }
//    public function userScore(Request $request)
// {
//     return response()->json(['status' => true, 'msg' => 'controller reached']);
// }

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
        ->select('id', 'user_name', 'email', 'avatar', 'mobile_no', 'token', 'is_active', 'user_type','login_times')
        ->first();

        $md5_password = md5('123456');
       
        
    
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
                    'password'=> $md5_password,
                    'login_times'=>$user_data->login_times+1,
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
                        'password'=> $md5_password,
                        'login_times'=>$user_data->login_times+1,
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
                $data = array('status' => true, 'msg' => 'Registered successfully please signin!','user_status'=>'new','data'=>$data);
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
        // return $email;

      
        $user_data = DB::table('users')
        ->where('email','=',$email)
        ->where('password','=',$md5_password)
        ->select('is_active','login_times')
        ->get();
        // return $user_data;
        // return $user_data;
        // return $user_data;
        
        // return $user_data->is_active;
        if(count($user_data) == 0){
            // return "hiiii";
          
                $data = array('status' => false, 'msg' => 'Login Failed. Please enter correct credentials');
                return response()->json($data);
            
        }
        else{
            // return "hello";
            // return $user_data;
            $user_data=$user_data[0];  
       
            // return $user_data;
            $token = Str::random(60);
            $api_token = hash('sha256', $token);
            if($user_data->is_active){
                $update_data=DB::table('users')
                ->where('email','=',$email)
                ->where('password','=',$md5_password)
                ->update([
                    'last_login' => $date,
                    'token' => $api_token,
                    'login_times'=>$user_data->login_times+1,
                ]);
                $user_data = DB::table('users as u')
                ->leftJoin('company as c','u.company_id','=','c.id')
                ->where('u.email','=',$email)
                ->where('u.password','=',$md5_password)
                ->select('u.id','u.user_name','u.name','u.email','u.avatar','u.mobile_no','u.token','u.is_active','u.user_type','c.company_name','u.company_id','u.twitter_url','u.facebook_url','u.google_url','u.linkedin_url','u.instagram_url','u.quora_url','u.login_times as first_time_login')
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
                        'login_times'=>$user_data->login_times+1,
                    ]);
                    $user_data = DB::table('users as u')
                ->leftJoin('company as c','u.company_id','=','c.id')
                ->where('u.email','=',$email)
                ->where('u.password','=',$md5_password)
                ->select('u.id','u.user_name','u.name','u.email','u.avatar','u.mobile_no','u.token','u.is_active','u.user_type','c.company_name','u.company_id','u.twitter_url','u.facebook_url','u.google_url','u.linkedin_url','u.instagram_url','u.quora_url','u.login_times as first_time_login')
                ->first();
                if($request->input('password')=='123456'){
                    $user_data->first_time_login='Yes';
                }
                else{
                    $user_data->first_time_login='No';
                }
                return $user_data;
                    $data = array('status' => true, 'msg' => 'Login successfull!','data'=>$user_data);
                    return response()->json($data);
                }
                else{
                $data = array('status' => false, 'msg' => 'Account is inactive. Please contact customer care!');
                return response()->json($data); }
            }

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
        // public function verify_OTP(Request $request)
        // {
        //     $this->validate($request, [
        //         'email' => 'required',
        //         'otp' => 'required'
        //     ]);
        //     $email = $request->input('email');
        //     $otp = $request->input('otp');
        //     $result = DB::table('users')
        //         ->where('email', '=', $email)
        //         ->where('otp', '=', $otp)
        //         ->first();
        //     if ($result) {
        //         $response = array('status' => true, 'msg' => 'Otp verified successfully!','data'=>$result->email);
        //         return json_encode($response);
        //     } else {
        //         $response = array('status' => false, 'msg' => 'Invalid OTP, please enter valid OTP');
        //         return json_encode($response);
        //     }
        // }
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
                // $result= response()->json($result); 
                // return $result;

            
                $md5_password = md5($request->input('confirm_password'));
                // return $result->email;
            if ($result) {
            //    return "hii";
                DB::table('users')
                    ->where('email', $email)
                    ->update([
                        'password' =>$md5_password,
                        'login_times'=>$result->login_times+1,
                    ]);
                    $data = array(
                        'user_id'=>$result->id,
                        'campaign_id'=>$result->campaign_id);

                        $pid= DB::table('campaign_users')->insertGetId($data);
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
        // return "hi";    
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
                DB::table('users')
                    ->where('email', $email)
                    ->update([
                        'user_name' => $request->input('user_name'),
                        'user_type'=>3,
                        'is_active'=>1,
                        'password' =>$md5_password,
                    ]);

                    $participant=DB::table('campaign_users')
                    ->where('user_id','=',$result->id)
                    ->where('campaign_id','=',$request->campaign_id)
                    ->first();
                    if($participant){

                    }
                    else{
                        $data = array(
                            'user_id'=>$result->id,
                            'campaign_id'=>$request->campaign_id);
                            $pid= DB::table('campaign_users')->insertGetId($data);
                    }
                $response = array('status' => true, 'msg' => 'Information updated successfully');
                return json_encode($response);
        } else {
            // return "hi";
            $token = Str::random(60);
            $api_token = hash('sha256', $token);
            $data = array(
                'email' => $email,
                'user_name' => $request->input('user_name'),
                'password' => $md5_password,
                'token' => $api_token,
                'user_type'=>3,
                'is_active'=>1,
                );
    
                $gid= DB::table('users')->insertGetId($data);
                if($request->campaign_id){
                $data = array(
                    'user_id'=>$gid,
                    'campaign_id'=>$request->campaign_id);
                    $pid= DB::table('campaign_users')->insertGetId($data);
                }
                $data = array('status' => true, 'msg' => 'Registered successfully please signin!','user_status'=>'new','data'=>$data,'leftAttempts'=>0);
                return response()->json($data);
            }
        }
public function update_user_info(Request $request)
{
    $image = null;

    if ($request->hasFile('image')) {
        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'image.max' => 'The image must be below 2MB.',
            'image.mimes' => 'Only jpeg, png, jpg, and gif formats are allowed.'
        ]);

        $imagePath = $request->file('image')->store('images', 'public');
        $image = 'storage/' . $imagePath;
    }

    $name = $request->name !== 'null' ? $request->name : '';

    $data = [
        'user_name' => $request->user_name,
        'name' => $name,
        'mobile_no' => $request->phone_number,
        'email' => $request->email
    ];

    if ($image) {
        $data['avatar'] = $image;
    }

    $updated = DB::table('users')->where('id', $request->id)->update($data);

    if ($updated) {
        return response()->json([
            'status' => true,
            'data' => $image,
            'msg' => 'Details updated successfully'
        ]);
    } else {
        return response()->json([
            'status' => false,
            'msg' => 'Failed'
        ]);
    }
}



        public function update_user_password(Request $request)
        {
            $current_date_time = date('Y-m-d H:i:s');
    
            $new_password = $request->input('new_password');
            $confirm_password = $request->input('confirm_password');
            $old_password = md5($request->input('old_password'));
            $result = DB::table('users')
                ->where('email', '=', $request['logged_email'] )
                ->where('password','=',$old_password)
                ->first();
            
                $md5_password = md5($request->input('confirm_password'));
               
            if ($result) {
            //    return "hii";
                DB::table('users')
                    ->where('email', '=', $request['logged_email'] )
                    ->update([
                        'password' =>$md5_password,
                    ]);
                $response = array('status' => true, 'msg' => 'Password changed successfully');
                return json_encode($response);
            } else {
                $response = array('status' => false, 'msg' => 'Please enter correct old password');
                return json_encode($response);
            }
    
        }


        public function update_social_media_account(Request $request)
        {
           
          
            $result = DB::table('users')
                ->where('email', '=', $request['logged_email'] )
                ->first();
            if ($result) {
            //    return "hii";
                DB::table('users')
                    ->where('email', '=', $request['logged_email'] )
                    ->update([
                        'twitter_url'=>$request['twitter_url'],
                        'facebook_url'=>$request['facebook_url'],
                        'google_url'=>$request['google_url'],
                        'linkedin_url'=>$request['linkedin_url'],
                        'instagram_url'=>$request['instagram_url'],
                        'quora_url'=>$request['quora_url'],
                    ]);
                $response = array('status' => true, 'msg' => 'Details updated successfully');
                return json_encode($response);
            } else {
                $response = array('status' => false, 'msg' => 'Something went wrong');
                return json_encode($response);
            }
    
        }
        public function ProfileInfo(REQUEST $request){
            $CIF=0;
            $token =$request->header('token');
            if($token=="1234567"){
                $CIF=1;
            }
            if($CIF==1){
                $response = array('status' => true, 'msg' => 'Details fetched successfully','data' => ['CIF_id' => $CIF]);
                return json_encode($response);
            }else{
                $response = array('status' => false, 'msg' => "Invalid token");
                return json_encode($response);
            }
        }
       public function userScore(Request $request)
{
    // return "hi";
    $token = $request->header('Authorization');

    // Get user by token
    $user_data = DB::table('users')->where('token', $token)->first();

    if (!$user_data) {
        return response()->json([
            'status' => false,
            'msg' => 'Invalid token'
        ]);
    }

    // Validate input (optional but recommended)
    $request->validate([
        'gameKey' => 'required|string',
        'game_id' => 'required|integer',
        'campaign_id' => 'required|integer',
        'score' => 'required|numeric'
    ]);

    $user_score = DB::table('users_score')
        ->where('gameKey', $request->input('gameKey'))
        ->where('user_id', $user_data->id)
        ->where('game_id', $request->input('game_id'))
        ->where('campaign_id', $request->input('campaign_id'))
        ->first();

    if ($user_score) {
        // Update score if new score is higher
        if ($user_score->score < $request->score) {
            DB::table('campaign_participants')
                ->where('game_id', $request->input('game_id'))
                ->where('user_id', $user_data->id)
                ->where('campaign_id', $request->input('campaign_id'))
                ->update(['points' => $request->score]);

            DB::table('users_score')
                ->where('gameKey', $request->input('gameKey'))
                ->where('game_id', $request->input('game_id'))
                ->where('user_id', $user_data->id)
                ->where('campaign_id', $request->input('campaign_id'))
                ->update(['score' => $request->score]);
        }
    } else {
        // Insert new score
        $scoreData = [
            'user_id' => $user_data->id,
            'gameKey' => $request->input('gameKey'),
            'game_id' => $request->input('game_id'),
            'campaign_id' => $request->input('campaign_id'),
            'score' => $request->score
        ];

        $participantData = [
            'user_id' => $user_data->id,
            'game_id' => $request->input('game_id'),
            'campaign_id' => $request->input('campaign_id'),
            'points' => $request->score
        ];

        DB::table('users_score')->insert($scoreData);
        DB::table('campaign_participants')->insert($participantData);
    }

  

    return response()->json([
        'status' => true,
        'msg' => 'Highscore updated successfully',
     
    ]);
}

        
           public function gameUserLogin(REQUEST $request){
    return json_encode(["status" => "success"]);
    }
    public function forgot_password_mail(REQUEST $request)
    {
        $this->validate($request, [
            'email' => 'required',
        
        ]);
        
        $date = date('Y-m-d H:i:s');
        $email = $request->input('email');
        $user_data = DB::table('users')
        ->where('email', '=', $email)
        ->select('id', 'user_name', 'email', 'avatar', 'mobile_no', 'token', 'is_active', 'user_type','login_times')
        ->first();
    
        
        if ($user_data) { 
         
            $otp = rand(1000, 9999); // generates a 4-digit number between 1000 and 9999
            if($user_data->is_active){
                $update_data=DB::table('users')
                ->where('email','=',$email)
                ->update([
                   
                    'token' => $user_data->token,
                    'otp'=>$otp
                ]);
                $user_data = DB::table('users')
                ->where('email','=',$email)
                ->select('id','user_name','email','avatar','mobile_no','token','is_active','user_type')
                ->first();
                $data=[
                    'user_name' => $user_data->user_name,
                    'email' => $user_data->email,
                    'otp'=> $otp
                ]  ;
                Mail::to($user_data->email)->send(new ForgotPasswordMail($data));
                $data = array('status' => true, 'msg' => 'OTP sent','user_status'=>'existed','data'=>$user_data);
                return response()->json($data);
            }
            else{
                if($request->input('password')=='123456'){
                    $update_data=DB::table('users')
                    ->where('email','=',$email)
                    ->update([
                       
                        'token' => $user_data->token,
                        'is_active'=> 1,
                        'otp'=>$otp
                       
                    ]);
                    $user_data = DB::table('users')
                    ->where('email','=',$email)
                    ->select('id','user_name','email','avatar','mobile_no','token','is_active','user_type')
                    ->first();
                    $data=[
                        'user_name' => $user_data->user_name,
                        'email' => $user_data->email,
                        'otp'=> $otp
                    ]  ;
                    Mail::to($user_data->email)->send(new ForgotPasswordMail($data));
                          $data = array('status' => true, 'msg' => 'OTP sent','user_status'=>'existed','data'=>$user_data);
                    return response()->json($data);
                }
                else{
                $data = array('status' => false, 'msg' => 'Account is inactive. Please contact customer care!');
                return response()->json($data); }
            }
    
        } else {
            $data = array('status' => false, 'msg' => 'Incorrect email!');
            return response()->json($data);
           
            }
    
            
    } 
    public function verify_otp(REQUEST $request){
        $this->validate($request, [
            'otp' => 'required',
        
        ]);
        
        $date = date('Y-m-d H:i:s');
        $otp = $request->input('otp');
        $email = $request->input('email');
        // return $otp;
        $user_data = DB::table('users')
        ->where('email', '=', $email)
        ->select('id', 'user_name', 'email', 'avatar', 'mobile_no', 'token', 'is_active', 'user_type','login_times')
        ->first();
    
        
        if ($user_data) { 
            $otp_data=DB::table('users')
            ->where('email','=',$email)
            ->where('otp','=',$otp)
            ->first();

            if($otp_data){
                $data = array('status' => true, 'msg' => 'OTP verified');
                
            
            }else{
                $data = array('status' => true, 'msg' => 'Incorrect OTP');
            }
        }
        else{
            $data = array('status' => false, 'msg' => 'Incorrect email');

        }
        return response()->json($data);
    }
    public function reset_password(REQUEST $request){
        $this->validate($request, [
            'password' => 'required',
        
        ]);
        $password = $request->input('password');
        $md5_password = md5($request->input('password'));
        $email = $request->input('email');

        $update_data=DB::table('users')
                ->where('email','=',$email)
                ->update([
                    'is_active'=> 1,
                    'password'=>$md5_password
                ]);
                $data = array('status' => true, 'msg' => 'Password resetted');
                return response()->json($data);
    }
}
  