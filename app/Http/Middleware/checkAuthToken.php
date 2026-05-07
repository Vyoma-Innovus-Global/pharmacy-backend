<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Token;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class checkAuthToken
{
    public function handle(Request $request, Closure $next): Response
    {

        if ($request->header('token')) {
            $token = $request->header('token');
            $token_res = Token::select('t_token', 't_expired_on', 't_user_id')->where('t_token', $token)->first();

            if ($token_res) {
                if ($token_res->t_expired_on < now()) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Token Expired, Login Again'
                    ], 401);
                } else {
                    $request->headers->set('auth_user_data', json_encode([
                        'user_id' => $token_res->t_user_id,
                        // 'role_id' => !empty($token[0]) ? (int)$token[0] : null,
                        // 'vtc_code' => !empty($token[2]) ? $token[2] : null,
                        // 'nodal_id' => !empty($token[3]) ? (int)$token[3] : null,
                    ]));

                    return $next($request);
                }
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid Token, Login Again'
                ], 401);
            }
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Token Not Found'
            ], 401);
        }
    }
}
