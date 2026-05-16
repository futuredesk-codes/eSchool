"use strict";

var $table = $("#table_list"); // "table" accordingly
var electiveSubjectGroupCounter = 1;

$(function () {
    // $("#sortable-row").sortable({
    //     placeholder: "ui-state-highlight"
    // });
    function checkList(listName, newItem, id) {
        var dupl = false;
        $("#" + listName + " > div").each(function () {
            if ($(this)[0] !== newItem[0]) {
                if ($(this).find("li").attr('id') == newItem.find("li").attr('id')) {
                    dupl = true;
                }
            }
        });
        return dupl;
    }
    $('#table_list_exam_questions').on('check.bs.table', function (e, row) {
        var questions = $(this).bootstrapTable('getSelections');
        let li = ''
        $.each(questions, function (index, value) {
            if (value.question_type) {
                li = $('<div class="list-group mb-2">' +
                    '<input type="hidden" name="assign_questions[' + value.question_id + '][question_id]" value="' + value.question_id + '">' +
                    '<li id="q' + value.question_id + '" class="list-group-item d-flex justify-content-between align-items-center ui-state-default list-group-item-secondary">' +
                    '<div class="d-flex align-items-center">' +
                    '<span class="mr-3">' + value.question_id + '.</span>' +
                    '<span class="question-text">' + value.question + '</span>' +
                    '</div>' +
                    '<div class="d-flex align-items-center">' +
                    '<div class="form-group mb-0 mr-2">' +
                    '<input type="number" class="form-control" name="assign_questions[' + value.question_id + '][marks]" style="width: 100px" placeholder="Marks">' +
                    '</div>' +
                    '<a class="btn btn-danger btn-sm remove-row" data-id="' + value.question_id + '">' +
                    '<i class="fa fa-times" aria-hidden="true"></i>' +
                    '</a>' +
                    '</div>' +
                    '</li>' +
                    '</div>');
            } else {
                li = $('<div class="list-group mb-2">' +
                    '<input type="hidden" name="assign_questions[' + value.question_id + '][question_id]" value="' + value.question_id + '">' +
                    '<li id="q' + value.question_id + '" class="list-group-item d-flex justify-content-between align-items-center ui-state-default list-group-item-secondary">' +
                    '<div class="d-flex align-items-center">' +
                    '<span class="mr-3">' + value.question_id + '.</span>' +
                    '<span class="question-text">' + value.question + '</span>' +
                    '</div>' +
                    '<div class="d-flex align-items-center">' +
                    '<div class="form-group mb-0 mr-2">' +
                    '<input type="number" class="form-control" name="assign_questions[' + value.question_id + '][marks]" style="width: 100px" placeholder="Marks">' +
                    '</div>' +
                    '<a class="btn btn-danger btn-sm remove-row" data-id="' + value.question_id + '">' +
                    '<i class="fa fa-times" aria-hidden="true"></i>' +
                    '</a>' +
                    '</div>' +
                    '</li>' +
                    '</div>');
            }
            var pasteItem = checkList("sortable-row", li, row.question_id);
            if (!pasteItem) {
                $("#sortable-row").append(li);
            }
        });
        createCkeditor();
    })
    $('#table_list_exam_questions').on('uncheck.bs.table', function (e, row) {
        $("#sortable-row > div").each(function () {
            $(this).find('#q' + row.question_id).remove();
        });
    })
    $table.bootstrapTable('destroy').bootstrapTable({
        exportTypes: ['csv', 'excel', 'pdf', 'txt', 'json'],
    });

    $("#toolbar")
        .find("select")
        .change(function () {
            $table.bootstrapTable("refreshOptions", {
                exportDataType: $(this).val()
            });
        });

    //File Upload Custom Component
    $('.file-upload-browse').on('click', function () {
        var file = $(this).parent().parent().parent().find('.file-upload-default');
        file.trigger('click');
    });
    $('.file-upload-default').on('change', function () {

        $(this).parent().find('.form-control').val($(this).val().replace(/C:\\fakepath\\/i, ''));
    });
    tinymce.init({
        height: "400",
        selector: '#tinymce_message',
        menubar: 'file edit view formate tools',
        toolbar: [
            'styleselect fontselect fontsizeselect',
            'undo redo | cut copy paste | bold italic | alignleft aligncenter alignright alignjustify',
            'bullist numlist | outdent indent | blockquote autolink | lists |  code'
        ],
        plugins: 'autolink link image lists code'
    });

    $('.modal').on('hidden.bs.modal', function () {
        //Reset input file on modal close
        $('.file-upload-default').val('');
        $('.file-upload-info').val('');
    })
    /*simplemde editor*/
    if ($("#simpleMde").length) {
        var simplemde = new SimpleMDE({
            element: $("#simpleMde")[0],
            hideIcons: ["guide", "fullscreen", "image", "side-by-side"],
        });
    }

    //Color Picker Custom Component
    if ($(".color-picker").length) {
        $('.color-picker').asColorPicker({
            format: 'hex',
            keepInput: true, // Keep the input value in HEX format
            hideInput: true, // Hide the original input field
            onChange: function (color) {
                $('.color_value').val(color); // Update the HEX color value
            }
        });
    }

    //Added this for Dynamic No Future Date Picker input Initialization
    $('body').on('focus', ".datepicker-popup-no-future", function () {
        if (!$(this).hasClass('hasDatepicker')) {
            var today = new Date();
            var maxDate = new Date();
            maxDate.setDate(today.getDate());
            $(this).datepicker({
                enableOnReadonly: false,
                todayHighlight: true,
                format: "dd-mm-yyyy",
                endDate: maxDate,
            });
        }
    });

    //Added this for Dynamic No Future Date Picker input Initialization
    $('body').on('focus', ".datepicker-popup-no-past", function () {
        if (!$(this).hasClass('hasDatepicker')) {
            var today = new Date();
            var minDate = new Date();
            minDate.setDate(today.getDate());
            $(this).datepicker({
                enableOnReadonly: false,
                todayHighlight: true,
                format: "dd-mm-yyyy",
                startDate: minDate,
            });
        }
    });


    //Added this for Dynamic Date Picker input Initialization
    $('body').on('focus', ".datepicker-popup", function () {
        // Check if the element has the `hasDatepicker` class
        if (!$(this).hasClass('hasDatepicker')) {
            $(this).datepicker({
                enableOnReadonly: false,
                todayHighlight: true,
                format: "dd-mm-yyyy",
            });
        }
    });


    //Time Picker
    if ($("#timepicker-example").length) {
        $('#timepicker-example').datetimepicker({
            format: 'LT'
        });
    }
    //Select
    if ($(".js-example-basic-single").length) {
        $(".js-example-basic-single").select2();
    }
    // form reapeater
    $('.repeater').repeater({
        // (Optional)
        // "defaultValues" sets the values of added items.  The keys of
        // defaultValues refer to the value of the input's name attribute.
        // If a default value is not specified for an input, then it will
        // have its value cleared.
        defaultValues: {
            'text-input': 'foo'
        },
        // (Optional)
        // "show" is called just after an item is added.  The item is hidden
        // at this point.  If a show callback is not given the item will
        // have $(this).show() called on it.
        show: function () {
            $(this).slideDown();
        },
        // (Optional)
        // "hide" is called when a user clicks on a data-repeater-delete
        // element.  The item is still visible.  "hide" is passed a function
        // as its first argument which will properly remove the item.
        // "hide" allows for a confirmation step, to send a delete request
        // to the server, etc.  If a hide callback is not given the item
        // will be deleted.
        hide: function (deleteElement) {
            // if (confirm('Are you sure you want to delete this element?')) {
            //     $(this).slideUp(deleteElement);
            // }
            if ($(this).find('input:first').val() != '') {
                Swal.fire({
                    title: trans('Are you sure?'),
                    text: trans('You won\'t to delete this element?'),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    cancelButtonText: trans('Cancel'),
                    confirmButtonText: trans('Yes, delete it!')
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: location.protocol + "//" + location.hostname + (location.port && ":" + location.port) + "/timetable/" + $(this).find('input:first').val(),
                            type: "DELETE",
                            success: function (response) {
                                if (response['error'] == false) {
                                    showSuccessToast(response['message']);
                                    $(this).slideUp(deleteElement);
                                } else {
                                    showErrorToast(response['message']);
                                }
                            }
                        });
                    }
                })
            } else {
                $(this).slideUp(deleteElement);
            }
        },
        // (Optional)
        // Removes the delete button from the first list item,
        // defaults to false.
        isFirstItemUndeletable: true
    })
    $(document).on('click', '[data-toggle="lightbox"]', function (event) {
        event.preventDefault();
        $(this).ekkoLightbox();
    });

});

//Setup CSRF Token default in AJAX Request
$(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    });
});

// Initialize radio button functionality on page load
$(document).ready(function () {
    $('input[name="parent_guardian_type"]:checked').trigger('change');
});


$(document).on('change', 'input[name="parent_guardian_type"]', function () {
    var selectedValue = $(this).val();
    if (selectedValue === 'guardian') {
        $('#guardian_div').show();
        $('#guardian_div input, #guardian_div select').prop('disabled', false);
        $('#guardian_image').prop('disabled', true);
        $('#parents_div').hide();
        $('#parents_div input, #parents_div select').prop('disabled', true);
    } else if (selectedValue === 'parent') {
        $('#parents_div').show();
        $('#parents_div input, #parents_div select').prop('disabled', false);
        $('#father_image').prop('disabled', true);
        $('#mother_image').prop('disabled', true);
        $('#guardian_div').hide();
        $('#guardian_div input, #guardian_div select').prop('disabled', true);
    }
});

$('#create-form,.create-form').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback() {
        formElement[0].reset();
        $('#table_list').bootstrapTable('refresh');
        setTimeout(function () {
            window.location.reload();
        }, 1000)
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})


$('#create-form-with-redirect,.create-form-with-redirect').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);
    const session_year_id = sessionStorage.getItem('session_year_id_table');
    data.append("session_year_id", session_year_id);
    let redirectUrl = $(this).data('redirect-url');

    let preSubmitFunction = $(this).data('pre-submit-function');
    if (preSubmitFunction) {
        //If custom function name is set in the Form tag then call that function using eval
        eval(preSubmitFunction + "()");
    }
    function successCallback() {
        formElement[0].reset();
        $('#table_list').bootstrapTable('refresh');
        setTimeout(function () {
            window.location.href = redirectUrl;
        }, 1000)
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})

$('#edit-form,.editform').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let data = new FormData(this);
    const session_year_id = sessionStorage.getItem('session_year_id_table');
    data.append("session_year_id", session_year_id);
    data.append("_method", "PUT");
    let url = $(this).attr('action') + "/" + data.get('edit_id');

    function successCallback(response) {
        $('#table_list').bootstrapTable('refresh');
        setTimeout(function () {
            $('#editModal').modal('hide');
        }, 1000)
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})

$('.edit-announcement-form').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let data = new FormData(this);
    let url = $(this).attr('action');

    // Remove any trailing ID from the URL if present
    url = url.replace(/\/\d+$/, '');

    function successCallback(response) {
        $('#table_list').bootstrapTable('refresh');
        setTimeout(function () {
            $('#editModal').modal('hide');
        }, 1000)
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})

$(document).on('click', '.delete-form', function (e) {
    e.preventDefault();

    const related = $(this).data('related'); // array or undefined
    let extraHtml = '';

    if (Array.isArray(related) && related.length > 0) {
        extraHtml = `
            <p class="text-danger mt-2">
                This data is linked with <strong>${related.join(', ')}</strong>.
                Deleting it will also delete all associated data.
            </p>
        `;
    }

    Swal.fire({
        title: trans('Are you sure?'),
        html: `
            <p>${trans("You won't be able to revert this!")}</p>
            ${extraHtml}
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: trans('Cancel'),
        confirmButtonText: trans('Yes, delete it!')
    }).then((result) => {
        if (result.isConfirmed) {
            let url = $(this).attr('href');
            let data = null;

            function successCallback(response) {
                $('#table_list').bootstrapTable('refresh');
                showSuccessToast(response.message);
            }

            function errorCallback(response) {
                showErrorToast(response.message);
            }

            ajaxRequest('DELETE', url, data, null, successCallback, errorCallback);
        }
    });
});



$('.edit-class-teacher-form').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let data = new FormData(this);
    let url = $(this).attr('action');

    function successCallback(response) {
        $('#table_list').bootstrapTable('refresh');

        //Reset input file field
        $('.file-upload-default').val('');
        $('.file-upload-info').val('');
        setTimeout(function () {
            window.location.reload();
        }, 1000)
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})

$('.add-new-core-subject').on('click', function (e) {
    e.preventDefault();
    let core_subject = cloneNewCoreSubjectTemplate();
    $(this).parent().parent().siblings('.edit-extra-core-subjects').append(core_subject);
});

$(document).on('click', '.remove-core-subject', function (e) {
    e.preventDefault();
    let $this = $(this);
    if ($this.data('id')) {
        const related = $this.data('related');
        let extraHtml = '';
        if (Array.isArray(related) && related.length > 0) {
            extraHtml = `
            <p class="text-danger mt-2">
                Deleting this subject will also remove related data such as 
                <strong>${related.join(', ')}</strong> if they exist.
            </p>
            `
        }
        Swal.fire({
            title: trans('Are you sure?'),
            html: `
                <p>${trans("You won't be able to revert this!")}</p>
                ${extraHtml}
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Yes, delete it!')
        }).then((result) => {
            if (result.isConfirmed) {
                let id = $this.data('id');
                let url = baseUrl + '/class/subject/' + id;
                function successCallback() {
                    $('#table_list').bootstrapTable('refresh');
                    $this.parent().parent().remove();
                }
                function errorCallback(response) {
                    showErrorToast(response.message);
                }
                ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
            }
        });

    } else {
        $this.parent().parent().remove();
    }
});

$(document).on('click', '.add-new-elective-subject', function (e) {
    e.preventDefault();
    let subject_list = cloneNewElectiveSubject($(this));
    //Removed Class subject id because its new elective subject
    subject_list.find('.edit-elective-subject-class-id').remove();
    subject_list.find('.remove-elective-subject').removeAttr('data-id');
    let total_selectable_subject = $(this).parent().next().children().children('input');
    let max = $(this).siblings('.elective-subject-div').length;
    $(total_selectable_subject).rules("add", {
        max: max,
    });
    // if ($(total_selectable_subject).length && $(total_selectable_subject).data("validator")) {
    //     $(total_selectable_subject).rules("add", {
    //         max: max,
    //     });
    // }
    $(subject_list).insertBefore($(this));
});

$(document).on('click', '.remove-elective-subject', function (e) {
    e.preventDefault();
    let $this = $(this);
    let total_selectable_subject = $(this).parent().parent().next().children().children('input');
    if ($(this).data('id')) {
        Swal.fire({
            title: trans('Are you sure?'),
            text: trans("You won't be able to revert this!"),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Yes, delete it!')
        }).then((result) => {
            if (result.isConfirmed) {
                let id = $this.data('id');
                let url = baseUrl + '/class/subject/' + id;

                function successCallback() {
                    let max = $this.parent().siblings('.elective-subject-div').length - 1;
                    $(total_selectable_subject).rules("add", {
                        max: max,
                    });
                    $('#table_list').bootstrapTable('refresh');
                    $this.parent().prev('span').remove();
                    $this.parent().remove();
                }

                ajaxRequest('DELETE', url, null, null, successCallback);
            }
        })
    } else {
        let max = $(this).parent().siblings('.elective-subject-div').length - 1;
        // $(total_selectable_subject).rules("add", {
        //     max: max,
        // });
        $(this).parent().prev('span').remove();
        $(this).parent().remove();
    }
});

$(document).on('click', '.add-elective-subject-group', function (e) {
    e.preventDefault();
    let html = cloneNewElectiveSubjectGroup();
    html.appendTo('#edit-extra-elective-subject-group');
});

