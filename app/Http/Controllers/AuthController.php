<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AuthController extends Controller
{
    function __construct() {
        $this->middleware('auth:api')->only(['request', 'logout']);
    }
    
    function login(Request $request) {
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $user = Auth::user();
        $tokenResult = $user->createToken('Access Token');
        $token = $tokenResult->token;
        $token->save();
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString()
        ], 200);
    }
    
    function logout(Request $request) {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'Logged out']);
    }
    
    function request(Request $request) {
        // GET DATA (lat, long...) FROM MADRID CENTRE
        $url = 'https://api.sunrise-sunset.org/json?lat=40.41831&lng=-3.70275&date=' . date('Y-m-d') . '&formatted=0';
        $actualData = json_decode(file_get_contents($url));
        
        // TO HOURS, MINUTES AND SECONDS
        $results = $actualData->results;
        $from = explode('T', $results->sunrise)[1];
        $from = explode('+', $from)[0];
        $to = explode('T', $results->sunset)[1];
        $to = explode('+', $to)[0];
        
        // TO MINUTES
        $from = (intval(explode(':', $from)[0] * 60)) + (intval(explode(':', $from)[1]));
        $to = (intval(explode(':', $to)[0] * 60)) + (intval(explode(':', $to)[1]));
        $actualMinutes = (intval(date('H')) * 60) + (intval(date('i'))) + 60;
        
        // COS AND SIN
        $equation = (-pi() / 2) + ((((pi() / 2) - (-pi() / 2)) / ($to - $from)) * ($actualMinutes - $from));
        $cos = cos($equation);
        $sin = sin($equation);
            
        // CHECK IF IN TIME RANGE
        if ($actualMinutes >= $from && $actualMinutes <= $to) {
            return response()->json([
                'sin' => round($sin, 2),
                'cos' => round($cos, 2),
                'sensor1' => rand(0, 1),
                'sensor2' => rand(0, 1),
                'sensor3' => rand(0, 1),
                'sensor4' => rand(0, 1)
            ], 200);
        } else {
            return response()->json([
                'message' => 'Out of sunny range... Come back tomorrow!',
                'sin' => 0,
                'cos' => 0,
                'sensor1' => rand(0, 1),
                'sensor2' => rand(0, 1),
                'sensor3' => rand(0, 1),
                'sensor4' => rand(0, 1)
            ], 200);
        }
        
        // return view('request', [
        //      'from' => $from,
        //      'to' => $to,
        //      'sin' => $sin,
        //      'cos' => $cos,
        //      'sensor1' => rand(0, 1),
        //      'sensor2' => rand(0, 1),
        //      'sensor3' => rand(0, 1),
        //      'sensor4' => rand(0, 1)
        // ]);
    }
}
