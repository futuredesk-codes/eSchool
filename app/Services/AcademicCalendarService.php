<?php

namespace App\Services;

use App\Models\Event;
use App\Models\ExamTimetable;
use App\Models\Holiday;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Throwable;

class AcademicCalendarService
{
    public function generate(array $classIds): string
    {
        $sessionYear = $this->getSessionYear();

        $semesterBreaks = $this->getSemesterBreaks($sessionYear);
        $holidays       = $this->getHolidays($sessionYear->id);
        $events         = $this->getEvents($sessionYear->id);
        $exams          = $this->getExams($sessionYear, $classIds);

        $allItems = $holidays
            ->merge($semesterBreaks)
            ->merge($events)
            ->merge($exams)
            ->sortBy('date')
            ->values();

        $calendarItemsByMonth = $allItems->groupBy(function ($item) {
            return Carbon::parse($item->date)->format('F Y');
        });

        return $this->generatePdf($sessionYear->name, $calendarItemsByMonth);
    }

    private function getSessionYear()
    {
        $sessionYearId = getSettings('session_year')['session_year'];

        $sessionYear = DB::table('session_years')
            ->select('id', 'name', 'start_date', 'end_date')
            ->where('id', $sessionYearId)
            ->first();

        if (!$sessionYear) {
            throw new \Exception('Session year not found.');
        }

        return $sessionYear;
    }

    private function getSemesterBreaks($sessionYear): Collection
    {
        $semesterBreaks = collect();

        $semesters = Semester::query()
            ->whereBetween('start_date', [
                $sessionYear->start_date,
                $sessionYear->end_date
            ])
            ->orderBy('start_date')
            ->get();

        for ($i = 0; $i < $semesters->count() - 1; $i++) {
            $current = $semesters[$i];
            $next    = $semesters[$i + 1];

            if (
                $current->end_date &&
                $next->start_date &&
                Carbon::parse($next->start_date)->gt(Carbon::parse($current->end_date))
            ) {
                $breakStart = Carbon::parse($current->end_date)->addDay();
                $breakEnd   = Carbon::parse($next->start_date)->subDay();

                if (
                    $breakStart->between($sessionYear->start_date, $sessionYear->end_date) &&
                    $breakEnd->between($sessionYear->start_date, $sessionYear->end_date)
                ) {
                    $semesterBreaks->push((object)[
                        'date'  => $breakStart->toDateString(),
                        'type'  => 'holiday',
                        'title' => "Semester Break: {$breakStart->format('jS M')} - {$breakEnd->format('jS M')}",
                    ]);
                }
            }
        }

        return $semesterBreaks;
    }

    private function getHolidays(int $sessionYearId): Collection
    {
        return Holiday::where('session_year_id', $sessionYearId)
            ->orderBy('date')
            ->get()
            ->map(fn($item) => (object)[
                'date'  => $item->date,
                'type'  => 'holiday',
                'title' => $item->title,
            ])->toBase();
    }

    private function getEvents(int $sessionYearId): Collection
    {
        return Event::where('session_year_id', $sessionYearId)
            ->orderBy('start_date')
            ->get()
            ->map(fn($item) => (object)[
                'date'  => $item->start_date,
                'type'  => 'event',
                'title' => $item->title,
            ])->toBase();
    }

    private function getExams($sessionYear, array $classIds): Collection
    {
        return ExamTimetable::whereBetween('date', [
            $sessionYear->start_date,
            $sessionYear->end_date
        ])
            ->whereIn('class_id', $classIds)
            ->orderBy('date')
            ->get()
            ->map(fn($item) => (object)[
                'date'  => $item->date,
                'type'  => 'exam',
                'title' => $item->exam_name ?? 'Exam',
            ])->toBase();
    }

    private function generatePdf(string $reportYear, $calendarItemsByMonth): string
    {
        $data = [
            'report_year'             => $reportYear,
            'calendar_items_by_month' => $calendarItemsByMonth,
            'school_name'             => env('APP_NAME'),
            'school_address'          => getSettings('school_address')['school_address'] ?? null,
            'logo'                    => public_path('/storage/' . env('LOGO2')),
        ];

        $pdf = Pdf::loadView('academic_calendar.academic_calendar_pdf', $data);

        return base64_encode($pdf->output());
    }
}
