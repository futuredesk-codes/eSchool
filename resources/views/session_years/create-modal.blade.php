<div class="modal fade" id="createSessionYearModal" tabindex="-1" role="dialog"
    aria-labelledby="createSessionYearModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content create-session-year-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="createSessionYearModalLabel">Create New Session Year</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="createSessionYearForm">
                    @csrf
                    <!-- Basic Details Section -->
                    <div class="section-header">
                        <i class="fa fa-calendar"></i>
                        <span>Basic Details</span>
                    </div>

                    <div class="form-group">
                        <label for="sessionName">Session Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="sessionName" name="name"
                            placeholder="e.g., 2025-2026" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="startDate">Start Date <span class="text-danger">*</span></label>
                                <input type="text" class="form-control datepicker-popup" id="startDate"
                                    name="start_date" placeholder="Select start date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="endDate">End Date <span class="text-danger">*</span></label>
                                <input type="text" class="form-control datepicker-popup" id="endDate"
                                    name="end_date" placeholder="Select end date" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="feesDueDate">Fees Due Date <span class="text-danger">*</span></label>
                                <input type="text" class="form-control datepicker-popup" id="feesDueDate"
                                    name="fees_due_date" placeholder="Select fees due date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="feesDueCharges">Fees Due Charges (%) <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="feesDueCharges" name="fees_due_charges"
                                    placeholder="e.g., 5" min="1" max="100" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="freeAppUseDays">Free App Use Days</label>
                        <input type="text" class="form-control datepicker-popup" id="freeAppUseDays"
                            name="free_app_use_date" placeholder="Select date">
                    </div>

                    <!-- Fees Installment Settings Section -->
                    <div class="section-header">
                        <i class="fa fa-dollar"></i>
                        <span>Fees Installment Settings</span>
                    </div>

                    <div class="installment-toggle-wrapper">
                        <div class="installment-toggle-label">
                            <span>Enable Fees Installment</span>
                            <small>Allow students to pay fees in multiple installments</small>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="enableFeesInstallment" name="fees_installment" value="1">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <!-- Installment Content -->
                    <div id="installmentContent" style="display: none;">
                        <div id="installmentList">
                            <div class="installment-item" data-index="1">
                                <div class="installment-header">
                                    <span>Installment 1</span>
                                    <button type="button" class="btn-remove-installment" data-index="1">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                                <div class="form-group">
                                    <label>Installment Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="installment_data[1][name]"
                                        placeholder="e.g., First Installment">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Due Date <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control datepicker-popup"
                                                name="installment_data[1][due_date]" placeholder="Select due date">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Due Charges (%) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control"
                                                name="installment_data[1][due_charges]" placeholder="e.g., 2"
                                                min="1" max="100">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <button type="button" class="btn btn-add-installment mb-3" id="addInstallmentBtn">
                            <i class="fa fa-plus"></i> Add Installment
                        </button>
                    </div>

                    <!-- Data Transfer Options Section -->
                    <div class="section-header">
                        <i class="fa fa-database"></i>
                        <span>Data Transfer Options</span>
                        <!-- <span class="section-badge">From Previous Session</span> -->
                    </div>

                    <div class="data-transfer-options">

                        {{-- Source Session Year Picker (always visible) --}}
                        <div class="form-group" id="sourceSessionYearWrapper">
                            <label for="sourceSessionYear">
                                Import Data From Session Year <span class="text-danger">*</span>
                            </label>
                            <select class="form-control" id="sourceSessionYear" name="source_session_year_id">
                                <option value="">Select Session Year</option>
                                @if (!empty($sessionYears))
                                    @foreach ($sessionYears as $sy)
                                        <option value="{{ $sy->id }}">{{ $sy->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <small class="form-text text-muted">
                                Data for the checked options below will be copied from this session year into the new
                                one.
                            </small>
                        </div>

                        <label class="checkbox-option">
                            <input type="checkbox" name="transfer_semester" value="1" class="transfer-checkbox"
                                id="transferSemesterCheckbox">
                            <span>Semester</span>
                        </label>

                        <!-- Semester list (auto-populated from selected session year) -->
                        <div id="semesterTransferContent" style="display: none;">
                            <div id="semesterTransferList">
                                {{-- Populated dynamically via AJAX --}}
                            </div>
                            <p id="semesterEmptyMsg" class="text-muted small m-0" style="display: none;">
                                {{-- No semesters found for the selected session year. --}}
                            </p>
                            <p id="semesterLoadingMsg" class="text-muted small" style="display: none;">
                                <i class="fa fa-spinner fa-spin"></i> Loading semesters...
                            </p>
                        </div>

                        <label class="checkbox-option">
                            <input type="checkbox" name="transfer_class_subject" value="1"
                                class="transfer-checkbox">
                            <span>Class Subject</span>
                        </label>
                        <label class="checkbox-option">
                            <input type="checkbox" name="transfer_class_teacher_subject" value="1"
                                class="transfer-checkbox">
                            <span>Class Teacher and Subject Teacher</span>
                        </label>
                        <label class="checkbox-option">
                            <input type="checkbox" name="transfer_class_timetable" value="1"
                                class="transfer-checkbox">
                            <span>Class Timetable</span>
                        </label>
                        <label class="checkbox-option">
                            <input type="checkbox" name="transfer_exam_grades" value="1"
                                class="transfer-checkbox">
                            <span>Exam Grades</span>
                        </label>
                        <label class="checkbox-option">
                            <input type="checkbox" name="transfer_class_fee_type" value="1"
                                class="transfer-checkbox">
                            <span>Class and Fee Type Assignment</span>
                        </label>
                        <label class="checkbox-option">
                            <input type="checkbox" name="transfer_leave_settings" value="1"
                                class="transfer-checkbox">
                            <span>Leave Settings</span>
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-create-session-year btn-success" id="submitSessionYear">Create
                    Session
                    Year</button>
            </div>
        </div>
    </div>
</div>
