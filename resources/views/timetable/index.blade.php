@extends('layouts.master')

@section('title')
    {{ __('timetable') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('create') . ' ' . __('timetable') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-3">
                                <label for="session_year" class="form-label text-nowrap">
                                    {{ __('session_year') }}
                                </label>
                                <select name="session_year" id="session_year" class="form-control session_year">
                                    <option value="">{{ __('select') }}</option>
                                    @foreach ($session_years as $session_year)
                                        <option value="{{ $session_year->id }}">
                                            {{ $session_year->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="select_error text-danger d-none">This field is required.</div>
                            </div>

                            <div class="form-group col-sm-12 col-md-3">
                                <label class="form-label">{{ __('class') }} {{ __('section') }}</label>
                                <select required name="class_section_id" id="timetable_class_section" class="form-control"
                                    aria-hidden="true">
                                    <option value="">{{ __('select') }}</option>
                                </select>
                                <div class="select_error text-danger d-none">This field is required.</div>
                                <input type="hidden" name="active_tab" id="active_tab">
                            </div>
                        </div>
                        <div id="timetable-div" class="d-none">
                            <ul class="nav nav-tabs timetable_nav " role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="monday-tab" data-id="monday" data-toggle="tab"
                                        href="#monday" role="tab" aria-controls="monday"
                                        aria-selected="false">{{ __('Monday') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tuesday-tab" data-id="tuesday" data-toggle="tab" href="#tuesday"
                                        role="tab" aria-controls="tuesday"
                                        aria-selected="false">{{ __('Tuesday') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="wednesday-tab" data-id="wednesday" data-toggle="tab"
                                        href="#wednesday" role="tab" aria-controls="wednesday"
                                        aria-selected="false">{{ __('Wednesday') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="thursday-tab" data-id="thursday" data-toggle="tab"
                                        href="#thursday" role="tab" aria-controls="thursday"
                                        aria-selected="false">{{ __('Thursday') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="friday-tab" data-id="friday" data-toggle="tab" href="#friday"
                                        role="tab" aria-controls="friday" aria-selected="false">{{ __('Friday') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="saturday-tab" data-id="saturday" data-toggle="tab"
                                        href="#saturday" role="tab" aria-controls="saturday"
                                        aria-selected="false">{{ __('Saturday') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="sunday-tab" data-id="sunday" data-toggle="tab" href="#sunday"
                                        role="tab" aria-controls="sunday" aria-selected="false">{{ __('Sunday') }}</a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade active show" id="monday" role="tabpanel"
                                    aria-labelledby="monday-tab">
                                    <div class="media">
                                        <div class="media-body">
                                            @include('timetable.tab_title')
                                            <div data-repeater-list="monday_group" class="set_monday">
                                                <div data-repeater-item class="row mb-2 monday_count">
                                                    @include('timetable.addmore')
                                                </div>
                                            </div>
                                            @include('timetable.tab_footer')
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tuesday" role="tabpanel" aria-labelledby="tuesday-tab">
                                    <div class="media">
                                        <div class="media-body">
                                            @include('timetable.tab_title')
                                            <div data-repeater-list="tuesday_group" class="set_tuesday">
                                                <div data-repeater-item class="row mb-2 tuesday_count">
                                                    @include('timetable.addmore')
                                                </div>
                                            </div>
                                            @include('timetable.tab_footer')
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="wednesday" role="tabpanel"
                                    aria-labelledby="wednesday-tab">
                                    <div class="media">
                                        <div class="media-body">
                                            @include('timetable.tab_title')
                                            <div data-repeater-list="wednesday_group" class="set_wednesday">
                                                <div data-repeater-item class="row mb-2 wednesday_count">
                                                    @include('timetable.addmore')
                                                </div>
                                            </div>
                                            @include('timetable.tab_footer')
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="thursday" role="tabpanel"
                                    aria-labelledby="thursday-tab">
                                    <div class="media">
                                        <div class="media-body">
                                            @include('timetable.tab_title')
                                            <div data-repeater-list="thursday_group" class="set_thursday">
                                                <div data-repeater-item class="row mb-2 thursday_count">
                                                    @include('timetable.addmore')
                                                </div>
                                            </div>
                                            @include('timetable.tab_footer')
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="friday" role="tabpanel" aria-labelledby="friday-tab">
                                    <div class="media">
                                        <div class="media-body">
                                            @include('timetable.tab_title')
                                            <div data-repeater-list="friday_group" class="set_friday">
                                                <div data-repeater-item class="row mb-2 friday_count">
                                                    @include('timetable.addmore')
                                                </div>
                                            </div>
                                            @include('timetable.tab_footer')
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="saturday" role="tabpanel"
                                    aria-labelledby="saturday-tab">
                                    <div class="media">
                                        <div class="media-body">
                                            @include('timetable.tab_title')
                                            <div data-repeater-list="saturday_group" class="set_saturday">
                                                <div data-repeater-item class="row mb-2 saturday_count">
                                                    @include('timetable.addmore')
                                                </div>
                                            </div>
                                            @include('timetable.tab_footer')
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="sunday" role="tabpanel" aria-labelledby="sunday-tab">
                                    <div class="media">
                                        <div class="media-body">
                                            @include('timetable.tab_title')
                                            <div data-repeater-list="sunday_group" class="set_sunday">
                                                <div data-repeater-item class="row mb-2 sunday_count">
                                                    @include('timetable.addmore')
                                                </div>
                                            </div>
                                            @include('timetable.tab_footer')
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $('#timetable_class_section').on('change', function(e) {
            var tab = $('#active_tab').val();
            clearTimetableRows(tab);
            set_subject();
            checktimetable(tab);
        });

        $('#session_year').on('change', function() {
            var session_year_id = $(this).val();
            var tab = $('#active_tab').val();
            clearTimetableRows(tab);

            // update hidden input in active tab form
            $('#' + tab + ' input[name="session_year_id"]').val(session_year_id);

            loadClassSectionsWithSemesters();
        });

        function loadClassSectionsWithSemesters() {
            var session_year_id = $('#session_year').val();

            if (!session_year_id) {
                $('#timetable_class_section').html('<option value="">' + trans('select') + '</option>');
                return;
            }

            $.ajax({
                url: "{{ url('get-class-sections-with-semesters') }}",
                type: "GET",
                data: {
                    session_year_id: session_year_id
                },
                success: function(response) {
                    var html = '<option value="">' + trans('select') + '</option>';
                    for (var i = 0; i < response.length; i++) {
                        html += '<option value="' + response[i].class_section_id + '"' +
                            ' data-class="' + response[i].class_id + '"' +
                            ' data-semester="' + (response[i].semester_id || '') + '"' +
                            '>' + response[i].label + '</option>';
                    }
                    $('#timetable_class_section').html(html);
                }
            });
        }


        function set_subject() {
            $('.select_error').addClass('d-none');

            if (
                $('#timetable_class_section').val() !== '' &&
                $('#session_year').val() !== ''
            ) {
                $('.addmore').removeClass('d-none');
            } else {
                $('.addmore').addClass('d-none');
                return;
            }

            var tab = $('#active_tab').val();

            $('#' + tab + ' input[name="class_section_id"]').val($('#timetable_class_section').val());
            $('#' + tab + ' input[name="session_year_id"]').val($('#session_year').val());
            $('#' + tab + ' input[name="semester_id"]').val($('#timetable_class_section').find(':selected').attr(
                'data-semester') || '');

            var class_id = $('#timetable_class_section').find(':selected').attr('data-class');
            var session_year_id = $('#session_year').val();
            var semester_id = $('#timetable_class_section').find(':selected').attr('data-semester') || '';
            $.ajax({
                url: "{{ url('get-subject-by-class-section') }}",
                type: "GET",
                data: {
                    class_id: class_id,
                    session_year_id: session_year_id,
                    semester_id: semester_id
                },
                success: function(response) {
                    var html = '';
                    if (response != '') {
                        html += '<option value="">' + trans('select') + ' ' + trans('subject') + '</option>';
                        for (let i = 0; i < response.length; i++) {
                            html += '<option value=' + response[i]['subject']['id'] + '>' + response[i][
                                'subject'
                            ]['name'] + ' - ' + response[i]['subject']['type'] + '</option>';
                        }
                    } else {
                        html += '<option value="">' + trans('no_data_found') + '</option>';
                    }
                    $('.set_subject_id').html(html)
                }

            });
        }

        $(document).ready(function() {
            tab = $('.timetable_nav').find('li a.active').attr('data-id');
            total_row = $('.' + tab + '_count').length;

            $('#' + tab + ' input[name="day"]').val(tab);
            $('#' + tab + ' input[name="class_section_id"]').val($('#timetable_class_section').val());
            $('#' + tab + ' input[name="session_year_id"]').val($('#session_year').val());
            $('#' + tab + ' input[name="semester_id"]').val($('#timetable_class_section').find(':selected').attr(
                'data-semester') || '');

            $('#active_tab').val(tab);

            get_total();
            set_teacher_data();
        });


        // tab change data
        $('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
            tab = $(e.target).attr("data-id");

            total_row = $('.' + tab + '_count').length;

            $('#' + tab + ' input[name="day"]').val(tab);
            $('#' + tab + ' input[name="class_section_id"]').val($('#timetable_class_section').val());
            $('#' + tab + ' input[name="session_year_id"]').val($('#session_year').val());
            $('#' + tab + ' input[name="semester_id"]').val($('#timetable_class_section').find(':selected').attr(
                'data-semester') || '');

            $('#active_tab').val(tab);

            get_total();
            set_teacher_data();
            checktimetable(tab);
        });


        function get_total() {
            $('.addmore').on('click', function() {
                total_row = $('.' + tab + '_count').length;
                var html = '';
                html += '';
                data = (total_row - 1);
                $('select[name="' + tab + '_group[' + data + '][subject_id]"]').html($('select[name="' + tab +
                    '_group[0][subject_id]"]').html());
                set_teacher_data();

            });
            $('.row_remove').on('click', function() {
                total_row = $('.' + tab + '_count').length;
                set_teacher_data();
            });
        }

        function set_teacher_data() {
            for (let k = 0; k < total_row; k++) {
                $('select[name="' + tab + '_group[' + k + '][subject_id]"]').on('change', function(e) {
                    var subject_id = $(this).val();
                    var class_section_id = $('#timetable_class_section').val();
                    var session_year_id = $('#session_year').val();

                    $.ajax({
                        url: "{{ url('getteacherbysubject') }}",
                        type: "GET",
                        data: {
                            subject_id: subject_id,
                            class_section_id: class_section_id,
                            session_year_id: session_year_id
                        },
                        success: function(response) {
                            var html = '';
                            if (response != '') {
                                html += '<option value="">Select Teacher</option>';
                                for (let i = 0; i < response.length; i++) {
                                    html += '<option data-id=' + response[i]['id'] + ' value=' +
                                        response[i]['teacher_id'] + '>' + response[i]['teacher']['user']
                                        ['first_name'] + ' ' + response[i]['teacher']['user'][
                                            'last_name'
                                        ] + '</option>';
                                }

                            } else {
                                html += '<option value="">No Data</option>';
                            }
                            $('select[name="' + tab + '_group[' + k + '][teacher_id]"]').html(html);
                        }
                    });
                });
            }
        }

        function checktimetable(day_name) {
            var day = '';
            if (day_name == 'monday') {
                day = 1;
            } else if (day_name == 'tuesday') {
                day = 2;
            } else if (day_name == 'wednesday') {
                day = 3;
            } else if (day_name == 'thursday') {
                day = 4;
            } else if (day_name == 'friday') {
                day = 5;
            } else if (day_name == 'saturday') {
                day = 6;
            } else if (day_name == 'sunday') {
                day = 7;
            }
            var class_section_id = $('#timetable_class_section').val();
            var session_year_id = $('#session_year').val();
            var semester_id = $('#timetable_class_section').find(':selected').attr('data-semester') || '';

            $.ajax({
                url: "{{ url('checkTimetable') }}",
                type: "GET",
                data: {
                    class_section_id: class_section_id,
                    session_year_id: session_year_id,
                    semester_id: semester_id,
                    day: day
                },
                success: function(response) {
                    var html = '';
                    if (response != '') {
                        set_subject();
                        for (let i = 0; i < response.length; i++) {

                            let liveClassUrl = response[i]['live_class_url'] ? response[i]['live_class_url'] :
                                '';
                            let linkName = response[i]['link_name'] ? response[i]['link_name'] : '';

                            html += '<div data-repeater-item="" class="row mb-2 ' + day_name + '_count" id="' +
                                day_name + '_count[' + i + ']">' +
                                '<input required hidden type="text" value="' + response[i]['id'] + '" name="' +
                                day_name + '_group[' + i + '][id]" id="' + day_name + '_id[' + i +
                                ']" class="form-control" placeholder="Id">' +
                                '<div class="input-group col-sm-12 col-md-2">' +
                                '<select required name="' + day_name + '_group[' + i +
                                '][subject_id]" id="subject" class="form-control set_subject_id ' + day_name +
                                '_group[' + i +
                                '][subject_id]" style="width:100%;" tabindex="-1" aria-hidden="true"></select>' +
                                '</div>' +
                                '<div class="input-group col-sm-12 col-md-2">' +
                                '<select required name="' + day_name + '_group[' + i +
                                '][teacher_id]" id="teacher" class="form-control set_teacher_id" style="width:100%;" tabindex="-1" aria-hidden="true"></select>' +
                                '</div>' +
                                '<div class="input-group col-sm-12 col-md-2">' +
                                '<input required type="time" value="' + response[i]['start_time'] + '" name="' +
                                day_name + '_group[' + i +
                                '][start_time]" class="timetable_start_time form-control" placeholder="Start time">' +
                                '</div>' +
                                '<div class="input-group col-sm-12 col-md-2">' +
                                '<input required type="time" value="' + response[i]['end_time'] + '" name="' +
                                day_name + '_group[' + i +
                                '][end_time]" class="timetable_end_time form-control" placeholder="End time">' +
                                '</div>' +
                                '<div class="input-group col-sm-12 col-md-2">' +
                                '<input type="text" value="' + liveClassUrl + '" name="' + day_name +
                                '_group[' + i + '][live_class_url]" class="form-control" placeholder="Link">' +
                                '</div>' +
                                '<div class="input-group col-sm-12 col-md-1">' +
                                '<input type="text" value="' + linkName + '" name="' + day_name + '_group[' +
                                i + '][link_name]" class="form-control" placeholder="Link">' +
                                '</div>' +
                                '<button data-repeater-delete type="button" class="row_remove btn btn-gradient-danger btn-sm icon-btn ml-2"><i class="fa fa-trash"></i></button>' +
                                '</div>'
                        }
                        $('.set_' + day_name).html(html);
                        tab = day_name;
                        total_row = $('.' + tab + '_count').length;
                        set_teacher_data();

                        for (let j = 0; j < response.length; j++) {
                            setTimeout(function() {
                                $('select[name^="' + day_name + '_group[' + j + '][subject_id]"]')
                                    .children('option[value="' + response[j]['subject_teacher'][
                                        'subject'
                                    ]['id'] + '"]').attr("selected", true);
                                $('select[name^="' + day_name + '_group[' + j + '][subject_id]"]')
                                    .trigger('change');
                                setTimeout(function() {
                                    $('select[name^="' + day_name + '_group[' + j +
                                        '][teacher_id]"]').children('option[value="' +
                                        response[j]['subject_teacher']['teacher']['id'] +
                                        '"]').attr("selected", true);
                                }, 1000)
                            }, 1000)
                        }
                    }
                }
            });
        }

        function clearTimetableRows(tab) {
            // clear subject & teacher dropdowns
            $('select[name^="' + tab + '_group"]').each(function() {
                $(this).val('');
            });

            // clear time inputs
            $('input[name^="' + tab + '_group"][name$="[start_time]"]').val('');
            $('input[name^="' + tab + '_group"][name$="[end_time]"]').val('');

            // clear link inputs
            $('input[name^="' + tab + '_group"][name$="[live_class_url]"]').val('');
            $('input[name^="' + tab + '_group"][name$="[link_name]"]').val('');
        }
    </script>
@endsection
