"use strict";


// Bootstrap Custom Column Formatters
function fileFormatter(value, row) {
    let file_upload = "<br><h6>" + trans('file_upload') + "</h6>";
    let youtube_link = "<br><h6>" + trans('youtube_link') + "</h6>";
    let video_upload = "<br><h6>" + trans('video_upload') + "</h6>";
    let other_link = "<br><h6>" + trans('other_link') + "</h6>";

    let file_upload_counter = 1;
    let youtube_link_counter = 1;
    let video_upload_counter = 1;
    let other_link_counter = 1;

    $.each(row.file, function (key, data) {
        //1 = File Upload , 2 = Youtube , 3 = Uploaded Video , 4 = Other
        if (data.type == 1) {
            // 1 = File Ulopad
            file_upload += "<a href='" + data.file_url + "' target='_blank' >" + file_upload_counter + ". " + data.file_name + "</a><br>";
            file_upload_counter++;
        } else if (data.type == 2) {
            // 2 = Youtube Link
            youtube_link += "<a href='" + data.file_url + "' target='_blank' >" + youtube_link_counter + ". " + data.file_name + "</a><br>";
            youtube_link_counter++;
        } else if (data.type == 3) {
            // 3 = Uploaded Video
            video_upload += "<a href='" + data.file_url + "' target='_blank' >" + video_upload_counter + ". " + data.file_name + "</a><br>";
            video_upload_counter++;
        } else if (data.type == 4) {
            // 4 = Other Link
            other_link += "<a href='" + data.file_url + "' target='_blank' >" + other_link_counter + ". " + data.file_name + "</a><br>";
            other_link_counter++;
        }
    })
    let html = "";
    if (file_upload_counter > 1) {
        html += file_upload;
    }

    if (youtube_link_counter > 1) {
        html += youtube_link;
    }

    if (video_upload_counter > 1) {
        html += video_upload;
    }

    if (other_link_counter > 1) {
        html += other_link;
    }

    return html;
}

function resubmissionFormatter(value, row) {
    let html = "";
    if (row.resubmission) {
        html = "<span class='alert alert-success'>" + trans('yes') + "</span>";
    } else {
        html = "<span class='alert alert-danger'>" + trans('no') + "</span>";
    }
    return html;
}

function assignmentFileFormatter(value, row) {
    let html = "<a target='_blank' href='" + row.file + "'>" + row.name + "</a>";
    return html;
}

function assignmentSubmissionStatusFormatter(value, row) {
    let html = "";
    // 0 = Pending/In Review , 1 = Accepted , 2 = Rejected , 3 = Resubmitted
    if (row.status === 0) {
        html = "<span class='badge badge-warning'>" + trans('pending') + "</span>";
    } else if (row.status === 1) {
        html = "<span class='badge badge-success'>" + trans('accepted') + "</span>";
    } else if (row.status === 2) {
        html = "<span class='badge badge-danger'>" + trans('rejected') + "</span>";
    } else if (row.status === 3) {
        html = "<span class='badge badge-warning'>" + trans('resubmitted') + "</span>";
    }
    return html;
}

function imageFormatter(value, row) {
    if (row.image) {
        return "<a data-toggle='lightbox' href='" + row.image + "'><img src='" + row.image + "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'  /></a>";
    } else {
        return '-'
    }
}

function notificationImageFormatter(value, row) {
    if (row.image) {
        const imageURL = 'storage/' + row.image;
        return "<a data-toggle='lightbox' href='" + imageURL + "'><img src='" + imageURL + "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'  /></a>";
    } else {
        return '-'
    }
}

function thumbnailFormatter(value, row) {
    if (row.thumbnail) {
        return "<a data-toggle='lightbox' href='" + row.thumbnail + "'><img src='" + row.thumbnail + "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'  /></a>";
    } else {
        return '-'
    }
}

function fatherImageFormatter(value, row) {
    if (row.father_image) {
        return "<a data-toggle='lightbox' href='" + row.father_image + "'><img src='" + row.father_image + "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'/></a>";
    }
}

function motherImageFormatter(value, row) {
    if (row.mother_image) {
        return "<a data-toggle='lightbox' href='" + row.mother_image + "'><img src='" + row.mother_image + "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'/></a>";
    }
}

function guardianImageFormatter(value, row) {
    if (row.guardian_image) {
        return "<a data-toggle='lightbox' href='" + row.guardian_image + "'><img src='" + row.guardian_image + "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'/></a>";
    }
}

