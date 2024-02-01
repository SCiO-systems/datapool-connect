<?php

namespace App\Http\Middleware;

use App\Services\OAuthService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ValidateToken
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * * @param  string  $permission
* //     * @return JsonResponse
     */

    public function handle(Request $request, Closure $next)
    {
        if (env('APP_ENV') === 'testing') {
            return $next($request);
        }

        $authResult = (new OAuthService)->validateAccessToken();

        if($authResult["code"] === 200){
            return $next($request);
        } else {
            Log::info("This is the auth result", $authResult);
            return response()->json(["result" => $authResult["result"], "errorMessage" => $authResult["errorMessage"]], $authResult["code"]);
        }
    }
}
