"use strict";

/**
 * Gets the semester ID associated with a subject select element.
 * For core subjects, the semester select is in the same row.
 * For elective subjects, the semester select is at the group level.
 * Returns empty string if semesters are not enabled or not selected.
 */
function getSemesterForSubject(element) {
    var $el = $(element);
    var semesterSelect;

    // For core subjects - semester select is in the same row
    semesterSelect = $el.closest('.row').find('select[name*="[semester_id]"]');
    if (semesterSelect.length) {
        return semesterSelect.val() || '';
    }

    // For elective subjects - semester select is at the group level
    var groupDiv = $el.closest('.edit-elective-subject-group-div, .elective-subject-group-div');
    if (groupDiv.length) {
        semesterSelect = groupDiv.find('select[name*="[semester_id]"]');
        if (semesterSelect.length) {
            return semesterSelect.val() || '';
        }
    }

    return '';
}

$.validator.addMethod("noDuplicateValues", function (value, element, options) {
    if (typeof options.class != "undefined" && options.class != null) {
        let components = (typeof options.group != "undefined" && options.group != "") ? $("." + options.class + "[data-group='" + options.group + "']") : $("." + options.class);
        let arrayValues = [];

        components.each(function (index, el) {
            if ($(el).val() !== "" && $(el).attr('name') !== $(element).attr('name')) {
                // Create a composite key of subject_id + semester_id
                let elSemester = getSemesterForSubject(el);
                arrayValues.push($(el).val() + '_' + elSemester);
            }
        })

        let currentSemester = getSemesterForSubject(element);
        let currentKey = value + '_' + currentSemester;
        return !arrayValues.includes(currentKey);
    }
    return false;
}, trans("Duplicate values are not allowed"));

// End date must be greater than or equal to start date
$.validator.addMethod("greaterThanStart", function (value, element, startFieldName) {
    var startVal = $('input[name="' + startFieldName + '"]').val();
    if (!value || !startVal) {
        return true; // Let 'required' handle empty fields
    }
    return value >= startVal;
}, trans("end_date_should_be_date_after_start_date"));

// Date must be within session year range
$.validator.addMethod("withinSessionYear", function (value, element) {
    if (!value || typeof sessionYearDates === 'undefined') {
        return true;
    }
    // Find the session year select in the same form context
    var form = $(element).closest('form');
    var sessionYearId = form.find('select[name="session_year_id"]').val();
    if (!sessionYearId || !sessionYearDates[sessionYearId]) {
        return true;
    }
    var range = sessionYearDates[sessionYearId];
    return value >= range.start && value <= range.end;
}, trans("date_must_be_within_session_year_range"));

function errorPlacement(label, element) {
    label.addClass('mt-2 text-danger');
    if (label.text()) {
        if (element.is(":radio") || element.is(":checkbox")) {
            label.insertAfter(element.parent().parent().parent());
        } else if (element.is(":file")) {
            label.insertAfter(element.siblings('div'));
        } else if (element.hasClass('color-picker')) {
            label.insertAfter(element.parent());
        } else {
            label.insertAfter(element);
        }
    }

}

function highlight(element, errorClass) {
    if ($(element).hasClass('color-picker')) {
        $(element).parent().parent().addClass('has-danger')
    } else {
        $(element).parent().addClass('has-danger')
    }

    $(element).addClass('form-control-danger')
}

$.validator.addMethod("uniqueSubjectIds", function (value, element) {
    let subjectKeys = {};
    let isValid = true;
    $('.subject').each(function () {
        let subjectId = $(this).val();
        if (subjectId !== "") {
            // Create a composite key of subject_id + semester_id
            let semesterId = getSemesterForSubject(this);
            let key = subjectId + '_' + semesterId;
            if (subjectKeys[key]) {
                isValid = false;
                $(this).addClass('has-danger');
                subjectKeys[key].addClass('form-control-danger');
            } else {
                subjectKeys[key] = $(this);
                $(this).removeClass('has-danger');
            }
        }
    });
    return isValid;
}, "");


