$(document).ready(function () {
    $("#calculatorform-channel").on('change',function () {
        var cid = $(this).val();
        if(cid == '1')
            $("#is_lazada").show();
        else
            $("#is_lazada").hide();

    })
})
$(".select2").select2();