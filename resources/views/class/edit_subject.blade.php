@extends('layouts.master')

@section('title')
    {{ __('class') . ' ' . __('subject') }}
@endsection

@section('content')
    @php($includeSemestersForSession = $class->getIncludeSemestersForSession($sessionYearId))
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('edit') . ' ' . __('class') . ' ' . __('subject') }}
            </h3>
        </div>
        <div class="alert alert-danger" role="alert">
            <i class="fa fa-exclamation-triangle"></i> {{ __('note') }}: {{ __('editing_class_subjects_may_affect_students_data_as_students_may_associated_with_the_subjects') }}
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        {{-- <form class="pt-3 assign-class-subject-form" id="create-form" data-pre-submit-function="classValidation" action="{{ route('class.subject.update') }}"  method="POST" novalidate="novalidate"> --}}
                        <form class="pt-3 assign-class-subject-form" id="create-form-with-redirect"
                            data-pre-submit-function="classValidation" action="{{ route('class.subject.update') }}"
                            novalidate="novalidate" data-redirect-url="{{ route('class.subject') }}">

                            <input type="hidden" name="class_id" id="class_id" value="{{ $class->id }}" />
                            <div class="modal-body">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>{{ __('class') }} <span class="text-danger">*</span></label>
                                        <input name="class_name" type="text" id="edit_class_id" class="form-control"
                                            value="{{ $class->name . ' - ' . $class->medium->name }} {{ $class->streams->name ?? ' ' }}"
                                            readonly />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>{{ __('include_semesters') }} <span class="text-danger">*</span></label>
                                        <input name="include_semesters" type="text" id="include_semesters"
                                            class="form-control" value="{{ $includeSemestersForSession === null ? 'Not Configured' : ($includeSemestersForSession ? 'Yes' : 'No') }}"
                                            readonly />
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <h4 title="{{ __('core_subject_compulsory') }}" class="mb-3">
                                            {{ __('core_subject') }}
                                            <span class="fa fa-info-circle pl-2"></span>
                                        </h4>
                                        {{-- Template for old core subject --}}
                                        <div class="row edit-core-subject-div" style="display: none;">
                                            @if ($includeSemestersForSession)
                                                <div class="col-5 semester-div">
                                                    <div class="form-group">
                                                        <label for="semester_id" class="d-none"></label>
                                                        <select name="edit_core_subject[0][semester_id]"
                                                            class="form-control edit-core-subject-semester-id" required
                                                            disabled="true">
                                                            <option value="" hidden="">--
                                                                {{ __('Select Semester') }} --</option>
                                                            @foreach ($semesters as $semester)
                                                                <option value="{{ $semester->id }}">{{ $semester->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <input type="hidden" name="edit_core_subject[0][class_subject_id]"
                                                        class="edit-class-subject-id form-control" disabled="true">
                                                    <select name="edit_core_subject[0][subject_id]"
                                                        class="edit-core-subject-id form-control subject"
                                                        required="required" disabled="true">
                                                        <option value="">{{ __('select_subject') }}</option>
                                                        @foreach ($subjects as $subject)
                                                            <option value="{{ $subject->id }}"
                                                                data-medium-id="{{ $subject->medium_id }}">
                                                                {{ $subject->name }} - {{ $subject->type }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-1 pl-0">
                                                <button type="button" data-id="123"
                                                    class="btn btn-icon btn-inverse-danger remove-core-subject"
                                                    data-related='["online exams","online exam questions","elective subject group"]'
                                                    title="{{ __('remove_core_subject') }}">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Template for New Core Subject --}}
                                        <div class="row core-subject-div" style="display: none;">
                                            @if ($includeSemestersForSession)
                                                <div class="col-5">
                                                    <div class="form-group">
                                                        <select name="core_subjects[0][semester_id]"
                                                            class="form-control select2" disabled="true" required>
                                                            <option value="" hidden="">--
                                                                {{ __('Select Semester') }} --</option>
                                                            @foreach ($semesters as $semester)
                                                                <option value="{{ $semester->id }}">{{ $semester->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="col-6">
                                                <div class="form-group">
                                                    <select name="core_subjects[0][subject_id]"
                                                        class="core-subject-id form-control subject" required="required"
                                                        disabled="true">
                                                        <option value="">{{ __('select_subject') }}</option>
                                                        @foreach ($subjects as $subject)
                                                            <option value="{{ $subject->id }}"
                                                                data-medium-id="{{ $subject->medium_id }}">
                                                                {{ $subject->name }} - {{ $subject->type }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-1 pl-0">
                                                <button type="button"
                                                    class="btn btn-inverse-success btn-icon add-core-subject"
                                                    title="{{ __('add_new_core_subject') }}">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Dynamic New Core Subject will be added in this DIV --}}
                                        <div class="mt-3 edit-extra-core-subjects"></div>
                                        <div>
                                            <div class="form-group pl-0 mt-4">
                                                <button type="button"
                                                    class="col-md-3 btn btn-inverse-success add-new-core-subject">
                                                    {{ __('core_subject') }} <i class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <hr>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <h4 class="mb-4" title="{{ __('elective_subject_choiceable') }}">
                                            {{ __('elective_subject') }} <span class="fa fa-info-circle pl-2"></span>
                                        </h4>
                                        {{-- Template for Old Elective Subjects --}}
                                        <div id="edit-elective-subject-group-div" class="edit-elective-subject-group-div"
                                            style="display: none;">
                                            <input type="hidden" name="edit_elective_subjects[0][subject_group_id]"
                                                class="edit-elective-subject-group-id form-control" disabled="true" />
                                            <div class="row col d-flex align-items-center">
                                                <h5 class="mb-0 group-no">{{ __('group') }}</h5>
                                                <i
                                                    class="fa fa-2x fa-times-circle text-left pl-1 pr-0  text-danger remove-elective-subject-group"></i>
                                            </div>

                                            <div class="form-group row">
                                                @if ($includeSemestersForSession)
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <select name="edit_elective_subjects[0][semester_id]"
                                                                class="form-control select2 edit-elective-subject-semester-id"
                                                                disabled="true" required>
                                                                <option value="" hidden="">--
                                                                    {{ __('Select Semester') }} --</option>
                                                                @foreach ($semesters as $semester)
                                                                    <option value="{{ $semester->id }}">
                                                                        {{ $semester->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="form-group row">
                                                <div class="col-3 align-items-end elective-subject-div">
                                                    <input type="hidden"
                                                        name="edit_elective_subjects[0][class_subject_id][0]"
                                                        class="edit-elective-subject-class-id form-control"
                                                        disabled="true" />
                                                    <select name="edit_elective_subjects[0][subject_id][0]"
                                                        class="form-control edit-elective-subject-name subject"
                                                        disabled="true" required="required">
                                                        <option value="">{{ __('select_subject') }}</option>
                                                        @foreach ($subjects as $subject)
                                                            <option value="{{ $subject->id }}"
                                                                data-medium-id="{{ $subject->medium_id }}">
                                                                {{ $subject->name }} - {{ $subject->type }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <i class='fa fa-times-circle text-danger col text-right pl-1 pr-0 remove-elective-subject'
                                                        id="remove-elective-subject" style="visibility: hidden;"></i>
                                                </div>
                                                <span class='mt-3 or'>{{ __('or') }}</span>
                                                <div class="col-3 align-items-end elective-subject-div">
                                                    <input type="hidden"
                                                        name="edit_elective_subjects[0][class_subject_id][1]"
                                                        class="edit-elective-subject-class-id form-control"
                                                        disabled="true" />
                                                    <select name="edit_elective_subjects[0][subject_id][1]"
                                                        class="form-control edit-elective-subject-name subject"
                                                        disabled="true" required="required">
                                                        <option value="">{{ __('select_subject') }}</option>
                                                        @foreach ($subjects as $subject)
                                                            <option value="{{ $subject->id }}"
                                                                data-medium-id="{{ $subject->medium_id }}">
                                                                {{ $subject->name }} - {{ $subject->type }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <i class='fa fa-times-circle text-danger col text-right pl-1 pr-0 remove-elective-subject'
                                                        id="remove-elective-subject" style="visibility: hidden;"></i>
                                                </div>
                                                <button type="button"
                                                    class="btn btn-inverse-success btn-icon add-new-elective-subject ml-3"
                                                    title="{{ __('add_new_elective_subject') }}" value="1">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                            <div class="form-group row">
                                                <div class="col-3">
                                                    <label>{{ __('total_selectable_subjects') }}
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <input name="edit_elective_subjects[0][total_selectable_subjects]"
                                                        type="number"
                                                        placeholder="{{ __('total_selectable_subjects') }}"
                                                        class="form-control edit-total-selectable-subject" min="1"
                                                        disabled="disabled" required />
                                                </div>
                                            </div>
                                            <hr>
                                        </div>

                                        {{-- Template for New Elective Subjects --}}
                                        <div id="elective-subject-group-div" class="elective-subject-group-div"
                                            style="display: none;">
                                            <div class="row col d-flex align-items-center">
                                                <h5 class="mb-0 group-no">{{ __('group') }}</h5>
                                                <i class="fa fa-2x text-left pl-1 pr-0 fa-times-circle text-danger remove-elective-subject-group"
                                                    data-related='[
                                                    "class subjects",
                                                    "student subject selections",
                                                    "online exams",
                                                    "online exam questions"
                                                ]'></i>
                                            </div>
                                            <div class="form-group row">
                                                @if ($includeSemestersForSession)
                                                    <div class="col-4">
                                                        <div class="form-group">
                                                            <select name="elective_subjects[0][semester_id]"
                                                                class="form-control select2 semesters" disabled="true"
                                                                required>
                                                                <option value="" hidden="">--
                                                                    {{ __('Select Semester') }} --</option>
                                                                @foreach ($semesters as $semester)
                                                                    <option value="{{ $semester->id }}">
                                                                        {{ $semester->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="form-group row">
                                                <div class="col-3 align-items-end elective-subject-div">
                                                    <select name="elective_subjects[0][subject_id][0]"
                                                        class="form-control elective-subject-name subject" disabled="true"
                                                        required="required">
                                                        <option value="">{{ __('select_subject') }}</option>
                                                        @foreach ($subjects as $subject)
                                                            <option value="{{ $subject->id }}"
                                                                data-medium-id="{{ $subject->medium_id }}">
                                                                {{ $subject->name }} - {{ $subject->type }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <i class='fa fa-times-circle text-danger col text-right pl-1 pr-0 remove-elective-subject'
                                                        id="remove-elective-subject" style="visibility: hidden;"></i>
                                                </div>
                                                <span class='mt-3 or'>{{ __('or') }}</span>
                                                <div class="col-3 align-items-end elective-subject-div">
                                                    <select name="elective_subjects[0][subject_id][1]"
                                                        class="form-control elective-subject-name subject" disabled="true"
                                                        required="required">
                                                        <option value="">{{ __('select_subject') }}</option>
                                                        @foreach ($subjects as $subject)
                                                            <option value="{{ $subject->id }}"
                                                                data-medium-id="{{ $subject->medium_id }}">
                                                                {{ $subject->name }} - {{ $subject->type }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <i class='fa fa-times-circle text-danger text-right pl-1 pr-0 remove-elective-subject'
                                                        id="remove-elective-subject" style="visibility: hidden;"></i>
                                                </div>
                                                <button type="button"
                                                    class="btn btn-inverse-success btn-icon add-new-elective-subject ml-3"
                                                    title="Add New Elective Subject" value="1">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                            <div class="form-group row">
                                                <div class="col-3">
                                                    <label>{{ __('total_selectable_subjects') }}
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <input name="elective_subjects[0][total_selectable_subjects]"
                                                        type="number"
                                                        placeholder="{{ __('total_selectable_subjects') }}"
                                                        class="form-control total-selectable-subject" min="1"
                                                        disabled="disabled" required />
                                                </div>
                                            </div>
                                            <hr>
                                        </div>
                                        {{-- Dynamic New Elective Subject Group will be added in this DIV --}}
                                        <div id="edit-extra-elective-subject-group"></div>
                                        <div>
                                            <div class="form-group pl-0 mt-4">
                                                <button type="button"
                                                    class="col-md-3 btn btn-inverse-success add-elective-subject-group">
                                                    {{ __('elective_subject') }} <i class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input class="btn btn-theme" type="submit" value={{ __('submit') }} />
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
        // $(document).on('change', '.subject', function() {
        //     // $("#create-form").validate().element($(this));
        // });

        $(document).ready(function() {

            @if ($class->coreSubject)
                var coreSubjects = @json($class->coreSubject);
                var tempIndex = 0;
                coreSubjects.forEach(function(coreSubject, index) {

                    let core_subject = cloneOldCoreSubjectTemplate();
                    //Fill the Values
                    core_subject.find('.remove-core-subject').attr('data-id', coreSubject.id);
                    core_subject.find('.edit-class-subject-id').val(coreSubject.id).prop('disabled', false);
                    core_subject.find('.edit-core-subject-id').val(coreSubject.subject_id).prop('disabled',
                        false);

                    if (coreSubject.semester_id) {
                        core_subject.find('.edit-core-subject-semester-id').val(coreSubject.semester_id)
                            .prop('disabled', false);
                    }
                    $('.edit-extra-core-subjects').append(core_subject);
                });
            @endif


            @if ($class->electiveSubjectGroup)
                var electiveSubjectGroups = @json($class->electiveSubjectGroup);

                electiveSubjectGroups.forEach(function(group, index) {
                    let subjectGroup = cloneOldElectiveSubjectGroup();
                    $('#edit-extra-elective-subject-group').append(subjectGroup);

                    // Fill the values in cloned element
                    subjectGroup.find('.edit-total-selectable-subject').val(group.total_selectable_subjects)
                        .attr('max', group.total_subjects - 1);
                    subjectGroup.find('.remove-elective-subject-group').attr('data-id', group.id);

                    // Set Group ID and Semester ID
                    subjectGroup.find('.edit-elective-subject-group-id').val(group.id);
                    subjectGroup.find('.edit-elective-subject-semester-id').val(group.semester_id);

                    var electiveSubjects = group.elective_subjects;

                    electiveSubjects.forEach(function(elective_subject, key) {
                        if (key === 0) {
                            subjectGroup.find('.edit-elective-subject-name:first').val(
                                elective_subject.subject_id);
                            subjectGroup.find('.edit-elective-subject-name:first').siblings(
                                '.edit-elective-subject-class-id').val(elective_subject.id);
                        } else if (key === 1) {
                            subjectGroup.find('.edit-elective-subject-name:eq(1)').val(
                                elective_subject.subject_id);
                            subjectGroup.find('.edit-elective-subject-name:eq(1)').siblings(
                                '.edit-elective-subject-class-id').val(elective_subject.id);
                        } else {
                            let electiveSubjectButton = subjectGroup.find(
                                '.add-new-elective-subject');
                            let electiveSubject = cloneNewElectiveSubject(electiveSubjectButton);
                            electiveSubject.insertBefore(subjectGroup.find(
                                '.add-new-elective-subject'));
                            electiveSubject.find('.edit-elective-subject-name').val(elective_subject
                                .subject_id);
                            electiveSubject.find('.edit-elective-subject-name').siblings(
                                '.edit-elective-subject-class-id').val(elective_subject.id);
                            electiveSubject.find('.edit-elective-subject-name').siblings(
                                '.remove-elective-subject').attr('data-id', elective_subject.id);
                        }
                    });

                    // Remove any extra cloned elements if there are less than 3 subjects
                    let totalSubjects = electiveSubjects.length;
                    let extraInputs = subjectGroup.find('.edit-elective-subject-name').length;

                    // Remove excess fields if not needed
                    if (totalSubjects < extraInputs) {
                        subjectGroup.find('.edit-elective-subject-name:gt(' + (totalSubjects - 1) + ')')
                            .closest('.elective-subject-div').remove();
                    }
                    if (totalSubjects === 2) {
                        subjectGroup.find('.or').last().remove();
                    }
                });
            @endif

            $('.semesters').trigger('change');

        });
    </script>
@endsection
