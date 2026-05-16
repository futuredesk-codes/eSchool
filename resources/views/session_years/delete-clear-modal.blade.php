<!-- Delete/Clear Session Data Modal -->
<div class="modal fade" id="deleteSessionModal" tabindex="-1" role="dialog" aria-labelledby="deleteSessionModalLabel"
    aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content delete-session-modal-content">
            <!-- Step 1: Delete or Clear Choice -->
            <div class="modal-step" id="step-1">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="deleteSessionModalLabel">Delete or Clear Session Data</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="session-year-label mb-3">Session Year: <span class="session-year-value"></span></p>

                    <p class="mb-3 font-weight-medium">Choose how you want to handle this session year:</p>

                    <div class="choice-option mb-3">
                        <label class="custom-radio-container">
                            <input type="radio" name="delete_option" value="delete_entire" checked>
                            <span class="radio-checkmark"></span>
                            <div class="radio-content">
                                <div class="radio-title">Delete Entire Session</div>
                                <div class="radio-description">Permanently remove this session year and all associated
                                    data</div>
                            </div>
                        </label>
                    </div>

                    <div class="choice-option">
                        <label class="custom-radio-container">
                            <input type="radio" name="delete_option" value="clear_specific">
                            <span class="radio-checkmark"></span>
                            <div class="radio-content">
                                <div class="radio-title">Clear Specific Data</div>
                                <div class="radio-description">Choose which data types to remove while keeping the
                                    session</div>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-gradient-success continue-btn"
                        id="continueBtn">Continue</button>
                </div>
            </div>

            <!-- Step 2: Delete Entire Session Confirmation -->
            <div class="modal-step" id="step-2" style="display: none;">
                <div class="modal-header border-0">
                    <div class="d-flex align-items-start w-100">
                        <div class="delete-icon-container mr-3">
                            <i class="fa fa-trash"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="modal-title mb-1">Delete Entire Session?</h5>
                            <p class="session-year-label mb-0">Session Year: <span class="session-year-value"></span>
                            </p>
                        </div>
                        <button type="button" class="close ml-3" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="warning-box mb-3">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-exclamation-triangle warning-icon mr-2"></i>
                            <div>
                                <div class="warning-title">Warning:</div>
                                <div class="warning-text">This action cannot be undone. All data associated with this
                                    session year will be permanently deleted, including</div>
                            </div>
                        </div>
                    </div>

                    <ul class="delete-items-list" id="relatedDataList">
                        <!-- Will be populated dynamically -->
                    </ul>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-gradient-danger delete-session-btn" id="deleteSessionBtn">
                        <i class="fa fa-trash"></i> Delete Session
                    </button>
                </div>
            </div>

            <!-- Step 3: Clear Specific Data -->
            <div class="modal-step" id="step-3" style="display: none;">
                <div class="modal-header border-0">
                    <div class="d-flex align-items-start w-100">
                        <div class="delete-icon-container mr-3">
                            <i class="fa fa-trash"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="modal-title mb-1">Clear Session Data</h5>
                            <p class="session-year-label mb-0">Session Year: <span class="session-year-value"></span>
                            </p>
                        </div>
                        <button type="button" class="close ml-3" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="warning-box mb-3">
                        <div class="d-flex align-items-start">
                            <i class="fa fa-exclamation-triangle warning-icon mr-2"></i>
                            <div>
                                <div class="warning-title">Warning:</div>
                                <div class="warning-text">This action cannot be undone. All selected data will be
                                    permanently deleted from this session year.</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="section-title">Select Data to Clear</div>
                            <div class="section-subtitle">Choose which data types you want to remove</div>
                        </div>
                        <button type="button" class="btn btn-link select-all-btn" id="selectAllBtn">Select
                            All</button>
                    </div>

                    {{-- <div class="clear-data-options">
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="class_subject">
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Class Subject</div>
                                <div class="checkbox-description">Remove all class subject assignments</div>
                            </div>
                        </label>

                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="class_teachers">
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Class Teachers & Subject Teachers</div>
                                <div class="checkbox-description">Clear all teacher assignments and mappings</div>
                            </div>
                        </label>

                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="fees_transactions">
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Fees Transactions and Fee Details</div>
                                <div class="checkbox-description">Delete all fee-related records and transactions</div>
                            </div>
                        </label>

                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="notifications">
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Notifications</div>
                                <div class="checkbox-description">Remove all notification records</div>
                            </div>
                        </label>

                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="attendance">
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Student Attendance</div>
                                <div class="checkbox-description">Clear all student attendance records</div>
                            </div>
                        </label>

                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="assignments">
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Assignments</div>
                                <div class="checkbox-description">Remove all assignments and submissions</div>
                            </div>
                        </label>

                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="exams">
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Exams, Grades, and Results</div>
                                <div class="checkbox-description">Delete all exam and result data</div>
                            </div>
                        </label>

                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="timetables">
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Timetables, Events, and Announcements</div>
                                <div class="checkbox-description">Clear all timetables, events, and announcements</div>
                            </div>
                        </label>
                    </div> --}}

                    <div class="clear-data-options">

                        {{-- Class Subject --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="class_subject"
                                {{ in_array('class_subject', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Class Subject</div>
                                <div class="checkbox-description">Remove all class subject assignments</div>
                            </div>
                        </label>

                        {{-- Class Teachers & Subject Teachers --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="class_teachers"
                                {{ in_array('class_teachers', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Class Teachers & Subject Teachers</div>
                                <div class="checkbox-description">Clear all teacher assignments and mappings</div>
                            </div>
                        </label>

                        {{-- Fees Transactions and Fee Details --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="fees_transactions"
                                {{ in_array('fees_transactions', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Fees Transactions and Fee Details</div>
                                <div class="checkbox-description">Delete all fee-related records and transactions</div>
                            </div>
                        </label>

                        {{-- Notifications --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="notifications"
                                {{ in_array('notifications', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Notifications</div>
                                <div class="checkbox-description">Remove all notification records</div>
                            </div>
                        </label>

                        {{-- Timetable --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="timetable"
                                {{ in_array('timetable', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Timetable</div>
                                <div class="checkbox-description">Clear all timetable entries</div>
                            </div>
                        </label>

                        {{-- Student Attendance --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="attendance"
                                {{ in_array('attendance', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Student Attendance</div>
                                <div class="checkbox-description">Clear all student attendance records</div>
                            </div>
                        </label>

                        {{-- Assignments --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="assignments"
                                {{ in_array('assignments', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Assignment</div>
                                <div class="checkbox-description">Remove all assignment data</div>
                            </div>
                        </label>

                        {{-- Online / Offline Exam --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="exams"
                                {{ in_array('exams', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Online / Offline Exam</div>
                                <div class="checkbox-description">Clear all exam records and results</div>
                            </div>
                        </label>

                        {{-- Grade --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="grades"
                                {{ in_array('grades', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Grade</div>
                                <div class="checkbox-description">Delete all grade and grading data</div>
                            </div>
                        </label>

                        {{-- Announcements --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="announcements"
                                {{ in_array('announcements', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Announcements</div>
                                <div class="checkbox-description">Remove all announcements</div>
                            </div>
                        </label>

                        {{-- Holidays --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="holidays"
                                {{ in_array('holidays', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Holidays</div>
                                <div class="checkbox-description">Clear all holiday records</div>
                            </div>
                        </label>

                        {{-- Events --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="events"
                                {{ in_array('events', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Events</div>
                                <div class="checkbox-description">Delete all event records</div>
                            </div>
                        </label>

                        {{-- Student and Staff Leave --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="leaves"
                                {{ in_array('leaves', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Student and Staff Leave</div>
                                <div class="checkbox-description">Remove all leave applications and records</div>
                            </div>
                        </label>

                        {{-- Allowed Leave Days --}}
                        <label class="custom-checkbox-container">
                            <input type="checkbox" name="clear_data[]" value="allowed_leave_days"
                                {{ in_array('allowed_leave_days', old('clear_data', [])) ? 'checked' : '' }}>
                            <span class="checkbox-checkmark"></span>
                            <div class="checkbox-content">
                                <div class="checkbox-title">Allowed Leave Days</div>
                                <div class="checkbox-description">Clear leave day configurations</div>
                            </div>
                        </label>

                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-gradient-danger clear-data-btn" id="clearDataBtn" disabled>
                        <i class="fa fa-trash"></i> Clear Data
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