$(".medium-create-form").validate({
    rules: {
        'name': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".medium-edit-form").validate({
    rules: {
        'username': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".section-create-form").validate({
    rules: {
        'username': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".section-edit-form").validate({
    rules: {
        'username': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".class-create-form").validate({
    rules: {
        'name': "required",
        'medium_id': "required",
        'section_id[]': "required",
    },
    success: function (label, element) {

        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".class-edit-form").validate({
    rules: {
        'name': "required",
        'medium_id': "required",
        'section_id[]': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".subject-create-form").validate({
    rules: {
        'medium_id': "required",
        'name': "required",
        'bg_color': "required",
        image: {
            required: true,
            extension: "png|jpg|jpeg|svg"
        },
        'type': "required",
    },
    success: function (label, element) {
        if ($(element).attr("name") == "bg_color") {
            $(element).parent().parent().removeClass('has-danger')
        } else {
            $(element).parent().removeClass('has-danger')
            $(element).removeClass('form-control-danger')
        }
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});
$(".assign-class-subject-form").validate({
    rules: {
        'class_id': "required",
        'core_subject_id[0]': {
            required: true,
            uniqueSubjectIds: true
        },
        // 'elective_subject_id[0][0]': "required",
        'total_selectable_subjects[]': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$("#formdata").validate({
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    },
});
$("#editdata").validate({
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    },
});

$(".assign-class-teacher-form").validate({
    rules: {
        'class_section_id': "required",
        'teacher_id': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".edit-class-teacher-form").validate({
    rules: {
        'class_section_id': "required",
        'teacher_id': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".student-registration-form").validate({
    rules: {
        'first_name': "required",
        'last_name': "required",
        'mobile': "number",
        'image': {
            required: true,
            extension: "jpg|jpeg|png",
            filesize: 2048
        },
        'dob': "required",
        'class_section_id': "required",
        'category_id': "required",
        'admission_no': "required",
        'roll_number': "required",
        // 'caste': "required",
        // 'religion': "required",
        'admission_date': "required",
        'blood_group': "required",
        // 'height': "required",
        // 'weight': "required",
        'current_address': "required",
        'permanent_address': "required",
        'father_first_name': "required",
        'father_last_name': "required",
        'father_email': {
            "email": true,
            "required": true,
        },
        'father_mobile': {
            "number": true,
            "required": true,
        },
        'father_occupation': "required",
        'father_dob': "required",
        'father_image': {
            extension: "jpg|jpeg|png",
            filesize: 2048
        },

        'mother_email': {
            "required": true,
            "email": true,
        },
        'mother_first_name': "required",
        'mother_last_name': "required",
        'mother_mobile': {
            "number": true,
            "required": true,
        },
        'mother_occupation': "required",
        'mother_dob': "required",
        'mother_image': {
            extension: "jpg|jpeg|png",
            filesize: 2048
        },
        'guardian_email': {
            "required": true,
            "email": true,
        },
        'guardian_first_name': "required",
        'guardian_last_name': "required",
        'guardian_mobile': {
            "number": true,
            "required": true,
        },
        'guardian_occupation': "required",
        'guardian_dob': "required",
        // 'guardian_image': {
        //     extension: "jpg|jpeg|png",
        //     filesize: 2048
        // },
    },
    messages: {
        'image': {
            extension: "Please select a valid image file (JPEG, JPG, or PNG)",
            filesize: "Image size should not be greater than 2048 KB"
        },
        'father_image': {
            extension: "Please select a valid image file (JPEG, JPG, or PNG)",
            filesize: "Image size should not be greater than 2048 KB"
        },
        'mother_image': {
            extension: "Please select a valid image file (JPEG, JPG, or PNG)",
            filesize: "Image size should not be greater than 2048 KB"
        },
        'guardian_image': {
            extension: "Please select a valid image file (JPEG, JPG, or PNG)",
            filesize: "Image size should not be greater than 2048 KB"
        }
    },
    success: function (label, element) {
        // add the success class to the input field
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".edit-student-registration-form").validate({
    rules: {
        'first_name': "required",
        'last_name': "required",
        'dob': "required",
        'class_id': "required",
        'class_section_id': "required",
        'category_id': "required",
        'admission_no': "required",
        'roll_number': "required",
        // 'caste': "required",
        // 'religion': "required",
        'admission_date': "required",
        'blood_group': "required",
        // 'height': "required",
        // 'weight': "required",
        'address': "required",

        'father_email': "required",
        'father_first_name': "required",
        'father_last_name': "required",
        'father_mobile': {
            "number": true,
            "required": true,
        },
        'father_occupation': "required",
        'father_dob': "required",

        'mother_email': "required",
        'mother_first_name': "required",
        'mother_last_name': "required",
        'mother_mobile': {
            "number": true,
            "required": true,
        },
        'mother_occupation': "required",
        'mother_dob': "required",

        'guardian_email': "required",
        'guardian_first_name': "required",
        'guardian_last_name': "required",
        'guardian_mobile': {
            "number": true,
            "required": true,
        },
        'guardian_occupation': "required",
        'guardian_dob': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".add-lesson-form").validate({
    rules: {
        'class_section_id': "required",
        'subject_id': "required",
        'name': "required",
        'description': "required",
        'file[0][name]': "required",
        'file[0][thumbnail]': "required",
        'file[0][file]': "required",
        'file[0][link]': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

//Added this Event here because this form has dynamic input fields.
// $('.add-lesson-form').on('submit', function () {
//     var file = $('[name^="file"]');
//     file.filter('input').each(function (key, data) {
//         $(this).rules("add", {
//             required: true,
//         });
//     });
//     file.filter('input[name$="[name]"]').each(function (key, data) {
//         $(this).rules("add", {
//             required: true,
//         });
//     });
// })

$(".edit-lesson-form").validate({
    rules: {
        'class_section_id': "required",
        'subject_id': "required",
        'name': "required",
        'description': "required",
        'edit_file[0][name]': "required",
        'edit_file[0][link]': "required",
        'file[0][name]': "required",
        'file[0][thumbnail]': "required",
        'file[0][file]': "required",
        'file[0][link]': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".add-topic-form").validate({
    rules: {
        'class_section_id': "required",
        'subject_id': "required",
        'lesson_id': "required",
        'name': "required",
        'description': "required",
        'file[0][name]': "required",
        'file[0][thumbnail]': "required",
        'file[0][file]': "required",
        'file[0][link]': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".edit-topic-form").validate({
    rules: {
        'class_section_id': "required",
        'subect_id': "required",
        'name': "required",
        'description': "required",
        'edit_file[0][name]': "required",
        'edit_file[0][link]': "required",
        'file[0][name]': "required",
        'file[0][thumbnail]': "required",
        'file[0][file]': "required",
        'file[0][link]': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".add-exam-form").validate({
    rules: {
        'class_id[]': "required",
        'name': "required",
        'timetable[0][subject_id]': "required",
        'timetable[0][total_marks]': "required",
        'timetable[0][passing_marks]': "required",
        'timetable[0][start_time]': "required",
        'timetable[0][end_time]': "required",
        'timetable[0][date]': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        if (element.hasClass('select2-hidden-accessible')) {
            label.insertAfter(element.next('span.select2'));
        } else {
            label.insertAfter(element);
        }
        label.addClass('mt-2 text-danger');
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".add-assignment-form").validate({
    rules: {
        'class_section_id': "required",
        'subject_id': "required",
        'name': "required",
        'due_date': "required",
        'extra_days_for_resubmission': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".edit-assignment-form").validate({
    rules: {
        'class_section_id': "required",
        'subject_id': "required",
        'name': "required",
        'due_date': "required",
        'extra_days_for_resubmission': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

//End Time Custom Validation
$.validator.addMethod("timeGreaterThan", function (value, element, params) {
    let startTime = $(params).val();
    let endTime = $(element).val();
    return endTime > startTime;
}, "End time should be greater than Start time.");

$(".subject-edit-form").validate({
    rules: {
        'medium_id': "required",
        'name': "required",
        'bg_color': "required",
        image: {
            extension: "png|jpg|jpeg|svg",
        },
        'type': "required",
    },
    success: function (label, element) {
        if ($(element).attr("name") == "bg_color") {
            $(element).parent().parent().removeClass('has-danger')
        } else {
            $(element).parent().removeClass('has-danger')
            $(element).removeClass('form-control-danger')
        }
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});
$('.form-validation').validate({
    success: function (label, element) {
        if ($(element).attr("name") == "bg_color") {
            $(element).parent().parent().removeClass('has-danger')
        } else {
            $(element).parent().removeClass('has-danger')
            $(element).removeClass('form-control-danger')
        }
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});
$(".student-export").validate({
    rules: {
        'class_section_id': "required",
        'date': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".student-import").validate({
    rules: {
        'class_section_id': "required",
        'file': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$("#create-form-fields").validate({
    rules: {
        'name': "required",
        'type': "required",
        'for': "required"
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$("#edit-form-fields").validate({
    rules: {
        'edit_name': "required",
        'edit-type': "required",
        'edit_for': "required",
        'default_values[]': "required",
    },
    groups: {
        default_values_group: 'default_values[]'
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".create-notification").validate({
    rules: {
        'send_to': "required",
        'user': "required",
        'type': "required",
        'url': "required",
        'title': "required",
        'message': "required"
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".assign_subject_teacher").validate({
    rules: {
        'class_section_id': "required",
        'session_year_id': "required",
        'subject_id': "required",
        'teacher_id[]': "required",
    },
    errorPlacement: function (label, element) {
        if (element.hasClass('select2-hidden-accessible')) {
            label.insertAfter(element.next('span.select2'));
        } else {
            label.insertAfter(element);
        }
        label.addClass('mt-2 text-danger');
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    },
});

$(".create-bulk-data").validate({
    rules: {
        'class_section_id': "required",
        'file': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".sliders-create-form").validate({
    rules: {
        'image': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".create-fees-type").validate({
    rules: {
        'name': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".admin-profile-update").validate({
    rules: {
        'first_name': "required",
        'last_name': "required",
        'mobile': "required",
        'gender': "required",
        'image': {
            extension: "jpg|jpeg|png"
        },
        'dob': "required",
        'father_email': {
            "email": true,
            "required": true,
        },

    },
    messages: {
        'image': {
            extension: "Please select a valid image file (JPEG, JPG, or PNG)"
        }
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".category-form").validate({
    rules: {
        'name': "required",
        'status': "required"
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".edit-parent-form").validate({
    rules: {
        'first_name': "required",
        'last_name': "required",
        'gender': "required",
        'dob': "required",
        'email': "required",
        'mobile': "required",
        'occupation': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".edit-holiday").validate({
    rules: {
        'date': "required",
        'title': "required"
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".edit-event").validate({
    rules: {
        'date': "required",
        'title': "required"
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".edit-announcement").validate({
    rules: {
        'title': "required"
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".edit-fees-type").validate({
    rules: {
        'name': "required"
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".staff-form").validate({
    rules: {
        'role_id': "required",
        'first_name': "required",
        'last_name': "required",
        'gender': "required",
        'image': {
            required: true,
            extension: "jpg|jpeg|png",
            filesize: 2048
        },
        'dob': "required",
        'address': "required"
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".staff-edit-form").validate({
    rules: {
        'role_id': "required",
        'first_name': "required",
        'last_name': "required",
        'gender': "required",
        'dob': "required",
        'address': "required"
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".create-online-exam").validate({
    rules: {
        'online_exam_based_on': "required",
        'class_id': 'required',
        'subject_class_id': 'required',
        'title_class': 'required',
        'exam_key_class': 'required',
        'duration_class': 'required',
        'start_date_class': {
            required: true,
            withinSessionYear: true
        },
        'end_date_class': {
            required: true,
            greaterThanStart: 'start_date_class',
            withinSessionYear: true
        },

        'class_section_id': 'required',
        'subject_class_section_id': 'required',
        'title_class_section': 'required',
        'exam_key_class_section': 'required',
        'duration_class_section': 'required',
        'start_date_class_section': {
            required: true,
            withinSessionYear: true
        },
        'end_date_class_section': {
            required: true,
            greaterThanStart: 'start_date_class_section',
            withinSessionYear: true
        },

    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$("#edit-form.edit-form").validate({
    rules: {
        'edit_title': 'required',
        'edit_exam_key': 'required',
        'edit_duration': 'required',
        'edit_start_date': 'required',
        'edit_end_date': {
            required: true,
            greaterThanStart: 'edit_start_date'
        },
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".general-setting").validate({
    rules: {
        'favicon': {
            extension: "jpg|jpeg|png|svg"
        },
        'logo1': {
            extension: "jpg|jpeg|png|svg"
        },
        'logo2': {
            extension: "jpg|jpeg|png|svg"
        },
        'login_image': {
            extension: "jpg|jpeg|png|svg"
        },

    },
    messages: {
        'favicon': {
            extension: "Please select a valid image file (JPEG, JPG, PNG or SVG)"
        },
        'logo1': {
            extension: "Please select a valid image file (JPEG, JPG, PNG or SVG)"
        },
        'logo2': {
            extension: "Please select a valid image file (JPEG, JPG, PNG or SVG)"
        },
        'login_image': {
            extension: "Please select a valid image file (JPEG, JPG, PNG or SVG)"
        },

    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});
$(".edit-content").validate({
    rules: {
        'tag': 'required',
        'heading': 'required',
        'content': 'required',
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".edit-program").validate({
    rules: {
        'title': 'required',
        'image': 'required'
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".event-form").validate({
    rules: {
        'title': 'required',
        'event_type': 'required',
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".id-card-setting").validate({
    rules: {
        'header_color': 'required',
        'footer_color': 'required',
        'header_footer_text_color': 'required',
        'layout_type': 'required',
        'profile_image_style': 'required',
        'card_width': 'required',
        'card_height': 'required',
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".generate-document").validate({
    rules: {
        'reason': "required"
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".leaving-document").validate({
    rules: {
        'reason': "required",
        'promoted_to': "required"
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".leave-setting").validate({
    rules: {
        'total_leave': 'required',
        'holiday_days[]': 'required',
        'session_year': 'required'
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".create-leave").validate({
    rules: {
        'reason': 'required',
        'from_date': 'required',
        'to_date': 'required'
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        errorPlacement(label, element);
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".create_exam_timetable_form").validate({
    rules: {
        'exam_id': "required",
        'class_id': "required",
        'timetable[0][subject_id]': "required",
        'timetable[0][total_marks]': "required",
        'timetable[0][passing_marks]': "required",
        'timetable[0][start_time]': "required",
        'timetable[0][end_time]': "required",
        'timetable[0][date]': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        if (element.hasClass('select2-hidden-accessible')) {
            label.insertAfter(element.next('span.select2'));
        } else {
            label.insertAfter(element);
        }
        label.addClass('mt-2 text-danger');
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$(".edit-form-timetable").validate({
    rules: {
        'edit_timetable[0][subject_id]': "required",
        'edit_timetable[0][total_marks]': "required",
        'edit_timetable[0][passing_marks]': "required",
        'edit_timetable[0][start_time]': "required",
        'edit_timetable[0][end_time]': "required",
        'edit_timetable[0][date]': "required",
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        if (element.hasClass('select2-hidden-accessible')) {
            label.insertAfter(element.next('span.select2'));
        } else {
            label.insertAfter(element);
        }
        label.addClass('mt-2 text-danger');
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});


$("#chat-delete").validate({
    rules: {
        'from_date': "required",
        'to_date': "required"
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        if (element.hasClass('select2-hidden-accessible')) {
            label.insertAfter(element.next('span.select2'));
        } else {
            label.insertAfter(element);
        }
        label.addClass('mt-2 text-danger');
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});

$("#chat-delete").validate({
    rules: {
        'from_date': "required",
        'to_date': "required"
    },
    success: function (label, element) {
        $(element).parent().removeClass('has-danger')
        $(element).removeClass('form-control-danger')
    },
    errorPlacement: function (label, element) {
        if (element.hasClass('select2-hidden-accessible')) {
            label.insertAfter(element.next('span.select2'));
        } else {
            label.insertAfter(element);
        }
        label.addClass('mt-2 text-danger');
    },
    highlight: function (element, errorClass) {
        highlight(element, errorClass);
    }
});
