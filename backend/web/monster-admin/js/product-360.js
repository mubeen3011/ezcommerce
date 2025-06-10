$(document).ready(function () {
    $('[data-toggle="tooltip"]').tooltip();


    $("input[name='p360[action]']").on("change", function () {

        var act = $(this).val();
        if (act == 'new') {
            $(".new-shops-selection").removeClass('hide');
            $(".update-product").addClass('hide');
            $(".select2").select2();

        } else {
            $(".update-product").removeClass('hide');
            $(".new-shops-selection").addClass('hide');
            $(".select2").select2();
        }
    });

    // show category base on shop selection
    $(".chk-lazada").each(function () {
        if ($(this).click(function () {
            if ($(this).prop("checked") == true) {
                if ($('.chk-lazada').filter(':checked').length > 0) {
                    $(".lzd-cat").removeClass('hide');
                    $(".select2").select2();
                    if($("#lzd_category").val()!=""){
                        lzdAttrAjax($("#lzd_category").val(), "");
                    }
                }
            } else {
                if ($('.chk-lazada').filter(':checked').length <= 0)
                    $(".lzd-cat").addClass('hide');
            }
        })) ;
    });

    $(".chk-prestashop").each(function () {
        if ($(this).click(function () {
                if ($(this).prop("checked") == true) {
                    if ($('.chk-prestashop').filter(':checked').length > 0) {
                        $(".usbox-presta-cat").removeClass('hide');
                        $(".select2").select2();
                        if($("#presta_category").val()!=""){
                            PrestaAttrAjax($("#presta_category").val(), "");
                        }
                    }
                } else {
                    if ($('.chk-prestashop').filter(':checked').length <= 0)
                        $(".usbox-presta-cat").addClass('hide');
                }
            })) ;
    });


    $(".chk-shopee").each(function () {
        if ($(this).click(function () {
            if ($(this).prop("checked") == true) {
                if ($('.chk-shopee').filter(':checked').length > 0) {
                    $(".shopee-cat").removeClass('hide');
                    $(".select2").select2();
                }
            } else {
                if ($('.chk-shopee').filter(':checked').length <= 0)
                    $(".shopee-cat").addClass('hide');
            }
        })) ;
    });

    $(".chk-street").each(function () {
        if ($(this).click(function () {
            if ($(this).prop("checked") == true) {
                if ($('.chk-street').filter(':checked').length > 0) {
                    $(".street-cat").removeClass('hide');
                    $(".select2").select2();
                }
            } else {
                if ($('.chk-street').filter(':checked').length <= 0)
                    $(".street-cat").addClass('hide');
            }
        })) ;
    });


    if (isUpdate) {
        $(".new-shops-selection").removeClass('hide');
        $(".select2").select2();
        $(".update-product").addClass('hide');
        if ($('.chk-lazada').filter(':checked').length > 0) {
            $(".lzd-cat").removeClass('hide');
            $(".select2").select2();
            // load lazada attributes
            if($("#lzd_category").val()!=""){
                lzdAttrAjax($("#lzd_category").val(), "");
            }
        }
        if ($('.chk-shopee').filter(':checked').length > 0) {
            $(".shopee-cat").removeClass('hide');
            $(".select2").select2();
            // load shopee attributes
            shopeAttrAjax($("#shope_category").val(), shopattr);
        }
        if ($('.chk-street').filter(':checked').length > 0) {
            $(".street-cat").removeClass('hide');
            $(".select2").select2();
        }
        if ($('.chk-prestashop').filter(':checked').length > 0){
            $(".usbox-presta-cat").removeClass('hide');
            PrestaAttrAjax($("#presta_category").val(),);
            $(".select2").select2();
        }
    }
    //custom errros handlinhg
    $.validator.addMethod("alphanumeric_cus", function (value, element) {
        // allow any non-whitespace characters as the host part
        return this.optional(element) || /^[a-z\d\-_\s]+$/i.test(value);
    }, 'Please enter alphanumeric text.');

    $.validator.addMethod("less", function(value, element) {
    return parseInt($('#costPrice').val()) < parseInt($('#productPrice').val())
    }, "* Must be less than product price.");

    $.validator.addMethod("alphanumeric_special", function (value, element) {
        // allow any non-whitespace characters as the host part
        return this.optional(element) || /^[a-z\d\-()-/_\s]+$/i.test(value);
    }, 'Alphanumeric and only these special charachers ()/_- are allowed.');

    var validation_rules = {
        'p360[shop][]': "required",
        'p360[common_attributes][product_sku]': {
            required: true,
            minlength: 7,
            maxlength: 10,
            alphanumeric_special: true,
            remote: {
                url: "check-sku",
                type: "get",
                dataType: "text",
                data: {
                    product_sku: function() {
                        return $( "#product_sku" ).val();
                    }
                },
                dataFilter : function (response){
                    //alert(response);
                    //return false;
                    if (response!=""){
                        console.log( $(":checkbox[value=BLP-LZD]").prop("checked") );

                        $('#sku_server_error').val(response);
                    }else{
                        return true;
                    }
                }
            }
        },
        'p360[common_attributes][brand]': "required",
        'p360[lzd_category]': {
            required: ".chk-lazada:checked",
        },
        'p360[shopee_attributes][shpe_logistics]': {
            required: ".chk-shopee:checked",
        },
        'p360[shope_category]': {
            required: ".chk-shopee:checked",
        },
        'p360[street_category]': {
            required: ".chk-street:checked",
        },
        'p360[common_attributes][product_short_description]': {
            required: true,
            minlength: 20,
            maxlength: 3000,
        },
        'p360[common_attributes][product_color]': "required",
        'p360[common_attributes][special_from_date]': "required",
        'p360[common_attributes][special_to_date]': "required",
        'p360[common_attributes][product_name]': {
            required: true,
            minlength: 20,
            maxlength: 120,
            alphanumeric_special: true
        },
        'p360[common_attributes][product_price]': {
            required: true,
            number: true,
            min: 5,
            max: 2500
        }, 'p360[common_attributes][product_qty]': {
            required: true,
            number: true,
            min: 1
        },
        'p360[common_attributes][product_cprice]': {
            required: true,
            number: true,
            min: 5,
            max: 2500,
            less:true
        }, 'p360[package_height]': {
            required: true,
            number: true,
            min: 1
        },
        'p360[common_attributes][package_width]': {
            required: true,
            number: true,
            min: 1
        },
        'p360[common_attributes][package_length]': {
            required: true,
            number: true,
            min: 1
        },
        'p360[common_attributes][package_weight]': {
            required: true,
            number: true,
            min: 1
        }
    };
    var validation_messages = {
        'p360[shop][]': "Please select at least one shop",
        'p360[lzd_category]': "Please select category for lazada shops",
        'p360[shope_category]': "Please select category for shopee shops",
        'p360[street_category]': "Please select category for 11 street shops",
        'p360[common_attributes][product_name]': {
            required: "Please enter product name",
            alphanumeric_cus: "Please enter alphanumeric text."
        },
        'p360[common_attributes][product_sku]': {
            required: "Please enter product sku",
            alphanumeric_cus: "Please enter alphanumeric text.",
            remote: function () {
                return $("#sku_server_error").val()
            }
        },
        'p360[common_attributes][brand]': "Please select brand",
        'p360[common_attributes][product_short_description]': {
            required: "Please add description",
        },
        'p360[common_attributes][product_price]': "RCCP cannot be null",
        'p360[common_attributes][product_cprice]':{
            required:"Cost price cannot be null",
            less: 'Must be less than product price.'
        },
        'p360[common_attributes][product_qty]': "Quantity cannot be null",
        'p360[common_attributes][package_height]': "Please enter product height",
        'p360[common_attributes][package_length]': "Please enter product length",
        'p360[common_attributes][package_weight]': "Please enter product weight",
        'p360[common_attributes][package_width]': "Please enter product width",
        'p360[shopee_attributes][shpe_logistics]': "Please select logistics",
        'p360[shopee_attributes][special_from_date]': "From date is required",
        'p360[shopee_attributes][special_to_date]': "To date is required",
    };
    for ( var a = 0 ; a < 20 ; a++ ){
        validation_rules["p360[variations]["+a+"][price]"] = "required";
        validation_messages["p360[variations]["+a+"][price]"] = "Price cannot be null";
        validation_rules["p360[variations]["+a+"][rccp]"] = "required";
        validation_messages["p360[variations]["+a+"][rccp]"] = "Rccp cannot be null";
        validation_rules["p360[variations]["+a+"][stock]"] = "required";
        validation_messages["p360[variations]["+a+"][stock]"] = "Stock cannot be null";
        validation_rules["p360[variations]["+a+"][sku]"] = "required";
        validation_messages["p360[variations]["+a+"][sku]"] = "Sku cannot be null";
    }
    $("#pinfo").validate({
        rules: validation_rules,
        messages: validation_messages,
        errorPlacement: function (label, element) {
            if (element.attr("name") === "p360[shop][]") {
                element.parent().append(label); // this would append the label after all your checkboxes/labels (so the error-label will be the last element in <div class="controls"> )
            }else {
                label.insertAfter(element); // standard behaviour
            }
        },
        submitHandler: function (form) {
            if ( ($('.dz-success').length >= 3 && $('.dz-error').length==0 ) || $("#pid").val() != '') {
                if (isUpdate) {
                    cnf = confirm('Are you sure you want to update product?');
                } else {
                    var sku = $("#product_sku").val();
                    var shops = $("input[name='p360[shop][]']:checked").map(function () {
                        return this.value;
                    }).get().join(',');
                    cnf = confirm('Are you sure you want to create product with SKU-code: ' + sku + ' on the following shops ' + shops + ' ?');
                }
                if (cnf) {
                    form.submit();
                    return true;
                }else{
                    return false;
                }


            } else {
                var success_images = $('.dz-success').length;
                var error_images = $('.dz-error').length;
                var total_images = $('.dz-preview').length;

                if ( total_images == 0 ){
                    //alert("Atleast 3 Image required!!");
                    display_notice('failure','Atleast 3 images required');
                    return false;
                }else if ( success_images < 3 ) {
                    alert("You have uploaded "+success_images+" correct images, Please upload "+ (3 - success_images) + " more");
                    return false;
                }else if ( error_images > 0 ){
                    alert("You have "+error_images+" images with errors. Please remove it first. Then proceed");
                    return false;
                }

            }


        }
    });

    // load shops base on sku for update product
    $("#product").on('change', function () {
        var selectedSkuId = $(this).val();
        if (selectedSkuId != '') {
            //ajax call
            $.ajax({
                async: false,
                type: "post",
                url: "/product-360/get-product-shops-by-sku",
                data: {'pid': selectedSkuId},
                dataType: "json",
                beforeSend: function () {
                },
                success: function (data) {
                    $(".shops-list").html(data.msg);
                    // redirect to update page when radio checked
                    $('input[name="selected_shop"]').click(function () {
                        if ($(this).prop("checked") == true) {
                            var shopid = $(this).val();
                            var itemid = $(this).attr('data-item');
                            var pid = $(this).attr('data-pid');
                            window.location.replace("/product-360/manage?id=" + pid + "&shop=" + shopid + "&item=" + itemid);
                        }

                    });
                },
                error: function (data) {
                    //$(".modal-body").html("somthing wentwrong please try again later.");
                }
            });
        }
    });

    // delete image
    $(".delete").click(function () {
        var nearimage = $(this);
        var imgId = $(this).attr('data-img-id');
        var cnf = confirm('Are you sure you want to delete this image?');
        if (cnf) {
            //ajax delete image
            $.ajax({
                type: "POST",
                url: "/product-360/delete-img",
                data: {img: imgId, sid: $("#sid").val()},
                success: function (data) {
                    nearimage.closest('div').remove();
                }
            });

        }

    });

    //shopee category on change
    $("#shope_category").on('change', function () {
        var catid = $(this).val();
        shopeAttrAjax(catid);
    });
    //lazada category on change
    $("#lzd_category").on('change', function () {
        var catid = $(this).val();
        if(catid!="")
            lzdAttrAjax(catid);
    });
    //usboxing category on change
    $("#presta_category").on('change', function () {
        var catid = $(this).val();
        if(catid!="")
            PrestaAttrAjax(catid,$(this).attr('data-channel-id'));
    });

});

