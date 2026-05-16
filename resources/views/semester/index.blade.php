@extends('layouts.master')

@section('title')
    {{ __('semester') }}
@endsection

@section('content')
    <div class="content-wrapper">
        {{-- Page Header --}}
        <div class="page-header semester-manage-header">
            <h3 class="page-title">
                {{ __('semester_management') }}
            </h3>
            <div class="d-flex align-items-center">
                <span class="mr-2 text-muted"
                    style="font-size: 0.85rem;">{{ __('manage_semesters_for_session_year') }}:</span>
                <select id="semester_session_year" class="form-control semester-session-dropdown">
                    @php
                        $hasCurrentSessionYear = $session_years->contains(fn($year) => (int) ($year->default ?? 0) === 1);
                    @endphp
                    @foreach ($session_years as $session_year)
                        <option value="{{ $session_year->id }}" data-name="{{ $session_year->name }}"
                            data-start-date="{{ $session_year->start_date ?? '' }}"
                            data-end-date="{{ $session_year->end_date ?? '' }}"
                            {{ ((int) ($session_year->default ?? 0) === 1 || (!$hasCurrentSessionYear && $loop->first)) ? 'selected' : '' }}>
                            {{ $session_year->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Info Banner --}}
        <div class="semester-info-banner mb-4">
            <i class="fa fa-info-circle"></i>
            <span>{{ __('semesters_session_year_info') }}</span>
        </div>

        {{-- Class Assignment Configuration --}}
        <div class="card semester-config-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">{{ __('class_assignment_configuration') }}</h4>
                    @if (Auth::user()->can('semester-edit'))
                        <button type="button" class="btn btn-sm btn-inverse-primary" id="btn-configure-classes">
                            <i class="fa fa-cog mr-1"></i> {{ __('configure_classes') }}
                        </button>
                    @endif
                </div>
                <hr class="config-divider">
                {{-- No-semester warning – shown via JS when no semesters exist for this session year --}}
                <div id="no-semester-config-warning" class="no-semester-warning d-none">
                    <i class="fa fa-exclamation-triangle"></i>
                    <span>{{ __('no_semesters_create_first') }}</span>
                </div>
                <p class="text-muted mb-2" style="font-size: 0.8125rem;">
                    {{ __('semesters_will_be_automatically_assigned_to') }}:</p>
                <div class="assigned-classes-section">
                    <div id="assigned-classes-badges">
                        {{-- Populated via JS --}}
                        <span class="text-muted">{{ __('loading') }}</span>
                    </div>
                </div>
                <div class="config-hint mt-3">
                    <i class="fa fa-lightbulb-o mr-1"></i>
                    {{ __('class_auto_assign_note') }}
                </div>
            </div>
        </div>

        {{-- Semesters List Section --}}
        <div class="card semester-list-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-1">{{ __('semesters') }}</h4>
                    </div>
                    @if (Auth::user()->can('semester-create'))
                        <button type="button" class="btn btn-theme" id="btn-create-semester" data-toggle="modal"
                            data-target="#createSemesterModal">
                            {{ __('create_semester') }}
                        </button>
                    @endif
                </div>

                <div class="table-responsive">
                    <table aria-describedby="semester-table-desc" class="table table-striped" id="semester-table"
                        data-click-to-select="true"
                        data-side-pagination="server"
                        data-pagination="true"
                        data-page-list="[5, 10, 20, 50, 100, 200]"
                        data-search="true"
                        data-show-refresh="true"
                        data-show-columns="true"
                        data-mobile-responsive="true"
                        data-sort-name="id"
                        data-sort-order="asc"
                        data-maintain-selected="true"
                        data-escape="false"
                        data-query-params="semesterQueryParams">
                        <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                <th scope="col" data-field="name" data-sortable="true">{{ __('semester_name') }}</th>
                                <th scope="col" data-field="start_date" data-sortable="true">{{ __('start_date') }}</th>
                                <th scope="col" data-field="end_date" data-sortable="true">{{ __('end_date') }}</th>
                                <th scope="col" data-field="status" data-sortable="false" data-formatter="semesterStatusFormatter">{{ __('status') }}</th>
                                <th scope="col" data-field="operate" data-escape="false" data-sortable="false" class="text-right">{{ __('action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

{{-- Configure Classes Modal --}}
<div class="modal fade" id="configureClassesModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom remove-bottom-padding">
                <div>
                    <h5 class="modal-title">{{ __('configure_class_assignment') }}</h5>
                    <p class="text-muted mb-0 mt-1 small">
                        {{ __('configure_class_assignment_desc') }}
                    </p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="padding-md">
              {{-- ⚠ Warning Note --}}
                <div class="alert alert-warning small">
                    <strong>{{ __('note') }}:</strong>
                    {{ __('changing_class_assignment_warning') }}
                </div>
            </div>

            <div class="modal-body remove-bottom-padding">

                <label>{{ __('select_classes') }} <span class="text-danger">*</span></label>

                <div id="class-selector-container">
                    {{-- Selected classes chips --}}
                    <div id="selected-classes-chips" class="class-chips-container mb-2"></div>

                    {{-- Search input --}}
                    <div class="class-search-wrapper mb-2">
                        <i class="fa fa-search"></i>
                        <input type="text" id="class-search-input" class="form-control"
                            placeholder="{{ __('search_classes') }}...">
                    </div>

                    {{-- Class list --}}
                    <div class="class-list-container" id="class-list">
                        @foreach ($classes as $class)
                            <label class="class-list-item"
                                data-class-id="{{ $class->id }}"
                                data-class-name="{{ $class->name }}"
                                data-medium-name="{{ $class->medium->name ?? '' }}">
                                
                                <input type="checkbox" class="class-checkbox" value="{{ $class->id }}">
                                <span class="custom-check"></span>
                                <span class="class-name">{{ $class->name }}</span>

                                @if ($class->medium)
                                    <span class="class-medium">{{ $class->medium->name }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    {{ __('close') }}
                </button>
                <button type="button" class="btn btn-theme" id="btn-save-class-config">
                    {{ __('save') }}
                </button>
            </div>
        </div>
    </div>
</div>

        {{-- Create Semester Modal --}}
        <div class="modal fade" id="createSemesterModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header remove-bottom-padding">
                        <h5 class="modal-title">{{ __('create') . ' ' . __('semester') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="semester-create-form" action="{{ route('semester.store') }}"
                        method="POST" novalidate="novalidate">
                        <div class="modal-body remove-bottom-padding">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label>{{ __('name') }} <span class="text-danger">*</span></label>
                                    <input name="name" type="text" placeholder="{{ __('name') }}"
                                        class="form-control" required />
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ __('start_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="start_date" class="datepicker-popup form-control"
                                        placeholder="{{ __('start_date') }}" autocomplete="off" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="end_date" class="datepicker-popup form-control"
                                        placeholder="{{ __('end_date') }}" autocomplete="off" required>
                                </div>
                            </div>
                            <input type="hidden" name="session_year_id" id="create_session_year_id" value="">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">{{ __('close') }}</button>
                            <input class="btn btn-theme" id="semester-create-btn" type="submit"
                                value="{{ __('submit') }}">
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Edit Semester Modal --}}
        <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('edit') . ' ' . __('semester') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form class="pt-3" id="semester-edit-form" action="{{ url('semester') }}"
                        novalidate="novalidate">
                        <input type="hidden" name="edit_id" id="edit_id" value="" />
                        <input type="hidden" name="edit_session_year_id" id="edit_session_year_id" value="" />
                        <div class="modal-body">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label>{{ __('name') }} <span class="text-danger">*</span></label>
                                    <input name="edit_name" id="edit_name" type="text"
                                        placeholder="{{ __('name') }}" class="form-control" required />
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ __('start_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="edit_start_date" id="edit_start_date"
                                        class="datepicker-popup form-control" placeholder="{{ __('start_date') }}"
                                        autocomplete="off" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="edit_end_date" id="edit_end_date"
                                        class="datepicker-popup form-control" placeholder="{{ __('end_date') }}"
                                        autocomplete="off" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">{{ __('close') }}</button>
                            <input class="btn btn-theme" type="submit" value="{{ __('edit') }} " />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function semesterQueryParams(params) {
            return {
                limit: params.limit,
                sort: params.sort,
                order: params.order,
                offset: params.offset,
                search: params.search,
                session_year_id: $('#semester_session_year').val(),
            };
        }

        function semesterStatusFormatter(value) {
            return value
                ? '<span class="badge rounded-pill badge-soft-success text-success px-3 py-2">{{ __('yes') }}</span>'
                : '<span class="badge rounded-pill badge-soft-danger text-danger px-3 py-2">{{ __('no') }}</span>';
        }

        $(document).ready(function() {
            const semesterShowUrl = "{{ url('semester/show') }}";
            const semesterStoreUrl = "{{ route('semester.store') }}";
            const semesterUpdateUrl = "{{ url('semester') }}";
            const classConfigUrl = "{{ url('semester/class-config') }}";

            let currentSessionYearId = $('#semester_session_year').val();
            let assignedClasses = [];
            let hasSemesters = false;

            // Load initial data
            applySessionYearDateLimits();
            loadAssignedClasses();
            // Initialize table with URL and trigger first load
            loadSemesters();

            // Session year change
            $('#semester_session_year').on('change', function() {
                currentSessionYearId = $(this).val();
                applySessionYearDateLimits();
                loadAssignedClasses();
                loadSemesters();
            });

            // ===================== ASSIGNED CLASSES =====================

            function loadAssignedClasses() {
                $.ajax({
                    url: classConfigUrl,
                    type: 'GET',
                    data: {
                        session_year_id: currentSessionYearId
                    },
                    success: function(response) {
                        assignedClasses = response.data || [];
                        renderAssignedClassesBadges();
                    },
                    error: function() {
                        $('#assigned-classes-badges').html(
                            '<span class="text-muted">{{ __('error_loading_classes') }}</span>');
                    }
                });
            }

            function renderAssignedClassesBadges() {
                const container = $('#assigned-classes-badges');
                container.empty();
                if (assignedClasses.length === 0) {
                    container.html('<span class="text-muted">{{ __('no_classes_configured') }}</span>');
                    return;
                }
                assignedClasses.forEach(function(cls) {
                    const mediumText = cls.medium_name ? ' (' + cls.medium_name + ')' : '';
                    container.append(
                        '<span class="badge badge-semester-class mr-2 mb-1">' +
                        cls.name + mediumText +
                        '</span>'
                    );
                });
            }

            // ===================== CONFIGURE CLASSES MODAL =====================

            $('#btn-configure-classes').on('click', function() {
                if (!hasSemesters) {
                    showWarningToast('{{ __('create_semester_before_configuring') }}');
                    return;
                }
                // Reset search
                $('#class-search-input').val('');
                $('.class-list-item').show();

                // Check currently assigned classes
                $('.class-checkbox').prop('checked', false);
                assignedClasses.forEach(function(cls) {
                    $('.class-checkbox[value="' + cls.id + '"]').prop('checked', true);
                });
                renderSelectedChips();
                $('#configureClassesModal').modal('show');
            });

            // Search filter
            $('#class-search-input').on('input', function() {
                const search = $(this).val().toLowerCase();
                $('.class-list-item').each(function() {
                    const name = $(this).data('class-name').toString().toLowerCase();
                    const medium = ($(this).data('medium-name') || '').toString().toLowerCase();
                    $(this).toggle(name.indexOf(search) > -1 || medium.indexOf(search) > -1);
                });
            });

            // Checkbox change
            $(document).on('change', '.class-checkbox', function() {
                renderSelectedChips();
            });

            function renderSelectedChips() {
                const container = $('#selected-classes-chips');
                container.empty();
                $('.class-checkbox:checked').each(function() {
                    const item = $(this).closest('.class-list-item');
                    const id = $(this).val();
                    const name = item.data('class-name');
                    const medium = item.data('medium-name') || '';
                    const label = name + (medium ? ' (' + medium + ')' : '');
                    container.append(
                        '<span class="chip-badge">' +
                        label +
                        ' <span class="chip-remove" data-id="' + id + '">&times;</span>' +
                        '</span>'
                    );
                });
            }

            // Remove chip
            $(document).on('click', '.chip-remove', function() {
                const id = $(this).data('id');
                $('.class-checkbox[value="' + id + '"]').prop('checked', false);
                renderSelectedChips();
            });

            // Save class config
            $('#btn-save-class-config').on('click', function() {
                const selectedIds = [];
                $('.class-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                const btn = $(this);
                btn.prop('disabled', true).text('{{ __('saving') }}...');

                $.ajax({
                    url: classConfigUrl,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        session_year_id: currentSessionYearId,
                        class_ids: selectedIds
                    },
                    success: function(response) {
                        if (!response.error) {
                            $('#configureClassesModal').modal('hide');
                            loadAssignedClasses();
                            loadSemesters();
                            showSuccessToast(response.message);
                        } else {
                            showErrorToast(response.message);
                        }
                    },
                    error: function() {
                        showErrorToast('{{ __('error_occurred') }}');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('{{ __('save') }}');
                    }
                });
            });

            // ===================== SEMESTERS TABLE =====================

            function loadSemesters() {
                $('#create_session_year_id').val(currentSessionYearId);
                $('#edit_session_year_id').val(currentSessionYearId);

                const $table = $('#semester-table');
                if ($table.data('bootstrap.table')) {
                    // Already initialized — just refresh
                    $table.bootstrapTable('refresh', { silent: true });
                } else {
                    // First call — initialize with url
                    $table.bootstrapTable({
                        url: semesterShowUrl,
                    });
                }
            }

            $('#semester-table').on('load-success.bs.table', function(e, data) {
                hasSemesters = data?.total > 0;
                updateConfigureButtonState();
            });

            function updateConfigureButtonState() {
                const btn = $('#btn-configure-classes');
                const warning = $('#no-semester-config-warning');
                if (hasSemesters) {
                    btn.removeClass('btn-disabled-configure');
                    btn.prop('title', '');
                    warning.addClass('d-none');
                } else {
                    btn.addClass('btn-disabled-configure');
                    btn.prop('title', '{{ __('create_semester_before_configuring') }}');
                    warning.removeClass('d-none');
                }
            }

            // ===================== CREATE SEMESTER =====================

            $('#semester-create-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const btn = form.find('#semester-create-btn');
                btn.prop('disabled', true);

                $.ajax({
                    url: semesterStoreUrl,
                    type: 'POST',
                    data: form.serialize() + '&_token={{ csrf_token() }}',
                    success: function(response) {
                        if (!response.error) {
                            $('#createSemesterModal').modal('hide');
                            form[0].reset();
                            loadSemesters();
                            showSuccessToast(response.message);
                        } else {
                            showErrorToast(response.message);
                        }
                    },
                    error: function() {
                        showErrorToast('{{ __('error_occurred') }}');
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                    }
                });
            });

            // ===================== EDIT SEMESTER =====================

            $(document).on('click', '.btn-edit-semester', function() {
                const btn = $(this);
                $('#edit_id').val(btn.data('id'));
                $('#edit_name').val(btn.data('name'));
                applySessionYearDateLimits();
                $('#edit_start_date').datepicker('update', formatDate(btn.data('start')));
                $('#edit_end_date').datepicker('update', formatDate(btn.data('end')));
                $('#editModal').modal('show');
            });

            $('#semester-edit-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const editId = $('#edit_id').val();

                $.ajax({
                    url: semesterUpdateUrl + '/' + editId,
                    type: 'POST',
                    data: form.serialize() + '&_token={{ csrf_token() }}&_method=PUT',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (!response.error) {
                            $('#editModal').modal('hide');
                            loadSemesters();
                            showSuccessToast(response.message);
                        } else {
                            showErrorToast(response.message);
                        }
                    },
                    error: function() {
                        showErrorToast('{{ __('error_occurred') }}');
                    }
                });
            });

            // ===================== DELETE SEMESTER =====================

            $(document).on('click', '.btn-delete-semester', function() {
                const id = $(this).data('id');
                const related = $(this).data('related');
                let extraHtml = '';
                if (Array.isArray(related) && related.length > 0) {
                    extraHtml = `
                    <p class="text-danger mt-2">
                        Deleting this semester will also remove related data such as 
                        <strong>${related.join(', ')}</strong> if they exist. If you will delete all semesters then semester config with class will be reset.
                    </p>
                    `
                }
                Swal.fire({
                    title: "{{ __('delete_title') }}",
                    icon: 'warning',
                    html: `
                        <p>{{ __('you_wont_be_able_to_revert_this') }}</p>
                        ${extraHtml}
                    `,
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: "{{ __('yes_delete') }}",
                    cancelButtonText: "{{ __('cancel') }}"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: semesterUpdateUrl + '/' + id,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (!response.error) {
                                    loadSemesters();
                                    loadAssignedClasses();
                                    showSuccessToast(response.message);
                                } else {
                                    showErrorToast(response.message);
                                }
                            },
                            error: function() {
                                showErrorToast('{{ __('error_occurred') }}');
                            }
                        });
                    }
                });
            });

            // ===================== HELPERS =====================

            function formatDate(dateStr) {
                if (!dateStr) return '-';
                const d = new Date(dateStr);
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                return day + '-' + month + '-' + d.getFullYear();
            }

            function parseIsoDate(dateStr) {
                if (!dateStr) return null;
                const parts = dateStr.split('-');
                if (parts.length !== 3) return null;
                const year = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10) - 1;
                const day = parseInt(parts[2], 10);
                if (Number.isNaN(year) || Number.isNaN(month) || Number.isNaN(day)) return null;
                return new Date(year, month, day);
            }

            function applySessionYearDateLimits() {
                const selectedOption = $('#semester_session_year option:selected');
                const sessionStart = parseIsoDate(selectedOption.data('start-date'));
                const sessionEnd = parseIsoDate(selectedOption.data('end-date'));
                const fields = $('input[name="start_date"], input[name="end_date"], #edit_start_date, #edit_end_date');

                fields.each(function() {
                    const $field = $(this);
                    $field.datepicker('setStartDate', sessionStart);
                    $field.datepicker('setEndDate', sessionEnd);
                });
            }

            function showSuccessToast(msg) {
                $.toast({
                    text: msg,
                    showHideTransition: 'slide',
                    icon: 'success',
                    position: 'top-right'
                });
            }

            function showErrorToast(msg) {
                $.toast({
                    text: msg,
                    showHideTransition: 'slide',
                    icon: 'error',
                    position: 'top-right'
                });
            }

            function showWarningToast(msg) {
                $.toast({
                    text: msg,
                    showHideTransition: 'slide',
                    icon: 'warning',
                    position: 'top-right',
                    loaderBg: '#f5a623'
                });
            }
        });
    </script>
