// In this file we have some JS code that may be used by all pages

// Show tooltips
$(document).ready(function () {
    $('[data-toggle="tooltip"]').tooltip();
});

// Pre-fill values
$(document).ready(function () {
    var hashParams = window.location.hash.substring(1).split('&');
    for (var i = 0; i < hashParams.length; i++) {
        var p = hashParams[i].split('=');
        element = document.getElementById(p[0]);
        if(element){
            element.value = decodeURIComponent(p[1]);
        }
    }
});

// Helper functions for index
$(document).ready(function () {
    $("#select_all_checkboxes").click( function (){
        if ($(this).prop('checked')){
            $("tbody input[type=checkbox]").prop('checked', true);
        } else {
            $("tbody input[type=checkbox]").prop('checked', false);
        }
    });

    $('#advancedfilterform').submit(function () {
        $(this)
            .find('input[name], select[name]')
            .filter(function () {
                return !this.value;
            })
            .prop('name', '');
    });

    $('#Bulk_updates input[type=checkbox]').click(function () {
        if ($(this).prop('checked')){
            $(this).parent().parent().find('input[name], textarea, select[name]').prop("disabled", false);
            $(this).parent().parent().find('input[name], textarea, select[name]').prop("required", true);
        } else {
            $(this).parent().parent().find('input[name], textarea, select[name]').prop("disabled", true);
        } 
    });

    $('#Bulk_updates').find("input[name], textarea, select[name]").prop("disabled", true);
});