$(document).on('click', '.remove-elective-subject-group', function (e) {
    e.preventDefault();

    let $this = $(this);

    if ($this.data('id')) {

        const related = $this.data('related'); // optional, if present
        let extraHtml = '';

        if (Array.isArray(related) && related.length > 0) {
            extraHtml = `
                <p class="text-danger mt-2">
                    This elective subject group is linked with
                    <strong>${related.join(', ')}</strong>.
                    Deleting it will also delete all associated data.
                </p>
            `;
        }

        Swal.fire({
            title: trans('Are you sure?'),
            html: `
                <p>${trans("You won't be able to revert this!")}</p>
                ${extraHtml}
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Yes, delete it!')
        }).then((result) => {
            if (result.isConfirmed) {
                let id = $this.data('id');
                let url = baseUrl + '/class/subject-group/' + id;

                function successCallback() {
                    $('#table_list').bootstrapTable('refresh');
                    $this.parent().parent().remove();
                }

                function errorCallback(response) {
                    showErrorToast(response.message);
                }

                ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
            }
        });

    } else {
        $this.parent().parent().remove();
    }
});

// $('#show-edit-guardian-details').on('change', function () {
//     if ($(this).is(':checked')) {
//         $('#edit_guardian_div').show();
//         $('#edit_guardian_div input,#edit_guardian_div select').attr('disabled', false);
//     } else {
//         $('#edit_guardian_div').hide();
//         //Added this to prevent data submission while elective subject option is Off.
//         $('#edit_guardian_div input,#edit_guardian_div select').attr('disabled', true);
//     }
// })

// $('#show-edit-parents-details').on('change', function () {

//     if ($(this).is(':checked')) {
//         $('#edit_parents_div').show();
//         $('#edit_parents_div input,#edit_parents_div select').attr('disabled', false);
//     } else {
//         $('#edit_parents_div').hide();
//         //Added this to prevent data submission while elective subject option is Off.
//         $('#edit_parents_div input,#edit_parents_div select').attr('disabled', true);
//     }
// })
// if ($('#show-edit-parents-details').is(':checked')) {
//     $('#show-edit-parents-details').change();
// }


$(document).on('change', 'input[name="edit_parent_guardian_type"]', function () {
    var selectedValue = $(this).val();
    if (selectedValue === 'guardian') {
        $('#edit_guardian_div').show();
        $('#edit_guardian_div input,#edit_guardian_div select').attr('disabled', false);

        $('#edit_parents_div').hide();
        //Added this to prevent data submission while elective subject option is Off.
        $('#edit_parents_div input,#edit_parents_div select').attr('disabled', true);

        // $('#edit_guardian_div').show();
        // $('#edit_guardian_div input, #edit_guardian_div select').prop('disabled', false);
        // $('#edit_guardian_image').prop('disabled', true);
        // $('#edit_parents_div').hide();
        // $('#edit_parents_div input, #edit_parents_div select').prop('disabled', true);

    } else if (selectedValue === 'parent') {
        $('#edit_guardian_div').hide();
        //Added this to prevent data submission while elective subject option is Off.
        $('#edit_guardian_div input,#edit_guardian_div select').attr('disabled', true);

        $('#edit_parents_div').show();
        $('#edit_parents_div input,#edit_parents_div select').attr('disabled', false);

        // $('#edit_parents_div').show();
        // $('#edit_parents_div input, #edit_parents_div select').prop('disabled', false);
        // $('#edit_father_image').prop('disabled', true);
        // $('#edit_mother_image').prop('disabled', true);
        // $('#edit_guardian_div').hide();
        // $('#edit_guardian_div input, #edit_guardian_div select').prop('disabled', true);
    }
});

$(document).on('change', '.file_type', function () {
    var type = $(this).val();
    var parent = $(this).parent();
    // Reset all input fields to clear previous values when type changes
    parent.siblings('#file_name_div').find('input').val('');
    parent.siblings('#file_thumbnail_div').find('input').val('');
    parent.siblings('#file_div').find('input').val('');
    parent.siblings('#file_link_div').find('input').val('');
    if (type == "file_upload") {
        parent.siblings('#file_name_div').show();
        parent.siblings('#file_thumbnail_div').hide();
        parent.siblings('#file_div').show();
        parent.siblings('#file_link_div').hide();
    } else if (type == "video_upload") {
        parent.siblings('#file_name_div').show();
        parent.siblings('#file_thumbnail_div').show();
        parent.siblings('#file_div').show();
        parent.siblings('#file_link_div').hide();
    } else if (type == "youtube_link") {
        parent.siblings('#file_name_div').show();
        parent.siblings('#file_thumbnail_div').show();
        parent.siblings('#file_div').hide();
        parent.siblings('#file_link_div').show();
    } else if (type == "other_link") {
        parent.siblings('#file_name_div').show();
        parent.siblings('#file_thumbnail_div').show();
        parent.siblings('#file_div').hide();
        parent.siblings('#file_link_div').show();
    } else {
        parent.siblings('#file_name_div').hide();
        parent.siblings('#file_thumbnail_div').hide();
        parent.siblings('#file_div').hide();
        parent.siblings('#file_link_div').hide();
    }
})


$(document).on('click', '.add-lesson-file', function (e) {
    e.preventDefault();
    let html = $('.file_type_div:last').clone();
    html.find('.error').remove();
    html.find('.has-danger').removeClass('has-danger');
    // This function will replace the last index value and increment in the multidimensional name attribute
    html.find(':input').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
    })
    html.find('.add-lesson-file i').addClass('fa-times').removeClass('fa-plus');
    html.find('.add-lesson-file').addClass('btn-inverse-danger remove-lesson-file').removeClass('btn-inverse-success add-lesson-file');
    $(this).parent().parent().siblings('.extra-files').append(html);
    // Trigger change only after the html is appended to DOM
    html.find('.file_type').val('').trigger('change');
    html.find('input').val('');
});

$(document).on('click', '.edit-lesson-file', function (e) {
    e.preventDefault();
    let html = $('.file_type_div:last').clone();
    html.find('.error').remove();
    html.find('.has-danger').removeClass('has-danger');
    // This function will replace the last index value and increment in the multidimensional name attribute
    html.find(':input').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
    })
    html.find('.add-lesson-file i').addClass('fa-times').removeClass('fa-plus');
    html.find('.add-lesson-file').addClass('btn-inverse-danger remove-lesson-file').removeClass('btn-inverse-success add-lesson-file');
    $(this).parent().parent().siblings('.edit-extra-files').append(html);
    // Trigger change only after the html is appended to DOM
    html.find('.file_type').val('').trigger('change');
    html.find('input').val('');
});

$(document).on('click', '.remove-lesson-file', function (e) {
    e.preventDefault();
    var $this = $(this);
    // If button has Data ID then Call ajax function to delete file
    if ($(this).data('id')) {
        var file_id = $(this).data('id');

        Swal.fire({
            title: trans('Are you sure?'),
            text: trans("You won't be able to revert this!"),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Yes, delete it!')
        }).then((result) => {
            if (result.isConfirmed) {
                let url = baseUrl + '/file/delete/' + file_id;
                let data = null;

                function successCallback(response) {
                    $this.parent().parent().remove();
                    setTimeout(function () {
                        $('#editModal').modal('hide');
                    }, 1000)
                    $('#table_list').bootstrapTable('refresh');

                    showSuccessToast(response.message);
                }

                function errorCallback(response) {
                    showErrorToast(response.message);
                }

                ajaxRequest('DELETE', url, data, null, successCallback, errorCallback);
            }
        })
    } else {
        // If button don't have any Data Id then simply remove that row from DOM
        $(this).parent().parent().remove();
    }
});

// note: deprecated function
// $('#topic_class_section_id').on('change', function () {
//     let class_section_id = $(this).val();
//     $('#topic_lesson_id').html("<option value=''>--Select Lesson--</option>");
//     $('#topic_subject_id').html('<option value="">--' + trans('select_subject') + '--</option>');

//     if (!class_section_id) return;

//     let url = baseUrl + '/subject-by-class-section';
//     const session_year_id = $('.session_year_id').val();
//     let data = { class_section_id: class_section_id, session_year_id: session_year_id, all_semesters: 1 };

//     function successCallback(response) {
//         if (response.length > 0) {
//             let html = '<option value="">--' + trans('select_subject') + '--</option>';
//             $.each(response, function (key, value) {
//                 let label = value.subject.name + ' - ' + value.subject.type;
//                 if (value.semester) {
//                     label += ' - ' + value.semester.name;
//                 }
//                 html += '<option value="' + value.subject_id + '" data-semester="' + (value.semester_id || '') + '">' + label + '</option>';
//             });
//             $('#topic_subject_id').html(html);
//         } else {
//             $('#topic_subject_id').html("<option value=''>" + trans('no_data_found') + "</option>");
//         }
//     }

//     ajaxRequest('GET', url, data, null, successCallback, null, null, true);
// })

$('#topic_subject_id').on('change', function () {
    let url = baseUrl + '/search-lesson';
    let data = {
        'subject_id': $(this).val(),
        'class_section_id': $('#topic_class_section_id').val(),
        'semester_id': $(this).find(':selected').data('semester') || ''
    };

    function successCallback(response) {
        let html = ""
        if (response.data.length > 0) {
            html += "<option>--Select Lesson--</option>"
            response.data.forEach(function (data) {
                html += "<option value='" + data.id + "'>" + data.name + "</option>";
            })
        } else {
            html = "<option value=''>No Data Found</option>";
        }
        $('#topic_lesson_id').html(html);
    }

    ajaxRequest('GET', url, data, null, successCallback, null, null, true);
})

$('#resubmission_allowed').on('change', function () {
    if ($(this).is(':checked')) {
        $(this).val(1);
        $('#extra_days_for_resubmission_div').show();
    } else {
        $(this).val(0);
        $('#extra_days_for_resubmission_div').hide();
    }
})

$('#edit_resubmission_allowed').on('change', function () {
    if ($(this).is(':checked')) {
        $(this).val(1);
        $('#edit_extra_days_for_resubmission_div').show();
    } else {
        $(this).val(0);
        $('#edit_extra_days_for_resubmission_div').hide();
    }
})

$('#edit_topic_class_section_id').on('change', function (e, subject_id) {
    let class_section_id = $(this).val();
    let html = "<option value=''>--Select Lesson--</option>";
    $('#edit_topic_lesson_id').html(html);

    if (!class_section_id) {
        $('#edit_topic_subject_id').html('<option value="">--' + trans('select_subject') + '--</option>');
        return;
    }

    let url = baseUrl + '/subject-by-class-section';
    const session_year_id = sessionStorage.getItem('session_year_id_table');

    let data = {
        class_section_id: class_section_id,
        session_year_id: session_year_id,
        all_semesters: 1,
    };

    function successCallback(response) {
        if (response.length > 0) {
            let optHtml = '';
            $.each(response, function (key, value) {
                let label = value.subject.name + ' - ' + value.subject.type;
                if (value.semester) {
                    label += ' - ' + value.semester.name;
                }
                optHtml += '<option value="' + value.subject_id + '" data-semester="' + (value.semester_id || '') + '">' + label + '</option>';
            });
            $('#edit_topic_subject_id').html(optHtml);
            if (subject_id) {
                $('#edit_topic_subject_id').val(subject_id);
            }
        } else {
            $('#edit_topic_subject_id').html("<option value=''>" + trans('no_data_found') + "</option>");
        }
    }

    ajaxRequest('GET', url, data, null, successCallback, null, null, true);
})

$('#edit_topic_subject_id').on('change', function () {
    let url = baseUrl + '/search-lesson';
    let data = {
        'subject_id': $(this).val(),
        'class_section_id': $('#edit_topic_class_section_id').val(),
        'semester_id': $(this).find(':selected').data('semester') || ''
    };

    function successCallback(response) {
        let html = ""
        if (response.data.length > 0) {
            response.data.forEach(function (data) {
                html += "<option value='" + data.id + "'>" + data.name + "</option>";
            })
        } else {
            html = "<option value=''>No Data Found</option>";
        }
        $('#edit_topic_lesson_id').html(html);
    }

    ajaxRequest('GET', url, data, null, successCallback, null, null, true);
})

$(document).on('click', '.remove-assignment-file', function (e) {
    e.preventDefault();
    var $this = $(this);
    var file_id = $(this).data('id');

    Swal.fire({
        title: trans('Are you sure?'),
        text: trans("You won't be able to revert this!"),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: trans('Cancel'),
        confirmButtonText: trans('Yes, delete it!')
    }).then((result) => {
        if (result.isConfirmed) {
            let url = baseUrl + '/file/delete/' + file_id;
            let data = null;

            function successCallback(response) {
                $this.parent().remove();
                $('#table_list').bootstrapTable('refresh');
                showSuccessToast(response.message);
            }

            function errorCallback(response) {
                showErrorToast(response.message);
            }

            ajaxRequest('DELETE', url, data, null, successCallback, errorCallback);
        }
    })
});

$(document).on('click', '.add-exam-timetable', function (e) {
    e.preventDefault();
    let html = $('.exam_timetable:last').clone();
    html.find('.error').remove();
    html.find('.has-danger').removeClass('has-danger');
    // This function will replace the last index value and increment in the multidimensional name attribute
    html.find('.form-control').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
    })
    html.find('.add-exam-timetable i').addClass('fa-times').removeClass('fa-plus');
    html.find('.add-exam-timetable').addClass('btn-inverse-danger remove-exam-timetable').removeClass('btn-inverse-success add-exam-timetable');
    $(this).parent().parent().siblings('.extra-timetable').append(html);
    html.find('.form-control').val('');
});

$(document).on('click', '.edit-exam-timetable', function (e) {
    e.preventDefault();
    let html = $('.exam_timetable:last').clone();
    html.find('.error').remove();
    html.find('.has-danger').removeClass('has-danger');
    // This function will replace the last index value and increment in the multidimensional name attribute
    html.find('.form-control').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
    })
    html.find('.add-exam-timetable i').addClass('fa-times').removeClass('fa-plus');
    html.find('.add-exam-timetable').addClass('btn-inverse-danger remove-exam-timetable').removeClass('btn-inverse-success add-exam-timetable');
    $(this).parent().parent().siblings('.edit-extra-timetable').append(html);
    html.find('.form-control').val('');
});

$(document).on('click', '.remove-exam-timetable', function (e) {
    e.preventDefault();
    let $this = $(this);
    // If button has Data ID then Call ajax function to delete file
    if ($(this).data('id')) {
        let timetable_id = $(this).data('id');

        Swal.fire({
            title: trans('Are you sure?'),
            text: trans("You won't be able to revert this!"),
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
                    $this.parent().parent().remove();
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
        // If button don't have any Data Id then simply remove that row from DOM
        $(this).parent().parent().remove();
    }
});


$('#exam_id').on('change', function () {

    let class_id = $('#class_id').val();
    let exam_id = $(this).val();

    let url = baseUrl + '/exams/get-subjects/' + class_id + '/' + exam_id;
    $('#subject_id option').hide();

    function successCallback(response) {
        let html = ''
        html = '<option>No Subjects</option>';
        if (response.data) {
            html = '<option value="">' + trans('select') + ' ' + trans('subject') + '</option>';
            $.each(response.data, function (key, data) {
                html += '<option value=' + data.subject.id + '>' + data.subject.name + ' - ' + data.subject.type + '</option>';
            });
        } else {
            html = '<option>No Subjects Found</option>';
        }
        $('#subject_id').html(html);
    }

    ajaxRequest('GET', url, null, null, successCallback, null);
});

$('#class_id').on('change', function () {

    let class_id = $(this).val();

    let class_section_id = $(this)
        .find(':selected')
        .attr('data-class-section-id');

    let url = baseUrl + '/exams/get-exams/' + class_id
        + '?class_section_id=' + class_section_id;

    $('#exam_id option').hide();
    $('#subject_id option').hide();

    function successCallback(response) {
        let html = '';
        if (response.data && response.data.length > 0) {
            html = '<option value="">Select Exam</option>';

            $.each(response.data, function (key, data) {
                html += '<option value=' + data.id + '>' + data.name + '</option>';
            });

        } else {
            html = '<option>No Exams Found</option>';
        }

        $('#exam_id').html(html);
    }

    ajaxRequest('GET', url, null, null, successCallback, null);
});

$('#class_section_id').on('change', function () {

    let selectedOption = $(this).find(':selected');
    let rawValue = selectedOption.val();
    let class_section_id = rawValue;
    let semester_id = selectedOption.data('semester') || '';

    if (rawValue && rawValue.includes('|')) {
        [class_section_id, semester_id] = rawValue.split('|');
    }

    if (!class_section_id || class_section_id === '') {
        $('#exam_id').html('<option value="">Select Exam</option>');
        return;
    }

    let url = baseUrl + '/exams/get-publish-exam/' + class_section_id;
    let data = {};

    if (semester_id) {
        data.semester_id = semester_id;
    }

    $('#exam_id').html('<option value="">Loading...</option>');

    function successCallback(response) {
        let html = '<option value="">Select Exam</option>';

        if (response.data && response.data.length > 0) {
            $.each(response.data, function (key, data) {
                html += '<option value=' + data.exam.id + '>' + data.exam.name + '</option>';
            });
        } else {
            html = '<option value="">No Exams Found</option>';
        }
        $('#exam_id').html(html);
    }

    function errorCallback(response) {
        $('#exam_id').html('<option value="">Error loading exams</option>');
        showErrorToast('Error loading exams. Please try again.');
    }

    ajaxRequest('GET', url, data, null, successCallback, errorCallback, null, true);
});

//Father Search
parentSearch($(".father-search"), baseUrl + "/parent/search", { 'type': 'father' }, trans('Search for Father Email'), parentSearchSelect2DesignTemplate, function (repo) {
    if (!repo.text) {
        //Remove dynamic jquery validation
        $(".father-search").rules("remove", "email");
        $(".father_image").rules("remove", "required");
        $('#father_first_name').val(repo.first_name).attr('readonly', true);
        $('#father_last_name').val(repo.last_name).attr('readonly', true);
        $('#father_mobile').val(repo.mobile).attr('readonly', true);
        $('#father_occupation').val(repo.occupation).attr('readonly', true);
        $('#father_dob').val(repo.dob).attr('readonly', true);
        $('#father-image-tag').attr('src', repo.image);

        $('.father-extra-div').hide();

    } else {
        //Add dynamic jquery validation
        $(".father-search").rules("add", {
            email: true,
        });

        $(".father_image").rules("add", {
            required: true,
        });
        $('#father_first_name').val('').attr('readonly', false);
        $('#father_last_name').val('').attr('readonly', false);
        $('#father_mobile').val('').attr('readonly', false);
        $('#father_occupation').val('').attr('readonly', false);
        $('#father_dob').val('').attr('readonly', false);
        $('#father-image-tag').attr('src', '');
        $('.father-extra-div').show();
    }
    return repo.email || repo.text;
});
parentSearch($(".mother-search"), baseUrl + "/parent/search", { 'type': 'mother' }, trans('Search for Mother Email'), parentSearchSelect2DesignTemplate, function (repo) {
    if (!repo.text) {
        //Remove dynamic jquery validation
        $(".mother-search").rules("remove", "email");
        $(".mother_image").rules("remove", "required");
        $('#mother_first_name').val(repo.first_name).attr('readonly', true);
        $('#mother_last_name').val(repo.last_name).attr('readonly', true);
        $('#mother_mobile').val(repo.mobile).attr('readonly', true);
        $('#mother_occupation').val(repo.occupation).attr('readonly', true);
        $('#mother_dob').val(repo.dob).attr('readonly', true);
        $('#mother-image-tag').attr('src', repo.image);
        $('.mother-extra-div').hide();

    } else {
        //Add dynamic jquery validation
        $(".mother-search").rules("add", {
            email: true,
        });
        $(".mother_image").rules("add", {
            required: true,
        });
        $('#mother_first_name').val('').attr('readonly', false);
        $('#mother_last_name').val('').attr('readonly', false);
        $('#mother_mobile').val('').attr('readonly', false);
        $('#mother_occupation').val('').attr('readonly', false);
        $('#mother_dob').val('').attr('readonly', false);
        $('#mother-image-tag').attr('src', '');
        $('.mother-extra-div').show();
    }
    return repo.email || repo.text;
});
//Father Search
parentSearch($(".guardian-search"), baseUrl + "/parent/search", null, trans('Search for Guardian Email'), parentSearchSelect2DesignTemplate, function (repo) {
    if (!repo.text) {
        // Guardian selected from dropdown - populate fields
        $(".guardian-search").rules("remove", "email");
        $(".guardian_image").rules("remove", "required");
        $('#guardian_first_name').val(repo.first_name).attr('readonly', true);
        $('#guardian_last_name').val(repo.last_name).attr('readonly', true);
        $('#guardian_mobile').val(repo.mobile).attr('readonly', true);
        $('#guardian_occupation').val(repo.occupation).attr('readonly', true);
        if (repo.gender == 'Male') {
            $('#guardian_female').removeAttr('checked');
            $('#guardian_male').attr('checked', 'true');
        } else {
            $('#guardian_male').removeAttr('checked');
            $('#guardian_female').attr('checked', 'true');
        }
        $('#guardian_dob').val(repo.dob).attr('readonly', true);
        $('#guardian-image-tag').attr('src', repo.image).attr('readonly', true);

        $('.guardian-extra-div').hide();
    } else {
        // New guardian or search placeholder - clear fields for manual entry
        $(".guardian-search").rules("add", {
            email: true,
        });

        $(".guardian_image").rules("add", {
            required: true,
        });
        $('#guardian_first_name').val('').attr('readonly', false);
        $('#guardian_last_name').val('').attr('readonly', false);
        $('#guardian_mobile').val('').attr('readonly', false);
        $('#guardian_occupation').val('').attr('readonly', false);
        $('#guardian_dob').val('').attr('readonly', false);
        $('#guardian-image-tag').attr('src', '').attr('readonly', false);

        $('.guardian-extra-div').show();
    }
    return repo.email || repo.text;
});

parentSearch($(".edit-father-search"), baseUrl + "/parent/search", { 'type': 'father' }, trans('Search for Father Email'), parentSearchSelect2DesignTemplate, function (repo) {
    if (!repo.text) {
        //Remove dynamic jquery validation
        $(".edit-father-search").rules("remove", "email");
        $(".father_image").rules("remove", "required");
        $('#edit_father_first_name').val(repo.first_name).attr('readonly', true);
        $('#edit_father_last_name').val(repo.last_name).attr('readonly', true);
        $('#edit_father_mobile').val(repo.mobile).attr('readonly', true);
        $('#edit_father_occupation').val(repo.occupation).attr('readonly', true);
        $('#edit_father_dob').val(repo.dob).attr('readonly', true);
        $('#edit-father-image-tag').attr('src', repo.image);
        // } else if (repo.text !== "Search for Father Email") {
    } else {

        //Add dynamic jquery validation
        $(".edit-father-search").rules("add", {
            email: true,
        });

        $(".father_image").rules("add", {
            required: true,
        });
        $('#edit_father_first_name').val('').attr('readonly', false);
        $('#edit_father_last_name').val('').attr('readonly', false);
        $('#edit_father_mobile').val('').attr('readonly', false);
        $('#edit_father_occupation').val('').attr('readonly', false);
        $('#edit_father_dob').val('').attr('readonly', false);
        $('#edit-father-image-tag').attr('src', '');
    }
    // }
    return repo.email || repo.text;
});

parentSearch($(".edit-mother-search"), baseUrl + "/parent/search", { 'type': 'mother' }, trans('Search for Mother Email'), parentSearchSelect2DesignTemplate, function (repo) {
    if (!repo.text) {
        //Remove dynamic jquery validation
        $(".edit-mother-search").rules("remove", "email");
        $(".mother_image").rules("remove", "required");
        $('#edit_mother_first_name').val(repo.first_name).attr('readonly', true);
        $('#edit_mother_last_name').val(repo.last_name).attr('readonly', true);
        $('#edit_mother_mobile').val(repo.mobile).attr('readonly', true);
        $('#edit_mother_occupation').val(repo.occupation).attr('readonly', true);
        $('#edit_mother_dob').val(repo.dob).attr('readonly', true);
        $('#edit-mother-image-tag').attr('src', repo.image);
    } else {
        //Add dynamic jquery validation
        $(".edit-mother-search").rules("add", {
            email: true,
        });
        $(".mother_image").rules("add", {
            required: true,
        });
        $('#edit_mother_first_name').val('').attr('readonly', false);
        $('#edit_mother_last_name').val('').attr('readonly', false);
        $('#edit_mother_mobile').val('').attr('readonly', false);
        $('#edit_mother_occupation').val('').attr('readonly', false);
        $('#edit_mother_dob').val('').attr('readonly', false);
        $('#edit-mother-image-tag').attr('src', '');
    }
    return repo.email || repo.text;
});

parentSearch($(".edit-guardian-search"), baseUrl + "/parent/search", null, trans('Search for Guardian Email'), parentSearchSelect2DesignTemplate, function (repo) {
    if (!repo.text) {
        //Remove dynamic jquery validation
        $(".edit-guardian-search").rules("remove", "email");
        $(".guardian_image").rules("remove", "required");
        $('#edit_guardian_first_name').val(repo.first_name).attr('readonly', true);
        $('#edit_guardian_last_name').val(repo.last_name).attr('readonly', true);
        if (repo.gender == 'Male') {
            $('#edit_guardian_female').removeAttr('checked');
            $('#edit_guardian_male').attr('checked', 'true');
        } else {
            $('#edit_guardian_male').removeAttr('checked');
            $('#edit_guardian_female').attr('checked', 'true');
        }
        $('#edit_guardian_mobile').val(repo.mobile).attr('readonly', true);
        $('#edit_guardian_occupation').val(repo.occupation).attr('readonly', true);
        $('#edit_guardian_dob').val(repo.dob).attr('readonly', true);
        $('#edit-guardian-image-tag').attr('src', repo.image).attr('readonly', true);
    } else {
        //Add dynamic jquery validation
        $(".edit-guardian-search").rules("add", {
            email: true,
        });
        $(".guardian_image").rules("add", {
            required: true,
        });
        $('#edit_guardian_first_name').val('').attr('readonly', false);
        $('#edit_guardian_last_name').val('').attr('readonly', false);
        $('#edit_guardian_mobile').val('').attr('readonly', false);
        $('#edit_guardian_occupation').val('').attr('readonly', false);
        $('#edit_guardian_dob').val('').attr('readonly', false);
        $('#edit-guardian-image-tag').attr('src', '').attr('readonly', false);
    }
    return repo.email || repo.text;
});
$(document).on('submit', '.setting-form', function (e) {
    e.preventDefault();
    var data = new FormData(this);
    var message = data.get('setting_message');
    let submitButtonElement = $(this).find(':submit');
    var type = $('#type').val();
    var url = $(this).attr('action');
    let submitButtonText = submitButtonElement.val();
    $.ajax({
        type: "POST",
        url: url,
        data: { message: message, type: type },
        beforeSend: function () {
            submitButtonElement.val('Please Wait...').attr('disabled', true);
        },
        success: function (response) {
            if (response.error == false) {
                showSuccessToast(response.message);
                submitButtonElement.val(submitButtonText).attr('disabled', false);
            } else {
                showErrorToast(response.message);
            }
        }

    });
});

$('.general-setting').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback() {
        setTimeout(function () {
            location.reload();
        }, 3000)
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
});

$('#timetable_class_section').on('change', function () {
    if ($(this).val() !== "") {
        $('#timetable-div').removeClass('d-none');
    } else {
        $('#timetable-div').addClass('d-none');
    }
});


$('.assign_student_class').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback() {
        formElement[0].reset();
        $('#assign_table_list').bootstrapTable('refresh');
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})

// Assignment: Load class sections when session year changes (create form)
function loadAssignmentClassSections(session_year_id, targetSelector, subjectSelector) {
    let url = baseUrl + '/assignment-class-sections';
    let data = { session_year_id: session_year_id };

    function successCallback(response) {
        let html = '<option value="">--' + trans('select_class_section') + '--</option>';
        if (response.length > 0) {
            $.each(response, function (key, value) {
                html += '<option value="' + value.id + '" data-class="' + value.class_id + '" data-semester="' + (value.semester_id || '') + '">' + value.name + '</option>';
            });
        }
        $(targetSelector).html(html);
        // Reset subjects
        $(subjectSelector).html('<option value="">--' + trans('select_subject') + '--</option>');
    }

    ajaxRequest('GET', url, data, null, successCallback, null, null, true);
}

// Create form: session year change
$('.assignment_session_year').on('change', function () {
    let session_year_id = $(this).val();
    loadAssignmentClassSections(session_year_id, '#class_section_id_in_assignment', '#subject_id');
});

// Filter toolbar: session year change
$('.assignment_filter_session_year').on('change', function () {
    let session_year_id = $(this).val();
    loadAssignmentClassSections(session_year_id, '#filter_class_section_id', '#filter_subject_id');
    $('#table_list').bootstrapTable('refresh');
});

// Filter toolbar: class section change -> load filter subjects
$('#filter_class_section_id').on('change', function () {
    let class_section_id = $(this).val();
    let session_year_id = $('#filter_session_year').val();

    if (class_section_id) {
        let url = baseUrl + '/subject-by-class-section';
        let data = { class_section_id: class_section_id, session_year_id: session_year_id, all_semesters: 1 };
        function successCallback(response) {
            let html = '<option value="">' + trans('all') + '</option>';
            if (response.length > 0) {
                $.each(response, function (key, value) {
                    let label = value.subject.name + ' - ' + value.subject.type;
                    if (value.semester) {
                        label += ' - ' + value.semester.name;
                    }
                    html += '<option value="' + value.subject_id + '" data-semester="' + (value.semester_id || '') + '">' + label + '</option>';
                });
            }
            $('#filter_subject_id').html(html);
        }
        ajaxRequest('GET', url, data, null, successCallback, null, null, true);
    } else {
        $('#filter_subject_id').html('<option value="">' + trans('all') + '</option>');
    }
    $('#table_list').bootstrapTable('refresh');
});

$('#filter_subject_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
});

// Trigger initial load for both create form and filter
$(document).ready(function () {
    if ($('.assignment_session_year').length) {
        $('.assignment_session_year').trigger('change');
    }
    if ($('.assignment_filter_session_year').length) {
        $('.assignment_filter_session_year').trigger('change');
    }
});

$('.class_section_id_in_assignment').on('change', function () {
    let class_section_id = $(this).val();
    let semester_id = $(this).find(':selected').data('semester') || '';
    $('#semester_id').val(semester_id);
    let url = baseUrl + '/subject-by-class-section';
    const session_year_id = $('#session_year').val();

    let data = {
        class_section_id: class_section_id,
        session_year_id: session_year_id,
        semester_id: semester_id,
    };
    function successCallback(response) {
        if (response.length > 0) {
            let html = '';
            html += '<option>' + trans('select_subject') + '</option>';
            $.each(response, function (key, value) {
                html += '<option value="' + value.subject_id + '" data-semester="' + (value.semester_id || '') + '">' + value.subject.name + ' - ' + value.subject.type + '</option>'
            });
            $('.subject_id').html(html);
        } else {
            $('.subject_id').html("<option value=''>" + trans('no_data_found') + "</option>>");
        }
    }

    ajaxRequest('GET', url, data, null, successCallback, null, null, true)
})

$('.class_section_id').on('change', function () {
    let class_section_id = $(this).val();
    $('#semester_id').val('');

    let url = baseUrl + '/subject-by-class-section';

    const session_year_id = $('.session_year_id').val();
    let data = {
        class_section_id: class_section_id,
        session_year_id: session_year_id,
        all_semesters: 1,
    };
    function successCallback(response) {
        if (response.length > 0) {
            let html = '';
            html += '<option>' + trans('select_subject') + '</option>';
            $.each(response, function (key, value) {
                let label = value.subject.name + ' - ' + value.subject.type;
                if (value.semester) {
                    label += ' - ' + value.semester.name;
                }
                html += '<option value="' + value.subject_id + '" data-semester="' + (value.semester_id || '') + '">' + label + '</option>'
            });
            $('.subject_id').html(html);
        } else {
            $('.subject_id').html("<option value=''>" + trans('no_data_found') + "</option>>");
        }
    }

    ajaxRequest('GET', url, data, null, successCallback, null, null, true)
})

$('.lessons_class_section_id').on('change', function () {
    let class_section_id = $(this).val();
    $('#semester_id').val('');

    let url = baseUrl + '/teacher-semester-subjects';

    const session_year_id = $('.session_year_id').val();
    let data = {
        class_section_id: class_section_id,
        session_year_id: session_year_id,
        all_semesters: 1,
    };
    function successCallback(response) {
        if (response.length > 0) {
            let html = '';
            html += '<option>' + trans('select_subject') + '</option>';
            $.each(response, function (key, value) {
                let label = value.subject.name + ' - ' + value.subject.type;
                if (value.semester) {
                    label += ' - ' + value.semester.name;
                }
                html += '<option value="' + value.subject_id + '" data-semester="' + (value.semester_id || '') + '">' + label + '</option>'
            });
            $('.subject_id').html(html);
        } else {
            $('.subject_id').html("<option value=''>" + trans('no_data_found') + "</option>>");
        }
    }

    ajaxRequest('GET', url, data, null, successCallback, null, null, true)
})

// Set semester_id when subject is selected (create form)
$('#subject_id').on('change', function () {
    let semester_id = $(this).find(':selected').data('semester') || '';
    $('#semester_id').val(semester_id);
})

$('#edit_class_section_id').on('change', function (e, subject_id) {
    let class_section_id = $(this).val();
    $('#edit_semester_id').val('');

    if (!class_section_id) {
        $('#edit_subject_id').html('<option value="">--' + trans('select') + '--</option>');
        return;
    }

    let url = baseUrl + '/subject-by-class-section';
    const session_year_id = sessionStorage.getItem('session_year_id_table');

    let data = {
        class_section_id: class_section_id,
        session_year_id: session_year_id,
        all_semesters: 1,
    };

    function successCallback(response) {
        if (response.length > 0) {
            let html = '';
            $.each(response, function (key, value) {
                let label = value.subject.name + ' - ' + value.subject.type;
                if (value.semester) {
                    label += ' - ' + value.semester.name;
                }
                html += '<option value="' + value.subject_id + '" data-semester="' + (value.semester_id || '') + '">' + label + '</option>'
            });
            $('#edit_subject_id').html(html);
            if (subject_id) {
                $('#edit_subject_id').val(subject_id);
                $('#edit_semester_id').val($('#edit_subject_id').find(':selected').data('semester') || '');
            }
        } else {
            $('#edit_subject_id').html("<option value=''>" + trans('no_data_found') + "</option>>");
        }
    }

    ajaxRequest('GET', url, data, null, successCallback, null, null, true)
})

// Set semester_id when subject is selected (edit form)
$('#edit_subject_id').on('change', function () {
    let semester_id = $(this).find(':selected').data('semester') || '';
    $('#edit_semester_id').val(semester_id);
})

$(document).on('change', '.timetable_start_time', function () {
    let $this = $(this);
    let end_time = $(this).parent().siblings().children('.timetable_end_time');
    $(end_time).rules("add", {
        timeGreaterThan: $this,
    });
})

$('#system-update').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback() {
        setTimeout(function () {
            window.location.reload();
        }, 1000)
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})

$("#create-form-bulk-data").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

// Load exams based on session year in create exam timetable form
$(document).on('change', '#select_session_year', function () {
    let session_year_id = $(this).val();
    let examDropdown = $('#exam_options');
    examDropdown.html('<option value="">--- Select ---</option>');
    $('#exam_classes_options').html('<option value="">--- Select ---</option>');
    $('.exam_subjects_options').html('<option value="">--- Select ---</option>');
    if (session_year_id) {
        let url = baseUrl + '/exam/get-exams-by-session-year/' + session_year_id;
        $.ajax({
            type: "GET",
            url: url,
            success: function (response) {
                if (!response.error && response.data && response.data.length > 0) {
                    let html = '<option value="">--- Select ---</option>';
                    $.each(response.data, function (key, exam) {
                        html += '<option value="' + exam.id + '" data-semester-id="' + (exam.semester_id || '') + '">' + exam.name + '</option>';
                    });
                    examDropdown.html(html);
                }
            }
        });
    }
});

// get classes on Drop down exam changes
$('#exam_options').on('change', function () {
    let exam_id = $(this).val();
    let url = baseUrl + '/exam/get-classes/' + exam_id;
    $.ajax({
        type: "get",
        url: url,
        success: function (response) {
            let html = ""
            if (response.data.length > 0) {
                html += "<option value=''>--- Select ---</option>";
                $.each(response.data, function (key, data) {
                    let streamName = data.class.streams ? ' ' + data.class.streams.name : '';
                    html += "<option value='" + data.class_id + "'>" + data.class.name + ' ' + data.class.medium.name + streamName + "</option>";
                });
            } else {
                html = "<option value=''>No Data Found</option>";
            }
            $('#exam_classes_options').html(html);
        }
    });
});

// get Subjects on Drop down classes changes
$('#exam_classes_options').on('change', function () {
    let class_id = $(this).val();
    let semester_id = $('#exam_options').find(':selected').data('semester-id') || '';
    let url = baseUrl + '/exam/get-subjects/' + class_id;
    let data = {};
    if (semester_id) {
        data.semester_id = semester_id;
    }
    $.ajax({
        type: "get",
        url: url,
        data: data,
        success: function (response) {
            let html = ""
            html += "<option value=''>--- Select ---</option>";
            if (response.data.length > 0) {
                $.each(response.data, function (key, data) {
                    html += "<option value='" + data.subject.id + "'>" + data.subject.name + ' (' + data.subject.type + ')' + "</option>";
                });
            } else {
                html = "<option value=''>No Data Found</option>";
            }
            $('.exam_subjects_options').html(html);
        }
    });
});


// add more subject in create exam timetable
$(document).on('click', '.add-exam-timetable-content', function (e) {
    e.preventDefault();
    let html = $('.exam_timetable_content:last').clone();
    html.find('.error').remove();
    html.find('.has-danger').removeClass('has-danger');
    // This function will replace the last index value and increment in the multidimensional name attribute
    html.find('.form-control').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
    })
    html.find('.add-exam-timetable-content i').addClass('fa-times').removeClass('fa-plus');
    html.find('.add-exam-timetable-content').addClass('btn-inverse-danger remove-exam-timetable-content').removeClass('btn-inverse-success add-exam-timetable');
    $(this).parent().parent().parent().siblings('.extra-timetable').append(html);
    html.find('.form-control').val('');
});

// remove more subject in create exam timetable
$(document).on('click', '.remove-exam-timetable-content', function (e) {
    e.preventDefault();
    $(this).parent().parent().parent().remove();
});

$(".exam_class_filter").find("select").change(function () {
    $table.bootstrapTable("refreshOptions", {
        exportDataType: $(this).val()
    });
});

// Load exams based on session year in exam timetable filter
$('#filter_session_year').on('change', function () {
    let session_year_id = $(this).val();
    let examDropdown = $('#filter_exam_name');
    examDropdown.html('<option value="">All</option>');
    if (session_year_id) {
        let url = baseUrl + '/exam/get-exams-by-session-year/' + session_year_id + '?show_all=true';
        $.ajax({
            type: "GET",
            url: url,
            success: function (response) {
                if (!response.error && response.data.length > 0) {
                    let html = '<option value="">All</option>';
                    $.each(response.data, function (key, exam) {
                        html += '<option value="' + exam.id + '">' + exam.name + '</option>';
                    });
                    examDropdown.html(html);
                }
            }
        });
    }
    $('#table_list').bootstrapTable('refresh');
});

// Trigger initial load of exams based on selected session year
$(document).ready(function () {
    if ($('#filter_session_year').length) {
        $('#filter_session_year').trigger('change');
    }
});

$("#edit_class_id").on('change', function () {
    let data = $(this).find(':selected').data("medium");
    let url = baseUrl + "/class-subject-list/" + data
    $.ajax({
        type: "GET",
        url: url,
        success: function (response) {
            let html = ""
            if (response.data.length > 0) {
                response.data.forEach(function (data) {
                    html += "<option value='" + data.id + "'>" + data.name + "</option>";
                })
            } else {
                html = "<option value=''>No Data Found</option>";
            }
            $('.core-subject-id').html(html);
            $('.elective-subject-name').html(html)
        }
    });
});

// According to Conditions Show the Button of Adding new row
function checkAddNewRowBtn() {
    if ($('.grade_content').find('.ending_range').length) {
        let chk_max = $(this).val();
        if (chk_max < 100 && chk_max != '') {
            $('.add-grade-content').prop('disabled', false);
        } else {
            $('.add-grade-content').prop('disabled', true);
        }
        $('.ending_range:last').keyup(function (e) {
            let chk_max = $(this).val();
            if (chk_max < 100 && chk_max != '') {
                $('.add-grade-content').prop('disabled', false);
            } else {
                $('.add-grade-content').prop('disabled', true);
            }
        });

    } else {
        $('.add-grade-content').prop('disabled', false);
    }
}

checkAddNewRowBtn();

// create grade ajax
$('#create-grades').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);
    const session_year_id = sessionStorage.getItem('session_year_id_table');
    data.append("session_year_id", session_year_id);

    function successCallback() {
        setTimeout(function () {
            location.reload();
        }, 1000);
        checkAddNewRowBtn(); // calling the function of adding new row btn
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
$('.remove-grades').hide();
$('.grade_content:last').find('.remove-grades').show();
let value = parseInt($('.grade_content:last').find('.ending_range').val());
if (value >= 100) {
    $('.add-grade-content').prop('disabled', true);
} else {
    $('.add-grade-content').prop('disabled', false);
}
//adding new row for grade
$(document).on('click', '.add-grade-content', function (e) {
    e.preventDefault();
    let value = parseFloat($('.grade_content:last').find('.ending_range').val());
    if (value) {
        value = value + 1;
    } else {
        value = 0;
    }
    let html = $('.grade_content:last').clone();
    $('.grade_content:last').find('.remove-grades').hide();
    html.find('.error').remove();
    html.find('.temp_starting_range').removeClass('temp_starting_range').addClass('starting_range');
    html.find('.temp_ending_range').removeClass('temp_ending_range').addClass('ending_range');
    html.find('.temp_grade').removeClass('temp_grade').addClass('grade');
    html.css('display', 'block');
    html.find('.has-danger').removeClass('has-danger');
    html.find('.hidden').remove();
    html.find(".remove-grades").removeAttr('data-id');
    // This function will replace the last index value and increment in the multidimensional name attribute
    $(this).parent().siblings('.extra-grade-content').append(html);
    $('.add-grade-content').prop('disabled', true);
    html.find('.starting_range').val('')
    html.find('.ending_range').val('');
    html.find('.grade').val('');
    html.find('input').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
    })
    let increment_stating_range = html.find('.starting_range').val(value);
    increment_stating_range.attr('min', value);
    let min_attr = parseInt(increment_stating_range.attr("min"));
    increment_stating_range.keyup(function () {
        if ($(this).val()) {
            if ($(this).val() < min_attr) {
                $('.add-grade-content').prop('disabled', true);
            }
        } else {
            $('.add-grade-content').prop('disabled', true);
        }
    });

    let ending_range = html.find('.ending_range');
    ending_range.attr('max', 100);
    ending_range.keyup(function () {
        if ($(this).val()) {
            if ($(this).val() <= min_attr) {
                $('.add-grade-content').prop('disabled', true);
            } else {
                if ($(this).val() < 100) {
                    $('.add-grade-content').prop('disabled', false);
                } else {
                    $('.add-grade-content').prop('disabled', true);
                }
            }
        } else {
            $('.add-grade-content').prop('disabled', true);
        }
    });
});
// remove more grade in create grade
$(document).on('click', '.remove-grades', function (e) {
    e.preventDefault();
    let $this = $(this);
    if ($(this).data('id')) {
        Swal.fire({
            title: trans('Are you sure?'),
            text: trans("You won't be able to revert this!"),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Yes, delete it!')
        }).then((result) => {
            if (result.isConfirmed) {
                let id = $this.data('id');
                let url = baseUrl + '/destroy-grades/' + id;

                function successCallback() {
                    $this.parent().parent().remove();
                    window.location.reload();
                    checkAddNewRowBtn();
                }

                ajaxRequest('DELETE', url, null, null, successCallback);

            }
        })
    } else {
        $(this).parent().parent().parent().remove();
        $('.grade_content:last').find('.remove-grades').show();
        let last_ending_val = $('.grade_content:last').find('.ending_range').val();
        if (last_ending_val >= 100 && last_ending_val == '') {
            $('.add-grade-content').prop('disabled', true);
        } else {
            $('.add-grade-content').prop('disabled', false);
        }
        $('.ending_range:last').keyup(function (e) {
            let chk_max = $(this).val();
            if (chk_max < 100 && chk_max != '') {
                $('.add-grade-content').prop('disabled', false);
            } else {
                $('.add-grade-content').prop('disabled', true);
            }
        });
    }
});

$('.assign_subject_teacher').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback() {
        formElement[0].reset();
        $('.select2-selection__rendered').html('');
        $('#table_list').bootstrapTable('refresh');
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
$('#student-registration-form').on('submit', function (e) {

    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback() {
        window.location.reload();
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
$('#student-registration').on('submit', function (e) {

    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback() {
        window.location.reload();
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
$('#admin-profile-update').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);


    function successCallback() {
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
$('.edit_exam_result_marks_form').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);


    function successCallback() {
        $('#editModal').modal('hide');
        $('#table_list').bootstrapTable('refresh');
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
$('.create_exam_timetable_form').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);


    function successCallback() {
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
$('.add-new-timetable-data').click(function (e) {
    e.preventDefault();
    let html;
    if (!$('.edit-timetable-container:last').is(':empty')) {
        html = $('.edit-timetable-container').find('.edit_exam_timetable:last').clone();
    } else {
        html = $('.edit_exam_timetable_tamplate').clone();
    }
    html.css('display', 'block');
    html.find('.error').remove();
    html.removeClass('edit_exam_timetable_tamplate').addClass('edit_exam_timetable');
    html.find('.has-danger').removeClass('has-danger');
    html.find('.remove-edit-exam-timetable-content').removeAttr('data-timetable_id');
    // This function will replace the last index value and increment in the multidimensional name attribute
    html.find('.form-control').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
    })
    $(this).parent().siblings('.edit-timetable-container').append(html);
    html.find('.form-control').val('');

});

$('.edit-form-timetable').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);


    function successCallback() {
        $('#editModal').modal('hide');
        $('#table_list').bootstrapTable('refresh');
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
$('.verify_email').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);


    function successCallback() {
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
});
$('.subject_id').on('change', function () {
    // let class_id = $(this).find(':selected').data('class');
    let class_section_id = $('.class_section_id').val();
    let subject_id = $(this).val();
    let url = baseUrl + '/teacher-by-class-subject';
    let data = {
        class_section_id: class_section_id,
        subject_id: subject_id,
    };


    function successCallback(response) {
        if (response.length > 0) {
            let html = '';
            $.each(response, function (key, value) {
                html += '<option value="' + value.id + '">' + value.user.first_name + ' ' + value.user.last_name + '</option>'
            });
            $('#teacher_id').html(html);
        } else {
            $('#teacher_id').html("<option value=''>--No data Found--</option>>");
        }
    }

    ajaxRequest('GET', url, data, null, successCallback, null, null, true)
})
$('#edit_subject_id').on('change', function () {

    let edit_id = $('#id').val();
    let class_section_id = $('#edit_class_section_id').val();
    let subject_id = $(this).val();
    let url = baseUrl + '/teacher-by-class-subject';
    let data = {
        edit_id: edit_id,
        class_section_id: class_section_id,
        subject_id: subject_id,
    };
    function successCallback(response) {
        if (response.length > 0) {
            let html = '';
            $.each(response, function (key, value) {
                html += '<option value="' + value.id + '">' + value.user.first_name + ' ' + value.user.last_name + '</option>'
            });
            $('#edit_teacher_id').html(html);
        } else {
            $('#edit_teacher_id').html("<option value=''>--No data Found--</option>>");
        }
    }

    ajaxRequest('GET', url, data, null, successCallback, null, null, true)
})
$('.add-new-fees-type').on('click', function (e) {
    e.preventDefault();
    let html = ''
    if ($('.edit-extra-fees-types').find('.template_fees_type:last').html()) {
        html = $('.edit-extra-fees-types').find('.template_fees_type:last').clone();
        html.find('.form-control').each(function (key, element) {
            this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
                return '[' + (parseInt(p1, 10) + 1) + ']';
            });
            this.id = this.id.replace(/\_(\d+)/, function (str, p1) {
                return '_' + (parseInt(p1, 10) + 1);
            });
            $(element).attr('disabled', false);
        })
    } else {
        html = $('.template_fees_type').clone().show();
    }
    html.find('select').siblings('.error').remove();
    html.find('.add-fees-type i').addClass('fa-times').removeClass('fa-plus');
    html.find('.add-fees-type').addClass('btn-inverse-danger remove-fees-type').removeClass('btn-inverse-success add-fees-type');
    $('.edit-extra-fees-types').append(html);
});

$('#fees-class-create-form').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);
    const session_year_id = sessionStorage.getItem('session_year_id_table');
    data.append("session_year_id", session_year_id);

    function successCallback() {
        setTimeout(function () {
            $('#editModal').modal('hide');
        }, 1000)
        $('#table_list').bootstrapTable('refresh');
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
});

$(document).on('click', '.remove-fees-type', function (e) {
    e.preventDefault();

    let $this = $(this);

    if ($this.data('id')) {

        const related = $this.data('related');
        let extraHtml = '';

        if (Array.isArray(related) && related.length > 0) {
            extraHtml = `
                <p class="text-danger mt-2">
                    This fees type is linked with
                    <strong>${related.join(', ')}</strong>.
                    Deleting it will also delete all associated data.
                </p>
            `;
        }

        Swal.fire({
            title: trans('Are you sure?'),
            html: `
                <p>${trans("You won't be able to revert this!")}</p>
                ${extraHtml}
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Yes, delete it!')
        }).then((result) => {
            if (result.isConfirmed) {
                let id = $this.data('id');
                let url = baseUrl + '/class/fees-type/' + id;

                function successCallback(response) {
                    showSuccessToast(response.message);

                    setTimeout(function () {
                        $('#editModal').modal('hide');
                    }, 1000);

                    $('#table_list').bootstrapTable('refresh');
                    $this.parent().parent().remove();
                }

                function errorCallback(response) {
                    showErrorToast(response.message);
                }

                ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
            }
        });

    } else {
        // Unsaved row
        $this.parent().parent().remove();
    }
});
$('.mode').on('change', function (e) {
    e.preventDefault();
    let mode_val = $(this).val();
    if (mode_val == 1) {
        $('.cheque_no_container').show(200);
    } else {
        $('.cheque_no_container').hide(200);
    }
});
$('.pay_student_fees_offline').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);


    function successCallback() {
        $('#editModal').modal('hide');
        $('.cheque_no_container').hide();
        formElement[0].reset();
        $('#table_list').bootstrapTable('refresh');
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
$('.edit_mode').on('change', function (e) {
    e.preventDefault();
    let mode_val = $(this).val();
    if (mode_val == 1) {
        $('.edit_cheque_no_container').show(200);
    } else {
        $('.edit_cheque_no_container').hide(200);
    }
});
$(document).on('click', '.remove-paid-choiceable-fees', function (e) {
    e.preventDefault();
    Swal.fire({
        title: trans('Are you sure?'),
        text: trans("You won't be able to revert this!"),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: trans('Cancel'),
        confirmButtonText: trans('Yes, delete it!')
    }).then((result) => {
        if (result.isConfirmed) {
            let amount = $(this).data("amount");
            // let url = $(this).attr('href');
            let id = $(this).data("id");
            let url = baseUrl + '/fees/paid/remove-choiceable-fees/' + id;
            let data = null;

            function successCallback(response) {
                $('#table_list').bootstrapTable('refresh');
                setTimeout(function () {
                    $('#editFeesPaidModal').modal('hide');
                }, 1000)
                showSuccessToast(response.message);
            }

            function errorCallback(response) {
                showErrorToast(response.message);
            }

            ajaxRequest('DELETE', url, data, null, successCallback, errorCallback);
        }
    })
})
$('#create-fees-config-form').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback() {
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
$('#edit-fees-paid-form').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let data = new FormData(this);
    data.append("_method", "PUT");
    let url = $(this).attr('action') + "/" + data.get('edit_id');

    function successCallback(response) {
        $('#table_list').bootstrapTable('refresh');
        setTimeout(function () {
            $('#editFeesPaidModal').modal('hide');
        }, 1000)
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
$('#session_year').on('change', function () {
    var session_year_id = $(this).val();
    var $select = $('#class_timetable_class_section');
    var scope = $select.data('scope') || '';

    $('.set_timetable').html('');
    $('.warning_no_data').show(300);

    if (!session_year_id) {
        $select.html('<option value="">' + trans('select') + '</option>');
        return;
    }

    $.ajax({
        url: baseUrl + '/get-class-sections-with-semesters',
        type: "GET",
        data: { session_year_id: session_year_id, scope: scope },
        success: function (response) {
            var html = '<option value="">' + trans('select') + '</option>';
            for (var i = 0; i < response.length; i++) {
                html += '<option value="' + response[i].class_section_id + '"'
                    + ' data-class="' + response[i].class_id + '"'
                    + ' data-semester="' + (response[i].semester_id || '') + '"'
                    + '>' + response[i].label + '</option>';
            }
            $select.html(html);
        }
    });
});
$('#class_timetable_class_section').on('change', function (e) {
    $('.list_buttons').show(200);
    var class_section_id = $(this).val();
    var session_year_id = $('#session_year').val();
    var semester_id = $(this).find(':selected').attr('data-semester') || '';
    function titleCase(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : s; }

    $('.set_timetable').html('');
    $.ajax({
        url: baseUrl + '/gettimetablebyclass',
        type: "GET",
        data: { class_section_id: class_section_id, session_year_id: session_year_id, semester_id: semester_id },
        success: function (response) {
            var html = '';
            if (response['days'].length) {
                $('.warning_no_data').hide(300);
                for (let i = 0; i < response['days'].length; i++) {
                    html += '<div class="col-lg-4 col-xl-4 col-xxl-2 col-md-4 col-sm-12 col-12 project-grid">';
                    html += '<div class="project-grid-inner">';
                    html += '<div class="wrapper bg-light">';
                    const dayKey = titleCase(response['days'][i]['day_name']);
                    html += '<h5 class="card-header header-sm bg-secondary">' + trans(dayKey) + '</h5>';
                    for (let j = 0; j < response['timetable'].length; j++) {
                        if (response['days'][i]['day'] == response['timetable'][j]['day']) {
                            html += '<p class="timetable-body p-3">'
                                + response['timetable'][j]['subject_teacher']['subject']['name'] + ' - ' + trans(response['timetable'][j]['subject_teacher']['subject']['type'])
                                + '<br>' + response['timetable'][j]['subject_teacher']['teacher']['user']['first_name'] + ' ' + response['timetable'][j]['subject_teacher']['teacher']['user']['last_name']
                                + '<br>' + trans('start_time') + ': ' + response['timetable'][j]['start_time'] + '<br>' + trans('end_time') + ': '
                                + response['timetable'][j]['end_time'] + '</p><hr>';

                        }
                    }
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    $('.set_timetable').html(html);
                }
            } else {
                $('.warning_no_data').show(300);
                $('.table_content').hide();
            }
        }
    })
});
$('#session_year').on('change', function () {
    var session_year_id = $(this).val();
    var $select = $('#teacher_timetable_class_section');
    var scope = $select.data('scope') || '';

    $('.set_timetable').html('');
    $('.warning_no_data').hide();

    $select.html('<option value="">' + trans('select') + '</option>');
    if (!session_year_id) return;

    $.ajax({
        url: baseUrl + '/get-class-sections-with-semesters',
        type: "GET",
        data: { session_year_id: session_year_id, scope: scope },
        success: function (response) {
            var html = '<option value="">' + trans('select') + '</option>';
            for (var i = 0; i < response.length; i++) {
                html += '<option value="' + response[i].class_section_id + '"'
                    + ' data-class="' + response[i].class_id + '"'
                    + ' data-semester="' + (response[i].semester_id || '') + '"'
                    + '>' + response[i].label + '</option>';
            }
            $select.html(html);
        }
    });

    // Load all timetables for this session year (no class section filter)
    loadTeacherTimetable(session_year_id, '', '');
});
$('#teacher_timetable_class_section').on('change', function (e) {
    var class_section_id = $(this).val();
    var session_year_id = $('#session_year').val();
    var semester_id = $(this).find(':selected').attr('data-semester') || '';

    if (!session_year_id) return;

    loadTeacherTimetable(session_year_id, class_section_id, semester_id);
});

function loadTeacherTimetable(session_year_id, class_section_id, semester_id) {
    $('.set_timetable').html('');
    $.ajax({
        url: baseUrl + "/get-timetable-by-subject-teacher-class",
        type: "GET",
        data: { class_section_id: class_section_id, session_year_id: session_year_id, semester_id: semester_id },
        success: function (response) {
            if (response['days'].length) {
                $('.warning_no_data').hide(300);
                var html = '';
                for (let i = 0; i < response['days'].length; i++) {
                    html += '<div class="col-lg-4 col-xl-4 col-xxl-2 col-md-4 col-sm-12 col-12 project-grid">';
                    html += '<div class="project-grid-inner">';
                    html += '<div class="wrapper bg-light">';
                    html += '<h5 class="card-header header-sm bg-secondary">' + response['days'][i]['day_name'].charAt(0).toUpperCase() + response['days'][i]['day_name'].slice(1) + '</h5>';
                    for (let j = 0; j < response['timetable'].length; j++) {
                        if (response['days'][i]['day'] == response['timetable'][j]['day']) {
                            var semesterName = response['timetable'][j]['semester'] ? response['timetable'][j]['semester']['name'] : '';
                            html += '<p class="timetable-body p-3">'
                                + response['timetable'][j]['class_section']['class']['name'] + ' - ' + response['timetable'][j]['class_section']['section']['name']
                                + '<br>' + response['timetable'][j]['subject_teacher']['subject']['name'] + ' - ' + response['timetable'][j]['subject_teacher']['subject']['type']
                                + '<br>start time: ' + response['timetable'][j]['start_time'] + '<br>end time: '
                                + response['timetable'][j]['end_time']
                                + (semesterName ? '<br><span class="badge badge-info">' + semesterName + '</span>' : '');
                            if (response['timetable'][j]['link_name'] !== null) {
                                html += '<br><a href=' + response['timetable'][j]['live_class_url'] + '>' + response['timetable'][j]['link_name'] + '</a><br>'
                            }
                            html += '<button class="btn btn-theme btn-block classurl mt-3" data-id="' + response['timetable'][j]['id'] + '">Edit</button></p>';
                        }
                    }
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    $('.set_timetable').html(html);
                }
            } else {
                $('.warning_no_data').show(300);
            }
        }
    });
}
$('#session_year').on('change', function () {
    var teacher_id = $('#teacher_timetable_teacher_id').val();
    if (teacher_id !== '') {
        $('#teacher_timetable_teacher_id').trigger('change');
    }
});
$('#teacher_timetable_teacher_id').on('change', function (e) {
    var teacher_id = $(this).val();
    var session_year_id = $('#session_year').val();
    if (!session_year_id) return;

    $('.set_timetable').html('');
    $.ajax({
        url: baseUrl + "/gettimetablebyteacher",
        type: "GET",
        data: {
            teacher_id: teacher_id,
            session_year_id: session_year_id
        },
        success: function (response) {
            var html = '';
            let counter = 0;
            for (let n = 0; n < response['days'].length; n++) {
                for (let i = 0; i < response['days'][n].length; i++) {
                    counter += 1;
                    html += '<div class="col-lg-4 col-xl-4 col-xxl-2 col-md-4 col-sm-12 col-12 project-grid">';
                    html += '<div class="project-grid-inner">';
                    html += '<div class="wrapper bg-light">';
                    html += '<h5 class="card-header header-sm bg-secondary">' + response['days'][n][i]['day_name'].charAt(0).toUpperCase() + response['days'][n][i]['day_name'].slice(1) + '</h5>';
                    for (let m = 0; m < response['timetable'].length; m++) {
                        if (response['timetable'][m] != '') {
                            for (let j = 0; j < response['timetable'][m].length; j++) {
                                if (response['days'][n][i]['day'] == response['timetable'][m][j]['day']) {
                                    var semesterName = response['timetable'][m][j]['semester'] ? response['timetable'][m][j]['semester']['name'] : '';
                                    html += '<p class="timetable-body p-3">' + response['timetable'][m][j]['class_section']['class']['name'] +
                                        ' - ' + response['timetable'][m][j]['class_section']['section']['name'] +
                                        '<br>' + response['timetable'][m][j]['subject_teacher']['subject']['name'] + '-' + response['timetable'][m][j]['subject_teacher']['subject']['type'] +
                                        '<br>Start Time: ' + response['timetable'][m][j]['start_time'] +
                                        '<br>End Time: ' + response['timetable'][m][j]['end_time'] +
                                        (semesterName ? '<br><span class="badge badge-info">' + semesterName + '</span>' : '') +
                                        '</p><hr>';
                                }
                            }
                        }
                    }
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    $('.set_timetable').html(html);
                }
            }
            if (counter != 0) {
                $('.warning_no_data').hide(300);
            } else {
                $('.warning_no_data').show(300);
            }
        }
    })
});

$('#razorpay_status').on('change', function (e) {
    e.preventDefault();
    if ($(this).val() == 1) {
        $('#stripe_status').val(0);
        $('#paystack_status').val(0);
    }
});
$('#stripe_status').on('change', function (e) {
    e.preventDefault();
    if ($(this).val() == 1) {
        $('#razorpay_status').val(0);
        $('#paystack_status').val(0);
    }
});
$('#paystack_status').on('change', function (e) {
    e.preventDefault();
    if ($(this).val() == 1) {
        $('#razorpay_status').val(0);
        $('#stripe_status').val(0);
    }
});
$('#assign-roll-no-form').on('submit', function (e) {
    e.preventDefault();
    Swal.fire({
        title: lang_delete_title,
        text: lang_delete_warning,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: lang_yes_change_it
    }).then((result) => {
        if (result.isConfirmed) {
            let formElement = $(this);
            let submitButtonElement = $(this).find(':submit');
            let url = $(this).attr('action');
            let data = new FormData(this);

            function successCallback() {
                $('#table_list').bootstrapTable('refresh');
            }

            formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
        }
    })
})
$('.online-exam-class-section-id').on('change', function (e) {
    e.preventDefault();
    let url = baseUrl + '/get-subject-online-exam';
    let data = {
        'based_on': 1,
        'class_section_id': $(this).val(),
        'session_year_id': $('#session_year_id').val(),
        'semester_id': $('#semester_id').val()
    };

    function successCallback(response) {
        let html = ""
        if (response.data.length) {
            html += "<option value=''>-- " + lang_select_subject + " --</option>"
            response.data.forEach(function (data) {
                html += "<option value='" + data.id + "'>" + data.name + ' - ' + data.type + "</option>";
            })
        } else {
            html = "<option value=''>" + lang_no_data_found + "</option>";
        }
        $('.online-exam-subject-id').html(html);
    }

    ajaxRequest('GET', url, data, null, successCallback, null, null, true);
})
$('#add-new-option').on('click', function (e) {
    e.preventDefault();
    let html = $('.option-container').find('.form-group:last').clone();
    html.find('.add-question-option').val('');
    html.find('.error').remove();
    html.find('.has-danger').removeClass('has-danger');
    $('.remove-option-content').css('display', 'none');
    html.addClass('quation-option-extra');

    // html.removeClass('col-md-6').addClass('col-md-5');
    // This function will increment in the label option number
    let inner_html = html.find('.option-number:last').html();
    html.find('.option-number:last').each(function (key, element) {
        inner_html = inner_html.replace(/(\d+)/, function (str, p1) {
            return (parseInt(p1, 10) + 1);
        });
    })
    html.find('.option-number:last').html(inner_html)

    // This function will replace the last index value and increment in the multidimensional name attribute
    html.find(':input').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
    })
    html.find('.remove-option-content').html('<button class="btn btn-inverse-danger remove-option btn-sm mt-1" type="button"><i class="fa fa-times"></i></button>')
    $('.option-container').append(html)

    let select_answer_option = '<option value=' + inner_html + ' class="answer_option extra_answers_options">' + lang_option + ' ' + inner_html + '</option>'
    $('#answer_select').append(select_answer_option)
});
$(document).on('click', '.remove-option', function (e) {
    e.preventDefault();
    $(this).parent().parent().remove();
    $('.option-container').find('.form-group:last').find('.remove-option-content').css('display', 'block');
    $('#answer_select').find('.answer_option:last').remove();
})
$('#create-online-exam-questions-form').on('submit', function (e) {
    e.preventDefault();
    for (var equation_editor in CKEDITOR.instances) {
        CKEDITOR.instances[equation_editor].updateElement();
    }
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback() {
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
$('.question_type').on('change', function (e) {
    $('.quation-option-extra').remove();
    $('#answer_select').val(null).trigger("change");
    if ($(this).val() == 1) {
        $('#simple-question').hide();
        $('#equation-question').show(500);
    } else {
        $('#simple-question').show(500);
        $('#equation-question').hide();
    }
})
$('#add-new-eqation-option').on('click', function (e) {
    e.preventDefault();
    let html = $('.equation-option-container').find('.quation-option-template:last').clone();
    html.find('.error').remove();
    html.find('.has-danger').removeClass('has-danger');
    $('.remove-equation-option-content').css('display', 'none');

    // html.removeClass('col-md-6').addClass('col-md-5');
    // This function will increment in the label equation-option-number
    let inner_html = html.find('.equation-option-number:last').html();
    html.find('.equation-option-number:last').each(function (key, element) {
        inner_html = inner_html.replace(/(\d+)/, function (str, p1) {
            return (parseInt(p1, 10) + 1);
        });
    })

    // This function will replace the last index value and increment in the multidimensional name attribute
    let name;
    html.find(':input').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            name = '[' + (parseInt(p1, 10) + 1) + ']';
            return name;
        });
    })

    let option_html = '<div class="form-group col-md-6 equation-editor-options-extra quation-option-template"><label>' + lang_option + ' <span class="equation-option-number">' + inner_html + '</span> <span class="text-danger">*</span></label><textarea class="editor_options" name="eoption' + name + '" placeholder="' + lang_select_option + '"></textarea><div class="remove-equation-option-content"><button class="btn btn-inverse-danger remove-equation-option btn-sm mt-1" type="button"><i class="fa fa-times"></i></button></div></div>'
    $('.equation-option-container').append(option_html).ready(function () {
        createCkeditor();
    });
    let select_answer_option = '<option value=' + inner_html + ' class="answer_option extra_answers_options">' + lang_option + ' ' + inner_html + '</option>'
    $('#answer_select').append(select_answer_option)
});
$(document).on('click', '.remove-equation-option', function (e) {
    e.preventDefault();
    $(this).parent().parent().remove();
    $('.equation-option-container').find('.form-group:last').find('.remove-equation-option-content').css('display', 'block');
    $('#answer_select').find('.answer_option:last').remove();
})

