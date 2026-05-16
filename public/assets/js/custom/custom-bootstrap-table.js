"use strict";
//Bootstrap actionEvents
window.lessonEvents = {
    'click .edit-data': function (e, value, row, index) {
        //Reset Values
        $('.edit-extra-files').html('')
        $('.edit_file_type_div').show();
        $('#edit_id').val(row.id);
        $('#edit_class_section_id').val(row.class_section_id).trigger('change', [row.subject_id]);
        setTimeout(() => {
            $('#edit_subject_id').val(row.subject_id).trigger('change');
        }, 1000);
        $('#edit_name').val(row.name);
        $('#edit_description').val(row.description);
        if (row.file.length > 0) {
            $.each(row.file, function (key, data) {
                let html = '';
                if (key == 0) {
                    html = $('.edit_file_type_div');
                } else {
                    html = $('.edit_file_type_div:last').clone().show();
                    $('.edit-extra-files').append(html);
                }
                html.removeAttr('id');
                html.find('.error').remove();
                html.find('.has-danger').removeClass('has-danger');
                // This function will replace the last index value and increment in the multidimensional name attribute
                html.find(':input').each(function (key, element) {
                    this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
                        return '[' + (parseInt(p1, 10) + 1) + ']';
                    });
                })

                // html.find('.edit-lesson-file i').addClass('fa-times').removeClass('fa-plus');
                html.find('.remove-lesson-file').attr('data-id', data.id);
                html.find('#edit_file_id').val(data.id);

                //1 = File Upload , 2 = Youtube Link , 3 = Uploaded Video , 4 = Other Link
                if (data.type == 1) {
                    // 1 = File Ulopad
                    html.find('#edit_file_type').val('file_upload').trigger('change');
                    html.find('#file_preview').attr('href', data.file_url).text(data.file_name);
                    //Used class name as a selector instead of id because of jquery dynamic field validation.
                    html.find('.file_name').val(data.file_name);
                } else if (data.type == 2) {
                    // 2 = Youtube Link
                    html.find('#edit_file_type').val('youtube_link').trigger('change');
                    html.find('#file_thumbnail_preview').attr('src', data.file_thumbnail);
                    html.find('.file_link').val(data.file_url);

                    html.find('.file_name').val(data.file_name);
                } else if (data.type == 3) {
                    // 3 = Uploaded Video
                    html.find('#edit_file_type').val('video_upload').trigger('change');
                    html.find('#file_thumbnail_preview').attr('src', data.file_thumbnail);
                    html.find('#file_preview').attr('src', data.file_url).text(data.file_name);

                    html.find('.file_name').val(data.file_name);
                } else if (data.type == 4) {
                    // 4 = Other Link
                    html.find('#edit_file_type').val('other_link').trigger('change');
                    html.find('#file_thumbnail_preview').attr('src', data.file_thumbnail);

                    html.find('.file_name').val(data.file_name);
                    html.find('.file_link').val(data.file_url);
                }
            })
        } else {
            $('.edit_file_type_div').hide();
        }
    }
};


window.topicEvents = {
    'click .edit-data': function (e, value, row, index) {
        //Reset Values
        $('.edit-extra-files').html('')
        $('.edit_file_type_div').show();
        $('#edit_id').val(row.id);
        $('#edit_topic_class_section_id').val(row.class_section_id).trigger('change', [row.subject_id]);
        setTimeout(() => {
            $('#edit_topic_subject_id').val(row.subject_id).trigger('change');
            setTimeout(() => {
                $('#edit_topic_lesson_id').val(row.lesson_id);
            }, 500);
        }, 1000);
        $('#edit_name').val(row.name);
        $('#edit_description').val(row.description);

        if (row.file.length > 0) {
            $.each(row.file, function (key, data) {
                let html = '';
                if (key == 0) {
                    html = $('.edit_file_type_div');
                } else {
                    html = $('.edit_file_type_div:last').clone().show();
                    $('.edit-extra-files').append(html);
                }
                html.removeAttr('id');
                html.find('.error').remove();
                html.find('.has-danger').removeClass('has-danger');
                // This function will replace the last index value and increment in the multidimensional name attribute
                html.find(':input').each(function (key, element) {
                    this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
                        return '[' + (parseInt(p1, 10) + 1) + ']';
                    });
                })

                // html.find('.edit-lesson-file i').addClass('fa-times').removeClass('fa-plus');
                // html.find('.edit-lesson-file').addClass('btn-inverse-danger remove-lesson-file').removeClass('btn-inverse-success edit-lesson-file').attr('data-id', data.id);

                html.find('.remove-lesson-file').attr('data-id', data.id);
                html.find('#edit_file_id').val(data.id);

                //1 = File Upload , 2 = Youtube Link , 3 = Uploaded Video , 4 = Other Link
                if (data.type == 1) {
                    // 1 = File Ulopad
                    html.find('#edit_file_type').val('file_upload').trigger('change');
                    html.find('#file_preview').attr('href', data.file_url).text(data.file_name);
                    //Used class name as a selector instead of id because of jquery dynamic field validation.
                    html.find('.file_name').val(data.file_name);
                } else if (data.type == 2) {
                    // 2 = Youtube Link
                    html.find('#edit_file_type').val('youtube_link').trigger('change');
                    html.find('#file_thumbnail_preview').attr('src', data.file_thumbnail);
                    html.find('.file_link').val(data.file_url);

                    html.find('.file_name').val(data.file_name);
                } else if (data.type == 3) {
                    // 3 = Uploaded Video
                    html.find('#edit_file_type').val('video_upload').trigger('change');
                    html.find('#file_thumbnail_preview').attr('src', data.file_thumbnail);
                    html.find('#file_preview').attr('src', data.file_url).text(data.file_name);

                    html.find('.file_name').val(data.file_name);
                } else if (data.type == 4) {
                    // 4 = Other Link
                    html.find('#edit_file_type').val('other_link').trigger('change');
                    html.find('#file_thumbnail_preview').attr('src', data.file_thumbnail);

                    html.find('.file_name').val(data.file_name);
                    html.find('.file_link').val(data.file_url);
                }
            })
        } else {
            $('.edit_file_type_div').hide();
        }
    }
};

