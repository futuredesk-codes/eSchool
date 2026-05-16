<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Add your CSS stylesheets or link to external stylesheets here -->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Leaving Certificate || {{ config('app.name') }}</title>

    {{-- <link rel="shortcut icon" href="}"/> --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css" integrity="sha512-P5MgMn1jBN01asBgU0z60Qk4QxiXo86+wlFahKrsQf37c9cro517WzVSPPV1tDKzhku2iJ2FVgL67wG03SGnNA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

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
            max-width: 95%; /* Adjust for margins */
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

        .heading{
            font-size: 1.5rem;
            font-weight: 700;
            color: {{ $settings['theme_color'] ?? '#22577a' }};
        }
        .sub-heading{
            font-size: 0.8rem;
            color: {{ $settings['secondary_color'] ?? '#38A3A5' }};
        }
        .card-document {
            padding: 30px;
        }
        .result-table {
            width: 95% !important;
            border-collapse: collapse;
            font-size: 1rem;
            text-align: left;
        }
        td{
            padding: 2px;
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            /* white-space: nowrap; */
            word-break: break-all;
        }
        tr{
            width: 100%;

        }
        th{
            padding: 5px;
            max-width: 100px;
        }
    </style>

</head>
<body>
    <div class="container">
        <div class="card-document">
            <div class="row">
                <div class="col-12">
                    <div class="text-center">
                        <img style="height: 4rem;width: 11rem;"  src="{{public_path('storage/').$settings['logo1']}}" alt="logo"><br>
                        <span class="text-default-d3 heading">{{$settings['school_name']}}</span><br>
                        <span class="text-default-d3 sub-heading">{{$settings['school_address']}}</span>
                    </div>
                    <div class="text-center">
                        <h4 class="heading">
                           School Leaving Certificate
                        </h4>
                    </div>
                    <div class="row">
                        <table class="result-table">
                            <tr>
                                <th scope="row" class="text-left">1. Name:</th>
                                <td>{{$student_name ?? '-'}}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-left">2. Date of Birth:</th>
                                <td>{{$dob ?? '-'}}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-left">3. Father Name:</th>
                                <td>{{ $father_name ?? '-'}}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-left">4. Mother Name:</th>
                                <td>{{ $mother_name ?? '-'}}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-left">5. Guardian Name (if applicable):</th>
                                <td >{{ $guardian_name ?? '-'}}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-left">6. GR Number :</th>
                                <td> {{ $gr_no  ?? '-'}}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-left">7. Date of Admission:</th>
                                <td> {{ $admission_date ?? '-'}}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-left">8. Academic Year:</th>
                                <td> {{ $sessionYear ?? '-'}}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-left">9. Last Class:</th>
                                <td>{{ $class_section ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-left">10. Promoted To:</th>
                                <td>{{ $promoted_to ?? '-'}}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-left">11. General Conduct:</th>
                                <td>{{ $general_conduct ?? '-'}}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-left">12. Date of Issue:</th>
                                <td>{{ $date ?? '-'}}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-left">13. Reason for Leaving:</th>
                                <td> {{ $reason ?? '-'}}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-left">14. Any Other / Remark:</th>
                                <td>{{ $remarks ?? '-'}}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="row">
                        @if ($settings['signature'] ?? '')
                            <img class="" height="40" class="signature"  style="position: fixed;bottom:50px;right:40px" width="100" align="center" src="{{ public_path('storage/').$settings['signature'] }}" alt="">
                            <span style="position: fixed;bottom:25px;right:40px"><b>Signature</b></span>
                    @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>

</html>
