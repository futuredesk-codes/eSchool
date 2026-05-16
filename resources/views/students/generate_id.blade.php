@extends('layouts.master')

@section('title')
    {{ __('students') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('students') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list') . ' ' . __('students') }}
                        </h4>
                        <div id="toolbar">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <select name="filter_session_year" id="filter_session_year" data-scope="table"
                                        class="form-control filter_session_year">
                                        @foreach ($session_years as $session_year)
                                            <option value="{{ $session_year->id }}">
                                                {{ $session_year->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <select name="filter_class_section_id" id="filter_class_section_id"
                                        class="form-control">
                                        <option value="">{{ __('select_class_section') }}</option>
                                        @if (!$is_teacher)
                                            @foreach ($class_section as $class)
                                                <option value="{{ $class->id }}">
                                                    {{ $class->class->name .
                                                        ' ' .
                                                        $class->section->name .
                                                        ' ' .
                                                        $class->class->medium->name .
                                                        ' ' .
                                                        ($class->class->streams->name ?? '') }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                    data-url="{{ url('students-list-data') }}" data-click-to-select="true"
                                    data-side-pagination="server" data-pagination="true"
                                    data-page-list="[5, 10, 20, 50, 100, 200 , 500]" data-search="true"
                                    data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                                    data-fixed-columns="true" data-trim-on-search="false" data-mobile-responsive="true"
                                    data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                                    data-export-types='["txt","excel"]'
                                    data-export-options='{ "fileName": "students-list-<?= date('d-m-y') ?>" ,"ignoreColumn":
                                    ["operate"]}' data-query-params="studentDetailsqueryParams"
                                    data-check-on-init="true" data-escape="true">

                                    <thead>
                                        <tr>
                                            <th data-field="state" data-checkbox="true"></th>
                                            <th data-field="id" data-sortable="true" data-visible="false">
                                                {{ __('id') }}</th>
                                            <th data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                            <th data-field="user_id" data-sortable="false" data-visible="false">
                                                {{ __('user_id') }}</th>
                                            <th data-field="admission_no" data-sortable="false"> {{ __('gr_number') }}</th>
                                            <th data-field="class_section_id" data-sortable="false" data-visible="false">
                                                {{ __('class') . ' ' . __('section') . ' ' . __('id') }}</th>
                                            <th data-field="class_section_name" data-sortable="false">
                                                {{ __('class') . ' ' . __('section') }}</th>
                                            <th data-field="stream_name" data-sortable="false">{{ __('stream') }}</th>
                                            <th data-field="roll_number" data-sortable="false">{{ __('roll_no') }}</th>
                                            <th data-field="first_name" data-sortable="false">{{ __('first_name') }}</th>
                                            <th data-field="last_name" data-sortable="false">{{ __('last_name') }}</th>
                                            <th data-field="dob" data-sortable="false">{{ __('dob') }}</th>
                                            <th data-field="image" data-sortable="false" data-formatter="imageFormatter">
                                                {{ __('image') }}</th>
                                            <th data-field="father_first_name" data-sortable="false">
                                                {{ __('father') . ' ' . __('name') }}</th>
                                            <th data-field="father_mobile" data-sortable="false">
                                                {{ __('father') . ' ' . __('mobile') }}</th>
                                            <th data-field="guardian_first_name" data-sortable="false">
                                                {{ __('guardian') . ' ' . __('name') }}</th>
                                            <th data-field="guardian_mobile" data-sortable="false">
                                                {{ __('guardian') . ' ' . __('mobile') }}</th>
                                            <th data-field="current_address">{{ __('address') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                        <div class="form-group col-12">
                            <form action="{{ url('generate-id-card') }}" target="_blank" method="post">
                                @csrf
                                <textarea id="user_id" name="user_id" style="display: none"></textarea>
                                <input type="submit" class="btn btn-theme mt-4" value="{{ __('Generate') }}">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        var $tableList = $('#table_list')
        var selections = []
        var user_list = [];

        @if ($is_teacher)
            $(function() {
                function loadClassSections(sessionYearId) {
                    var $dropdown = $('#filter_class_section_id');
                    $dropdown.html('<option value="">{{ __('select_class_section') }}</option>');

                    if (!sessionYearId) return;

                    $.ajax({
                        url: '{{ url('student-class-sections') }}',
                        type: 'GET',
                        data: {
                            session_year_id: sessionYearId
                        },
                        success: function(response) {
                            var html = '<option value="">{{ __('select_class_section') }}</option>';
                            $.each(response, function(key, value) {
                                html += '<option value="' + value.id + '">' + value.name +
                                    '</option>';
                            });
                            $dropdown.html(html);
                        }
                    });
                }

                $('#filter_session_year').on('change', function() {
                    loadClassSections($(this).val());
                });

                // Load class sections for the initially selected session year
                loadClassSections($('#filter_session_year').val());
            });
        @endif

        function responseHandler(res) {
            $.each(res.rows, function(i, row) {
                row.state = $.inArray(row.id, selections) !== -1
            })
            return res
        }

        $(function() {
            $tableList.on('check.bs.table check-all.bs.table uncheck.bs.table uncheck-all.bs.table',
                function(e, rowsAfter, rowsBefore) {
                    user_list = [];
                    var rows = rowsAfter
                    if (e.type === 'uncheck-all') {
                        rows = rowsBefore
                    }
                    var ids = $.map(!$.isArray(rows) ? [rows] : rows, function(row) {
                        return row.id
                    })
                    var func = $.inArray(e.type, ['check', 'check-all']) > -1 ? 'union' : 'difference'
                    selections = window._[func](selections, ids)
                    selections.forEach(element => {
                        user_list.push(element);
                    });
                    $('textarea#user_id').val(user_list);
                })
        })
    </script>
@endsection