window.examEvents = {
    'click .publish-exam-result': function (e, value, row, index) {
        e.preventDefault();
        // alert('working');
        Swal.fire({
            title: trans('Are you sure?'),
            text: trans('You won\'t be able to revert this!'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Confirm!')
        }).then((result) => {
            if (result.isConfirmed) {
                let url = baseUrl + '/exams/publish/' + row.id;

                function successCallback(response) {
                    showSuccessToast(response.message);
                    $('#table_list').bootstrapTable('refresh');
                }
                function errorCallback(response) {
                    showErrorToast(response.message);
                }

                ajaxRequest('POST', url, null, null, successCallback, errorCallback);
            }
        })
    },
    'click .edit-data': function (e, value, row, index) {
        //Reset to Old Values
        $('.edit-extra-timetable').html('');
        $('.edit_exam_timetable').show();
        $('#edit_id').val(row.id);
        $('#edit_class_id').val(row.class_id);
        $('#edit_name').val(row.name);
        $('#edit_description').val(row.description);
        $('.js-example-basic-multiple').select2({
            placeholder: "Please Select",
            dropdownParent: $("#editModal"),

        });
        $('#edit_class_id').on('select2:unselecting', function (e) {
            // alert('working');
            e.preventDefault();

            var exam_id = document.getElementById('edit_id').value;
            let class_id = e.params.args.data.id;

            Swal.fire({
                title: trans('Are you sure?'),
                text: trans('You won\'t be able to revert this!'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                cancelButtonText: trans('Cancel'),
                confirmButtonText: trans('Confirm!')
            }).then((result) => {
                if (result.isConfirmed) {
                    let url = baseUrl + '/delete-exam-class/' + exam_id + '/' + class_id;

                    function successCallback(response) {
                        $('#editModal').modal('hide');
                        $('#table_list').bootstrapTable('refresh');
                        showSuccessToast(response.message);

                    }

                    function errorCallback(response) {
                        showErrorToast(response.message);
                    }

                    ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
                }
            })
        });
    }
};

window.assignmentEvents = {
    'click .edit-data': function (e, value, row, index) {
        //Reset to Old Values
        var html_file = '';
        $('#edit_id').val(row.id);
        $('#edit_name').val(row.name);
        $('#edit_instructions').val(row.instructions);

        var dueParts = row.due_date.match(/(\d{2})-(\d{2})-(\d{4}) (\d{2}):(\d{2}):(\d{2})/);
        var Fromdatetime = dueParts[3] + "-" + dueParts[2] + "-" + dueParts[1] + "T" + dueParts[4] + ":" + dueParts[5] + ":" + dueParts[6];
        $('#edit_due_date').val(Fromdatetime);
        $('#edit_points').val(row.points);
        if (row.resubmission) {
            $('#edit_resubmission_allowed').prop('checked', true).trigger('change');
            $('#edit_extra_days_for_resubmission').val(row.extra_days_for_resubmission);
        } else {
            $('#edit_resubmission_allowed').prop('checked', false).trigger('change');
            $('#edit_extra_days_for_resubmission').val('');
        }

        if (row.file) {
            $.each(row.file, function (key, data) {
                html_file += '<div class="file"><a target="_blank" href="' + data.file_url + '" class="m-1">' + data.file_name + '</a> <span class="fa fa-times text-danger remove-assignment-file" data-id=' + data.id + '></span><br><br></div>'
            })

            $('#old_files').html(html_file);
        }

        // Both dropdowns are disabled (display-only) — populate directly from row data,
        // no need to re-fetch from the server.
        $('#edit_semester_id').val(row.semester_id || '');
        $('#edit_class_section_id').html(
            '<option value="' + row.class_section_id + '">' + row.class_section_name + '</option>'
        );
        $('#edit_subject_id').html(
            '<option value="' + row.subject_id + '">' + row.subject_name + '</option>'
        );
    }
};

window.announcementEvents = {
    'click .edit-announcement-form': function (e, value, row, index) {
        // Log the row for debugging
        // Set the ID in the hidden field
        $('#edit_id').val(row.id);

        // Set the title
        $('#title').val(row.title);

        // Set the description
        $('#description').val(row.description);

        // Set the assign type (e.g., Subject, Noticeboard, etc.)
        $('#edit_set_data').val(row.assign_to.class_section ? 'class_section' : 'noticeboard');
        // Update the form action URL to include the announcement ID
        var form = $('#edit-announcement-form');
        var baseUrl = form.attr('action');
        form.attr('action', baseUrl.replace(/\/\d+$/, '') + '/' + row.id);

        // If assigned to a Subject (class_section), show and set class section and subject dropdowns
        if (row.assign === 'Subject' && row.assign_to && row.assign_to.class_section_id) {
            $('.edit_show_class_section_id').show();
            $('#edit_class_section_id').val(row.assign_to.class_section_id).trigger('change', [row.assign_to.subject_id]);
            // Optionally set subject if you have a subject dropdown
            if ($('#edit_subject_id').length && row.assign_to.subject_id) {
                $('#edit_subject_id').val(row.assign_to.subject_id).trigger('change');
            }
        } else {
            $('.edit_show_class_section_id').hide();
        }

        // Display any attached files
        if (row.file && row.file.length > 0) {
            let fileHtml = '';
            row.file.forEach(function (file) {
                fileHtml += '<div class="mb-2">';
                fileHtml += '<a href="' + file.file_url + '" target="_blank">' + file.file_name + '</a>';
                fileHtml += '</div>';
            });
            $('#old_files').html(fileHtml);
        } else {
            $('#old_files').html('No files attached');
        }
    }
};

window.parentEvents = {
    'click .editdata': function (e, value, row, index) {
        $('#edit_id').val(row.id);
        $('#first_name').val(row.first_name);
        $('#last_name').val(row.last_name);
        $('input[name=gender][value=' + row.gender + '].edit').prop('checked', true);
        $('#email').val(row.email);
        $('#mobile').val(row.mobile);
        $('#occupation').val(row.occupation);
        $('#dob').val(row.dob);
        $('#edit-image-tag').attr('src', row.image);
        if (row.current_address) {
            $('#current_address_div').show();
            $('#current_address').val(row.current_address);
        } else {
            $('#current_address_div').hide();
        }
        if (row.permanent_address) {
            $('#permanent_address_div').show();
            $('#permanent_address').val(row.permanent_address);
        } else {
            $('#permanent_address_div').hide();
        }
        setTimeout(function () {
            $('input[type=checkbox].edit_checkbox').removeAttr('checked');
            $('.edit_text_number').val('');
            $('.edit_dropdown').val('');
            $('.edit_textarea').val('');
            $('.edit_radio').removeAttr('checked');
            // $('.edit_file').css('display', 'none');

            $.each(row.dynamic_field, function (key, value) {

                var id = Object.keys(row.dynamic_field[key])[0];
                var id_with_hash = '#' + Object.keys(row.dynamic_field[key])[0];
                $('#file-' + id + '').val('');
                $('#' + id + '-div').css('display', 'none');

                // Confirm is checkbox or not
                let count = 0;
                Object.keys(value).forEach(function (key) {
                    let value_1 = value[key];
                    if (typeof value_1 === 'object') {
                        count++;
                    }
                });

                if (count == 0) {
                    let myDiv = document.getElementById(id);
                    let tagName = '';
                    // TEXT / NUMBER / TEXTAERA / SELECT / FILE
                    if (myDiv) {
                        tagName = myDiv.tagName;
                        if (tagName == 'A') { // IF INPUT TYPE FILE
                            if (value[id]) {
                                $('#' + id + '-div').css('display', 'block');
                                $('#' + id + '-hidden').val(value[id]);
                                $(id_with_hash).attr('href', 'storage/' + value[id]);
                                $('#file-' + id + '').val(value[id]);
                                $(id_with_hash + '-name').val(value[id]);
                            }
                        } else { // TEXT / NUMBER / TEXTAERA / SELECT
                            // let input_type = $(id_with_hash).attr('type');
                            $(id_with_hash).val('');
                            $(id_with_hash).val(value[id]);
                        }

                    } else {
                        // RADIO
                        let input_type = $('#' + value[id]).attr('type');
                        if (input_type == 'radio') {
                            $('#' + value[id]).attr('checked', true);
                        }
                    }

                } else { // CHECKBOX
                    let checkbox_value = [];

                    $.each(value, function (key_1, value_1) {
                        if (value_1 != null) {
                            checkbox_value.push(Object.keys(value_1));
                        }
                    })

                    $.each(checkbox_value, function (key_1, value_1) {
                        $.each(value_1, function (key_2, value_2) {
                            $('#checkbox_' + value_2).attr('checked', true);
                        })
                    })

                }

            });
        }, 1000);
    }
};

window.studentEvents = {
    'click .editdata': function (e, value, row, index) {
        $('#edit_id').val(row.user_id);
        $('#edit_first_name').val(row.first_name);
        $('#edit_last_name').val(row.last_name);
        $('#edit_mobile').val(row.mobile);
        $('#edit_dob').val(row.dob);
        $('#edit_class_id').val(row.class_id).trigger('change');
        $('#edit_class_section_id').val(row.class_section_id);
        $('#edit_category_id').val(row.category_id);
        $('#edit_admission_no').val(row.admission_no);
        $('#edit_roll_number').val(row.roll_number);
        $('#edit_caste').val(row.caste);
        $('#edit_religion').val(row.religion);
        $('#edit_admission_date').val(row.admission_date);
        $('#edit_blood_group').val(row.blood_group);
        $('#edit_height').val(row.height);
        $('#edit_weight').val(row.weight);
        $('#edit_current_address').val(row.current_address);
        $('#edit_permanent_address').val(row.permanent_address);
        $('#edit-student-image-tag').attr('src', row.image_link);

        $('#student_active').prop('checked', false);
        $('#student_inactive').prop('checked', false);
        $('#class_section_div').hide();

        if (row.gender == 'male') {
            $('#edit_female').removeAttr('checked');
            $('#edit_male').attr('checked', 'true');
        } else {
            $('#edit_male').removeAttr('checked');
            $('#edit_female').attr('checked', 'true');
        }
        // STUDENT DYNAMIC FIELDS
        setTimeout(function () {
            $('input[type=checkbox].edit_checkbox').removeAttr('checked');
            $('.edit_text_number').val('');
            $('.edit_dropdown').val('');
            $('.edit_textarea').val('');
            $('.edit_radio').removeAttr('checked');
            // $('.edit_file').css('display', 'none');

            $.each(row.dynamic_data_field, function (key, value) {
                var id = Object.keys(row.dynamic_data_field[key])[0];
                var id_with_hash = '#' + Object.keys(row.dynamic_data_field[key])[0];
                $('#file-' + id + '').val('');
                $('#' + id + '-div').css('display', 'none');

                // Confirm is checkbox or not
                let count = 0;
                Object.keys(value).forEach(function (key) {
                    let value_1 = value[key];
                    if (typeof value_1 === 'object') {
                        count++;
                    }
                });

                if (count == 0) {
                    let myDiv = document.getElementById(id);
                    let tagName = '';
                    // TEXT / NUMBER / TEXTAERA / SELECT / FILE
                    if (myDiv) {
                        tagName = myDiv.tagName;
                        if (tagName == 'A') { // IF INPUT TYPE FILE
                            if (value[id]) {
                                $('#' + id + '-div').css('display', 'block');
                                $('#' + id + '-hidden').val(value[id]);
                                $(id_with_hash).attr('href', 'storage/' + value[id]);
                                $('#file-' + id + '').val(value[id]);
                                $(id_with_hash + '-name').val(value[id]);
                            }
                        } else { // TEXT / NUMBER / TEXTAERA / SELECT
                            // let input_type = $(id_with_hash).attr('type');
                            $(id_with_hash).val('');
                            $(id_with_hash).val(value[id]);
                        }

                    } else {
                        // RADIO
                        let input_type = $('#' + value[id]).attr('type');
                        if (input_type == 'radio') {
                            $('#' + value[id]).attr('checked', true);
                        }
                    }

                } else { // CHECKBOX
                    let checkbox_value = [];

                    $.each(value, function (key_1, value_1) {
                        if (value_1 != null) {
                            checkbox_value.push(Object.keys(value_1));
                        }
                    })

                    $.each(checkbox_value, function (key_1, value_1) {
                        $.each(value_1, function (key_2, value_2) {
                            $('#checkbox_' + value_2).attr('checked', true);
                        })
                    })

                }

            });
        }, 1000);
        // END DYNAMIC FIELDS
        if (row.gender == 'male') {
            $('#edit_female').removeAttr('checked');
            $('#edit_male').attr('checked', 'true');
        } else {
            $('#edit_male').removeAttr('checked');
            $('#edit_female').attr('checked', 'true');
        }

        if (row.father_id && row.mother_id) {
            $('#show-edit-parents-details').attr('checked', true);
            $('input[name="edit_parent_guardian_type"]').val('parent').trigger('change');
            console.log('parent');
        } else {
            $('#show-edit-guardian-details').attr('checked', true);
            $('input[name="edit_parent_guardian_type"]').val('guardian').trigger('change');
            console.log('guardian');
        }

        //Father Data
        $("#edit_father_email").select2("trigger", "select", {
            data: {
                id: row.father_id ? row.father_id : "",
                text: row.father_email ? row.father_email : "",
            }
        });
        //Adding delay to fill data so that select2 code and this code don't conflict each other
        setTimeout(function () {
            $('#edit_father_first_name').val(row.father_first_name).attr('readonly', true);
            $('#edit_father_last_name').val(row.father_last_name).attr('readonly', true);
            $('#edit_father_mobile').val(row.father_mobile).attr('readonly', true);
            $('#edit_father_dob').val(row.father_dob).attr('readonly', true);
            $('#edit_father_occupation').val(row.father_occupation).attr('readonly', true);
            $('#edit-father-image-tag').attr('src', row.father_image_link);
            $(".edit-father-search").rules("remove", "email");
            $(".father_image").rules("remove", "required");
        }, 500);


        //Mother Data
        $("#edit_mother_email").select2("trigger", "select", {
            data: {
                id: row.mother_id ? row.mother_id : "",
                text: row.mother_email ? row.mother_email : "",
            }
        });
        //Adding delay to fill data so that select2 code and this code don't conflict each other

        setTimeout(function () {
            $('#edit_mother_first_name').val(row.mother_first_name).attr('readonly', true);
            $('#edit_mother_last_name').val(row.mother_last_name).attr('readonly', true);
            $('#edit_mother_mobile').val(row.mother_mobile).attr('readonly', true);
            $('#edit_mother_dob').val(row.mother_dob).attr('readonly', true);
            $('#edit_mother_occupation').val(row.mother_occupation).attr('readonly', true);
            $('#edit-mother-image-tag').attr('src', row.mother_image_link);
            $(".edit-mother-search").rules("remove", "email");
            $(".mother_image").rules("remove", "required");
        }, 500);


        // if (row.guardian_id) {
        //     $('input[name="edit_parent_guardian_type"]').attr('checked', true).trigger('change');
        // } else {
        //     $('input[name="edit_parent_guardian_type"]').attr('checked', false).trigger('change');
        // }

        // Guardian Data
        $("#edit_guardian_email").select2("trigger", "select", {
            data: {
                id: row.guardian_id ? row.guardian_id : "",
                text: row.guardian_email ? row.guardian_email : "",
                edit_data: true,
            }
        });

        //Adding delay to fill data so that select2 code and this code don't conflict each other
        setTimeout(function () {
            $('#edit_guardian_first_name').val(row.guardian_first_name).attr('readonly', true);
            $('#edit_guardian_last_name').val(row.guardian_last_name).attr('readonly', true);
            $('#edit_guardian_mobile').val(row.guardian_mobile).attr('readonly', true);
            if (row.guardian_gender == 'Male') {
                $('#edit_guardian_female').removeAttr('checked');
                $('#edit_guardian_male').attr('checked', 'true');
            } else {
                $('#edit_guardian_male').removeAttr('checked');
                $('#edit_guardian_female').attr('checked', 'true');
            }
            $('#edit_guardian_dob').val(row.guardian_dob).attr('readonly', true);
            $('#edit_guardian_occupation').val(row.guardian_occupation).attr('readonly', true);
            $('#edit-guardian-image-tag').attr('src', row.guardian_image_link);
            $(".edit-guardian-search").rules("remove", "email");
            $(".guardian_image").rules("remove", "required");
        }, 500);

    },

    'click .deletepermanentdata': function (e, value, row, index) {
        e.preventDefault();
        Swal.fire({
            title: trans('Are you sure?'),
            text: trans('You want to delete it permanently ?'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Yes, delete it!')
        }).then((result) => {
            if (result.isConfirmed) {
                let url = baseUrl + '/permanent-delete/' + row.id;

                function successCallback(response) {
                    $('#editModal').modal('hide');
                    $('#table_list').bootstrapTable('refresh');
                    showSuccessToast(response.message);
                }

                function errorCallback(response) {
                    showErrorToast(response.message);
                }

                ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
            }
        })


    }
};

window.assignmentSubmissionEvents = {
    'click .edit-data': function (e, value, row, index) {
        let file_html = "";
        $('#edit_id').val(row.id);
        $('#assignment_name').val(row.assignment_name);
        $('#subject').val(row.subject);
        $('#student_name').val(row.student_name);

        $.each(row.file, function (key, data) {
            file_html += " <a target='_blank' href='" + data.file_url + "'>" + data.file_name + "</a><br>";
        });

        $('#files').html(file_html);
        if (row.assignment_points) {
            $('#points_div').show();
            $('#assignment_points').text('/ ' + row.assignment_points);
            $('#points').prop('max', row.assignment_points);
            $('#points').val(row.points);
        } else {
            $('#points_div').hide();
            $('#assignment_points').text('');
        }
        $('#text').val(row.text);
        $('#feedback').val(row.feedback);
        if (row.status === 1) {
            $('#status_accept').attr('checked', true);
        } else if (row.status === 2) {
            $('#status_reject').attr('checked', true);
        }
    }
};

window.examMarksEvents = {
    'click .edit-data': function (e, value, row, index) {
        $('.student_name').html(row.student_name);
        $('.subject_container').html('');
        var no = 0;
        $.each(row.data, function (key, data) {
            var html_data = '<div class="row"><input type="hidden" id="marks_id form-control" readonly name="edit[' + no + '][marks_id]" value="' + data.id + '"/><div class="row mx-2"><input type="hidden" id="marks_id form-control" readonly name="edit[' + no + '][exam_id]" value="' + data.timetable.exam_id + '"/><div class="row mx-2"><input type="hidden" id="marks_id form-control" readonly name="edit[' + no + '][student_id]" value="' + row.student_id + '"/><div class="row mx-2"><input type="hidden" id="marks_id form-control" readonly name="edit[' + no + '][passing_marks]" value="' + data.timetable.passing_marks + '"/><div class="form-group col-sm-12 col-md-4"><input type="text" class="subject_name form-control" readonly name="edit[' + no + '][subject_name]" value="' + data.subject.name + '" /></div><div class="form-group col-sm-12 col-md-4"><input type="text" class="total_marks form-control" readonly name="edit[' + no + '][total_marks]" value="' + data.timetable.total_marks + '" /></div><div class="form-group col-sm-12 col-md-4"><input type="text" class="obtained_marks form-control" name="edit[' + no + '][obtained_marks]" value="' + data.obtained_marks + '" /></div></div>';
            $('.subject_container').append(html_data);
            no++;
        });
    }
};

window.examTimetableEvents = {
    'click .edit-data': function (e, value, row, index) {

        $('.edit_timetable_exam_id').val(row.exam_id);
        $('.edit_timetable_class_id').val(row.class_id);
        $('.edit_timetable_session_year_id').val(row.session_year_id);

        $('.edit-timetable-container').html('');
        let select_subject_html = "";
        if (row.subjects.length > 0) {
            $.each(row.subjects, function (key, data) {
                select_subject_html += "<option value='" + data.id + "'>" + data.name + ' - ' + data.type + "</option>";
            });
        } else {
            select_subject_html = "<option value=''>No Data Found</option>";
        }
        $('.edit_exam_subjects_options').html(select_subject_html);
        if (row.timetable.length != 0) {
            $.each(row.timetable, function (key, value) {
                let html = '';
                if (!$('.edit-timetable-container:last').is(':empty')) {
                    html = $('.edit-timetable-container').find('.edit_exam_timetable:last').clone();
                } else {
                    html = $('.edit_exam_timetable_tamplate').clone();
                }
                html.addClass('edit_exam_timetable').removeClass('edit_exam_timetable_tamplate');
                html.css('display', 'block');
                html.find('.error').remove();
                html.find('.has-danger').removeClass('has-danger');
                // This function will replace the last index value and increment in the multidimensional name attribute
                html.find('.form-control').each(function (key, element) {
                    this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
                        return '[' + (parseInt(p1, 10) + 1) + ']';
                    });
                })

                html.find('.remove-edit-exam-timetable-content').attr("data-timetable_id", value.id);

                html.find('.edit_timetable_id').val(value.id);

                html.find('.edit_timetable_exam_id').val(value.exam_id);

                html.find('.edit_timetable_class_id').val(value.class_id);

                html.find('.edit_exam_subjects_options').val(value.subject_id)

                html.find('.edit_total_marks').val(value.total_marks);

                html.find('.edit_passing_marks').val(value.passing_marks);

                html.find('.edit_start_time').val(value.start_time);

                html.find('.edit_end_time').val(value.end_time);

                var dateParts = value.date.match(/(\d{2})-(\d{2})-(\d{4})(?: (\d{2}):(\d{2}):(\d{2}))?/);
                var date = dateParts ? new Date(dateParts[3], dateParts[2] - 1, dateParts[1]) : new Date(value.date),
                    yr = date.getFullYear(),
                    month = date.getMonth() < 9 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1,
                    day = date.getDate() < 9 ? '0' + date.getDate() : date.getDate(),
                    newDate = day + '-' + month + '-' + yr;

                html.find('.edit_date').val(newDate);
                $('.edit-timetable-container').append(html);
            });
            $(document).on('click', '.remove-edit-exam-timetable-content', function (e) {
                e.preventDefault();

                let $this = $(this);
                // If button has Data ID then Call ajax function to delete file
                let timetable_id = $(this).data('timetable_id');

                if (timetable_id) {
                    Swal.fire({
                        title: trans('Are you sure?'),
                        text: trans('You won\'t be able to revert this!'),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        cancelButtonText: trans('Cancel'),
                        confirmButtonText: trans('Yes, delete it!')
                    }).then((result) => {
                        if (result.isConfirmed) {
                            let url = baseUrl + '/exams/delete-timetable/' + timetable_id;

                            function successCallback(response) {
                                $this.parent().parent().parent().remove();
                                $('#editModal').modal('hide');
                                $('#table_list').bootstrapTable('refresh');
                                showSuccessToast(response.message);
                            }

                            function errorCallback(response) {
                                showErrorToast(response.message);
                            }

                            ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
                        }
                    })
                } else {
                    $(this).parent().parent().parent().remove();
                }

            });
        }
    }
}

window.FeesTypeActionEvents = {
    'click .edit-data': function (e, value, row, index) {
        $('#edit_id').val(row.id);
        $('#edit_name').val(row.name);
        $('#edit_description').val(row.description);
        if (row.choiceable) {
            $('#edit_choiceable_true').val(row.choiceable).attr('checked', true);
            $('#edit_choiceable_false').attr('checked', false)
        } else {
            $('#edit_choiceable_false').val(row.choiceable).attr('checked', true);
            $('#edit_choiceable_true').removeAttr('checked', false)
        }
    }
};

window.feesClassEvents = {
    'click .edit-data': function (e, value, row, index) {
        $('#edit_class_id').val(row.class_id);
        $('#class_id').val(row.class_id);

        if (row.fees_type.length) {
            $('.edit-extra-fees-types').html('');
            $.each(row.fees_type, function (key, value) {
                let fees_type = $('.edit-fees-type-div:last').clone().show();
                // Remove the error label from the main html so that duplicate error will not be show
                fees_type.find('select').siblings('.error').remove();

                //Change the Name array attribute for jquery validation
                //Add incremental name value
                fees_type.find('.form-control').each(function (key, element) {
                    this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
                        return '[' + (parseInt(p1, 10) + 1) + ']';
                    });
                    this.id = this.id.replace(/\_(\d+)/, function (str, p1) {
                        return '_' + (parseInt(p1, 10) + 1);
                    });
                    $(element).attr('disabled', false);
                })
                //Fill the Values
                fees_type.find('.remove-fees-type').attr('data-id', value.id);
                fees_type.find('.edit-fees-type-id').val(value.id);
                fees_type.find('select').find("option[value = '" + value.fees_type_id + "']").attr("selected", "selected");
                fees_type.find('.edit_amount').val(value.amount);
                if (value.choiceable) {
                    fees_type.find('#editChoiceableNo_' + (key + 1)).removeAttr("checked");
                    fees_type.find('#editChoiceableYes_' + (key + 1)).attr("checked", true);
                } else {
                    fees_type.find('#editChoiceableYes_' + (key + 1)).removeAttr("checked");
                    fees_type.find('#editChoiceableNo_' + (key + 1)).attr("checked", true);
                }
                fees_type.find('.edit_choiceable').val(value.choiceable).attr('checked', true);
                $('.edit-extra-fees-types').append(fees_type);
            })
        } else {
            $('.edit-extra-fees-types').html('');
        }
    }
};

