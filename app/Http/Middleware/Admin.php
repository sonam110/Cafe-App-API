<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if(!\Auth::check()){
            return response(prepareResult(true, [], trans('translate.permission_not_defined')), config('httpcodes.forbidden'));
        }
        if(!$request->user()->role_id == '1'){
            $this->renderable(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
                return response(prepareResult(true, [], trans('translate.permission_not_defined')), config('httpcodes.forbidden'));
            });
        }
        return $next($request);
    }
}