function examTimetableFormatter(value, row) {
    let html = []
    if (row.timetable.length != null) {
        $.each(row.timetable, function (key, timetable) {
            html.push('<p>' + timetable.subject.name + '(' + timetable.subject.type + ')  - ' + timetable.total_marks + '/' + timetable.passing_marks + ' - ' + timetable.start_time + ' - ' + timetable.end_time + ' - ' + moment(timetable.date, 'YYYY-MM-DD').format('DD-MM-YYYY') + '</p>')
        });
    }
    return html.join('')
}

function examSubjectFormatter(index, row) {
    if (row.subject_name) {
        return row.subject_name;
    } else {
        return $('#subject_id :selected').text();
    }
}

function examStudentNameFormatter(index, row) {
    return "<input type='hidden' name='exam_marks[" + row.no + "][student_id]' class='form-control' value='" + row.student_id + "' />" + row.student_name
}

function obtainedMarksFormatter(index, row) {
    if (row.obtained_marks) {
        return "<input type='hidden' name='exam_marks[" + row.no + "][exam_marks_id]' class='form-control' value='" + row.exam_marks_id + "' />" +
            "<input type='number' name='exam_marks[" + row.no + "][obtained_marks]' class='form-control' min='0' value='" + row.obtained_marks + "' />" + "<input type='hidden' name='exam_marks[" + row.no + "][total_marks]' class='form-control' value='" + parseInt(row.total_marks) + "' />"
    } else {
        return "<input type='hidden' name='exam_marks[" + row.no + "][exam_marks_id]' class='form-control' value='" + row.exam_marks_id + "' />" +
            "<input type='number' name='exam_marks[" + row.no + "][obtained_marks]' class='form-control' min='0' value='" + ' ' + "' />" +
            "<input type='hidden' name='exam_marks[" + row.no + "][total_marks]' class='form-control' value='" + parseInt(row.total_marks) + "' />"
    }
}

function teacherReviewFormatter(index, row) {
    if (row.teacher_review) {
        return "<textarea name='exam_marks[" + row.no + "][teacher_review]' class='form-control'>" + row.teacher_review + "</textarea>"
    } else {
        return "<textarea name='exam_marks[" + row.no + "][teacher_review]' class='form-control'>" + ' ' + "</textarea>"
    }
}

function examPublishFormatter(index, row) {
    if (index == 0) {
        return "<span class='badge badge-danger'>" + trans('no') + "</span>"
    } else {
        return "<span class='badge badge-success'>" + trans('yes') + "</span>"
    }
}

function eventFormatter(value, row) {

    let count = 1;
    let html = "<div style='line-height: 20px;'>";
    $.each(row.description, function (key, value) {
        if (value) {
            html += "<br>" + count + ". " + value.title + ". " + value.start_time + " - " + value.end_time
            count++;
        }
    })
    html += "</div>";
    return html;
}

function descriptionFormatter(value, row) {
    let count = 1;
    let html = "<div style='line-height: 20px;'>";
    if (row.type == 'multiple') {
        $.each(row.timing, function (key, value) {
            if (value) {
                html += "<br>" + count + ". " + value.description
                count++;
            }
        })

    } else {

        html += "<br>" + row.description
    }

    html += "</div>";
    return html;
}

function classTeacherFormatter(value, row) {
    let class_teacher_count = 1;
    let html = "<div style='line-height: 20px;'>";
    $.each(row.teachers, function (key, value) {
        if (value) {
            html += "<br>" + class_teacher_count + ". " + value;
            class_teacher_count++;
        }
    })
    html += "</div>";
    return html;
}


function coreSubjectFormatter(value, row) {
    if (row.include_semesters == 1) {
        let core_subject_count = 1;
        let html = "<div style='line-height: 20px;'>";
        let previous_sem_id = null;

        $.each(row.core_subjects, function (key, value) {
            if (value.semester_id !== previous_sem_id) {
                // Add the semester name only if it's different from the previous one
                html += `<br><strong>${value?.semester?.name ?? ''}</strong><br>`;
                previous_sem_id = value.semester_id; // Update the previous_sem_id
                core_subject_count = 1;
            }

            if (value.subject) {
                // Add the subject details
                html += core_subject_count + ". " + value.subject.name + " - " + value.subject.type;
                core_subject_count++;
            }
            html += "<br>";
        });

        html += "</div>";

        return html;

    } else {

        let core_subject_count = 1;
        let html = "<div style='line-height: 20px;'>";
        $.each(row.core_subjects, function (key, value) {
            if (value.subject) {
                html += "<br>" + core_subject_count + ". " + value.subject.name + " - " + value.subject.type
                core_subject_count++;
            }
        })
        html += "</div>";

        return html;
    }


}