window.feesPaidEvents = {
    'click .compulsory-data': function (e, value, row, index) {
        $(document).find('.cheque_no').val(null)
        $('.cheque_compulsory_mode').attr('checked', false);
        $('.cash_compulsory_mode').attr('checked', true).change();

        $('#student_id').val(row.student_id);
        $('#class_id').val(row.class_id);

        $('.student_name').html(row.student_name + ' - ' + row.class_name);
        $('.paid_date').val(row.current_date);

        if ((row.mode == 1) && (row.type_of_fee == 0 || row.type_of_fee == 1 || row.type_of_fee == null)) {
            $(document).find('.mode_container').show(200);
            $('.cash_compulsory_mode').attr('checked', false)
            $('.cheque_compulsory_mode').attr('checked', true).change();
            $(document).find('.cheque_no').val(row.cheque_no)
        } else if (row.mode == 0 && (row.type_of_fee == 0 || row.type_of_fee == 1 || row.type_of_fee == null)) {
            $(document).find('.mode_container').show(200);
            $(document).find('.cheque_no').val(null)
            $('.cheque_compulsory_mode').attr('checked', false);
            $('.cash_compulsory_mode').attr('checked', true).change();
        } else if (row.mode == null) {
            $(document).find('.mode_container').show(200);
            $(document).find('.cheque_no').val(null)
            $('.cheque_compulsory_mode').attr('checked', false);
            $('.cash_compulsory_mode').attr('checked', true).change();
        } else {
            $(document).find('.mode_container').hide(200);
        }
        if (row.compulsory_fees != null) {
            $('.compulsory_div').show();
            let html = '';
            let base_amount = 0;
            let total = 0;
            // Adding the data of compulsory fees with installment data
            html = '<table class="table"><tbody>'
            $.each(row.compulsory_fees, function (index, value) {
                html += '<tr><td scope="row" class="text-left"></td><td colspan="2" class="text-left">' + value.fees_type.name + '</td><td class="text-right">' + (value.amount).toFixed(2) + '</td></tr>'
            });

            //Due Charges For Whole Session Year
            if (row.due_charges.charges) {
                html += '<input type="hidden" name="due_charges_whole_year" value="' + row.due_charges.charges + '">';
                html += '<tr class="due_charges_whole_year"><td scope="row" class="text-left"></td><td colspan="2" class="text-left">' + lang_due_charges + '<br><small>' + lang_date + ' :- (' + row.due_charges.date + ')</small></td><td class="text-right">' + row.due_charges.charges ?? (row.due_charges.charges).toFixed(2) + '</td></tr>'
            }

            // Pay In Installment Tick
            if (row.is_installment_paid == 1 && row.installment_data.length) {
                if (row.due_charges) {
                    html += '<tr class="pay_in_installment_row"><td scope="row" class="text-left"></td><td colspan="2" class="text-left">' + lang_pay_in_installment + '</td><td class="text-right"><input type="checkbox" name="pay_in_installment" class="form-check-input pay_in_installment" value="" data-base_amount="' + row.base_amount_with_due_charges + '"></td></tr>'
                } else {
                    html += '<tr class="pay_in_installment_row"><td scope="row" class="text-left"></td><td colspan="2" class="text-left">' + lang_pay_in_installment + '</td><td class="text-right"><input type="checkbox" name="pay_in_installment" class="form-check-input pay_in_installment" value="" data-base_amount="' + row.base_amount + '"></td></tr>'
                }
            }

            // Get the total Count of Installment is paid
            let paid_installment_count = -1;
            if (row.installment_data.length) {
                $.each(row.installment_data, function (index, installment_data) {
                    if (installment_data.paid) {
                        paid_installment_count++;
                    }
                });
            }
            // show the installment data
            if (row.fees_status == null || row.fees_status == 0) {
                if (row.installment_data.length &&
                    (row.is_installment_paid == 0 || row.is_installment_paid == 1 || row.fees_status == 0 || row.fees_status == null || row.total_fees == null)) {
                    // get installment amount
                    let installment_amount = (Number(row.base_amount) / Number(row.installment_data.length)).toFixed(2);
                    let lastPaidIndex = -1; // Track the last paid installment index

                    // First find the last paid installment index
                    $.each(row.installment_data, function (index, installment_data) {
                        if (installment_data.paid) {
                            lastPaidIndex = index;
                        }
                    });

                    $.each(row.installment_data, function (index, installment_data) {
                        // if due charges applicable then show the data accordingly
                        if (installment_data.due_charges_applicable) {
                            let due_charges = (installment_amount * Number(installment_data.due_charges) / 100).toFixed(2)
                            let total_installment_amount = (Number(installment_amount) + Number(due_charges)).toFixed(2);
                            total = (Number(total) + Number(total_installment_amount)).toFixed(2);

                            // if the data is last
                            if (row.installment_data.length == (index + 1)) {
                                // if the data is paid
                                if (installment_data.paid) {
                                    //if the count of paid installment equals to index of loop
                                    if (paid_installment_count == index) {
                                        // then show the cross sign
                                        html += '<tr class="installment_rows"><td scope="row" class="text-left"><span class="remove-installment-fees-paid text-left" data-id="' + installment_data.paid_id + '"><i class="fa fa-times text-danger" style="cursor:pointer" aria-hidden="true"></i></span></td><td colspan="2" class="text-left"><lable>' + installment_data.name + '<lable><br><small class="text-success">' + lang_paid_on + ': ' + installment_data.paid_on + '</small></td><td class="text-right"><lable>' + total_installment_amount + '</lable></td></tr>'
                                    } else {
                                        html += '<tr class="installment_rows"><td scope="row" class="text-left"></td><td colspan="2" class="text-left"><lable>' + installment_data.name + '<lable><br><small class="text-success">' + lang_paid_on + ': ' + installment_data.paid_on + '</small></td><td class="text-right"><lable>' + total_installment_amount + '</lable></td></tr>'
                                    }
                                } else {
                                    // show the check box - only enable if previous installment is paid
                                    let isDisabled = (index > lastPaidIndex + 1) ? 'disabled' : '';
                                    html += '<tr class="installment_rows"><td scope="row" class="text-left"><input type="checkbox" name="installment_fees[' + index + '][id]" class="installment-chkclass" value="' + installment_data.id + '" data-amount="' + total_installment_amount + '" ' + isDisabled + '></td><td colspan="2" class="text-left"><lable>' + installment_data.name + '<lable><br><small class="text-danger">' + lang_due_date_on + ': ' + installment_data.due_date + ',<br>' + lang_charges + ': ' + installment_data.due_charges + ' %</small></td><td class="text-right"><lable>' + installment_amount + '<br><small>' + due_charges + '</small><br><hr>' + total_installment_amount + '</lable><input type="hidden" name="installment_fees[' + index + '][amount]" value="' + total_installment_amount + '"><input type="hidden" name="installment_fees[' + index + '][fully_paid]" value="1"><input type="hidden" name="installment_fees[' + index + '][due_charges]" value="' + due_charges + '"></td></tr>'
                                }
                            } else {
                                // if the installment is paid
                                if (installment_data.paid) {
                                    //if the count of paid installment equals to index of loop
                                    if (paid_installment_count == index) {
                                        // then show the cross sign
                                        html += '<tr class="installment_rows"><td scope="row" class="text-left"><span class="remove-installment-fees-paid text-left" data-id="' + installment_data.paid_id + '"><i class="fa fa-times text-danger" style="cursor:pointer" aria-hidden="true"></i></span></td><td colspan="2" class="text-left"><lable>' + installment_data.name + '<lable><br><small class="text-success">' + lang_paid_on + ': ' + installment_data.paid_on + '</small></td><td class="text-right"><lable>' + total_installment_amount + '</lable></td></tr>'
                                    } else {
                                        html += '<tr class="installment_rows"><td scope="row" class="text-left"></td><td colspan="2" class="text-left"><lable>' + installment_data.name + '<lable><br><small class="text-success">' + lang_paid_on + ': ' + installment_data.paid_on + '</small></td><td class="text-right"><lable>' + total_installment_amount + '</lable></td></tr>'
                                    }
                                } else {
                                    // show the check box - only enable if previous installment is paid
                                    let isDisabled = (index > lastPaidIndex + 1) ? 'disabled' : '';
                                    html += '<tr class="installment_rows"><td scope="row" class="text-left"><input type="checkbox" name="installment_fees[' + index + '][id]" class="installment-chkclass" value="' + installment_data.id + '" data-amount="' + total_installment_amount + '" ' + isDisabled + '></td><td colspan="2" class="text-left"><lable>' + installment_data.name + '<lable><br><small class="text-danger">' + lang_due_date_on + ': ' + installment_data.due_date + ',<br>' + lang_charges + ': ' + installment_data.due_charges + ' %</small></td><td class="text-right"><lable>' + installment_amount + '<br><small>' + due_charges + '</small><br><hr>' + total_installment_amount + '</lable><input type="hidden" name="installment_fees[' + index + '][amount]" value="' + total_installment_amount + '"><input type="hidden" name="installment_fees[' + index + '][fully_paid]" value="0"><input type="hidden" name="installment_fees[' + index + '][due_charges]" value="' + due_charges + '"></td></tr>'
                                }
                            }
                        } else {
                            // if the due charges is not applicable to installment
                            // if the data is last
                            total = (Number(total) + Number(installment_amount)).toFixed(2);
                            if (row.installment_data.length == (index + 1)) {
                                if (installment_data.paid) {
                                    if (paid_installment_count == index) {
                                        html += '<tr class="installment_rows"><td scope="row" class="text-left"><span class="remove-installment-fees-paid text-left" data-id="' + installment_data.paid_id + '"><i class="fa fa-times text-danger" style="cursor:pointer" aria-hidden="true"></i></span></td><td colspan="2" class="text-left"><lable>' + installment_data.name + '<lable><br><small class="text-success">' + lang_paid_on + ': ' + installment_data.paid_on + '</small></td><td class="text-right"><lable>' + installment_amount + '</lable></td></tr>'
                                    } else {
                                        html += '<tr class="installment_rows"><td scope="row" class="text-left"></td><td colspan="2" class="text-left"><lable>' + installment_data.name + '<lable><br><small class="text-success">' + lang_paid_on + ': ' + installment_data.paid_on + '</small></td><td class="text-right"><lable>' + installment_amount + '</lable></td></tr>'
                                    }
                                } else {
                                    // show the check box - only enable if previous installment is paid
                                    let isDisabled = (index > lastPaidIndex + 1) ? 'disabled' : '';
                                    html += '<tr class="installment_rows"><td scope="row" class="text-left"><input type="checkbox" name="installment_fees[' + index + '][id]" class="installment-chkclass" value="' + installment_data.id + '" data-amount="' + installment_amount + '" ' + isDisabled + '></td><td colspan="2" class="text-left"><lable>' + installment_data.name + '<lable><br><small>' + lang_due_date_on + ': ' + installment_data.due_date + ',</small><br><small>' + lang_charges + ': ' + installment_data.due_charges + ' %</small></td><td class="text-right"><lable>' + installment_amount + '</lable><input type="hidden" name="installment_fees[' + index + '][amount]" value="' + installment_amount + '"><input type="hidden" name="installment_fees[' + index + '][fully_paid]" value="1"></td></tr>'
                                }
                            } else {
                                if (installment_data.paid) {
                                    if (paid_installment_count == index) {
                                        html += '<tr class="installment_rows"><td scope="row" class="text-left"><span class="remove-installment-fees-paid text-left" data-id="' + installment_data.paid_id + '"><i class="fa fa-times text-danger" style="cursor:pointer" aria-hidden="true"></i></span></td><td colspan="2" class="text-left"><lable>' + installment_data.name + '<lable><br><small class="text-success">' + lang_paid_on + ': ' + installment_data.paid_on + '</small></td><td class="text-right"><lable>' + installment_amount + '</lable></td></tr>'
                                    } else {
                                        html += '<tr class="installment_rows"><td scope="row" class="text-left"></td><td colspan="2" class="text-left"><lable>' + installment_data.name + '<lable><br><small class="text-success">' + lang_paid_on + ': ' + installment_data.paid_on + '</small></td><td class="text-right"><lable>' + installment_amount + '</lable></td></tr>'
                                    }
                                } else {
                                    // show the check box - only enable if previous installment is paid
                                    let isDisabled = (index > lastPaidIndex + 1) ? 'disabled' : '';
                                    html += '<tr class="installment_rows"><td scope="row" class="text-left"><input type="checkbox" name="installment_fees[' + index + '][id]" class="installment-chkclass" value="' + installment_data.id + '" data-amount="' + installment_amount + '" ' + isDisabled + '></td><td colspan="2" class="text-left"><lable>' + installment_data.name + '<lable><br><small>' + lang_due_date_on + ': ' + installment_data.due_date + ',</small><br><small>' + lang_charges + ': ' + installment_data.due_charges + ' %</small></td><td class="text-right"><lable>' + installment_amount + '</lable><input type="hidden" name="installment_fees[' + index + '][amount]" value="' + installment_amount + '"><input type="hidden" name="installment_fees[' + index + '][fully_paid]" value="0"></td></tr>'
                                }
                            }
                        }
                    });
                } else {
                    total = row.base_amount_with_due_charges;
                }
            } else {
                total = row.base_amount_with_due_charges;
            }
            if (row.fees_status == null) {
                total = row.base_amount_with_due_charges
                    ? row.base_amount_with_due_charges
                    : row.base_amount; // fallback to base_amount when no due charges
            }
            html += '<tr><td scope="row" class="text-left"></td><td colspan="2" class="text-left"></td><td class="text-right"><hr></td></tr>';

            html += '<tr><td scope="row" class="text-left"></td><td colspan="2" class="text-left">' + lang_total_amount + ' </td><td class="text-right compulsory_amount"></td></tr>';
            html += '</tbody></table>';

            // STEP 1: Inject HTML into DOM first so .compulsory_amount exists
            $('.compulsory_fees_content').html(html);
            $('.installment_rows').hide();

            // STEP 2: Now safely set the amount on the injected element
            if (row.due_charges.charges) {
                $('.compulsory_amount').html(parseFloat(row.base_amount_with_due_charges).toFixed(2));
                $('#total_amount').val(row.base_amount_with_due_charges);
            } else {
                $('.compulsory_amount').html(parseFloat(row.base_amount).toFixed(2));
                $('#total_amount').val(row.base_amount);
            }

            // STEP 3: Override with installment-calculated total when fees_status is set
            if (row.fees_status != null) {
                $('.compulsory_amount').html(parseFloat(total).toFixed(2));
                $('.total_amount').val(parseFloat(total).toFixed(2));
            }

            // make Installment Payment Enabled disabled
            if (row.is_installment_paid) {
                $(document).find('.pay_in_installment').click();
                $(document).find('.pay_in_installment').attr('disabled', true);
            } else if (row.fees_status == 1) {
                $(document).find('.pay_in_installment_row').hide(200);
                $(document).find('.pay_in_installment').attr('disabled', true);
            } else {
                $(document).find('.pay_in_installment').attr('disabled', false);
            }
            if (row.fees_status == 0) {
                if (row.mode == 2) {
                    $(document).find('.compulsory_fees_payment').prop('disabled', true);
                } else {
                    $(document).find('.compulsory_fees_payment').prop('disabled', false);
                }
            }
            else {
                $(document).find('.compulsory_fees_payment').prop('disabled', false);
                $('.compulsory_amount').html(parseFloat(total).toFixed(2));
                $('.total_amount').val(parseFloat(total).toFixed(2));
            }


            $('.installment-chkclass').on('click', function (e) {
                let choice_amount = parseFloat($('.compulsory_amount').html());
                if ($(this).is(':checked')) {
                    $(document).find('.mode_container').show(200);
                    $(this).addClass('added_price');
                    $(this).removeClass('installment-chkclass');
                    choice_amount += parseFloat($(this).data("amount"));
                    $('.compulsory_amount').html((choice_amount).toFixed(2));
                    $('.total_amount').val((choice_amount).toFixed(2));
                } else {
                    $(document).find('.mode_container').hide(200);
                    $(this).removeClass('added_price');
                    $(this).addClass('installment-chkclass');
                    choice_amount -= parseFloat($(this).data("amount"));
                    $('.compulsory_amount').html((choice_amount).toFixed(2));
                    $('.total_amount').val((choice_amount).toFixed(2));
                }
                $('.pay_in_installment').on('change', function () {
                    if (!$(this).is(':checked')) {
                        $('.added_price').prop('checked', false);
                    }
                });

                // Check the Amount And Make PAY Button Clickable Or Not
                if (choice_amount > 1) {
                    $(document).find('.compulsory_fees_payment').prop('disabled', false);
                } else {
                    $(document).find('.compulsory_fees_payment').prop('disabled', true);
                }

                $('#total_amount').val(choice_amount);
            });
        } else {
            $('.compulsory_div').hide();
        }
    },
    'click .optional-data': function (e, value, row, index) {
        let total = 0;
        let count = 0;
        // Disable PAY Button
        $(document).find('.optional_fees_payment').prop('disabled', true);

        // Add data in Modal
        $('#optional_student_id').val(row.student_id);
        $('#optional_class_id').val(row.class_id);
        $('.student_name').html(row.student_name + ' - ' + row.class_name);
        $('.current-date').val(row.current_date);

        if (row.mode == 1 && (row.type_of_fee == 2 || row.type_of_fee == null)) {
            $(document).find('.mode_container').show(200);
            $('.cash_mode').attr('checked', false)
            $('.cheque_mode').attr('checked', true).change();
            $(document).find('.cheque_no').val(row.cheque_no)
        } else if (row.mode == 0 && (row.type_of_fee == 2 || row.type_of_fee == null)) {
            $(document).find('.mode_container').show(200);
            $(document).find('.cheque_no').val(null)
            $('.cheque_mode').attr('checked', false);
            $('.cash_mode').attr('checked', true).change();
        } else if (row.mode == null) {
            $(document).find('.mode_container').show(200);
            $(document).find('#cheque_no').val(null)
            $('.cheque_mode').attr('checked', false);
            $('.cash_mode').attr('checked', true).change();
        } else {
            $(document).find('.mode_container').hide(200);
        }

        // IF Optional Fee is not Empty
        if (row.choiceable_fees.length) {
            // Make Optional DIV visible
            $('.optional_div').show();

            // Declare HTML
            let html = '';

            // Adding the data of Optional Fees with Paid Optional Fee
            html = '<table class="table"><tbody>'
            $.each(row.choiceable_fees, function (index, value) {
                // IF is Paid then add CROSS ICON for Delete Else Add Checkbox
                total = (Number(total) + Number(value.amount)).toFixed(2);
                if (value.is_paid) {
                    if (value.date) {
                        html += '<tr><td scope="row" class="text-left"><span class="remove-optional-fees-paid text-left" data-id="' + value.paid_id + '"><i class="fa fa-times text-danger" style="cursor:pointer" aria-hidden="true"></i></span></td><td colspan="2" class="text-left">' + value.name + '<br><span class="text-small text-success">(' + lang_paid_on + ' :- ' + value.date + ')</span></td><td class="text-right">' + (value.amount).toFixed(2) + '</td></tr>'
                    } else {
                        html += '<tr><td scope="row" class="text-left"><span class="remove-optional-fees-paid text-left" data-id="' + value.paid_id + '"><i class="fa fa-times text-danger" style="cursor:pointer" aria-hidden="true"></i></span></td><td colspan="2" class="text-left">' + value.name + '</td><td class="text-right">' + (value.amount).toFixed(2) + '</td></tr>'
                    }
                } else {
                    count = count + 1;
                    html += '<tr><td scope="row" class="text-left"><input type="checkbox" class="chkclass" name="optional_fees_type_data[' + index + '][id]" value="' + value.fees_type_id + '" data-amount="' + value.amount + '"></td><td colspan="2" class="text-left">' + value.name + '</td><td class="text-right">' + (value.amount).toFixed(2) + '<input type="hidden" name="optional_fees_type_data[' + index + '][amount]" value=' + value.amount + '></td></tr>'
                }
            });
            html += '<tr><td scope="row" class="text-left"></td><td colspan="2" class="text-left"></td><td class="text-right"><hr></td></tr>';

            // Add Total Amount Section
            html += '<tr><td></td><td colspan="2" class="text-left">' + lang_total_amount + ' </td><td class="text-right"><span class="optional_total_amount_label"></span><input type="hidden" name="total_amount" class="optional_total_amount"></td></tr></tbody></table>';

            // Add The Html to Optional DIV
            $('.optional_fees_content').html(html);

            if (count > 0) {
                $('.optional_total_amount_label').html((0).toFixed(2));
            } else {
                // Make Total Amount Fixed to 2 Decimal Points
                $('.optional_total_amount_label').html(parseFloat(total).toFixed(2));
            }

            // Get the Amount Of Total Amount From DIV
            let choice_amount = parseInt($('.optional_total_amount_label').html());
            $('.chkclass').on('click', function (e) {

                // Check if Checkbox Checked or not then Update the total Amount Accordingly
                if ($(this).is(':checked')) {
                    $(document).find('.mode_container').show(200);
                    $(this).addClass('added_price');
                    $(this).removeClass('chkclass');
                    (choice_amount += $(this).data("amount")).toFixed(2);
                    $('.optional_total_amount_label').html((choice_amount).toFixed(2));
                    $('.optional_total_amount').val((choice_amount).toFixed(2));
                } else {
                    $(this).removeClass('added_price');
                    $(this).addClass('chkclass');
                    (choice_amount -= $(this).data("amount")).toFixed(2);
                    $('.optional_total_amount_label').html((choice_amount).toFixed(2));
                    $('.optional_total_amount').val((choice_amount).toFixed(2));
                }

                // Check the Amount And Make PAY Button Clickable Or Not
                if (choice_amount > 1) {
                    $(document).find('.optional_fees_payment').prop('disabled', false);
                } else {
                    $(document).find('.optional_fees_payment').prop('disabled', true);
                }
            });
        } else {
            $('.compulsory_div').hide();
        }
    },

    'click .edit-data': function (e, value, row, index) {
        $('#edit_id').val(row.id);
        $('#edit_student_id').val(row.student_id);
        $('#edit_class_id').val(row.class_id);
        $('.edit_total_amount').val(row.total_fees);
        $('.edit_student_name').html(row.student_name + ' - ' + row.class_name);
        $('.edit_date').val(row.formatted_date);
        if (row.mode) {
            $('#edit_mode_cheque').attr('checked', true);
            $('.edit_cheque_no_container').show(200);
            $('#edit_cheque_no').val(row.cheque_no);
        } else {
            $('#edit_mode_cash').attr('checked', true);
            $('.edit_cheque_no_container').hide(200);
        }

        if (row.fees_class_choiceable_data != null || row.fees_class_paid_choiceable_data != null) {
            $('.edit_choiceable_div').show();
            let html = '';
            html += '<div class="form-check form-check-inline"><label class="edit_paid_amount" data-amount=' + row.total_fees + '>Paid amount - ' + row.total_fees + '</label></div>'
            $.each(row.fees_class_choiceable_data, function (index, value) {
                html += '<div class="form-check form-check-inline"><label class="form-check-label"><input type="checkbox" name="add_new_choiceable_fees[]" class="form-check-input edit_new_chkclass" value="' + value.fees_type_id + '" data-amount="' + value.amount + '">' + value.fees_type.name + ' - ' + value.amount + '<i class="input-helper"></i></label></div>'
            });
            if (row.fees_class_paid_choiceable_data != null) {
                html += '<hr>';
                $.each(row.fees_class_paid_choiceable_data, function (index, value) {
                    html += '<div><label><a href="#" data-id=' + value.id + ' data-amount=' + value.amount + ' style="color:red" class="remove-paid-choiceable-fees"><i class="fa fa-remove"></i></a> ' + value.fees_type.name + ' - ' + value.amount + '</label></div>'
                });
            }
            $('.edit_choiceable_fees_content').html(html);
            $('.edit_total_amount_label').html(row.total_fees);
            let choice_amount = parseInt(row.total_fees);
            $('.edit_new_chkclass').on('click', function (e) {
                if ($(this).is(':checked')) {
                    $(this).addClass('added_price');
                    $(this).removeClass('chkclass');
                    choice_amount += $(this).data("amount");
                    $('.edit_total_amount_label').html(choice_amount);
                    $('.edit_total_amount').val(choice_amount);
                } else {
                    $(this).addClass('added_price');
                    $(this).removeClass('chkclass');
                    choice_amount -= $(this).data("amount");
                    $('.edit_total_amount_label').html(choice_amount);
                    $('.edit_total_amount').val(choice_amount);
                }
            });
        } else {
            $('.edit_choiceable_div').hide();
        }
    }
};

