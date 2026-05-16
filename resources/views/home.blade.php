@extends('layouts.master')
@section('title')
    {{ __('dashboard') }}
@endsection
@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-theme text-white mr-2">
                    <i class="fa fa-home"></i>
                </span> {{ __('dashboard') }}
            </h3>
        </div>
        @if ($class_sections)
            <div class="row classes">
                <div class="col-md-12 grid-margin stretch-card search-container">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">{{ __('Class Teacher') }}</h4>
                            <div class="d-flex flex-wrap" id="class-sections-container">
                                @php
                                    $colors = [
                                        'bg-gradient-danger',
                                        'bg-gradient-success',
                                        'bg-gradient-primary',
                                        'bg-gradient-info',
                                        'bg-gradient-secondary',
                                        'bg-gradient-warning',
                                    ];
                                    $colorIndex = 0;
                                @endphp

                                @foreach ($class_sections as $class_section)
                                    @php
                                        $currentColor = $colors[$colorIndex];
                                        $colorIndex = ($colorIndex + 1) % count($colors);
                                    @endphp

                                    <div class="col-md-2 stretch-card grid-margin">
                                        <div class="card {{ $currentColor }} card-img-holder text-white">
                                            <div class="card-body">
                                                <img src="{{ asset(config('global.CIRCLE_SVG')) }}"
                                                    class="card-img-absolute" alt="circle-image" />
                                                <h6 class="mb-2">
                                                    <h4>{{ $class_section->class->name }}-{{ $class_section->section->name }}
                                                        {{ $class_section->class->medium->name }}
                                                        {{ $class_section->class->streams->name ?? '' }}</h4>
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="row">
            @if ($teacher !== null)
                <div class="col-md-4 stretch-card grid-margin">
                    <div class="card bg-gradient-danger card-img-holder text-white">
                        <div class="card-body">
                            <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                alt="circle-image" />
                            <h4 class="font-weight-normal mb-3">{{ __('total_teachers') }}<i
                                    class="mdi mdi-chart-line mdi-24px float-right"></i>
                            </h4>
                            <h2 class="mb-5" id="teacher-count">{{ $teacher }}</h2>
                            {{-- <h6 class="card-text">Increased by 60%</h6> --}}
                        </div>
                    </div>
                </div>
            @endif
            @if ($student !== null)
                <div class="col-md-4 stretch-card grid-margin">
                    <div class="card bg-gradient-info card-img-holder text-white">
                        <div class="card-body">
                            <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                alt="circle-image" />
                            <h4 class="font-weight-normal mb-3">{{ __('total_students') }}<i
                                    class="mdi mdi-bookmark-outline mdi-24px float-right"></i>
                            </h4>
                            <h2 class="mb-5" id="student-count">{{ $student }}</h2>
                        </div>
                    </div>
                </div>
            @endif
            @if ($parent !== null)
                <div class="col-md-4 stretch-card grid-margin">
                    <div class="card bg-gradient-success card-img-holder text-white">
                        <div class="card-body">
                            <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                alt="circle-image" />
                            <h4 class="font-weight-normal mb-3">{{ __('total_parents') }}<i
                                    class="mdi mdi-diamond mdi-24px float-right"></i>
                            </h4>
                            <h2 class="mb-5" id="parent-count">{{ $parent }}</h2>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="row">

            @if ($attendance)
                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body h-scroll">
                            <h4 class="card-title">{{ __('attendance') }}</h4>
                            <br>
                            <br>
                            <canvas id="myChart" style="width:100%;max-width:600px"></canvas>
                        </div>
                    </div>
                </div>
            @endif
            @if ($leaves)
                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body v-scroll">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="card-title">{{ __('leave') }}</h4>
                                <select id="leaveFilter" class="form-control w-auto" onchange="filterLeaves()">
                                    <option value="today">{{ __('today') }}</option>
                                    <option value="tomorrow">{{ __('tomorrow') }}</option>
                                    <option value="upcoming">{{ __('upcoming') }}</option>
                                </select>
                            </div>

                            <div class="table-responsive">
                                <table class="table" id="leavesTable">
                                    <thead>
                                        <tr>
                                            <th> {{ __('no.') }}</th>
                                            <th> {{ __('image') }}</th>
                                            <th> {{ __('name') }}</th>
                                            <th> {{ __('type') }}</th>
                                            <th> {{ __('date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($leaves as $key => $row)
                                            <tr data-date="{{ $row->date }}">
                                                <td>{{ $key + 1 }}</td>
                                                <td><img class="img-sm rounded-circle" src="{{ $row->leave->user->image }}"
                                                        alt="profile" onerror="onErrorImage(event)"></td>
                                                <td>{{ $row->leave->user->first_name ?? '' }}
                                                    {{ $row->leave->user->last_name ?? '' }}</td>
                                                <td><span class='badge badge-info'>{{ $row->type }}</span></td>
                                                <td><span
                                                        class='badge badge-success'>{{ date('d M', strtotime($row->date)) }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif


        </div>

        <div class="row">
            @if (isset($teachers) && !empty($teachers))
                <div class="col-md-4 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body v-scroll">
                            <h4 class="card-title">{{ __('teacher') }}</h4>
                            @foreach ($teachers as $row)
                                <div class="wrapper d-flex align-items-center py-2 border-bottom">
                                    <img class="img-sm rounded-circle" src="{{ $row->user->image }}" alt="profile"
                                        onerror="onErrorImage(event)">
                                    <div class="wrapper truncate">
                                        <h6 class="ml-1 mb-1 truncate">
                                            {{ $row->user->first_name . ' ' . $row->user->last_name }}
                                        </h6>
                                        <small class="text-muted mb-0">
                                            <i
                                                class="mdi mdi-map-marker-outline mr-1"></i>{{ $row->qualification }}</small>
                                    </div>
                                    <div class="badge badge-pill badge-success ml-auto px-1 py-1">
                                        <i class="mdi mdi-check"></i>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            @if (isset($rankers) && !empty($rankers))
                <div class="col-md-4 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body v-scroll">
                            <h4 class="card-title">Top Rankers</h4>
                            <div class="wrapper d-flex align-items-center py-2 border-bottom">
                                <div class="table-responsive">
                                    <table class="table rankers-table">
                                        <thead>
                                            <tr>
                                                <th> {{ __('no.') }}</th>
                                                <th> {{ __('class') }}</th>
                                                <th> {{ __('name') }}</th>
                                                <th> {{ __('percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($rankers as $key => $row)
                                                <tr>
                                                    <td class="text-center">{{ $key + 1 }}</td>
                                                    <td class="text-wrap">
                                                        {{ $row->class_section->class->name }} -
                                                        {{ $row->class_section->section->name }}
                                                        {{ $row->class_section->class->medium->name }}
                                                        {{ $row->class_section->class->streams->name ?? '' }}
                                                    </td>
                                                    <td class="text-left">
                                                        {{ $row->student->user->full_name }}
                                                    </td>
                                                    <td class="text-center">{{ $row->max_percentage }} %</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if ($boys || $girls)
                <div class="col-md-4 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">{{ __('gender') }}</h4>
                            <canvas id="gender-ratio-chart"></canvas>
                            <div id="gender-ratio-chart-legend"
                                class="rounded-legend legend-vertical legend-bottom-left pt-4"></div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="row">
            @if ($announcement)
                <div class="col-md-12 grid-margin stretch-card search-container">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">{{ __('noticeboard') }}</h4>
                            <div class="table-responsive ">
                                <table class="table align-middle noticeboard">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%;">{{ __('no.') }}</th>
                                            <th style="width: 25%;">{{ __('title') }}</th>
                                            <th style="width: 55%;">{{ __('description') }}</th>
                                            <th style="width: 15%; text-align: right;">{{ __('date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($announcement as $key => $row)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $row->title }}</td>
                                                <td class="full-data">{{ $row->description }}</td>
                                                <td style="text-align: right; white-space: nowrap;">
                                                    {{ $row->created_at->format('d M Y') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
@section('script')
    @if ($boys || $girls)
        <script>
            (function($) {
                'use strict';
                $(function() {
                    Chart.defaults.global.legend.labels.usePointStyle = true;
                    if ($("#gender-ratio-chart").length) {
                        let ctx = document.getElementById('gender-ratio-chart').getContext("2d")
                        let gradientStrokeBlue = ctx.createLinearGradient(0, 0, 0, 181);
                        gradientStrokeBlue.addColorStop(0, 'rgba(54, 215, 232, 1)');
                        gradientStrokeBlue.addColorStop(1, 'rgba(177, 148, 250, 1)');
                        let gradientLegendBlue =
                            'linear-gradient(to right, rgba(54, 215, 232, 1), rgba(177, 148, 250, 1))';

                        let gradientStrokeRed = ctx.createLinearGradient(0, 0, 0, 50);
                        gradientStrokeRed.addColorStop(0, 'rgba(255, 191, 150, 1)');
                        gradientStrokeRed.addColorStop(1, 'rgba(254, 112, 150, 1)');
                        let gradientLegendRed =
                            'linear-gradient(to right, rgba(255, 191, 150, 1), rgba(254, 112, 150, 1))';
                        let trafficChartData = {
                            datasets: [{
                                data: [{{ $boys }}, {{ $girls }}],
                                backgroundColor: [
                                    gradientStrokeBlue,
                                    gradientStrokeRed
                                ],
                                hoverBackgroundColor: [
                                    gradientStrokeBlue,
                                    gradientStrokeRed
                                ],
                                borderColor: [
                                    gradientStrokeBlue,
                                    gradientStrokeRed
                                ],
                                legendColor: [
                                    gradientLegendBlue,
                                    gradientLegendRed
                                ]
                            }],

                            // These labels appear in the legend and in the tooltips when hovering different arcs
                            labels: [
                                "{{ __('boys') }}",
                                "{{ __('girls') }}"
                            ]
                        };
                        let trafficChartOptions = {
                            responsive: true,
                            animation: {
                                animateScale: true,
                                animateRotate: true
                            },
                            legend: false,
                            legendCallback: function(chart) {
                                let text = [];
                                text.push('<ul>');
                                for (let i = 0; i < trafficChartData.datasets[0].data.length; i++) {
                                    text.push('<li><span class="legend-dots" style="background:' +
                                        trafficChartData.datasets[0].legendColor[i] + '"></span>');
                                    if (trafficChartData.labels[i]) {
                                        text.push(trafficChartData.labels[i]);
                                    }
                                    text.push('<span class="float-right">' + trafficChartData.datasets[0]
                                        .data[i] + "%" + '</span>')
                                    text.push('</li>');
                                }
                                text.push('</ul>');
                                return text.join('');
                            }
                        };
                        let trafficChartCanvas = $("#gender-ratio-chart").get(0).getContext("2d");
                        let trafficChart = new Chart(trafficChartCanvas, {
                            type: 'doughnut',
                            data: trafficChartData,
                            options: trafficChartOptions
                        });
                        $("#gender-ratio-chart-legend").html(trafficChart.generateLegend());
                    }
                    if ($("#inline-datepicker").length) {
                        $('#inline-datepicker').datepicker({
                            enableOnReadonly: true,
                            todayHighlight: true,
                        });
                    }
                });
            })(jQuery);
        </script>
    @endif
    @if ($attendance)
        <script>
            var xValues = []; // Class section names
            var yValues = []; // Attendance percentages

            @foreach ($attendance as $data)
                xValues.push(
                    "{{ $data->class_section->class->name ?? '' }} - {{ $data->class_section->section->name ?? '' }} {{ $data->class_section->class->medium->name ?? '' }} {{ $data->class_section->class->streams->name ?? '' }}"
                );
                var totalAttendance = {{ $data->total_attendance }};
                var totalPresent = {{ $data->total_present }};
                var attendancePercentage = (totalPresent / totalAttendance) * 100;
                attendancePercentage = attendancePercentage.toFixed(2); // Round to two decimal places
                yValues.push(attendancePercentage);
            @endforeach

            var ctx = document.getElementById('myChart').getContext('2d');
            var myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: xValues,
                    datasets: [{
                        data: yValues,
                        backgroundColor: [
                            '#fe7096',
                            '#047edf',
                            '#84d9d2',
                            '#da8cff',
                            '#f6e384',
                        ],
                        borderWidth: 1,
                        borderColor: 'transparent', // Set border color to transparent
                        fill: true, // Enable fill to apply gradients
                    }]
                },
                options: {
                    legend: {
                        display: false
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                max: 100 // Adjust the maximum value as needed
                            },
                            scaleLabel: {
                                display: true,
                                labelString: "{{ __('percentage') }} (%)"
                            },
                            gridLines: {
                                display: false // Remove y-axis gridlines
                            },
                        }],
                        xAxes: [{
                            categoryPercentage: 1.0, // Adjust this value to control the bar width
                            barPercentage: 0.3, // Adjust this value to control the gap between bars
                            gridLines: {
                                display: false // Remove x-axis gridlines
                            }
                        }]
                    },
                    title: {
                        display: true,
                        text: "{{ __('Attendance Percentage by Class Section') }}",
                        position: "bottom" // Display title below the graph
                    },
                }
            });
        </script>
    @endif
    @if ($leaves)
        <script>
            function filterLeaves() {
                var filter = document.getElementById('leaveFilter').value;
                var today = new Date().toISOString().split('T')[0];
                var tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                var tomorrowDate = tomorrow.toISOString().split('T')[0];

                var rows = document.querySelectorAll('#leavesTable tbody tr:not(.no-data)');
                var visibleCount = 0;
                let serialNumber = 1;

                // First hide the no data message if it exists
                var noDataRow = document.querySelector('#leavesTable tbody tr.no-data');
                if (noDataRow) {
                    noDataRow.style.display = 'none';
                }

                // Filter the actual data rows
                rows.forEach(function(row) {
                    var rowDate = row.getAttribute('data-date');
                    if ((filter === 'today' && rowDate === today) ||
                        (filter === 'tomorrow' && rowDate === tomorrowDate) ||
                        (filter === 'upcoming' && rowDate > tomorrowDate)) {
                        row.style.display = '';
                        row.cells[0].innerText = serialNumber++;
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // If no rows are visible after filtering, show the no data message
                if (visibleCount === 0) {
                    if (!noDataRow) {
                        var tbody = document.querySelector('#leavesTable tbody');
                        noDataRow = document.createElement('tr');
                        noDataRow.className = 'no-data';
                        noDataRow.innerHTML = '<td colspan="5" class="text-center">{{ __('no_data_found') }}</td>';
                        tbody.appendChild(noDataRow);
                    }
                    noDataRow.style.display = '';
                }
            }

            // Call filterLeaves on page load to apply default filter
            document.addEventListener('DOMContentLoaded', function() {
                filterLeaves();
            });
        </script>
        <script>
            window.circleSvg = "{{ asset(config('global.CIRCLE_SVG')) }}";
        </script>
    @endif
@endsection
