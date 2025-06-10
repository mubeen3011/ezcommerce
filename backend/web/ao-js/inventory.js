/**
 * Created by user_PC on 8/27/2019.
 */

$('#ci-stock-filters').click(function () {

    var hasClass=$('.inputs-margin').hasClass("filters-visible");
    //alert(hasClass);
    if( hasClass ){
        $('.inputs-margin').removeClass("filters-visible");
        //$('th .select2-selection--single').css('display','block');
        $('.ci-sku-search').next(".select2-container").show();
    }else{
        //$('th .select2-selection--single').css('display','none');
        $('.ci-sku-search').next(".select2-container").hide();
        $('.inputs-margin').addClass("filters-visible");
    }

});

$('#wi-sl').click(function () {
    var hasClass=$('.inputs-margin').hasClass("filters-visible");
    if( hasClass ){
        $('.inputs-margin').removeClass("filters-visible");
        $('th .select2-selection--single').css('display','block');
    }else{
        $('th .select2-selection--single').css('display','none');
        $('.inputs-margin').addClass("filters-visible");
    }

});

$('#ci-price-filters').click(function () {
    var hasClass=$('.inputs-margin').hasClass("filters-visible");
    if( hasClass ){
        $('.inputs-margin').removeClass("filters-visible");
        $('th .select2-selection--single').css('display','block');
    }else{
        $('th .select2-selection--single').css('display','none');
        $('.inputs-margin').addClass("filters-visible");
    }

});
if ( $('.ci-sku-search').length ){
    $('.ci-sku-search').select2({ width: '100%' });
    $('.ci-sku-search').next(".select2-container").show();
}

if ( $('.ci-warehouse-search').length ){
    $('.ci-warehouse-search').select2({ width: '100%' });
    $('.ci-warehouse-search').next(".select2-container").hide();
}
$('#ci-search-sku').change(function () {
    if ($(this).val()!=''){
        $('#ci-search-form').submit();
    }
})
$('#ci-search-category').change(function () {
    if ($(this).val()!=''){
        $('#ci-search-form').submit();
    }
})
//ci-search-selling-status
$('#ci-search-selling-status').change(function () {
    if ($(this).val()!=''){
        $('#ci-search-form').submit();
    }
})
$('#export_csv_warehouses').select2({ width: '100%' });
$( document ).trigger( "enhance.tablesaw" );