window.onlineExamEvents = {
    'click .edit-data': function (e, value, row, index) {
        console.log(row);
        $('#edit_id').val(row.online_exam_id);
        $('#edit-online-exam-semester').val(row.semester_name || '');
        $('#edit-online-exam-semester-id').val(row.semester_id || '');
        $('#edit-online-exam-title').val(row.title);
        $('#edit-online-exam-key').val(row.exam_key);
        $('#edit-online-exam-duration').val(row.duration);

        // Convert date format from "DD-MM-YYYY HH:MM:SS" to "YYYY-MM-DDTHH:MM" for datetime-local input
        // Split the date string by space to separate date and time
        if (row.start_date) {
            let startParts = row.start_date.split(' '); // ["10-10-2024", "14:30:00"]
            let startDateParts = startParts[0].split('-'); // ["10", "10", "2024"]
            let startTime = startParts[1].substring(0, 5); // "14:30" (remove seconds)
            // Rearrange to YYYY-MM-DDTHH:MM format
            $('#edit-online-exam-start-date').val(startDateParts[2] + '-' + startDateParts[1] + '-' + startDateParts[0] + 'T' + startTime);
        }

        if (row.end_date) {
            let endParts = row.end_date.split(' '); // ["10-10-2024", "14:30:00"]
            let endDateParts = endParts[0].split('-'); // ["10", "10", "2024"]
            let endTime = endParts[1].substring(0, 5); // "14:30" (remove seconds)
            // Rearrange to YYYY-MM-DDTHH:MM format
            $('#edit-online-exam-end-date').val(endDateParts[2] + '-' + endDateParts[1] + '-' + endDateParts[0] + 'T' + endTime);
        }
    },
};

