@extends('layouts.master')

@section('title')
    {{ __('class') }} {{ __('teacher') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="car\`qd-title">
                            {{ __('assign') . ' ' . __('class') . ' ' . __('teacher') }}
                        </h4>
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <label for="filter_session_year" class="form-label text-nowrap">
                                    {{ __('session_year') }}
                                </label>
                                <select name="filter_session_year" id="filter_session_year" data-scope="table"
                                    class="form-control filter_session_year">
                                    @foreach ($session_years as $session_year)
                                        <option value="{{ $session_year->id }}">
                                            {{ $session_year->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="filter_class_id" class="form-label text-nowrap">
                                    {{ __('class') }}
                                </label>
                                <select name="filter_class_id" id="filter_class_id" class="form-control">
                                    <option value="">{{ __('all') }}</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}">
                                            {{ $class->name . ' ' . $class->medium->name . ' ' . ($class->streams->name ?? '') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="filter_semester_id" class="form-label text-nowrap">
                                    {{ __('semester') }}
                                </label>
                                <select name="filter_semester_id" id="filter_semester_id" class="form-control">
                                    <option value="">{{ __('all') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                    data-url="{{ url('class-teacher-list') }}" data-click-to-select="true"
                                    data-side-pagination="server" data-pagination="true"
                                    data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar"
                                    data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                                    data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                                    data-query-params="AssignTeacherQueryParams" data-sort-order="desc"
                                    data-maintain-selected="true" data-export-types='["txt","excel"]'
                                    data-export-options='{ "fileName": "data-list-<?= date(' d-m-y') ?>" }'
                                    data-escape="true">
                                    <thead>
                                        <tr>
                                            <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                                {{ __('id') }}</th>
                                            <th scope="col" data-field="no" data-sortable="false">
                                                {{ __('no.') }}</th>
                                            <th scope="col" data-field="class" data-sortable="false">
                                                {{ __('class') }}</th>
                                            <th scope="col" data-field="stream_name" data-sortable="true">
                                                {{ __('stream') }}</th>
                                            <th scope="col" data-field="section" data-sortable="false">
                                                {{ __('section') }}</th>
                                            <th scope="col" data-field="semester_name" data-sortable="false">
                                                {{ __('semester') }}</th>
                                            <th scope="col" data-field="teacher_id" data-sortable="false"
                                                data-visible="false">
                                                {{ __('Teacher ID') }}</th>
                                            <th scope="col" data-field="teachers" data-sortable="false"
                                                data-formatter="classTeacherFormatter">
                                                {{ __('Class Teacher') }}</th>
                                            <th data-events="actionEvents" data-escape="false" scope="col"
                                                data-field="operate" data-sortable="false">{{ __('action') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                {{ __('edit') . ' ' . __('class') . ' ' . __('teacher') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="edit-class-teacher-form" action="{{ route('class.teacher.store') }}"
                            novalidate="novalidate">
                            @csrf
                            <input type="hidden" name="session_year_id" id="session_year_id_value" value="">
                            <input type="hidden" name="semester_id" id="semester_id_value" value="">
                            <div class="modal-body">
                                <div class="row form-group">
                                    <div class="form-group col-sm-12 col-md-12">
                                        {{-- hidden input to store id --}}
                                        <input type="hidden" name="class_section_id" id="class_section_id_value">

                                        <label>{{ __('class') }} {{ __('section') }} <span
                                                class="text-danger">*</span></label>
                                        <select name="class_section_id_select" id="class_section_id" class="form-control"
                                            disabled>
                                            @foreach ($class_section as $section)
                                                <option value="{{ $section->id }}">
                                                    {{ $section->class->name . ' ' . $section->section->name }}
                                                    {{ $section->class->streams->name ?? ' ' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="form-group col-sm-12 col-md-12">
                                        <label>{{ __('Add New Class Teacher') }} <span
                                                class="text-danger">*</span></label>
                                        <select name="teacher_id" id="teacher_id" class="form-control teacher">


                                        </select>
                                    </div>
                                </div>
                                <div class="form-group" id="hello">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="mb-0"><strong>{{ __('Current Class Teachers') }}</strong></label>
                                    </div>
                                    <div class="class_teachers">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{{ __('close') }}</button>
                                <input class="btn btn-theme" type="submit" value={{ __('submit') }} />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function loadSemesters() {
            let session_year_id = $('#filter_session_year').val();
            let $semesterSelect = $('#filter_semester_id');
            $semesterSelect.html('<option value="">{{ __('all') }}</option>');
            if (!session_year_id) return;

            let semUrl = baseUrl + '/session-year/' + session_year_id + '/semesters';
            ajaxRequest('GET', semUrl, null, null, function(response) {
                var semesters = response.data || [];
                var html = '<option value="">{{ __('all') }}</option>';
                $.each(semesters, function(i, sem) {
                    html += '<option value="' + sem.id + '">' + sem.name + '</option>';
                });
                $semesterSelect.html(html);
            }, null, null, true);
        }

        // Load semesters after the global session-year sync sets the correct value
        $(document).on('session-year-synced', function() {
            loadSemesters();
        });

        $('#filter_session_year').on('change', function() {
            loadSemesters();
        });

        $('#filter_semester_id').on('change', function() {
            $('#table_list').bootstrapTable('refresh');
        });

        window.actionEvents = {
            'click .editdata': function(e, value, row, index) {
                $('#class_section_id').val(row.id);

                //hidden input to store id
                $('#class_section_id_value').val(row.id);

                const session_year_id = $('#filter_session_year').val();
                const semester_id = row.semester_id || '';
                $('#session_year_id_value').val(session_year_id);
                $('#semester_id_value').val(semester_id);

                $.ajax('/get-all-class-teacher/' + row.id + '/' + session_year_id + '?semester_id=' + semester_id, {
                    dataType: 'json',

                    success: function(data, status, xhr) {
                        let html = ''
                        html += '<option value=" ">' + trans('please_select') + '</option>';
                        $.each(data, function(key, value) {

                            html += '<option value="' + value.id + '">' + value.user
                                .first_name + ' ' + value.user.last_name + '</option>';

                        });
                        $('.teacher').html(html);
                    },
                    error: function(jqXhr, textStatus, errorMessage) {
                        $('.teacher').append('Error: ' + errorMessage);
                    }
                });

                if (row.teacher_id == null) {
                    $(function() {
                        let html = '';
                        html += '<label class="form-check label">No Data Found</label>';
                        $('.class_teachers').html(html);
                    });

                } else {
                    $.ajax('/get-class-teacher/' + row.id + "/" + session_year_id + '?semester_id=' + semester_id, {
                        dataType: 'json',

                        success: function(data, status, xhr) {
                            let html = ''
                            let count = 1;
                            $.each(data, function(key, value) {
                                html +=
                                    '<div class="form-check form-check-inline"><label class="form-check-label">' +
                                    count + ". " + value.user.first_name + ' ' + value.user
                                    .last_name + '';
                                html +=
                                    '<button type="button" class="btn btn-inverse-danger btn-icon ml-3 remove-class-teacher-btn" data-class-id="' +
                                    row.id + '" data-teacher-id="' + value.id +
                                    '" data-semester-id="' + semester_id +
                                    '"><i class="fa fa-close"></i></button>';
                                html += '</label></div>';
                                count++;
                            });
                            $('.class_teachers').html(html);
                        },
                        error: function(jqXhr, textStatus, errorMessage) {
                            $('.class_teachers').append('Error: ' + errorMessage);
                        }
                    });
                }
            }
        };

        $(document).on('click', '.remove-class-teacher-btn', function() {
            const classSectionId = $(this).data('class-id');
            const teacherId = $(this).data('teacher-id');
            const semester_id = $(this).data('semester-id');

            Swal.fire({
                title: trans('Are you sure?'),
                text: trans("Remove Class Teacher!"),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: trans('Confirm!'),
            }).then((result) => {
                if (result.isConfirmed) {
                    let url = baseUrl + '/remove-class-teacher/' + classSectionId +
                        '/' + teacherId;
                    if (semester_id) {
                        url += '?semester_id=' + semester_id;
                    }

                    function successCallback(response) {
                        $.toast({
                            text: response.message,
                            icon: 'success',
                            loader: false,
                            position: 'top-right',
                        });
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }

                    function errorCallback(response) {
                        showErrorToast(response.message);
                    }
                    ajaxRequest('POST', url, null, null, successCallback,
                        errorCallback);
                }
            });
        });
    </script>
@endsection
