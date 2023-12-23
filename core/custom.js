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
        if (element) {
            element.value = decodeURIComponent(p[1]);
        }
    }
});

// Helper functions for index
$(document).ready(function () {
    $("#select_all_checkboxes").click(function () {
        if ($(this).prop('checked')) {
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
        id = $(this).attr('id');
        if (id.includes("-null")) {
            // This is a null checkbox
            field = $(this).attr('name');
            if ($(this).prop('checked')) {
                $("#" + field).prop("disabled", true);
            } else {
                $("#" + field).prop("disabled", false);
            }
        } else {
            // This is a selection checkbox
            inputs = $(this).parent().parent().find('input[name], textarea, select[name]');
            if ($(this).prop('checked')) {
                inputs.prop("disabled", false);
                inputs.filter('input[type!=checkbox]').prop("required", true);
            } else {
                inputs.prop("disabled", true);
            }
        }
    });

    $('#Bulk_updates').find("input[name], textarea, select[name]").prop("disabled", true);

    $("#Bulk_updates button").click(function () {
        buttontext = $(this).html().replace(/\s\s+/g, ' ').trim().toLowerCase();
        console.log(buttontext);
        if (!confirm("Are you sure you want to " + buttontext + "?")) {
            event.preventDefault();
        }
    });
});

/** Count the number of checkboxes and insert this value in the submit buttons */
function count_checked_boxes() {
    nr_checked = $("tbody input[type=checkbox]:checked").length;
    $("button#bulkupdate-update-button").html("Update " + nr_checked + " records");
    $("button#bulkupdate-delete-button").html("Delete " + nr_checked + " records");
}