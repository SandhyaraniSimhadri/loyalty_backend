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
            
            $data1 = array(
                    'user_id' => $request['logged_id'],
                    'campaign_id' =>  $request->campaign_id,
                    'duration' =>  $request->duration,
                    'time_taken'=> $request->time_taken
                    );

                    $ucid= DB::table('users_campaigns_timetaken')->insertGetId($data1);
            $index=0;
            // return $request->quizzes;
            foreach($request->quizzes as $quiz){
            // return  $quiz['id'];
            $predicted_answer = isset($request->selected_winners[$index]) ? $request->selected_winners[$index] : "Notansweredtimeexceeded"; // or any default value
            // return $request->points_calc;
          
        $data = array(
                    'user_id' => $request['logged_id'],
                    'campaign_id' =>  $request->campaign_id,
                    'game_id' =>  $quiz['id'],
                    'predicted_answer'=> $predicted_answer
                    );
                
            $pid= DB::table('campaign_participants')->insertGetId($data);
            $points=0;
            if($quiz['correct_answer']==$predicted_answer){
                $points=$quiz['points'];
            }
            if($request->points_calc=="true"){
                DB::table('campaign_participants')
                ->where('deleted', '=', 0)
                ->where('predicted_answer','=',$predicted_answer)
                ->where('campaign_id', $request->campaign_id)
                ->where('game_id', $quiz['id'])
                ->update(['points' => $points]);}
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
        $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata')->format('Y-m-d');
    
        if ($request->has('campaign_id')) {
            $campaign_data = DB::table('campaigns as c')
                ->leftJoin('users_campaigns_timetaken as uc', function ($join) use ($request) {
                    $join->on('c.id', '=', 'uc.campaign_id')
                        ->where('uc.user_id', '=', $request['logged_id']);
                })
                ->where('c.deleted', 0)
                ->where('c.id', $request['campaign_id'])
                ->where('c.end_date', '>=', now())
                ->select('c.*', 'uc.time_taken')
                ->first();
        } else {
            $user_linked = DB::table('users as u')
                ->leftJoin('campaign_users as c', 'u.id', '=', 'c.user_id')
                ->where('u.id', $request['logged_id'])
                ->where(function ($query) {
                    $query->where('u.deleted', 0)->orWhereNull('u.deleted');
                })
                ->select('u.*', 'c.*')
                ->orderByDesc('u.created_at')
                ->first();
    
            if (!$user_linked) {
                return response()->json(['status' => true, 'data' => []]);
            }
    
            $campaign_data = DB::table('campaigns as c')
                ->leftJoin('users_campaigns_timetaken as uc', function ($join) use ($request) {
                    $join->on('c.id', '=', 'uc.campaign_id')
                        ->where('uc.user_id', '=', $request['logged_id']);
                })
                ->where('c.deleted', 0)
                ->where('c.end_date', '>=', now())
                ->select('c.*', 'uc.time_taken')
                ->first();
        }
    
        if (!$campaign_data) {
            return response()->json(['status' => true, 'data' => []]);
        }
    
        // Fetch campaign details
        $campaign = DB::table('campaigns as cam')
            ->leftJoin('events as e', 'cam.event_id', '=', 'e.id')
            ->where('cam.id', $campaign_data->id)
            ->where('e.deleted', 0)
            ->where('cam.deleted', 0)
            ->select('cam.*', 'cam.campaign_title', 'cam.campaign_image as avatar', 'e.title as event_title')
            ->first();
    
        if (!$campaign) {
            return response()->json(['status' => true, 'data' => []]);
        }
    
        $campaign->total_time_taken = $campaign_data->time_taken;
    
        // Fetch participants
        $participants = DB::table('users as u')
            ->select(
                'u.id as user_id',
                'u.user_name',
                'u.avatar',
                'u.company_id',
                DB::raw('GROUP_CONCAT(DISTINCT c.predicted_answer ORDER BY c.id SEPARATOR ",") as predicted_answers'),
                DB::raw('COALESCE(totals.total_points, 0) as total_points'),
                DB::raw('COALESCE(cu.time_taken, 0) as time_taken')
            )
            ->join('campaign_participants as c', function ($join) use ($campaign) {
                $join->on('u.id', '=', 'c.user_id')
                    ->where('c.campaign_id', '=', $campaign->id)
                    ->where('c.deleted', 0);
            })
            ->leftJoin(DB::raw('(SELECT user_id, SUM(points) as total_points FROM campaign_participants WHERE campaign_id = '.$campaign->id.' AND deleted = 0 GROUP BY user_id) as totals'), 'u.id', '=', 'totals.user_id')
            ->leftJoin(DB::raw('(SELECT user_id, MIN(time_taken) as time_taken FROM users_campaigns_timetaken WHERE campaign_id = '.$campaign->id.' AND deleted = 0 GROUP BY user_id) as cu'), 'u.id', '=', 'cu.user_id')
            ->where('u.deleted', 0)
            ->groupBy('u.id', 'u.user_name', 'u.avatar', 'u.company_id', 'totals.total_points', 'cu.time_taken')
            ->orderBy('total_points', 'desc')
            ->orderBy('time_taken', 'asc')
            ->orderBy('u.created_at', 'asc')
            ->get();
            // return $participants;
        // Format participants with ranks
       
        $campaign->self = null; // ← You forgot a semicolon here
            // return $request;
        $participantsArray = $participants->map(function ($participant, $index) use ($request, &$campaign) {
            $data = [
                'rank' => $index + 1,
                'user_id' => $participant->user_id,
                'user_name' => $participant->user_name,
                'avatar' => $participant->avatar,
                'company_id' => $participant->company_id,
                'predicted_answers' => $participant->predicted_answers,
                'total_points' => $participant->total_points,
                'time_taken' => $participant->time_taken,
            ];
        
            // Check if this participant is the logged-in user
            if ($participant->user_id == $request->logged_id) {
                $campaign->self = $data;
            }
        
            return $data;
        })->toArray();
        
    
        $campaign->participants = array_slice($participantsArray, 0, $request->input('limit', 10));
    
        // Fetch event-specific data
        if ($campaign->event_title === "PREDICTION EVENT") {
            $games = DB::table('games')
                ->where('campaign_id', $campaign->id)
                ->where('deleted', 0)
                ->select('id', 'name', 'team_a', 'team_b', 'points', 'selected_winner', 'game_start_date', 'game_end_date', 'game_start_time', 'game_end_time', 'team_a_image', 'team_b_image')
                ->get();
    
            if (!$games->isEmpty()) {
                $teamASelections = $participants->where('predicted_answer', $games[0]->team_a)->count();
                $teamBSelections = $participants->where('predicted_answer', $games[0]->team_b)->count();
                $totalSelections = $teamASelections + $teamBSelections;
                $games[0]->team_a_percentage = $totalSelections ? ($teamASelections / $totalSelections) * 100 : 0;
                $games[0]->team_b_percentage = $totalSelections ? ($teamBSelections / $totalSelections) * 100 : 0;
                $campaign->games = $games;
            }
        } elseif ($campaign->event_title === "QUIZ") {
            $campaign->quizzes = DB::table('quizzes')
                ->where('campaign_id', $campaign->id)
                ->where('deleted', 0)
                ->select('id', 'question', 'response_a', 'response_b', 'response_c', 'response_d', 'points', 'correct_answer', 'image', 'file_name')
                ->get();
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
    public function points_for_participant(Request $request)
    {
    
        $currentDateTime = Carbon::now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i');
        $currentDate = Carbon::now()->setTimezone('Asia/Kolkata')->format('Y-m-d');



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



        $campaigns = DB::table('campaigns')
        ->where('event_id', '=', 2)
        ->where('calc_points_immediately', '=', "false")
        ->where('deleted', '=', 0)
        ->select('*', DB::raw("CONCAT(start_date) as game_end_date"))
        ->get();
        // return $campaigns;
        if(!$campaigns->isEmpty()){
        foreach ($campaigns as $campaign) {
            // return $campaign->id;
            if($campaign->game_end_date<=$currentDate){
                // return $game->points;
            $quizzes=DB::table('quizzes')
                ->where('deleted', '=', 0)
                ->where('campaign_id', $campaign->id)
                ->get();
              }
            //   return $quizzes;
              foreach ($quizzes as $quiz){
                // return $quiz->correct_answer;
                DB::table('campaign_participants')
                ->where('deleted', '=', 0)
                ->where('predicted_answer','=', $quiz->correct_answer)
                ->where('campaign_id', $campaign->id)
                ->where('game_id', $quiz->id)
                ->update(['points' => $quiz->points]);
              }

        }}
}
    
}