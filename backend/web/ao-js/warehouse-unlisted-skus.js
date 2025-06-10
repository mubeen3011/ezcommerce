/**
 * Created by user_PC on 12/26/2019.
 */
$('.warehouse-dd').change(function () {
    $('.warehouse-form').submit();
});
$('.warehouse-skus , .warehouse-shop').change(function () {
    $('.unlisted-skus-filters').submit();
});
$('.warehouse-sku-available').click(function () {

    var warehouseSkuAvailable = $(this).val();
    if ( warehouseSkuAvailable == '' ) {
        $(this).val('=1');
    }

});
$(document).ready(function () {
    $('.warehouse-skus').select2();

    $('#filters').click(function () {
        var hasClass=$('.inputs-margin').hasClass("filters-visible");
        if( hasClass ){
            $('.inputs-margin').removeClass("filters-visible");
            if ( $('.warehouse-skus').length ){
                $('.warehouse-skus').next(".select2-container").show();
            }

        }else{
            /*$('.select2-container--default').hide();
            $('.select2-selection--single').hide();*/
            $('.warehouse-skus').next(".select2-container").hide();
            $('.inputs-margin').addClass("filters-visible");
        }

    });
});
$( document ).trigger( "enhance.tablesaw" );