window.onlineExamQuestionsEvents = {
    'click .edit-data': function (e, value, row, index) {
        $('#edit_id').val(row.online_exam_question_id);
        $('.edit_question_type').val(row.question_type);
        $('#edit-online-exam-class-id').val(row.class_id).trigger('change');

        //added the subject on class id after 0.5 seconds
        setTimeout(() => {
            $('#edit-online-exam-subject-id').val(row.subject_id).trigger('change');
        }, 1000);

        if (row.question_type) {
            $('.edit_question').html('')
            $('.edit_option_container').html('')
            // set data in question text area
            CKEDITOR.instances['edit_equestion'].setData(row.question_row)

            $('#edit-simple-question').hide(100)
            $('#edit-equation-question').show(300);
            $('.edit_eoption_container').html('')

            let html_option = '';
            $.each(row.options, function (index, value) {
                if (index >= 2) {
                    html_option += '<div class="form-group col-md-6"><input type="hidden" class="edit_eoption_id" name="edit_eoption[' + (index + 1) + '][id]" value=' + value.id + '><label>' + lang_option + ' <span class="edit-eoption-number">' + (index + 1) + '</span> <span class="text-danger">*</span></label><textarea class="editor_options" name="edit_eoption[' + (index + 1) + '][option]" placeholder="' + lang_enter_option + '">' + value.option_row + '</textarea><div class="remove-edit-option-content"><button type="button" class="btn btn-inverse-danger remove-edit-option btn-sm mt-1" data-id="' + value.id + '"><i class="fa fa-times"></i></button></div></div>'
                    $('.edit_eoption_container').html(html_option);
                } else {
                    html_option += '<div class="form-group col-md-6"><input type="hidden" class="edit_eoption_id" name="edit_eoption[' + (index + 1) + '][id]" value=' + value.id + '><label>' + lang_option + ' <span class="edit-eoption-number">' + (index + 1) + '</span> <span class="text-danger">*</span></label><textarea class="editor_options" name="edit_eoption[' + (index + 1) + '][option]" placeholder="' + lang_enter_option + '">' + value.option_row + '</textarea></div>'
                    $('.edit_eoption_container').html(html_option);
                }
            });
            createCkeditor();
        } else {
            $('#edit-equation-question').hide(100);
            $('#edit-simple-question').show(300);
            $('.edit_option_container').html('')

            $('.edit-question').html(row.question);
            // add options and add the options in answers
            let html = ''
            $.each(row.options, function (index, value) {
                if (index >= 2) {
                    html = '<div class="form-group col-md-6"><input type="hidden" class="edit_option_id" name="edit_options[' + (index + 1) + '][id]" value=' + value.id + '><label>' + lang_option + ' <span class="edit-option-number"> ' + (index + 1) + '</span> <span class="text-danger">*</span></label><input type="text" name="edit_options[' + (index + 1) + '][option]" value="' + value.option + '" placeholder="' + lang_enter_option + '" class="form-control add-edit-question-option" /><div class="remove-edit-option-content"><button type="button" class="btn btn-inverse-danger remove-edit-option btn-sm mt-1" data-id="' + value.id + '"><i class="fa fa-times"></i></button></div></div>';
                } else {
                    html = '<div class="form-group col-md-6"><input type="hidden" class="edit_option_id" name="edit_options[' + (index + 1) + '][id]" value=' + value.id + '><label>' + lang_option + ' <span class="edit-option-number"> ' + (index + 1) + '</span> <span class="text-danger">*</span></label><input type="text" name="edit_options[' + (index + 1) + '][option]" value="' + value.option + '" placeholder="' + lang_enter_option + '" class="form-control add-edit-question-option" /><div class="remove-edit-option-content"></div></div>';
                }
                $('.edit_option_container').append(html);
            });
        }
        $('.answers_db').html('');
        $('.edit_answer_select').html('');
        if (row.answers.length) {
            $.each(row.options, function (index, value) {
                $.each(row.answers, function (answer_index, answer_value) {
                    if (value.id == answer_value.option_id) {
                        if (row.answers.length == 1) {
                            let html = '<i class="fa fa-circle" aria-hidden="true"></i> ' + lang_option + ' ' + (index + 1) + '<br>';
                            $('.answers_db').append(html);
                            return false;
                        } else {
                            let html = '<i class="fa fa-circle" aria-hidden="true"></i> ' + lang_option + ' ' + (index + 1) + ' <span class="fa fa-times text-danger remove-answers" data-id=' + answer_value.id + ' style="cursor:pointer"></span><br>';
                            $('.answers_db').append(html);
                            return false;
                        }
                    }
                });
            });
        }

        if (row.options_not_answers) {
            $.each(row.options, function (index, value) {
                $.each(row.options_not_answers, function (answer_index, option_data) {
                    if (value.id == option_data.id) {
                        $('.edit_answer_select').append('<option value="' + (option_data.id) + '">' + lang_option + ' ' + (index + 1) + '</option>');
                        return false;
                    }
                });
            });
        }

        $('.edit_answer_select').ready(function () {
            if ($('.answers_db').html() == '') {
                $('.edit_answer_select').attr('required', true);
            } else {
                $('.edit_answer_select').removeAttr('required');
            }
        })
        $('#image_preview').attr('src', row.image);
        $('.edit_note').val(row.note);
    },
};

