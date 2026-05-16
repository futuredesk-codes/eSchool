@extends('layouts.master')

@section('title')
    {{ __('students') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('student') . ' ' . __('Result') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('generate') . ' ' . __('student') . ' ' . __('result') }}
                        </h4>
                        {{-- get-student-list --}}
                        @csrf
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-6">
                                <label for="filter_session_year">{{ __('session_year') }}</label>
                                <select name="filter_session_year" id="filter_session_year" data-scope="table"
                                    class="form-control filter_session_year">
                                    @foreach ($session_years as $session_year)
                                        <option value="{{ $session_year->id }}">
                                            {{ $session_year->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-6">
                                <label for="class_id">{{ __('class') }}</label>
                                <select required name="class_id" id="class_id" class="form-control select2"
                                    style="width:100%;" tabindex="-1" aria-hidden="true">
                                    <option value="">{{ __('select_class') }}</option>
                                    @if (!$is_teacher)
                                        @foreach ($classes as $data)
                                            <option data-class-section-id="{{ $data->id }}" value="{{ $data->class->id }}">
                                                {{ $data->class->name }} - {{ $data->section->name }}
                                                {{ $data->class->medium->name }} {{ $data->class->streams->name ?? '' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-12">
                                <button type="button" id="search" class="btn btn-theme">{{ __('Search') }}</button>
                            </div>
                        </div>
                        <div class="show_student_list">
                            <table aria-describedby="mydesc" class='table student_table' id='table_list' data-toggle="table"
                                data-url="{{ route('get.student.list') }}" data-click-to-select="true"
                                data-side-pagination="server" data-pagination="false"
                                data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true"
                                data-show-refresh="true" data-fixed-columns="true" data-trim-on-search="false"
                                data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                                data-maintain-selected="true" data-export-types='["txt","excel"]'
                                data-export-options='{ "fileName": "exam-result-list-<?= date(' d-m-y') ?>" ,"ignoreColumn":
                                ["operate"]}'
                                data-query-params="uploadMarksqueryParams" data-toolbar="#toolbar" data-escape="true">
                                <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                            {{ __('id') }}</th>
                                        <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                        <th scope="col" data-field="student_name" data-sortable="true">
                                            {{ __('name') }}</th>
                                        <th scope="col" data-field="admission_no" data-sortable="false">
                                            {{ __('gr_number') }}</th>
                                        <th scope="col" data-field="roll_number" data-sortable="false">
                                            {{ __('roll_no') }}</th>
                                        <th scope="col" data-field="class_section_id"
                                            data-sortable="false"data-visible="false">
                                            {{ __('class') . ' ' . __('section') . ' ' . __('id') }}</th>
                                        <th scope="col" data-field="class_section_name" data-sortable="false">
                                            {{ __('class') . ' ' . __('section') }}</th>
                                        @canany(['generate-result'])
                                            <th data-escape="false" data-events="studentEvents" data-width="150" scope="col"
                                                data-field="operate" data-sortable="false">{{ __('action') }}</th>
                                        @endcanany
                                    </tr>
                                </thead>
                            </table>
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
        @if ($is_teacher)
        $(function () {
            function loadClassSections(sessionYearId) {
                var $dropdown = $('#class_id');
                $dropdown.html('<option value="">{{ __("select_class") }}</option>');

                if (!sessionYearId) return;

                $.ajax({
                    url: '{{ url("student-class-sections") }}',
                    type: 'GET',
                    data: { session_year_id: sessionYearId },
                    success: function (response) {
                        var html = '<option value="">{{ __("select_class") }}</option>';
                        $.each(response, function (key, value) {
                            html += '<option data-class-section-id="' + value.id + '" value="' + value.id + '">' + value.name + '</option>';
                        });
                        $dropdown.html(html);
                    }
                });
            }

            $('#filter_session_year').on('change', function () {
                loadClassSections($(this).val());
            });
        });
        @endif

        $('#search').on('click , input', function() {
            $('.show_student_list').show();
            $('.student_table').bootstrapTable('refresh');
        });
        $('#table_list').on('load-success.bs.table', function(e, response) {
            if (response.error == true) {
                showErrorToast(response.message);
                $('.show_student_list').hide();
            }
        })
    </script>
@endsection
