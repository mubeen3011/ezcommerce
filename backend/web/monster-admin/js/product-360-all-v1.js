/**
 * Created by user_PC on 2/8/2019.
 */

$(document).ready(function () {
    $(".select2").select2();
});

$('#filters').click(function () {
    var hasClass=$('.inputs-margin').hasClass("filters-visible");
    if( hasClass ){
        $('.inputs-margin').removeClass("filters-visible");
        $('th .select2-selection--multiple').css('display','block');
    }else{
        $('th .select2-selection--multiple').css('display','none');
        $('.inputs-margin').addClass("filters-visible");
    }

});


$('#submit-filters').click(function(){
    $.blockUI({
        message: $('#displayBox'),
        baseZ: 2000
    });
})



function viewshops(pid)
{
    //ajax call
    $.ajax({
        async: false,
        type: "post",
        url: "/product-sync/get-product-shops",
        data: {'pid': pid},
        dataType: "json",
        beforeSend: function () {
        },
        success: function (data) {
            $(".modal-body").html(data.msg);
            $(".select2").select2();
            $(".select2-selection").css('min-width', '100px');
        },
        error:function (data) {
            $(".modal-body").html("somthing wentwrong please try again later.");
        }
    });

    $('#MarketplacesModel').modal('show');
}
function deleteproduct(sid){
    //var nearimage = $(this);
    //console.log(nearimage.parent('tr'))
    var p3sId = sid;
    var cnf = confirm('Are you sure you want to delete this product?');
    if (cnf) {
        //ajax delete image
        $.ajax({
            type: "POST",
            url: "/product-sync/delete-product",
            data: {sid: p3sId},
            success: function (data) {
                if(data=="deleted"){
                    alert("Product Deleted Successfully");
                    $('#MarketplacesModel').modal('hide');
                }else{
                    alert(data);
                }
            }
        });

    }
}
function UpdatProductStatus(sid){
    var p3sId = sid;
    var cnf = confirm('Are you sure you want to activate/deactivate this product?');
    if (cnf) {
        //ajax delete image
        $.ajax({
            type: "POST",
            url: "/product-sync/update-product-status",
            data: {sid: p3sId},
            success: function (data) {
                alert(data);
                $('#MarketplacesModel').modal('hide');
            }
        });

    }
}
function CopyProduct(_this){
    //var x = abc;
    var shops = $(_this).closest("td").find('select').val();
    var s3id = $(_this).closest("td").find('input:hidden:first').val();

    //alert(s3id);
    //console.log(shops);
    if(shops==""){
        alert("Please select any shop to copy product");
    }else{
        //var p3id = "98";
        var cnf = confirm('Are you sure you want to copy this product?');
        if (cnf) {
            $.ajax({
                type: "POST",
                url: "/product-sync/copy-product-shops",
                data: {sid: s3id,shoplist: shops},
                success: function (data) {
                    alert(data);
                    console.log(data);
                    $('#MarketplacesModel').modal('hide');
                }
            });
        }


    }
}

