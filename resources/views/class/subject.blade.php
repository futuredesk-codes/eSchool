@extends('layouts.master')

@section('title')
    {{ __('class') . ' ' . __('subject') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('class') . ' ' . __('subject') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div id="toolbar" class="mb-3">
                            <div class="row align-items-end">
                                <div class="col-md-6">
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
                                <div class="col-md-6">
                                    <label for="filter_medium_id" class="form-label">
                                        {{ __('medium') }}
                                    </label>
                                    <select name="filter_medium_id" id="filter_medium_id" class="form-control">
                                        <option value="">{{ __('all') }}</option>
                                        @foreach ($mediums as $medium)
                                            <option value="{{ $medium->id }}">
                                                {{ $medium->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                            data-url="{{ route('class.subject.list') }}" data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                            data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                            data-fixed-columns="true" data-trim-on-search="false" data-mobile-responsive="true"
                            data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                            data-export-types='["txt","excel"]' data-query-params="AssignclassQueryParams"
                            data-export-options='{ "fileName": "class-list-<?= date(' d-m-y') ?>" ,"ignoreColumn":
                            ["operate"]}'
                            data-show-export="true" data-escape="true">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                        {{ __('id') }}</th>
                                    <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                    <th scope="col" data-field="name" data-sortable="true">{{ __('name') }}</th>
                                    <th scope="col" data-field="medium_name" data-sortable="false">{{ __('medium') }}
                                    </th>
                                    <th scope="col" data-field="stream_name" data-sortable="false">{{ __('stream') }}
                                    </th>
                                    <th scope="col" data-field="section_names" data-sortable="false">{{ __('section') }}
                                    </th>
                                    <th scope="col" data-field="include_semesters" data-formatter="semesterFormatter"
                                        data-sortable="false">{{ __('semester') }}</th>
                                    <th scope="col" data-field="core_subject" data-sortable="false"
                                        data-formatter="coreSubjectFormatter">{{ __('core_subject') }}</th>
                                    <th scope="col" data-field="elective_subject" data-sortable="false"
                                        data-formatter="electiveSubjectFormatter">{{ __('elective_subject') }}</th>
                                    <th scope="col" data-field="created_at" data-sortable="true" data-visible="false">
                                        {{ __('created_at') }}</th>
                                    <th scope="col" data-field="updated_at" data-sortable="true" data-visible="false">
                                        {{ __('updated_at') }}</th>
                                    <th scope="col" data-field="operate" data-escape="false" data-sortable="false">
                                        {{ __('action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(document).on('click', '.edit-data', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            var sessionYearId = $('#filter_session_year').val();
            if (sessionYearId) {
                url += '?session_year_id=' + sessionYearId;
            }
            window.location.href = url;
        });
    </script>
@endsection
