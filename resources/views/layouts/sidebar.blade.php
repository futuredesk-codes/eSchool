<!-- partial:../../partials/_sidebar.html -->
<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        {{-- Mobile-only: Show header controls at top of sidebar --}}
        @php
            $current_session_id_sidebar = getSettings('session_year')['session_year'];
            $current_session_sidebar = DB::table('session_years')
                ->select('id', 'name')
                ->where('id', $current_session_id_sidebar)
                ->whereNull('deleted_at')
                ->first();
            $session_years_sidebar = DB::table('session_years')->whereNull('deleted_at')->orderBy('id', 'ASC')->get();
            $filter_session_year_selected_sidebar = session('session_year', $current_session_id_sidebar);
        @endphp
        <li class="nav-item d-lg-none sidebar-mobile-header">
            <div class="sidebar-mobile-controls">
                @php
                    $email_config_verify_value = DB::table('settings')
                        ->select('message')
                        ->where('type', 'email_configration_verification')
                        ->first();
                    if ($email_config_verify_value) {
                        $message = $email_config_verify_value->message;
                    } else {
                        $message = 0;
                    }
                @endphp
                @if ($message == 0)
                    @can('email-setting-create')
                        <div>
                            <div class="mx-auto order-0 d-lg-none text-center">
                                <a href="{{ route('setting.email-config-index') }}"
                                    class="badge badge-pill badge-danger small-email-alert">
                                    <i class="fa fa-exclamation-circle"></i> Email setup required, tap to configure
                                </a>
                            </div>
                        </div>
                    @endcan
                @endif
                <div class="sidebar-mobile-session">
                    <select id="filter_session_year_global_mobile" data-scope="global"
                        class="session-year-dropdown filter_session_year sidebar-session-dropdown">
                        @foreach ($session_years_sidebar as $session_year)
                            <option value="{{ $session_year->id }}"
                                {{ $session_year->id == $filter_session_year_selected_sidebar ? 'selected' : '' }}>
                                {{ $session_year->name }}
                                @if ($current_session_sidebar && $session_year->id == $current_session_sidebar->id)
                                    - System Default
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sidebar-mobile-actions">
                    <a href="{{ url('clear') }}">
                        <input class="btn btn-inverse-info btn-sm" type="submit" value="{{ __('cache_clear') }}">
                    </a>
                </div>
            </div>
        </li>
        {{-- End mobile-only header controls --}}

        @php
            $isRoute = function (...$patterns) {
                foreach ($patterns as $pattern) {
                    if (request()->routeIs($pattern) || request()->is($pattern)) {
                        return true;
                    }
                }
                return false;
            };

            $academicsOpen = $isRoute(
                'medium.*',
                'section.*',
                'stream.*',
                'shifts.*',
                'subject.*',
                'semester.*',
                'class.*',
                'class.subject',
                'class.teacher',
                'subject-teachers.*',
                'students.assign-class',
                'promote-student.*',
            );
            $studentsOpen = $isRoute(
                'category.*',
                'students.create',
                'online-registration.*',
                'students.index-students-roll-number',
                'students.index',
                'generate_id.*',
                'student.result.*',
                'students.reset_password',
                'students.create-bulk-data',
            );
            $teacherOpen = $isRoute('teachers.*', 'teacher.*');
            $staffOpen = $isRoute('staff.*', 'roles*');
            $leaveOpen = $isRoute(
                'leave*',
                'leave-master.*',
                'leave-report.*',
                'leave-request.*',
                'staff-leave.*',
                'student-leave-request.*',
            );
            $timetableOpen = $isRoute('timetable.*', 'class-timetable*', 'teacher-timetable*');
            $attendanceOpen = $isRoute('attendance*', 'attendace_report.*');
            $subjectLessonOpen = $isRoute('lesson*', 'lesson-topic*');
            $assignmentOpen = $isRoute('assignment.*');
            $examOpen = $isRoute('exams.*', 'exam-timetable.*', 'grades');
            $feesOpen = $isRoute('fees-type.*', 'fees.class.*', 'fees.paid.*', 'fees.transactions.log.*');
            $onlineExamOpen = $isRoute('online-exam.*', 'online-exam-question.*');
            $galleryOpen = $isRoute('photo.*', 'video.*');
            $webSettingsOpen = $isRoute('content.*', 'educational.*', 'faq.*', 'contact_us.*');
            $settingsOpen = $isRoute(
                'app-settings*',
                'settings*',
                'language*',
                'fcm-settings*',
                'chat_setting.*',
                'fees.config.*',
                'email-settings*',
                'id-card-settings*',
                'privacy.*',
                'contact-us*',
                'about-us*',
                'terms-condition*',
            );
        @endphp
        {{-- dashboard --}}
        <li class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
            <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ url('/home') }}">
                <i class="fa fa-tachometer menu-icon" style="margin: 0 1px 0 1px"></i>
                <span class="menu-title">{{ __('dashboard') }}</span>
            </a>
        </li>

        @hasrole('Super Admin')
            {{-- academics --}}
            @canany(['medium-create', 'section-create', 'subject-create', 'class-create', 'subject-create',
                'class-teacher-create', 'subject-teacher-list', 'subject-teachers-create', 'assign-class-to-new-student',
                'promote-student-create'])
                <li class="nav-item {{ $academicsOpen ? 'active' : '' }}">
                    <a class="nav-link" data-toggle="collapse" href="#academics-menu"
                        aria-expanded="{{ $academicsOpen ? 'true' : 'false' }}" aria-controls="academics-menu">
                        <i class="fa fa-university menu-icon"></i><span class="menu-title">{{ __('academics') }}</span>
                        <i class="fa fa-angle-left fa-2xl menu-arrow"></i>
                    </a>
                    <div class="collapse {{ $academicsOpen ? 'show' : '' }}" id="academics-menu">
                        <ul class="nav flex-column sub-menu">
                            @can('medium-create')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('medium.*') ? 'active' : '' }}"
                                        href="{{ route('medium.index') }}"> {{ __('medium') }} </a>
                                </li>
                            @endcan

                            @can('section-create')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('section.*') ? 'active' : '' }}"
                                        href="{{ route('section.index') }}"> {{ __('section') }} </a>
                                </li>
                            @endcan

                            @can('stream-create')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('stream.*') ? 'active' : '' }}"
                                        href="{{ route('stream.index') }}"> {{ __('stream') }} </a>
                                </li>
                            @endcan

                            @can('shift-create')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('shifts.*') ? 'active' : '' }}"
                                        href="{{ route('shifts.index') }}"> {{ __('shifts') }} </a>
                                </li>
                            @endcan

                            @can('subject-create')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('subject.*') ? 'active' : '' }}"
                                        href="{{ route('subject.index') }}"> {{ __('subject') }} </a>
                                </li>
                            @endcan

                            @can('semester-create')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('semester.*') ? 'active' : '' }}"
                                        href="{{ route('semester.index') }}"> {{ __('semester') }} </a>
                                </li>
                            @endcan

                            @can('class-create')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('class') ? 'active' : '' }}"
                                        href="{{ route('class.index') }}"> {{ __('class') }} </a>
                                </li>
                            @endcan

                            @can('subject-create')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('class.subject') ? 'active' : '' }}"
                                        href="{{ route('class.subject') }}">{{ __('assign_class_subject') }} </a>
                                </li>
                            @endcan

                            @can('assign-elective-subjects')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('class.select-elective-subjects') ? 'active' : '' }}"
                                        href="{{ route('class.select-elective-subjects') }}">{{ __('assign_elective_subjects') }}
                                    </a>
                                </li>
                            @endcan

                            @can('class-teacher-create')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('class.teacher') ? 'active' : '' }}"
                                        href="{{ route('class.teacher') }}">
                                        {{ __('assign_class_teacher') }}
                                    </a>
                                </li>
                            @endcan

                            @canany(['subject-teacher-list', 'subject-teacher-create', 'subject-teacher-edit',
                                'subject-teacher-delete'])
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('subject-teachers.*') ? 'active' : '' }}"
                                        href="{{ route('subject-teachers.index') }}">
                                        {{ __('assign') . ' ' . __('subject') . ' ' . __('teacher') }}
                                    </a>
                                </li>
                            @endcan
                            @can('assign-class-to-new-student')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('students.assign-class') ? 'active' : '' }}"
                                        href="{{ route('students.assign-class') }}">
                                        {{ __('assign_new_student_class') }}
                                    </a>
                                </li>
                            @endcan
                            @can('promote-student-create')
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('promote-student.*') ? 'active' : '' }}"
                                        href="{{ route('promote-student.index') }}">
                                        {{ __('promote_student') }}
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </div>
                </li>
            @endcanany
        @endrole

        @can('form-field-create')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('form-fields.*') ? 'active' : '' }}"
                    href="{{ route('form-fields.index') }}">
                    <i class="fa fa-list-alt menu-icon"></i>
                    <span class="menu-title">{{ __('custom_fields') }}</span>
                </a>
            </li>
        @endcan

        {{-- student --}}
        @canany(['student-create', 'student-list', 'category-create', 'student-reset-password', 'class-teacher'])
            <li class="nav-item {{ $studentsOpen ? 'active' : '' }}">
                <a class="nav-link" data-toggle="collapse" href="#student-menu"
                    aria-expanded="{{ $studentsOpen ? 'true' : 'false' }}" aria-controls="academics-menu"><i
                        class="fa fa-graduation-cap menu-icon" style="padding: 8px"></i>
                    <span class="menu-title">{{ __('students') }}</span>
                    <i class="fa fa-angle-left menu-arrow"></i>
                </a>
                <div class="collapse {{ $studentsOpen ? 'show' : '' }}" id="student-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('category-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('category.*') ? 'active' : '' }}"
                                    href="{{ route('category.index') }}">
                                    {{ __('student_category') }}
                                </a>
                            </li>
                        @endcan

                        @can('student-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('students.create') ? 'active' : '' }}"
                                    href="{{ route('students.create') }}">
                                    {{ __('student_admission') }}
                                </a>
                            </li>
                        @endcan
                        @can('online-registration-list')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('online-registration.*') ? 'active' : '' }}"
                                    href="{{ route('online-registration.index') }}">
                                    {{ __('online_registrations') }}
                                </a>
                            </li>
                        @endcan
                        @can('student-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('students.index-students-roll-number') ? 'active' : '' }}"
                                    href="{{ route('students.index-students-roll-number') }}">
                                    {{ __('assign') }} {{ __('roll_no') }}
                                </a>
                            </li>
                        @endcan

                        @canany(['student-list', 'class-teacher', 'generate-document'])
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('students.index') ? 'active' : '' }}"
                                    href="{{ route('students.index') }}">
                                    {{ __('student_details') }}
                                </a>
                            </li>
                        @endcanany

                        @can('generate-id-card')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('generate_id.*') ? 'active' : '' }}"
                                    href="{{ route('generate_id.index') }}">
                                    {{ __('generate') . ' ' . __('id') . ' ' . __('card') }}
                                </a>
                            </li>
                        @endcan

                        @can('generate-result')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('student.result.*') ? 'active' : '' }}"
                                    href="{{ route('student.result.index') }}">
                                    {{ __('generate') . ' ' . __('result') }}
                                </a>
                            </li>
                        @endcan

                        @can('student-reset-password')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('students.reset_password') ? 'active' : '' }}"
                                    href="{{ route('students.reset_password') }}">
                                    {{ __('students') . ' ' . __('reset_password') }}
                                </a>
                            </li>
                        @endcan

                        @if (Auth::user()->hasRole('Super Admin'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('students.create-bulk-data') ? 'active' : '' }}"
                                    href="{{ route('students.create-bulk-data') }}">
                                    {{ __('add_bulk_data') }}
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </li>
        @endcanany

        {{-- teacher --}}
        @can(['teacher-create', 'teacher-list'])
            <li class="nav-item {{ $teacherOpen ? 'active' : '' }}">
                <a class="nav-link" data-toggle="collapse" href="#teacher-menu"
                    aria-expanded="{{ $teacherOpen ? 'true' : 'false' }}" aria-controls="academics-menu"><i
                        class="fa fa-user menu-icon" style="margin: 0 4px 0 4px"></i>
                    <span class="menu-title">{{ __('teacher') }}</span>
                    <i class="fa fa-angle-left menu-arrow"></i>
                </a>
                <div class="collapse {{ $teacherOpen ? 'show' : '' }}" id="teacher-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('teacher-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('teachers.*') ? 'active' : '' }}"
                                    href="{{ route('teachers.index') }}">
                                    {{ __('teacher_create') }}
                                </a>
                            </li>
                        @endcan
                        @can('teacher-list')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('teacher.*') ? 'active' : '' }}"
                                    href="{{ route('teacher.details') }}">
                                    {{ __('teacher_details') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endcan

        {{-- parents --}}
        @can('parents-create')
            <li class="nav-item">
                <a href="{{ route('parents.index') }}"
                    class="nav-link {{ request()->routeIs('parents.*') ? 'active' : '' }}">
                    <i class="fa fa-users menu-icon"></i>
                    <span class="menu-title">{{ __('parents') }}</span>
                </a>
            </li>
        @endcan

        {{-- Staff Management --}}
        @canany(['role-create', 'staff-create', 'staff-list'])
            <li class="nav-item {{ $staffOpen ? 'active' : '' }}">
                <a class="nav-link" data-toggle="collapse" href="#staff-menu"
                    aria-expanded="{{ $staffOpen ? 'true' : 'false' }}" aria-controls="settings-menu"><i
                        class="fa fa-user-secret menu-icon" style="margin: 0 2px 0 2px"></i>
                    <span class="menu-title">{{ __('staff') . ' ' . __('management') }}</span>
                    <i class="fa fa-angle-left menu-arrow"></i>
                </a>
                <div class="collapse {{ $staffOpen ? 'show' : '' }}" id="staff-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('role-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('roles*') ? 'active' : '' }}"
                                    href="{{ url('roles/') }}"> {{ __('role_permission') }}
                                </a>
                            </li>
                        @endcan
                        @can('staff-list')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('staff.*') ? 'active' : '' }}"
                                    href="{{ route('staff.index') }}"> {{ __('staff') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endcanany

        @canany(['leave-setting-create', 'leave-list', 'leave-create', 'leave-delete', 'leave-edit',
            'student-leave-approve'])
            <li class="nav-item {{ $leaveOpen ? 'active' : '' }}">
                <a class="nav-link" data-toggle="collapse" href="#leave-menu"
                    aria-expanded="{{ $leaveOpen ? 'true' : 'false' }}"><i class="fa fa-plane menu-icon"
                        style="margin: 0 2px 0 2px"></i>
                    <span class="menu-title">{{ __('leave') }}</span>
                    <i class="fa fa-angle-left menu-arrow"></i>
                </a>
                <div class="collapse {{ $leaveOpen ? 'show' : '' }}" id="leave-menu">
                    <ul class="nav flex-column sub-menu">

                        @can('leave-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('leave.index') ? 'active' : '' }}"
                                    href="{{ route('leave.index') }}"> {{ __('apply') . ' ' . __('leave') }}
                                </a>
                            </li>
                        @endcan
                        @can('leave-setting-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('leave-master.*') ? 'active' : '' }}"
                                    href="{{ route('leave-master.index') }}">{{ __('leave') . ' ' . __('setting') }}</a>
                            </li>
                        @endcan
                        @canany(['leave-list', 'leave-approve'])
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('leave-report.*') ? 'active' : '' }}"
                                    href="{{ route('leave-report.index') }}"> {{ __('leave') . ' ' . __('report') }}
                                </a>
                            </li>
                        @endcanany
                        @can('leave-approve')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('leave-request.*') ? 'active' : '' }}"
                                    href="{{ route('leave-request.index') }}">
                                    {{ __('staff') . ' ' . __('leave') . ' ' . __('requests') }}
                                </a>
                            </li>
                        @endcan
                        {{-- @canany('staff-leave-list')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('staff-leave.*') ? 'active' : '' }}" href="{{ route('staff-leave.index')}}"> {{__('staff').' '. __('leave')}}
                    </a>
        </li>
        @endcan --}}
                        @canany('student-leave-approve')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('student-leave-request.*') ? 'active' : '' }}"
                                    href="{{ route('student-leave-request.index') }}">
                                    {{ __('student') . ' ' . __('leave') . ' ' . __('requests') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endcanany

        {{-- timetable --}}
        @canany(['timetable-create', 'class-timetable', 'teacher-timetable'])
            <li class="nav-item {{ $timetableOpen ? 'active' : '' }}">
                <a class="nav-link" data-toggle="collapse" href="#timetable-menu"
                    aria-expanded="{{ $timetableOpen ? 'true' : 'false' }}" aria-controls="timetable-menu"> <i
                        class="fa fa-calendar menu-icon" style="margin: 0 1px 0 1px"></i>
                    <span class="menu-title">{{ __('timetable') }}</span>
                    <i class="fa fa-angle-left menu-arrow"></i>
                </a>
                <div class="collapse {{ $timetableOpen ? 'show' : '' }}" id="timetable-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('timetable-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('timetable.*') ? 'active' : '' }}"
                                    href="{{ route('timetable.index') }}">{{ __('create_timetable') }} </a>
                            </li>
                        @endcan
                        @canany(['class-timetable', 'class-teacher'])
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('class-timetable*') ? 'active' : '' }}"
                                    href="{{ url('class-timetable') }}">
                                    {{ __('class_timetable') }}
                                </a>
                            </li>
                        @endcanany
                        @can('teacher-timetable')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('teacher-timetable*') ? 'active' : '' }}"
                                    href="{{ url('teacher-timetable') }}">
                                    {{ __('teacher_timetable') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endcanany

        {{-- attendance --}}
        @canany(['class-teacher', 'attendance-report'])
            <li class="nav-item {{ $attendanceOpen ? 'active' : '' }}">
                <a class="nav-link" data-toggle="collapse" href="#attendance-menu"
                    aria-expanded="{{ $attendanceOpen ? 'true' : 'false' }}" aria-controls="attendance-menu"><i
                        class="fa fa-check menu-icon"></i>
                    <span class="menu-title">{{ __('attendance') }}</span>
                    <i class="fa fa-angle-left menu-arrow"></i>
                </a>
                <div class="collapse {{ $attendanceOpen ? 'show' : '' }}" id="attendance-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('attendance-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('attendance.index') ? 'active' : '' }}"
                                    href="{{ route('attendance.index') }}">
                                    {{ __('add_attendance') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('attendance.add-bulk-data') ? 'active' : '' }}"
                                    href="{{ route('attendance.add-bulk-data') }}">
                                    {{ __('add_bulk_data') }}
                                </a>
                            </li>
                        @endcan

                        {{-- view attendance --}}
                        @can('attendance-list')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('attendance.view') ? 'active' : '' }}"
                                    href="{{ route('attendance.view') }}">
                                    {{ __('view_attendance') }}
                                </a>
                            </li>
                        @endcan
                        @can('attendance-report')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('attendace_report.*') ? 'active' : '' }}"
                                    href="{{ route('attendace_report.index') }}">
                                    {{ __('attendance_report') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endcanany

        {{-- subject lesson --}}
        @canany(['lesson-list', 'lesson-create', 'lesson-edit', 'lesson-delete', 'topic-list', 'topic-create',
            'topic-edit', 'topic-delete'])
            <li class="nav-item {{ $subjectLessonOpen ? 'active' : '' }}">
                <a class="nav-link" data-toggle="collapse" href="#subject-lesson-menu"
                    aria-expanded="{{ $subjectLessonOpen ? 'true' : 'false' }}" aria-controls="subject-lesson-menu"><i
                        class="fa fa-book menu-icon"></i>
                    <span class="menu-title">{{ __('subject_lesson') }}</span> <i
                        class="fa fa-angle-left menu-arrow"></i></a>
                <div class="collapse {{ $subjectLessonOpen ? 'show' : '' }}" id="subject-lesson-menu">
                    <ul class="nav flex-column sub-menu">
                        @canany(['lesson-list', 'lesson-create', 'lesson-edit', 'lesson-delete'])
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('lesson') ? 'active' : '' }}"
                                    href="{{ url('lesson') }}"> {{ __('create_lesson') }}</a>
                            </li>
                        @endcanany

                        @canany(['topic-list', 'topic-create', 'topic-edit', 'topic-delete'])
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('lesson-topic*') ? 'active' : '' }}"
                                    href="{{ url('lesson-topic') }}"> {{ __('create_topic') }}</a>
                            </li>
                        @endcanany
                    </ul>
                </div>
            </li>
        @endcanany

        {{-- student assignment --}}
        @canany(['assignment-create', 'assignment-submission'])
            <li class="nav-item {{ $assignmentOpen ? 'active' : '' }}">
                <a class="nav-link" data-toggle="collapse" href="#student-assignment-menu"
                    aria-expanded="{{ $assignmentOpen ? 'true' : 'false' }}" aria-controls="student-assignment-menu"> <i
                        class="fa fa-tasks menu-icon"></i>
                    <span class="menu-title">{{ __('student_assignment') }}</span>
                    <i class="fa fa-angle-left menu-arrow"></i>
                </a>
                <div class="collapse {{ $assignmentOpen ? 'show' : '' }}" id="student-assignment-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('assignment-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('assignment.index') ? 'active' : '' }}"
                                    href="{{ route('assignment.index') }}">
                                    {{ __('create_assignment') }}
                                </a>
                            </li>
                        @endcan
                        @can('assignment-submission')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('assignment.submission') ? 'active' : '' }}"
                                    href="{{ route('assignment.submission') }}">
                                    {{ __('assignment_submission') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endcanany

        {{-- exam --}}
        @canany(['exam-create', 'exam-timetable-create', 'exam-upload-marks', 'grade-create'])
            <li class="nav-item {{ $examOpen ? 'active' : '' }}">
                <a class="nav-link" data-toggle="collapse" href="#exam-menu"
                    aria-expanded="{{ $examOpen ? 'true' : 'false' }}" aria-controls="exam-menu"><i
                        class="fa fa-file-text menu-icon" style="margin: 0 2px 0 2px"></i>
                    <span class="menu-title">{{ __('exam') }}</span>
                    <i class="fa fa-angle-left menu-arrow"></i>
                </a>
                <div class="collapse {{ $examOpen ? 'show' : '' }}" id="exam-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('exam-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('exams.index') ? 'active' : '' }}"
                                    href="{{ route('exams.index') }}"> {{ __('create_exam') }}
                                </a>
                            </li>
                        @endcan
                        @can('exam-timetable-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('exam-timetable.*') ? 'active' : '' }}"
                                    href="{{ route('exam-timetable.index') }}">
                                    {{ __('create_exam_timetable') }}
                                </a>
                            </li>
                        @endcan
                        @can('exam-upload-marks')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('exams.upload-marks') ? 'active' : '' }}"
                                    href="{{ route('exams.upload-marks') }}">
                                    {{ __('upload') }} {{ __('exam_marks') }}
                                </a>
                            </li>
                        @endcan
                        @can('view-exam-result')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('exams.view-result') ? 'active' : '' }}"
                                    href="{{ route('exams.view-result') }}">
                                    {{ __('exam_result') }}
                                </a>
                            </li>
                        @endcan
                        @can('exam-result')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('exams.get-result') ? 'active' : '' }}"
                                    href="{{ route('exams.get-result') }}">
                                    {{ __('students') }} {{ __('exam_result') }}
                                </a>
                            </li>
                        @endcan
                        @can('grade-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('grades') ? 'active' : '' }}"
                                    href="{{ route('grades') }}">
                                    {{ __('exam') }} {{ __('grade') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endcan

        {{-- Fees --}}
        @canany(['fees-type', 'fees-classes', 'fees-paid'])
            <li class="nav-item {{ $feesOpen ? 'active' : '' }}">
                <a class="nav-link" data-toggle="collapse" href="#fees-menu"
                    aria-expanded="{{ $feesOpen ? 'true' : 'false' }}" aria-controls="exam-menu"><i
                        class="fa fa-money menu-icon"></i>
                    <span class="menu-title">{{ __('fees') }}</span>
                    <i class="fa fa-angle-left menu-arrow"></i>
                </a>
                <div class="collapse {{ $feesOpen ? 'show' : '' }}" id="fees-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('fees-type')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('fees-type.*') ? 'active' : '' }}"
                                    href="{{ route('fees-type.index') }}"> {{ __('fees') }}
                                    {{ __('type') }}
                                </a>
                            </li>
                        @endcan
                        @can('fees-classes')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('fees.class.*') ? 'active' : '' }}"
                                    href="{{ route('fees.class.index') }}">{{ __('assign') }}
                                    {{ __('fees') }} {{ __('classes') }} </a>
                            </li>
                        @endcan
                        @can('fees-paid')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('fees.paid.*') ? 'active' : '' }}"
                                    href="{{ route('fees.paid.index') }}"> {{ __('fees') }}
                                    {{ __('paid') }}
                                </a>
                            </li>
                        @endcan

                        @can('fees-paid')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('fees.transactions.log.*') ? 'active' : '' }}"
                                    href="{{ route('fees.transactions.log.index') }}"> {{ __('fees') }}
                                    {{ __('transactions') }} {{ __('logs') }}
                                </a>
                            </li>
                        @endcan
                        {{-- @can('fees-paid')
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('fees.receipt') }}"> {{__('fees')}} {{ __('receipt') }} {{__('logs')}}
                </a>
    </li>
    @endcan --}}
                    </ul>
                </div>
            </li>
        @endcan

        @canany(['manage-online-exam'])
            <li class="nav-item {{ $onlineExamOpen ? 'active' : '' }}">
                <a class="nav-link" data-toggle="collapse" href="#online-exam-menu"
                    aria-expanded="{{ $onlineExamOpen ? 'true' : 'false' }}" aria-controls="online-exam-menu"> <i
                        class="fa fa-laptop menu-icon"></i>
                    <span class="menu-title">{{ __('online') }} {{ __('exam') }}</span>
                    <i class="fa fa-angle-left menu-arrow"></i>
                </a>
                <div class="collapse {{ $onlineExamOpen ? 'show' : '' }}" id="online-exam-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('manage-online-exam')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('online-exam.*') ? 'active' : '' }}"
                                    href="{{ route('online-exam.index') }}"> {{ __('manage') }}
                                    {{ __('online') }} {{ __('exam') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('online-exam-question.*') ? 'active' : '' }}"
                                    href="{{ route('online-exam-question.index') }}"> {{ __('manage') }}
                                    {{ __('questions') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('online-exam.terms-conditions') ? 'active' : '' }}"
                                    href="{{ route('online-exam.terms-conditions') }}">
                                    {{ __('terms_condition') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endcan

        {{-- notification --}}
        @can('notification-create')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}"
                    href="{{ route('notifications.index') }}">
                    <i class="fa fa-bell menu-icon"></i>
                    <span class="menu-title">{{ __('custom') . ' ' . __('notifications') }}</span>
                </a>
            </li>
        @endcan


        {{-- announcement --}}
        @can('announcement-create')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('announcement.*') ? 'active' : '' }}"
                    href="{{ route('announcement.index') }}">
                    <i class="fa fa-bullhorn menu-icon"></i>
                    <span class="menu-title">{{ __('announcement') }}</span>
                </a>
            </li>
        @endcan


        {{-- sliders --}}
        @can('slider-create')
            <li class="nav-item">
                <a href="{{ route('sliders.index') }}"
                    class="nav-link {{ request()->routeIs('sliders.*') ? 'active' : '' }}"> <i
                        class="fa fa-sliders menu-icon"></i>
                    <span class="menu-title">{{ __('sliders') }}</span></a>
            </li>
        @endcan

        {{-- Holiday --}}
        @canany(['holiday-create', 'holiday-list'])
            <li class="nav-item">
                @can('holiday-list')
                    <a href="{{ route('holiday.index') }}"
                        class="nav-link {{ request()->routeIs('holiday.*') ? 'active' : '' }}">
                        <i class="fa fa-calendar-check-o menu-icon"></i>
                        <span class="menu-title">{{ __('holiday_list') }}</span> </a>
                @endcan
            </li>
        @endcanany

        {{-- Events --}}
        @canany(['event-create'])
            <li class="nav-item">
                @can('holiday-list')
                    <a href="{{ route('events.index') }}"
                        class="nav-link {{ request()->routeIs('events.*') ? 'active' : '' }}">
                        <i class="fa fa-list-ul menu-icon"></i>
                        <span class="menu-title">{{ __('events') }}</span> </a>
                @endcan
            </li>
        @endcanany

        {{-- session-year --}}
        @can('session-year-create')
            <li class="nav-item">
                <a href="{{ route('session-years.index') }}"
                    class="nav-link {{ request()->routeIs('session-years.*') ? 'active' : '' }}">
                    <i class="fa fa-calendar-o menu-icon" style="margin: 0 1px 0 1px"></i>
                    <span class="menu-title">{{ __('session_years') }}</span>
                </a>
            </li>
        @endcan


        {{-- gallery --}}
        @canany(['media-create'])
            <li class="nav-item {{ $galleryOpen ? 'active' : '' }}">
                <a class="nav-link" data-toggle="collapse" href="#gallery-menu"
                    aria-expanded="{{ $galleryOpen ? 'true' : 'false' }}" aria-controls="gallery-menu">
                    {{-- <i class="fa fa-laptop menu-icon"></i> --}}
                    <i class="fa fa-photo menu-icon"></i>
                    <span class="menu-title">{{ __('gallery') }}</span>
                    <i class="fa fa-angle-left menu-arrow"></i>
                </a>
                <div class="collapse {{ $galleryOpen ? 'show' : '' }}" id="gallery-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('media-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('photo.*') ? 'active' : '' }}"
                                    href="{{ route('photo.index') }}">{{ __('photos') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('video.*') ? 'active' : '' }}"
                                    href="{{ route('video.index') }}"> {{ __('videos') }}</a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endcan

        {{-- web-settings --}}
        @canany(['content-create', 'event-create', 'program-create', 'faq-create', 'contact-us'])
            <li class="nav-item {{ $webSettingsOpen ? 'active' : '' }}">
                <a class="nav-link" data-toggle="collapse" href="#web-settings"
                    aria-expanded="{{ $webSettingsOpen ? 'true' : 'false' }}" aria-controls="settings-menu"><i
                        class="fa fa-wrench menu-icon" style="margin: 0 2px 0 2px"></i>
                    <span class="menu-title">{{ __('web_settings') }}</span>
                    <i class="fa fa-angle-left menu-arrow"></i>
                </a>
                <div class="collapse {{ $webSettingsOpen ? 'show' : '' }}" id="web-settings">
                    <ul class="nav flex-column sub-menu">
                        @can('content-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('content.*') ? 'active' : '' }}"
                                    href="{{ route('content.index') }}">{{ __('content_settings') }}</a>
                            </li>
                        @endcan
                        @can('program-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('educational.*') ? 'active' : '' }}"
                                    href="{{ route('educational.index') }}">{{ __('educational_program') }}</a>
                            </li>
                        @endcan
                        @can('faq-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('faq.*') ? 'active' : '' }}"
                                    href="{{ route('faq.index') }}"> {{ __('faqs') }}</a>
                            </li>
                        @endcan
                        @can('contact-us')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('contact_us.*') ? 'active' : '' }}"
                                    href="{{ route('contact_us.index') }}"> {{ __('contact_us') }}</a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endcan


        {{-- settings --}}
        @canany(['setting-create', 'fcm-setting-create', 'email-setting-create', 'privacy-policy', 'contact-us',
            'about-us', 'chat-message-delete', 'chat-settings'])
            <li class="nav-item {{ $settingsOpen ? 'active' : '' }}">
                <a class="nav-link" data-toggle="collapse" href="#settings-menu"
                    aria-expanded="{{ $settingsOpen ? 'true' : 'false' }}" aria-controls="settings-menu"><i
                        class="fa fa-gear menu-icon" style="margin: 0 2px 0 2px"></i>
                    <span class="menu-title">{{ __('system_settings') }}</span>
                    <i class="fa fa-angle-left menu-arrow"></i>
                </a>
                <div class="collapse {{ $settingsOpen ? 'show' : '' }}" id="settings-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('setting-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('app-settings*') ? 'active' : '' }}"
                                    href="{{ url('app-settings') }}">
                                    {{ __('app_settings') }}</a>
                            </li>
                        @endcan
                        @can('setting-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('settings*') ? 'active' : '' }}"
                                    href="{{ url('settings') }}">
                                    {{ __('general_settings') }}</a>
                            </li>
                        @endcan
                        @can('language-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('language*') ? 'active' : '' }}"
                                    href="{{ url('language') }}">
                                    {{ __('language_settings') }}</a>
                            </li>
                        @endcan
                        @can('fcm-setting-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('fcm-settings*') ? 'active' : '' }}"
                                    href="{{ url('fcm-settings') }}"> {{ __('notification') . ' ' . __('setting') }}
                                </a>
                            </li>
                        @endcan
                        @canany(['chat-message-delete', 'chat-settings'])
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('chat_setting.*') ? 'active' : '' }}"
                                    href="{{ route('chat_setting.index') }}">
                                    {{ __('chat_settings') }}</a>
                            </li>
                        @endcan
                        @can('fees-config')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('fees.config.*') ? 'active' : '' }}"
                                    href="{{ route('fees.config.index') }}"> {{ __('fees') }}
                                    {{ __('configration') }}
                                </a>
                            </li>
                        @endcan
                        @can('email-setting-create')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('email-settings*') ? 'active' : '' }}"
                                    href="{{ url('email-settings') }}">
                                    {{ __('email_configuration') }}
                                </a>
                            </li>
                        @endcan
                        @can('generate-id-card')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('id-card-settings*') ? 'active' : '' }}"
                                    href="{{ url('id-card-settings') }}">{{ __('student') . ' ' . __('id') . ' ' . __('card') . ' ' . __('setting') }}</a>
                            </li>
                        @endcan
                        @can('privacy-policy')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('privacy.*') ? 'active' : '' }}"
                                    href="{{ route('privacy.index') }}">
                                    {{ __('privacy_policy') }}
                                </a>
                            </li>
                        @endcan
                        @can('contact-us')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('contact-us*') ? 'active' : '' }}"
                                    href="{{ url('contact-us') }}"> {{ __('contact_us') }}
                                </a>
                            </li>
                        @endcan
                        @can('about-us')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('about-us*') ? 'active' : '' }}"
                                    href="{{ url('about-us') }}"> {{ __('about_us') }}
                                </a>
                            </li>
                        @endcan
                        @can('terms-condition')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('terms-condition*') ? 'active' : '' }}"
                                    href="{{ url('terms-condition') }}">
                                    {{ __('terms_condition') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
            @endif

            @if (Auth::user()->hasRole('Super Admin'))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('system-update.*') ? 'active' : '' }}"
                        href="{{ route('system-update.index') }}">
                        <i class="fa fa-cloud-download menu-icon"></i>
                        <span class="menu-title">{{ __('system_update') }}</span>
                    </a>
                </li>
            @endif

        </ul>
    </nav>