window.teacherActionEvents = {
    'click .editdata': function (e, value, row, index) {
        $('input[type=checkbox].edit_checkbox').removeAttr('checked');
        $('.edit_text_number').val('');
        $('.edit_dropdown').val('');
        $('.edit_textarea').val('');
        $('.edit_radio').removeAttr('checked');
        $('.edit_file').css('display', 'none');

        $('#id').val(row.id);
        $('#user_id').val(row.user_id);
        $('#first_name').val(row.first_name);
        $('#last_name').val(row.last_name);
        $('input[name=gender][value=' + row.gender + '].edit').prop('checked', true);
        $('#current_address').val(row.current_address);
        $('#permanent_address').val(row.permanent_address);
        $('#email').val(row.email);
        $('#mobile').val(row.mobile);
        $('#dob').val(row.dob);
        $('#qualification').val(row.qualification);

        // DYNAMIC FIELDS
        setTimeout(function () {
            $.each(row.dynamic_data_field, function (key, value) {
                $('#' + id + '-div').css('display', 'none');

                var id = Object.keys(row.dynamic_data_field[key])[0];
                var id_with_hash = '#' + Object.keys(row.dynamic_data_field[key])[0];
                $('#' + id).val('');


                // Confirm is checkbox or not
                let count = 0;
                Object.keys(value).forEach(function (key) {
                    let value_1 = value[key];
                    if (typeof value_1 === 'object') {
                        count++;
                    }
                });

                if (count == 0) {
                    let myDiv = document.getElementById(id);
                    let tagName = '';
                    // TEXT / NUMBER / TEXTAERA / SELECT / FILE
                    if (myDiv) {
                        tagName = myDiv.tagName;
                        if (tagName == 'A') { // IF INPUT TYPE FILE
                            if (value[id]) {
                                $('#' + id + '-div').css('display', 'block');
                                $(id_with_hash).attr('href', 'storage/' + value[id]);
                                $('#' + id + '-hidden').val(value[id]);
                                $('#file-' + id + '').val(value[id]);
                                $(id_with_hash + '-name').val(value[id]);
                            }
                        } else { // TEXT / NUMBER / TEXTAERA / SELECT
                            // let input_type = $(id_with_hash).attr('type');
                            $(id_with_hash).val('');
                            $(id_with_hash).val(value[id]);
                        }

                    } else {
                        // RADIO
                        let input_type = $('#' + value[id]).attr('type');
                        if (input_type == 'radio') {
                            $('#' + value[id]).attr('checked', true);
                        }
                    }

                } else { // CHECKBOX
                    let checkbox_value = [];
                    $.each(value, function (key_1, value_1) {
                        if (value_1 != null) {
                            checkbox_value.push(Object.keys(value_1));
                        }
                    })

                    $.each(checkbox_value, function (key_1, value_1) {
                        $.each(value_1, function (key_2, value_2) {
                            $('#checkbox_' + value_2).attr('checked', true);
                        })
                    })

                }

            });
        }, 1000);
        // END DYNAMIC FIELDS
        if (row.has_student_permissions) {
            $('.edit_permission_chk').prop("checked", true);
            $('.edit_permission_chk').addClass('warning_ckh');
            $(document).on('change', '.warning_ckh', function () {
                if (!this.checked) {
                    Swal.fire({
                        title: lang_delete_title,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: lang_yes_uncheck
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $(this).prop("checked", false);
                        } else {
                            $(this).prop("checked", true);
                        }
                    })
                }
            });
        } else {
            $('.edit_permission_chk').prop("checked", false);
            $('.edit_permission_chk').removeClass('warning_ckh');
        }
    }
}

