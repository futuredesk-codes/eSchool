@extends('layouts.master')

@section('title')
    {{ __('assign') }} {{ __('roll_no') }}
@endsection

@section('content')
    <style>
        .btn-outline-success {
            padding: 15px;
        }
    </style>
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('students') }} {{ __('roll_no') }}
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
                                {{-- <div class="col">
                                    <label for="filter_session_year" class="form-label text-nowrap">
                                        {{ __('session_year') }}
                                    </label>
                                    <select name="filter_session_year" id="filter_session_year" data-scope="table"
                                        class="form-control filter_session_year">
                                        @foreach ($session_years as $year)
                                            <option value="{{ $year->id }}">
                                                {{ $year->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div> --}}

                                <div class="col">
                                    <label>{{ __('class_section') }} </label>
                                    <select name="filter_roll_number_class_section_id"
                                        id="filter_roll_number_class_section_id" class="form-control">
                                        <option value="">{{ __('select_class_section') }}</option>
                                        @foreach ($class_section as $class)
                                            <option value={{ $class->id }}
                                                {{ $class->id == ($defaultClassSection->id ?? 1) ? 'selected' : '' }}>
                                                {{ $class->class->name . ' ' . $class->section->name . ' ' . $class->class->medium->name . ' ' . ($class->class->streams->name ?? ' ') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col">
                                    <label>{{ __('sort_by') }} </label>
                                    <select name="sort_by" id="sort_by" class="form-control">
                                        <option value="first_name">{{ __('first_name') }}</option>
                                        <option value="last_name">{{ __('last_name') }}</option>
                                    </select>
                                </div>

                            </div>
                        </div>
                        @if ($defaultClassSection)
                            <form id="assign-roll-no-form" action="{{ route('students.store-roll-number') }}"
                                method="post">
                                @csrf
                                <div class="row search-container">
                                    <div class="col-12">
                                        <table aria-describedby="mydesc" data-escape="true" class='table' id='table_list'
                                            data-toggle="table"
                                            data-url="{{ route('students.list-students-roll-number', $defaultClassSection->id) }}"
                                            data-click-to-select="true" data-search="true" data-toolbar="#toolbar"
                                            data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                                            data-trim-on-search="false" data-mobile-responsive="true"
                                            data-maintain-selected="true" data-export-types='["txt","excel"]'
                                            data-export-options='{ "fileName": "{{ __('students') }} {{ __('roll_no') }}-<?= date('d-m-y')
                                            ?>","ignoreColumn": ["operate"]}'
                                            data-query-params="studentRollNumberQueryParams">
                                            <thead>
                                                <tr>
                                                    <th scope="col" data-field="no" data-sortable="false">
                                                        {{ __('no.') }}</th>
                                                    <th scope="col" data-field="student_id" data-sortable="false"
                                                        data-visible="false">{{ __('student_id') }} </th>
                                                    <th scope="col" data-field="user_id" data-sortable="false"
                                                        data-visible="false">{{ __('user_id') }}</th>
                                                    <th scope="col" data-field="new_roll_number" data-escape="false"
                                                        data-sortable="false">{{ __('new_roll_no') }}</th>
                                                    <th scope="col" data-field="old_roll_number" data-sortable="false">
                                                        {{ __('old_roll_no') }}</th>
                                                    <th scope="col" data-field="first_name" data-sortable="false">
                                                        {{ __('first_name') }}</th>
                                                    <th scope="col" data-field="last_name" data-sortable="false">
                                                        {{ __('last_name') }}</th>
                                                    <th scope="col" data-field="dob" data-sortable="false">
                                                        {{ __('dob') }}</th>
                                                    <th scope="col" data-field="image" data-sortable="false"
                                                        data-formatter="imageFormatter">{{ __('image') }}</th>
                                                    <th scope="col" data-field="class_section_id" data-sortable="false"
                                                        data-visible="false">
                                                        {{ __('class') . ' ' . __('section') . ' ' . __('id') }}</th>
                                                    <th scope="col" data-field="admission_no" data-sortable="false">
                                                        {{ __('admission_no') }}</th>
                                                    <th scope="col" data-field="admission_date" data-sortable="false">
                                                        {{ __('admission_date') }}</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                                <div class="text-left">
                                    <input class="btn btn-theme btn_generate_roll_number my-4" id="create-btn"
                                        type="submit" value={{ __('submit') }}>
                                </div>
                            </form>
                        @else
                            <div class="alert alert-warning text-center mt-4">
                                {{ __('no_class_section_assigned') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Update table URL when class section changes
            $('#filter_roll_number_class_section_id').on('change', function() {
                var classSectionId = $(this).val();
                var newUrl = '{{ route('students.list-students-roll-number', ':id') }}'.replace(':id',
                    classSectionId);
                $('#table_list').bootstrapTable('refresh', {
                    url: newUrl
                });
            });
        });
    </script>
@endsection
