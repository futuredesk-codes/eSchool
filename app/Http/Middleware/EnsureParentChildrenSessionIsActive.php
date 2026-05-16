<?php

namespace App\Http\Middleware;

use App\Models\Students;
use App\Models\StudentSessions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentChildrenSessionIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $session_year_id = getSettings('session_year')['session_year'];

        $children = Students::where(function ($q) use ($user) {
            $q->where('father_id', $user->parent->id)
                ->orWhere('mother_id', $user->parent->id)
                ->orWhere('guardian_id', $user->parent->id);
        })
            ->get();

        $hasAnyValidSession = false;
        $hasAnyActiveChild  = false;

        foreach ($children as $child) {

            $session = StudentSessions::where('session_year_id', $session_year_id)
                ->where('student_id', $child->id)
                ->first();

            // Valid session exists
            if ($session) {
                $hasAnyValidSession = true;

                // Active / studying
                if ((int) $session->status === 1) {
                    $hasAnyActiveChild = true;
                }
            }
        }

        // No child has session for current year
        if (! $hasAnyValidSession) {
            return response()->json([
                'message' => 'Your account is not active for the current academic year because promotion to the next class has not been completed. Please contact the administration.',
                'code'    => 'SESSION_NOT_ACTIVE',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // All children are deactivated
        if (! $hasAnyActiveChild) {
            return response()->json([
                'message' => 'Your account is deactivated. Please contact admin for further help.',
                'code'    => 'ACCOUNT_DEACTIVATED',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