$('.edit-question-type').on('change', function (e) {
    if ($(this).val() == 1) {
        $('#edit-simple-question-content').hide();
        $('#edit-equation-question-content').show(500);
    } else {
        $('#edit-simple-question-content').show(500);
        $('#edit-equation-question-content').hide();
    }
})
$(document).on('click', '.add-new-edit-option', function (e) {
    e.preventDefault();
    let html = $('.edit_option_container').find('.form-group:last').clone();
    html.find('.add-edit-question-option').val('');
    html.find('.error').remove();
    html.find('.has-danger').removeClass('has-danger');
    html.find('.edit_option_id').val('')
    let hide_button = {}
    hide_button = $('.remove-edit-option-content:last').find('.remove-edit-option')
    if (hide_button.data('id')) {
        $('.remove-edit-option-content:last').css('display', 'block');
    } else {
        $('.remove-edit-option-content:last').css('display', 'none');
    }

    // This function will increment in the label option number
    let inner_html = html.find('.edit-option-number:last').html();
    html.find('.edit-option-number:last').each(function (key, element) {
        inner_html = inner_html.replace(/(\d+)/, function (str, p1) {
            return (parseInt(p1, 10) + 1);
        });
    })
    html.find('.edit-option-number:last').html(inner_html)

    // This function will replace the last index value and increment in the multidimensional name attribute
    html.find(':input').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
    })
    html.find('.remove-edit-option-content').html('<button class="btn btn-inverse-danger remove-edit-option btn-sm mt-1" type="button"><i class="fa fa-times"></i></button>')
    $('.edit_option_container').append(html)

    let select_answer_option = '<option value="new' + $.trim(inner_html) + '" class="edit_answer_option">' + lang_option + ' ' + inner_html + '</option>'
    $('.edit_answer_select').append(select_answer_option)
});
$(document).on('click', '.remove-edit-option', function (e) {
    e.preventDefault();
    if ($(this).data('id')) {
        Swal.fire({
            title: lang_delete_title,
            text: lang_delete_warning,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: lang_yes_delete
        }).then((result) => {
            if (result.isConfirmed) {
                let id = $(this).data('id');
                let url = baseUrl + '/online-exam-question/remove-option/' + id;

                function successCallback(response) {
                    $('#editModal').modal('hide');
                    setTimeout(function () {
                        $('#table_list_questions').bootstrapTable('refresh');
                    }, 500)
                    showSuccessToast(response.message);
                }

                function errorCallback(response) {
                    showErrorToast(response.message);
                }
                ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
            }
        })
    } else {
        $(this).parent().parent().remove();
        $('.edit_answer_select').find('.edit_answer_option:last').remove()
        $('.edit_option_container').find('.form-group:last').find('.remove-edit-option-content').css('display', 'block');
        $('.edit_eoption_container').find('.form-group:last').find('.remove-edit-option-content').css('display', 'block');
    }
});
$(document).on('click', '.remove-answers', function (e) {
    e.preventDefault();
    Swal.fire({
        title: lang_delete_title,
        text: lang_delete_warning,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: lang_yes_delete
    }).then((result) => {
        if (result.isConfirmed) {
            let id = $(this).data('id');
            let url = baseUrl + '/online-exam-question/remove-answer/' + id;

            function successCallback(response) {
                $('#editModal').modal('hide');
                setTimeout(function () {
                    $('#table_list_questions').bootstrapTable('refresh');
                }, 500)
                showSuccessToast(response.message);
            }
            function errorCallback(response) {
                showErrorToast(response.message);
            }

            ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
        }
    })
});
$('#add-new-question-online-exam').on('submit', function (e) {
    e.preventDefault();
    for (var equation_editor in CKEDITOR.instances) {
        CKEDITOR.instances[equation_editor].updateElement();
    }
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        // Get the CKEditor instance
        var editors = Object.values(CKEDITOR.instances);


        // Loop through each instance
        editors.filter(editor => editor.element.hasClass('editor_question')).forEach(editor => {
            editor.setData(''); // clear the text
            editor.resetDirty(); // reset the points to save the changes
        });

        editors.filter(editor => editor.element.hasClass('editor_options')).forEach(editor => {
            editor.setData(''); // clear the text
            editor.resetDirty(); // reset the points to save the changes
        });


        // remove the extra options of ckeditors
        $(document).find('.equation-editor-options-extra').remove();
        $(document).find('.extra_answers_options').remove();

        $('.add-new-question-container').hide(200)
        $('.add-new-question-button').show(300).ready(function () {
            $('.add-new-question-button').html(lang_add_new_question);
        })
        formElement[0].reset();
        $('#simple-question').show();
        $('#equation-question').hide();

        $('#answer_select').val(null).trigger("change");
        $('.quation-option-extra').remove();
        $('#table_list_exam_questions').bootstrapTable('refresh');
        function checkList(listName, newItem) {
            var dupl = false;
            $("#" + listName + " > div").each(function () {
                if ($(this)[0] !== newItem[0]) {
                    if ($(this).html() == newItem.html()) {
                        dupl = true;
                    }
                }
            });
            return !dupl;
        }
        let li = ''
        if (response.data.question_type == 1) {
            li = $('<div class="list-group"><input type="hidden" name="assign_questions[' + response.data.question_id + '][question_id]" value="' + response.data.question_id + '"><li id="q' + response.data.question_id + '"class="list-group-item d-flex justify-content-between align-items-center ui-state-default list-group-item-secondary m-2">' + response.data.question_id + ". " + response.data.question + ' <span class="text-right row"><input type="number" class="list-group-item col-md-6" name="assign_questions[' + response.data.question_id + '][marks]" style="width: 10rem"><a class="btn btn-danger btn-sm remove-row ml-2" data-id="' + response.data.question_id + '"><i class="fa fa-times" aria-hidden="true"></i></a></span></li></div>');
        } else {
            li = $('<div class="list-group"><input type="hidden" name="assign_questions[' + response.data.question_id + '][question_id]" value="' + response.data.question_id + '"><li id="q' + response.data.question_id + '"class="list-group-item d-flex justify-content-between align-items-center ui-state-default list-group-item-secondary m-2">' + response.data.question_id + ". " + '<span class="text-center">' + response.data.question + '</span> <span class="text-right row"><input type="number" class="list-group-item col-md-6" name="assign_questions[' + response.data.question_id + '][marks]" style="width: 10rem"><a class="btn btn-danger btn-sm remove-row ml-2" data-id="' + response.data.question + '"><i class="fa fa-times" aria-hidden="true"></i></a></span></li></div>');
        }
        var pasteItem = checkList("sortable-row", li);
        if (pasteItem) {
            $("#sortable-row").append(li);
        }
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
});
$('.add-new-question-button').on('click', function (e) {
    e.preventDefault();
    $('#answer_select').val(null).trigger("change");
    $('.add-new-question-container').show(300);
    $(this).hide();
    $(this).html('');
})
$('.remove-add-new-question').on('click', function (e) {
    e.preventDefault();
    $('.add-new-question-container').hide(300);
    $('.add-new-question-button').show(300).ready(function () {
        $('.add-new-question-button').html(lang_add_new_question);
    });
})
$(document).on('click', '.remove-row', function (e) {
    e.preventDefault();

    let $this = $(this);
    let id = $this.data('id');
    let edit_id = $this.data('edit_id');

    if (edit_id) {

        const related = $this.data('related');
        let extraHtml = '';

        if (Array.isArray(related) && related.length > 0) {
            extraHtml = `
                <p class="text-danger mt-2">
                    This choice is linked with
                    <strong>${related.join(', ')}</strong>.
                    Deleting it will also delete all associated data.
                </p>
            `;
        }

        Swal.fire({
            title: lang_delete_title,
            html: `
                <p>${lang_delete_warning}</p>
                ${extraHtml}
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: lang_yes_delete
        }).then((result) => {
            if (result.isConfirmed) {
                let url = baseUrl + '/online-exam/remove-choiced-question/' + edit_id;

                function successCallback(response) {
                    showSuccessToast(response.message);
                    $this.parent().parent().parent().remove();
                    $('#table_list_exam_questions').bootstrapTable('refresh');
                }

                function errorCallback(response) {
                    showErrorToast(response.message);
                }

                ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
            }
        });

    } else {
        // Unsaved row
        $this.parent().parent().parent().remove();
        $('#table_list_exam_questions').bootstrapTable('uncheckBy', {
            field: 'question_id',
            values: [id]
        });
    }
}); $('#store-assign-questions-form').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback() {
        window.location.reload();
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})

$('#edit-question-form').on('submit', function (e) {
    e.preventDefault();
    for (var equation_editor in CKEDITOR.instances) {
        CKEDITOR.instances[equation_editor].updateElement();
    }
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let data = new FormData(this);
    data.append("_method", "PUT");
    let url = $(this).attr('action') + "/" + data.get('edit_id');

    function successCallback(response) {
        $('#table_list_questions').bootstrapTable('refresh');
        setTimeout(function () {
            $('#editModal').modal('hide');
        }, 1000)
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})

$(document).on('click', '.delete-question-form', function (e) {
    e.preventDefault();

    let $this = $(this);
    const related = $this.data('related');
    let extraHtml = '';

    if (Array.isArray(related) && related.length > 0) {
        extraHtml = `
            <p class="text-danger mt-2">
                This question is linked with
                <strong>${related.join(', ')}</strong>.
                Deleting it will also delete all associated data.
            </p>
        `;
    }

    Swal.fire({
        title: trans('Are you sure?'),
        html: `
            <p>${trans("You won't be able to revert this!")}</p>
            ${extraHtml}
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: trans('Cancel'),
        confirmButtonText: trans('Yes, delete it!')
    }).then((result) => {
        if (result.isConfirmed) {
            let url = $this.attr('href');
            let data = null;

            function successCallback(response) {
                $('#table_list_questions').bootstrapTable('refresh');
                showSuccessToast(response.message);
            }

            function errorCallback(response) {
                showErrorToast(response.message);
            }

            ajaxRequest('DELETE', url, data, null, successCallback, errorCallback);
        }
    });
});
$('#table_list_questions').on('load-success.bs.table', function () {
    createCkeditor();
});
$('#table_list_exam_questions').on('load-success.bs.table', function () {
    createCkeditor();
});
$(document).on('click', '.add-new-edit-eoption', function (e) {
    e.preventDefault();

    // destroy the editors for no cloning the last ckeditor
    for (var equation_editor in CKEDITOR.instances) {
        CKEDITOR.instances[equation_editor].destroy();
    }
    let html = $('.edit_eoption_container').find('.form-group:last').clone();
    html.find('.editor_options').val('');
    html.find('.error').remove();
    html.find('.has-danger').removeClass('has-danger');
    html.find('.edit_eoption_id').val('')
    let hide_button = {}
    hide_button = $('.remove-edit-option-content:last').find('.remove-edit-option')
    if (hide_button.data('id')) {
        $('.remove-edit-option-content:last').css('display', 'block');
    } else {
        $('.remove-edit-option-content:last').css('display', 'none');
    }

    // This function will increment in the label option number
    let inner_html = html.find('.edit-eoption-number:last').html();
    html.find('.edit-eoption-number:last').each(function (key, element) {
        inner_html = inner_html.replace(/(\d+)/, function (str, p1) {
            return (parseInt(p1, 10) + 1);
        });
    })
    html.find('.edit-eoption-number:last').html(inner_html)

    // This function will replace the last index value and increment in the multidimensional name attribute
    html.find(':input').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
    })
    html.find('.remove-edit-option-content').html('<button class="btn btn-inverse-danger remove-edit-option btn-sm mt-1" type="button"><i class="fa fa-times"></i></button>')
    $('.edit_eoption_container').append(html).ready(function () {
        createCkeditor();
    })

    let select_answer_option = '<option value="new' + $.trim(inner_html) + '" class="edit_answer_option">' + lang_option + ' ' + inner_html + '</option>'
    $('.edit_answer_select').append(select_answer_option)
});
$('.online_exam_based_on').on('change', function (e) {
    if ($(this).val() == 1) {
        $('.class_container').hide(200);
        $('.class_section_container').show(500);
        $('.online-exam-class-section-id').val('');
        $('.online-exam-subject-id').val('');
    } else {
        $('.online-exam-class-id').val('');
        $('.online-exam-subject-id').val('');
        $('.class_section_container').hide(200);
        $('.class_container').show(500);
    }
})
$('.online-exam-class-id').on('change', function (e) {
    e.preventDefault();
    let url = baseUrl + '/get-subject-online-exam';
    let data = {
        'based_on': 0,
        'class_id': $(this).val(),
        'session_year_id': $('#session_year_id').val(),
        'semester_id': $('#semester_id').val()
    };

    function successCallback(response) {
        let html = ""
        if (response.data.length) {
            html += "<option value=''>-- " + lang_select_subject + " --</option>"
            response.data.forEach(function (data) {
                html += "<option value='" + data.id + "'>" + data.name + ' - ' + data.type + "</option>";
            })
        } else {
            html = "<option value=''>" + lang_no_data_found + "</option>";
        }
        $('.online-exam-subject-id').html(html);
    }

    ajaxRequest('GET', url, data, null, successCallback, null, null, true);
})
$('input[type="file"]').on('change', function (e) {
    $(this).closest('form').valid();
})

