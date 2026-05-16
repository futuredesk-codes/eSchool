<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\ExamResult;
use App\Models\LeaveDetail;
use App\Models\Parents;
use App\Models\Semester;
use App\Models\StudentSessions;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function login(): RedirectResponse|View
    {
        if (Auth::user()) {
            return redirect('home');
        }

        return view('auth.login');
    }

    public function resetpassword(): View
    {
        return view('settings.reset_password');
    }

    public function checkPassword(Request $request): JsonResponse
    {
        $old_password = $request->input('old_password');
        $password = User::where('id', Auth::id())->first();

        $isValid = $password && Hash::check($old_password, $password->password);

        return response()->json($isValid ? 1 : 0);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $id = Auth::id();
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        try {
            $data = ['password' => Hash::make($request->input('new_password'))];
            User::where('id', $id)->update($data);

            return response()->json([
                'error' => false,
                'message' => trans('data_update_successfully'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function index(): View
    {
        $session_year = (int) (session('session_year') ? session('session_year') : getSettings('session_year')['session_year']);

        $user = Auth::user();

        // Initialize variables
        $data = [
            'teacher' => null,
            'student' => null,
            'parent' => null,
            'teachers' => null,
            'class_sections' => null,
            'rankers' => null,
            'attendance' => null,
            'leaves' => null,
            'filter_upcoming' => 'Today',
            'boys' => 0,
            'girls' => 0,
            'offset' => 0,
            'limit' => 10,
        ];

        // Handle Super Admin specific data
        if ($user->hasRole('Super Admin')) {
            $data = array_merge($data, $this->getSuperAdminData($session_year));
        }

        // Handle Teacher specific data
        if ($user->hasRole('Teacher')) {
            $data['class_sections'] = $this->getTeacherClassSections($user->teacher->id, $session_year);
        }

        // Common data for all users
        $data['date_format'] = 'd-m-Y H:i:s';
        $data['announcement'] = Announcement::where('table_type', '')
            ->where('session_year_id', $session_year)
            ->latest()
            ->limit(3)
            ->get();

        $data['attendance'] = Attendance::with('class_section')
            ->where('session_year_id', $session_year)
            ->select(
                'class_section_id',
                'type',
                'date',
                DB::raw('COUNT(*) as total_attendance'),
                DB::raw('SUM(CASE WHEN type = 1 THEN 1 ELSE 0 END) as total_present')
            )
            ->groupBy('class_section_id')
            ->get();

        $data['leaves'] = $this->getUpcomingLeaves((int) $session_year);

        return view('home', $data);
    }

    public function dashboardData(Request $request): JsonResponse
    {
        $session_year = (int) ($request->session_year_id ? $request->session_year_id : getSettings('session_year')['session_year']);
        $user = Auth::user();

        // Initialize base data structure
        $data = [
            'counts' => [
                'teacher' => null,
                'student' => null,
                'parent' => null,
            ],
            'class_sections' => null,
            'teachers' => null,
            'rankers' => null,
            'attendance' => null,
            'leaves' => null,
            'announcement' => null,
            'boys' => 0,
            'girls' => 0,
        ];

        // Handle Super Admin specific data
        if ($user->hasRole('Super Admin')) {
            $superAdminData = $this->getSuperAdminData($session_year);

            $data['counts'] = [
                'teacher' => $superAdminData['teacher'],
                'student' => $superAdminData['student'],
                'parent' => $superAdminData['parent'],
            ];
            $data['teachers'] = $superAdminData['teachers'];
            $data['boys'] = $superAdminData['boys'];
            $data['girls'] = $superAdminData['girls'];
            $data['rankers'] = $superAdminData['rankers'];
        }

        // Handle Teacher specific data
        if ($user->hasRole('Teacher')) {
            $data['class_sections'] = $this->getTeacherClassSections($user->teacher->id, $session_year);

            // For teachers, we don't need counts, teachers list, or rankers
            unset($data['counts']);
            unset($data['teachers']);
            unset($data['rankers']);
        }

        // Common data for all users
        $data['announcement'] = Announcement::where('table_type', '')
            ->where('session_year_id', $session_year)
            ->latest()
            ->limit(3)
            ->get();

        $data['attendance'] = Attendance::with('class_section')
            ->where('session_year_id', $session_year)
            ->select(
                'class_section_id',
                'type',
                'date',
                DB::raw('COUNT(*) as total_attendance'),
                DB::raw('SUM(CASE WHEN type = 1 THEN 1 ELSE 0 END) as total_present')
            )
            ->groupBy('class_section_id')
            ->get();

        $data['leaves'] = $this->getUpcomingLeaves((int) $session_year);

        return response()->json($data);
    }

    private function getSuperAdminData($session_year_id): array
    {
        $teacher = Teacher::count();
        $studentSessions = StudentSessions::with('student')
            ->where('status', 1)
            ->where('session_year_id', $session_year_id)
            ->groupBy('student_id')
            ->get();

        $student = $studentSessions->count();

        $parentIds = $studentSessions
            ->flatMap(function ($session) {
                if (! $session->student) {
                    return [];
                }

                return [
                    $session->student->father_id,
                    $session->student->mother_id,
                    $session->student->guardian_id,
                ];
            })
            ->filter()
            ->unique()
            ->values();

        $parent = Parents::whereIn('id', $parentIds)->count();

        $teachers = Teacher::with('user:id,first_name,last_name,image')->get();

        $boys = 0;
        $girls = 0;

        // Get unique user IDs from the student sessions and count genders from the users table
        $studentUserIds = $studentSessions->map(
            fn ($s) => optional($s->student)->user_id
        )->filter()->unique()->values();

        if ($studentUserIds->isNotEmpty()) {
            $boys_count = User::whereIn('id', $studentUserIds)->where('gender', 'male')->count();
            $girls_count = User::whereIn('id', $studentUserIds)->where('gender', 'female')->count();

            $boys = round((($boys_count * 100) / ($boys_count + $girls_count)), 2);
            $girls = round((($girls_count * 100) / ($boys_count + $girls_count)), 2);
        }

        $rankers = ExamResult::with('student.user', 'class_section')
            ->where('session_year_id', $session_year_id)
            ->select('class_section_id', 'student_id', 'percentage', 'grade', DB::raw('MAX(percentage) as max_percentage'))
            ->groupBy('class_section_id')
            ->whereNot('grade', 'Fail')
            ->get();

        return compact('teacher', 'student', 'parent', 'teachers', 'boys', 'girls', 'rankers');
    }

    private function getTeacherClassSections(int $teacher_id, int $session_year): ?Collection
    {
        $class_section_ids = ClassTeacher::select('class_section_id')
            ->where('class_teacher_id', $teacher_id)
            ->where('session_year_id', $session_year)
            ->pluck('class_section_id');

        if ($class_section_ids->isNotEmpty()) {
            return ClassSection::with('class', 'section', 'class.medium', 'class.streams')
                ->whereIn('id', $class_section_ids)
                ->get();
        }

        return null;
    }

    private function getUpcomingLeaves(int $session_year_id): ?Collection
    {
        $today_date = Carbon::now()->format('Y-m-d');

        return LeaveDetail::whereHas('leave', function ($query) use ($session_year_id) {
            $query->where('status', 1)
                ->whereHas('leave_master', fn ($q) => $q->where('session_year_id', $session_year_id));
        })
            ->with('leave.user')
            ->whereDate('date', '>=', $today_date)
            ->orderBy('date', 'ASC')
            ->take(10)
            ->get();
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->flush();
        $request->session()->regenerate();

        return redirect('/');
    }

    public function getSubjectByClassSection(Request $request): JsonResponse
    {
        $currentSemester = Semester::get()->first(function ($semester) {
            return $semester->current;
        });

        $session_year_id = (int) ($request->input('session_year_id') ?: getSettings('session_year')['session_year']);
        $classSectionId = (int) $request->input('class_section_id');
        $classSection = ClassSection::select('class_id')->where('id', $classSectionId)->first();

        if (! $classSection) {
            return response()->json([]);
        }

        $class = ClassSchool::withSessionConfigFor($session_year_id)->find($classSection->class_id);
        if (! $class) {
            return response()->json([]);
        }

        $semesterId = $request->input('semester_id');
        $allSemesters = $request->boolean('all_semesters');

        $subjects = [];
        if ($class->getIncludeSemestersForSession($session_year_id)) {
            if ($allSemesters) {
                $subjects = ClassSubject::SubjectTeacher($classSectionId)
                    ->where('class_id', $class->id)
                    ->whereNotNull('semester_id')
                    ->where('session_year_id', $session_year_id)
                    ->with('subject', 'semester')
                    ->get();
            } elseif ($semesterId || $currentSemester) {
                $effectiveSemesterId = $semesterId ?: $currentSemester->id;
                $subjects = ClassSubject::SubjectTeacher($classSectionId, $effectiveSemesterId)
                    ->where('class_id', $class->id)
                    ->where('semester_id', $effectiveSemesterId)
                    ->where('session_year_id', $session_year_id)
                    ->with('subject')
                    ->get();
            }
        } else {
            $subjects = ClassSubject::SubjectTeacher($classSectionId)
                ->where('class_id', $class->id)
                ->where('session_year_id', $session_year_id)
                ->with('subject')
                ->get();
        }

        return response()->json($subjects);
    }

    public function getTeacherByClassSubject(Request $request): JsonResponse
    {
        $teachers = Teacher::with('user')->get();

        return response()->json($teachers);
    }

    public function resetPasswordView(): View
    {
        return view('settings.reset_password');
    }

    public function editProfile(): View
    {
        $admin_data = Auth::user();

        return view('settings.update_profile', compact('admin_data'));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'mobile' => 'required|digits_between:1,16',
            'image' => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'gender' => 'required|in:male,female',
            'dob' => 'required|date',
            'current_address' => 'required',
            'permanent_address' => 'required',
        ]);

        try {
            $user = Auth::user();
            $data = $request->only([
                'first_name',
                'last_name',
                'mobile',
                'gender',
                'current_address',
                'permanent_address',
            ]);

            $data['dob'] = date('Y-m-d', strtotime($request->input('dob')));

            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($user->getRawOriginal('image')) {
                    Storage::disk('public')->delete($user->getRawOriginal('image'));
                }

                $file = $request->file('image');
                $fileName = time().'-'.$file->getClientOriginalName();
                $filePath = 'profile/'.$fileName;

                resizeImage($file);
                $destinationPath = storage_path('app/public/profile');
                $file->move($destinationPath, $fileName);

                $data['image'] = $filePath;
            }

            $user->update($data);

            return response()->json([
                'error' => false,
                'message' => trans('data_update_successfully'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function updateWarningModal(Request $request): JsonResponse
    {
        try {
            // Implementation for warning modal update
            return response()->json([
                'error' => false,
                'message' => 'Warning modal updated successfully',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }
}
