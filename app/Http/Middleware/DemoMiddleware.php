<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DemoMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // echo $request->getRequestUri();

        // these endpoints are allowed in DEMO MODE 
        $exclude_uri = array(
            '/login',
            '/generate-id-card',
            '/set-session-year',

            '/api/student/login',
            '/api/student/get-user-message',
            '/api/student/send-message',
            '/api/student/get-leave-list',

            '/api/parent/login',
            '/api/parent/get-user-message',
            '/api/parent/send-message',
            '/api/parent/get-leave-list',

            '/api/teacher/login',
            '/api/teacher/get-user-message',
            '/api/teacher/send-message',
            '/api/teacher/get-leave-list',
        );

        if (env('DEMO_MODE')) {
            if (!$request->isMethod('get') && !in_array($request->getRequestUri(), $exclude_uri) && \Auth::user() && \Auth::user()->email !== "demomodeoff@gmail.com") {
                if ($request->is('api/*')) {
                    return response()->json(array(
                        'error' => true,
                        'message' => "This is not allowed in the Demo Version.",
                        'code' => 112
                    ));
                }
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(array(
                        'error' => true,
                        'message' => "This is not allowed in the Demo Version.",
                        'code' => 112
                    ), 403);
                }
                return redirect()->back()->withErrors(["This is not allowed in the Demo Version."]);
            }
        }
        return $next($request);
    }
}