window.sliderEvents = {
    'click .edit-data': function (e, value, row, index) {
        $('#edit_id').val(row.id);
        $('#edit_type').val(row.type);
        // Select the file input field
        var fileInput = $('.edit_image');

        // Clear the selected file by resetting the input value
        fileInput.val(null);

        // Update the text input field to display "No file selected"
        fileInput.siblings('.form-control').val('');

        $('#edit_slider_image').attr('src', row.image);
    }
}

window.classEvents = {
    'click .edit-data': function (_e, _value, row) {

        $('#edit_id').val(row.id);
        $('#edit_name').val(row.name);
        $('#edit_stream_id').val(row.stream_id);
        $('#edit_shift_id').val(row.shift_id);
        $('#edit_medium_id').val(row.medium_id);
        $('#edit_educational_program_id').val(row.educational_program_id);
        $('#edit_section_id').val(row.section_id).trigger('change');

        if (row.include_semesters == 1) {
            $('#edit_include_semesters').prop('checked', true).change();
        } else {
            $('#edit_include_semesters').prop('checked', false).change();
        }
    }
};

// Session Year
window.sessionYearEvents = {
    'click .editdata': function (e, value, row, index) {
        // Reset all edit modal input fields before loading new data
        $('#editModal').find('input[type="text"], input[type="hidden"], input[type="number"], input[type="date"]').val('');
        $('#editModal').find('select').val('').trigger('change');
        $('#edit_include_fee_installments').val('').trigger('change');
        $('.edit-installment-container').html('');
        $('.installment-div').hide();

        // Now load the data
        $('#id').val(row.id);
        $('#name').val(row.name);
        $('#free_app_use_date').val(row.free_app_use_date ? row.free_app_use_date : '');
        $('#start_date').val(row.start_date);
        $('#end_date').val(row.end_date);
        $('#fees_due_date').val(row.fees_due_date);
        $('#fees_due_charges').val(row.fees_due_charges);
        $('#edit_include_fee_installments').val(row.include_fee_installments).trigger('change');

        // Store raw ISO dates on hidden input for use by "add new installment" handler
        $('#id').attr('data-start-date-raw', row.start_date_raw || '');
        $('#id').attr('data-end-date-raw', row.end_date_raw || '');

        // Helper: parse ISO "Y-m-d" string reliably into a JS Date at midnight
        function parseISO(str) {
            if (!str) return null;
            var parts = str.split('-');
            if (parts.length !== 3) return null;
            // Use explicit constructor to avoid timezone-shift issues with new Date('Y-m-d')
            return new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        }

        // Use raw ISO dates (Y-m-d) from API — always format-independent
        var startDateObj = parseISO(row.start_date_raw);
        var endDateObj = parseISO(row.end_date_raw);

        // --- fees_due_date: must be >= start_date and <= end_date ---
        $('#fees_due_date').datepicker('destroy').removeClass('hasDatepicker');
        $('#fees_due_date').datepicker({
            enableOnReadonly: false,
            todayHighlight: true,
            format: 'dd-mm-yyyy',
            autoclose: true,
            startDate: startDateObj,
            endDate: endDateObj,
        });

        // --- free_app_use_date: must be strictly after today ---
        var tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(0, 0, 0, 0);

        $('#free_app_use_date').datepicker('destroy').removeClass('hasDatepicker');
        $('#free_app_use_date').datepicker({
            enableOnReadonly: false,
            todayHighlight: true,
            format: 'dd-mm-yyyy',
            autoclose: true,
            startDate: tomorrow,
        });

        if (row.fee_installments && row.fee_installments.length) {
            let html = '';
            $('.edit-installment-container').html("");
            $('.installment-div').show();
            $.each(row.fee_installments, function (key, value) {
                if (!$('.edit-installment-container:last').is(':empty')) {
                    html = $('.edit-installment-container').find('.edit-installment-content:last').clone();
                } else {
                    html = $('.edit-installment-content-template').clone();
                }
                html.addClass('edit-installment-content').removeClass('edit-installment-content-template');
                html.css('display', 'block');
                html.find('.error').remove();
                html.find('.has-danger').removeClass('has-danger');
                // Update name and id attributes for each input
                html.find('.form-control').each(function (_key, element) {
                    this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
                        return '[' + (parseInt(p1, 10) + 1) + ']';
                    });
                    this.id = this.id.replace(/_(\d+)/, function (str, p1) {
                        return '_' + (parseInt(p1, 10) + 1);
                    });
                });
                html.find('#editInstallmentId_' + (key + 1)).val(value.id);
                html.find('#editInstallmentName_' + (key + 1)).val(value.name);
                html.find('#editInstallmentDueDate_' + (key + 1)).val(value.due_date);
                html.find('#editInstllmentDueCharges_' + (key + 1)).val(value.due_charges);

                html.find('.add-edit-fee-installment-content i').addClass('fa-times').removeClass('fa-plus');
                html.find('.add-edit-fee-installment-content').addClass('btn-inverse-danger remove-edit-fee-installment-content').removeClass('btn-inverse-success add-edit-fee-installment-content');
                html.find('.remove-edit-fee-installment-content')
                    .attr("data-id", value.id)
                    .attr(
                        'data-related',
                        '["installments paid"]'
                    );
                $('.edit-installment-container').append(html);

                // Initialize datepicker with min/max constraints
                let dueDateInput = html.find('#editInstallmentDueDate_' + (key + 1));
                dueDateInput.datepicker('destroy').removeClass('hasDatepicker');
                dueDateInput.datepicker({
                    enableOnReadonly: false,
                    todayHighlight: true,
                    format: 'dd-mm-yyyy',
                    autoclose: true,
                    startDate: startDateObj,
                    endDate: endDateObj,
                });
            });
        } else {
            $('.installment-div').hide();
            $('.edit-installment-container').html("");
        }
    }
};