$('.fees_installment_toggle').on('change', function (e) {
    e.preventDefault();
    if ($(this).val() == 1) {
        $('.fees_installment_content').show(200)
    } else {
        $('.fees_installment_content').hide(200)
    }
})


// add installment content
$(document).on('click', '.add-fee-installment-content', function (e) {
    e.preventDefault();
    let html = $('.fees_installment_content:last').clone();
    html.find('.error').remove();
    html.find('.has-danger').removeClass('has-danger');
    // This function will replace the last index value and increment in the multidimensional name attribute
    html.find('.form-control').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
        this.id = this.id.replace(/_(\d+)/, function (str, p1) {
            return '_' + (parseInt(p1, 10) + 1);
        });
    })
    html.find('.add-fee-installment-content i').addClass('fa-times').removeClass('fa-plus');
    html.find('.add-fee-installment-content').addClass('btn-inverse-danger remove-exam-timetable-content').removeClass('btn-inverse-success add-exam-timetable');
    $(this).parent().parent().parent().siblings('.extra-fee-installment-content').append(html);
    html.find('.form-control').val('');
});

// general form ajax with reload
$('#create-form-reload,.create-form-reload').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback() {
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
$('.add-extra-fee-installment-data').on('click', function (e) {
    e.preventDefault();
    let html = $('.installment-div').find('.edit-installment-container').find('.edit-installment-content:last').clone();
    html.find('.error').remove();
    html.find('.has-danger').removeClass('has-danger');
    // This function will replace the last index value and increment in the multidimensional name attribute
    html.find('.form-control').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
        this.id = this.id.replace(/_(\d+)/, function (str, p1) {
            return '_' + (parseInt(p1, 10) + 1);
        });
    })
    html.find('.add-edit-fee-installment-content i').addClass('fa-times').removeClass('fa-plus');
    html.find('.add-edit-fee-installment-content').addClass('btn-inverse-danger remove-edit-fee-installment-content').removeClass('btn-inverse-success add-edit-fee-installment-content');
    html.find('.remove-edit-fee-installment-content').removeAttr("data-id").removeAttr('data-related');
    $(this).parent().siblings('.edit-installment-container').append(html);
    html.find('.form-control').val('');

    // Initialize datepicker with min/max constraints
    // Read raw ISO dates stored when the edit row was clicked (always Y-m-d, format-independent)
    function parseISOLocal(str) {
        if (!str) return null;
        var p = str.split('-');
        if (p.length !== 3) return null;
        return new Date(parseInt(p[0]), parseInt(p[1]) - 1, parseInt(p[2]));
    }
    var startRaw = $('#id').attr('data-start-date-raw');
    var endRaw = $('#id').attr('data-end-date-raw');
    let dueDateInput = html.find('.datepicker-popup');
    dueDateInput.datepicker('destroy').removeClass('hasDatepicker');
    dueDateInput.datepicker({
        enableOnReadonly: false,
        todayHighlight: true,
        format: 'dd-mm-yyyy',
        autoclose: true,
        startDate: parseISOLocal(startRaw),
        endDate: parseISOLocal(endRaw)
    });
});
$(document).on('click', '.remove-edit-fee-installment-content', function (e) {
    e.preventDefault();
    let $this = $(this);

    if ($this.data('id')) {

        const related = $this.data('related');
        let extraHtml = '';

        if (Array.isArray(related) && related.length > 0) {
            extraHtml = `
                <p class="text-danger mt-2">
                    This installment is linked with <strong>${related.join(', ')}</strong>.
                    Deleting it will also delete all associated data.
                </p>
            `;
        }

        Swal.fire({
            title: trans('Are you sure?'),
            html: `
                <p>${trans("You won't be able to revert this!")}</p>
                ${extraHtml}
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Yes, delete it!')
        }).then((result) => {
            if (result.isConfirmed) {
                let id = $this.data('id');
                let url = baseUrl + '/remove-installment-data/' + id;

                function successCallback(response) {
                    $('#table_list').bootstrapTable('refresh');
                    setTimeout(function () {
                        $('#editModal').modal('hide');
                    }, 500);
                    showSuccessToast(response.message);
                }

                function errorCallback(response) {
                    showErrorToast(response.message);
                }

                ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
            }
        });

    } else {
        $this.parent().parent().parent().remove();
    }
});
$('.pay_optional_fees_offline').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);
    const session_year_id = sessionStorage.getItem('session_year_id_table');
    data.append("session_year_id", session_year_id);

    function successCallback() {
        $('#optionalModal').modal('hide');
        $('.cheque_no_container').hide();
        formElement[0].reset();
        $('#table_list').bootstrapTable('refresh');
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})

$(document).on('click', '.remove-optional-fees-paid', function (e) {
    e.preventDefault();
    let $this = $(this);
    if ($(this).data('id')) {
        Swal.fire({
            title: trans('Are you sure?'),
            text: trans("You won't be able to revert this!"),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Yes, delete it!')
        }).then((result) => {
            if (result.isConfirmed) {
                let id = $this.data('id');
                let url = baseUrl + '/fees/paid/remove-choiceable-fees/' + id;

                function successCallback() {
                    $('#table_list').bootstrapTable('refresh');
                    setTimeout(() => {
                        $('#optionalModal').modal('hide');
                    }, 500);

                }

                function errorCallback(response) {
                    showErrorToast(response.message);
                }

                ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
            }
        })
    } else {
        $(this).parent().parent().remove();
    }
});
$(document).on('click', '.pay_in_installment', function (e) {
    if ($(this).is(':checked')) {
        $('#installment_mode').val(1)
        $('.due_charges_whole_year').hide(200);
        $('.installment_rows').show(200);
        $('.compulsory_amount').html(Number(0).toFixed(2))

        let choice_amount = parseInt($('.compulsory_amount').html());
        // Check the Amount And Make PAY Button Clickable Or Not
        if (choice_amount > 1) {
            $(document).find('.compulsory_fees_payment').prop('disabled', false);
        } else {
            $(document).find('.compulsory_fees_payment').prop('disabled', true);
        }
    } else {
        $(document).find('.compulsory_fees_payment').prop('disabled', false);
        $('#installment_mode').val(0)
        $('.installment_rows').hide(200);
        $('.due_charges_whole_year').show(200);
        $('.compulsory_amount').html($(this).data("base_amount"))
    }
})
$('.pay_compulsory_fees_offline').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);
    const session_year_id = sessionStorage.getItem('session_year_id_table');
    data.append("session_year_id", session_year_id);

    function successCallback() {
        $('#compulsoryModal').modal('hide');
        $('.cheque_no_container').hide();
        formElement[0].reset();
        $('#table_list').bootstrapTable('refresh');
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
$(document).on('click', '.remove-installment-fees-paid', function (e) {
    e.preventDefault();
    let $this = $(this);
    if ($(this).data('id')) {
        Swal.fire({
            title: trans('Are you sure?'),
            text: trans("You won't be able to revert this!"),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Yes, delete it!')
        }).then((result) => {
            if (result.isConfirmed) {
                let id = $this.data('id');
                let url = baseUrl + '/fees/paid/remove-installment-fees/' + id;

                function successCallback(response) {
                    showSuccessToast(response.message);
                    $('#table_list').bootstrapTable('refresh');
                    setTimeout(() => {
                        $('#compulsoryModal').modal('hide');
                    }, 500);

                }

                function errorCallback(response) {
                    showErrorToast(response.message);
                }

                ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
            }
        })
    }
});

$(function () {
    $(".daterange").daterangepicker({
        opens: 'right',
        autoUpdateInput: false,
    }).on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('DD-MM-YYYY') + ' - ' + picker.endDate.format('DD-MM-YYYY'));
    }).on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
    });


});

$(function () {
    $(".timerange").daterangepicker({
        autoUpdateInput: false,
        timePicker: true,
        timePicker24Hour: true,
        timePickerIncrement: 1,
        timePickerSeconds: true,
        locale: {
            format: 'HH:mm:ss'
        }
    }).on('show.daterangepicker', function (ev, picker) {
        picker.container.find(".calendar-table").hide();
        // picker.container.find(".drp-buttons").hide();
    }).on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('HH:mm:ss') + ' - ' + picker.endDate.format('HH:mm:ss'));
    }).on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
    });
});

function initializeTimerangePicker() {
    $(".timerange").daterangepicker({
        autoUpdateInput: false,
        timePicker: true,
        timePicker24Hour: true,
        timePickerIncrement: 1,
        timePickerSeconds: true,
        locale: {
            format: 'HH:mm:ss'
        }
    }).on('show.daterangepicker', function (ev, picker) {
        picker.container.find(".calendar-table").hide();
    }).on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('HH:mm:ss') + ' - ' + picker.endDate.format('HH:mm:ss'));
    }).on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
    });
}

$('.type').on('change', function () {
    if ($.inArray($(this).val(), ['dropdown', 'checkbox', 'radio']) > -1) {
        $('#default-values-div').show(500);
        $('.default_values').attr('disabled', false);
    } else {
        $('#default-values-div').hide(500);
        $('.default_values').attr('disabled', true);
    }
})

$('.edit_type').on('change', function () {
    if ($.inArray($(this).val(), ['dropdown', 'checkbox', 'radio']) > -1) {
        $('#edit-default-values-div').show(500);
        $('.edit_default_values').attr('disabled', false);
    } else {
        $('#edit-default-values-div').hide(500);
        $('.edit_default_values').attr('disabled', true);
    }
})

$('.event_type').on('change', function () {
    var type = $(this).val();
    if (type == 'multiple') {
        $('#single-div').hide();
        $('#date-range-div').show(500);
        $('#add-multiple-event-div').show(500);
        $('#extra-multiple-event').show(500);
        $('#add-more').show();
    } else {
        $('#date-range-div').hide();
        $('.add-multiple-event-div').hide();
        $('#extra-multiple-event').hide();
        $('#add-more').hide();
        $('#single-div').show(500);
    }

})

$('.edit_event_type').on('change', function () {
    var type = $(this).val();
    if (type == 'multiple') {
        $('#edit-single-div').hide();
        $('#edit-date-range-div').show(500);
        $('#edit-multiple-event-group-div').show(500);
        $('#edit-extra-multiple-event').show(500);
        $('#edit-add-more').show();
    } else {
        $('#edit-date-range-div').hide();
        $('#edit-multiple-event-group-div').hide();
        $('#edit-extra-multiple-event').hide();
        $('#edit-add-more').hide();
        $('#edit-single-div').show(500);
    }

})

$('.add-multi-div').on('click', function (e) {
    e.preventDefault();
    var rowCount = $('.add-multiple-event-div').length + 1;

    if (rowCount <= 2) {
        $('.remove-multiple-event-div').attr('disabled', true);
    } else {
        $('.remove-multiple-event-div').attr('disabled', false);
    }
    let html = $('.add-multiple-event-div:last').clone().show();

    html.find(':input').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
        this.id = this.id.replace(/\d+/, function (match) {
            return parseInt(match, 10) + 1;
        });
    })
    html.find('input[type="text"]').val('');
    html.find('textarea').val('');

    html.insertAfter('.add-multiple-event-div:last');
    initializeTimerangePicker(html);
})

$(document).on('click', '.remove-new-multiple-event-group', function (e) {
    e.preventDefault();
    $(this).parent().parent().remove();
})

$(document).on('click', '.remove-multiple-event-div', function (e) {
    e.preventDefault();
    var rowCount = $('.add-multiple-event-div').length - 1;
    if (rowCount <= 2) {
        $('.remove-multiple-event-div').attr('disabled', true);
    } else {
        $('.remove-multiple-event-div').attr('disabled', false);
    }
    $(this).parent().parent().remove();
})

$(document).on('click', '.remove-multiple-event-div-edit', function (e) {
    e.preventDefault();
    $(this).parent().parent().remove();
})

$('.add-multiple-event-group-div').on('click', function (e) {
    e.preventDefault();
    $('.remove-multiple-event-div').attr('disabled', false);
    let html = $('.add-multiple-event-div:last').clone().show();
    html.find(':input').each(function (key, element) {
        this.name = this.name.replace(/\[(\d+)\]/, function (str, p1) {
            return '[' + (parseInt(p1, 10) + 1) + ']';
        });
    })
    html.find('input[type="text"]').val('');
    html.find('textarea').val('');
    $('#edit-extra-multiple-event').append(html);

    initializeTimerangePicker(html);
})

$(document).on('click', '.edit-remove-multiple-event-group', function (e) {
    e.preventDefault();

    let $this = $(this);
    if ($(this).data('id')) {
        Swal.fire({
            title: trans('Are you sure?'),
            text: trans("You won't be able to revert this!"),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Yes, delete it!')
        }).then((result) => {
            if (result.isConfirmed) {
                let id = $this.data('id');
                let url = baseUrl + '/multiple-event/' + id;

                function successCallback() {
                    $('#table_list').bootstrapTable('refresh');
                    $this.parent().parent().remove();
                }

                function errorCallback(response) {
                    showErrorToast(response.message);
                }

                ajaxRequest('DELETE', url, null, null, successCallback, errorCallback);
            }
        })
    } else {

        $(this).parent().parent().remove();
    }
})

$('.add-more-default-values').on('click', function (e) {
    e.preventDefault();
    $('.remove-default-values').attr('disabled', false);
    let html = $('#add-default-values .row:last').clone();
    html.find('.default_values').val('');
    $('#add-default-values').append(html);
})

$('.edit-add-more-default-values').on('click', function (e) {
    e.preventDefault();
    $('.edit-remove-default-values').attr('disabled', false);
    let html = $('#edit-add-default-values .row:last').clone();
    html.find('.edit_default_values').val('');
    $('#edit-add-default-values').append(html);
})

$(document).on('click', '.remove-default-values', function (e) {
    e.preventDefault();
    $(this).parent().parent().remove();
    if ($('#add-default-values .row').length === 2) {
        $('.remove-default-values').attr('disabled', true);
    }
})

$(document).on('click', '.edit-remove-default-values', function (e) {
    e.preventDefault();
    $(this).parent().parent().remove();
    if ($('#edit-add-default-values .row').length === 2) {
        $('.edit-remove-default-values').attr('disabled', true);
    }
})

// Repeater On Default Values section's Option Section
var editDefaultValuesRepeater = $('.edit-default-values-section').repeater({
    show: function () {
        var optionNumber = parseInt($('.edit-option-section:nth-last-child(2)').find('.edit-option-number').text()) + 1;

        if (!optionNumber) {
            optionNumber = 1;
        }

        $(this).find('.edit-option-number').text(optionNumber);

        $(this).slideDown();
        $(this).addClass('extra-edit-option-section');

        editToggleAccessOfDeleteButtons();

    },
    hide: function (deleteElement) {
        Swal.fire({
            title: trans('Are you sure?'),
            text: trans("You won't to delete this element?"),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: trans('Cancel'),
            confirmButtonText: trans('Yes, delete it!')
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).slideUp(deleteElement);
            }
        })

    }
});


let toggleAccessOfDeleteButtons = () => {
    if ($('.option-section').length >= 3) {
        $('.remove-default-option').removeAttr('disabled');
    } else {
        $('.remove-default-option').attr('disabled', false);
    }
}

// Function to make remove button accessible on the basis of Option Section Length
let editToggleAccessOfDeleteButtons = () => {
    if ($('.edit-option-section').length >= 3) {
        $('.remove-edit-default-option').removeAttr('disabled');
    } else {
        $('.remove-edit-default-option').attr('disabled', false);
    }
}

// Change the order of Form fields Data
$('#change-order-form-field').click(async function () {
    const ids = await $('#table_list').bootstrapTable('getData').map(function (row) {
        return row.id;
    });
    $.ajax({
        type: "post",
        url: baseUrl + "/form-fields/change-rank",
        data: {
            ids: ids
        },
        dataType: "json",
        success: function (data) {
            $('#table_list').bootstrapTable('refresh');
            if (!data.error) {
                showSuccessToast(data.message);
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showErrorToast(data.message);
            }
        }
    });
})

$(".create-form-field").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        formElement[0].reset();
        setTimeout(function () {
            location.reload();
        }, 1000);
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});


$(".edit-form-field").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            $('#editModal').modal('hide');
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$('.send_to').on('change', function () {
    let send = $(this).val();
    if (send == 2) {
        $('#user_div').show(400);
        $('.user_div').attr('disabled', false);
    }
    else {
        $('#user_div').hide(400);
        $('.user_div').attr('disabled', true);
    }
});


$('#show-image-uploader').on('change', function () {
    if ($(this).is(':checked')) {
        $('#image-uploader').show(400);
        $('#image-uploader').attr('disabled', false);
    } else {
        $('#image-uploader').hide(400);
        $('#image-uploader').attr('disabled', true);
    }
});

$(".create-notification").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        formElement[0].reset();
        setTimeout(function () {
            location.reload();
        }, 1000);
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

// $(document).ready(function () {
//     $('.online_exam_based_on').on('change', function () {
//         location.reload(); // Refresh the page
//     });
// });

$(".event-form").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        formElement[0].reset();
        setTimeout(function () {
            location.reload();
        }, 1000);
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-event").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);
    data.append("_method", "PUT");
    function successCallback(response) {
        setTimeout(function () {
            $('#editModal').modal('hide');
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-schedule").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);
    function successCallback(response) {
        setTimeout(function () {
            $('#editModal').modal('hide');
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-content-about").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            $('#editModal').modal('hide');
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-content-whoweare").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            $('#editModal').modal('hide');
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-content-teacher").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            $('#editModal').modal('hide');
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-content-event").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            $('#editModal').modal('hide');
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-content-program").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            $('#editModal').modal('hide');
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-content-photo").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            $('#editModal').modal('hide');
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-content-video").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            $('#editModal').modal('hide');
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-content-faq").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            $('#editModal').modal('hide');
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-content-app").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            $('#editModal').modal('hide');
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-content-question").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            $('#editModal').modal('hide');
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-program").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            $('#editModal').modal('hide');
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-photo").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#edit-image").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#staff-form").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#staff-edit-form").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);
    data.append("_method", "PUT");
    function successCallback(response) {
        setTimeout(function () {
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});


$(document).on('click', '.image-delete', function (e) {
    e.preventDefault();
    Swal.fire({
        title: trans('Are you sure?'),
        text: trans("You won't be able to revert this!"),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: trans('Cancel'),
        confirmButtonText: trans('Yes, delete it!')
    }).then((result) => {
        if (result.isConfirmed) {
            let url = $(this).attr('href');
            let data = null;

            function successCallback(response) {
                setTimeout(function () {
                    location.reload();
                }, 1000)
                showSuccessToast(response.message);
            }

            function errorCallback(response) {
                showErrorToast(response.message);
            }

            ajaxRequest('DELETE', url, data, null, successCallback, errorCallback);
        }
    })
})

$("#edit-video").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#id-card-setting").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$(document).on('click', '.student-id-card-settings', function (e) {
    e.preventDefault();
    let type = $(this).data('type');
    let link = baseUrl + '/remove-image/';
    Swal.fire({
        title: trans('Are you sure?'),
        text: trans("You won't be able to revert this!"),
        icon: 'warning',
        showCancelButton: true,
        cancelButtonText: trans('Cancel'),
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: trans('Cancel'),
        confirmButtonText: trans('Yes, delete it!')
    }).then((result) => {
        if (result.isConfirmed) {
            let url = link + type;
            let data = null;

            function successCallback(response) {
                $('#' + type).hide(500);
                setTimeout(function () {
                    location.reload();
                }, 1000)
                showSuccessToast(response.message);
            }

            function errorCallback(response) {
                showErrorToast(response.message);
            }

            ajaxRequest('DELETE', url, data, null, successCallback, errorCallback);
        }
    })

})

$("#leave-setting").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);
    data.append("_method", "PUT");

    function successCallback(response) {
        setTimeout(function () {
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$('#to_date,#from_date').change(function (e) {
    e.preventDefault();
    let from_date = $('#from_date').val().split("-").reverse().join("-");
    let to_date = $('#to_date').val().split("-").reverse().join("-");
    let div = '.leave_dates';
    let to_date_null = '#to_date';
    let disabled = '';
    let holiday_days = $('.holiday_days').val();
    // public_holiday
    let public_holiday = $('.public_holiday').val();
    if (holiday_days) {
        holiday_days = holiday_days.split(',');
    } else {
        holiday_days = [];
    }
    let html = date_list(from_date, to_date, div, to_date_null, disabled, holiday_days, public_holiday);

    $('.leave_dates').html(html);
});

$('#edit_to_date,#edit_from_date').change(function (e) {
    e.preventDefault();
    let from_date = $('#edit_from_date').val().split("-").reverse().join("-");
    let to_date = $('#edit_to_date').val().split("-").reverse().join("-");
    let div = '.edit_leave_dates';
    let to_date_null = '#edit_to_date';
    let disabled = 'disabled';
    let holiday_days = $('.holiday_days').val();
    let public_holiday = $('.public_holiday').val();

    if (holiday_days) {
        holiday_days = holiday_days.split(',');
    } else {
        holiday_days = [];
    }
    let html = date_list(from_date, to_date, div, to_date_null, disabled, holiday_days, public_holiday);

    $('.edit_leave_dates').html(html);
});

function date_list(from_date, to_date, div, to_date_null, disabled, holiday_days, public_holiday) {
    if (from_date && to_date) {
        from_date = new Date(from_date);
        to_date = new Date(to_date);
        var days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
        if (from_date > to_date) {
            $(to_date_null).val('');
        }

        if (public_holiday) {
            public_holiday = public_holiday.split(',').map(date => {
                const [year, month, day] = date.split('-');
                return `${day}-${month}-${year}`;
            });
        }
        let html = '';
        $(div).slideDown(500);
        while (from_date <= to_date) {
            let date = moment(from_date, 'YYYY-MM-DD').format('DD-MM-YYYY');
            let day = days[from_date.getDay()];
            if (!holiday_days.includes(day) && !public_holiday.includes(date)) {
                html += '<div class="form-group col-sm-12 col-md-12">';
                html += '<label class="mr-2">' + date + '</label>-';
                html += '<label class="ml-2">' + day + '</label>';
                html += '<div class="form-group row col-sm-12 col-md-12"> <div class="form-check mr-3"> <label class="form-check-label"> <input type="radio" class="form-check-input" name="type[' + date + '][]" id="optionsRadios1" ' + disabled + ' value="Full"> ' + 'Full' + ' <i class="input-helper"></i></label> </div> <div class="form-check mr-3"> <label class="form-check-label"> <input type="radio" class="form-check-input" name="type[' + date + '][]" id="optionsRadios2" ' + disabled + ' value="First Half"> ' + "First Half" + ' <i class="input-helper"></i></label> </div> <div class="form-check mr-3"> <label class="form-check-label"> <input type="radio" class="form-check-input" name="type[' + date + '][]" id="optionsRadios3" ' + disabled + ' value="Second Half">' + "Second Half" + ' <i class="input-helper"></i></label> </div> </div>';
                html += '</div>';
            }
            from_date.setDate(from_date.getDate() + 1);
        }
        return html;
    }

}

$(".status-update").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#create-leave").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$("#chat-delete").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback(response) {
        setTimeout(function () {
            location.reload();
        }, 1000)
        formElement[0].reset();
    }
    function errorCallback(response) {
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
});

$(".add-attendance").submit(function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    if ($('.search-input').val()) {
        Swal.fire({
            text: "Kindly clear the data from the search field",
            icon: 'error',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ok'
        });
    } else {
        function successCallback(response) {
            setTimeout(function () {
                location.reload();
            }, 1000)
            formElement[0].reset();
        }
        function errorCallback(response) {
        }

        formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback, errorCallback);
    }
});

$('.student_inactive').on('change', function () {
    $('#class_section_div').hide(500);
});

$('.student_active').on('change', function () {
    $('#class_section_div').show(500);
});

$('#edit_class_id').on('change', function () {

    // let exam_id = $('#exam_id').val();
    let class_id = $(this).val();

    if (!class_id || Array.isArray(class_id)) {
        return;
    }

    let url = baseUrl + '/get-class-section-by-class/' + class_id;

    function successCallback(response) {
        let html = ''
        if (response.data) {
            html = '<option value="" >Select Class Section</option>';
            $.each(response.data, function (key, data) {
                html += '<option value=' + data.id + '>' + data.class.name + ' - ' + data.section.name + ' ' + data.class.medium.name + ' ' + (data.class.streams ? data.class.streams.name : '') + '</option>';

            });
        } else {
            html = '<option>No Class Section Found</option>';
        }
        $('#edit_class_section_id').html(html);
    }

    ajaxRequest('GET', url, null, null, successCallback, null);
});

$('.rejected').on('change', function () {
    if ($(this).is(':checked')) {
        $('#rejected_reason_div').show(400);
        $('#reject_reason').attr('disabled', false);
    }
});

$('.approved').on('change', function () {
    if ($(this).is(':checked')) {
        $('#rejected_reason_div').hide(400);
        $('#reject_reason').attr('disabled', false);
    }
});

$('.pending').on('change', function () {
    if ($(this).is(':checked')) {
        $('#rejected_reason_div').hide(400);
        $('#reject_reason').attr('disabled', false);
    }
});


document.addEventListener('DOMContentLoaded', function () {
    jQuery(function ($) {

        // Prevent table from loading until both filters are selected
        var $studentTable = $('#table_list');
        if ($studentTable.length && $studentTable.data('query-params') === 'studentDetailsqueryParams') {
            // Remove data-url so bootstrap-table doesn't auto-load on init
            var studentListUrl = $studentTable.data('url');
            $studentTable.removeAttr('data-url');
            $studentTable.bootstrapTable('removeAll');

            function refreshStudentTable() {
                var sessionYear = $('#filter_session_year').val();
                var classSection = $('#filter_class_section_id').val();
                if (sessionYear && classSection) {
                    $studentTable.bootstrapTable('refreshOptions', { url: studentListUrl });
                    $studentTable.bootstrapTable('refresh');
                } else {
                    // Clear the table when filters are not fully selected
                    $studentTable.bootstrapTable('refreshOptions', { url: '' });
                    $studentTable.bootstrapTable('removeAll');
                }
            }

            window.studentDetailsqueryParams = function (p) {
                return {
                    limit: p.limit,
                    offset: p.offset,
                    sort: p.sort,
                    order: p.order,
                    search: p.search,
                    class_id: $('#filter_class_section_id').val(),
                    filter_status: $('#filter_status').val(),
                    filter_elective_subject: $('#filter_elective_subject').val(),
                    session_year_id: $('#filter_session_year').val(),
                    semester_id: $('#filter_semester').val(),
                };
            };

            $('#filter_class_section_id, #filter_status, #filter_semester,#filter_elective_subject').on('change',
                function () {
                    refreshStudentTable();
                });

            // For restricted teachers: reload class section dropdown when session year changes
            if ($('#is_restricted_teacher').val() === '1') {
                function loadStudentClassSections(session_year_id) {
                    var url = $('#student_class_sections_url').val();
                    var data = { session_year_id: session_year_id };

                    function successCallback(response) {
                        var filterHtml = '<option value="">' + trans('select_class_section') + '</option>';
                        var editHtml = '<option value="">' + trans('select') + ' ' + trans('class') + ' ' + trans('section') + '</option>';
                        if (response.length > 0) {
                            $.each(response, function (key, value) {
                                filterHtml += '<option value="' + value.id + '">' + value.name + '</option>';
                                editHtml += '<option value="' + value.id + '">' + value.name + '</option>';
                            });
                        }
                        $('#filter_class_section_id').html(filterHtml);
                        $('#edit_class_section_id').html(editHtml);
                        refreshStudentTable();
                    }

                    ajaxRequest('GET', url, data, null, successCallback, null, null, true);
                }

                // Listen for changes on the table-scoped session year dropdown
                $(document).on('change', '.filter_session_year[data-scope="table"]', function () {
                    loadStudentClassSections($(this).val());
                });

                // Initial load
                var initialSessionYear = $('.filter_session_year[data-scope="table"]').val();
                if (initialSessionYear) {
                    loadStudentClassSections(initialSessionYear);
                }
            } else {
                // Non-restricted: still require both filters before loading
                $(document).on('change', '.filter_session_year[data-scope="table"]', function () {
                    refreshStudentTable();
                });
            }
        } else {
            // Fallback for pages that don't have the student table
            window.studentDetailsqueryParams = function (p) {
                return {
                    limit: p.limit,
                    offset: p.offset,
                    sort: p.sort,
                    order: p.order,
                    search: p.search,
                    class_id: $('#filter_class_section_id').val(),
                    filter_status: $('#filter_status').val(),
                    filter_elective_subject: $('#filter_elective_subject').val(),
                    session_year_id: $('#filter_session_year').val(),
                    semester_id: $('#filter_semester').val(),
                };
            };

            $('#filter_class_section_id, #filter_status, #filter_semester, #filter_elective_subject').on('change',
                function () {
                    $('#table_list').bootstrapTable('refresh');
                });
        }
    });
});


$(document).ready(function () {
    // ========= STATE VARIABLES (Reset on every modal open/close) =========
    let assignedSubjectIds = [];     // Per-student pre-assigned subjects
    let selectedStudentIds = [];     // Bulk mode: selected student IDs
    let currentModalMode = null;     // 'single' or 'bulk'

    // ===================================================================
    // ====================== RESET FUNCTIONS ============================
    // ===================================================================

    /**
     * Reset all modal-related state
     */
    function resetAllModalState() {
        assignedSubjectIds = [];
        selectedStudentIds = [];
        currentModalMode = null;

        $('#assign_student_id').val('');
        $('#assignModal').removeAttr('data-bulk');
        $('#studentDetails').empty();
        $('#assignForm')[0].reset();

        resetElectiveModal();
    }

    // /**
    //  * Reset Students Selection Data
    //  */
    // function resetStudentsData() {
    //     selectedStudentIds = [];
    // }

    /**
     * Reset Elective Modal UI + State
     */
    function resetElectiveModal() {
        $('#subjectsContainer').empty();
        $('#selectInfo').hide();
        $('#selectCount').text('');
        $('#saveAssignmentBtn').prop('disabled', true).text('Save');
        $('#elective_group').html('<option value="">Choose an elective group</option>');
        assignedSubjectIds = []; // Always reset assigned subjects
    }

    // ===================================================================
    // ========================== AJAX HELPERS ===========================
    // ===================================================================

    /**
     * Load Elective Groups via AJAX
     */
    function loadElectiveGroups(classId) {
        const $select = $('#elective_group');
        $select.html('<option value="">Loading...</option>').prop('disabled', true);

        $.ajax({
            url: baseUrl + '/get-elective-groups-by-class',
            type: 'GET',
            data: {
                class_id: classId,
                session_year_id: $('#filter_session_year').val(),
                semester_id: $('#filter_semester').val() || ''
            },
            success: function (response) {
                $select.empty().append('<option value="">Choose an elective group</option>').prop('disabled', false);

                if (!response || response.length === 0) {
                    $select.append('<option value="" disabled>No elective groups found</option>');
                    return;
                }

                $.each(response, function (index, group) {
                    const groupText = group.name || ('Group ' + (index + 1));
                    const displayText = group.total_subjects ? `${groupText} (${group.total_subjects} subjects)` : groupText;

                    const $option = $('<option></option>')
                        .val(group.id)
                        .text(displayText)
                        .attr('data-group', JSON.stringify(group));

                    $select.append($option);
                });

                if (response.length === 1) {
                    $select.val(response[0].id).trigger('change');
                }
            },
            error: function (xhr, status, error) {
                console.error('Failed to load groups:', error);
                $select.empty().append('<option value="" disabled>Failed to load groups</option>').prop('disabled', false);
            }
        });
    }

    /**
     * Load Student's Previously Assigned Subjects (Single Mode Only)
     */
    function loadAssignedSubjects(studentId, callback) {
        $.ajax({
            url: baseUrl + '/get-student-assigned-subjects',
            type: 'GET',
            data: {
                student_id: studentId,
                session_year_id: $('#filter_session_year').val(),
                semester_id: $('#filter_semester').val() || ''
            },
            success: function (response) {
                assignedSubjectIds = response.assigned_subjects || [];
                if (callback) callback();
            },
            error: function () {
                assignedSubjectIds = [];
                if (callback) callback();
            }
        });
    }

    // ===================================================================
    // ===================== BOTTOM SELECTION BAR ========================
    // ===================================================================

    function updateBottomSelectionBar() {
        const selections = $('#table_list').bootstrapTable('getSelections');
        const count = selections.length;
        const $bottomBar = $('#bottomSelectionBar');
        const $selectionCount = $('#selectionCount');
        const $selectedAvatars = $('#selectedAvatars');

        if (count > 0) {
            $bottomBar.fadeIn(300);
            $selectionCount.text(`${count} Student${count > 1 ? 's' : ''} selected`);
            $selectedAvatars.empty();

            const maxVisible = 5;
            const visible = selections.slice(0, maxVisible);

            visible.forEach(student => {
                const name = student.full_name || student.name;
                const imageUrl = student.photo || student.profile_image || student.avatar ||
                    `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=10B981&color=fff`;

                const $avatar = $('<img>', {
                    src: imageUrl,
                    alt: name,
                    class: 'avatar-item',
                    title: name,
                }).on('error', function () {
                    $(this).attr('src', `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=10B981&color=fff`);
                });

                $selectedAvatars.append($avatar);
            });

            if (count > maxVisible) {
                $selectedAvatars.append(
                    $('<div>', {
                        class: 'avatar-more',
                        text: `+${count - maxVisible}`,
                        title: `${count - maxVisible} more`
                    })
                );
            }
        } else {
            $bottomBar.fadeOut(300);
        }
    }

    // ===================================================================
    // ========================= EVENT BINDINGS ==========================
    // ===================================================================

    $('#table_list').on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', updateBottomSelectionBar);
    $('#table_list').on('load-success.bs.table', updateBottomSelectionBar);

    $('#clearSelectionBtn').on('click', function () {
        $('#table_list').bootstrapTable('uncheckAll');
        resetAllModalState();
        updateBottomSelectionBar();
    });

    // ===================================================================
    // ======================= BULK ASSIGN BUTTON ========================
    // ===================================================================

    $('#bulkAssignBtn').on('click', function () {
        const selections = $('#table_list').bootstrapTable('getSelections');
        if (selections.length === 0) {
            showErrorToast('Please select at least one student');
            return;
        }

        const sectionIds = selections.map(s => s.class_section_id);
        const uniqueSections = [...new Set(sectionIds)];
        if (uniqueSections.length > 1) {
            showErrorToast('All selected students must be from the same class section');
            return;
        }

        const first = selections[0];
        const classId = first.class_id || first.class_section?.class_id;
        if (!classId) {
            showErrorToast('Unable to determine class ID');
            return;
        }

        // === FULL RESET BEFORE OPENING ===
        resetAllModalState();

        // === BULK MODE SETUP ===
        selectedStudentIds = selections.map(s => s.id);
        currentModalMode = 'bulk';

        $('#assign_student_id').val(selectedStudentIds.join(','));
        $('#assignModal').attr('data-bulk', 'true');
        $('#studentDetails').html(`
            <strong>${selections.length} students selected</strong><br>
            <small>${selections.map(s => s.full_name).join(', ').substring(0, 100)}${selections.length > 5 ? '...' : ''}</small>
        `);

        loadElectiveGroups(classId);
        $('#assignModal').modal('show');
    });

    // ===================================================================
    // ======================= SINGLE STUDENT MODAL ======================
    // ===================================================================

    $(document).on('show.bs.modal', '#assignModal', function (event) {
        if ($(event.relatedTarget).is('#bulkAssignBtn')) return; // Skip if bulk

        const button = $(event.relatedTarget);
        const studentId = button.data('student-id');
        const classId = button.data('class-id');

        if (!studentId || !classId) return;

        // === FULL RESET ===
        resetAllModalState();
        currentModalMode = 'single';

        var row = $('#table_list').bootstrapTable('getRowByUniqueId', studentId);

        if (row) {
            var studentName = row.full_name || '';
            $('#studentDetails').text(studentName);
        }

        $('#assign_student_id').val(studentId);
        $('#assignModal').attr('data-bulk', 'false');

        // Load assigned + groups sequentially
        loadAssignedSubjects(studentId, () => loadElectiveGroups(classId));
    });

    // ===================================================================
    // ========================== GROUP CHANGE ===========================
    // ===================================================================

    $(document).on('change', '#elective_group', function () {
        const $selected = $(this).find('option:selected');
        const groupJson = $selected.attr('data-group');
        if (!groupJson) {
            resetSubjectContainer();
            return;
        }

        const group = parseElectiveData(groupJson);
        if (!group) {
            resetSubjectContainer();
            return;
        }

        renderElectiveSubjects(group);
    });

    function resetSubjectContainer() {
        $('#subjectsContainer').empty();
        $('#selectInfo').hide();
        $('#saveAssignmentBtn').prop('disabled', true);
    }

    function parseElectiveData(jsonString) {
        try { return JSON.parse(jsonString); } catch (e) { return null; }
    }

    // ===================================================================
    // ======================= SUBJECT RENDERING =========================
    // ===================================================================

    function renderElectiveSubjects(group) {
        const $container = $('#subjectsContainer').empty();
        const required = parseInt(group.total_selectable_subjects || 0, 10);

        $('#selectCount').text(required);
        $('#selectInfo').show();

        if (!group.elective_subjects || group.elective_subjects.length === 0) {
            $container.html('<div class="elective-empty-state">No subjects found for this group.</div>');
            $('#saveAssignmentBtn').prop('disabled', true);
            return;
        }

        group.elective_subjects.forEach(item => {
            const subject = item.subject;
            const isAssigned = currentModalMode === 'single' && assignedSubjectIds.includes(subject.id);

            const $card = $('<div>', {
                class: 'elective-subject-card' + (isAssigned ? ' selected' : ''),
                'data-subject-id': subject.id
            });

            const $checkbox = $('<input>', {
                type: 'checkbox',
                class: 'elective-subject-checkbox',
                name: `selected_subjects[${group.id}][]`,
                value: subject.id,
                'data-group-id': group.id,
                id: 'subject_' + subject.id,
                checked: isAssigned
            });

            const $content = $('<div>', { class: 'elective-subject-content' });
            const $img = $('<img>', {
                src: subject.image || '/images/default-subject.png', //default image is pending
                class: 'elective-subject-image',
                alt: subject.name,
            });

            const $info = $('<div>', { class: 'elective-subject-info' });
            $('<div>', { class: 'elective-subject-name', text: subject.name }).appendTo($info);
            $('<div>', { class: 'elective-subject-code', text: 'Code: ' + subject.code }).appendTo($info);

            $content.append($img).append($info);
            $card.append($checkbox).append($content);
            $container.append($card);
        });

        attachSubjectHandlers(group, required);
    }

    function attachSubjectHandlers(group, required) {
        const $container = $('#subjectsContainer');

        $container.off('click.change').on('click.change', '.elective-subject-card', function (e) {
            if ($(e.target).is('input')) return;
            const $cb = $(this).find('.elective-subject-checkbox');
            $cb.prop('checked', !$cb.prop('checked')).trigger('change');
        });

        $container.off('change.cb').on('change.cb', '.elective-subject-checkbox', function () {
            const $checked = $(`input[name="selected_subjects[${group.id}][]"]:checked`);
            const count = $checked.length;

            if (count > required) {
                $(this).prop('checked', false);
                showElectiveAlert(`You can select only ${required} subject(s) from this group.`);
                return;
            }

            $(this).closest('.elective-subject-card').toggleClass('selected', this.checked);
            $('#saveAssignmentBtn').prop('disabled', count !== required);
        });

        // Initial state
        $('#saveAssignmentBtn').prop('disabled',
            $(`input[name="selected_subjects[${group.id}][]"]:checked`).length !== required
        );
    }

    function showElectiveAlert(msg) {
        alert(msg);
    }

    // ===================================================================
    // =========================== FORM SUBMIT ===========================
    // ===================================================================

    $('#assignForm').on('submit', function (e) {
        e.preventDefault();

        const isBulk = $('#assignModal').attr('data-bulk') === 'true';
        let formData = $(this).serializeArray();
        const session_year_id = sessionStorage.getItem('session_year_id_table');

        formData.push({ name: 'is_bulk', value: isBulk ? 1 : 0 });
        formData.push({ name: 'session_year_id', value: session_year_id });

        const semester_id = $('#filter_semester').val();
        if (semester_id) {
            formData.push({ name: 'semester_id', value: semester_id });
        }

        if (isBulk && selectedStudentIds.length > 0) {
            selectedStudentIds.forEach((id, i) => {
                formData.push({ name: `student_ids[${i}]`, value: id });
            });
            formData = formData.filter(f => f.name !== 'student_id');
        }

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            beforeSend: () => {
                $('#saveAssignmentBtn').prop('disabled', true).text('Saving...');
            },
            success: (response) => {
                showSuccessToast(response.message || 'Assignment saved!');
                $('#table_list').bootstrapTable('refresh');
                $('#table_list').bootstrapTable('uncheckAll');
            },
            error: (jqXHR) => {
                let msg = 'Error saving assignment.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    msg = jqXHR.responseJSON.message;
                }
                showErrorToast(msg);
            },
            complete: () => {
                $('#saveAssignmentBtn').prop('disabled', false).text('Save');
                $('#assignModal').modal('hide'); // This will trigger hidden.bs.modal
            }
        });
    });

    // ===================================================================
    // ========================= MODAL HIDDEN ============================
    // ===================================================================

    $(document).on('hidden.bs.modal', '#assignModal', function () {
        // FULL CLEANUP on close
        resetAllModalState();
        updateBottomSelectionBar();
    });
});

//? Promote Student Code

//* Class Section Selection
$('#classes').on('change', function () {

    // 1. Clear table immediately
    $('#promote_student_table_list').bootstrapTable('load', { rows: [], total: 0 });

    let class_id = $(this).val();
    let url = baseUrl + '/get-class-sections/' + encodeURIComponent(class_id);

    function successCallback(response) {
        let html = '<option value="">' + trans('select_class') + '</option>';

        $.each(response, function (key, value) {
            html += '<option value="' + value.section_id + '">' + value.full_name + '</option>';
        });

        $('#student_class_section').html(html);

        // Clear table again after sections update
        $('#promote_student_table_list').bootstrapTable('load', { rows: [], total: 0 });
    }

    ajaxRequest('GET', url, null, null, successCallback, null, null, true);
});

// Trigger initial load only once
$('#classes').trigger('change');

let currentSessionYear;
function handlePromoteStudentListResponse(res) {
    const $table = $('#promote_student_table_list');
    $table.data('current-session-year', res.current_session_year);
    currentSessionYear = res.current_session_year;
    return res;
}

$(document).on('click', '.promote-student', function () {
    const studentId = $(this).data('student-id');
    const classId = $(this).data('class-id');
    const table = $('#promote_student_table_list').bootstrapTable('getData');
    const student = table.find(s => s.id === studentId);
    const currentClassName = $('#student_class_section option:selected').text().trim();
    const sessionYear = $('#promote_student_table_list').data('current-session-year');

    $('.promote-info-grid').css('grid-template-columns', '1fr 1fr 1fr');
    $('#gr_no_container').show();

    // Store IDs
    $('#modal_student_id').val(studentId);
    $('#modal_class_section_id').val(classId);

    // Populate student info
    if (student) {
        $('#studentDetails').text(student.name || '-');
        $('#modal_gr_number').text(student.admission_no || '-');
        $('#modal_current_class').text(currentClassName || '-');
        $('#modal_current_session_year').text(sessionYear?.name || '-');
    }

    // Update repeat option and filter current class from dropdown
    if (currentClassName) {
        $('#repeat_same_class_option')
            .text('Repeat in Same Class (' + currentClassName + ')')
            .val(classId)
            .show();

        // Hide current class from other options
        $('#promote_to option[data-class-id="' + classId + '"]').hide();
    }
});

// Handle toggle buttons
$(document).on('click', '.promote-toggle-btn', function () {
    const value = $(this).data('value');
    const group = (value === "pass" || value === "fail") ? 'result' : 'continue';

    if (group === 'result') {
        $('.promote-toggle-pass, .promote-toggle-fail').removeClass('active');
        $('#result_status').val(value);
    } else {
        $('.promote-toggle-yes, .promote-toggle-no').removeClass('active');
        $('#continue_school').val(value);

        if (value === 'yes') {
            $('#promote_to_group').slideDown(200);
        } else {
            $('#promote_to_group').slideUp(200);
            $('#promote_to').val('');
        }
    }

    $(this).addClass('active');
});

$('#updatePromoteBtn').on('click', function (e) {
    e.preventDefault();

    const studentId = $('#modal_student_id').val();
    const continueSchool = $('#continue_school').val();
    const studentResult = $('#result_status').val();
    const promoteTo = $('#promote_to').val();
    const sessionYear = $('#session_year_id').val();
    const url = baseUrl + "/promote-student";
    const fromSessionYearId = sessionStorage.getItem('session_year_id_table');


    if (!sessionYear) {
        return showErrorToast('Please select session year');
    }

    if (continueSchool === 'yes' && !promoteTo) {
        showErrorToast('Please select a class to promote to');
        return;
    }

    const statusValue = continueSchool === 'yes' ? 1 : 0;
    const resultValue = studentResult === 'pass' ? 1 : 0;

    const formData = new FormData();

    // Append values
    formData.append('student_id[]', studentId);
    formData.append('status' + studentId, statusValue);
    formData.append('result' + studentId, resultValue);
    formData.append('class_section_id', $('#modal_class_section_id').val());
    formData.append('new_class_section_id', promoteTo);
    formData.append('session_year_id', sessionYear);
    formData.append('previous_session_id_for_student', fromSessionYearId);
    formData.append('_token', $('input[name="_token"]').val());

    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            $('#promoteModal').modal('hide');
            $('#promote_student_table_list').bootstrapTable('refresh');

            if (!response.error) {
                showSuccessToast(response.message || 'Student promotion details updated successfully');
            }
            else {
                showErrorToast(response?.message || 'An error occurred while updating');
            }
        },
        error: function (xhr) {
            showErrorToast(xhr.responseJSON?.message || 'An error occurred while updating');
        }
    });
});


$(document).on('change', '#session_year_id', function () {
    const selectedId = $(this).val();
    if (!selectedId) {
        $('#session_warning').hide().text('');
        return;
    }

    if (!currentSessionYear || !currentSessionYear.start_date) {
        console.warn("Current Session Year not loaded yet");
        return;
    }

    $.ajax({
        url: baseUrl + '/session-year/details/' + selectedId,
        method: 'GET',
        success: function (res) {
            if (!res.status) return;

            const selected = new Date(res.data.start_date);
            const current = new Date(currentSessionYear.start_date);

            if (selected < current) {
                $('#session_warning')
                    .show()
                    .html(
                        `<strong>Warning:</strong> You are selecting a previous academic year (${res.data.name}) 
                        which is older than the current session (${currentSessionYear.name}). 
                        This appears to be a demotion or backdated entry.`
                    );
            } else {
                $('#session_warning').hide().text('');
            }
        }
    });
});

// Reset modal on close
$('#promoteModal').on('hidden.bs.modal', function () {
    $('#promoteStudentForm')[0].reset();
    $('.promote-toggle-btn').removeClass('active');
    $('.promote-toggle-pass, .promote-toggle-yes').addClass('active');
    $('#result_status').val('pass');
    $('#continue_school').val('yes');
    $('#promote_to_group').show();
    $('#studentDetails, #modal_gr_number, #modal_current_class').text('-');
    $('#repeat_same_class_option').hide();
    $('#session_warning').hide().text('');
    $('#session_year_id').val('');

    // Show all options again when modal closes
    $('#promote_to option[data-class-id]').show();
});


// ========================= BULK PROMOTE ==============================

// State
let bulkSelectedIds = [];

// Update bottom selection bar (same style as elective page)
function updatePromoteBottomBar() {
    const selections = $('#promote_student_table_list').bootstrapTable('getSelections');
    const count = selections.length;
    const $bar = $('#bottomSelectionBar');
    const $avatars = $('#selectedAvatars');
    const $count = $('#selectionCount');

    if (count === 0) {
        $bar.fadeOut(200);
        return;
    }

    $bar.fadeIn(200);
    $count.text(`${count} Student${count > 1 ? 's' : ''} selected`);
    $avatars.empty();

    // Show up to 5 avatars
    const max = 5;
    selections.slice(0, max).forEach(s => {
        const img = s.profile_image || s.photo || s.avatar ||
            `https://ui-avatars.com/api/?name=${encodeURIComponent(s.name)}&background=10B981&color=fff`;

        $('<img>', {
            src: img,
            class: 'avatar-item',
            title: s.name
        }).appendTo($avatars);
    });

    if (count > max) {
        $('<div>', {
            class: 'avatar-more',
            text: `+${count - max}`
        }).appendTo($avatars);
    }
}

// Bind to table events
$('#promote_student_table_list')
    .on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', updatePromoteBottomBar)
    .on('load-success.bs.table', updatePromoteBottomBar);


// ========================= CLEAR BULK SELECTION ==============================

$('#clearSelectionBtn').on('click', function () {
    $('#promote_student_table_list').bootstrapTable('uncheckAll');
    updatePromoteBottomBar();
});

$('#bulkPromoteBtn').on('click', function () {
    const selections = $('#promote_student_table_list').bootstrapTable('getSelections');

    if (selections.length === 0) {
        return showErrorToast('Please select students first');
    }

    // Validate same class section
    const sectionIds = [...new Set(selections.map(s => s.class_section_id))];
    if (sectionIds.length > 1) {
        return showErrorToast('All selected students must belong to the same class');
    }

    // Store bulk IDs
    bulkSelectedIds = selections.map(s => s.id);

    // Build student list
    const names = selections.map(s => s.name).join(', ');
    $('#studentDetails').html(
        `<strong>${selections.length} students selected</strong><br>${names.substring(0, 120)}...`
    );

    // ========================= SET CURRENT CLASS =========================
    const currentClassName = $('#student_class_section option:selected').text().trim();

    $('#modal_current_class').text(currentClassName || '-');

    const table = $('#promote_student_table_list').bootstrapTable('getData');
    const anyRow = table[0]; // because all are same
    const classId = $(anyRow.updateBtn).data('class-id');

    // ========================= SET CURRENT SESSION =========================
    if (currentSessionYear) {
        $('#modal_current_session_year').text(currentSessionYear.name || '-');
    } else {
        $('#modal_current_session_year').text('-');
    }

    // ========================= BULK DOES NOT SHOW GR NO =========================
    $('.promote-info-grid').css('grid-template-columns', '1fr 1fr');
    $('#gr_no_container').hide();

    // Update repeat option and filter current class from dropdown
    if (currentClassName) {
        $('#repeat_same_class_option')
            .text('Repeat in Same Class (' + currentClassName + ')')
            .val(classId)
            .show();

        // Hide current class from other options
        $('#promote_to option[data-class-id="' + classId + '"]').hide();
    }

    // Reset promote controls
    $('#promoteStudentForm')[0].reset();
    $('#result_status').val('pass');
    $('#continue_school').val('yes');
    $('#session_year_id').val('');

    $('.promote-toggle-btn').removeClass('active');
    $('.promote-toggle-pass, .promote-toggle-yes').addClass('active');

    $('#promote_to_group').show();

    $('#promoteModal').modal('show');
});

// ========================= SUBMIT BULK PROMOTE ==============================

$('#updatePromoteBtn').off('click').on('click', function (e) {
    e.preventDefault();

    const continueSchool = $('#continue_school').val();
    const resultStatus = $('#result_status').val();
    const promoteTo = $('#promote_to').val();
    const currentClassId = parseInt($('#student_class_section option:selected').val());
    const sessionYear = $('#session_year_id').val();
    const fromSessionYearId = sessionStorage.getItem('session_year_id_table');

    // Validation
    if (!sessionYear) {
        return showErrorToast('Please select session year');
    }

    if (continueSchool === 'yes' && !promoteTo) {
        return showErrorToast('Please select a class to promote to');
    }

    const statusVal = continueSchool === 'yes' ? 1 : 0;
    const resultVal = resultStatus === 'pass' ? 1 : 0;

    const formData = new FormData();

    // Single promote?
    const isBulk = bulkSelectedIds.length > 0;

    if (isBulk) {
        bulkSelectedIds.forEach(id => {

            formData.append('student_id[]', id);
            formData.append('status' + id, statusVal);
            formData.append('result' + id, resultVal);
        });
    } else {

        // fallback for single mode (your existing logic)
        const studentId = $('#modal_student_id').val();
        formData.append('student_id[]', studentId);
        formData.append('status' + studentId, statusVal);
        formData.append('result' + studentId, resultVal);
    }

    formData.append('class_section_id', currentClassId);
    formData.append('new_class_section_id', promoteTo);
    formData.append('session_year_id', sessionYear);
    formData.append('previous_session_id_for_student', fromSessionYearId);
    formData.append('_token', $('input[name="_token"]').val());
    $.ajax({
        url: baseUrl + '/promote-student',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (res) {
            $('#promoteModal').modal('hide');
            $('#promote_student_table_list').bootstrapTable('refresh');
            $('#promote_student_table_list').bootstrapTable('uncheckAll');
            bulkSelectedIds = [];
            updatePromoteBottomBar();

            if (!res.error) {
                showSuccessToast(res.message || 'Promotion updated successfully');
            } else {
                showErrorToast(res?.message || 'Error updating promotion');
            }
        },
        error: function (xhr) {
            showErrorToast(xhr.responseJSON?.message || 'Error updating promotion');
        }
    });
});


// ========================= RESET ON MODAL CLOSE ==============================

$('#promoteModal').on('hidden.bs.modal', function () {
    bulkSelectedIds = [];
    updatePromoteBottomBar();
});

// Global variable to store all exam results
let allExamResults = {};
let currentStudentData = {};

// View Exam Result Modal Trigger
$(document).on('click', '.view-exam-result', function (e) {
    e.preventDefault();
    const studentId = $(this).data('student-id');
    const classSectionId = ($(this).data('class_section-id'));
    loadStudentResult(studentId, classSectionId);
});

// Load Student Result Data
function loadStudentResult(studentId, classSectionId) {
    // Show loading state
    $('#studentResultModal').modal('show');
    const url = baseUrl + "/student-result-details";
    const sessionYearId = sessionStorage.getItem('session_year_id_table');

    const data = {
        "student_id": studentId,
        "class_section_id": classSectionId,
        "session_year_id": sessionYearId
    }

    // Make AJAX request to fetch student result data
    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        success: function (response) {
            currentStudentData = response;
            populateResultModal(response);
        },
        error: function (xhr) {
            console.error('Error loading student result:', xhr);
            showErrorToast('Failed to load student result');
            $('#studentResultModal').modal('hide');
        }
    });
}

