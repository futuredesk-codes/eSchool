@extends('layouts.master')

@section('title')
    {{ __('attendance') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('attendance') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('create') . ' ' . __('attendance') }}
                        </h4>
                        <form action="{{ route('attendance.store') }}" class="add-attendance" id="formdata">
                            @csrf
                            <div class="row" id="toolbar">
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('class') }} {{ __('section') }} <span class="text-danger">*</span></label>
                                    <select required name="class_section_id" id="timetable_class_section"
                                            class="form-control select2" style="width:100%;" tabindex="-1" aria-hidden="true">
                                        <option value="">{{ __('select') . ' ' . __('class') }}</option>
                                        @foreach ($class_sections as $section)
                                            <option
                                                value="{{ $section->id }}"
                                                data-class="{{ $section->class->id }}"
                                                data-date-start="{{ $classSectionDateLimits[$section->id]['start_date'] ?? '' }}"
                                                data-date-end="{{ $classSectionDateLimits[$section->id]['end_date'] ?? '' }}"
                                            >
                                                {{ $section->class->name }} - {{ $section->section->name }} {{ $section->class->medium->name }} {{$section->class->streams->name ?? ''}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('date') }} <span class="text-danger">*</span></label>
                                    @php
                                        $sessionStartDate = date('d-m-Y', strtotime($currentSessionYear->start_date));
                                        $sessionEndDate = date('d-m-Y', strtotime($currentSessionYear->end_date));
                                    @endphp
                                    {!! Form::text('date', null, ['required', 'placeholder' => __('date'), 'class' => 'datepicker-popup form-control', 'id' => 'date', 'data-date-start-date' => $sessionStartDate, 'data-date-end-date' => $sessionEndDate]) !!}
                                    <span class="input-group-addon input-group-append">
                                </span>
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input type="checkbox" class="form-check-input" name="holiday" id="holiday"
                                                   value="0">Holiday
                                            <i class="input-helper"></i></label>
                                    </div>
                                </div>
                            </div>

                            <div class="show_student_list">
                                <table aria-describedby="mydesc" class='table student_table' id='table_list'
                                       data-toggle="table" data-url="{{ url('student-list') }}" data-click-to-select="true"
                                       data-side-pagination="server" data-pagination="false"
                                       data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                                       data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                                       data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="roll_number"
                                       data-sort-order="asc" data-maintain-selected="true" data-export-types='["txt","excel"]'
                                       data-export-options='{ "fileName": "student-list-<?= date('d-m-y') ?>" ,"ignoreColumn": ["operate"]}'
                                       data-query-params="AttendanceQueryParams" data-escape="true">
                                    <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                            {{ __('id') }}</th>
                                        <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>

                                        <th scope="col" data-escape="false" data-field="student_id" data-sortable="true">
                                            {{ __('student_id') }}</th>
                                        <th scope="col" data-field="admission_no" data-sortable="true">
                                            {{ __('admission_no') }}</th>
                                        <th scope="col" data-field="roll_no" data-sortable="true">{{ __('roll_no') }}
                                        </th>
                                        <th scope="col" data-field="name" data-sortable="false">{{ __('name') }}
                                        </th>
                                        <th scope="col" data-escape="false" data-field="type" data-sortable="false">{{ __('type') }}
                                        </th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                            <input class="btn btn-theme btn_attendance mt-4" id="create-btn" type="submit" value={{ __('submit') }}>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function parseDDMMYYYY(dateString) {
            if (!dateString) {
                return null;
            }

            const parts = dateString.split('-');
            if (parts.length !== 3) {
                return null;
            }

            return new Date(parts[2], parts[1] - 1, parts[0]);
        }

        function applyDateRangeForSelectedClass() {
            const $selectedOption = $('#timetable_class_section option:selected');
            const defaultStartDate = @json($sessionStartDate);
            const defaultEndDate = @json($sessionEndDate);

            const startDate = $selectedOption.data('date-start') || defaultStartDate;
            const endDate = $selectedOption.data('date-end') || defaultEndDate;
            const $dateInput = $('#date');

            $dateInput.attr('data-date-start-date', startDate);
            $dateInput.attr('data-date-end-date', endDate);

            if ($dateInput.data('datepicker')) {
                $dateInput.datepicker('setStartDate', startDate);
                $dateInput.datepicker('setEndDate', endDate);
            }

            const selectedDate = parseDDMMYYYY($dateInput.val());
            const minDate = parseDDMMYYYY(startDate);
            const maxDate = parseDDMMYYYY(endDate);

            if (selectedDate && minDate && maxDate && (selectedDate < minDate || selectedDate > maxDate)) {
                $dateInput.val('');
            }
        }

        $('#date').on('input change', function () {
            $('.student_table').bootstrapTable('refresh');
        });

        $('.btn_attendance').hide();
        function set_data(){
            $(document).ready(function()
            {
                student_class=$('#timetable_class_section').val();
                session_year=$('#date').val();

                if(student_class!='' && date!='' )
                {
                    $('.btn_attendance').show();
                }
                else{
                    $('.btn_attendance').hide();
                }
            });
        }
        $('#timetable_class_section,#date').on('change', function() {
            set_data();
        });

        $('#timetable_class_section').on('change', function () {
            applyDateRangeForSelectedClass();
        });

        $(document).ready(function () {
            applyDateRangeForSelectedClass();
        });
    </script>

    <script>
        $('input[name="holiday"]').click(function () {
            class_section_id = $('#timetable_class_section').val();
            date = $('#date').val();
            checkBox = document.getElementById('holiday');
            if (class_section_id != '' && date != '') {
                Swal.fire({
                    title: "{{ __('are_you_sure') }}",
                    text: "",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('yes') }}"
                }).then((result) => {
                    if (checkBox.checked) {
                        if (result.isConfirmed == true) {
                            $("#holiday").val(3);
                            $('input[name="holiday"]').prop('checked', true);
                            $('.type').prop('required', false);
                        } else {
                            checkBox.checked = false;
                        }
                    } else {
                        if (result.isConfirmed == true) {
                            $("#holiday").val(0);
                            $('.type').prop('required', true);
                            return true;
                        } else {
                            checkBox.checked = true;
                        }

                    }
                })
            } else {
                Swal.fire({
                    title: "{{ __('select class & date') }}",
                    text: "",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('yes') }}"
                }).then((result) => {
                    checkBox.checked = false;
                })
            }
        });
    </script>
    <script>
        $('#timetable_class_section,#date').on('change , input', function () {
            date = $('#date').val();
            class_section_id = $('#timetable_class_section').val();
            $.ajax({
                url: "{{ url('getAttendanceData') }}",
                type: "GET",
                data: {
                    date: date,
                    class_section_id: class_section_id
                },
                success: function (response) {
                    if (response == 3) {
                        $('input[name="holiday"]').attr('checked', true);
                        $("#holiday").val(3);
                        $('.type').prop('required', false);
                    } else {
                        $('input[name="holiday"]').attr('checked', false);
                        $("#holiday").val(0);
                        $('.type').prop('required', true);
                    }
                }
            });
        });
    </script>
@endsection