function electiveSubjectFormatter(value, row) {
    let html = "<div style='line-height: 20px;'>";
    let previous_sem_id = null;

    if (row.include_semesters == 1) {
        let semesterGroupCounts = {};
        let previous_sem_id = null;

        $.each(row.elective_subject_groups, function (key, group) {
            let elective_subject_count = 1;

            $.each(group.elective_subjects, function (key, elective_subject) {
                let semester_name = (elective_subject && elective_subject.semester)
                    ? elective_subject.semester.name
                    : '';

                if (!semesterGroupCounts[semester_name]) {
                    semesterGroupCounts[semester_name] = 0;
                }

                if (elective_subject.semester_id !== previous_sem_id || key === 0) {
                    semesterGroupCounts[semester_name]++;
                    html += "<br><strong>" + semester_name + "</strong> (<b>Group " + semesterGroupCounts[semester_name] + "</b>)<br>";
                    previous_sem_id = elective_subject.semester_id;
                    elective_subject_count = 1;
                }

                html += elective_subject_count + ". " + elective_subject.subject.name + " - " + elective_subject.subject.type + "<br>";
                elective_subject_count++;
            });

            html += "<b>Total Subjects : </b>" + group.total_subjects + "<br>";
            html += "<b>Total Selectable Subjects : </b>" + group.total_selectable_subjects + "<br><br>";
        });

        html += "</div>";
        return html;

    } else {
        let html = "<div style='line-height: 20px;'>";
        $.each(row.elective_subject_groups, function (key, group) {
            let elective_subject_count = 1;
            html += "<b>Group " + (key + 1) + "</b><br>";

            $.each(group.elective_subjects, function (key, elective_subject) {
                html += elective_subject_count + ". " + elective_subject.subject.name + " - " + elective_subject.subject.type + "<br>"
                elective_subject_count++;
            })

            html += "<b>Total Subjects : </b>" + group.total_subjects + "<br>"
            html += "<b>Total Selectable Subjects : </b>" + group.total_selectable_subjects + "<br><br>"
        })
        html += "</div>";
        return html;
    }


}

function defaultYearFormatter(index, row) {
    if (index == 0) {
        return "<span class='badge badge-danger'>" + trans('no') + "</span>"
    } else {
        return "<span class='badge badge-success'>" + trans('yes') + "</span>"
    }
}

function feesTypeChoiceable(index, row) {
    if (row.choiceable) {
        return "<span class='badge badge-success'>" + trans('yes') + "</span>"
    } else {
        return "<span class='badge badge-danger'>" + trans('no') + "</span>"
    }
}

function feesTypeFormatter(index, row) {
    let html = [];
    if (row.fees_type.length) {
        let no = 1;
        $.each(row.fees_type, function (key, value) {
            html.push("<span>" + no + ". " + value.fees_name + " - " + value.amount + "</span><br>")
            no++;
        });
    } else {
        html.push("<p class='text-center'>-</p>")
    }
    return html.join('')

}

function feesPaidModeFormatter(index, row) {
    if (row.mode != null) {
        if (row.mode == 0) {
            return "<span class='badge badge-info'>" + lang_cash + "</span>"
        } else if (row.mode == 1) {
            return "<span class='badge badge-warning'>" + lang_cheque + "</span>"
        } else {
            return "<span class='badge badge-success'>" + lang_online + "</span>"
        }
    }
}

function feesOnlineTransactionLogParentGateway(index, row) {
    if (row.payment_gateway == 1) {
        return "<span class='badge badge-info'>RazorPay</span>";
    } else if (row.payment_gateway == 2) {
        return "<span class='badge badge-primary'>Stripe</span>";
    } else if (row.payment_gateway == 3) {
        return "<span class='badge badge-dark'>Paystack</span>";
    } else if (row.payment_gateway == 4) {
        return "<span class='badge badge-warning'>Flutterwave</span>";
    } else {
        return " ";
    }
}

function feesOnlineTransactionLogPaymentStatus(index, row) {
    if (row.payment_status == 1) {
        return "<span class='badge badge-success'>" + lang_success + "</span>"
    } else if (row.payment_status == 2) {
        return "<span class='badge badge-warning'>" + lang_pending + "</span>"
    } else {
        return "<span class='badge badge-danger'>" + lang_failed + "</span>";
    }
}

function questionTypeFormatter(index, row) {
    if (row.question_type) {
        return "<span class='badge badge-secondary'>" + lang_equation_based + "</span>"
    } else {
        return "<span class='badge badge-info'>" + lang_simple_question + "</span>"
    }
}

