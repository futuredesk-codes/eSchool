@extends('layouts.master')

@section('title')
    {{ __('exam_result') }}
@endsection

@section('content')
    <div class="content-wrapper">

        <div class="page-header">
            <h3 class="page-title">{{ __('exam_result') }}</h3>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">

                        <h4 class="card-title">{{ __('view_result') }}</h4>

                        <form id="formdata">
                            <div class="row">

                                <!-- Session Year -->
                                <div class="form-group col-md-4">
                                    <label>{{ __('session_year') }}</label>
                                    <select name="session_year_id" id="session_year_id" class="form-control">
                                        @foreach ($session_years as $session_year)
                                            <option value="{{ $session_year->id }}"
                                                {{ (int) $session_year->id === (int) ($currentSessionYearId ?? 0) ? 'selected' : '' }}>
                                                {{ $session_year->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Class -->
                                <div class="form-group col-md-4">
                                    <label>{{ __('class') }}</label>
                                    <select required name="class_section_id_select" id="class_section_id"
                                        class="form-control select2">
                                        <option value="">{{ __('select_class') }}</option>
                                    </select>
                                    <input type="hidden" name="semester_id" id="semester_id">
                                    <input type="hidden" name="class_section_id" id="class_section_id_hidden">
                                </div>

                                <!-- Exam -->
                                <div class="form-group col-md-4">
                                    <label>{{ __('exam') }}</label>
                                    <select required name="exam_id" id="exam_id" class="form-control select2">
                                        <option value="">{{ __('select_exam') }}</option>
                                    </select>
                                </div>

                                <div class="form-group col-md-12">
                                    <button type="button" id="search" class="btn btn-theme">Search</button>
                                </div>

                            </div>

                            <!-- Table -->
                            <div class="show_students_list" style="display:none;">

                                <div class="alert alert-info" id="loading-message" style="display:none;">
                                    <i class="fa fa-spinner fa-spin"></i> Loading exam results...
                                </div>

                                <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                    data-click-to-select="true" data-side-pagination="server" data-method="post"
                                    data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                                    data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                                    data-fixed-columns="true" data-trim-on-search="false" data-mobile-responsive="true"
                                    data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                                    data-export-types='["txt","excel"]'
                                    data-export-options='{ "fileName": "exam-list-<?= date('d-m-y') ?>", "ignoreColumn":
                                    ["operate"]}'
                                    data-show-export="true" data-detail-formatter="examListFormatter"
                                    data-query-params="getExamResult" data-escape="true">
                                    <thead>
                                        <tr>
                                            <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                                {{ __('id') }}</th>
                                            <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}
                                            </th>
                                            <th scope="col" data-field="student_name" data-sortable="false">
                                                {{ __('students') . ' ' . __('name') }}</th>
                                            <th scope="col" data-field="admission_no" data-sortable="false">
                                                {{ __('GR') . ' ' . __('No.') }}</th>
                                            <th scope="col" data-field="total_marks" data-sortable="true">
                                                {{ __('total_marks') }}</th>
                                            <th scope="col" data-field="obtained_marks" data-sortable="true">
                                                {{ __('obtained_marks') }}</th>
                                            <th scope="col" data-field="percentage" data-sortable="true">
                                                {{ __('percentage') }}</th>
                                            <th scope="col" data-field="grade" data-sortable="true">{{ __('grade') }}
                                            </th>
                                            <th scope="col" data-field="created_at" data-sortable="true"
                                                data-visible="false">{{ __('created_at') }}</th>
                                            <th scope="col" data-field="updated_at" data-sortable="true"
                                                data-visible="false">{{ __('updated_at') }}</th>
                                            <th scope="col" data-escape="false" data-field="operate"
                                                data-sortable="false" data-events="examMarksEvents">{{ __('action') }}
                                            </th>
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
        // Server-rendered initial data
        const initialClasses = @json($classOptions);
        const initialSemesterMap = @json($allowedSemesterMap);

        // Attach filters to bootstrap-table API request
        function getExamResult(params) {
            return {
                session_year_id: $('#session_year_id').val(),
                class_section_id: $('#class_section_id_hidden').val() || $('#class_section_id').val(),
                exam_id: $('#exam_id').val(),
                semester_id: $('#semester_id').val(),
                limit: params.limit,
                offset: params.offset,
                search: params.search,
            };
        }

        $(document).ready(function() {

            // ── Helpers ──────────────────────────────────────────────────────────

            function buildClassDropdown(classes, semesterMap) {
                let html = `<option value="">{{ __('select_class') }}</option>`;

                classes.forEach(opt => {
                    const semesters = semesterMap[opt.id] || [];

                    if (semesters.length) {
                        semesters.forEach(sem => {
                            html += `<option value="${opt.id}|${sem.id}"
                            data-class-section-id="${opt.id}"
                            data-class="${opt.class_id}"
                            data-semester="${sem.id}">
                            ${opt.label} - ${sem.name}
                        </option>`;
                        });
                    } else {
                        html += `<option value="${opt.id}"
                        data-class-section-id="${opt.id}"
                        data-class="${opt.class_id}">
                        ${opt.label}
                    </option>`;
                    }
                });

                $('#class_section_id').html(html).trigger('change.select2');
                $('#semester_id').val('');
                $('#class_section_id_hidden').val('');
            }

            function fetchClassOptions(sessionYearId) {
                ajaxRequest(
                    'GET',
                    '{{ route('exams.class-options') }}', {
                        session_year_id: sessionYearId
                    },
                    null,
                    function(response) {
                        buildClassDropdown(response.classes, response.allowedSemesterMap);
                    },
                    null, null, true
                );
            }

            // ── Events ───────────────────────────────────────────────────────────

            // Sync hidden fields when class/semester option is selected
            $('#class_section_id').on('change', function() {
                const selected = $(this).find(':selected');
                let value = selected.val() || '';
                let semester = selected.data('semester') || '';
                let classSectionId = value;

                if (value.includes('|')) {
                    [classSectionId, semester] = value.split('|');
                }

                $('#semester_id').val(semester);
                $('#class_section_id_hidden').val(classSectionId);
            });

            // Re-fetch classes when session year changes
            $('#session_year_id').on('change', function() {
                $('.show_students_list').hide();
                fetchClassOptions($(this).val());
            });

            // Show table on search
            // Show table on search
            $('#search').on('click', function() {

                const classSectionId = $('#class_section_id').val();
                const examId = $('#exam_id').val();

                // Frontend validation
                if (!classSectionId) {
                    showErrorToast('Please select a class.');
                    return;
                }

                if (!examId) {
                    showErrorToast('Please select an exam before searching.');
                    return;
                }

                $('.show_students_list').show();

                $('#table_list').bootstrapTable('refresh', {
                    url: "{{ route('exams.fetch-exam-result-list') }}"
                });
            });

            // Hide table on bootstrap-table error response
            $('#table_list').on('load-success.bs.table', function(e, response) {
                if (response.error) {
                    showErrorToast(response.message);
                    $('.show_students_list').hide();
                }
            });

            // ── Initial render (no AJAX on first load) ───────────────────────────
            buildClassDropdown(initialClasses, initialSemesterMap);

        });
    </script>
@endsection
