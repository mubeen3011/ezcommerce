/**
 * Created by user_PC on 12/17/2018.
 */
$(document).ready(function () {
    $('.start_date').datepicker({});
    $('.end_date').datepicker({});
    $('.select2').select2({placeholder: 'Select sku'});
})
function showBundlePopup(bundle_name) {
    $.ajax({
        type: "GET",
        url: "/bundles/bundle-detail",
        data:  'bundle_id='+bundle_name,
        dataType: "html",
        success: function(msg)
        {
            $('#bundleChild_popup').text('');
            $('#bundleChild_popup').html(msg);
        },
        beforeSend: function()
        {
            $('#bundleChild_popup').text('');
        }
    });
    $('#showBundlePopup').modal('toggle');

}
$('#product_type_bundle').change(function () {
    var product_type = $('#product_type_bundle').val();
    $.ajax({
        url: "/bundles/bundle-type",
        type: "GET",
        data: 'bundle_type='+product_type,
        dataType : 'html',
        beforeSend: function(data) {
        },
        success: function(data) {
            $('.related-products').text('');
            $('.related-products').html(data);
            $('.select2').select2();
        }
    });

})
$('.add-more-related-products').click(function (e) {
    //alert();
    var child_elements_count=($(".related-products > div").length);
    var child_skus=$('.child_foc_lists :selected').map(function(){ // ["city1","city2","choose io","foo"]
        return $(this).val();
    });

    var result = Object.keys(child_skus).map(function(key) {
        return [Number(key), child_skus[key]];
    });
    if ( child_elements_count == 5 ){
        alert('You cannot relate more than 5 child products');
    }else{


        var product_type = $('#product_type_bundle').val();
        $.ajax({
            url: "/bundles/bundle-type",
            type: "GET",
            data: 'bundle_type='+product_type+'&child_skus='+(result),
            dataType : 'html',
            beforeSend: function(data) {
            },
            success: function(data) {
                //$('.related-products').text('');
                $('.related-products').append(data);
                $('.select2').select2({placeholder: 'Select sku'});
            }
        });
        //$(".related").last().clone().appendTo(".related-products");

    }
    e.preventDefault();
});
$('.add-foc-bundle').click(function () {
    var status=1;
    var bundle_name= $('input[name="bundle_name"]').val();
    var start_date = $('input[name="start_date"]').val();
    var end_date = $('input[name="end_date"]').val();
    var bundle_price = $('input[name="bundle_price"]').val();
    var main_sku = $('#main_sku').val();

    if ( bundle_name=='' ){
        $('.form-control-feedback-bundle-name').remove();
        $('input[name="bundle_name"]').after('<small class="form-control-feedback-bundle-name error-color">Bundle name cannot be empty.</small>');
        status=0;
    }else{
        $('.form-control-feedback-bundle-name').remove();
    }
    if ( main_sku=='' ){
        $('.form-control-feedback-main-sku').remove();
        $('#bundle-price-label').before('<small class="form-control-feedback-main-sku error-color">Main Sku cannot be empty.</small><br />');
        status=0;
    }else{
        $('.form-control-feedback-main-sku').remove();
    }
    if ( bundle_price=='' ){
        $('.form-control-feedback-bundle-price').remove();
        $('input[name="bundle_price"]').after('<small class="form-control-feedback-bundle-price error-color">Bundle price cannot be empty.</small>');
        status=0;
    }else if ( bundle_price < 1 ){
        $('.form-control-feedback-bundle-price').remove();
        $('input[name="bundle_price"]').after('<small class="form-control-feedback-bundle-price error-color">Bundle price cannot be less than 1.</small>');
        status=0;
    }else if ( bundle_price > 5000 ){
        $('.form-control-feedback-bundle-price').remove();
        $('input[name="bundle_price"]').after('<small class="form-control-feedback-bundle-price error-color">Bundle price cannot be greater than 5000.</small>');
        status=0;
    }else{
        $('.form-control-feedback-bundle-price').remove();
    }
    if (start_date==''){
        $('.form-control-feedback-start_date').remove();
        $('input[name="start_date"]').after('<small class="form-control-feedback-start_date error-color">Start Date cannot be empty.</small>');
        status=0;
    }else{
        $('.form-control-feedback-start_date').remove();
    }
    if (end_date==''){
        $('.form-control-feedback-end_date').remove();
        $('input[name="end_date"]').after('<small class="form-control-feedback-end_date error-color">End Date cannot be empty.</small>');
        status=0;
    }else{
        $('.form-control-feedback-end_date').remove();
    }
    if ( status!=1 ){
        return false;
    }
    $.ajax({
        url: "/bundles/check-bundle-already-exist",
        type: "GET",
        data: 'bundle_name='+$('input[name="bundle_name"]').val(),
        dataType : 'text',
        beforeSend: function(data) {
        },
        success: function(data) {
            if (data==0){
                $('.form-control-feedback-bundle-name').remove();
                $('input[name="bundle_name"]').after('<small class="form-control-feedback-bundle-name error-color">Bundle name already exist. </small>');
                return false;
            }else{
                $('.form-control-feedback-bundle-name').remove();
            }
        }
    });

    var child_form = ($('#child_products_form').serialize());
    var parent_form = ($('#product_form').serialize());
    var complete_form = child_form+'&'+parent_form;

    $.ajax({
        url: "/bundles/add-bundle",
        type: "GET",
        data: complete_form,
        cache: false,
        processData: false,
        dataType : 'json',
        beforeSend: function(data) {
        },
        success: function(data) {
            console.log(data);
            if ( data.status == 1 ){
                $('#bundle-add-status').text('');
                $('#bundle-add-status').css('color','green');
                $('#bundle-add-status').text('Bundle Successully Added.');
            }else if( data.status == 0 ){
                $('#bundle-add-status').text('');
                $('#bundle-add-status').css('color','red');
                $('#bundle-add-status').text(data.Message);
            }
        }
    });

});
$(".related-products").on("click", "span.remove-me", function(){
    var field_count = $(".related-products > .related").length;
    if ( field_count > 1 ){
        $(this).parent().parent().parent().parent().remove();
    }

});
$('#main_sku').on('select2:select', function (e) {
    var data = e.params.data;
    var main_sku = data.id;
    var bundle_price=($('input[name="bundle_price"]').val());
    $.ajax({
        url: "/bundles/get-sku-cost",
        type: "GET",
        data: 'sku='+main_sku,
        dataType : 'json',
        beforeSend: function(data) {
        },
        success: function(data) {
            $('#main_sku_price').val(data.cost);
        }
    });

});
function changeBundleStatus(bundleId){
    var cnf = confirm('Are you sure you want to update the status of bundle?');
    if (cnf) {
        var element = this.event;
        $.ajax({
            type: "GET",
            url: "/bundles/update-bundle-status",
            data: 'bundle_id=' + bundleId,
            dataType: "html",
            success: function (msg) {
                if (msg == "updated") {
                    alert("Bundle status updated");
                    element.srcElement.text = (element.srcElement.text == "Activated") ? "DeActivated" : "Activated";
                } else if (msg == "notfound")
                    alert("No bundle found");
                else
                    alert(msg);//alert("Error while updating");
            },
            beforeSend: function () {
            }
        });
    }
}