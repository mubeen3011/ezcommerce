/**
 * Created by user on 6/14/2018.
 */

function showfilters(){
    if($('.filters-thead').hasClass('filters-hide')==true){
        $('.filters-thead').removeClass('filters-hide');
    }else{
        $('.filters-thead').addClass('filters-hide');
    }
}