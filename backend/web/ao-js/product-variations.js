/**
 * Created by user_PC on 7/11/2019.
 */
$(document).ready(function(){
    $('.add-options').on("click",function () {
        var variations_count = $('.variations-count').length;

        if ( variations_count <= 19 ){
            $('.variation-options-group').append('<input style="width: 20%;" class="form-control variations-count var-option-'+variations_count+'" name="variations_options[]"/><i class="mdi mdi-delete" style="font-size: 16px;"></i>');
            var variations_count = $('.variations-count').length + 1;
            $('.add-options-span').text('Add Options ('+(variations_count)+'/20)');
            if ( shop_id != 2 ){
                var drop_zone = '<tr><td colspan="5"><h3>Images</h3><div id="image-'+variations_count+'-10-7" class="dropzone variation-dropzone"></div></td></tr>';
            }else{
                var drop_zone = '';
            }
            $('.variation-list-tbody').append('<tr class="variations-count">' +
                '<td><select class="color-list select2 form-control" id="v-color-'+variations_count+'" name="p360[variations]['+variations_count+'][type][Color]"> <option value="Olive">Olive</option><option value="Cherry">Cherry</option><option value="Galaxy">Galaxy</option><option value="Chocolate">Chocolate</option><option value="Ivory">Ivory</option><option value="Rainbow">Rainbow</option><option value="Rose">Rose</option><option value="Mango">Mango</option><option value="Blue">Blue</option><option value="Aqua">Aqua</option><option value="Bronze">Bronze</option><option value="Camel">Camel</option><option value="Neon">Neon</option><option value="Sand">Sand</option><option value="Orange">Orange</option><option value="Green">Green</option><option value="Not Specified">Not Specified</option><option value="Magenta">Magenta</option><option value="Mahogany">Mahogany</option><option value="Jade">Jade</option><option value="White">White</option><option value="Purple">Purple</option><option value="Maroon">Maroon</option><option value="Red">Red</option><option value="Blueberry">Blueberry</option><option value="Cream">Cream</option><option value="Coffee">Coffee</option><option value="Peach">Peach</option><option value="Peanut">Peanut</option><option value="Mint">Mint</option><option value="Silver">Silver</option><option value="Yellow">Yellow</option><option value="Pink">Pink</option><option value="Grey">Grey</option><option value="Multicolor">Multicolor</option><option value="Brown">Brown</option><option value="Floral">Floral</option><option value="Cinnamon">Cinnamon</option><option value="Beige">Beige</option><option value="Chestnut">Chestnut</option><option value="Neutral">Neutral</option><option value="Champagne">Champagne</option><option value="Lavender">Lavender</option><option value="Matte Black">Matte Black</option><option value="Turquoise">Turquoise</option><option value="Light blue">Light blue</option><option value="Violet">Violet</option><option value="Dark Brown">Dark Brown</option><option value="Rose Gold">Rose Gold</option><option value="Blush Pink">Blush Pink</option><option value="Avocado">Avocado</option><option value="Charcoal">Charcoal</option><option value="Chili Red">Chili Red</option><option value="Apricot">Apricot</option><option value="Hotpink">Hotpink</option><option value="Khaki">Khaki</option><option value="Tan">Tan</option><option value="Navy Blue">Navy Blue</option><option value="Light yellow">Light yellow</option><option value="Watermelon red">Watermelon red</option><option value="Emerald Green">Emerald Green</option><option value="Fluorescent Yellow">Fluorescent Yellow</option><option value="Off White">Off White</option><option value="Light Grey">Light Grey</option><option value="Deep green">Deep green</option><option value="Burgundy">Burgundy</option><option value="Light green">Light green</option><option value="Fluorescent Green">Fluorescent Green</option><option value="Lake Blue">Lake Blue</option><option value="Lemon Yellow">Lemon Yellow</option><option value="Army Green">Army Green</option><option value="Gold">Gold</option><option value="Black">Black</option><option value="Clear">Clear</option><option value="Dark blue">Dark blue</option><option value="Dark Grey">Dark Grey</option><option value="Fuchsia">Fuchsia</option><option value="Blue Gray">Blue Gray</option><option value="Orchid Grey">Orchid Grey</option><option value="Teal">Teal</option><option value="Jet Black">Jet Black</option><option value="Cacao">Cacao</option><option value="Wither Black">Wither Black</option><option value="Sand Brown">Sand Brown</option><option value="Dark Ash">Dark Ash</option><option value="Deep Gray">Deep Gray</option><option value="Champagne Pink">Champagne Pink</option><option value="Light Ash">Light Ash</option><option value="Antique White">Antique White</option><option value="Ochre Brown">Ochre Brown</option><option value="Glitter Black">Glitter Black</option><option value="Glitter Blue">Glitter Blue</option><option value="Metallic Cherry">Metallic Cherry</option><option value="Metallic Lilac">Metallic Lilac</option><option value="Metallic Teal">Metallic Teal</option><option value="Space Grey">Space Grey</option><option value="Light Black">Light Black</option><option value="Rose Red">Rose Red</option><option value="Deep Black">Deep Black</option><option value="Deep Blue">Deep Blue</option><option value="Glow Yellow">Glow Yellow</option><option value="Neo Bright">Neo Bright</option> </select></td>' +
                '<td><input placeholder="RM" type="number" id="v-price-'+variations_count+'" name="p360[variations]['+variations_count+'][price]" class="form-control var_price"/></td>' +
                '<td><input placeholder="RM" type="number" id="v-rccp-'+variations_count+'" name="p360[variations]['+variations_count+'][rccp]" class="form-control var_rccp"/></td>' +
                '<td><input type="number" id="v-stock-'+variations_count+'" name="p360[variations]['+variations_count+'][stock]" class="form-control var_stock" /></td>' +
                '<td><input type="text" id="v-sku-'+variations_count+'" name="p360[variations]['+variations_count+'][sku]" class="form-control var_sku"></td>' +
                '<td rowspan="2"><i class="mdi mdi-delete" onclick="remove(this)" style="color: red;font-size: 24px;cursor: pointer;"></i></td></tr>'+
                drop_zone);
            $('.color-list').select();
            generateDropZone();
            var total_divs = $('.variation-dropzone').length;
            $('#image-'+total_divs+'-'+(total_divs*10)+'-'+total_divs).after('<div class="dropzone variation-dropzone" id="image-'+(total_divs+1)+'-'+((total_divs+1)*10)+'-'+(total_divs+1)+'"></div>');
            generateDropZone();
        }else{
            alert('You cannot add more than 20 options.');
            return false;
        }

    });
    $('.apply-to-all').click(function () {
        var variationPrice= $('.variation_price').val();
        var variationStock= $('.variation_stock').val();
        var variationSku= $('.variation_sku').val();
        for ( var a = 0 ; a < 20 ; a++ ){
            $('input[name="p360[variations]['+a+'][price]"]').val(variationPrice);
            $('input[name="p360[variations]['+a+'][stock]"]').val(variationStock);
            $('input[name="p360[variations]['+a+'][sku]"]').val(variationSku);
        }

    })
    $('.close-variation').click(function () {
        $('.variation').remove();
        $('.enable-variation').show();
    })
    Dropzone.autoDiscover = false;

    $('.variation-dropzone').each(function(){
        var options = $(this).attr('id').split('-');
        var dropUrl = 'upload-variation-images';
        var dropParamName = 'variation-' + options[1];
        var dropMaxFileSize = parseInt(options[3]);
        $(this).addClass('dropzone');
        if (!$(this).hasClass('dz-clickable')){
            $(this).dropzone({

                url: dropUrl,
                params : {
                    '_csrf-backend' : $('input[name=_csrf-backend]').val(),
                    'uqid' : $('input[name=uqid]').val()
                },
                maxFiles: 8,
                paramName: dropParamName,
                maxFilesSize: dropMaxFileSize,

                // Rest of the configuration equal to all dropzones
            });
        }

    });
});
function remove(element) {
    var remove_dropdown = $(element).parent().parent().next('tr').remove();
    $(element).parent().parent().remove();
    $(element).parent().parent().next().remove();
    var variations_count = $('.variations-count').length;
    $('.add-options-span').text('Add Options ('+variations_count+'/20)');
}
function generateDropZone() {
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
}