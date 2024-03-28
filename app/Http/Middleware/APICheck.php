<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\DB;


use App;
use Closure;
class APICheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // return ($request->header('token'));
        if ($request->header('token')) {
                $user =  DB::table('users')
                ->where('token', $request->header('token'))
                ->first();
                if (isset($user->id)) {
                    $request['logged_id'] = $user->id;
                    $request['logged_name'] = $user->user_name;
                    $request['logged_email'] = $user->email;
                    $request['logged_user_type'] = $user->user_type;
                    return $next($request);
                } else {
                    return response(array('msg' => 'Token Expired'), 401)
                        ->header('Content-Type', 'application/json');
                }
            } else {
                return response(array('msg' => 'Unauthorized User'), 401)
                    ->header('Content-Type', 'application/json');
            }

    }
}
