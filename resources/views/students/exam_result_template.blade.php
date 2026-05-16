<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Add your CSS stylesheets or link to external stylesheets here -->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Student Result || {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css"
        integrity="sha512-P5MgMn1jBN01asBgU0z60Qk4QxiXo86+wlFahKrsQf37c9cro517WzVSPPV1tDKzhku2iJ2FVgL67wG03SGnNA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* Add your CSS styles here */
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 0;
            width: 100%;
            height: auto;
        }


        .container {
            max-width: 95%;
            /* Adjust for margins */
            height: 95%;
            margin: 0;
            padding: 20px;
            border-radius: 5px;
            background-color: #f9f9f9;
            align-items: center;
            border: 2px solid {{ $settings['theme_color'] ?? '#22577a' }};
        }

        h1 {
            text-align: center;
        }

        .student-info {
            margin-bottom: 20px;
        }

        .result-table {
            width: 95% !important;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 0.7rem;
        }

        .result-table th,
        .result-table td {
            border: 1px solid {{ $settings['theme_color'] ?? '#22577a' }};
            padding: 8px;
            text-align: center;
        }

        .result-table th {
            background-color: #f2f2f2;
            font-size: 0.7rem
        }

        .table-header {
            border: none;
            font-size: 12px;
            width: 95%;
            text-align: center;
        }

        .table-data {
            border: 1px solid {{ $settings['theme_color'] ?? '#22577a' }};
            width: 95%;
            font-size: 0.8rem;
        }

        .table-data tr {
            border: 1px solid {{ $settings['theme_color'] ?? '#22577a' }};
        }

        td {
            padding: 8px;

        }

        tr {
            width: 100%;
        }

        th {
            padding: 0 0 0 5px;
        }

        .heading {
            font-size: 1.3rem;
            font-weight: 700;
            color: {{ $settings['theme_color'] ?? '#22577a' }};
        }

        .sub-heading {
            font-size: 0.8rem;
            word-break: break-all;
            color: {{ $settings['secondary_color'] ?? '#38A3A5' }};
        }

        .heading1 {
            font-size: 1rem;
            font-weight: 700;
            color: {{ $settings['theme_color'] ?? '#22577a' }};
        }

        .sub-heading1 {
            font-size: 0.9rem;
            font-weight: 600;
            color: {{ $settings['theme_color'] ?? '#22577a' }};
        }

        .card-document {
            padding: 10px;
            /* Add padding to the card */
        }
    </style>

</head>

<body>
    <div class="container">
        <div class="card-document">
            <div class="row">
                <div class="col-12">

                    <div class="text-center">
                        @if ($settings['logo1'] ?? '')
                            <img style="height: 3rem;width: 8rem;"
                                src="{{ public_path('storage/') . $settings['logo1'] }}" alt="logo"><br>
                        @endif
                        <span class="text-default-d3 heading">{{ $settings['school_name'] ?? '' }}</span><br>
                        <span class="text-default-d3 sub-heading">{{ $settings['school_address'] ?? '' }}</span>
                    </div>


                    <div class="text-center">
                        <span class="heading1">{{ $data['class_section'] }}
                            @if (!empty($exam))
                                <span>- {{ $exam->name }} -</span>
                            @endif
                            Result
                        </span><br>
                        <span class="sub-heading1"> Session Year - ({{ $data['sessionYear'] }})</span>
                    </div>
                    <br>
                    <div class="student-info">
                        <table class="table-data">
                            <tr>
                                <td><strong>Name:</strong> {{ $data['student_name'] }} </td>
                                <td><strong>DOB :</strong> {{ $data['dob'] }} </td>
                                <td><strong>GR No. :</strong> {{ $data['gr_no'] }} </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Father/Guardian Name :</strong>
                                    {{ trim($data['guardian_name'] ?? '') ?: (trim($data['father_name'] ?? '') ?: '-') }}
                                </td>
                                <td><strong>Class :</strong> {{ $data['class_section'] }}</td>
                                <td><strong>Roll number :</strong> {{ $data['roll_number'] ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                    <table class="result-table">
                        <thead>
                            <tr>
                                <th>Subjects</th>
                                <th>Marks</th>
                                <th>Grade</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($data['subjects'] as $subjectName => $subjectData)
                                <tr>
                                    <th>
                                        {{ $subjectName }}
                                    </th>

                                    <td>
                                        {{ $subjectData['obtained'] ?? '-' }}/{{ $subjectData['total'] ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $subjectData['grade'] ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <br>
                    <div class="student-info">
                        <table class="table-data">
                            <tr>
                                <td><strong>Total :</strong> {{ $data['obtainmarks'] }} out of
                                    {{ $data['totalMarks'] }} </td>
                                <td><strong>Percentage :</strong> {{ $data['percentage'] }}% </td>
                            </tr>
                            <tr>
                                <td><strong>Grade :</strong> {{ $data['grade'] }} </td>
                                <td><strong>Result :</strong> {{ $data['result'] }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="footer">
                    @if ($settings['signature'] ?? '')
                        <img class="" height="40" style="position: fixed;bottom:50px;right:40px"
                            class="signature" width="100" align="center"
                            src="{{ public_path('storage/') . $settings['signature'] }}" alt="">
                        <span style="position: fixed;bottom:25px;right:40px"><b>Signature</b></span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>

</html>