function optionsFormatter(index, row) {
    let html = '';
    $.each(row.options, function (index, value) {
        html += "<div class='row'>";
        html += "<div class= 'col-md-1 text-center'><i class='fa fa-arrow-right small' aria-hidden='true'></i></div><div class='col-md-6'>" + value.option + "</div><br>"
        html += "</div>";
    });
    return html;
}

function answersFormatter(index, row) {
    let html = '';
    $.each(row.answers, function (index, value) {
        html += "<div class='row'>";
        html += "<span class= 'col-md-1 text-center'><i class='fa fa-arrow-right small' aria-hidden='true'></i></span><div class='col-md-6'>" + value.answer + "</div><br>"
        html += "</div>";
    });
    return html;
}

function feesPaidStatusFormatter(value, row, index) {
    if (row.fees_status == 1) {
        return "<span class='badge badge-success'>" + lang_success + "</span>"
    } else if (row.fees_status == 0) {
        return "<span class='badge badge-info'>" + lang_partial_paid + "</span>"
    } else {
        return "<span class='badge badge-warning'>" + lang_pending + "</span>";
    }
}

function textFormatter(value, row) {
    if (row.text) {
        if (row.text.length > 20) {
            return "<p>" + row.text.substring(0, 100) + "..." + "</p>";
        } else {
            return "<p>" + row.text + "</p>";
        }
    } else {
        return "-";
    }
}

function messageFormatter(value, row) {
    if (row.message) {

        if (row.message.length > 20) {
            return "<p>" + row.message.substring(0, 50) + "..." + "</p>";
        } else {
            return "<p>" + row.message + "</p>";
        }
    } else {
        return "-";
    }
}

function shiftStatusFormatter(value, row, index) {
    if (row.status == 1) {
        return "<span class='badge badge-success'>" + lang_active + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + lang_inactive + "</span>";
    }
}

function StatusFormatter(value, row, index) {
    if (row.status == 1) {
        return "<span class='badge badge-success'>" + lang_enable + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + lang_disable + "</span>";
    }
}

function sliderTypeFormatter(index, row) {
    if (row.type == 1) {
        return "<span class='badge badge-success'>" + trans("app") + "</span>"
    } else if (row.type == 2) {
        return "<span class='badge badge-secondary'>" + trans("web") + "</span>"
    } else if (row.type == 3) {
        return "<span class='badge badge-warning'>" + trans("both") + "</span>"
    } else {
        return ""
    }
}

$('#filter_exam_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})

$('#filter_medium_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})

$('#filter_session_year').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
    $('#table_list_questions').bootstrapTable('refresh');
})

