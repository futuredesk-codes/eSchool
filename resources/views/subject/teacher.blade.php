@extends('layouts.master')

@section('title')
    {{ __('subject') . ' ' . __('teacher') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('subject') . ' ' . __('teacher') }}
            </h3>
        </div>

        <div class="row">
            @can('subject-teacher-create')
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">
                                {{ __('assign') . ' ' . __('subject') . ' ' . __('teacher') }}
                            </h4>
                            <form class="assign_subject_teacher pt-3" action="{{ url('subject-teachers') }}" method="POST"
                                novalidate="novalidate">
                                @csrf
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label for="session_year_id" class="form-label text-nowrap">
                                            {{ __('session_year') }} <span class="text-danger">*</span>
                                        </label>
                                        <select name="session_year_id" id="session_year_id" data-scope="table"
                                            class="form-control session_year_id">
                                            @foreach ($session_years as $session_year)
                                                <option value="{{ $session_year->id }}">
                                                    {{ $session_year->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('class') }} {{ __('section') }} <span
                                                class="text-danger">*</span></label>
                                        <input type="hidden" name="semester_id" id="semester_id">
                                        <select name="class_section_id" id="class_section_id"
                                            class="class_section_id form-control" style="width:100%;" tabindex="-1"
                                            aria-hidden="true">
                                            <option value="">{{ __('select') }}</option>
                                            @foreach ($class_section as $section)
                                                <option value="{{ $section->id }}" data-class="{{ $section->class->id }}">
                                                    {{ $section->class->name . ' ' . $section->section->name . ' - ' . $section->class->medium->name . '  ' . ($section->class->streams->name ?? '') }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('subject') }} <span class="text-danger">*</span></label>
                                        <select name="subject_id" id="subject_id" class="subject_id form-control"
                                            style="width:100%;" tabindex="-1" aria-hidden="true">
                                            <option value="">{{ __('select') }}</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('teacher') }} <span class="text-danger">*</span></label>
                                        <select multiple name="teacher_id[]" id="teacher_id"
                                            class="form-control js-example-basic-single select2-hidden-accessible"
                                            style="width:100%;" tabindex="-1" aria-hidden="true">
                                        </select>
                                    </div>
                                </div>

                                {{-- <div class="row"></div> --}}
                                <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            @can('subject-teacher-list')
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">
                                {{ __('list') . ' ' . __('subject') . ' ' . __('teacher') }}
                            </h4>
                            <div class="row">
                                <div id="toolbar" class="row">
                                    <div class="form-group col-sm-12 col-md-6 col-lg">
                                        <label for="filter_session_year" class="form-label">{{ __('session_year') }}</label>
                                        <select name="filter_session_year" id="filter_session_year" data-scope="table"
                                            class="form-control filter_session_year">
                                            @foreach ($session_years as $session_year)
                                                <option value="{{ $session_year->id }}">
                                                    {{ $session_year->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-sm-12 col-md-6 col-lg">
                                        <label for="filter_class_section_id" class="form-label">{{ __('class') }}
                                            {{ __('section') }}</label>
                                        <select name="filter_class_section_id" id="filter_class_section_id"
                                            class="form-control">
                                            <option value="">{{ __('select_class_section') }}</option>
                                            @foreach ($class_section as $class)
                                                <option value="{{ $class->id }}" data-class="{{ $class->class->id }}">
                                                    {{ $class->class->name . ' ' . $class->section->name . ' ' . $class->class->medium->name . ' ' . ($class->class->streams->name ?? ' ') }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-sm-12 col-md-6 col-lg">
                                        <label for="filter_teacher_id" class="form-label">{{ __('teacher') }}</label>
                                        <select name="filter_teacher_id" id="filter_teacher_id" class="form-control">
                                            <option value="">{{ __('select_teacher') }}</option>
                                            @foreach ($teachers as $teacher)
                                                <option value="{{ $teacher->id }}">
                                                    {{ $teacher->user->first_name . ' ' . $teacher->user->last_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-sm-12 col-md-6 col-lg">
                                        <label for="filter_subject_id" class="form-label">{{ __('subject') }}</label>
                                        <select name="filter_subject_id" id="filter_subject_id" class="form-control">
                                            <option value="">{{ __('select_subject') }}</option>
                                            @foreach ($subjects as $subject)
                                                <option value="{{ $subject->id }}">
                                                    {{ $subject->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                        data-url="{{ url('subject-teachers-list') }}" data-click-to-select="true"
                                        data-side-pagination="server" data-pagination="true"
                                        data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar"
                                        data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                                        data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                                        data-sort-order="desc" data-maintain-selected="true"
                                        data-export-types='["txt","excel"]'
                                        data-export-options='{ "fileName": "session-year-list-<?= date(' d-m-y') ?>
                                        ","ignoreColumn": ["operate"]}'
                                        data-query-params="AssignSubjectTeacherQueryParams" data-escape="true">
                                        <thead>
                                            <tr>
                                                <th scope="col" data-field="id" data-sortable="true"
                                                    data-visible="false">
                                                    {{ __('id') }}</th>
                                                <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}
                                                </th>
                                                <th scope="col" data-field="class_section_id" data-sortable="false"
                                                    data-visible="false">{{ __('class_section_id') }}</th>
                                                <th scope="col" data-field="class_section_name" data-sortable="false">
                                                    {{ __('class') . ' ' . __('section') . ' ' . __('name') }}</th>
                                                <th scope="col" data-field="stream_id" data-sortable="true"
                                                    data-visible="false">{{ __('stream_id') }}</th>
                                                <th scope="col" data-field="stream_name" data-sortable="false">
                                                    {{ __('stream') . ' ' . __('name') }}</th>
                                                <th scope="col" data-field="semester_id" data-sortable="true"
                                                    data-visible="false">{{ __('semester_id') }}</th>
                                                <th scope="col" data-field="semester_name" data-sortable="false">
                                                    {{ __('semester') }}</th>
                                                <th scope="col" data-field="subject_id" data-sortable="true"
                                                    data-visible="false">{{ __('subject_id') }}</th>
                                                <th scope="col" data-field="subject_name" data-sortable="false">
                                                    {{ __('subject') . ' ' . __('name') }}</th>
                                                <th scope="col" data-field="teacher_id" data-sortable="true"
                                                    data-visible="false">{{ __('teacher_id') }}</th>
                                                <th scope="col" data-field="teacher_name" data-sortable="false">
                                                    {{ __('teacher') . ' ' . __('name') }}</th>
                                                @canany(['subject-teacher-edit', 'subject-teacher-delete'])
                                                    <th data-escape="false" data-events="actionEvents" scope="col"
                                                        data-field="operate" data-sortable="false">{{ __('action') }}</th>
                                                @endcanany
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </div>


    <div class="modal fade" id="editModal" data-backdrop="static" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">
                        {{ __('edit') . ' ' . __('subject') . ' ' . __('teacher') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fa fa-close"></i></span>
                    </button>
                </div>
                <form id="formdata" class="editform" action="{{ url('subject-teachers') }}" novalidate="novalidate">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <input type="hidden" name="id" id="id">
                        <input type="hidden" name="class_section_id" id="edit_class_section_id">
                        <input type="hidden" name="subject_id" id="edit_subject_id">
                        <input type="hidden" name="session_year_id" id="edit_session_year_id">
                        <input type="hidden" name="semester_id" id="edit_semester_id">
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-12">
                                <label>{{ __('class') }} {{ __('section') }}</label>
                                <input type="text" id="edit_class_section_name" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-12">
                                <label>{{ __('subject') }}</label>
                                <input type="text" id="edit_subject_name" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-12">
                                <label>{{ __('teacher') }} <span class="text-danger">*</span></label>
                                <select name="teacher_id" id="edit_teacher_id" class="form-control select2"
                                    style="width:100%;">
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"data-dismiss="modal">{{ __('close') }}</button>
                        <input class="btn btn-theme" type="submit" value={{ __('submit') }} />
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        window.actionEvents = {
            'click .editdata': function(e, value, row, index) {
                $('#id').val(row.id);
                $('#edit_id').val(row.id);
                $('#edit_class_section_id').val(row.class_section_id);
                $('#edit_subject_id').val(row.subject_id);
                $('#edit_session_year_id').val($('#filter_session_year').val());
                $('#edit_semester_id').val(row.semester_id || '');
                $('#edit_class_section_name').val(row.class_section_name + (row.stream_name && row.stream_name !== '-' ? ' ' + row.stream_name : '') + (row.semester_name && row.semester_name !== '-' ? ' - ' + row.semester_name : ''));
                $('#edit_subject_name').val(row.subject_name);

                // Load available teachers for this class_section + subject
                let url = baseUrl + '/teacher-by-class-subject';
                let data = {
                    edit_id: row.id,
                    class_section_id: row.class_section_id,
                    subject_id: row.subject_id,
                };
                ajaxRequest('GET', url, data, null, function(response) {
                    let html = '';
                    if (response.length > 0) {
                        $.each(response, function(key, value) {
                            html += '<option value="' + value.id + '">' + value.user.first_name + ' ' + value.user.last_name + '</option>';
                        });
                    } else {
                        html = "<option value=''>" + trans('no_data_found') + "</option>";
                    }
                    $('#edit_teacher_id').html(html);
                    $('#edit_teacher_id').val(row.teacher_id).trigger('change');
                }, null, null, true);
            }
        };

        $(document).ready(function() {
            $('#filter_session_year').on('change', function() {
                $('#table_list').bootstrapTable('refresh');
            });
        });
    </script>
@endsection
