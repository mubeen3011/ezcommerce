/**
 * Created by user_PC on 9/25/2019.
 */
$(".select2").select2();
$(".complex-colorpicker").asColorPicker({
    mode: 'complex'
});
$('.out-of-stock').change(function () {
    oos = $('.out-of-stock').val();
    $('.ooss_greater').val(oos);
    $('.ooss_less').val(parseInt(oos)+10);
    $('.in_stock').val(parseInt(oos)+10);
});
$('.ooss_less').change(function () {
    oos = $('.out-of-stock').val();
    ooss_less=$('.ooss_less').val();
    if ( ooss_less < oos ){
        $('.ooss_less').val(oos);
    }else{
        $('.in_stock').val(ooss_less);
    }
});
//$('#zip-table').DataTable();
$('.show_items').click(function () {
    var stateName= $(this).attr('data-state-name');
    var hasClass = $(this).hasClass('fa-plus');

    if ( $('#cities-of-'+stateName).length > 0 ){

        if ( hasClass ){
            $(this).removeClass('fa-plus');
            $(this).addClass('fa-minus');
            $('#cities-of-'+stateName).show();
        }else{
            $(this).removeClass('fa-minus');
            $(this).addClass('fa-plus');
            $('#cities-of-'+stateName).hide();
        }


    }
    else if (hasClass && $('#cities-of-'+stateName).length == 0 ){
        $.ajax({
            async: false,
            type: "post",
            url: "/warehouse/get-state-zip-codes",
            data: {'state_id':stateName},
            dataType: "json",
            beforeSend: function () {

            },
            success: function (data) {
                $('.state-'+stateName+' td span').removeClass('fa-plus');
                $('.state-'+stateName+' td span').addClass('fa-minus');
                $('.state-'+stateName).after(data.content);
            },
        });
    }else{
        $('#cities-of-'+stateName).hide();
        $(this).removeClass('fa-minus');
        $(this).addClass('fa-plus');
    }

});
$(document).on('click','.select-all',function () {
    var state_id=$(this).attr('data-state-id');
    var checkBoxes = $('.state-zip-'+state_id);
    if (this.checked){
        checkBoxes.prop("checked", true);
    }else{
        checkBoxes.prop("checked", false);
    }
});

$(document).ready(function(){
    if(already_selected!="new_entry") // if updating warehouse
    {
        if ( $('.warehouse_type').val()=='amazon-fba' || $('.warehouse_type').val()=='lazada-fbl' ){
            create_config_html(config_values, 1);
        }else{
            create_config_html(config_values);
        }

    }
});

$('.warehouse_type').change(function(){
    let selected_option=$(this).val();
    let prepared_columns;
    if(already_selected=="new_entry") // if not update option
    {
        prepared_columns=map_field_values('new',config_fields[selected_option]);
    }
    else if(already_selected==selected_option) // show already filled default options
    {
        prepared_columns=config_values;
    }
    else
    {
        if ( selected_option=='amazon-fba' || selected_option=='lazada-fbl' ){
            selected_option = 'other';
        }
        prepared_columns=map_field_values('new',config_fields[selected_option]);
    }
    create_config_html(prepared_columns);
});

function map_field_values(type,options)
{
    let result={};
    if(type=="new")
    {
        options.forEach(function (item, index) {
            result[item]="";
        });
        return result;
    }
    return options;
}

function create_config_html(fields, hide = 0)
{
    console.log(fields);
    if ( hide == 1 ){
        var hideHtml = ' style="display:none" ';
        var hidecss = ';display:none';
    }else{
        var hideHtml = '';
        var hidecss = '';
    }
    if(fields && Object.keys(fields).length)
    {

        let html ='<h6 '+hideHtml+'>Configuration</h6>';
        html +='<div class="row" style="background: lightgray;padding:1%'+hidecss+'">';

        let col_start='<div class="col-4 form-group">';
        let col_end='</div>';
        $.each(fields, function(key, value) {
            //console.log(key, value);
            html +=col_start;
            html +='<label class="control-label" for="configuration['+ key +']">'+ key +'</label>';
            html +='<input required class="form-control" type="text" name="configuration['+ key +']" value="'+value+'">';
            html +=col_end;
        });
        html +='</div><br/>';
        $('.config_space').html(html);

    }
    else
    {
        empty_config_space()
    }
    return;
}

function empty_config_space()
{
    $('.config_space').html("");
}