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
        $index = 0;
        if($request->type==1){
            foreach($request->game as $game)
        $data = array(
                    'user_id' => $request['logged_id'],
                    'campaign_id' =>  $request->campaign_id,
                    'game_id' =>  $game['id'],
                    'predicted_answer'=> $request->selected_winners[$index]
                    );
                
            $pid= DB::table('campaign_participants')->insertGetId($data);
        }
        if($request->type==2){
            $index=0;
            // return $request->quizzes;
            foreach($request->quizzes as $quiz){
            // return  $quiz['id'];
        $data = array(
                    'user_id' => $request['logged_id'],
                    'campaign_id' =>  $request->campaign_id,
                    'game_id' =>  $quiz['id'],
                    'predicted_answer'=> $request->selected_winners[$index]
                    );
                
            $pid= DB::table('campaign_participants')->insertGetId($data);
            $index=$index+1;}
        }
           
            // $updated_value = DB::table('campaign_participants')
            // ->where('campaign_id','=',$request->campaign_id)
            // ->where('user_id','=', $request['logged_id'])
            // ->update(
            //     []
            // );
      

        if ($pid) { 
                $data = array('status' => true, 'msg' => 'Completed successfully');
                return response()->json($data);

        } else {
            // return true;
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }

    }
    public function get_prediction_details(Request $request) {
        // Fetch campaigns
        $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata')->format('Y-m-d');
        // return $request['campaign_id'];
        $campaign_data= DB::table('campaigns')
        ->where('deleted', '=', 0)
        ->where('company_id', '=',  $request['logged_company'])
        ->where('end_date', '>=',  $currentDateTime)
        ->first();
        // return $campaign_data;
        if($request['campaign_id']){
            $campaign_data= DB::table('campaigns')
            ->where('deleted', '=', 0)
            ->where('id', '=',  $request['campaign_id'])
            ->first();
        }
     
        // return $campaign_data;
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
        $subquery = DB::table('campaign_participants as c')
        ->select('c.user_id', DB::raw('SUM(c.points) as total_points'))
        ->groupBy('c.user_id');
     
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
        ->orWhereNull('c.campaign_id')
        ->where('u.company_id', '=', $campaign->company_id)
        ->where('u.company_id', '!=', 0) // Exclude records where company_id is 0
        ->where('u.deleted', '=', 0)
        ->select(
            'u.id as user_id', 
            'c.campaign_id',
            'u.user_name', 
            'u.avatar', 
            'u.company_id', 
            DB::raw('MAX(c.predicted_answer) as predicted_answer'), // Selects one predicted_answer
            'totals.total_points'
        )
        ->groupBy('u.id','c.campaign_id' , 'u.user_name', 'u.avatar', 'u.company_id', 'totals.total_points')
        ->get();



        $participant_self = DB::table('users as u')
        ->leftJoin('campaign_participants as c', function($join) use ($campaign) {
            $join->on('u.id', '=', 'c.user_id')
                ->where('c.campaign_id', '=', $campaign->id)
                ->where('c.deleted', '=', 0);
        })
        ->leftJoinSub($subquery, 'totals', function ($join) {
            $join->on('u.id', '=', 'totals.user_id');
        })
        
        ->where('u.company_id', '=', $campaign->company_id)
        ->where('u.id', '=', $request->logged_id)

        // ->where('u.company_id', '!=', 0) // Exclude records where company_id is 0
        ->where('u.deleted', '=', 0)
        ->select('c.game_id',
            'u.id as user_id', 
            'u.user_name', 
            'u.avatar', 
            'u.company_id', 
        'c.predicted_answer','c.campaign_id', // Selects one predicted_answer
            'totals.total_points'
        )
        ->groupBy('u.id', 'c.game_id','c.campaign_id','u.user_name', 'u.avatar', 'u.company_id', 'c.predicted_answer','totals.total_points')
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


    if($campaign->event_title=="PREDICTION EVENT"){
        $games = DB::table('games')
            ->where('campaign_id', '=', $campaign->id)
            ->where('deleted', '=', 0)
            ->select('id', 'name', 'team_a', 'team_b','points','selected_winner','game_start_date','game_end_date','game_start_time','game_end_time','team_a_image','team_b_image')
            ->get();
            // return $games;

       
       

        // Main query to join users with the aggregated points
   
            
            if($games!=null){
                // return $games[0]->team_a;
                $teamASelections = $participants->where('predicted_answer', '=', $games[0]->team_a)->count();
                $teamBSelections = $participants->where('predicted_answer', '=', $games[0]->team_b)->count();
                // return $teamASelections;

                // Calculate percentage of selection for each team
                $totalSelections = $teamASelections + $teamBSelections;
                $teamAPercentage = $totalSelections > 0 ? ($teamASelections / $totalSelections) * 100 : 0;
                $teamBPercentage = $totalSelections > 0 ? ($teamBSelections / $totalSelections) * 100 : 0;

                // Assign percentages to game object
                $games[0]->team_a_percentage = $teamAPercentage;
                $games[0]->team_a_selections = $teamASelections;
                        // return   $games[0]->team_a_percentage;

                $games[0]->team_b_percentage = $teamBPercentage;
                $games[0]->team_b_selections = $teamBSelections;

                // Add games to the campaign object
                $campaign->games = $games;

        }
            $campaign->participants = $participants;
    }
    if($campaign->event_title=="QUIZ"){
        // return "hiii";
        $quizzes = DB::table('quizzes')
            ->where('campaign_id', '=', $campaign->id)
            ->where('deleted', '=', 0)
            ->select('id', 'question', 'response_a',  'response_b',  'response_c', 'response_d', 'points','correct_answer')
            ->get();
           
        
        $campaign->total_points = $totalCampaignPoints;

        // Add games to the campaign object
        $campaign->quizzes = $quizzes;
        $campaign->participants = $participants;
    }

        $campaign->self = $participant_self;


    


    return response()->json(['status' => true, 'data' => $campaign]);}
    else{
        return response()->json(['status' => true, 'data' =>[]]);
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
    public function points_for_participant(Request $request)
    {
    
        $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i');


        $games = DB::table('games')
            ->where('deleted', '=', 0)
            ->select('*', DB::raw("CONCAT(game_end_date, ' ', game_end_time) as game_end_datetime"))
            ->get();

            // return $games
        

        foreach ($games as $game) {
            if($game->game_end_datetime<=$currentDateTime){
                // return $game->points;
            DB::table('campaign_participants')
                ->where('deleted', '=', 0)
                ->where('predicted_answer','=', $game->selected_winner)
                ->where('campaign_id', $game->campaign_id)
                ->where('game_id', $game->id)
                ->update(['points' => $game->points]);}
        }
}
    
}