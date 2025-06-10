$( document ).trigger( "enhance.tablesaw" );
function updateTotal(channel_id) {
    var others=parseFloat($('.others_'+channel_id).val(),10);
    var total=parseFloat($('.total_'+channel_id).text(),10);
    var previous_total=parseFloat($('.previous_number_'+channel_id).val());
    var grand_total = others + total;
    console.log(others);
    console.log(previous_total);
    console.log(grand_total);
    $('.total_'+channel_id).text(grand_total-previous_total);
    $('.previous_number_'+channel_id).val(others)
    callAjax(channel_id,others);
   // console.log(total_increase);
}
function callAjax(channel_id,others) {
    $.ajax({
        type: "get",
        url: "/deals-maker/category-mapping-update-others",
        data: 'channel_id='+channel_id+'&others='+others,
        dataType: "json",
        beforeSend: function () {},
        success: function (data) {
            //console.log(data);
            if (data==1){
                $.toast({
                    heading: 'Update status',
                    text: 'Successfully Updated.',
                    position: 'top-right',
                    loaderBg:'#ff6849',
                    icon: 'success',
                    hideAfter: 3500,
                    stack: 6
                });
            }else if(0){
                $.toast({
                    heading: 'Update status',
                    text: 'Some thing went wrong when updating.',
                    position: 'top-right',
                    loaderBg:'#ff6849',
                    icon: 'error',
                    hideAfter: 3500
                });
            }
        },
    });
}

/*
 Template Name: Monster Admin
 Author: Themedesigner
 Email: niravjoshi87@gmail.com
 File: js
 */
$(function() {
    "use strict";
    $(".tst1").click(function(){
        $.toast({
            heading: 'Welcome to Monster admin',
            text: 'Use the predefined ones, or specify a custom position object.',
            position: 'top-right',
            loaderBg:'#ff6849',
            icon: 'info',
            hideAfter: 3000,
            stack: 6
        });

    });

    $(".tst2").click(function(){
        $.toast({
            heading: 'Welcome to Monster admin',
            text: 'Use the predefined ones, or specify a custom position object.',
            position: 'top-right',
            loaderBg:'#ff6849',
            icon: 'warning',
            hideAfter: 3500,
            stack: 6
        });

    });
    $(".tst3").click(function(){
        $.toast({
            heading: 'Welcome to Monster admin',
            text: 'Use the predefined ones, or specify a custom position object.',
            position: 'top-right',
            loaderBg:'#ff6849',
            icon: 'success',
            hideAfter: 3500,
            stack: 6
        });

    });

    $(".tst4").click(function(){
        $.toast({
            heading: 'Welcome to Monster admin',
            text: 'Use the predefined ones, or specify a custom position object.',
            position: 'top-right',
            loaderBg:'#ff6849',
            icon: 'error',
            hideAfter: 3500
        });

    });
});

