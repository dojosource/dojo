jQuery(function($) {
    $(".dojo-logged-out #dojo-signup").submit(function(e) {
        e.preventDefault();
        var data = {};
        $(this).find("input").each(function() {
            var name = $(this).attr("name");
            var val = $(this).val();
            data[name] = val;
        });
        $(".dojo-error").hide();
        $(".dojo-submit-button").hide();
        $(".dojo-please-wait").show();
        $.post(dojo.ajax("membership", "signup"), data, function(response) {
            if (response != "success") {
                $(".dojo-error").html(response);
                $(".dojo-error").show();
                $(".dojo-please-wait").hide();
                $(".dojo-submit-button").show();
            } else {
                window.location.reload();
            }
        });
    });
});

jQuery(function($) {
    $("#dojo-login").submit(function(e) {
        e.preventDefault();
        var data = {};
        $(this).find("input").each(function() {
            var name = $(this).attr("name");
            var val = $(this).val();
            data[name] = val;
        });
        $(".dojo-error").hide();
        $(".dojo-submit-button").hide();
        $(".dojo-please-wait").show();
        $.post(dojo.ajax("membership", "login"), data, function(response) {
            if (response != "success") {
                $(".dojo-error").html(response);
                $(".dojo-error").show();
                $(".dojo-please-wait").hide();
                $(".dojo-submit-button").show();
            } else {
                window.location.reload();
            }
        });
    });
});

jQuery(function($) {
    $(".dojo-user-billing .dojo-save-billing").click(function() {
        var data = {};
        $(".dojo-billing-options input, .dojo-billing-options select").each(function() {
            if ($(this).attr("type") == "radio") {
                if ($(this).is(":checked")) {
                    data[$(this).attr("name")] = $(this).val();
                }
            } else {
                data[$(this).attr("name")] = $(this).val();
            }
        });
        $.post(dojo.ajax("membership", "save_billing_options"), data, function(response) {
            if (response == "success") {
                window.location = dojo.param("membership_url");
            } else {
                $(".dojo-billing-error .dojo-error").text(response);
                $(".dojo-billing-error").show();
            }
        });
    });
});

jQuery(function($) {
    $(".dojo-user-enroll-apply .submit-application").click(function() {
        var unchecked = $(".terms-checkbox").not(":checked");
        if (unchecked.length) {
            $(".error-message").text("Please indicate that you have read and agree with the terms and conditions for each membership that requires it.");
            $(".error-container").show();
        } else {
            $(".error-container").hide();
            $(".submit-application").hide();
            $(".dojo-please-wait").show();
            $("#post").submit();
        }
    });
});

jQuery(function($) {
    function updateCheckout(doPost) {
        var data = {};
        if (doPost) {
            $("#dojo-enroll select").each(function() {
                data[$(this).attr("name")] = $(this).val();
            });
        } else {
            data.refresh_only = true;
        }
        $.post(dojo.ajax("membership", "save_enrollment"), data, function(response) {
            var data = eval("(" + response + ")");
            dojoCheckout.setLineItems(data.line_items);
            if (0 == data.line_items.length) {
                $(".dojo-monthly-pricing").hide();
                $(".dojo-registration-fee").hide();
            } else {
                $(".dojo-monthly-pricing").show();
                $(".dojo-registration-fee").show();
            }
            var reg_fee = parseInt(data.reg_fee) / 100;
            $(".dojo-registration-amount").text("$" + reg_fee.toFixed(2));
        });
    }
    if ($(".dojo-user-enroll").length > 0) {
        $("#dojo-enroll select").change(function() {
            updateCheckout(true);
        });
        $(".dojo-membership-details").click(function() {
            window.location = dojo.param("contract_url") + $(this).attr("data-id");
        });
        updateCheckout(false);
    }
});

jQuery(function($) {
    $(".dojo-user-membership .dojo-add-student").click(function() {
        window.location = dojo.param("students_edit_url");
    });
    $(".dojo-user-membership .dojo-students .dojo-select-list-item").click(function() {
        var id = $(this).attr("data-id");
        window.location = dojo.param("students_edit_url") + "?student=" + id;
    });
    $(".dojo-user-membership .dojo-enroll").click(function() {
        window.location = dojo.param("enroll_url");
    });
});

jQuery(function($) {
    var today = new Date();
    $(".dojo-user-students-edit .dojo-membership-date").datepicker({
        changeMonth: true,
        changeYear: true,
        yearRange: "1930:" + today.getFullYear()
    });
    $(".dojo-user-students-edit input[name=first_name]").change(function() {
        $("input[name=alias]").val($(this).val());
    });
    $(".dojo-user-students-edit .dojo-cancel-contract button").click(function() {
        $(".dojo-cancel-contract").hide();
        $(".dojo-confirm-cancel").show();
    });
    $(".dojo-user-students-edit .dojo-confirm-cancel a").click(function() {
        var data = {
            membership_id: dojo.param("membership_id")
        };
        $.post(dojo.ajax("membership", "cancel_membership"), data, function(response) {
            if (response == "success") {
                window.location.reload();
            } else {
                $(".dojo-cancel-error").text(response);
                $(".dojo-cancel-error-container").show();
            }
        });
    });
});

jQuery(function($) {
    $(".dojo-user-students .dojo-add-student").click(function() {
        window.location = dojo.param("students_edit_url");
    });
    $(".dojo-user-students .dojo-select-list-item").click(function() {
        var id = $(this).attr("data-id");
        window.location = dojo.param("students_edit_url") + "?student=" + id;
    });
    $(".dojo-user-students .dojo-enroll").click(function() {
        window.location = dojo.param("enroll_url");
    });
});