window.formFieldsEvents = {
    'click .edit-data': function (_e, _value, row, _index) {

        $('#edit-id').val(row.id);
        $('#edit-name').val(row.name);
        $('#edit-type').val(row.type).trigger('change');
        $('#edit-type').prop('disabled', true);
        $('#edit-type-value').val(row.type);

        $('#edit-for').val(row.for).trigger('change');
        $('#edit-for-value').val(row.for);
        if (row.is_required) {
            $('#edit-required').prop('checked', true).change();
        } else {
            $('#edit-required').prop('checked', false).change();
        }
        $('.edit-remove-default-values').each(function () {
            $(this).trigger('click');
        })
        if (row.default_values) {
            for (let i = 2; i < row.default_values.length; i++) {
                $('.edit-add-more-default-values').trigger('click');
            }

            $('.edit_default_values').each(function (index, _value) {
                $(this).val(row.default_values[index]);
            })
        }
    }
};

window.leavesSettingEvents = {
    'click .edit-data': function (e, value, row, index) {
        $('#edit_id').val(row.id);
        $('#total_leave').val(row.total_leave);
        $('#session_year').val(row.session_year_id);

        let holidays = row.holiday_days;
        var holiday = holidays.split(",");

        $('#holiday_days').val(holiday).trigger('change');

    },

}

window.leavesEvents = {
    'click .edit-data': function (e, value, row) {

        let html_file = '';
        $('#id').val(row.id);
        $('#edit_from_date').val(row.from_date);
        $('#edit_to_date').val(row.to_date);
        $('#edit_reason').val(row.reason);
        $('#reject_reason').val(row.reason_of_rejection);

        if (row.file) {
            $.each(row.file, function (key, data) {
                html_file += '<div class="file"><a target="_blank" href="' + data.file_url + '" class="m-1">' + data.file_name + '</a></span><br><br></div>'
            })

            $('#attachment').html(html_file);
        }

        setTimeout(() => {
            $('#edit_to_date').trigger('change');
        }, 500);
        setTimeout(() => {
            $.each(row.leave_detail, function (index, value) {
                $('input[name="type[' + moment(value.date, 'YYYY-MM-DD').format('DD-MM-YYYY') + '][]"][value="' + value.type + '"].form-check-input').prop('checked', true);
            });
        }, 500);


        // Set radio value
        $('input[name=status][value=' + row.status + '].leave-status').prop('checked', true);

        // Force change event so your show/hide logic runs
        $('input[name="status"]:checked').trigger('change');
    }
};

function formatDate(dateString) {
    const dateParts = dateString && dateString.match(/(\d{2})-(\d{2})-(\d{4})(?: (\d{2}):(\d{2}):(\d{2}))?/);
    const d = dateParts ? new Date(dateParts[3], dateParts[2] - 1, dateParts[1]) : new Date(dateString);
    if (isNaN(d)) return ''; // safety
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}-${month}-${year}`;
}

$('.datepicker-popup').datepicker({
    format: 'dd-mm-yyyy',
    autoclose: true
});

window.semesterEvents = {
    'click .edit-data': function (e, value, row, index) {
        $('#edit_id').val(row.id);
        $('#edit_name').val(row.name);

        $('#edit_start_date').datepicker('update', formatDate(row.start_date));
        $('#edit_end_date').datepicker('update', formatDate(row.end_date));
    }
};

function studentNameFormatter(value, row, index) {
    // Default placeholder image path
    const placeholderImage =
        `https://ui-avatars.com/api/?name=${encodeURIComponent(value)}&background=10B981&color=fff`

    // Get image path from row data
    const imagePath = row.photo ? row.photo : placeholderImage;
    // const imagePath = row.photo ? "{{ asset('') }}" + row.photo : placeholderImage;

    // Return HTML with image and name
    return `
        <div class="d-flex align-items-center">
            <img src="${imagePath}" 
                 alt="${value}" 
                 class="rounded-circle me-2" 
                 style="width: 35px; height: 35px; object-fit: cover; margin-right: 6px;"
                 onerror="this.onerror=null; this.src='${placeholderImage}';">
            <span>${value}</span>
        </div>
    `;
}

$(document).ready(function () {
    // Dynamic bounds update for Session Year Edit modal
    // When start_date or end_date are modified in the edit modal, cascade new bounds to all date pickers
    $('#start_date, #end_date').on('change changeDate', function () {
        // Re-read display-formatted values and recompute ISO raw from datepicker internal state
        var sDateObj = $('#start_date').data('datepicker') ? $('#start_date').datepicker('getDate') : null;
        var eDateObj = $('#end_date').data('datepicker') ? $('#end_date').datepicker('getDate') : null;

        // Update stored raw data attributes so "add new installment" handler gets updated bounds
        if (sDateObj) {
            var sy = sDateObj.getFullYear(), sm = String(sDateObj.getMonth() + 1).padStart(2, '0'), sd = String(sDateObj.getDate()).padStart(2, '0');
            $('#id').attr('data-start-date-raw', sy + '-' + sm + '-' + sd);
        }
        if (eDateObj) {
            var ey = eDateObj.getFullYear(), em = String(eDateObj.getMonth() + 1).padStart(2, '0'), ed = String(eDateObj.getDate()).padStart(2, '0');
            $('#id').attr('data-end-date-raw', ey + '-' + em + '-' + ed);
        }

        if ($('#fees_due_date').hasClass('hasDatepicker')) {
            $('#fees_due_date').datepicker('setStartDate', sDateObj);
            $('#fees_due_date').datepicker('setEndDate', eDateObj);
        }

        $('.edit-installment-container .datepicker-popup').each(function () {
            if ($(this).hasClass('hasDatepicker')) {
                $(this).datepicker('setStartDate', sDateObj);
                $(this).datepicker('setEndDate', eDateObj);
            }
        });
    });
});