function shopeAttrAjax(catid, shopattr) {
    $.ajax({
        type: "GET",
        url: "/product-360/shopee-attributes",
        data: 'cat_id=' + catid + '&attr=' + shopattr,
        //dataType: "text",
        success: function (options) {
            $(".shopee-attr").html(options);
            $(".select2").select2();
        },
        beforeSend: function () {
            $(".shopee-attr").html('</br></br><h3 style="font-size: 13px">Loading Shopee Attributes ...</h3></br></br></br>');
        }
    });
}
function lzdAttrAjax(catid) {
    var lzdattr = GetParameterValues('shop');
    $.ajax({
        type: "GET",
        url: "/product-360/lzd-attributes",
        data: 'cat_id=' + catid + '&status_id=' + lzdattr,
        //dataType: "text",
        success: function (options) {
            $(".lzd-attr").html(options);
            $(".select2").select2();
            if ( typeof CKEDITOR !== 'undefined' ) {
                CKEDITOR.disableAutoInline = true;
                CKEDITOR.addCss( 'img {max-width:100%; height: auto;}' );
                var editor = CKEDITOR.replace( 'editor2', {
                    //extraPlugins: 'uploadimage,image2',
                    //removePlugins: 'image',
                    height:250,
                    //filebrowserUploadUrl: "/product-360/upload-image"
                } );
                var editor3 = CKEDITOR.replace( 'editor3', {
                    //extraPlugins: 'uploadimage,image2',
                    //removePlugins: 'image',
                    height:250,
                    //filebrowserUploadUrl: "/product-360/upload-image"
                } );
                // CKFinder.setupCKEditor( editor );
            } else {
                document.getElementById( 'editor2' ).innerHTML =
                    '<div class="tip-a tip-a-alert">This sample requires working Internet connection to load CKEditor 4 from CDN.</div>'
                document.getElementById( 'editor3' ).innerHTML =
                    '<div class="tip-a tip-a-alert">This sample requires working Internet connection to load CKEditor 4 from CDN.</div>'
            }
        },
        beforeSend: function () {
            $(".lzd-attr").html('</br></br><h3 style="font-size: 13px">Loading Lazada Attributes ...</h3></br></br></br>');
        }
    });
}
function PrestaAttrAjax(catid,channel_id) {
    var shopattr = GetParameterValues('shop');
    $.ajax({
        type: "GET",
        url: "/product-360/presta-attributes",
        data: 'cat_id=' + catid + '&status_id=' + shopattr + '&channel_id='+channel_id,
        //dataType: "text",
        success: function (options) {
            $('.presta-cat').removeClass('hide');
            $(".presta-attr").html(options);
            $(".select2").select2();
            if ( typeof CKEDITOR !== 'undefined' ) {
                CKEDITOR.disableAutoInline = true;
                CKEDITOR.addCss( 'img {max-width:100%; height: auto;}' );
                var editor3 = CKEDITOR.replace( 'editor3', {
                    height:250

                } );
            } else {
                document.getElementById( 'editor2' ).innerHTML =
                    '<div class="tip-a tip-a-alert">This sample requires working Internet connection to load CKEditor 4 from CDN.</div>';
            }
        },
        beforeSend: function () {
            $(".presta-attr").html('</br></br><h3 style="font-size: 13px">Loading Shopee Attributes ...</h3></br></br></br>');
        }
    });
}
function GetParameterValues(param) {
        var url = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
        for (var i = 0; i < url.length; i++) {
            var urlparam = url[i].split('=');
            if (urlparam[0] == param) {
                return urlparam[1];
            }
        }
}
if ($('.start_datetime')[0]) {
    $(".start_datetime").datetimepicker({
        format: 'yyyy-mm-dd hh:ii',
        autoclose: true,
        startDate: new Date(),
        todayBtn: true
    }).on('changeDate', function (selected) {
        var minDate = new Date(selected.date.valueOf());
        $('.end_datetime').datetimepicker('setStartDate', minDate);
    });
}
if ($('.end_datetime')[0]) {
    $(".end_datetime").datetimepicker({
        format: 'yyyy-mm-dd hh:ii',
        startDate: new Date(),
        autoclose: true,
        todayBtn: true,
        minDate: 0
    });
}
function error_info(){
    return 'Not good enough';
}

if ( typeof CKEDITOR !== 'undefined' ) {
    CKEDITOR.disableAutoInline = true;
    CKEDITOR.addCss( 'img {max-width:100%; height: auto;}' );
    var editor = CKEDITOR.replace( 'editor1', {
        //extraPlugins: 'imageuploader',
        //removePlugins: 'image',
        height:250
        //filebrowserUploadMethod: 'form',
        //filebrowserUploadUrl: "http://aoa-latest.local/product-360/upload-image"
    } );
   // CKFinder.setupCKEditor( editor );
} else {
    document.getElementById( 'editor1' ).innerHTML =
        '<div class="tip-a tip-a-alert">This sample requires working Internet connection to load CKEditor 4 from CDN.</div>'
}
$('.enable-variation').on("click",function () {
    $.ajax({
        type: "GET",
        url: "/product-360/get-shopee-sales-information-view",
        success: function (options) {
            $('.variation-section').append(options);
            $('.enable-variation').hide();
        },
        beforeSend: function () {
        }
    });
});


//alert(CKEDITOR.version);
