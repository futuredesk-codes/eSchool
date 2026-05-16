<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Add your CSS stylesheets or link to external stylesheets here -->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Bonafide Certificate || {{ config('app.name') }}</title>
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
            /* background-color: #f9f9f9; */
            /* box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.1); Add shadow */
            padding: 30px; /* Add padding to the card */
        }
    </style>

</head>
<body>
    <div class="container">
        <div class="card-document">
            <div class="row">
                <div class="col-12">
                    <div class="text-center">
                        <img style="height: 4rem;width: 10rem;"  src="{{public_path('storage/').$settings['logo1']}}" alt="logo"><br>
                        <span class="text-default-d3 heading">{{$settings['school_name']}}</span><br>
                        <span class="text-default-d3 sub-heading">{{$settings['school_address']}}</span>

                    </div>
                    <br>
                    <div class="text-center">
                        <h2 class="heading">
                            Bonafide Certificate
                        </h2>
                    </div>
                    <div class="row mt-5">
                        <p>This is to certify that Mr./Ms. <strong>{{$student_name ?? ''}}</strong> is S/O or D/O of Mr/Mrs <strong>{{$guardian_name }}</strong> bearing roll number {{  $roll_number}}, is a bonafide student of {{ $settings['school_name'] }}, is a student of Standard {{  $class_section }}  for the academic year {{ $sessionYear}}.</p>

                        <p>The purpose of this certificate is to attest to {{ $reason }}.</p>

                        <p>Further details are as follows:</p>

                        <strong>1. Name:</strong> {{$student_name}}.<br>
                        <strong>2. Date of Birth:</strong> {{$dob}}.<br>
                        <strong>3. GR No.:</strong> {{ $gr_no }}.<br>
                        <strong>4. Standard:</strong> {{ $class_section }}.<br>
                        <strong>5. Academic Year:</strong> {{ $sessionYear}}.<br>
                        <strong>6. Reason for Certificate:</strong> {{ $reason}}.<br><br>

                        @if($valid_upto)
                            <p>This certificate is issued for the aforementioned purpose and is valid until {{ $valid_upto}}.</p>
                        @else
                            <p>This certificate is issued for the aforementioned purpose.</p>
                        @endif

                    </div>

                    <div class="row">
                        <span style="position: fixed;bottom:130px;left:50px"><b>Date: {{$date}}</b></span>
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
