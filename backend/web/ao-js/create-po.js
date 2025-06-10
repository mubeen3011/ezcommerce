/**
 * Created by user_PC on 4/8/2019.
 */

$(document).ready(function () {
    $('#warehouses').change(function () {
        if ( $('#warehouses').val()==7 ){
            $('#category').text('');
            $('#category').append('<option value="mcc">Mcc</option>')
        }else if ( $('#warehouses').val()==2 ){
            $('#category').text('');
            $('#category').append('<option value="dap">Dap</option>')
        }else if ( $('#warehouses').val()==3 ){
            $('#category').text('');
            $('#category').append('<option value="mcc">Mcc</option>')
        }else if ( $('#warehouses').val()==7 ){
            $('#category').text('');
            $('#category').append('<option value="mcc">Mcc</option>')
        }else{
            $('#category').text('');
            $('#category').append('<option value="mcc">Mcc</option><option value="dap">Dap</option>')
        }

    })
});