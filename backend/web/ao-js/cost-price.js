/**
 * Created by Admin on 9/24/2018.
 */
$(function() {
    $('.start_date').datepicker({});
    $('.end_date').datepicker({});
    // Initialize form validation on the registration form.
    // It has the name attribute "registration"
    $("#addSkuForm").validate({
        // Specify validation rules
        rules: {
            // The key name on the left side is the name attribute
            // of an input field. Validation rules are defined
            // on the right side
            sku_model: "required",
            n_c: {
                required: true,
                minlength: 7,
                number: true,
                maxlength: 12,
                remote: {
                    url: "/cost-price/n-c",
                    type: "GET",
                    data: {
                        t_n_c: function(){
                            return $('#n_c').val();
                        }
                    }
                }
                // Specify that email should be validated
                // by the built-in "email" rule
            },
            product_description: {
                required: true
                // Specify that email should be validated
                // by the built-in "email" rule
            },
            rcp: {
                required: true,
                min: 0
            },
            promo_price: {
                required: false
            },
            cost_price: {
                required: true,
                min: 0
            }, extra_cost: {
                required: false
            },
            sub_category: {
                required: true
            },
            margin: {
                required: true
            },
            subsidy: {
                required: true
            }
        },
        // Specify validation error messages
        messages: {
            sku_model: "Please enter sku model",
            n_c: {
                required: "Please enter the 12NC",
                remote : "12NC already exist"
            },
            product_description: {
                required: "Please provide product description"
            },
            rcp: {
                required : "Please enter RCP",
                number : true,
                min: "Value must be greater than 0"
            },
            cost_price: {
                required : "Please enter Cost Price",
                number : true,
                min: "Value must be greater than 0"
            },
            sub_category: "Please select sub category"
            /*margin: {
                required : "Please enter Margin",
                number : true
            },*/
            /*subsidy: {
                required : "Please enter Subsidy",
                number : true
            }*/
        },
        // Make sure the form is submitted to the destination defined
        // in the "action" attribute of the form when valid
        submitHandler: function(form) {
            /*form.submit();*/
            $.ajax({
                url: "/cost-price/add-new-sku",
                type: "GET",
                data: $('#addSkuForm').serialize(),
                cache: false,
                processData: false,
                beforeSend: function(data) {
                    $('#save_sku').attr('disabled',true);
                },
                success: function(data) {
                    $('#save_sku').attr('disabled',false);
                    if ( data==1 ){
                        $('#sku_add_status').text('');
                        $('#sku_add_status').append('<h3 style="color: green;"><b>Successfuly Added</b></h3>');
                        document.getElementById("addSkuForm").reset();

                    }else if( data == 0 ){
                        $('#sku_add_status').text('');
                        $('#sku_add_status').append('<h3 style="color: red;">Something went wrong</h3>');
                    }else if ( data=='Duplicate' ){
                        $('#sku_add_status').text('');
                        $('#sku_add_status').append('<h3 style="color: red;">Sku Already Exist</h3>');
                    }
                }
            });
            return false;
        }
    });
    $('.select2').select2({placeholder: 'Select sku'});
    $('.dropify').dropify();
    $("#sku-mapping").validate({
        // Specify validation rules
        rules: {
            // The key name on the left side is the name attribute
            // of an input field. Validation rules are defined
            // on the right side
            sku_model: "required"
        },
        // Specify validation error messages
        messages: {
            sku_model: "Please enter sku model"
        },
        // Make sure the form is submitted to the destination defined
        // in the "action" attribute of the form when valid
        submitHandler: function(form) {
            /*form.submit();*/
            $.ajax({
                url: "/cost-price/search-sku-for-mapping",
                type: "GET",
                data: $('#sku-mapping').serialize(),
                cache: false,
                processData: false,
                dataType : 'json',
                beforeSend: function(data) {
                    $('#mapping_update_status').text('');
                    $('#mapping_sku_search').attr('disabled',true);
                    $('#mapping_sku_child_parent').empty();
                },
                success: function(data) {
                    //console.log(data);
                    $('#mapping_sku_search').attr('disabled',false);
                    if (data.status==0){
                        $('#mapping_status').text('');
                        $('#mapping_status').append('<p style="color: orange;"><b>'+data.msg+'</b></p>');

                    }else{
                        $('#mapping_status').text('');
                        var table = '<thead><th>Sku</th><th>Parent</th><th>Child</th></thead><tbody>';
                        table += '<tbody>';
                        $.each(data, function(index, element) {
                            console.log(element);
                            var parent='';
                            var child='';
                            if ( element.parent_isis_sku=='0' ){
                                 parent='checked';
                            }else if( element.parent_isis_sku!='0' && element.parent_isis_sku!=null ){
                                 child = 'checked';
                            }
                            table += '<tr><td>'+element.isis_sku+'</td><td><input class="parent" type="checkbox" '+parent+' name="parent[]" value="'+element.isis_sku+'"/></td><td><input class="childs" '+child+' type="checkbox" name="child[]" value="'+element.isis_sku+'"/></td></tr>';
                        });
                        table += '</tbody>';
                        $('#mapping_sku_child_parent').text('');
                        $('#mapping_sku_child_parent').append(table);
                    }
                }
            });
            return false;
        }
    });
    $('#mapping_sku_update').click(function(){
        var inputs = $('#sku_mapping_form').serialize();
        $.ajax({
            url: "/cost-price/update-sku-mapping",
            type: "GET",
            data: inputs,
            cache: false,
            processData: false,
            dataType : 'json',
            beforeSend: function(data) {
                $('#mapping_sku_update').attr('disabled',true);
                $('#mapping_update_status').text('');
            },
            success: function(data) {
                //console.log(data);
                if (isEmpty(data)){
                    //mapping_update_status
                    $('#mapping_update_status').text('');
                    $('#mapping_update_status').append('<p style="color: green;"><b>Skus mapped Successfully</b></p>');
                }else{
                    $('#mapping_update_status').text('');
                    $('#mapping_update_status').append('<p style="color: red;"><b>There is some problem occuring</b></p>');
                }
                $('#mapping_sku_update').attr('disabled',false);
            }
        });
    });
    $("#updateSkuForm").validate({
        rules: {
            update_tnc: {
                required: true,
                minlength: 7,
                number: true,
                maxlength: 12,
                remote: {
                    url: "/cost-price/n-c",
                    type: "GET",
                    data: {
                        n_c: function(){
                            return $('#update_tnc').val();
                        },
                        currentsku: function(){
                            return $('#dropdownSkus').val();
                        },
                    }
                }
            },
            update_rcp: {
                required: true,
                min: 0
            },
            update_promo_price: {
                required: false
            },
            update_cost_price: {
                required: true,
                min: 0
            }, update_extra_cost: {
                required: false
            }
        },
        // Specify validation error messages
        messages: {
            update_tnc: {
                required: "Please enter the 12NC",
                remote : "12NC already exist"
            },
            update_rcp: {
                required : "Please enter RCP",
                number : true,
                min: "Value must be greater than 0"
            },
            update_cost_price: {
                required : "Please enter Cost Price",
                number : true,
                min: "Value must be greater than 0"
            }
        },
        submitHandler: function(form) {
            console.log($('#updateSkuForm').serialize());
            $.ajax({
                url: "/cost-price/update-sku",
                type: "GET",
                data: $('#updateSkuForm').serialize(),
                cache: false,
                processData: false,
                beforeSend: function(data) {
                    $('#update_sku').attr('disabled',true);
                },
                success: function(data) {
                    $('#update_sku').attr('disabled',false);
                    if ( data==1 ){
                        $('#sku_update_status').text('');
                        $('#sku_update_status').append('<h3 style="color: green;"><b>Successfuly Updated</b></h3>');
                        document.getElementById("addSkuForm").reset();

                    }else if( data == 0 ){
                        $('#sku_update_status').text('');
                        $('#sku_update_status').append('<h3 style="color: red;">Something went wrong</h3>');
                    }else if ( data=='NotFound' ){
                        $('#sku_update_status').text('');
                        $('#sku_update_status').append('<h3 style="color: red;">Sku Not Exist</h3>');
                    }
                }
            });
            return false;
        }
    });
});
function isEmpty(obj) {
    for(var key in obj) {
        if(obj.hasOwnProperty(key))
            return false;
    }
    return true;
}
$(document).ready(function () {
    //@naresh action dynamic childs
    var next = 0;
    $("#add-more").click(function(e){
        e.preventDefault();
        var addto = "#field" + next;
        var addRemove = "#field" + (next);
        next = next + 1;
        var newIn = ' <div id="field'+ next +'" name="field'+ next +'"><!-- Text input--><div class="form-group"> <label class="col-md-4 control-label" for="action_id">Action Id</label> <div class="col-md-5"> <input id="action_id" name="action_id" type="text" placeholder="" class="form-control input-md"> </div></div><br><br> <!-- Text input--><div class="form-group"> <label class="col-md-4 control-label" for="action_name">Action Name</label><!-- File Button --></div>';
        var newInput = $(newIn);
        var removeBtn = '<button id="remove' + (next - 1) + '" class="btn btn-danger remove-me" >Remove</button></div></div><div id="field">';
        var removeButton = $(removeBtn);
        $(addto).after(newInput);
        $(addRemove).after(removeButton);
        $("#field" + next).attr('data-source',$(addto).attr('data-source'));
        $("#count").val(next);
        $('.remove-me').click(function(e){
            e.preventDefault();
            var fieldNum = this.id.charAt(this.id.length-1);
            var fieldID = "#field" + fieldNum;
            $(this).remove();
            $(fieldID).remove();
        });
    });
    DropDownChangeEvent();
    //regenrate SKUS on change


});
$('#product_type').change(function () {
    DropDownChangeEvent();
});
$('#dropdownSkus').change(function () {
   GetSkuDetailBySKUName();
});
$('#btnManageSkus').click(function () {
    ClearFields();
});