$('#filter_shift_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})

$('#filter_subject_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})

$('#filter_class_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})


$('#filter_class_section_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})

$('#filter_teacher_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})

$('#filter_subject_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})

$('#filter-question-class-section-id').on('change', function () {
    $('#table_list_questions').bootstrapTable('refresh');
})

$('#filter-question-subject-id').on('change', function () {
    $('#table_list_questions').bootstrapTable('refresh');
})

$('#filter-view-question-class-id').on('change', function () {
    $('#table_list_questions').bootstrapTable('refresh');
})

function reasonFormatter(value, row) {
    if (row.reason) {
        if (row.reason.length > 20) {
            return "<p>" + row.reason.substring(0, 30) + "..." + "</p>";
        } else {
            return "<p>" + row.reason + "</p>";
        }
    } else {
        return "-";
    }
}

function startMonthFormatter(value, row) {
    const months = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];

    // Check if start_month or end_month is within the valid range (1-12)
    if (row.start_month >= 1 && row.start_month <= 12) {
        return trans(months[row.start_month - 1]);
    } else {
        return "-";
    }
}

function endMonthFormatter(value, row) {
    const months = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];

    // Check if start_month or end_month is within the valid range (1-12)
    if (row.end_month >= 1 && row.end_month <= 12) {
        return trans(months[row.end_month - 1]);
    } else {
        return "-";
    }
}

function semesterStatusFormatter(value, row) {
    // return `<span class="alert alert-${row.status ? "success" : "danger"}">${trans(row.status ? "yes" : "NO")}</span>`;

    return `<span class="alert alert-${row.status ? "success" : "danger"}">${row.status ? trans("YES") : trans("NO")}</span>`;
}


function semesterFormatter(value, row) {
    if (row.include_semesters === null) {
        return "<span class='badge badge-warning'>" + trans("Not Configured") + "</span>";
    } else if (row.include_semesters) {
        return "<span class='badge badge-success'>" + trans("YES") + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + trans("NO") + "</span>";
    }
}

function formFieldRequiredFormatter(index, row) {
    if (row.is_required) {
        return "<span class='badge badge-success'>" + trans("yes") + "</span>"
    } else {
        return "<span class='badge badge-danger'>" + trans("no") + "</span>"
    }
}

function forFormFormatter(index, row) {
    if (row.for == 1) {
        return "<span class='badge badge-info'>" + trans("student") + "</span>";
    } else if (row.for == 2) {
        return "<span class='badge badge-warning'>" + trans("parent") + "</span>";
    } else if (row.for == 3) {
        return "<span class='badge badge-success'>" + trans("teacher") + "</span>";
    } else if (row.for == 4) {
        return "<span class='badge badge-primary'>" + trans("Self Student Registration") + "</span>";
    } else {
        return "-";
    }
}

function formFieldDefaultValuesFormatter(index, row) {
    let html = '';
    if (row.default_values && row.default_values.length) {
        html += '<ul>'
        $.each(row.default_values, function (index, value) {
            if (value != null) {
                html += "<i class='fa fa-arrow-right' aria-hidden='true'></i> " + value + "<br>"
            }
        });
    } else {
        html = '<div class="text-center">-</div>';
    }
    return html;
}

function formFieldOtherValueFormatter(index, row) {
    let otherObj = JSON.parse(row.other);
    let html = '';
    if (otherObj) {
        html += '<ul>'
        otherObj.forEach(value => {
            Object.entries(value).forEach(([key, data]) => {
                html += "<i class='fa fa-arrow-right' aria-hidden='true'></i> " + key + ' - ' + data + '<br>'
            });
        });
    } else {
        html = '<div class="text-center">-</div>';
    }
    return html;
}

function leaveStatusFormatter(value) {
    if (value == 0) {
        return "<span class='badge badge-warning'>" + trans("Pending") + "</span>";
    } else if (value == 1) {
        return "<span class='badge badge-success'>" + trans("Approved") + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + trans("Rejected") + "</span>";
    }
}
function resultStatusFormatter(value, row) {
    if (value == 0) {
        return "<span class='badge badge-danger border rounded border-danger text-danger bg-transparent'>" + trans("fail") + "</span>";
    } else if (value == 1) {
        return "<span class='badge badge-success border rounded border-success text-success bg-transparent'>" + trans("pass") + "</span>";
    } else {
        return "<span class='badge badge-warning border rounded border-warning text-warning bg-transparent'>" + trans("pending") + "</span>";
    }
}
function continueStatusFormatter(value, row) {
    if (value == 0) {
        return "<span class='badge badge-danger border rounded border-danger text-danger bg-transparent'>" + trans("no") + "</span>";
    } else if (value == 1) {
        return "<span class='badge badge-success border rounded border-success text-success bg-transparent'>" + trans("yes") + "</span>";
    } else {
        return "<span>" + "-" + "</span>";
    }
}
function holidayDaysFormatter(value, row) {
    return value.split(',').map(day => trans(day)).join(', ');
}

function eventDescriptionFormatter(value, row) {
    return `<div class="text-wrap">${row.description}</div>`;
}
function studentResultFormatterold(value, row, index) {
    const val = row.studentResult;
    if (!val) {
        return "<span class='text-muted'>—</span>";
    }

    let percent = val.percentage ?? 0;
    let grade = val.grade ?? "-";

    return `
        <span>
           ${percent}% (${grade})
        </span>
    `;
}

function studentResultFormatter(value, row, index) {
    const val = row.studentResult;

    if (!val) {
        return "<span class='text-muted'>—</span>";
    }

    let percent = val.percentage ?? 0;
    let grade = val.grade ?? "-";

    return `
        <div class="d-flex align-items-center gap-2">
            <span>${percent}% (${grade})</span>
            <button
              class="btn btn-sm p-0 text-primary view-exam-result ml-1" 
              id="view-exam-result"
              data-student-id="${row.id}"
              data-class_section-id="${val.class_section_id}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                  <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                  <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                </svg>
            </button>
            </div>
        `;
}

function updateBtnFormatter(value, row, index) {
    return value;
}

function amountFormatter(value) {
    const number = Number(value);
    return Number.isFinite(number) ? number.toLocaleString() : "-";
}

function onlineRegistrationStatusFormatter(value, row) {
    if (value == 2) {
        return '<span class="badge badge-danger">' + trans('rejected') + '</span>';
    }
    return '<span class="badge badge-warning">' + trans('pending') + '</span>';
}