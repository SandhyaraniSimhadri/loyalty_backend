<?php

// namespace App\Http\Middleware;

// use Closure;

// class Cors
// {
//     public function handle($request, Closure $next)
//     {
//         return $next($request)
//             ->header('Access-Control-Allow-Origin', 'http://localhost:4200/')
//             // ->header('Access-Control-Allow-Origin', 'http://13.235.245.17')
//             ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
//             ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
//     }
// }
namespace App\Http\Middleware;

use Closure;

class Cors {
    
    public function handle($request, Closure $next) {
        
        $response = $next($request);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, GET');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With, Application', 'ip');
        return $response; 
    }
}