function DropDownChangeEvent(){
    var product_type=( $('#product_type').val() );
    if ( product_type=='ORDERABLE' ){
        $('input[name="rcp"]').val('');
        $('input[name="promo_rcp"]').val('');
        $('input[name="cost_price"]').val('');
        $('input[name="promo_rcp"]').parent().parent().show();
        $('input[name="cost_price"]').parent().parent().show();
        $('input[name="rcp"]').parent().parent().show();
        $('input[name="n_c"]').parent().parent().show();
        $('input[name="n_c"]').attr('disabled',false);
        $("#sub_category").prop("disabled", false);
        $("#sub-category-div-6").show();
    }
    if ( product_type=='FOC' ){
        $('input[name="rcp"]').val('0');
        $('input[name="promo_rcp"]').val('0');
        $('input[name="cost_price"]').val('0');
        // hide the fields of cost
        $('input[name="promo_rcp"]').parent().parent().hide();
        $('input[name="cost_price"]').parent().parent().hide();
        $('input[name="rcp"]').parent().parent().hide();
        $('input[name="n_c"]').parent().parent().hide();
        $('input[name="n_c"]').attr('disabled',true);
        $("#sub_category").prop("disabled", true);
        $("#sub-category-div-6").hide();
    }
}

function GetSkuDetailBySKUName(){
    var skus=$('#dropdownSkus').val();
    $.ajax({
        url: "/cost-price/get-sku-data",
        type: "POST",
        data: {'skuname':skus},
        cache: false,
        dataType : 'json',
        success: function(data) {
            if(data=="NotFound"){
                alert("No Sku Found");
            }else {
                FillFormFields(data);
            }
        }
    });
}