// Populate Modal with Data
function populateResultModal(data) {
    // Student Info
    $('#result_student_name').text(data?.student?.name || '-');
    $('#result_roll_number').text(data?.student?.roll_number || '-');
    $('#result_gr_number').text(data?.student?.admission_no || '-');
    $('#result_class').text(data?.student?.class_section || '-');

    if (data.student.image) {
        $('#result_student_image').attr('src', data.student.image);
    } else {
        $('#result_avatar').html('<div style="font-size: 24px; font-weight: 600; color: #9CA3AF;">' +
            (data.student.name ? data.student.name.charAt(0).toUpperCase() : '?') + '</div>');
    }

    // Summary Cards
    $('#total_exams').text(data.summary.total_exams || 0);
    $('#average_percentage').text((data.summary.average || 0) + '%');
    $('#highest_percentage').text((data.summary.highest || 0) + '%');
    $('#lowest_percentage').text((data.summary.lowest || 0) + '%');

    // Exam History
    renderExamHistory(data.exam_history);

    // Load first exam's subjects by default
    if (data.exam_history && data.exam_history.length > 0) {
        updateSubjectBreakdown(data.exam_history[0]);
    }
}

// Render Exam History List
function renderExamHistory(examHistory) {
    let examHistoryHtml = '';

    if (examHistory && examHistory.length > 0) {
        examHistory.forEach((exam, index) => {
            const isLatest = index === 0;
            const activeClass = isLatest ? 'active' : '';

            examHistoryHtml += `
                <div class="exam-history-item ${activeClass}" data-exam-index="${index}">
                    <div class="exam-header">
                        <div class="exam-name">
                            ${exam.name}
                            ${isLatest ? '<span class="exam-badge badge-latest">Latest</span>' : ''}
                        </div>
                        <span class="exam-grade-badge grade-${exam.grade.replace('+', '-plus')}">${exam.grade}</span>
                    </div>
                    <div class="exam-date">${exam.date}</div>
                    <div class="exam-result">
                        <span class="exam-percentage">${exam.percentage}%</span>
                        <span class="exam-marks">${exam.obtained_marks} / ${exam.total_marks}</span>
                    </div>
                </div>
            `;
        });
    } else {
        examHistoryHtml = '<p class="text-muted text-center py-3">No exam history available</p>';
    }

    $('#exam_history_list').html(examHistoryHtml);
}

