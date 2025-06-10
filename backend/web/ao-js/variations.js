Dropzone.autoDiscover = false;
$(function(){
    variations_count=parseInt(variations_count);
    if(variations_count)
    {
        generateDropZone();
    }

  //  var variations_count=total_variations;
    $('.select-variation-theme').change(function(){

        let selected_variation_theme=$(this).val();
        if(selected_variation_theme) {
            let variation_span = $('#variation-population-span');
            variations_count=1;
            make_variation_theme_inputs();
            make_variation_template();
            append_variation(true);

        } else
            {
                let variation_span=$('#variation-population-span');
                variation_span.html('');
                variations_count=0;
            }
       // variation_span.html(selected_variation_theme);

    });
    
    $(document).on('click','.add-new-variation',function () {

        //make_variation_template().insertAfter("div.variation-basic-template:last");
        //after($("div.variation-basic-template:last")).append(make_variation_template());
        variations_count=(variations_count+1);
        append_variation();


    });

    $(document).on('click','.delete-variation-btn',function () {
        $(this).parent().parent().remove();
      //  variations_count=(variations_count-1);
    });
    function append_variation(clear_first=false,append_new_var_btn=false)
    {
        let variation_span=$('#variation-population-span');
        if(clear_first)
            variation_span.html('');

        variation_span.append(make_variation_template());

        $('.add-new-variation').remove();
        variation_span.append(add_new_var_button()); // add new variation btn at the end
        generateDropZone();
        //alert(variations_count);
    }

    function add_new_var_button()
    {
       let  btn ='<div class="row m-2">';
        btn +=    '<div class="col-4 offset-lg-4">';
        btn +=      '<a href="javascript:" class="add-new-variation btn btn-sm btn-success"><span class="fa fa-plus"> Add New Variation</span></a>';
        btn +=    '</div>';
        btn +=     '</div>';
        return btn;
    }

    function make_variation_theme_inputs()
    {
        let input_fields_qty = $('option:selected', '.select-variation-theme').attr('input-fields-qty');
        var template="";
        for(let v=1;v<=parseInt(input_fields_qty);v++)
        {
            let input_type=$('option:selected', '.select-variation-theme').attr('data-input-type'+v);
            let input_name=$('option:selected', '.select-variation-theme').attr('data-option'+v);
            template +='<div class="col-3">';
                template +='<input placeholder="'+input_name+'" type="'+input_type+'" name="p360[variations]['+variations_count+']['+input_name+']"  class="form-control form-control-sm"/>';
            template += ' </div>';
        }
        return template;


    }

    function make_variation_template()
    {
        let template='<div class="variation-basic-template" style="border:2px dotted #F2F7F8; box-shadow:2px 2px 3px gray">';
                template +='<div class="row m-3" id="selected_variation_span" >';
                    template += '<div class="col-3"> Variation selected</div>';
                template += make_variation_theme_inputs();
                template +='  </div>';

                template +='<div class="row m-3">';
                 template +='<div class="col-3">Common </div>';
                template +='<div class="col-3">';
                    template +='<input placeholder="Price" step="0.01" type="number" name="p360[variations]['+variations_count+'][price]"  class="var_price form-control form-control-sm"/>';
                template +='</div>';
                template +=' <div class="col-3">';
                    template +='<input type="number" placeholder="Stock" name="p360[variations]['+variations_count+'][stock]"  class="form-control form-control-sm" />';
                template +='</div>';
                template +=' </div>';

                    template +=' <div class="row m-3">';
                    template +='    <div class="col-3"> IDS</div>';
                    template +='      <div class="col-3">';
                    template +='      <input type="text" name="p360[variations]['+variations_count+'][sku]" placeholder="SKU"  class="form-control form-control-sm" />';
                    template +='       </div>';
                    template +='       <div class="col-3">';
                    template +='       <input type="text" name="p360[variations]['+variations_count+'][product-id]" placeholder="EAN/UPC/GTIN/ASIN/ISBN"  class="form-control form-control-sm" />';
                    template +='       </div>';
                    template +='       <div class="col-3">';
                    template +='       <select name="p360[variations]['+variations_count+'][product-id-type]" class="form-control form-control-sm" >';
                   // template +='       <option value="">product id type</option>';
                    template +='    <option value="EAN">EAN</option>';
                   // template +='       <option value="UPC">UPC</option>';
                  //  template +='       <option value="ASIN">ASIN</option>';
                  //  template +='       <option value="ISBN">ISBN</option>';
                    template +='   </select>';
                    template +='</div>';
                    template +=' </div>';

                    template +='<div class="row m-3">';
                    template +='    <div class="col-3">Images </div>';
                    template +=' <div class="col-9">';
                    template +=' <div  id="image-'+variations_count+'-10-7" class="dropzone variation-dropzone"></div>';
                    template +=' </div>';
                    template +=' </div>';
                    template +='<div>';
                    template +='  <a data-toggle="tooltip" class="delete-variation-btn" title="Delete variation"><span class="fa fa-trash fa-2x" style="color:orange"></span> </a>';
                    template +='</div>';
                    template +=' </div>';

            template +=' </div>';
            return template;
    }

    function generateDropZone() {
       // Dropzone.autoDiscover = false;
        $('.variation-dropzone').each(function(){
            var options = $(this).attr('id').split('-');
            var dropUrl = 'upload-variation-images';
            var dropParamName = 'variation-' + options[1];
            var dropMaxFileSize = parseInt(options[3]);
            if (!$(this).hasClass('dz-clickable')){
                $(this).dropzone({

                    url: dropUrl,
                    params : {
                        '_csrf-backend' : $('input[name=_csrf-backend]').val(),
                        'uqid' : $('input[name=uqid]').val()
                    },
                    maxFiles: 8,
                    paramName: dropParamName,
                    maxFilesSize: dropMaxFileSize


                    // Rest of the configuration equal to all dropzones
                });
            }


        });

        ////
        $('.dropzonea').each(function(){
            var options = $(this).attr('id').split('-');
            var dropUrl = 'upload-variation-images';
            var dropParamName = 'variation-' + options[1];
            var dropMaxFileSize = parseInt(options[3]);
            if (!$(this).hasClass('dz-clickable')){
                $(this).dropzone({

                    url: dropUrl,
                    params : {
                        '_csrf-backend' : $('input[name=_csrf-backend]').val(),
                        'uqid' : $('input[name=uqid]').val()
                    },
                    maxFiles: 8,
                    paramName: dropParamName,
                    maxFilesSize: dropMaxFileSize


                    // Rest of the configuration equal to all dropzones
                });
            }


        });
        ///
    }
});