function FillFormFields(data){
    $('#update_sku_model').val(data.sku);
    $('#update_tnc').val(data.tnc);
    $('#update_cost_price').val(data.cost_price);
    $('#update_extra_cost').val(data.extra_cost);
    $('#update_rcp').val(data.rccp);
    $('#update_promo_price').val(data.promo_price);
    if(data.is_orderable==1) {
        $("#update_product_type option[value='ORDERABLE']").attr("selected", "selected");
    }
    else {
        $("#update_product_type option[value='FOC']").attr("selected", "selected");
        $('#update_rcp').parent().parent().hide();
        $('#update_cost_price').parent().parent().hide();
        $('#update_tnc').parent().parent().hide();
    }
    $("#update_product_type").prop("disabled", true);
    $('#divFormElemets').show();
}

function ClearFields(){
    $('#update_sku_model').val("");
    $('#update_tnc').val("");
    $('#update_cost_price').val("");
    $('#update_extra_cost').val("");
    $('#update_rcp').val("");
    $('#update_promo_price').val("");
    $('#sku_add_status').text('');
    $('#sku_update_status').text('');
    $('input[name="rcp"]').val('');
    $('input[name="promo_price"]').val('');
    $('input[name="cost_price"]').val('');
    $('input[name="sku_model"]').val('');
    $('input[name="n_c"]').val('');
    $('input[name="product_description"]').val('');
    $('input[name="extra_cost"]').val('');
    $('input[name="sub_category"]').val('');

    //$("#linkUpdateSku").removeClass("active show");
    //$("#linkAddSku").addClass("active show");
    //$('#AddSku').modal('show');
}