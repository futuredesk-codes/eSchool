"use strict";

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
function showErrorToast(message) {
    $.toast({
        text: message,
        showHideTransition: 'slide',
        icon: 'error',
        loaderBg: '#f2a654',
        position: 'top-right',
        hideAfter: 5000
    });
}

function showSuccessToast(message) {
    $.toast({
        text: message,
        showHideTransition: 'slide',
        icon: 'success',
        loaderBg: '#f96868',
        position: 'top-right',
        hideAfter: 5000
    });
}


function ajaxRequest(type, url, data, beforeSendCallback, successCallback, errorCallback, finalCallback, processData = false) {
    $.ajax({
        type: type,
        url: url,
        data: data,
        cache: false,
        processData: processData,
        contentType: false,
        dataType: 'json',
        beforeSend: function () {
            if (beforeSendCallback != null) {
                beforeSendCallback();
            }
        },
        success: function (data) {
            if (!data.error) {
                if (successCallback != null) {
                    successCallback(data);
                }
            } else {
                if (errorCallback != null) {
                    errorCallback(data);
                }
            }

            if (finalCallback != null) {
                finalCallback(data);
            }
        }, error: function (jqXHR, textStatus, errorThrown) {
            if (jqXHR.responseJSON) {
                showErrorToast(jqXHR.responseJSON.message);
            }
            if (finalCallback != null) {
                finalCallback();
            }
        }
    })
}
function formAjaxRequest(type, url, data, formElement, submitButtonElement, successCallback, errorCallback) {
    // To Remove Red Border from the Validation tag.
    formElement.find('.has-danger').removeClass("has-danger");
    formElement.validate();
    if (formElement.valid()) {
        let submitButtonText = submitButtonElement.val();

        function beforeSendCallback() {
            submitButtonElement.val('Please Wait...').attr('disabled', true);
        }

        function mainSuccessCallback(response) {
            showSuccessToast(response.message);
            if (successCallback != null) {
                successCallback(response);
            }
        }

        function mainErrorCallback(response) {
            showErrorToast(response.message);
            if (errorCallback != null) {
                errorCallback(response);
            }
        }

        function finalCallback(response) {
            submitButtonElement.val(submitButtonText).attr('disabled', false);
        }

        ajaxRequest(type, url, data, beforeSendCallback, mainSuccessCallback, mainErrorCallback, finalCallback)
    }
}
$('#create-form,.create-form').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');
    let data = new FormData(this);

    function successCallback() {
        formElement[0].reset();
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})
//File Upload Custom Component
$('.file-upload-browse').on('click', function () {
    var file = $(this).parent().parent().parent().find('.file-upload-default');
    file.trigger('click');
});

$('.file-upload-default').on('change', function () {

    $(this).parent().find('.form-control').val($(this).val().replace(/C:\\fakepath\\/i, ''));
});


// i want to "Parents Details" load

$(document).on('change', 'input[name="parent_guardian_type"]', function () {
    var selectedValue = $(this).val();

    $('#parents_div').hide();
    $('#parents_div input, #parents_div select').prop('disabled', true);

});




$(document).on('change', 'input[name="parent_guardian_type"]', function () {
    var selectedValue = $(this).val();
    if (selectedValue === 'Guardian') {
        $('#guardian_div').show();
        $('#guardian_div input, #guardian_div select').prop('disabled', false);
        $('#guardian_image').prop('disabled', true);
        $('#parents_div').hide();
        $('#parents_div input, #parents_div select').prop('disabled', true);
    } else if (selectedValue === 'Parent') {
        $('#parents_div').show();
        $('#parents_div input, #parents_div select').prop('disabled', false);
        $('#father_image').prop('disabled', true);
        $('#mother_image').prop('disabled', true);
        $('#guardian_div').hide();
        $('#guardian_div input, #guardian_div select').prop('disabled', true);
    }
});


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

if ($(".js-example-basic-single").length) {
    $(".js-example-basic-single").select2();
}

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

// Initialize radio button functionality on page load
$(document).ready(function () {
    $('input[name="parent_guardian_type"]:checked').trigger('change');
});
