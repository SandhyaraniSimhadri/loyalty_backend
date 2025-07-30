<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App;
use Response;
use Maatwebsite\Excel\Facades\Excel;


use App\Services\UserDataService;


class CampaignsController extends Controller{
    public function __construct()
    {
    }
    
    private function saveBase64Image($base64String, $folder = 'images') {
        
        if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches)) {
            $imageType = $matches[1];
            $imageBase64 = substr($base64String, strpos($base64String, ',') + 1);
            $imageBase64 = base64_decode($imageBase64);
    
            $fileName = uniqid() . '.' . $imageType;
            Storage::disk('public')->put("{$folder}/{$fileName}", $imageBase64);
    
            return "storage/{$folder}/{$fileName}";
        }
        return null;
    }
    public function add_campaign(Request $request)
    {
        // return "hello:";


        $tag_info = DB::table('campaigns')
        ->where('campaign_tag', '=', $request->campaign_tag)
        ->where('id', '!=', $request->id) // Exclude the current campaign
        ->get();
// return $tag_info;
        if ($tag_info->isEmpty() || $request->campaign_tag==null) {
            // return "hello:";

        $image=null;
        $date = date('Y-m-d H:i:s');
        // return $request;
      
        $data = array(
          
            'campaign_title' => $request->campaign_title,
            'title' => $request->title,
            'terms_and_conditions' => $request->terms_and_conditions,
            'game_type' => $request->game_type,
            'description' => $request->description,
            'login_text' => $request->login_text,
            'welcome_text' => $request->welcome_text,

            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'event_id' => $request->event_id,
            'company_id'=> $request->company_id,
            'duration'=> $request->duration,
            'calc_points_immediately'=>$request->calculatePoints,
            'login_text'=>$request->login_text,
            );

            $aid= DB::table('campaigns')->insertGetId($data);
            // return $aid;
          
            if($aid){
            $campaignFolder = "images/campaign_$aid";
            Storage::makeDirectory($campaignFolder); 
            $logo_image = null;
            $login_image = null;
            $welcome_image = null;
            $campaign_image = null;
          
            
            if (!empty($request->logo_image)) {
                $logo_image = $this->saveBase64Image($request->logo_image, $campaignFolder);
            }
            
            if (!empty($request->login_image)) {
                $login_image = $this->saveBase64Image($request->login_image, $campaignFolder);
            }
            
            if (!empty($request->welcome_image)) {
                $welcome_image = $this->saveBase64Image($request->welcome_image, $campaignFolder);
            }
            
            if (!empty($request->campaign_image)) {
                $campaign_image = $this->saveBase64Image($request->campaign_image, $campaignFolder);
            }
            
    
            // Update campaign with image paths
            DB::table('campaigns')
                ->where('id', $aid)
                ->update([
                    'logo_image' => $logo_image,
                    'login_image' => $login_image,
                    'welcome_image' => $welcome_image,
                    'campaign_image' => $campaign_image
                ]);
    

            }

            $event_value = DB::table('events')
            ->where('id','=',$request->event_id)
            ->get();
            if($event_value[0]->title=="PREDICTION EVENT"){
                $games = $request->games;
                if($games){
                    $i=0;
                foreach($games as $game){
                    $team_a_image=null;
                    $team_b_image=null;
                  
                    if ($request->hasFile("team_a_image_{$i}")) {
                        // return $request->hasFile('homeTeamLogo')
                        $team_a_image = $request->file("team_b_image_{$i}")->store('images', 'public');
                        $team_a_image = 'storage/'.$team_a_image;
                    }
                    if ($request->hasFile("team_b_image_{$i}")) {
                        // return $request->hasFile('homeTeamLogo')
                        $team_b_image = $request->file("team_b_image_{$i}")->store('images', 'public');
                        $team_b_image = 'storage/'.$team_b_image;
                    }
                    // return $request;
                    $data = array(
          
                        'name' => $game['name'],
                        'team_a' => $game['team_a'],
                        'team_b' => $game['team_b'],
                        'campaign_id'=>$aid,
                        'points'=>$game['points'],
                        'game_start_date' => $game['game_start_date'],
                        'game_start_time' => $game['game_start_time'],
                        'game_end_date' => $game['game_end_date'],
                        'game_end_time' => $game['game_end_time'],
                        'team_b_image' => $team_b_image,
                        'team_a_image' => $team_a_image,
                        
                        );
            
                        $gid= DB::table('games')->insertGetId($data);

                        $i=$i+1;
                }}
              
            }

            else if($event_value[0]->title=="QUIZ"){
                // return $request->questions;
                if ($request->questions) {
                    foreach ($request->questions as $question) {
                        $image = null;
                
                        // Check if 'selectedFile' exists and is Base64
                        if (!empty($question['selectedFile']) && preg_match('/^data:image\/(\w+);base64,/', $question['selectedFile'], $matches)) {
                            $imageType = $matches[1]; // Extract image type (e.g., jpg, png)
                            $imageBase64 = substr($question['selectedFile'], strpos($question['selectedFile'], ',') + 1);
                            $imageBase64 = base64_decode($imageBase64); // Decode Base64
                
                            // Generate unique file name
                            $fileName = uniqid() . '.' . $imageType;
                            $filePath = "storage/images/" . $fileName;
                
                            // Store the image in storage/app/public/images
                            Storage::disk('public')->put("images/" . $fileName, $imageBase64);
                
                            // Store only the relative path
                            $image = $filePath;
                        }
                        else{
                            $question['fileName']='';
                        }
                
                        // Insert question details into the database
                        $data = [
                            'question' => $question['question'],
                            'response_a' => $question['response_a'],
                            'response_b' => $question['response_b'],
                            'response_c' => $question['response_c'],
                            'response_d' => $question['response_d'],
                            'correct_answer' => $question['answer'],
                            'campaign_id' => $aid,
                            'points' => $question['points'],
                            'file_name'=>$question['fileName'],
                            'image' => $image // Store only the relative path
                        ];
                
                        DB::table('quizzes')->insert($data);
                    }
                }
                
                
              
            }
            else if($event_value[0]->title=="GAMES"){
                $game_welcome_image = null;
                $game_start_image = null;
                $game_end_image = null;

                if (!empty($request->game_welcome_image)) {
                    $game_welcome_image = $this->saveBase64Image($request->game_welcome_image, $campaignFolder);
                }
                if (!empty($request->game_start_image)) {
                    $game_start_image = $this->saveBase64Image($request->game_start_image, $campaignFolder);
                }
                if (!empty($request->game_end_image)) {
                    $game_end_image = $this->saveBase64Image($request->game_end_image, $campaignFolder);
                }
                $data = array(
          
                    'game_url' => $request['game_url'],
                    'game_welcome_text' => $request['game_welcome_text'],
                    'game_welcome_image' => $game_welcome_image,
                    'game_start_image' => $game_start_image,
                    'game_end_image' => $game_end_image,
                    'campaign_id'=>$aid,
                    'selected_primary_color'=>$request['selected_primary_color'],
                    'selected_secondary_color'=>$request['selected_secondary_color'],
                    'selected_page_color'=>$request['selected_page_color'],

                      'selected_startpage_color'=>$request['selected_startpage_color'],
                        'selected_overpage_color'=>$request['selected_overpage_color'],
                    'selected_welcomepage_button_color'=>$request['selected_welcomepage_button_color'],
                    

                    );
        
                    $gid= DB::table('html_games')->insertGetId($data);

                    if ($request->prizes) {
                        foreach ($request->prizes as $prize) {
                            $image = null;
                    
                            // Check if 'selectedFile' exists and is Base64
                            if (!empty($prize['selectedFile']) && preg_match('/^data:image\/(\w+);base64,/', $prize['selectedFile'], $matches)) {
                                $imageType = $matches[1]; // Extract image type (e.g., jpg, png)
                                $imageBase64 = substr($prize['selectedFile'], strpos($prize['selectedFile'], ',') + 1);
                                $imageBase64 = base64_decode($imageBase64); // Decode Base64
                    
                                // Generate unique file name
                                $fileName = uniqid() . '.' . $imageType;
                                $filePath = "storage/images/" . $fileName;
                    
                                // Store the image in storage/app/public/images
                                Storage::disk('public')->put("images/" . $fileName, $imageBase64);
                    
                                // Store only the relative path
                                $image = $filePath;
                            }
                            else{
                                $prize['fileName']='';
                            }
                    
                            // Insert question details into the database
                            $data = [
                                'prize_header' => $prize['prize_header'],
                                'prize_desc' => $prize['prize_desc'],
                                'campaign_id' => $aid,
                                'file_name'=>$prize['fileName'],
                                'image' => $image // Store only the relative path
                            ];
                    
                            DB::table('prizes')->insert($data);
                        }
                    }
                
                    

            }


        if ($aid) { 
                $data = array('status' => true, 'msg' => 'Campaign added successfully','tag'=>'No duplicate');
                return response()->json($data);

        } else {
            // return true;
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }
    }
    else{
        $data = array('status' => true, 'msg' => 'This tag is already in use. Please choose a different one.','tag'=>'Duplicate');
   return response()->json($data);
    }
    }
  
    public function get_campaigns(Request $request) {
        // Fetch campaigns
        $campaigns = DB::table('campaigns as cam')
        ->join('company as c', 'cam.company_id', '=', 'c.id')
            ->join('events as e', 'cam.event_id', '=', 'e.id')
            ->where('e.deleted', '=', 0)
            ->where('cam.deleted', '=', 0)
            ->where('c.deleted', '=', 0)
            ->select('cam.*', 'cam.campaign_title', 'cam.campaign_image as avatar','e.title as event_title','c.company_name as company_name')
            ->orderBy('cam.created_at', 'DESC')
            ->get();
    
        // Fetch games associated with each campaign
        foreach ($campaigns as $campaign) {
            if($campaign->event_title=='PREDICTION EVENT'){
            $games = DB::table('games')
                ->where('campaign_id', '=', $campaign->id)
                ->where('deleted', '=', 0)
                ->select('id', 'name', 'team_a', 'team_b','points')
                ->get();
    
            // Add games to the campaign object
            $campaign->games = $games;}

            if($campaign->event_title=='QUIZ'){
                $quizzes = DB::table('quizzes')
                    ->where('campaign_id', '=', $campaign->id)
                    ->where('deleted', '=', 0)
                    ->select('id', 'question', 'response_a',  'response_b',  'response_c', 'response_d', 'points','correct_answer')
                    ->get();
        
              
                $campaign->quizzes = $quizzes;}

        }
    
        return response()->json(['status' => true, 'data' => $campaigns]);
    }
    
    

    public function get_single_campaign(REQUEST $request){

        $campaigns = DB::table('campaigns as cam')
            ->join('events as e', 'cam.event_id', '=', 'e.id')
            ->join('company as c', 'cam.company_id', '=', 'c.id')
            ->where('cam.id','=',$request->id)
            ->where('e.deleted', '=', 0)
            ->where('cam.deleted', '=', 0)
            ->where('c.deleted', '=', 0)
            ->select('cam.*', 'cam.campaign_title', 'cam.campaign_image as avatar','e.title as event_title','c.company_name as company_name')
            ->orderBy('cam.created_at', 'DESC')
            ->get();
    
        foreach ($campaigns as $campaign) {
            if($campaign->event_title=='PREDICTION EVENT'){
                $games = DB::table('games')
                    ->where('campaign_id', '=', $campaign->id)
                    ->where('deleted', '=', 0)
                    ->select('id', 'name', 'team_a', 'team_b','points','game_start_date','game_end_date','game_start_time','game_end_time')
                    ->get();
        
                $campaign->games = $games;
            }
            if($campaign->event_title=='QUIZ'){
                    $quizzes = DB::table('quizzes')
                        ->where('campaign_id', '=', $campaign->id)
                        ->where('deleted', '=', 0)
                        ->select('id', 'question', 'response_a',  'response_b',  'response_c', 'response_d', 'points','correct_answer','image','file_name')
                        ->get();
                    $campaign->quizzes = $quizzes;
            }
            if($campaign->event_title=='GAMES'){
                $campaign->html_games = DB::table('html_games')
                ->where('campaign_id', $campaign->id)
                ->where('deleted', 0)
                ->get();

            $campaign->prizes = DB::table('prizes')
                ->where('campaign_id', $campaign->id)
                ->where('deleted', 0)
                ->get();
            }

        }
    
        return response()->json(['status' => true, 'data' => $campaigns]);
    }
    
    public function update_campaign(REQUEST $request){
        $tag_info = DB::table('campaigns')
        ->where('campaign_tag', '=', $request->campaign_tag)
        ->where('id', '!=', $request->id) // Exclude the current campaign
        ->get();
        if ($tag_info->isEmpty() || $request->campaign_tag==null || $request->campaign_tag=='') {
        
        $event_value = DB::table('events')
        ->where('id','=',$request->event_id)
        ->get();

    
        $update_data=DB::table('campaigns')
        ->where('id','=',$request->id)
        ->update([
            'event_id' => $request->event_id,
            'campaign_title' => $request->campaign_title,
            'campaign_tag'=> $request->campaign_tag,
            'title' => $request->title,
            'game_type' => $request->game_type,
            'terms_and_conditions' => $request->terms_and_conditions,
            'description' => $request->description,
            'login_text'=>$request->login_text,
            'welcome_text'=>$request->welcome_text,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'company_id'=> $request->company_id,
            'duration'=>$request->duration,
            'calc_points_immediately'=>$request->calculatePoints
        ]);


        $campaignFolder = "images/campaign_$request->id";
        if (!Storage::exists($campaignFolder)) {
        Storage::makeDirectory($campaignFolder); }
        $logo_image = null;
        $login_image = null;
        $welcome_image = null;
        $campaign_image = null;
      

        // Logo Image
        if (!empty($request->logo_image)) {
            $logo_image = $this->saveBase64Image($request->logo_image, $campaignFolder);
            if ($logo_image) {
                DB::table('campaigns')
                    ->where('id', $request->id)
                    ->update(['logo_image' => $logo_image]);
            }
        }
        
        // Login Image
        if (!empty($request->login_image)) {
            $login_image =$this->saveBase64Image($request->login_image, $campaignFolder);
            if ($login_image) {
                DB::table('campaigns')
                    ->where('id', $request->id)
                    ->update(['login_image' => $login_image]);
            }
        }
        
        // Welcome Image
        if (!empty($request->welcome_image)) {
            $welcome_image = $this->saveBase64Image($request->welcome_image, $campaignFolder);
            if ($welcome_image) {
                DB::table('campaigns')
                    ->where('id', $request->id)
                    ->update(['welcome_image' => $welcome_image]);
            }
        }
        
        // Campaign Image
        if (!empty($request->campaign_image)) {
            $campaign_image = $this->saveBase64Image($request->campaign_image, $campaignFolder);
            if ($campaign_image) {
                DB::table('campaigns')
                    ->where('id', $request->id)
                    ->update(['campaign_image' => $campaign_image]);
            }
        }
        

        if($event_value[0]->title=="PREDICTION EVENT"){
            $team_a_image=null;
            $team_b_image=null;
            if ($request->hasFile('team_a_image')) {
                // return $request->hasFile('homeTeamLogo')
                $team_a_image = $request->file('team_a_image')->store('images', 'public');
                $team_a_image = 'storage/'.$team_a_image;
            }
            if ($request->hasFile('team_b_image')) {
                // return $request->hasFile('homeTeamLogo')
                $team_b_image = $request->file('team_b_image')->store('images', 'public');
                $team_b_image = 'storage/'.$team_b_image;
            }
        $update_details=0;
        $update_id=0;
   
        $update_details=DB::table('games')
        ->where('campaign_id','=',$request->id)
        ->update([
            'deleted' => 1,
        ]);
        $games = $request->games;
        if($games){
            foreach($games as $game){

                $team_a_image=null;
                $team_b_image=null;
                // return $game->team_a_image;
                if ($request->hasFile("team_a_image")) {
                    $team_a_image_path = $request->file("team_a_image")->store('images', 'public');
                    $team_a_image = 'storage/' . $team_a_image_path;
                } else {
                    $inputA = $request->input("team_a_image");
                    if ($inputA && Str::startsWith($inputA, 'data:image')) {
                        $team_a_image = storeBase64Image($inputA, 'team_a');
                    }
                }

                // Handle team B image
                if ($request->hasFile("team_b_image")) {
                    $team_b_image_path = $request->file("team_b_image")->store('images', 'public');
                    $team_b_image = 'storage/' . $team_b_image_path;
                } else {
                    $inputB = $request->input("team_b_image");
                    if ($inputB && Str::startsWith($inputB, 'data:image')) {
                        $team_b_image = storeBase64Image($inputB, 'team_b');
                    }
                }
                
                // return $game;
                

                if($game['id']==0){

                     $data = array(
                    'name' => $game['name'],
                    'team_a' => $game['team_a'],
                    'team_b' => $game['team_b'],
                    'campaign_id'=>$request->id,
                    'points'=>$game['points'],
                    'game_start_date' => $game['game_start_date'],
                    'game_start_time' => $game['game_start_time'],
                    'game_end_date' => $game['game_end_date'],
                    'game_end_time' => $game['game_end_time'],
                    'team_b_image' => $team_b_image,
                    'team_a_image' => $team_a_image,
                    );
        
                    $update_id= DB::table('games')->insertGetId($data);}
                else{
                    $update_details=DB::table('games')
                    ->where('id','=',$game['id'])
                    ->where('campaign_id','=',$request->id)
                    ->update([
                        'name' => $game['name'],
                        'team_a' => $game['team_a'],
                        'campaign_id'=>$request->id,
                        'team_b' => $game['team_b'],
                        'deleted' => 0,
                        'points'=>$game['points'],
                        'game_start_date' => $game['game_start_date'],
                        'game_start_time' => $game['game_start_time'],
                        'game_end_date' => $game['game_end_date'],
                        'game_end_time' => $game['game_end_time'],
                        'team_b_image' => $team_b_image,
                    'team_a_image' => $team_a_image,
                    ]);
                }

            }}
          
        }

        if($event_value[0]->title=="QUIZ"){
            // return "hi";
            $update_details=0;
            $update_id=0;
            $update_details=DB::table('quizzes')
            ->where('campaign_id','=',$request->id)
            ->update([
                'deleted' => 1,
            ]);
            // $questions = json_decode($request->questions, true);


            if($request->questions){
                foreach($request->questions as $question){
                    // return $game;
                    $image = null;
                
                    // Check if 'selectedFile' exists and is Base64
                    if (isset($question['updated']) && $question['updated']) {
                        // Your code here
                                    

                    
                    if (!empty($question['selectedFile']) && preg_match('/^data:image\/(\w+);base64,/', $question['selectedFile'], $matches)) {
                        $imageType = $matches[1]; // Extract image type (e.g., jpg, png)
                        $imageBase64 = substr($question['selectedFile'], strpos($question['selectedFile'], ',') + 1);
                        $imageBase64 = base64_decode($imageBase64); // Decode Base64
            
                        // Generate unique file name
                        $file_name = uniqid() . '.' . $imageType;
                        $filePath = "storage/images/" . $file_name;
            
                        // Store the image in storage/app/public/images
                        Storage::disk('public')->put("images/" . $file_name, $imageBase64);
            
                        // Store only the relative path
                        $image = $filePath;
                    }
                }
                else{
                    $image = isset($question['image']) ? $question['image'] : null;

                }
                    if($question['id']==0){
    
                         $data = array(
                            'question' => $question['question'],
                            'response_a' => $question['response_a'],
                            'response_b' => $question['response_b'],
                            'response_c' => $question['response_c'],
                            'response_d' => $question['response_d'],
                            'correct_answer' => $question['correct_answer'],
                            'campaign_id'=>$request->id,
                            'points'=>$question['points'],
                           'file_name' => $question['file_name'] ?? null,
                            'image' => $image // Store only the relative path
                        );
            
                        $id= DB::table('quizzes')->insertGetId($data);}
                    else{
                        // return $question['correct_answer'];
                        $update_details=DB::table('quizzes')
                        ->where('id','=',$question['id'])
                        ->where('campaign_id','=',$request->id)
                        ->update([
                            'question' => $question['question'],
                            'response_a' => $question['response_a'],
                            'response_b' => $question['response_b'],
                            'response_c' => $question['response_c'],
                            'response_d' => $question['response_d'],
                            'correct_answer' => $question['correct_answer'],
                            'campaign_id'=>$request->id,
                            'points'=>$question['points'],
                            'file_name'=>$question['file_name'],
                            'image' => $image ,
                            'deleted' => 0,
                        ]);
                    }
    
                }}
          
                
        }


        if($event_value[0]->title=="GAMES"){
            // return "hi";
            $update_details=0;
            $update_id=0;
            $game_welcome_image = null;
            $game_start_image = null;
            $game_end_image = null;

            if (!empty($request->game_welcome_image)) {
                $game_welcome_image = $this->saveBase64Image($request->game_welcome_image, $campaignFolder);
            }
            else{
                $game_welcome_image=$request->html_games['game_welcome_image'];
            }
            if (!empty($request->game_start_image)) {
                $game_start_image = $this->saveBase64Image($request->game_start_image, $campaignFolder);
            }else{
                $game_start_image=$request->html_games['game_start_image'];
            }
            if (!empty($request->game_end_image)) {
                $game_end_image = $this->saveBase64Image($request->game_end_image, $campaignFolder);
            }
            else{
                $game_end_image=$request->html_games['game_end_image'];
            }
            $update_details=DB::table('html_games')
            ->where('campaign_id','=',$request->id)
            ->update([
                'game_url' => $request['html_games']['game_url'],
                            'game_welcome_text' => $request['html_games']['game_welcome_text'],
                            'game_welcome_image' => $game_welcome_image,
                            'game_start_image' => $game_start_image,
                            'game_end_image' => $game_end_image,
                            'selected_primary_color'=>$request['html_games']['selected_primary_color'],
                            'selected_secondary_color'=>$request['html_games']['selected_secondary_color'],
                            'selected_page_color'=>$request['html_games']['selected_page_color'],
                            'selected_startpage_color'=>$request['html_games']['selected_startpage_color'],
                            'selected_overpage_color'=>$request['html_games']['selected_overpage_color'],

                            'selected_welcomepage_button_color'=>$request['html_games']['selected_welcomepage_button_color'],

            ]);

            $update_details=DB::table('prizes')
            ->where('campaign_id','=',$request->id)
            ->update([
               'deleted'=>1   ]);

            // $questions = json_decode($request->questions, true);


            if($request->prizes){
                foreach($request->prizes as $prize){
                    // return $game;
                    $image = null;
                
                    // Check if 'selectedFile' exists and is Base64
                    if (isset($prize['updated']) && $prize['updated']) {
                        // Your code here
                                    

                    
                    if (!empty($prize['selectedFile']) && preg_match('/^data:image\/(\w+);base64,/', $prize['selectedFile'], $matches)) {
                        $imageType = $matches[1]; // Extract image type (e.g., jpg, png)
                        $imageBase64 = substr($prize['selectedFile'], strpos($prize['selectedFile'], ',') + 1);
                        $imageBase64 = base64_decode($imageBase64); // Decode Base64
            
                        // Generate unique file name
                        $file_name = uniqid() . '.' . $imageType;
                        $filePath = "storage/images/" . $file_name;
            
                        // Store the image in storage/app/public/images
                        Storage::disk('public')->put("images/" . $file_name, $imageBase64);
            
                        // Store only the relative path
                        $image = $filePath;
                    }
                }
                else{
                    $image = isset($prize['image']) ? $prize['image'] : null;

                }

               
                    if($prize['id']==0){

    
                        $data = [
                            'prize_header' => $prize['prize_header'],
                            'prize_desc' => $prize['prize_desc'],
                            'campaign_id' => $request->id,
                            'file_name'=>$prize['file_name'],
                            'image' => $image 
                        ];
                
                       
            
                        $id= DB::table('prizes')->insertGetId($data);}
                    else{
                        // return $question['correct_answer'];
                        $update_details=DB::table('prizes')
                        ->where('id','=',$prize['id'])
                        ->where('campaign_id','=',$request->id)
                        ->update([
                            'prize_header' => $prize['prize_header'],
                            'prize_desc' => $prize['prize_desc'],
                            'file_name'=>$prize['file_name'],
                            'image' => $image,
                            'deleted' => 0,
                        ]);
                    }
                }}       
        }
        if($update_data || $update_details || $update_id){
            $data = array('status' => true, 'msg' => 'Campaign details updated successfully','tag'=>'No Duplicate');
            return response()->json($data);
            } 
        else {
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }}
        else{
        
        $data = array('status' => true, 'msg' => 'This tag is already in use. Please choose a different one.','tag'=>'Duplicate');
        return response()->json($data);    
    }
    }
    function storeBase64Image($base64_image, $prefix = 'image') {
    $image_parts = explode(";base64,", $base64_image);
    if (count($image_parts) !== 2) {
        return null; // invalid format
    }

    $image_type_aux = explode("image/", $image_parts[0]);
    $image_type = $image_type_aux[1] ?? 'png';
    $image_base64 = base64_decode($image_parts[1]);

    $file_name = $prefix . '_' . time() . '.' . $image_type;
    $file_path = 'images/' . $file_name;

    Storage::disk('public')->put($file_path, $image_base64);

    return 'storage/' . $file_path;
}
    public function delete_campaign(REQUEST $request){
        $deleted_info=DB::table('campaigns')
        ->where('id','=',$request->id)
        ->update([
            'deleted'=>1,
        ]);
        $deleted_games=DB::table('games')
        ->where('campaign_id','=',$request->id)
        ->update([
            'deleted'=>1,
        ]);
        $deleted_participants=DB::table('campaign_participants')
        ->where('campaign_id','=',$request->id)
        ->update([
            'deleted'=>1,
        ]);
        $deleted_participants=DB::table('quizzes')
        ->where('campaign_id','=',$request->id)
        ->update([
            'deleted'=>1,
        ]);
    
        if($deleted_info){
            $data = array('status' => true, 'msg' => 'Campaign deleted successfully');
            return response()->json($data);
            } 
        else {
            // return true;
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }
    }
    public function get_report(REQUEST $request){
 
            $campaigns = DB::table('campaigns as cam')
        ->leftJoin('events as e', 'cam.event_id', '=', 'e.id')
        ->where('cam.id','=',$request->id)
        ->where('e.deleted', '=', 0)
        ->where('cam.deleted', '=', 0)
        ->select('cam.*', 'cam.campaign_title', 'cam.campaign_image as avatar','e.title as event_title')
        ->orderBy('cam.created_at', 'DESC')
        ->get();

            $campaign=$campaigns[0];
    
        if($campaign->event_title=="PREDICTION EVENT"){
            $games = DB::table('games')
                ->where('campaign_id', '=', $campaign->id)
                ->where('deleted', '=', 0)
                ->select('id', 'name', 'team_a', 'team_b','points','selected_winner','game_start_date','game_end_date','game_start_time','game_end_time','team_a_image','team_b_image')
                ->get();
                $subquery = DB::table('campaign_participants as c')
                ->select('c.user_id', DB::raw('SUM(c.points) as total_points'))
                ->groupBy('c.user_id');
                $participants = DB::table('users as u')
                ->join('campaign_participants as c', function($join) use ($campaign) {
                    $join->on('u.id', '=', 'c.user_id')
                        ->where('c.campaign_id', '=', $campaign->id)
                        ->where('c.deleted', '=', 0);
                })
                ->leftJoinSub($subquery, 'totals', function ($join) {
                    $join->on('u.id', '=', 'totals.user_id');
                })
                ->where('u.deleted', '=', 0)
                ->select(
                    'c.campaign_id',
                    'u.id as user_id', 
                    'u.user_name', 
                    'u.avatar', 
                    'u.company_id', 
                    DB::raw('MAX(c.predicted_answer) as predicted_answer'), // Selects one predicted_answer
                    'totals.total_points'
                )
                ->groupBy('u.id', 'c.campaign_id', 'u.user_name', 'u.avatar', 'u.company_id', 'totals.total_points')
                ->get();
            
                // return $participants;
                $totalCampaignPoints = DB::table('campaign_participants as c')
                ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
                ->where('c.campaign_id', '=', $campaign->id)
                ->where('u.deleted', '=', 0)
                ->where('c.deleted', '=', 0)
                ->sum('c.points');
            
                $campaign->total_points = $totalCampaignPoints;
    
            // Add games to the campaign object
            $campaign->games = $games;
            $campaign->participants = $participants;
        }


        if($campaign->event_title=="QUIZ"){
        //    return  $campaign->company_id;
            $quizzes = DB::table('quizzes')
                ->where('campaign_id', '=', $campaign->id)
                ->where('deleted', '=', 0)
                ->select('id', 'question', 'response_a',  'response_b',  'response_c', 'response_d', 'points','correct_answer')
                ->get();
                $subquery = DB::table('campaign_participants as c')
            ->select('c.user_id', DB::raw('SUM(c.points) as total_points'))
            ->groupBy('c.user_id');


            $participants = DB::table('users as u')
    ->leftJoin(DB::raw('(SELECT user_id, GROUP_CONCAT(REPLACE(predicted_answer, "Notansweredtimeexceeded", "-") ORDER BY id SEPARATOR ",") as predicted_answers 
                        FROM campaign_participants 
                        WHERE campaign_id = '.$campaign->id.' AND deleted = 0 
                        GROUP BY user_id) as c'), 'u.id', '=', 'c.user_id')
    ->join(DB::raw('(SELECT user_id, SUM(points) as total_points 
                    FROM campaign_participants 
                    WHERE campaign_id = '.$campaign->id.' AND deleted = 0 
                    GROUP BY user_id) as totals'), 'u.id', '=', 'totals.user_id')
    ->leftJoin(DB::raw('(SELECT user_id, MIN(time_taken) as time_taken 
                        FROM users_campaigns_timetaken 
                        WHERE campaign_id = '.$campaign->id.' AND deleted = 0 
                        GROUP BY user_id) as cu'), 'u.id', '=', 'cu.user_id')
    ->select(
        'u.id as user_id',
        'u.user_name',
        'u.avatar',
        'u.company_id',
        DB::raw('COALESCE(c.predicted_answers, "") as predicted_answers'),
        DB::raw('COALESCE(totals.total_points, 0) as total_points'),
        DB::raw('COALESCE(cu.time_taken, 0) as time_taken')
    )
    // Removed the line causing the error
    ->where('u.deleted', '=', 0)
    ->groupBy('u.id', 'u.user_name', 'u.avatar', 'u.company_id', 'totals.total_points', 'cu.time_taken', 'c.predicted_answers')
    ->orderBy('total_points', 'desc')
    ->orderBy('time_taken', 'asc')
    ->get();

         // return $participants;
                $totalCampaignPoints = DB::table('campaign_participants as c')
                ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
                ->where('c.campaign_id', '=', $campaign->id)
                ->where('u.deleted', '=', 0)
                ->where('c.deleted', '=', 0)
                ->sum('c.points');
            
            $campaign->total_points = $totalCampaignPoints;
    
            // Add games to the campaign object
            $campaign->quizzes = $quizzes;
            $campaign->participants = $participants;
        }



        
    
        return response()->json(['status' => true, 'data' => $campaign]);
    }
    public function select_winner(REQUEST $request){
        $selected_winner=DB::table('games')
        ->where('id','=',$request->id)
        ->update([
            'selected_winner'=>$request->winner,
        ]);
        return response()->json(['status' => true,'msg'=>"Winner selected successfully", 'data' => $selected_winner]);
    }
}