<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClassSchool;
use App\Models\ClassSessionConfig;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Services\ClassSessionConfigService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SemesterController extends Controller
{
    public function __construct(
        private readonly ClassSessionConfigService $classSessionConfigService,
    ) {}

    /**
     * Display a listing of semesters.
     */
    public function index()
    {
        if (! Auth::user()->can('semester-list')) {
            return redirect(route('home'))
                ->withErrors(['message' => trans('no_permission_message')]);
        }
        $session_years = SessionYear::orderBy('id', 'ASC')->get();
        $classes = ClassSchool::with('medium', 'streams')->get();

        // dd();
        return response(view('semester.index', compact('session_years', 'classes')));
    }

    /**
     * Store a newly created semester.
     */
    public function store(Request $request)
    {
        if (! Auth::user()->can('semester-create')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        // Validation: end_date must come after start_date
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'session_year_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }
        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            // Check for overlapping date ranges
            $checkOverlap = $this->checkIfDateRangeOverlaps($startDate, $endDate);

            if ($checkOverlap['error']) {
                return response()->json($checkOverlap);
            }

            Semester::create([
                'name' => $request->name,
                'session_year_id' => $request->session_year_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            return response()->json([
                'error' => false,
                'message' => trans('data_store_successfully'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show a paginated list (used by datatables / AJAX).
     */
    public function show()
    {
        if (! Auth::user()->can('semester-list')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');

        $query = Semester::query();

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%$search%")
                    ->orWhere('name', 'LIKE', "%$search%");
            });
        }

        if (request()->has('session_year_id') && request('session_year_id') != '') {
            $query->where('session_year_id', request('session_year_id'));
        }

        $total = $query->count();
        $res = $query->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        $rows = [];
        $no = 1;

        foreach ($res as $row) {
            $related = [
                'class subjects',
                'elective subject groups',
                'timetables',
                'student subjects',
                'online exams',
            ];

            $operate = '<a href="javascript:void(0)" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon btn-edit-semester"'
                .' data-id="'.$row->id.'"'
                .' data-name="'.$row->name.'"'
                .' data-start="'.$row->start_date->format('Y-m-d').'"'
                .' data-end="'.$row->end_date->format('Y-m-d').'"'
                .' title="'.trans('edit').'">'
                .'<i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href="javascript:void(0)" class="btn btn-xs btn-gradient-danger btn-rounded btn-icon btn-delete-semester"'
                .' data-id="'.$row->id.'"'
                .' data-related=\''.json_encode($related).'\''
                .' title="'.trans('delete').'">'
                .'<i class="fa fa-trash"></i></a>';

            $rows[] = [
                'id' => $row->id,
                'no' => $no++,
                'name' => $row->name,
                'start_date' => $row->start_date->format('Y-m-d'),
                'end_date' => $row->end_date->format('Y-m-d'),
                'status' => $row->current ? 1 : 0,
                'operate' => $operate,
                'created_at' => $row->created_at->format('Y-m-d'),
                'updated_at' => convertDateFormat($row->updated_at, 'd-m-Y H:i:s'),
            ];
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    /**
     * Update an existing semester.
     */
    public function update(Request $request)
    {
        if (! Auth::user()->can('semester-edit')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'edit_id' => 'required|exists:semesters,id',
            'edit_name' => 'required|string|max:191',
            'edit_session_year_id' => 'required|integer',
            'edit_start_date' => 'required|date',
            'edit_end_date' => 'required|date|after:edit_start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {
            $semester = Semester::findOrFail($request->edit_id);

            $startDate = Carbon::parse($request->edit_start_date);
            $endDate = Carbon::parse($request->edit_end_date);

            $checkOverlap = $this->checkIfDateRangeOverlaps($startDate, $endDate, $semester->id);

            if ($checkOverlap['error']) {
                return response()->json($checkOverlap);
            }

            $semester->update([
                'name' => $request->edit_name,
                'session_year_id' => $request->edit_session_year_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            return response()->json([
                'error' => false,
                'message' => trans('data_update_successfully'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove a semester.
     */
    public function destroy($id)
    {
        if (! Auth::user()->can('semester-delete')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        DB::beginTransaction();

        try {

            $semester = Semester::findOrFail($id);

            $sessionYearId = $semester->session_year_id;

            // Delete semester
            $semester->delete();

            // Check if any semesters remain for this session year
            $remainingSemesters = Semester::where('session_year_id', $sessionYearId)->exists();

            // If no semesters remain
            if (! $remainingSemesters) {

                // Get all configured class ids before deleting config
                $classIds = ClassSessionConfig::where('session_year_id', $sessionYearId)
                    ->where('include_semesters', true)
                    ->pluck('class_id')
                    ->toArray();

                // Delete config
                ClassSessionConfig::where('session_year_id', $sessionYearId)
                    ->where('include_semesters', true)
                    ->delete();

                // Wipe old data
                if (! empty($classIds)) {
                    app(ClassSessionConfigService::class)
                        ->wipeClassData($sessionYearId, $classIds, true);
                }
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get classes configured for semesters in a session year.
     */
    public function getClassConfig(Request $request)
    {
        $sessionYearId = $request->session_year_id;

        $configuredClassIds = ClassSessionConfig::where('session_year_id', $sessionYearId)
            ->where('include_semesters', true)
            ->pluck('class_id');

        $classes = ClassSchool::with('medium')
            ->whereIn('id', $configuredClassIds)
            ->get()
            ->map(function ($class) {
                return [
                    'id' => $class->id,
                    'name' => $class->name,
                    'medium_name' => $class->medium->name ?? '',
                ];
            });

        return response()->json(['error' => false, 'data' => $classes]);
    }

    /**
     * Save class assignment configuration for a session year.
     */
    public function saveClassConfig(Request $request)
    {
        if (! Auth::user()->can('semester-edit')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }

        $validator = Validator::make($request->all(), [
            'session_year_id' => 'required|integer|exists:session_years,id',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'integer|exists:classes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {
            $this->classSessionConfigService->saveConfig(
                (int) $request->session_year_id,
                $request->class_ids ?? [],
            );

            return response()->json([
                'error' => false,
                'message' => trans('data_update_successfully'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if a new semester overlaps with an existing one (date-based logic).
     */
    private function checkIfDateRangeOverlaps(Carbon $start, Carbon $end, ?int $ignoreID = null): array
    {
        $query = Semester::query()->withoutTrashed();
        if ($ignoreID) {
            $query->where('id', '!=', $ignoreID);
        }

        $semesters = $query->get();

        // dd(($semesters));
        foreach ($semesters as $semester) {
            $existingStart = Carbon::parse($semester->start_date);
            $existingEnd = Carbon::parse($semester->end_date);

            // Overlap condition: start < existing_end && end > existing_start
            if ($start->lt($existingEnd) && $end->gt($existingStart)) {
                return [
                    'error' => true,
                    'message' => trans('semester_overlap_message'),
                    'data' => [
                        'conflict_with' => $semester->name,
                        'existing_start' => $existingStart->toDateString(),
                        'existing_end' => $existingEnd->toDateString(),
                    ],
                ];
            }
        }

        return ['error' => false, 'message' => 'success'];
    }
}