// Handle Exam History Item Click
$(document).on('click', '.exam-history-item', function () {
    // Remove active class from all items
    $('.exam-history-item').removeClass('active');

    // Add active class to clicked item
    $(this).addClass('active');

    // Get exam index
    const examIndex = $(this).data('exam-index');

    // Get exam data
    const examData = currentStudentData.exam_history[examIndex];

    // Update subject breakdown
    updateSubjectBreakdown(examData);
});

// Update Subject Breakdown Section
function updateSubjectBreakdown(examData) {
    if (!examData) return;

    // Update Overall Grade Badge
    $('#overall_grade_badge').text('Overall: ' + (examData.grade || '-'));

    // Render Subjects
    let subjectHtml = '';

    if (examData.subjects && examData.subjects.length > 0) {
        examData.subjects.forEach(subject => {
            const progressColor = subject.percentage >= 80 ? 'progress-green' :
                subject.percentage >= 60 ? 'progress-blue' : 'progress-yellow';

            subjectHtml += `
                <div class="subject-item">
                    <div class="subject-header-row">
                        <h6 class="subject-name">${subject.name}</h6>
                        <span class="subject-grade">${subject.grade}</span>
                    </div>
                    <div class="subject-progress-bar">
                        <div class="subject-progress-fill ${progressColor}" style="width: ${subject.percentage}%"></div>
                    </div>
                    <div class="subject-marks">
                        <span class="marks-obtained">${subject.obtained_marks} / ${subject.total_marks}</span>
                        <span class="marks-percentage">${subject.percentage}%</span>
                    </div>
                </div>
            `;
        });
    } else {
        subjectHtml = '<p class="text-muted text-center py-3">No subject data available</p>';
    }

    $('#subject_breakdown_list').html(subjectHtml);

    // Update Total Marks Footer
    $('#total_obtained_marks').text(examData.obtained_marks || 0);
    $('#total_max_marks').text(examData.total_marks || 0);
    $('#total_percentage_footer').text((examData.percentage || 0) + '%');
}

