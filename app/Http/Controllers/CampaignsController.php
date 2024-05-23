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


class CampaignsController extends Controller{
    public function __construct()
    {
    }
    

    public function add_campaign(Request $request)
    {
        $image=null;
        if ($request->hasFile('image')) {
            // return $request->hasFile('homeTeamLogo')
            $image = $request->file('image')->store('images', 'public');
            $image = 'storage/'.$image;
        }
       
        $date = date('Y-m-d H:i:s');
        $data = array(
          
            'campaign_title' => $request->campaign_title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'event_id' => $request->event_id,
            'company_id'=> $request->company_id
            );

            $aid= DB::table('campaigns')->insertGetId($data);
            $event_value = DB::table('events')
            ->where('id','=',$request->event_id)
            ->get();
            if($event_value[0]->title=="PREDICTION EVENT"){

        
                $games = json_decode($request->games, true);
      

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

        if ($aid) { 
                $data = array('status' => true, 'msg' => 'Campaign added successfully');
                return response()->json($data);

        } else {
            // return true;
            $data = array('status' => false, 'msg' => 'Failed');
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
            ->select('cam.*', 'cam.campaign_title', 'cam.image as avatar','e.title as event_title','c.company_name as company_name')
            ->orderBy('cam.created_at', 'DESC')
            ->get();
    
        // Fetch games associated with each campaign
        foreach ($campaigns as $campaign) {
            $games = DB::table('games')
                ->where('campaign_id', '=', $campaign->id)
                ->where('deleted', '=', 0)
                ->select('id', 'name', 'team_a', 'team_b','points')
                ->get();
    
            // Add games to the campaign object
            $campaign->games = $games;
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
            ->select('cam.*', 'cam.campaign_title', 'cam.image as avatar','e.title as event_title','c.company_name as company_name')
            ->orderBy('cam.created_at', 'DESC')
            ->get();
    
        // Fetch games associated with each campaign
        foreach ($campaigns as $campaign) {
            $games = DB::table('games')
                ->where('campaign_id', '=', $campaign->id)
                ->where('deleted', '=', 0)
                ->select('id', 'name', 'team_a', 'team_b','points','selected_winner','game_start_date','game_end_date','game_start_time','game_end_time','team_a_image','team_b_image')
                ->get();


    
            // Add games to the campaign object
            $campaign->games = $games;
     

        }
    
        return response()->json(['status' => true, 'data' => $campaigns]);
    }
    
    public function update_campaign(REQUEST $request){
        $image=null;
        if ($request->hasFile('image')) {
            // return $request->hasFile('homeTeamLogo')
            $image = $request->file('image')->store('images', 'public');
            $image = 'storage/'.$image;
        }
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
        $update_data=DB::table('campaigns')
        ->where('id','=',$request->id)
        ->update([
            'event_id' => $request->event_id,
            'campaign_title' => $request->campaign_title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'company_id'=> $request->company_id
        ]);
        $update_game=0;
        $gid=0;
        $event_value = DB::table('events')
        ->where('id','=',$request->event_id)
        ->get();
        $update_game=DB::table('games')
        ->where('campaign_id','=',$request->id)
        ->update([
            'deleted' => 1,
        ]);
        if($event_value[0]->title=="PREDICTION EVENT"){
            $games = json_decode($request->games, true);
          
            if($games){
            foreach($games as $game){
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
                    'team_b_image' => $game['team_b_image'],
                    'team_a_image' => $game['team_a_image'],
                    );
        
                    $gid= DB::table('games')->insertGetId($data);}
                else{
                    $update_game=DB::table('games')
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
                        'team_b_image' => $game['team_b_image'],
                        'team_a_image' => $game['team_a_image'],
                    ]);
                }

            }}
          
        }
        if($update_data || $update_game || $gid){
            $data = array('status' => true, 'msg' => 'Campaign details updated successfully');
            return response()->json($data);
            } 
        else {
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }
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
            ->select('cam.*', 'cam.campaign_title', 'cam.image as avatar','e.title as event_title')
            ->orderBy('cam.created_at', 'DESC')
            ->get();
            $campaign=$campaigns[0];
    
        if($campaign->event_title=="PREDICTION EVENT"){
            $games = DB::table('games')
                ->where('campaign_id', '=', $campaign->id)
                ->where('deleted', '=', 0)
                ->select('id', 'name', 'team_a', 'team_b','points','selected_winner','game_start_date','game_end_date','game_start_time','game_end_time','team_a_image','team_b_image')
                ->get();

                $participants = DB::table('users as u')
                ->leftJoin('campaign_participants as c', function($join) use ($campaign) {
                    $join->on('u.id', '=', 'c.user_id')
                        ->where(function ($query) use ($campaign) {
                            $query->where('c.campaign_id', '=', $campaign->id)
                                  ->orWhereNull('c.campaign_id');
                        })
                        ->where('c.deleted', '=', 0);
                })
                ->leftJoinSub($subquery, 'totals', function ($join) {
                    $join->on('u.id', '=', 'totals.user_id');
                })
                ->where('c.campaign_id', '=', $campaign->id)
                // ->orWhereNull('c.campaign_id')
                ->where('u.company_id', '=', $campaign->company_id)
                ->where('u.company_id', '!=', 0) // Exclude records where company_id is 0
                ->where('u.deleted', '=', 0)
                ->select(
                    'u.id as user_id', 
                    'u.user_name', 
                    'u.avatar', 
                    'u.company_id', 
                    DB::raw('MAX(c.team_name) as team_name'), // Selects one team_name
                    'totals.total_points'
                )
                ->groupBy('u.id', 'u.user_name', 'u.avatar', 'u.company_id', 'totals.total_points')
                ->get();
                $totalCampaignPoints = DB::table('campaign_participants as c')
                ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
                ->where('u.company_id', '=', $campaign->company_id)
                ->where('u.company_id', '!=', 0) // Exclude records where company_id is 0
                ->where('c.campaign_id', '=', $campaign->id)
                ->where('u.deleted', '=', 0)
                ->where('c.deleted', '=', 0)
                ->sum('c.points');
            
                $campaign->total_points = $totalCampaignPoints;
    
            // Add games to the campaign object
            $campaign->games = $games;
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