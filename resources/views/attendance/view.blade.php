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
                        {{ __('view') . ' ' . __('attendance') }}
                    </h4>
                    <div class="row mb-3 align-items-end">
                        <div class="col-sm-12 col-md-2">
                            <div class="form-group">
                                <label class="d-none d-md-block">{{ __('session_year') }}</label>
                                <select name="filter_session_year" id="filter_session_year" data-scope="table"
                                    class="form-control filter_session_year">
                                    @foreach ($session_years as $session_year)
                                    <option value="{{ $session_year->id }}"
                                        data-start-date="{{ date('d-m-Y', strtotime($session_year->start_date)) }}"
                                        data-end-date="{{ date('d-m-Y', strtotime($session_year->end_date)) }}"
                                        {{ $session_year->id == $currentSessionYearId ? 'selected' : '' }}>
                                        {{ $session_year->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-2">
                            <div class="form-group">
                                <label class="d-none d-md-block">{{ __('class') }} {{ __('section') }}</label>
                                <select required name="class_section_id" id="timetable_class_section"
                                    class="form-control select2" style="width:100%;">
                                    <option value="">{{ __('select') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-2">
                            <div class="form-group">
                                <label class="d-none d-md-block">{{ __('date') }}</label>
                                @php
                                    $currentSY = $session_years->firstWhere('id', $currentSessionYearId);
                                    $viewStartDate = date('d-m-Y', strtotime($currentSY->start_date));
                                    $viewEndDate = date('d-m-Y', strtotime($currentSY->end_date));
                                @endphp
                                {!! Form::text('date', null, [
                                'required',
                                'placeholder' => __('date'),
                                'class' => 'datepicker-popup form-control',
                                'id' => 'date',
                                'data-date-start-date' => $viewStartDate,
                                'data-date-end-date' => $viewEndDate,
                                ]) !!}
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-2">
                            <div class="form-group">
                                <label class="d-none d-md-block">{{ __('type') }}</label>
                                <select required name="attendance_type" id="attendance_type"
                                    class="form-control select2" style="width:100%;">
                                    <option value="">{{ __('select') }}</option>
                                    <option value="1">{{ __('present') }}</option>
                                    <option value="0">{{ __('absent') }}</option>
                                    <option value="3">{{ __('holiday') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="show_attendance_student_list">
                        <table aria-describedby="mydesc" class='table student_table' id='table_list' data-toggle="table"
                            data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true"
                            data-page-list="[5, 10, 20, 50, 100, 200,All]" data-search="true" data-toolbar="#toolbar"
                            data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                            data-mobile-responsive="true" data-sort-name="id" data-sort-order="asc"
                            data-maintain-selected="true" data-export-types='["txt","excel"]' data-show-export="true"
                            data-export-options='{ "fileName": "view-attendance-list-<?= date('d-m-y') ?>"
                                ,"ignoreColumn": ["operate"]}'
                            data-query-params="queryParams" data-escape="true">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                        {{ __('id') }}
                                    </th>
                                    <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                    <th scope="col" data-field="user_id" data-sortable="true" data-visible="false">
                                        {{ __('user_id') }}
                                    </th>
                                    <th scope="col" data-field="student_id" data-sortable="true"
                                        data-visible="false">{{ __('student_id') }}</th>
                                    <th scope="col" data-field="admission_no" data-sortable="true">
                                        {{ __('admission_no') }}
                                    </th>
                                    <th scope="col" data-field="roll_no" data-sortable="true">{{ __('roll_no') }}
                                    </th>
                                    <th scope="col" data-field="name" data-sortable="false">{{ __('name') }}
                                    </th>
                                    <th scope="col" data-escape="false" data-field="type" data-sortable="false">
                                        {{ __('type') }}
                                    </th>
                                    {{-- <th scope="col" data-field="note" data-sortable="false">{{__('note')}}</th> --}}
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    function queryParams(p) {
        return {
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search,
            'session_year_id': $('#filter_session_year').val(),
            'class_section_id': $('#timetable_class_section').val(),
            'date': $('#date').val(),
            'attendance_type': $('#attendance_type').val(),
        };
    }
</script>

<script>
    var tableInitialized = false;

    function setDatePickerDisabled(disabled) {
        $('#date').prop('disabled', disabled);
    }

    function getSessionYearDateBounds() {
        var selectedSession = $('#filter_session_year').find('option:selected');
        return {
            startDate: selectedSession.data('start-date'),
            endDate: selectedSession.data('end-date')
        };
    }

    function setDatePickerBounds(startDate, endDate) {
        var $datepicker = $('#date');
        $datepicker.attr('data-date-start-date', startDate || '');
        $datepicker.attr('data-date-end-date', endDate || '');
        $datepicker.datepicker('setStartDate', startDate);
        $datepicker.datepicker('setEndDate', endDate);
        if (startDate) {
            $datepicker.datepicker('update', startDate);
        }
    }

    function applyDateBoundsByClassSection() {
        var selectedClassSection = $('#timetable_class_section').find('option:selected');
        var semesterStartDate = selectedClassSection.attr('data-semester-start-date');
        var semesterEndDate = selectedClassSection.attr('data-semester-end-date');
        var sessionBounds = getSessionYearDateBounds();

        if (semesterStartDate && semesterEndDate) {
            setDatePickerBounds(semesterStartDate, semesterEndDate);
        } else {
            setDatePickerBounds(sessionBounds.startDate, sessionBounds.endDate);
        }

        $('#date').val('');
    }

    function loadAttendanceClassSectionsWithSemesters() {
        var session_year_id = $('#filter_session_year').val();
        setDatePickerDisabled(true);

        if (!session_year_id) {
            $('#timetable_class_section').html('<option value="">{{ __("select") }}</option>').trigger('change.select2');
            setDatePickerDisabled(false);
            return;
        }

        $.ajax({
            url: "{{ url('get-class-sections-with-semesters') }}",
            type: "GET",
            data: {
                session_year_id: session_year_id,
                scope: 'teacher'
            },
            success: function(response) {
                var html = '<option value="">{{ __("select") }}</option>';

                for (var i = 0; i < response.length; i++) {
                    var label = response[i].label ? response[i].label : '';

                    if (!label) {
                        label = response[i].name || '';
                    }

                    html += '<option value="' + response[i].class_section_id + '"' +
                        ' data-class="' + response[i].class_id + '"' +
                        ' data-semester="' + (response[i].semester_id || '') + '"' +
                        ' data-semester-start-date="' + (response[i].semester_start_date || '') + '"' +
                        ' data-semester-end-date="' + (response[i].semester_end_date || '') + '"' +
                        '>' + label + '</option>';
                }

                $('#timetable_class_section').html(html).val('').trigger('change.select2');
                applyDateBoundsByClassSection();
            },
            complete: function() {
                setDatePickerDisabled(false);
            }
        });
    }

    function initializeOrRefreshTable() {
        // Only call the API if all required fields are selected
        var classSectionId = $('#timetable_class_section').val();
        var date = $('#date').val();
        var sessionYearId = $('#filter_session_year').val();

        if (classSectionId && date && sessionYearId) {
            if (!tableInitialized) {
                // Initialize the table with the data-url for the first time
                $('.student_table').bootstrapTable('refreshOptions', {
                    url: "{{ url('student-attendance-list') }}"
                });
                tableInitialized = true;
            } else {
                // Refresh the table for subsequent calls
                $('.student_table').bootstrapTable('refresh');
            }
        }
    }

    $('#date,#attendance_type,#timetable_class_section,#filter_session_year').on('input change', function() {
        initializeOrRefreshTable();
    });

    $('#timetable_class_section').on('change', function() {
        applyDateBoundsByClassSection();
    });

    // Update date picker bounds when session year changes
    $('#filter_session_year').on('change', function() {
        var sessionBounds = getSessionYearDateBounds();
        setDatePickerBounds(sessionBounds.startDate, sessionBounds.endDate);
        $('#date').val('');

        loadAttendanceClassSectionsWithSemesters();
    });

    $(document).ready(function() {
        var sessionBounds = getSessionYearDateBounds();
        setDatePickerBounds(sessionBounds.startDate, sessionBounds.endDate);
        loadAttendanceClassSectionsWithSemesters();
    });
</script>
@endsection