// Clear modal data on close
$('#studentResultModal').on('hidden.bs.modal', function () {
    currentStudentData = {};
    $('#exam_history_list').html('');
    $('#subject_breakdown_list').html('');
});

document.addEventListener('DOMContentLoaded', function () {
    const globalDropdown = document.querySelector(
        '.filter_session_year[data-scope="global"]'
    );
    const tableDropdown = document.querySelector(
        '.filter_session_year[data-scope="table"]'
    );

    if (!globalDropdown || !tableDropdown) return;

    const GLOBAL_KEY = 'session_year_id_global';
    const TABLE_KEY = 'session_year_id_table';

    // ---------- INITIALIZATION ----------
    const globalStored = sessionStorage.getItem(GLOBAL_KEY);

    const initialValue = globalStored ?? globalDropdown.value;

    // set both dropdowns from global
    globalDropdown.value = initialValue;
    tableDropdown.value = initialValue;

    sessionStorage.setItem(GLOBAL_KEY, initialValue);
    sessionStorage.setItem(TABLE_KEY, initialValue);

    // trigger table change so API runs once
    tableDropdown.dispatchEvent(new Event('change', { bubbles: true }));

    // ---------- GLOBAL → TABLE → API ----------
    globalDropdown.addEventListener('change', function () {
        const value = this.value;

        sessionStorage.setItem(GLOBAL_KEY, value);

        tableDropdown.value = value;
        sessionStorage.setItem(TABLE_KEY, value);
        // syncSessionYear(value);

        tableDropdown.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // ---------- TABLE → API ----------
    tableDropdown.addEventListener('change', function () {
        const value = this.value;

        sessionStorage.setItem(TABLE_KEY, value);
        syncSessionYear(value).then(() => {
            document.dispatchEvent(new CustomEvent('session-year-synced', { detail: { value } }));
        });
    });


});

function syncSessionYear(value) {
    return fetch('/set-session-year', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify({
            session_year_id: value
        })
    });
}

function getCsrfToken() {
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    return tokenMeta ? tokenMeta.getAttribute('content') : '';
}

$('#filter_session_year_global').on('change', function () {
    const value = $(this).val() || 1;

    sessionStorage.setItem('session_year_id_global', value);
    syncSessionYear(value).then(() => {
        if (window.location.pathname === "/home") {
            reloadHomeDashboard(value);
        }
    });
});

function reloadHomeDashboard(sessionYearId) {
    $.ajax({
        url: '/home/data',
        method: 'GET',
        data: {
            session_year_id: sessionYearId
        },
        beforeSend: function () {
            $('.content-wrapper').addClass('opacity-50');
        },
        success: function (response) {
            renderHomeDashboard(response);
        },
        complete: function () {
            $('.content-wrapper').removeClass('opacity-50');
        },
        error: function () {
            showErrorToast('Failed to reload dashboard data');
        }
    });
}

function renderHomeDashboard(data) {
    updateCounts(data.counts);
    updateClassSections(data.class_sections);
    updateLeaves(data.leaves);
    updateAnnouncements(data.announcement);
    updateAttendance(data.attendance);
    updateGenderChart(data.boys, data.girls);
    updateRankers(data.rankers);
}

function updateCounts(counts) {
    if (!counts) return;

    $('#teacher-count').text(counts.teacher ?? 0);
    $('#student-count').text(counts.student ?? 0);
    $('#parent-count').text(counts.parent ?? 0);
}

let attendanceChartInstance = null;
function updateAttendance(attendance) {
    if (!attendance || attendance.length === 0) return;

    if (attendanceChartInstance) {
        attendanceChartInstance.destroy();
        attendanceChartInstance = null;
    }

    const canvas = resetCanvas('myChart');
    if (!canvas) return;

    const labels = [];
    const values = [];

    attendance.forEach(a => {
        labels.push(
            `${a.class_section.class.name} - ${a.class_section.section.name} ` +
            `${a.class_section.class.medium.name} ` +
            `${a.class_section.class.streams ? a.class_section.class.streams.name : ''}`
        );

        values.push(((a.total_present / a.total_attendance) * 100).toFixed(2));
    });

    attendanceChartInstance = new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: [
                    '#fe7096',
                    '#047edf',
                    '#84d9d2',
                    '#da8cff',
                    '#f6e384'
                ],
                borderColor: 'transparent'
            }]
        },
        options: {
            responsive: true,
            legend: { display: false },
            tooltips: { mode: 'index', intersect: false },
            scales: {
                yAxes: [{
                    ticks: { beginAtZero: true, max: 100 },
                    scaleLabel: {
                        display: true,
                        labelString: 'Percentage (%)'
                    },
                    gridLines: { display: false }
                }],
                xAxes: [{
                    categoryPercentage: 1.0,
                    barPercentage: 0.3,
                    gridLines: { display: false }
                }]
            },
            title: {
                display: true,
                text: 'Attendance Percentage by Class Section',
                position: 'bottom'
            }
        }
    });
}

function updateLeaves(leaves) {
    const tbody = $('#leavesTable tbody');
    tbody.empty();

    if (!leaves || leaves.length === 0) {
        tbody.append(
            `<tr><td colspan="5" class="text-center">No data found</td></tr>`
        );
        return;
    }

    leaves.forEach((row, index) => {
        tbody.append(`
            <tr data-date="${row.date}">
                <td>${index + 1}</td>
                <td>
                    <img class="img-sm rounded-circle"
                         src="${row.leave?.user?.image || '/assets/images/default-avatar.png'}"
                         alt="profile"
                         onerror="onErrorImage(event)">
                </td>
                <td>
                    ${row.leave?.user?.first_name || ''} 
                    ${row.leave?.user?.last_name || ''}
                </td>
                <td><span class="badge badge-info">${row.type}</span></td>
                <td>
                    <span class="badge badge-success">
                        ${row.date_formatted ?? row.date}
                    </span>
                </td>
            </tr>
        `);
    });

    filterLeaves();
}

