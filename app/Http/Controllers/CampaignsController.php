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
        // return $request;
        $date = date('Y-m-d H:i:s');
        $data = array(
          
            'campaign_title' => $request->campaign_title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'event_id' => $request->event_id,
            );

            $aid= DB::table('campaigns')->insertGetId($data);
            $event_value = DB::table('events')
            ->where('id','=',$request->event_id)
            ->first();
            if($event_value['title']=="PREDICTION EVENT"){
                $games = json_decode($request->games, true);
        // return $games;

                if($games){
                foreach($games as $game){
                    // return $game;
                    $data = array(
          
                        'name' => $game['name'],
                        'team_a' => $game['team_a'],
                        'team_b' => $game['team_b'],
                        'campaign_id'=>$aid,
                        );
            
                        $gid= DB::table('games')->insertGetId($data);
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
            ->join('events as e', 'cam.event_id', '=', 'e.id')
            ->where('e.deleted', '=', 0)
            ->where('cam.deleted', '=', 0)
            ->select('cam.*', 'cam.campaign_title', 'cam.image as avatar','e.title as event_title')
            ->orderBy('cam.created_at', 'DESC')
            ->get();
    
        // Fetch games associated with each campaign
        foreach ($campaigns as $campaign) {
            $games = DB::table('games')
                ->where('campaign_id', '=', $campaign->id)
                ->where('deleted', '=', 0)
                ->select('id', 'name', 'team_a', 'team_b')
                ->get();
    
            // Add games to the campaign object
            $campaign->games = $games;
        }
    
        return response()->json(['status' => true, 'data' => $campaigns]);
    }
    
    

    public function get_single_campaign(REQUEST $request){

        $campaigns = DB::table('campaigns as cam')
            ->join('events as e', 'cam.event_id', '=', 'e.id')
            ->where('cam.id','=',$request->id)
            ->where('e.deleted', '=', 0)
            ->where('cam.deleted', '=', 0)
            ->select('cam.*', 'cam.campaign_title', 'cam.image as avatar','e.title as event_title')
            ->orderBy('cam.created_at', 'DESC')
            ->get();
    
        // Fetch games associated with each campaign
        foreach ($campaigns as $campaign) {
            $games = DB::table('games')
                ->where('campaign_id', '=', $campaign->id)
                ->where('deleted', '=', 0)
                ->select('id', 'name', 'team_a', 'team_b')
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
        $update_data=DB::table('campaigns')
        ->where('id','=',$request->id)
        ->update([
            'event_id' => $request->event_id,
            'campaign_title' => $request->campaign_title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);
        $update_game=0;
        $gid=0;
        $event_value = DB::table('events')
        ->where('id','=',$request->event_id)
        ->get();
        if($event_value[0]->title=="PREDICTION EVENT"){
            $games = json_decode($request->games, true);
            $update_game=DB::table('games')
            ->where('campaign_id','=',$request->id)
            ->update([
                'deleted' => 1,
            ]);
            if($games){
            foreach($games as $game){
                // return $game;
                

                if($game['id']==0){

                     $data = array(
                    'name' => $game['name'],
                    'team_a' => $game['team_a'],
                    'team_b' => $game['team_b'],
                    'campaign_id'=>$request->id,
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
    
}