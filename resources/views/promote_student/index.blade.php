@extends('layouts.master')

@section('title')
    {{ __('promote_student') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('promote_students_in_next_session') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('promote-student.store') }}" class="create-form" id="formdata">
                            @csrf
                            <div class="row" id="toolbar">
                                <div class="form-group col-sm-12 col-md-3">
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

                                <div class="form-group col-sm-12 col-md-3">
                                    <label>{{ __('classes') }}</label>
                                    <select required name="classes" id="classes" class="form-control select2"
                                        style="width:100%;" tabindex="-1" aria-hidden="true">
                                        @if ($classes && count($classes) > 0)
                                            @foreach ($classes as $class)
                                                <option value="{{ $class->id }}" {{ $loop->first ? 'selected' : '' }}>
                                                    {{ $class->name ?? 'Unnamed Class' }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="">{{ __('no_data_found') }}</option>
                                        @endif
                                    </select>
                                </div>

                                <div class="form-group col-sm-12 col-md-3">
                                    <label class="text-nowrap">
                                        {{ __('class') }} {{ __('section') }}
                                    </label>
                                    <select required name="class_section_id" id="student_class_section" class="form-control"
                                        style="width:100%;" tabindex="-1" aria-hidden="true">
                                        <option value="">{{ __('select_class') }} {{ __('section') }}</option>
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group col-sm-12 col-md-3">
                                    <label>
                                        {{ __('status') }}
                                    </label>
                                    <select name="status" id="filter_status" class="form-control">
                                        <option value="">{{ __('all') }}</option>
                                        <option value="pass">{{ __('pass') }}</option>
                                        <option value="fail">{{ __('fail') }}</option>
                                        <option value="pending">{{ __('pending') }}</option>
                                    </select>
                                </div>
                            </div>

                            <table aria-describedby="mydesc" class='table promote_student_table'
                                id='promote_student_table_list' data-toggle="table"
                                data-response-handler="handlePromoteStudentListResponse"
                                data-url="{{ url('promote-student-list') }}" data-click-to-select="false"
                                data-side-pagination="server" data-pagination="true" data-page-size="500"
                                data-page-list="[50, 100, 200]" data-search="true" data-toolbar="#toolbar"
                                data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                                data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                                data-sort-order="desc" data-maintain-selected="true" data-export-types='["txt","excel"]'
                                data-export-options='{ "fileName": "promote-student-list-<?= date('d-m-y') ?>"
                                ,"ignoreColumn": ["operate"]}'
                                data-query-params="queryParams" data-escape="true">
                                <thead>
                                    <tr>
                                        <th data-field="state" data-checkbox="true">
                                        </th>

                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                            {{ __('id') }}</th>

                                        <th scope="col" data-formatter="studentNameFormatter" data-field="name"
                                            data-sortable="false">{{ __('name') }}
                                        </th>

                                        <th scope="col" data-field="roll_number" data-sortable="true">
                                            {{ __('roll_no') }}</th>
                                        <th scope="col" data-field="admission_no" data-sortable="true">
                                            {{ __('gr_no') }}</th>

                                        <th scope="col" data-field="studentResult" data-sortable="false"
                                            data-formatter="studentResultFormatter">
                                            {{ __('exam_result') }}
                                        </th>

                                        <th scope="col" data-field="resultStatus" data-sortable="false"
                                            data-formatter="resultStatusFormatter">
                                            {{ __('status') }}
                                        </th>

                                        <th scope="col" data-field="continueStatus" data-sortable="false"
                                            data-formatter="continueStatusFormatter">
                                            {{ __('continue?') }}
                                        </th>

                                        <th scope="col" data-field="promotedTo" data-sortable="false">
                                            {{ __('promoted_to') }}
                                        </th>

                                        <th scope="col" data-field="updateBtn" data-escape="false"
                                            data-formatter="updateBtnFormatter">
                                            {{ __('action') }}
                                        </th>
                                    </tr>
                                </thead>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Promote Student Modal -->
    <div class="modal fade" id="promoteModal" tabindex="-1" role="dialog" aria-labelledby="promoteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content promote-modal-content">

                <div class="modal-header promote-modal-header">
                    <div class="promote-header-text">
                        <h5 class="modal-title promote-modal-title" id="promoteModalLabel">
                            {{ __('promote_student') }}
                        </h5>
                        <small id="studentDetails" class="promote-student-details"></small>
                    </div>
                    <button type="button" class="close promote-close-btn" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form id="promoteStudentForm">
                    @csrf
                    <div class="modal-body promote-modal-body">
                        <input type="hidden" id="modal_student_id" name="student_id">
                        <input type="hidden" id="modal_class_section_id" name="class_section_id">

                        <!-- Student Info -->
                        <div class="promote-info-grid">
                            <div class="promote-info-item" id="gr_no_container">
                                <label class="promote-info-label">{{ __('gr_no') }}</label>
                                <p id="modal_gr_number" class="promote-info-value">-</p>
                            </div>
                            <div class="promote-info-item">
                                <label class="promote-info-label">{{ __('current_class') }}</label>
                                <p id="modal_current_class" class="promote-info-value">-</p>
                            </div>
                            <div class="promote-info-item">
                                <label class="promote-info-label">{{ __('current_session') }}</label>
                                <p id="modal_current_session_year" class="promote-info-value">-</p>
                            </div>
                        </div>

                        <!-- Promoting To Session -->
                        <div class="form-group promote-form-group">
                            <label>{{ __('promoting_to_session') }} <span class="text-danger">*</span></label>

                            <div class="promote-select-wrapper">
                                <select required name="session_year_id" id="session_year_id"
                                    class="form-control promote-select">
                                    <option value="">{{ __('select') . ' ' . __('session_years') }}</option>
                                    @foreach ($session_years as $session_year)
                                        <option value="{{ $session_year->id }}">
                                            {{ $session_year->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="session_warning" style="display:none; margin-top:12px; border-radius:8px;"
                                class="alert alert-warning promote-warning-box">
                            </div>
                        </div>

                        <!-- Result Status -->
                        <div class="form-group promote-form-group">
                            <label class="promote-form-label">
                                {{ __('result_status') }} <span class="text-danger">*</span>
                            </label>
                            <div class="promote-button-group">
                                <button type="button" class="promote-toggle-btn promote-toggle-pass active"
                                    data-value="pass">
                                    {{ __('pass') }}
                                </button>
                                <button type="button" class="promote-toggle-btn promote-toggle-fail" data-value="fail">
                                    {{ __('fail') }}
                                </button>
                            </div>
                            <input type="hidden" name="result_status" id="result_status" value="pass">
                        </div>

                        <!-- Continue with School -->
                        <div class="form-group promote-form-group">
                            <label class="promote-form-label">
                                {{ __('continue_with_school') }} <span class="text-danger">*</span>
                            </label>
                            <div class="promote-button-group">
                                <button type="button" class="promote-toggle-btn promote-toggle-yes active"
                                    data-value="yes">
                                    {{ __('yes') }}
                                </button>
                                <button type="button" class="promote-toggle-btn promote-toggle-no" data-value="no">
                                    {{ __('no_leaving') }}
                                </button>
                            </div>
                            <input type="hidden" name="continue_school" id="continue_school" value="yes">
                        </div>

                        <!-- Promote To -->
                        <div class="form-group promote-form-group" id="promote_to_group">
                            <label for="promote_to" class="promote-form-label">
                                {{ __('promote_to') }} <span class="text-danger">*</span>
                            </label>
                            <div class="promote-select-wrapper">
                                <select name="promote_to" id="promote_to" class="form-control promote-select">
                                    <option value="">{{ __('choose_next_class') }}</option>
                                    <option value="repeat" id="repeat_same_class_option" style="display:none;">
                                        {{ __('repeat_in_same_class') }}
                                    </option>
                                    @foreach ($class_sections as $section)
                                        <option value="{{ $section->id }}" data-class-id="{{ $section->id }}">
                                            {{ $section->class->name }} - {{ $section->section->name }}
                                            {{ $section->class->medium->name }}
                                            {{ $section->class->streams->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer promote-modal-footer">
                        <button type="button" class="btn promote-btn-cancel" data-dismiss="modal">
                            {{ __('cancel') }}
                        </button>
                        <button type="submit" class="btn promote-btn-update" id="updatePromoteBtn">
                            {{ __('update') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bottom Selection Bar -->
    <div id="bottomSelectionBar" class="bottom-selection-bar" style="display: none;">
        <div class="selection-bar-container">
            <div class="selection-bar-content">
                <div class="selection-info">
                    <div class="selected-avatars" id="selectedAvatars"></div>
                    <span class="selection-count" id="selectionCount">0 Students selected</span>
                </div>
                <div class="selection-actions">
                    <button type="button" class="btn btn-assign" id="bulkPromoteBtn">Promote</button>
                    <button type="button" class="btn btn-clear" id="clearSelectionBtn">
                        <i class="mdi mdi-close"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Result Modal -->
    <div class="modal fade" id="studentResultModal" tabindex="-1" role="dialog"
        aria-labelledby="studentResultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content result-modal-content">
                <!-- Modal Header -->
                <div class="modal-header result-modal-header">
                    <div class="result-header-left">
                        <div class="result-avatar" id="result_avatar">
                            <img src="" alt="Student" id="result_student_image">
                        </div>
                        <div class="result-student-info">
                            <h5 class="modal-title result-modal-title" id="result_student_name">-</h5>
                            <div class="result-student-meta">
                                <span>Roll: <span id="result_roll_number">-</span></span>
                                <span class="meta-divider">•</span>
                                <span>GR: <span id="result_gr_number">-</span></span>
                                <span class="meta-divider">•</span>
                                <span>Class: <span id="result_class">-</span></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="close result-close-btn" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body result-modal-body">
                    <!-- Summary Cards -->
                    <div class="result-summary-grid">
                        <div class="result-summary-card">
                            <div class="summary-icon summary-icon-total">
                                <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21"
                                    fill="currentColor" class="bi bi-book" viewBox="0 0 16 16">
                                    <path
                                        d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783" />
                                </svg>
                            </div>
                            <div class="summary-content">
                                <p class="summary-label">Total Exams</p>
                                <h4 class="summary-value" id="total_exams">-</h4>
                            </div>
                        </div>

                        <div class="result-summary-card">
                            <div class="summary-icon summary-icon-average">
                                <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21"
                                    fill="currentColor" class="bi bi-graph-up-arrow" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd"
                                        d="M0 0h1v15h15v1H0zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61L13.445 4H10.5a.5.5 0 0 1-.5-.5" />
                                </svg>
                            </div>
                            <div class="summary-content">
                                <p class="summary-label">Average</p>
                                <h4 class="summary-value" id="average_percentage">-</h4>
                            </div>
                        </div>

                        <div class="result-summary-card">
                            <div class="summary-icon summary-icon-highest">
                                <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21"
                                    fill="currentColor" class="bi bi-trophy" viewBox="0 0 16 16">
                                    <path
                                        d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5q0 .807-.034 1.536a3 3 0 1 1-1.133 5.89c-.79 1.865-1.878 2.777-2.833 3.011v2.173l1.425.356c.194.048.377.135.537.255L13.3 15.1a.5.5 0 0 1-.3.9H3a.5.5 0 0 1-.3-.9l1.838-1.379c.16-.12.343-.207.537-.255L6.5 13.11v-2.173c-.955-.234-2.043-1.146-2.833-3.012a3 3 0 1 1-1.132-5.89A33 33 0 0 1 2.5.5m.099 2.54a2 2 0 0 0 .72 3.935c-.333-1.05-.588-2.346-.72-3.935m10.083 3.935a2 2 0 0 0 .72-3.935c-.133 1.59-.388 2.885-.72 3.935M3.504 1q.01.775.056 1.469c.13 2.028.457 3.546.87 4.667C5.294 9.48 6.484 10 7 10a.5.5 0 0 1 .5.5v2.61a1 1 0 0 1-.757.97l-1.426.356a.5.5 0 0 0-.179.085L4.5 15h7l-.638-.479a.5.5 0 0 0-.18-.085l-1.425-.356a1 1 0 0 1-.757-.97V10.5A.5.5 0 0 1 9 10c.516 0 1.706-.52 2.57-2.864.413-1.12.74-2.64.87-4.667q.045-.694.056-1.469z" />
                                </svg>
                            </div>
                            <div class="summary-content">
                                <p class="summary-label">Highest</p>
                                <h4 class="summary-value" id="highest_percentage">-</h4>
                            </div>
                        </div>

                        <div class="result-summary-card">
                            <div class="summary-icon summary-icon-lowest">
                                <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21"
                                    fill="currentColor" class="bi bi-graph-down-arrow" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd"
                                        d="M0 0h1v15h15v1H0zm10 11.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-4a.5.5 0 0 0-1 0v2.6l-3.613-4.417a.5.5 0 0 0-.74-.037L7.06 8.233 3.404 3.206a.5.5 0 0 0-.808.588l4 5.5a.5.5 0 0 0 .758.06l2.609-2.61L13.445 11H10.5a.5.5 0 0 0-.5.5" />
                                </svg>
                            </div>
                            <div class="summary-content">
                                <p class="summary-label">Lowest</p>
                                <h4 class="summary-value" id="lowest_percentage">-</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Grid -->
                    <div class="result-content-grid">
                        <!-- Exam History -->
                        <div class="result-section">
                            <h6 class="result-section-title">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                    fill="currentColor" class="bi bi-calendar4" viewBox="0 0 16 16">
                                    <path
                                        d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M2 2a1 1 0 0 0-1 1v1h14V3a1 1 0 0 0-1-1zm13 3H1v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1z" />
                                </svg> Exam History
                            </h6>
                            <div class="exam-history-list" id="exam_history_list">
                                <!-- Exam cards will be inserted here -->
                            </div>
                        </div>

                        <!-- Subject-wise Breakdown -->
                        <div class="result-section">
                            <div class="subject-header">
                                <h6 class="result-section-title">
                                    Subject-wise Breakdown
                                </h6>
                                <span class="overall-grade-badge" id="overall_grade_badge">Overall: B+</span>
                            </div>
                            <div class="subject-list" id="subject_breakdown_list">
                                <!-- Subject items will be inserted here -->
                            </div>

                            <!-- Total Marks Footer -->
                            <div class="total-marks-footer">
                                <div class="total-label">Total Marks</div>
                                <div class="total-values">
                                    <span id="total_obtained_marks">-</span> / <span id="total_max_marks">-</span>
                                </div>
                                <div class="total-percentage" id="total_percentage_footer">-%</div>
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
        function queryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search,
                class_id: $('#classes').val(),
                section_id: $('#student_class_section').val(),
                status: $('#filter_status').val(),
                session_year_id: $('#filter_session_year').val(),
            };
        }
    </script>

    <script>
        $('#student_class_section, #filter_status, #filter_session_year').on('change', function() {
            $('#promote_student_table_list').bootstrapTable('refresh');
        });
        $('.btn_promote').hide();

        function set_data() {
            $(document).ready(function() {
                student_class = $('#student_class_section').val();
                promote_class = $('#new_student_class_section').val();
                if (student_class != '' && promote_class != '') {
                    $('.btn_promote').show();
                } else {
                    $('.btn_promote').hide();
                }
            });
        }
        $('#student_class_section, #new_student_class_section, #filter_session_year').on('change', function() {
            set_data();
        });

        //
        const $from = $('#filter_session_year');
        const $to = $('#session_year_id');

        function filterSessionYears() {
            const selectedId = $from.val();

            // Reset options
            $to.find('option').show();

            if (selectedId) {
                $to.find('option[value="' + selectedId + '"]').hide();

                // If currently selected value becomes invalid, reset it
                if ($to.val() === selectedId) {
                    $to.val('');
                }
            }
        }

        // Run on change
        $from.on('change', filterSessionYears);

        // Run on page load (edit form support)
        filterSessionYears();
    </script>
@endsection