function updateRankers(rankers) {
    const tbody = $('.rankers-table tbody');

    // Rankers section not present (role-based UI)
    if (!tbody.length) return;

    tbody.empty();

    if (!rankers || rankers.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="4" class="text-center text-muted">
                    No rankers found
                </td>
            </tr>
        `);
        return;
    }

    rankers.forEach((row, index) => {
        tbody.append(`
            <tr>
                <td class="text-center">${index + 1}</td>
                <td class="text-wrap">
                    ${row.class_section.class.name} -
                    ${row.class_section.section.name}
                    ${row.class_section.class.medium.name}
                    ${row.class_section.class.streams ? row.class_section.class.streams.name : ''}
                </td>
                <td class="text-left">
                    ${row.student.user.full_name}
                </td>
                <td class="text-center">
                    ${row.max_percentage} %
                </td>
            </tr>
        `);
    });
}

function updateAnnouncements(list) {
    const tbody = $('.noticeboard tbody');
    tbody.empty();

    function formatDate(dateString) {
        const date = new Date(dateString);

        return date.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    }

    list.forEach((row, index) => {
        tbody.append(`
            <tr>
                <td>${index + 1}</td>
                <td>${row.title}</td>
                <td>${row.description}</td>
                <td></td>
                  <td style="text-align: right; white-space: nowrap;">
                    ${formatDate(row.created_at)}
                </td>
            </tr>
        `);
    });
}



let genderChartInstance = null;
function updateGenderChart(boys, girls) {
    if (boys == null && girls == null) return;

    if (genderChartInstance) {
        genderChartInstance.destroy();
        genderChartInstance = null;
    }

    const canvas = resetCanvas('gender-ratio-chart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    const blueGradient = ctx.createLinearGradient(0, 0, 0, 180);
    blueGradient.addColorStop(0, 'rgba(54, 215, 232, 1)');
    blueGradient.addColorStop(1, 'rgba(177, 148, 250, 1)');

    const redGradient = ctx.createLinearGradient(0, 0, 0, 50);
    redGradient.addColorStop(0, 'rgba(255, 191, 150, 1)');
    redGradient.addColorStop(1, 'rgba(254, 112, 150, 1)');

    genderChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Boys', 'Girls'],
            datasets: [{
                data: [boys, girls],
                backgroundColor: [blueGradient, redGradient],
                hoverBackgroundColor: [blueGradient, redGradient],
                legendColor: [
                    'linear-gradient(to right, rgba(54,215,232,1), rgba(177,148,250,1))',
                    'linear-gradient(to right, rgba(255,191,150,1), rgba(254,112,150,1))'
                ]
            }]
        },
        options: {
            responsive: true,
            animation: {
                animateScale: true,
                animateRotate: true
            },
            legend: false,
            legendCallback: function (chart) {
                let text = ['<ul>'];
                for (let i = 0; i < chart.data.datasets[0].data.length; i++) {
                    text.push(`
                        <li>
                            <span class="legend-dots"
                                  style="background:${chart.data.datasets[0].legendColor[i]}">
                            </span>
                            ${chart.data.labels[i]}
                            <span class="float-right">
                                ${chart.data.datasets[0].data[i]}%
                            </span>
                        </li>
                    `);
                }
                text.push('</ul>');
                return text.join('');
            }
        }
    });

    $('#gender-ratio-chart-legend')
        .html(genderChartInstance.generateLegend());
}

function updateClassSections(classSections) {
    const container = $('#class-sections-container');

    // If section does not exist (role without classes)
    if (!container.length) return;

    container.empty();

    if (!classSections || classSections.length === 0) {
        container.append(
            `<div class="col-12 text-center text-muted">No classes found</div>`
        );
        return;
    }

    const colors = [
        'bg-gradient-danger',
        'bg-gradient-success',
        'bg-gradient-primary',
        'bg-gradient-info',
        'bg-gradient-secondary',
        'bg-gradient-warning'
    ];

    classSections.forEach((cs, index) => {
        const color = colors[index % colors.length];

        container.append(`
            <div class="col-md-2 stretch-card grid-margin">
                <div class="card ${color} card-img-holder text-white">
                    <div class="card-body">
                        <img src="${window.circleSvg}"
                             class="card-img-absolute"
                             alt="circle-image" />
                        <h6 class="mb-2">
                            <h4>
                                ${cs.class.name}-${cs.section.name}
                                ${cs.class.medium.name}
                                ${cs.class.streams ? cs.class.streams.name : ''}
                            </h4>
                        </h6>
                    </div>
                </div>
            </div>
        `);
    });
}

function resetCanvas(canvasId) {
    const oldCanvas = document.getElementById(canvasId);
    if (!oldCanvas) return null;

    const parent = oldCanvas.parentNode;
    const newCanvas = document.createElement('canvas');
    newCanvas.id = canvasId;

    parent.replaceChild(newCanvas, oldCanvas);
    return newCanvas;
}

//? //////////////////////
//? SESSION YEAR CREATE
//? //////////////////////
$(document).ready(function () {
    let installmentCounter = 1;

    // Get the datepicker's internally selected Date object (null if not yet picked)
    function getPickedDate(selector) {
        if (!$(selector).data('datepicker')) return null;
        return $(selector).datepicker('getDate') || null;
    }

    // Return midnight of today
    function today() {
        var d = new Date();
        d.setHours(0, 0, 0, 0);
        return d;
    }

    // Return midnight of tomorrow
    function tomorrow() {
        var d = today();
        d.setDate(d.getDate() + 1);
        return d;
    }

    // Return the later of two Date objects (either may be null)
    function maxDate(a, b) {
        if (!a) return b;
        if (!b) return a;
        return a > b ? a : b;
    }

    // Initialize or update a datepicker with startDate and endDate bounds
    function applyBounds($el, sDate, eDate) {
        if (!$el.hasClass('hasDatepicker')) {
            $el.datepicker({
                format: 'dd-mm-yyyy',
                autoclose: true,
                todayHighlight: true,
                startDate: sDate,
                endDate: eDate,
            });
        } else {
            $el.datepicker('setStartDate', sDate);
            $el.datepicker('setEndDate', eDate);
        }
    }

    // Initialize start/end date pickers (no bounds on these two)
    ['#startDate', '#endDate'].forEach(function (sel) {
        if (!$(sel).hasClass('hasDatepicker')) {
            $(sel).datepicker({ format: 'dd-mm-yyyy', autoclose: true, todayHighlight: true });
        }
    });

    // Apply all constraints for the dependent fields
    function initializeDatepickers() {
        var sDate = getPickedDate('#startDate');   // null until user picks
        var eDate = getPickedDate('#endDate');     // null until user picks
        var todayDate = today();
        var tomorrowDate = tomorrow();

        // Fees Due Date:
        //   min = max(today, start_date)  — cannot be in the past nor before session start
        //   max = end_date
        var feesMin = maxDate(todayDate, sDate);
        applyBounds($('#feesDueDate'), feesMin, eDate);

        // Free App Use Date:
        //   min = tomorrow (strictly after today)
        //   max = end_date (cannot exceed session end)
        applyBounds($('#freeAppUseDays'), tomorrowDate, eDate);

        // Installment Due Dates:
        //   min = start_date
        //   max = end_date
        $('#installmentList').find('input[name*="[due_date]"]').each(function () {
            applyBounds($(this), sDate, eDate);
        });
    }

    // Run once on page load (sDate/eDate will be null — that's fine, today constraint still applies)
    initializeDatepickers();

    // Re-apply all bounds whenever start or end date is picked/changed
    $('#startDate, #endDate').on('changeDate', function () {
        initializeDatepickers();
    });

    // Class Subject depends on Semester — auto-check Semester when Class Subject is checked
    $(document).on('change', 'input[name="transfer_class_subject"]', function () {
        if ($(this).is(':checked') && !$('#transferSemesterCheckbox').is(':checked')) {
            $('#transferSemesterCheckbox').prop('checked', true).trigger('change');
        }
    });

    // If Semester is unchecked, also uncheck Class Subject (and its dependents cascade via trigger)
    $('#transferSemesterCheckbox').on('change.dependency', function () {
        if (!$(this).is(':checked') && $('input[name="transfer_class_subject"]').is(':checked')) {
            $('input[name="transfer_class_subject"]').prop('checked', false).trigger('change');
        }
    });

    // Re-fetch semesters when source session year changes (if semester checkbox is checked)
    $(document).on('change', '#sourceSessionYear', function () {
        const sessionYearId = $(this).val();
        if ($('#transferSemesterCheckbox').is(':checked')) {
            if (sessionYearId) {
                fetchAndRenderSemesters(sessionYearId);
            } else {
                $('#semesterTransferList').empty();
                // $('#semesterEmptyMsg').text('Please select a session year above first.').show();
            }
        }
    });

    // Toggle installment content
    $('#enableFeesInstallment').on('change', function () {
        if ($(this).is(':checked')) {
            $('#installmentContent').slideDown(300);
            // Add required attribute to installment fields
            $('#installmentContent').find('input[type="text"], input[type="number"]').each(function () {
                if ($(this).attr('name') && $(this).attr('name').includes('installment_data')) {
                    $(this).prop('required', true);
                }
            });
        } else {
            $('#installmentContent').slideUp(300);
            // Remove required attribute from installment fields
            $('#installmentContent').find('input[type="text"], input[type="number"]').each(function () {
                if ($(this).attr('name') && $(this).attr('name').includes('installment_data')) {
                    $(this).prop('required', false);
                }
            });
        }
    });

    // Add new installment
    $('#addInstallmentBtn').on('click', function () {
        installmentCounter++;

        const isInstallmentEnabled = $('#enableFeesInstallment').is(':checked');
        const requiredAttr = isInstallmentEnabled ? 'required' : '';

        const installmentHTML = `
            <div class="installment-item" data-index="${installmentCounter}">
                <div class="installment-header">
                    <span>Installment ${installmentCounter}</span>
                    <button type="button" class="btn-remove-installment" data-index="${installmentCounter}">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="form-group">
                    <label>Installment Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="installment_data[${installmentCounter}][name]" placeholder="e.g., First Installment" ${requiredAttr}>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Due Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control datepicker-popup" name="installment_data[${installmentCounter}][due_date]" ${requiredAttr}>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Due Charges (%) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="installment_data[${installmentCounter}][due_charges]" placeholder="e.g., 2" min="1" max="100" ${requiredAttr}>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#installmentList').append(installmentHTML);
        initializeDatepickers();
    });

    // Remove installment
    $(document).on('click', '.btn-remove-installment', function () {
        const index = $(this).data('index');
        const installmentItem = $(`.installment-item[data-index="${index}"]`);

        // Don't allow removing if it's the only installment
        if ($('.installment-item').length === 1) {
            alert('At least one installment is required when fees installment is enabled.');
            return;
        }

        installmentItem.fadeOut(300, function () {
            $(this).remove();
            // Renumber remaining installments
            renumberInstallments();
        });
    });

    // Renumber installments after removal
    function renumberInstallments() {
        $('.installment-item').each(function (index) {
            const newNumber = index + 1;
            $(this).find('.installment-header span').text(`Installment ${newNumber}`);
        });
    }

    // ---- Semester transfer: API-driven flow ----
    // When the semester checkbox is toggled, show/hide semester section and fetch if needed
    $('#transferSemesterCheckbox').on('change', function () {
        if ($(this).is(':checked')) {
            const sessionYearId = $('#sourceSessionYear').val();
            if (sessionYearId) {
                fetchAndRenderSemesters(sessionYearId);
            } else {
                $('#semesterTransferList').empty();
                $('#semesterEmptyMsg').hide();
                $('#semesterTransferContent').slideDown(300);
                // Show a hint that they need to select a session year first
                // $('#semesterEmptyMsg').text('Please select a session year above first.').show();
            }
        } else {
            $('#semesterTransferContent').slideUp(300);
            $('#semesterTransferList').empty();
            $('#semesterEmptyMsg').hide();
            // Remove required from any semester date fields
            $('#semesterTransferContent').find('input').prop('required', false);
        }
    });

    // Fetch semesters from API and render fixed rows (name readonly, dates editable)
    function fetchAndRenderSemesters(sessionYearId) {
        const $list = $('#semesterTransferList');
        const $empty = $('#semesterEmptyMsg');
        const $loading = $('#semesterLoadingMsg');

        $list.empty();
        $empty.hide();
        $loading.show();
        $('#semesterTransferContent').slideDown(300);

        $.ajax({
            url: '/session-year/' + sessionYearId + '/semesters',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                $loading.hide();

                if (!response.status || !response.data || response.data.length === 0) {
                    $empty.text('No semesters found for the selected session year.').show();
                    return;
                }

                $empty.hide();
                $.each(response.data, function (idx, semester) {
                    const num = idx + 1;
                    const semesterHTML = `
                        <div class="installment-item semester-transfer-item" data-semester-index="${num}">
                            <div class="form-group">
                                <label>Semester Name</label>
                                <input type="text" class="form-control" name="semester_data[${num}][name]"
                                    value="${$('<span>').text(semester.name).html()}" readonly
                                    style="background-color: #e9ecef; cursor: not-allowed;">
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Start Date <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control datepicker-popup"
                                            name="semester_data[${num}][start_date]"
                                            placeholder="Select start date" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>End Date <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control datepicker-popup"
                                            name="semester_data[${num}][end_date]"
                                            placeholder="Select end date" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $list.append(semesterHTML);
                });

                initializeDatepickers();
            },
            error: function () {
                $loading.hide();
                $empty.text('Failed to load semesters. Please try again.').show();
            }
        });
    }

    // Reset modal when closed
    $('#createSessionYearModal').on('hidden.bs.modal', function () {
        $('#createSessionYearForm')[0].reset();
        $('#installmentContent').hide();
        $('#enableFeesInstallment').prop('checked', false);

        // Remove all installments except the first one
        $('.installment-item').not(':first').remove();
        installmentCounter = 1;

        // Remove required from installment fields when modal is closed
        $('#installmentContent').find('input[type="text"], input[type="number"]').each(function () {
            if ($(this).attr('name') && $(this).attr('name').includes('installment_data')) {
                $(this).prop('required', false);
            }
        });

        // Reset source session year picker
        $('#sourceSessionYear').prop('required', false).val('');

        // Reset semester transfer
        $('#semesterTransferContent').hide();
        $('#transferSemesterCheckbox').prop('checked', false);
        $('#semesterTransferList').empty();
        $('#semesterEmptyMsg').hide();
        $('#semesterLoadingMsg').hide();
    });

    // Form submission
    $('#submitSessionYear').on('click', function (e) {
        e.preventDefault();

        const form = $('#createSessionYearForm')[0];

        // Remove required attribute from hidden installment fields before validation
        if (!$('#enableFeesInstallment').is(':checked')) {
            $('#installmentContent').find('input').prop('required', false);
        }

        // Remove required from semester fields if semester transfer is unchecked
        if (!$('#transferSemesterCheckbox').is(':checked')) {
            $('#semesterTransferContent').find('input').prop('required', false);
        }

        // Validate: if semester checkbox is checked, ensure session year is selected and semesters loaded
        if ($('#transferSemesterCheckbox').is(':checked') && $('.semester-transfer-item').length === 0) {
            if (!$('#sourceSessionYear').val()) {
                $('#sourceSessionYear')[0].setCustomValidity('Please select a source session year to load semesters.');
                form.reportValidity();
                $('#sourceSessionYear')[0].setCustomValidity('');
            } else {
                alert('No semesters found for the selected session year. Please uncheck the Semester option or select a different session year.');
            }
            return;
        }

        // Validate source session year when any transfer is checked
        const anyTransfer = $('.transfer-checkbox:checked').length > 0;
        if (anyTransfer && !$('#sourceSessionYear').val()) {
            $('#sourceSessionYear')[0].setCustomValidity('Please select a source session year.');
            form.reportValidity();
            $('#sourceSessionYear')[0].setCustomValidity('');
            return;
        } else {
            $('#sourceSessionYear')[0].setCustomValidity('');
        }

        // Validate form
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Prepare form data
        const formData = new FormData(form);

        // If installment is not enabled, set fees_installment to 0 and remove installment data
        if (!$('#enableFeesInstallment').is(':checked')) {
            formData.set('fees_installment', '0');
            // Remove all installment_data entries
            const keysToDelete = [];
            for (let key of formData.keys()) {
                if (key.startsWith('installment_data')) {
                    keysToDelete.push(key);
                }
            }
            keysToDelete.forEach(key => formData.delete(key));
        } else {
            // Set fees_installment value to 1 when enabled
            formData.set('fees_installment', '1');
        }

        // Submit via AJAX
        $.ajax({
            url: $('#createSessionYearForm').attr('action') || '/session-years',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $('#submitSessionYear').prop('disabled', true).text('Creating...');
            },
            success: function (response) {
                // Show success message
                if (typeof showSuccessToast === 'function') {
                    showSuccessToast('Session year created successfully!');
                } else {
                    alert('Session year created successfully!');
                }

                // Close modal
                $('#createSessionYearModal').modal('hide');

                // Reload table if it exists
                if (typeof $('#table_list').bootstrapTable !== 'undefined') {
                    $('#table_list').bootstrapTable('refresh');
                }
            },
            error: function (xhr) {
                let errorMessage = 'An error occurred while creating session year.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
                }

                if (typeof showErrorToast === 'function') {
                    showErrorToast(errorMessage);
                } else {
                    alert(errorMessage);
                }
            },
            complete: function () {
                $('#submitSessionYear').prop('disabled', false).text('Create Session Year');
            }
        });
    });
});


//? DELETE SESSION YEAR DATA
$(document).ready(function () {
    let currentSessionId = null;
    let currentSessionYear = '';
    let relatedData = [];

    // Open modal when delete button is clicked
    $(document).on('click', '.delete-clear-btn', function (e) {
        e.preventDefault();

        currentSessionId = $(this).data('id');
        relatedData = $(this).data('related');

        // Extract session year from the row (adjust selector based on your table structure)
        const row = $(this).closest('tr');
        currentSessionYear = row.find('td:eq(1)').text().trim(); // Adjust index based on your table

        // Reset modal to step 1
        resetModal();

        // Set session year in all instances
        $('.session-year-value').text(currentSessionYear);

        // Show modal
        $('#deleteSessionModal').modal('show');
    });

    // Continue button click
    $('#continueBtn').on('click', function () {
        const selectedOption = $('input[name="delete_option"]:checked').val();

        if (selectedOption === 'delete_entire') {
            // Show delete entire session confirmation
            showStep(2);
            populateRelatedData();
        } else if (selectedOption === 'clear_specific') {
            // Show clear specific data options
            showStep(3);
        }
    });

    // Delete Session button click
    $('#deleteSessionBtn').on('click', function () {
        const button = $(this);
        button.prop('disabled', true);
        button.html('<i class="fa fa-spinner fa-spin"></i> Deleting...');

        $.ajax({
            url: baseUrl + '/session-years/' + currentSessionId,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
            },
            success: function (response) {
                $('#deleteSessionModal').modal('hide');
                showSuccessToast('Session year deleted successfully');
                $('#table_list').bootstrapTable('refresh');
            },
            error: function (xhr) {
                var message = 'Failed to delete session year';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                $('#deleteSessionModal').modal('hide');
                showErrorToast(message);
            }
        });
    });

    // Clear Data button click
    $('#clearDataBtn').on('click', function () {
        const button = $(this);
        const selectedData = [];

        $('input[name="clear_data[]"]:checked').each(function () {
            selectedData.push($(this).val());
        });

        if (selectedData.length === 0) {
            showErrorToast('Please select at least one data type to clear');
            return;
        }

        button.prop('disabled', true);
        const count = selectedData.length;
        button.html(`<i class="fa fa-spinner fa-spin"></i> Clearing...`);

        $.ajax({
            url: baseUrl + '/session-years/' + currentSessionId + '/clear-specific',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                clear_data: selectedData
            },
            success: function (response) {
                $('#deleteSessionModal').modal('hide');
                showSuccessToast('Selected data cleared successfully');
                $('#table_list').bootstrapTable('refresh');
            },
            error: function (xhr) {
                var message = 'Failed to clear data';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                $('#deleteSessionModal').modal('hide');
                showErrorToast(message);
            }
        });
    });

    // Select All functionality
    $('#selectAllBtn').on('click', function () {
        const allChecked = $('input[name="clear_data[]"]:checked').length === $('input[name="clear_data[]"]').length;

        if (allChecked) {
            $('input[name="clear_data[]"]').prop('checked', false);
            $(this).text('Select All');
        } else {
            $('input[name="clear_data[]"]').prop('checked', true);
            $(this).text('Deselect All');
        }

        updateClearButtonState();
    });

    // Update clear button state when checkboxes change
    $(document).on('change', 'input[name="clear_data[]"]', function () {
        updateClearButtonState();

        // Update Select All button text
        const allChecked = $('input[name="clear_data[]"]:checked').length === $('input[name="clear_data[]"]').length;
        $('#selectAllBtn').text(allChecked ? 'Deselect All' : 'Select All');
    });

    // Reset modal when hidden
    $('#deleteSessionModal').on('hidden.bs.modal', function () {
        resetModal();
    });

    // Helper Functions
    function showStep(stepNumber) {
        $('.modal-step').hide();
        $(`#step-${stepNumber}`).show();
    }

    function resetModal() {
        showStep(1);
        $('input[name="delete_option"]').first().prop('checked', true);
        $('input[name="clear_data[]"]').prop('checked', false);
        $('#selectAllBtn').text('Select All');
        // $('#clearDataBtn').prop('disabled', true).html('<i class="fa fa-trash"></i> Clear (0) Data');
        $('#deleteSessionBtn').prop('disabled', false).html('<i class="fa fa-trash"></i> Delete Session');
        $('.choice-option').removeClass('active');
    }

    function populateRelatedData() {
        const list = $('#relatedDataList');
        list.empty();

        if (relatedData && relatedData.length > 0) {
            relatedData.forEach(function (item) {
                list.append(`<li>${item}</li>`);
            });
        } else {
            // Default items
            const defaultItems = [
                'All class subjects and assignments',
                'Teacher assignments and records',
                'Student attendance and leave records',
                'All fees transactions and details',
                'Exams, grades, and results',
                'Timetables, events, and announcements'
            ];

            defaultItems.forEach(function (item) {
                list.append(`<li>${item}</li>`);
            });
        }
    }

    function updateClearButtonState() {
        const checkedCount = $('input[name="clear_data[]"]:checked').length;
        const button = $('#clearDataBtn');

        if (checkedCount > 0) {
            button.prop('disabled', false);
            // button.find('#selectedCount').text(checkedCount);
        } else {
            button.prop('disabled', true);
            // button.find('#selectedCount').text(0);
        }
    }
});

$(document).ready(function () {

    const classSubject = $('input[name="transfer_class_subject"]');
    const classTeacherSubject = $('input[name="transfer_class_teacher_subject"]');
    const classTimetable = $('input[name="transfer_class_timetable"]');

    // When dependent checkboxes are checked
    classTeacherSubject.add(classTimetable).on('change', function () {
        if ($(this).is(':checked') && !classSubject.is(':checked')) {
            classSubject.prop('checked', true).trigger('change');
        }
    });

    // When Class Subject is unchecked
    classSubject.on('change', function () {
        if (!$(this).is(':checked')) {
            classTeacherSubject.prop('checked', false);
            classTimetable.prop('checked', false);
        }
    });

});

function toggleDependentCheckboxes() {
    const isChecked = classSubject.is(':checked');

    classTeacherSubject.prop('disabled', !isChecked);
    classTimetable.prop('disabled', !isChecked);
}

// Initial state
toggleDependentCheckboxes();

// On change
classSubject.on('change', toggleDependentCheckboxes);
