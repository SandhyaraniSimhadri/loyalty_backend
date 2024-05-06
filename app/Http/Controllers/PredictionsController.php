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


class PredictionsController extends Controller{
    public function __construct()
    {
    }
    

    public function add_prediction_winner(Request $request)
    {
       
        // return $request;
        $date = date('Y-m-d H:i:s');
     
        $data = array(
                    'user_id' => $request['logged_id'],
                    'campaign_id' => $campaign->id,
                    'team_name'=> $request->selected_winner
                    );
                    
            $pid= DB::table('campaign_participants')->insertGetId($data);
           
            // $updated_value = DB::table('campaign_participants')
            // ->where('campaign_id','=',$request->campaign_id)
            // ->where('user_id','=', $request['logged_id'])
            // ->update(
            //     []
            // );
      

        if ($pid) { 
                $data = array('status' => true, 'msg' => 'Winner prediction added successfully');
                return response()->json($data);

        } else {
            // return true;
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }

    }
    public function get_prediction_details(Request $request) {
        // Fetch campaigns
        $campaign_data= DB::table('campaigns')
        ->where('company_id', '=',  $request['logged_company'])
        ->first();

        if($campaign_data){
        $campaigns = DB::table('campaigns as cam')
        ->leftJoin('events as e', 'cam.event_id', '=', 'e.id')
        ->where('cam.id','=',$campaign_data->id)
        ->where('e.deleted', '=', 0)
        ->where('cam.deleted', '=', 0)
        ->select('cam.*', 'cam.campaign_title', 'cam.image as avatar','e.title as event_title')
        ->orderBy('cam.created_at', 'DESC')
        ->get();
        $campaign=$campaigns[0];
    // Fetch games associated with each campaign
    if($campaign->event_title=="PREDICTION EVENT"){
        $games = DB::table('games')
            ->where('campaign_id', '=', $campaign->id)
            ->where('deleted', '=', 0)
            ->select('id', 'name', 'team_a', 'team_b')
            ->get();

       


   
            $participants = DB::table('users as u')
            ->leftJoin('campaign_participants as c', 'u.id', '=', 'c.user_id')
            ->where('u.company_id', '=', $campaign->company_id)
            ->where('u.company_id', '!=', 0) // Exclude records where company_id is 0
            ->where(function ($query) use ($campaign) {
                $query->where('c.campaign_id', '=', $campaign->id)
                      ->orWhereNull('c.campaign_id'); // Include records where campaign_id is null
            })
            ->where('u.deleted', '=', 0)
            ->select('u.id', 'u.user_name', 'c.team_name', 'c.campaign_id', 'u.company_id') // Include campaign_id
            ->get();


       

        $teamASelections = $participants->where('team_name', '=', $games[0]->team_a)->count();
        $teamBSelections = $participants->where('team_name', '=', $games[0]->team_b)->count();

        // Calculate percentage of selection for each team
        $totalSelections = $teamASelections + $teamBSelections;
        $teamAPercentage = $totalSelections > 0 ? ($teamASelections / $totalSelections) * 100 : 0;
        $teamBPercentage = $totalSelections > 0 ? ($teamBSelections / $totalSelections) * 100 : 0;

        // Assign percentages to game object
        $games[0]->team_a_percentage = $teamAPercentage;
        $games[0]->team_b_percentage = $teamBPercentage;

        // Add games to the campaign object
        $campaign->games = $games;
        $campaign->participants = $participants;
    }
        $campaign->self = $campaign_data;


    


    return response()->json(['status' => true, 'data' => $campaign]);}
    else{
        return response()->json(['status' => true, 'data' =>[]]);
    }
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
                ->select('id', 'name', 'team_a', 'team_b','points','selected_winner')
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

    public function select_winner(REQUEST $request){
        $selected_winner=DB::table('games')
        ->where('id','=',$request->id)
        ->update([
            'selected_winner'=>$request->winner,
        ]);
        return response()->json(['status' => true,'msg'=>"Winner selected successfully", 'data' => $selected_winner]);
    }
}