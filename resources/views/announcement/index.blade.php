@extends('layouts.master')

@section('title')
    {{ __('announcement') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('announcement') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('create') . ' ' . __('announcement') }}
                        </h4>
                        <form class="create-form pt-3" action="{{ route('announcement.store') }}" id="formdata"
                            method="POST" novalidate="novalidate">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('title', null, ['required', 'placeholder' => __('title'), 'class' => 'form-control']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('description') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('description', null, [
                                        'rows' => '2',
                                        'placeholder' => __('description'),
                                        'class' => 'form-control',
                                    ]) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('files') }}</label>
                                    <input type="file" name="file[]" class="form-control" multiple />
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-3">
                                    <label>{{ __('assign_to') }} <span class="text-danger">*</span></label>
                                    <select name="set_data" id="set_data" class="form-control select2">
                                        <option value="">{{ __('select') . ' ' . __('assign_to') }}</option>
                                        @if (Auth::user()->hasRole('Teacher'))
                                            <option value="class_section">{{ __('class') . ' ' . __('section') }}</option>
                                        @else
                                            {{-- <option value="class">{{ __('class') }}</option> --}}
                                            <option value="noticeboard">{{ __('noticeboard') }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="form-group col-sm-12 col-md-3 show_class_section_id">
                                    <label>&nbsp;</label>
                                    <select name="class_section_id" id="class_section_id"
                                        class="announcement_class_section_id form-control" style="width:100%;"
                                        tabindex="-1" aria-hidden="true">
                                        <option value="">{{ __('select') . ' ' . __('class_section') }}</option>
                                    </select>
                                    <input type="hidden" name="semester_id" id="semester_id" value="">
                                </div>
                                <div class="form-group col-sm-12 col-md-3 show_class_section_id">
                                    <label>&nbsp;</label>
                                    <select name="get_data[]" id="get_data" class="subject_id form-control"
                                        style="width:100%; display: none"></select>
                                </div>
                            </div>
                            <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list') . ' ' . __('announcement') }}
                        </h4>
                        <div class="row">
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
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                    data-url="{{ url('announcement-list') }}" data-click-to-select="true"
                                    data-side-pagination="server" data-pagination="true"
                                    data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar"
                                    data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                                    data-fixed-right-number="1" data-trim-on-search="false" data-mobile-responsive="true"
                                    data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                                    data-export-types='["txt","excel"]'
                                    data-export-options='{ "fileName": "announcement-list-<?= date('d-m-y') ?>"
                                    ,"ignoreColumn": ["operate"]}'
                                    data-query-params="announcementQueryParams" data-escape="true">
                                    <thead>
                                        <tr>
                                            <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                                {{ __('id') }}</th>
                                            <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}
                                            </th>
                                            <th scope="col" data-field="title" data-sortable="false">
                                                {{ __('title') }}
                                            </th>
                                            <th scope="col" data-field="description" data-sortable="false">
                                                {{ __('description') }}</th>
                                            <th scope="col" data-field="assignto" data-sortable="false">
                                                {{ __('assign_to') }}</th>
                                            <th scope="col" data-field="file" data-sortable="false"
                                                data-formatter="fileFormatter">{{ __('files') }}</th>
                                            <th data-events="announcementEvents" data-escape="false" data-width="150"
                                                scope="col" data-field="operate" data-sortable="false">
                                                {{ __('action') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"> {{ __('edit') . ' ' . __('announcement') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fa fa-close"></i></span>
                    </button>
                </div>
                <form class="pt-3 edit-announcement-form" id="edit-announcement-form"
                    action="{{ url('announcement/update') }}" method="POST" novalidate="novalidate">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-12">
                                <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                {!! Form::text('title', null, [
                                    'required',
                                    'placeholder' => __('title'),
                                    'class' => 'form-control',
                                    'id' => 'title',
                                ]) !!}
                            </div>
                            <div class="form-group col-sm-12 col-md-12">
                                <label>{{ __('description') }}</label>
                                {!! Form::textarea('description', null, [
                                    'rows' => 2,
                                    'placeholder' => __('description'),
                                    'class' => 'form-control',
                                    'id' => 'description',
                                ]) !!}
                            </div>
                            <div class="form-group col-sm-12 col-md-12">
                                <label>{{ __('assign_to') }}</label>
                                <select name="set_data" id="edit_set_data" class="form-control">
                                    <option value="">
                                        {{ __('--Please') . ' ' . __('select') . ' ' . __('assign_to') . '--' }}</option>
                                    @if (Auth::user()->hasRole('Super Admin'))
                                        <option value="noticeboard">{{ __('noticeboard') }}</option>
                                    @else
                                        <option value="class_section">{{ __('class') . ' ' . __('section') }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-12 edit_show_class_section_id">
                                <label>{{ __('class_section') }}</label>
                                <select name="class_section_id" id="edit_class_section_id" class="form-control"
                                    style="width:100%;" tabindex="-1" aria-hidden="true">
                                    <option value="">{{ __('select') . ' ' . __('class_section') }}</option>
                                </select>
                                <input type="hidden" name="semester_id" id="edit_semester_id" value="">
                                <br>
                                <br>
                                <div class="form-group">
                                    <label>{{ __('old_files') }} </label>
                                    <div id="old_files"></div>
                                </div>

                                <div class="form-group">
                                    <label>{{ __('upload_new_files') }} </label>
                                    <input type="file" name="file[]" class="form-control" multiple />
                                </div>
                            </div>
                            <div class="form-group col-sm-12 col-md-12 edit_show_class_section_id">
                                <label>{{ __('subject') }}</label>
                                <select name="get_data" id="edit_get_data" class="form-control" style="width:100%;"
                                    tabindex="-1" aria-hidden="true"></select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                        <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $('.show_class_section_id').hide();

        // Load class sections with semester support
        function loadAnnouncementClassSections(targetSelector, subjectSelector, semesterInputSelector) {
            let session_year_id = '{{ getSettings('session_year')['session_year'] }}';
            $.ajax({
                url: "{{ url('announcement-class-sections') }}",
                type: "GET",
                data: {
                    session_year_id: session_year_id
                },
                success: function(response) {
                    let html = '<option value="">{{ __('select') . ' ' . __('class_section') }}</option>';
                    for (let i = 0; i < response.length; i++) {
                        html += '<option value="' + response[i]['id'] + '" data-class="' + response[i][
                            'class_id'
                        ] + '" data-semester="' + (response[i]['semester_id'] || '') + '">' + response[i][
                            'name'
                        ] + '</option>';
                    }
                    $(targetSelector).html(html);
                    $(subjectSelector).html('');
                    $(semesterInputSelector).val('');
                }
            });
        }

        // Load class sections on page load
        loadAnnouncementClassSections('#class_section_id', '#get_data', '#semester_id');
        loadAnnouncementClassSections('#edit_class_section_id', '#edit_get_data', '#edit_semester_id');

        $('#set_data').on('change', function() {
            data = $(this).val();
            if (data == 'class_section') {
                $('.show_class_section_id').show();
                $('#get_data').show();
            } else {
                $('.show_class_section_id').hide();
                $('#get_data').hide();
            }
            $.ajax({
                url: "{{ url('getAssignData') }}",
                type: "GET",
                data: {
                    data: data
                },
                success: function(response) {
                    html = '';
                    if (data == 'class') {
                        for (let i = 0; i < response.length; i++) {
                            html += '<option value=' + response[i]['id'] + '>' + response[i]['name'] +
                                '</option>';
                        }
                    }
                    $('#get_data').html(html);
                }
            });
        });

        // Create form: class section change -> load subjects with semester
        $('#class_section_id').on('change', function() {
            let class_id = $(this).find(':selected').attr('data-class');
            let semester_id = $(this).find(':selected').data('semester') || '';
            $('#semester_id').val(semester_id);

            if (!$(this).val()) {
                $('#get_data').html('');
                return;
            }

            $.ajax({
                url: "{{ url('getAssignData') }}",
                type: "GET",
                data: {
                    data: 'class_section',
                    class_id: class_id,
                    class_section_id: $('#class_section_id').val(),
                    semester_id: semester_id
                },
                success: function(response) {
                    let html = '';
                    if (response != '' && response.length > 0) {
                        html += '<option value="">' + trans('select') + ' ' + trans('subject') +
                            '</option>';
                        for (let i = 0; i < response.length; i++) {
                            html += '<option value="' + response[i]['subject']['id'] + '">' +
                                response[i]['subject']['name'] + '</option>';
                        }
                    }
                    $('#get_data').html(html);
                    $('#get_data').show();
                }
            });
        });

        $('.edit_show_class_section_id').hide();
        $('#edit_set_data').on('change', function(e, type_id) {
            data = $(this).val();
            if (data == 'class_section') {
                $('.edit_show_class_section_id').show();
            } else {
                $('.edit_show_class_section_id').hide();
            }
            $.ajax({
                url: "{{ url('getAssignData') }}",
                type: "GET",
                data: {
                    data: data
                },
                success: function(response) {
                    html = '';
                    if (data == 'class') {
                        for (let i = 0; i < response.length; i++) {
                            var chk = (response[i]['id'] == type_id) ? 'selected' : '';
                            html += '<option value=' + response[i]['id'] + '' + chk + '>' + response[i][
                                    'name'
                                ] +
                                '</option>';
                        }
                    }
                    $('#edit_get_data').html(html);
                }
            });
        });

        // Edit form: class section change -> load subjects with semester
        $('#edit_class_section_id').on('change', function(e, subjectid) {
            data = $('#edit_set_data').val();
            let class_id = $(this).find(':selected').attr('data-class');
            let semester_id = $(this).find(':selected').data('semester') || '';
            $('#edit_semester_id').val(semester_id);

            $.ajax({
                url: "{{ url('getAssignData') }}",
                type: "GET",
                data: {
                    data: data,
                    class_id: class_id,
                    class_section_id: $('#edit_class_section_id').val(),
                    semester_id: semester_id
                },
                success: function(response) {
                    html = '';
                    if (response != '') {
                        html += '<option value="">' + trans('select') + ' ' + trans('subject') +
                            '</option>';
                        for (let i = 0; i < response.length; i++) {
                            var chk = (response[i]['subject']['id'] == subjectid) ? 'selected' : '';
                            html += '<option value=' + response[i]['subject']['id'] + ' ' + chk + '>' +
                                response[i]['subject']['name'] + '</option>';

                        }
                    }
                    $('#edit_get_data').html(html);
                }
            });
        });
    </script>
@endsection
