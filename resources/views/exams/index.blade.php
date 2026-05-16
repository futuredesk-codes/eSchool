@extends('layouts.master')

@section('title')
    {{ __('manage') . ' ' . __('exam') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('exam') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">
                            {{ __('create') . ' ' . __('exams') }}
                        </h4>
                        <form class="pt-3 mt-6 add-exam-form create-form" method="POST" action="{{ url('exams') }}">
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('exam_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" id="name" name="name" placeholder="{{ __('exam_name') }}"
                                        class="form-control" />
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('session_years') }} <span class="text-danger">*</span></label>
                                    <select required name="session_year_id" id="session_year_id"
                                        class="form-control select2" style="width:100%;" tabindex="-1" aria-hidden="true">
                                        @foreach ($session_year_all as $years)
                                            <option value="{{ $years->id }}"{{ $years->default == 1 ? 'selected' : '' }}>
                                                {{ $years->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-sm-12 col-md-6" id="semester_div" style="display: none;">
                                    <label>{{ __('semester') }}</label>
                                    <select name="semester_id" id="semester_id" class="form-control">
                                        <option value="">--{{ __('select') }}--</option>
                                    </select>
                                    <small class="text-info mt-1 d-block">
                                        <i class="fa fa-info-circle"></i> {{ __('leave_unselected_for_non_semester_classes') }}
                                    </small>
                                </div>

                                {{-- class checkboxes --}}
                                @if (isset($classes))
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('class') }}<span class="text-danger">*</span></label><br>
                                        <select multiple name="class_id[]" id="exam_class_id"
                                            class="form-control js-example-basic-single select2-hidden-accessible">
                                            @foreach ($classes as $class)
                                                <option value="{{ $class['id'] }}"
                                                    data-medium="{{ $class['medium_id'] }}">{{ $class['name'] }}-
                                                    {{ $class['medium']['name'] }} {{ $class['streams']['name'] ?? ' ' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                {{-- class checkboxes --}}
                            </div>
                            <div class="row">
                                <div class="form-group col">
                                    <label>{{ __('exam_description') }}</label>
                                    <textarea id="description" name="description" placeholder="{{ __('exam_description') }}" class="form-control"></textarea>
                                </div>
                            </div>
                            <input class="btn btn-theme" id="add-exam-btn" type="submit" value={{ __('submit') }}>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list') . ' ' . __('exams') }}
                        </h4>
                        <div class='row'>
                            <div class="form-group col-sm-12 col-md-2">
                                <label for="filter_session_year" class="form-label text-nowrap">
                                    {{ __('session_year') }}
                                </label>
                                <select name="filter_session_year" id="filter_session_year" data-scope="table"
                                    class="form-control filter_session_year">
                                    @foreach ($session_year_all as $session_year)
                                        <option value="{{ $session_year->id }}">
                                            {{ $session_year->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                            data-url="{{ route('exams.show', 1) }}" data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                            data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                            data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                            data-sort-order="desc" data-maintain-selected="true" data-export-types='["txt","excel"]'
                            data-export-options='{ "fileName": "exam-list-<?= date(' d-m-y') ?>" ,"ignoreColumn":
                            ["operate"]}' data-show-export="true" data-detail-formatter="examListFormatter"
                            data-escape="true" data-query-params="queryParams">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                        {{ __('id') }}
                                    </th>
                                    <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                    <th scope="col" data-field="name" data-sortable="true">{{ __('name') }}</th>
                                    <th scope="col" data-field="description" data-sortable="true">
                                        {{ __('description') }}</th>
                                    <th scope="col" data-field="class_name" data-sortable="false">{{ __('class') }}
                                    </th>
                                    <th scope="col" data-field="publish" data-sortable="true"
                                        data-formatter="examPublishFormatter">{{ __('publish') }}</th>
                                    <th scope="col" data-field="session_year_name" data-sortable="false">
                                        {{ __('session_years') }}</th>
                                    <th scope="col" data-field="created_at" data-sortable="true"
                                        data-visible="false">
                                        {{ __('created_at') }}</th>
                                    <th scope="col" data-field="updated_at" data-sortable="true"
                                        data-visible="false">
                                        {{ __('updated_at') }}</th>
                                    <th scope="col" data-escape="false" data-field="operate" data-sortable="false"
                                        data-events="examEvents">{{ __('action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                {{ __('edit') . ' ' . __('exams') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="pt-3 edit-exam-form" id="edit-form" action="{{ url('exams') }}"
                            novalidate="novalidate">
                            <input type="hidden" name="edit_id" id="edit_id" value="" />
                            <div class="modal-body">
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('exam_name') }} <span class="text-danger">*</span></label>
                                        <input type="text" required id="edit_name" name="name"
                                            placeholder="{{ __('exam_name') }}" class="form-control" />
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('session_years') }}<span class="text-danger">*</span></label>
                                        <select required name="session_year_id" id="edit_session_year_id"
                                            class="form-control select2" style="width:100%;" tabindex="-1"
                                            aria-hidden="true">
                                            @foreach ($session_year_all as $years)
                                                <option
                                                    value="{{ $years->id }}"{{ $years->default == 1 ? 'selected' : '' }}>
                                                    {{ $years->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-6" id="edit_semester_div" style="display: none;">
                                        <label>{{ __('semester') }}</label>
                                        <select name="semester_id" id="edit_semester_id" class="form-control">
                                            <option value="">--{{ __('select') }}--</option>
                                        </select>
                                        <small class="text-info mt-1 d-block">
                                            <i class="fa fa-info-circle"></i> {{ __('leave_unselected_for_non_semester_classes') }}
                                        </small>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('class') }}<span class="text-danger">*</span></label><br>
                                        @if (isset($classes))
                                            <select multiple name="class_id[]" id="edit_class_id"
                                                class="form-control js-example-basic-single select2-hidden-accessible edit_class_id">
                                                @foreach ($classes as $class)
                                                    <option value="{{ $class['id'] }}"
                                                        data-medium="{{ $class['medium_id'] }}">{{ $class['name'] }}-
                                                        {{ $class['medium']['name'] }}
                                                        {{ $class['streams']['name'] ?? ' ' }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                    <div class="form-group col-sm-12 col-md-6">
                                        <label>{{ __('exam_description') }}</label>
                                        <textarea id="edit_description" name="description" placeholder="{{ __('exam_description') }}" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{{ __('close') }}</button>
                                <input class="btn btn-theme" type="submit" value={{ __('edit') }} />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script type="text/javascript">
        function queryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search,
                session_year_id: $('#filter_session_year').val(),
            };
        }

        $(document).ready(function () {
            // Store original class options for create form
            var originalClassOptions = [];
            $('#exam_class_id option').each(function () {
                originalClassOptions.push({
                    value: $(this).val(),
                    text: $(this).text().trim(),
                    medium: $(this).data('medium')
                });
            });

            // Store original class options for edit form
            var editOriginalClassOptions = [];
            $('#edit_class_id option').each(function () {
                editOriginalClassOptions.push({
                    value: $(this).val(),
                    text: $(this).text().trim(),
                    medium: $(this).data('medium')
                });
            });

            var semesterClassIds = [];

            // Fetch semesters and class config for a given session year
            function loadSemesters(sessionYearId, semesterSelector, semesterDivSelector, callback) {
                if (!sessionYearId) return;

                // Fetch class config to know which classes have semesters
                let configUrl = baseUrl + '/semester/class-config';
                ajaxRequest('GET', configUrl, { session_year_id: sessionYearId }, null, function (configResponse) {
                    semesterClassIds = (configResponse.data || []).map(function (c) { return String(c.id); });

                    // Fetch semesters for the session year
                    let semUrl = baseUrl + '/session-year/' + sessionYearId + '/semesters';
                    ajaxRequest('GET', semUrl, null, null, function (semResponse) {
                        var semesters = semResponse.data || [];
                        var html = '<option value="">--{{ __('select') }}--</option>';
                        $.each(semesters, function (i, sem) {
                            html += '<option value="' + sem.id + '">' + sem.name + '</option>';
                        });
                        $(semesterSelector).html(html);

                        if (semesters.length > 0 && semesterClassIds.length > 0) {
                            $(semesterDivSelector).show();
                        } else {
                            $(semesterDivSelector).hide();
                        }

                        if (callback) callback();
                    }, null, null, true);
                }, null, null, true);
            }

            // Filter class options based on selected semester
            function filterClasses(classSelector, options, selectedSemester) {
                var html = '';
                $.each(options, function (i, opt) {
                    var isSemesterClass = semesterClassIds.includes(String(opt.value));
                    if (selectedSemester) {
                        // When semester is selected, show only semester-configured classes
                        if (!isSemesterClass) return;
                    } else {
                        // When no semester is selected, hide semester-based classes
                        if (isSemesterClass) return;
                    }
                    html += '<option value="' + opt.value + '" data-medium="' + opt.medium + '">' + opt.text + '</option>';
                });
                $(classSelector).html(html);
            }

            // --- Create Form ---
            // On session year change
            $('#session_year_id').on('change', function () {
                $('#semester_id').val('');
                loadSemesters($(this).val(), '#semester_id', '#semester_div', function () {
                    filterClasses('#exam_class_id', originalClassOptions, '');
                });
            });

            // On semester change
            $('#semester_id').on('change', function () {
                filterClasses('#exam_class_id', originalClassOptions, $(this).val());
            });

            // Initial load for create form
            loadSemesters($('#session_year_id').val(), '#semester_id', '#semester_div', function () {
                filterClasses('#exam_class_id', originalClassOptions, '');
            });

            // --- Edit Form ---
            $('#edit_session_year_id').on('change', function () {
                $('#edit_semester_id').val('');
                loadSemesters($(this).val(), '#edit_semester_id', '#edit_semester_div', function () {
                    filterClasses('#edit_class_id', editOriginalClassOptions, '');
                });
            });

            $('#edit_semester_id').on('change', function () {
                filterClasses('#edit_class_id', editOriginalClassOptions, $(this).val());
            });
        });
    </script>
@endsection
