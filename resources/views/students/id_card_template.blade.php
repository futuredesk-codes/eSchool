<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style>
        * {
            font-family: 'DejaVu Sans', sans-serif;
        }

        html,
        body {
            margin: 0px !important;
        }

        .full-width {
            width: 100%;
        }

        .header th {
            padding: 10px 0px;
            background-color: {{ $settings['header_color'] ?? '#00edff' }};
            color: {{ $settings['header_footer_text_color'] ?? 'white' }};
        }

        .vertical-header th {
            padding: 10px 0px;
            background-color: {{ $settings['header_color'] ?? '#00edff' }};
            color: {{ $settings['header_footer_text_color'] ?? 'black' }};
        }

        table {
            border-collapse: collapse;
            border: none;
            font-size: 12px;
            z-index: 1;
        }

        table tr {
            padding: 15px;
        }

        .student-image {
            width: 30%;
            padding: 0px 10px;
            text-align: center;
            vertical-align: middle;
            height: 80px;
        }

        .student-name {
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            font-size: 16px;
            color: {{ $settings['header_color'] ?? '#00edff' }}
        }

        .student-data {
            text-align: left;
            padding-left: 10px;
            padding: 5px;
        }

        .card-title {
            padding: 6px 0px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .school-name {
            padding-right: 10px !important;
            text-align: right;
            font-size: 15px;
            text-transform: uppercase;
            font-weight: bold;
            border-bottom-right-radius: 10px;
        }

        .footer {
            background-color: {{ $settings['footer_color'] ?? '#56cc99' }};
            color: {{ $settings['header_footer_text_color'] ?? 'black' }};
            position: fixed;
            width: 100%;
            padding: 2px 0px;
            font-size: 12px;
            bottom: 0px;
            height: 20px;
            text-align: right;
            vertical-align: middle;
            letter-spacing: 0.8px;
            z-index: 1;
        }

        .school-logo {
            border-bottom-left-radius: 10px;
            background-color: #ffffff;
            z-index: 109
        }

        .card-body {
            height: {{ $settings['card_height'] }};
        }

        .vertical-student-data {
            text-align: left;
            padding: 5px 5px 5px 10px;
        }

        .signature {
            background-size: contain;
            background-position: center center;
            background-repeat: no-repeat;
            padding: 10px;
            position: fixed;
            bottom: 35px;
            right: 10px;
        }

        .vertical-school-name {
            padding: 10px 10px !important;
            text-align: center;
            font-size: 15px;
            text-transform: uppercase;
            font-weight: bold;
            border-bottom-right-radius: 10px;
            border-bottom-left-radius: 10px;
        }
    </style>

    @if (isset($settings['profile_image_style']) && $settings['profile_image_style'] == 'squre')
        <style>
            .student-profile {
                border: 3px solid black;
                border-radius: 6px;
                background-size: contain;
                background-position: center center;
                background-repeat: no-repeat;
                padding: 2px;
            }
        </style>
    @else
        <style>
            .student-profile {
                border: 3px solid black;
                border-radius: 80px;
                background-size: contain;
                background-position: center center;
                background-repeat: no-repeat;
                padding: 2px;
            }
        </style>
    @endif

    @if (isset($settings['layout_type']) && $settings['layout_type'] == 'horizontal')
        <style>
            .background-image {
                position: fixed;
                width: auto;
                padding: 5px;
                height: auto;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                opacity: 0.2;
                z-index: -1;
            }

            .background_image {
                z-index: -1;
                object-fit: cover;
                background-size: contain;
                background-position: center center;
                background-repeat: no-repeat;
            }
        </style>
    @else
        <style>
            .background-image {
                position: fixed;
                width: auto;
                padding: 5px;
                height: auto;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                opacity: 0.2;
                z-index: -1;
            }

            .background_image {
                z-index: -1;
                object-fit: cover;
                background-size: contain;
                background-position: center center;
                background-repeat: no-repeat;
            }
        </style>
    @endif
</head>

<body>
    @foreach ($students as $key => $student)
        <div class="card-body">
            @if ($settings['layout_type'] == 'horizontal')
                <table class="table full-width">
                    <tr class="header">
                        <th class="school-logo">
                            @if ($settings['logo2'] ?? '')
                                <img height="40" src="{{ public_path('storage/') . $settings['logo2'] }}"
                                    alt="">
                            @else
                                <img height="40" src="{{ public_path('assets/logo.svg') }}" alt="">
                            @endif
                        </th>
                        <th class="school-name" colspan="2">{{ $settings['school_name'] }}</th>
                    </tr>
                    <tr>
                        <td class="student-image" rowspan="{{ count($settings['student_id_card_fields']) }}">
                            @if ($student->user->getRawOriginal('image'))
                                <img class="student-profile" height="120" width="120" align="center"
                                    src="{{ public_path('storage/') . $student->user->getRawOriginal('image') }}"
                                    alt="">
                            @else
                                <img class="student-profile" height="120" width="120" align="center"
                                    src="{{ public_path('storage/dummy_logo.jpg') }}" alt="">
                            @endif

                        </td>
                        @if (in_array('student_name', $settings['student_id_card_fields']))
                            <td class="student-name" colspan="2">
                                {{ $student->user->first_name . ' ' . $student->user->last_name }}</td>
                        @endif

                    </tr>

                    @if (in_array('gr_no', $settings['student_id_card_fields']))
                        <tr>
                            <th class="student-data">GR No. :</th>
                            <td>{{ $student->admission_no }}</td>
                        </tr>
                    @endif

                    @if (in_array('class_section', $settings['student_id_card_fields']))
                        <tr>
                            <th class="student-data">Class Section :</th>
                            <td>{{ $student->class_section->class->name . ' ' . $student->class_section->section->name . ' ' . $student->class_section->class->medium->name . ' ' . ($student->class_section->class->streams->name ?? '') }}
                            </td>
                        </tr>
                    @endif

                    @if (in_array('roll_no', $settings['student_id_card_fields']))
                        <tr>
                            <th class="student-data">Roll No. :</th>
                            <td>{{ $student->roll_number ?? '' }}</td>
                        </tr>
                    @endif

                    @if (in_array('dob', $settings['student_id_card_fields']))
                        <tr>
                            <th class="student-data">DOB :</th>
                            <td>{{ date($settings['date_formate'], strtotime($student->user->dob)) }}</td>
                        </tr>
                    @endif

                    @if (in_array('blood_group', $settings['student_id_card_fields']))
                        <tr>
                            <th class="student-data">Blood Group :</th>
                            <td style="text-transform: capitalize">{{ $student->blood_group ?? '' }}</td>
                        </tr>
                    @endif

                    @if (in_array('gender', $settings['student_id_card_fields']))
                        <tr>
                            <th class="student-data">Gender :</th>
                            <td style="text-transform: capitalize">{{ $student->user->gender }}</td>
                        </tr>
                    @endif

                    @if (in_array('session_year', $settings['student_id_card_fields']))
                        <tr>
                            <th class="student-data">Session Year :</th>
                            <td>{{ $sessionYear ?? '' }}</td>
                        </tr>
                    @endif

                    @if (in_array('guardian_name', $settings['student_id_card_fields']))
                        <tr>
                            <th class="student-data">Father/Guardian Name :</th>
                            @if ($student->father)
                                <td>{{ ($student->father->first_name ?? '') . ' ' . ($student->father->last_name ?? '') }}
                                </td>
                            @else
                                <td>{{ ($student->guardian->first_name ?? '') . ' ' . ($student->guardian->last_name ?? '') }}
                                </td>
                            @endif
                        </tr>
                    @endif

                    @if (in_array('guardian_contact', $settings['student_id_card_fields']))
                        <tr>
                            <th class="student-data">Father/Guardian Contact :</th>
                            @if ($student->father)
                                <td>{{ $student->father->mobile ?? '' }}</td>
                            @else
                                <td>{{ $student->guardian->mobile ?? '' }}</td>
                            @endif
                        </tr>
                    @endif
                    @if (in_array('address', $settings['student_id_card_fields']))
                        <tr>
                            <th class="student-data">Address :</th>
                            <td style="text-transform: capitalize">{{ $student->user->permanent_address ?? '' }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td></td>
                        <td colspan="">
                            @if ($settings['signature'] ?? '')
                                <img height="40" class="signature" width="100" align="center"
                                    src="{{ public_path('storage/') . $settings['signature'] }}" alt="">
                                <span style="position: fixed;bottom:25px;right:40px"><b>Signature</b></span>
                            @endif
                        </td>
                    </tr>
                </table>
            @else
                {{-- Vertical --}}
                <table class="table full-width">
                    <tr class="vertical-header">
                        <th class="vertical-school-name" colspan="2">{{ $settings['school_name'] }}</th>
                    </tr>
                    <tr>
                        <th colspan="2">
                            @if ($settings['logo1'] ?? '')
                                <img height="40" style="padding-top: 5px"
                                    src="{{ public_path('storage/') . $settings['logo1'] }}" alt="">
                            @else
                                <img height="40" style="padding-top: 5px" src="{{ public_path('assets/logo.svg') }}"
                                    alt="">
                            @endif
                        </th>
                    </tr>
                    <br>
                    <tr>
                        <td class="student-image" colspan="2">
                            @if ($student->user->getRawOriginal('image'))
                                <img class="student-profile" height="120" width="120" align="center"
                                    src="{{ public_path('storage/') . $student->user->getRawOriginal('image') }}"
                                    alt="">
                            @else
                                <img class="student-profile" height="120" width="120" align="center"
                                    src="{{ public_path('storage/dummy_logo.jpg') }}" alt="">
                            @endif
                        </td>
                    </tr>
                    <br>
                    @if (in_array('student_name', $settings['student_id_card_fields']))
                        <tr>
                            <td class="student-name" colspan="2">
                                {{ $student->user->first_name . ' ' . $student->user->last_name }}</td>
                        </tr>
                    @endif

                    @if (in_array('gr_no', $settings['student_id_card_fields']))
                        <tr>
                            <th class="vertical-student-data">GR No. :</th>
                            <td style="text-transform: capitalize">{{ $student->admission_no }}</td>
                        </tr>
                    @endif

                    @if (in_array('class_section', $settings['student_id_card_fields']))
                        <tr>
                            <th class="vertical-student-data">Class Section :</th>
                            <td>{{ $student->class_section->class->name . ' ' . $student->class_section->section->name . ' ' . $student->class_section->class->medium->name . ' ' . ($student->class_section->class->streams->name ?? '') }}
                            </td>
                        </tr>
                    @endif

                    @if (in_array('roll_no', $settings['student_id_card_fields']))
                        <tr>
                            <th class="vertical-student-data">Roll No. :</th>
                            <td>{{ $student->roll_number ?? '' }}</td>
                        </tr>
                    @endif

                    @if (in_array('dob', $settings['student_id_card_fields']))
                        <tr>
                            <th class="vertical-student-data">DOB :</th>
                            <td>{{ date($settings['date_formate'], strtotime($student->user->dob)) }}</td>
                        </tr>
                    @endif

                    @if (in_array('blood_group', $settings['student_id_card_fields']))
                        <tr>
                            <th class="vertical-student-data">Blood Group :</th>
                            <td style="text-transform: capitalize">{{ $student->blood_group ?? '' }}</td>
                        </tr>
                    @endif

                    @if (in_array('gender', $settings['student_id_card_fields']))
                        <tr>
                            <th class="vertical-student-data">Gender :</th>
                            <td style="text-transform: capitalize">{{ $student->user->gender }}</td>
                        </tr>
                    @endif

                    @if (in_array('session_year', $settings['student_id_card_fields']))
                        <tr>
                            <th class="vertical-student-data">Session Year :</th>
                            <td>{{ $sessionYear ?? '' }}</td>
                        </tr>
                    @endif

                    @if (in_array('guardian_name', $settings['student_id_card_fields']))
                        <tr>
                            <th class="vertical-student-data">Father/Guardian Name :</th>
                            @if ($student->father)
                                <td>{{ ($student->father->first_name ?? '') . ' ' . ($student->father->last_name ?? '') }}
                                </td>
                            @else
                                <td>{{ ($student->guardian->first_name ?? '') . ' ' . ($student->guardian->last_name ?? '') }}
                                </td>
                            @endif

                        </tr>
                    @endif

                    @if (in_array('guardian_contact', $settings['student_id_card_fields']))
                        <tr>
                            <th class="vertical-student-data">Father/Guardian Contact :</th>
                            @if ($student->father)
                                <td>{{ $student->father->mobile ?? '' }}</td>
                            @else
                                <td>{{ $student->guardian->mobile ?? '' }}</td>
                            @endif

                        </tr>
                    @endif

                    @if (in_array('address', $settings['student_id_card_fields']))
                        <tr>
                            <th class="vertical-student-data">Address :</th>
                            <td style="text-transform: capitalize">{{ $student->user->permanent_address }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td></td>
                        <td>
                            @if ($settings['signature'] ?? '')
                                <img height="40" class="signature" width="100" align="center"
                                    src="{{ public_path('storage/') . $settings['signature'] }}" alt="">
                                <span style="position: fixed;bottom:25px;right:40px"><b>Signature</b></span>
                            @endif
                        </td>
                    </tr>
                </table>
            @endif
            <div class="footer">
                <span class="footer-text" style="padding-right:10px"> {{ $settings['school_address'] ?? '' }}</span>
            </div>
            @if (isset($settings['layout_type']) && $settings['layout_type'] == 'horizontal')
                <div class="background-image">
                    @if ($settings['background_image'] ?? '')
                        <img src="{{ public_path('storage/') . $settings['background_image'] }}"
                            class="background_image" height="140" width="360" alt="">
                    @endif
                </div>
            @else
                <div class="background-image">
                    @if ($settings['background_image'] ?? '')
                        <img src="{{ public_path('storage/') . $settings['background_image'] }}"
                            class="background_image" height="140" width="280" alt="">
                    @endif
                </div>
            @endif


        </div>
    @endforeach
</body>

</html>