@endsection

@section('css')
    <style>
        /* Session Year Dropdown - match system form-control */
        .semester-session-dropdown {
            min-width: 180px;
        }

        .semester-manage-header {
            justify-content: start;
            align-items: flex-start !important;
        }

        /* Info Banner - match system alert-info */
        .semester-info-banner {
            background-color: #d1e8f9;
            border: 1px solid #bfdef7;
            border-radius: 0.1875rem;
            padding: 0.875rem 1.25rem;
            font-size: 0.875rem;
            color: #0d4876;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .semester-info-banner i {
            font-size: 1rem;
            color: #0d4876;
        }

        /* Cards - match system .card */
        .semester-config-card,
        .semester-list-card {
            border: 0;
            border-radius: 0.3125rem;
        }

        /* Config divider */
        .config-divider {
            margin: 0.75rem 0;
            border-color: #ebedf2;
        }

        /* Assigned classes section */
        .assigned-classes-section {
            background: #f8f9fa;
            border: 1px dashed #d8dce1;
            border-radius: 0.3125rem;
            padding: 0.75rem 1rem;
            min-height: 42px;
            display: flex;
            align-items: center;
        }

        #assigned-classes-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }

        /* Config hint */
        .config-hint {
            font-size: 0.8rem;
            color: #9c9fa6;
            display: flex;
            align-items: center;
        }

        .config-hint i {
            color: #c9a84c;
            font-size: 0.85rem;
        }

        /* Class badges - match system .badge */
        .badge-semester-class {
            background: #ffffff;
            color: #343a40;
            border: 1px solid #6c757d;
            font-size: 75%;
            padding: 0.45em 0.6em;
            border-radius: 0.25rem;
        }

        /* Configure Classes Modal */
        .class-chips-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            min-height: 10px;
        }

        .chip-badge {
            background: linear-gradient(135deg, #f2edf3, #e8e0f0);
            color: #343a40;
            font-size: 0.8125rem;
            padding: 0.4em 0.75em;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
            border: 1px solid #ddd4e0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            transition: all 0.2s ease;
        }

        .chip-badge:hover {
            background: linear-gradient(135deg, #e8e0f0, #ddd4e0);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .chip-remove {
            cursor: pointer;
            font-size: 0.75rem;
            line-height: 1;
            color: #999;
            font-weight: bold;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.08);
            transition: all 0.2s ease;
        }

        .chip-remove:hover {
            background: #fe7096;
            color: #fff;
        }

        .class-search-wrapper {
            position: relative;
        }

        .class-search-wrapper i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 0.875rem;
        }

        .class-search-wrapper input {
            padding-left: 35px;
        }

        .class-list-container {
            max-height: 280px;
            overflow-y: auto;
            border: 1px solid #ebedf2;
            border-radius: 0.1875rem;
        }

        .class-list-item {
            display: flex;
            align-items: center;
            padding: 0.625rem 0.875rem;
            cursor: pointer;
            border-bottom: 1px solid #ebedf2;
            margin-bottom: 0;
            font-weight: normal;
            transition: background 0.15s;
        }

        .class-list-item:last-child {
            border-bottom: none;
        }

        .class-list-item:hover {
            background: #f2edf3;
        }

        .class-list-item input[type="checkbox"] {
            display: none;
        }

        .class-list-item .custom-check {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid #c3bdbd;
            margin-right: 12px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
        }

        .class-list-item input:checked~.custom-check {
            background: var(--theme-color);
            border-color: var(--theme-color);
        }

        .class-list-item input:checked~.custom-check::after {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #fff;
        }

        .class-list-item .class-name {
            flex: 1;
            font-size: 0.875rem;
            color: #343a40;
        }

        .class-list-item .class-medium {
            font-size: 0.8125rem;
            color: #6c757d;
            background: #f2edf3;
            padding: 0.15em 0.5em;
            border-radius: 0.25rem;
        }

        /* No-semester warning banner inside config card */
        .no-semester-warning {
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 0.25rem;
            padding: 0.6rem 0.9rem;
            font-size: 0.8125rem;
            color: #856404;
            margin-bottom: 0.75rem;
        }

        .no-semester-warning i {
            color: #e67e22;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        /* Visually-disabled configure button (pointer-events kept so toast fires) */
        .btn-disabled-configure {
            opacity: 0.5;
            cursor: not-allowed !important;
            pointer-events: auto !important;
            filter: grayscale(40%);
        }
    </style>
@endsection
