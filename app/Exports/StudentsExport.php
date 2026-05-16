<?php

declare(strict_types=1);

namespace App\Exports;

use DateTime;
use App\Models\StudentSessions;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class StudentsExport implements FromCollection, WithHeadings
{
    use Exportable;
    private $class_section_id;
    private $date;

    public function __construct($class_section_id, $date)
    {
        $this->class_section_id = $class_section_id;
        $this->date = $date;
    }

    public function collection()
    {
        $dateRange = explode(' - ', $this->date);
        $start = new DateTime($dateRange[0]);
        $end = new DateTime($dateRange[1]);

        $session_year_id = getSettings('session_year')['session_year'];

        $end->modify('+1 day');
        $interval = $start->diff($end);
        $numberOfDays = $interval->days;

        $students = StudentSessions::where('class_section_id', $this->class_section_id)
            ->where('session_year_id', $session_year_id)
            ->with('student.user')
            ->get()
            ->pluck('student');

        $data = array();
        $date = clone $start; // Create a clone of the start date

        for ($i = 0; $i < $numberOfDays; $i++) {
            foreach ($students as $student) {
                $data[] = [
                    'student_id' => $student->id,
                    'admission_no' => $student->admission_no,
                    'roll_number' => $student->roll_number,
                    'name' => $student->user->first_name . ' ' . $student->user->last_name,
                    'date' => $date->format('d-m-Y'), // Use the current date
                ];
            }
            $date->modify('+1 day'); // Increment the date for the next iteration
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'student_id',
            'admission_no',
            'roll_number',
            'name',
            'date',
            'type',
        ];
    }
}
