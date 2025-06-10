/**
 * Created by user_PC on 6/15/2020.
 */
$('#channel_id').change(function () {
    var channel_id = $(this).val();
    $.ajax({
        type: 'GET',
        url: '/channels-details/get-category-list',
        data: 'channel_id='+channel_id,
        beforeSend: function () {

        },
        success: function (optionList) {
            $('#category_id').text('');
            $('#category_id').append(optionList);
        }
    })
});