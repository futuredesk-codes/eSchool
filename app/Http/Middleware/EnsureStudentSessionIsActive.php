<?php

namespace App\Http\Middleware;

use App\Models\StudentSessions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentSessionIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $session_year_id = getSettings('session_year')['session_year'];
        $studentSession = StudentSessions::where('session_year_id', $session_year_id)
            ->where('student_id', $user->student->id)->first();;

        if (! $studentSession) {
            return response()->json([
                'message' => 'Your account is not active for the current academic year because promotion to the next class has not been completed. Please contact the administration.',
                'code'    => 'SESSION_NOT_ACTIVE',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($studentSession->status == 0) {
            return response()->json([
                'message' => 'Your Account is Deactivate Please contact Admin for Further Help.',
                'code'    => 'SESSION_NOT_ACTIVE',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
