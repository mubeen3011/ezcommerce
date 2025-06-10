jQuery(window).load(
    function () {

        var wait_loading = window.setTimeout(function () {
                $('#loading').slideUp('fast');
                jQuery('body').css('overflow', 'auto');
            }, 1000
        );

    });

/* ========================================================================
 * Bootstrap: tab.js v3.3.7
 * http://getbootstrap.com/javascript/#tabs
 * ========================================================================
 * Copyright 2011-2016 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * ======================================================================== */


+function ($) {
    'use strict';

    // TAB CLASS DEFINITION
    // ====================

    var Tab = function (element) {
        // jscs:disable requireDollarBeforejQueryAssignment
        this.element = $(element)
        // jscs:enable requireDollarBeforejQueryAssignment
    }

    Tab.VERSION = '3.3.7'

    Tab.TRANSITION_DURATION = 150

    Tab.prototype.show = function () {
        var $this    = this.element
        var $ul      = $this.closest('ul:not(.dropdown-menu)')
        var selector = $this.data('target')

        if (!selector) {
            selector = $this.attr('href')
            selector = selector && selector.replace(/.*(?=#[^\s]*$)/, '') // strip for ie7
        }

        if ($this.parent('li').hasClass('active')) return

        var $previous = $ul.find('.active:last a')
        var hideEvent = $.Event('hide.bs.tab', {
            relatedTarget: $this[0]
        })
        var showEvent = $.Event('show.bs.tab', {
            relatedTarget: $previous[0]
        })

        $previous.trigger(hideEvent)
        $this.trigger(showEvent)

        if (showEvent.isDefaultPrevented() || hideEvent.isDefaultPrevented()) return

        var $target = $(selector)

        this.activate($this.closest('li'), $ul)
        this.activate($target, $target.parent(), function () {
            $previous.trigger({
                type: 'hidden.bs.tab',
                relatedTarget: $this[0]
            })
            $this.trigger({
                type: 'shown.bs.tab',
                relatedTarget: $previous[0]
            })
        })
    }

    Tab.prototype.activate = function (element, container, callback) {
        var $active    = container.find('> .active')
        var transition = callback
            && $.support.transition
            && ($active.length && $active.hasClass('fade') || !!container.find('> .fade').length)

        function next() {
            $active
                .removeClass('active')
                .find('> .dropdown-menu > .active')
                .removeClass('active')
                .end()
                .find('[data-toggle="tab"]')
                .attr('aria-expanded', false)

            element
                .addClass('active')
                .find('[data-toggle="tab"]')
                .attr('aria-expanded', true)

            if (transition) {
                element[0].offsetWidth // reflow for transition
                element.addClass('in')
            } else {
                element.removeClass('fade')
            }

            if (element.parent('.dropdown-menu').length) {
                element
                    .closest('li.dropdown')
                    .addClass('active')
                    .end()
                    .find('[data-toggle="tab"]')
                    .attr('aria-expanded', true)
            }

            callback && callback()
        }

        $active.length && transition ?
            $active
                .one('bsTransitionEnd', next)
                .emulateTransitionEnd(Tab.TRANSITION_DURATION) :
            next()

        $active.removeClass('in')
    }


    // TAB PLUGIN DEFINITION
    // =====================

    function Plugin(option) {
        return this.each(function () {
            var $this = $(this)
            var data  = $this.data('bs.tab')

            if (!data) $this.data('bs.tab', (data = new Tab(this)))
            if (typeof option == 'string') data[option]()
        })
    }

    var old = $.fn.tab

    $.fn.tab             = Plugin
    $.fn.tab.Constructor = Tab


    // TAB NO CONFLICT
    // ===============

    $.fn.tab.noConflict = function () {
        $.fn.tab = old
        return this
    }


    // TAB DATA-API
    // ============

    var clickHandler = function (e) {
        e.preventDefault()
        Plugin.call($(this), 'show')
    }

    $(document)
        .on('click.bs.tab.data-api', '[data-toggle="tab"]', clickHandler)
        .on('click.bs.tab.data-api', '[data-toggle="pill"]', clickHandler)

}(jQuery);
jQuery(document).ready(function () {

    //po delete
    $(".po-delete").on("click",function (event) {
        event.preventDefault();
        var r=confirm("Are you sure you want to delete this PO?");
        if (r==true)   {
            window.location = $(this).attr('href');
        }
    });

    // logout post form handling
    jQuery('.cs-logout').on('click', function () {
        jQuery("#lg").submit();
    });
/*
    // call calculator
    $(".btnCal").on('click',function () {
        $.ajax({
            type: "post",
            url: "/site/calculator",
            data: [],
            dataType: "html",
            beforeSend: function () {},
            success: function (data) {
                $(".cal-view").html(data);

            },
        });
    });*/


    $('.cp-save').click(function (event) {
        event.preventDefault();
        var isValid = true;
        var $row = $(this).parents('tr');

        var sla = $row.find("input[name='seller_1']");
        var slb = $row.find("input[name='seller_2']");
        var slc = $row.find("input[name='seller_3']");

        var lpa = $row.find("input[name='low_price_1']");
        var lpb = $row.find("input[name='low_price_2']");
        var lpc = $row.find("input[name='low_price_3']");

        if(lpa.val() !== '' && sla.val() === '' )
        {
           sla.css('border-color', 'red');

        } else {
            sla.css('border-color', '');
        }

        if(sla.val().length < 2)
        {
            sla.css('border-color', 'red');

        } else {
            sla.css('border-color', '');
        }

        if(lpb.val() !== '' && slb.val() === '')
        {
           slb.css('border-color', 'red');

        } else {
            slb.css('border-color', '');
        }

        if(slb.val().length < 2)
        {
            slb.css('border-color', 'red');

        } else {
            slb.css('border-color', '');
        }


        if(lpc.val() !== '' && slc.val() === '' )
        {
           slc.css('border-color', 'red');

        } else {
            slc.css('border-color', '');
        }

        if(slc.val().length < 2)
        {
            slc.css('border-color', 'red');

        } else {
            slc.css('border-color', '');
        }


        /*var lp = $row.find('input.numc').each(function() {
           if($(this).val() === '') {
                $(this).css('border-color', 'red');
            } else {
                $(this).removeAttr('style');
            }
        });
        var sn = $row.find('input.only_alphanumric').each(function() {
            if($(this).val() === '') {
                $(this).css('border-color', 'red');
            } else {
                $(this).removeAttr('style');
            }
        });*/
        /*if(lp.val().length < 0 && sn.val().length < 0 )
        {
            lp.css('background-color', 'red');
            sn.css('background-color', 'red');
            isValid = false;
        }*/

        if(isValid)
        {
            var fields = $row.find('form').serialize();
            $.ajax({
                type: "post",
                url: "/competitive-pricing/save-prices",
                data: fields,
                dataType: "json",
                beforeSend: function () {
                    // $("#basket :input, #basket select").attr("disabled", true);
                },
                success: function (data) {
                    if (data.ch_1 == 'Yes')
                        $row.find('td.ch1_txt').css('background-color', 'lightgreen');
                    if (data.ch_2 == 'Yes')
                        $row.find('td.ch2_txt').css('background-color', 'lightgreen');
                    if (data.ch_3 == 'Yes')
                        $row.find('td.ch3_txt').css('background-color', 'lightgreen');

                    $row.find('td.ch1_txt').html(data.ch_1);
                    $row.find('td.ch2_txt').html(data.ch_2);
                    $row.find('td.ch3_txt').html(data.ch_3);
                },
            });
        }

    });

    $('.tg-kr944').find('input').blur(function () {

        var $row = $(this).parents('tr');
        var fields = $row.find('form').serialize();
        $.ajax({
            type: "post",
            url: "/subsidy/save-record",
            data: fields,
            dataType: "json",
            beforeSend: function () {
                // $("#basket :input, #basket select").attr("disabled", true);
            },
            success: function (data) {
                //$row.find('td').css('background-color', 'lightgreen');
            },
        });

    });

    $('.tg-kr94').find('input').blur(function (e) {
        e.preventDefault();
        $(this).closest('tr').find('.cp-save').trigger('click');
    });

    $('.tg-kr94').find('select').on('change', function (e) {
        e.preventDefault();
        $(this).closest('tr').find('.cp-save').trigger('click');
    });

    $(".date_filter").on('change', function () {
        $("#filter").submit();
    });

    $(".date_filterx").on('change', function () {
        $("#p-export").submit();
    });

    if ($("#sku_id").length > 0)
        $("#sku_id").select2();


    $('.chl-Shopee,.chl-11Street,.chl-Lelong,.chl-Blip,.chl-909-11Street,.chl-909-Lazada,.chl-909-Shopee,.chl-Deal4U-Lazada,.chl-BargainExp-11Street').hide();
    $('.pname,.pchange').hide();
    $(".badgebox").on('click', function () {
        clsName = $(this).attr('data-class');
        if (this.checked)
            $("." + clsName).show();
        else
            $("." + clsName).hide();
    });








    if ( $( "#tablexx" ).length ) {

        $('#tablexx tfoot th.tg-kr94').each(function () {
            var title = $(this).text();
            if (title == 'Lowest Price'
                || title == 'Margins at Lowest Price %'
                || title == 'Sale Price'
                || title == 'Margins at Sale Price %'
                || title == 'Loss/Profit in RM') {
                var did = $(this).attr("data-id");
                $(this).html('<select class="filter_comparatorx filter_comparator_'+did+'">\n' +
                    '  <option value="reset">reset</option>\n' +
                    '  <option value="eq">=</option>\n' +
                    '  <option value="gt">&gt;=</option>\n' +
                    '  <option value="lt">&lt;=</option>\n' +
                    '  <option value="ne">!=</option>\n' +
                    '</select>' +
                    '<br/><input type="number" class="filter_valuex filter_value_'+did+'">');
            }
            else
                $(this).html('<input type="text" class="form-control simple"  />');
        });

        var tablex = $('#tablexx').DataTable({
            lengthMenu: [[-1, 25, 50, 100], ["All", 25, 50, 100]],
            dom: 'Blfrtip',
            "bSort": false,
            deferLoading: 57,
            buttons: [
                'csv', 'excel'
            ],
            colReorder: true,
            autoWidth: true,
            columnDefs: [
                {"type": "html-input", "targets": '_all'}
            ]

        });

        $.fn.dataTableExt.afnFiltering.push(
            function (oSettings, aData, iDataIndex) {
                //var column_index = 6; //3rd column

                for(var column_index = 6; column_index <= 45 ; column_index++ )
                {
                    var comparator = $('.filter_comparator_'+column_index).val();
                    var value = $('.filter_value_'+column_index).val();
                    if (value.length > 0 && !isNaN(parseFloat(value))) {
                        value = parseFloat(value);
                        var row_data = parseFloat(aData[column_index]);
                        switch (comparator) {
                            case 'eq':
                                return row_data == value ? true : false;
                                break;
                            case 'gt':
                                return row_data >= value ? true : false;
                                break;
                            case 'lt':
                                return row_data <= value ? true : false;
                                break;
                            case 'ne':
                                return row_data != value ? true : false;
                                break;
                            case 'reset':
                                return true;
                                break;
                        }
                    }
                }
                return true;
            }
        );


        $('.filter_comparatorx').each(function(i, obj) {
            $(this).change(function() {
                tablex.draw();
            });
        });

        $('.filter_valuex').each(function(i, obj) {
            $(this).change(function() {
                tablex.draw();
            });
        });
        tablex.buttons().container()
            .appendTo( $('.col-sm-6:eq(0)', tablex.table().container() ) );

        $("select[name='tablexx_length']").addClass('form-control');
        $("#tablexx_filter").hide();
        // Apply the search
        tablex.columns().every(function () {
            var that = this;

            $('input.simple', this.footer()).on('keyup change', function () {
                if (that.search() !== this.value) {
                    that
                        .search(this.value)
                        .draw();
                }
            });
        });
    } else {
        // $('#table').DataTable();
        $('#table tfoot th.tg-kr94').each(function () {
            var title = $(this).text();
            $(this).html('<input type="text" class="form-control"  />');
        });

        $('#tablex tfoot th').each(function () {
            var title = $(this).text();
            $(this).html('<input type="text" class="form-control"  />');
        });

        // DataTable
        var table = $('#table,#tablex').DataTable({
            "lengthMenu": [[-1, 25, 50, 100], ["All", 25, 50, 100]],
            dom: 'Blfrtip',
            "bSort": false,
            "deferLoading": 57,
            buttons: [
                'csv', 'excel'
            ],
            colReorder: false,
            autoWidth: false,
            columnDefs: [
                {"type": "html-input", "targets": '_all'}
            ]
        });


        table.buttons().container()
            .appendTo( $('.col-sm-6:eq(0)', table.table().container() ) );

        $("select[name='table_length']").addClass('form-control');
        $("#table_filter").hide();


        // Apply the search
        table.columns().every(function () {
            var that = this;

            $('input', this.footer()).on('keyup change', function () {
                if (that.search() !== this.value) {
                    that
                        .search(this.value)
                        .draw();
                }
            });
        });
    }



    $('.po_isis_chk:checkbox').click(function(){
        if($(this).is(":checked")) {
            $('.chk').prop('checked', true);

        } else {
            $('.chk').prop('checked', false);
        }
        $('input[name="isis_po_skus[]"]').on('change', function () {
            Populate2()
        }).change();
    });

    $('.po_blip_chk:checkbox').click(function(){
        if($(this).is(":checked")) {
            $('.chk2').prop('checked', true);

        } else {
            $('.chk2').prop('checked', false);
        }
        $('input[name="blip_po_skus[]"]').on('change', function () {
            Populate3()
        }).change();
    })

    ;$('.po_f909_chk:checkbox').click(function(){
        if($(this).is(":checked")) {
            $('.chk3').prop('checked', true);

        } else {
            $('.chk3').prop('checked', false);
        }
        $('input[name="blip_po_skus[]"]').on('change', function () {
            Populate4()
        }).change();
    });


    $('#table thead th input:checkbox').click(function(){
        if($(this).is(":checked")) {
            $('.chk:visible').prop('checked', true);

        } else {
            $('.chk:visible').prop('checked', false);
        }
        $('input[name="asku[]"]').on('change', function () {
            Populate()
        }).change();
    });

    $(".numc").keypress(function(e){
        if (e.which != 46  && e.which != 8  && e.which != 45 && e.which != 46 &&
            !(e.which >= 48 && e.which <= 57)) {
            return false;
        }

    });

    $(".num_subsidy").blur(function(e){
       var num = $(this).val();
        if(parseInt(num) > 50)
            $(this).val('0');
        if(parseInt(num) < -50)
            $(this).val('0');
    });

    $(".num_ss").blur(function(e){
       var num = $(this).val();
        if(parseInt(num) > 10)
            $(this).val('0');
        if(parseInt(num) < -1)
            $(this).val('0');
    });





    if ($("#sku-list").length > 0) {
        $("#role_sub_cat_id").select2();
        // change skus list base sub category
        $('#role_sub_cat_id').on('change', function () {
            var category = $(this).val();
            if (category != '') {


                $.get("/competitive-pricing/sku-by-category?category=" + category).done(function (data) {
                    // $('html,body').find('input[name=areas]').removeAttr('disabled');
                    $('html,body').find('#sku-list').html(data);
                    $("#userroles-skus").select2({
                        placeholder: 'Select an option',
                        allowClear: true,
                        tags: "true",
                    });
                });
            }
        });

        if (!isNew) {
            $.get("/competitive-pricing/sku-by-category?category=" + category + "&selected=" + skus).done(function (data) {
                // $('html,body').find('input[name=areas]').removeAttr('disabled');
                $('html,body').find('#sku-list').html(data);
                $("#userroles-skus").select2({
                    placeholder: 'Select an option',
                    allowClear: true,
                    tags: "true",
                });
            });
        }

    }


    // assign sku to users

    $('input[name="asku[]"]').on('change', function () {
        Populate();
    }).change();

    // validation to check if checbox checked for sku selection

    $("select[name='user']").change(function () {
        var valid = true;
        if ($(".users_skus").val() == '') {
            valid = false;
            alert('Please at least check one SKU');
        }

        if (valid)
            $("#filter").submit();
    });


    $(".only_alphanumric").bind("keypress", function (event) {
        if (event.charCode!=0) {
            var regex = new RegExp("^[a-zA-Z0-9]+$");
            var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
            if (!regex.test(key)) {
                event.preventDefault();
                return false;
            }
        }
    });

    // sku settings save
    $('.tg-ss').find('input').blur(function () {
        save_ss($(this));
    });

    $('.tg-ss').find('select').on('change', function () {
        save_ss($(this));
    });

    // sku cost price save
    $('.tg-cp').find('input').change(function (e) {
        e.preventDefault();
        save_cp($(this));
    });

    $('.tg-cp').find('textarea').change(function (e) {
        e.preventDefault();
        save_cp($(this));
    });

    $('.tg-cp').find('select').on('change', function (e) {
        e.preventDefault();
        save_cp($(this));
    });

    // manage orders PO
    $(".po_code").on('change',function () {
       var seq = $('option:selected', this).attr('data-sq');
       $("input[name='po_seq']").val(seq);

    });



    $(".add-prd").on('click',function () {
        var warehouse = $(this).attr('data-warehouse');
        $.ajax({
            async: true,
            type: "post",
            url: "/stocks/add-product-line",
            data: {'for':warehouse},
            dataType: "html",
            beforeSend: function () {},
            success: function (data) {
                $(".temp-tr-"+warehouse).html(data);
                $(".sku-selects").select2();
                $("input[name='isis_sku']").focus();
                $(".btn-check").on('click',function() {
                    var skus = $("select[name='"+warehouse+"_sku']").val();
                    $.ajax({
                        async: true,
                        type: "post",
                        url: "/stocks/add-sku-details",
                        data: {'sku':skus,'for':warehouse,'isPO':isPo},
                        dataType: "html",
                        beforeSend: function () {},
                        success: function (data) {
                            if(data == '0')
                                alert("Selected SKU already in list or not match criteria");
                            else{
                                $("."+warehouse+"-data").append(data);
                                $(".temp-tr-"+warehouse).html("");
                            }
                        },
                    });

                });
                $(".btn-cancel").on('click',function() {
                    var warehouse = $(this).attr('data-warehouse');
                    $(".temp-tr-"+warehouse).html("");
                });
            },
        });
    });


    // change ship to select
    $(".ship_to").on('change', function () {
       var wh = $(this).attr('data-wh');
       var to = $(this).val();
       if(to == 'customer')
       {
           $("."+wh+"_ship_c_add").removeClass('hide');
       } else {
           $("."+wh+"_ship_c_add").addClass('hide');
       }

    });

    // add new multi SKU
    $(".add-dm-sku").on('click',function () {

        var len = $('.dm-multi-sku tr').length;
        var skulen = len / 2;
        if(len > 21)
        {
            $(this).attr('disabled',true);
            alert("You cannot request more then 10 SKUs in single Deal.")
            return false;
        } else if (skulen >= requestedSKUS){
            $(this).attr('disabled',true);
            alert("You cannot request more then "+requestedSKUS+" SKUs.")
            return false;
        }

        //reasons array
        var reasons = ['Competitor Top', 'Focus SKUs','Philips Campaign',
            'Flash Sale','Shocking Deal','Aging Stocks', 'EOL','Competitive Pricing',
            'Outright','Others'];

        var sku = $(".multi-select-sku").val();
        var skuName = $(".multi-select-sku option:selected").text();
        var price = $(".dm-price").val();
        var qty = $(".dm-qty").val();
        var subsidy = $(".dm-subsidy").val();
        if(subsidy == '') {
            $(".dm-subsidy").val('0');
            subsidy = '0';
        }if(qty == '') {
            $(".dm-qty").val('1');
            qty = '1';
        }
        var reason = $(".dm-reason").val();
        var margin_per = $(".dm-margin-per").val();
        var margin_rm = $(".dm-margin-rm").val();
        var skuExistsCount = 0;
         $('input.skunames').each(function () {
           var skuExists = $(this).val();
           if(skuName == skuExists)
               skuExistsCount = 1;
        });
        var marginPer = margin_per.replace("%", "");
        marginPer = marginPer.replace(",", "");
        if(skuExistsCount == 0)
        {
            if(sku != '' && price != '' && qty != '' && reason != '' && isNumber(price) && isNumber(qty) && isNumber(subsidy))
            {
                if(checkApproveSku() == 'yes') {
                    if(marginPer < 0) {


                        var options = '';
                        for (var i = 0; i < reasons.length; i++) {
                            selected = (reason == reasons[i] ) ? 'selected' : '';
                            options += "<option " + selected + " value='" + reasons[i] + "'>" + reasons[i] + "</option>";
                        }

                        var html = "<td><input type='text' class='skunames form-control' data-sku-id='" + sku + "' name='DM[s_" + sku + "][sku]' readonly value='" + skuName + "'></td> "
                            + "<td><input type='text' class=' form-control' name='DM[s_" + sku + "][price]'  value='" + price + "'></td> "
                            + "<td><input type='text' class=' form-control' name='DM[s_" + sku + "][subsidy]'  value='" + subsidy + "'></td> "
                            + "<td><input type='text' class=' form-control' name='DM[s_" + sku + "][qty]'  value='" + qty + "'></td> "
                            + "<td><input type='text' readonly class='form-control' name='DM[s_" + sku + "][margin_per]'  value='" + margin_per + "'></td> "
                            + "<td><input type='text' readonly class='form-control' name='DM[s_" + sku + "][margin_rm]'  value='" + margin_rm + "'></td> "
                            + "<td><select  class='form-control' name='DM[s_" + sku + "][reason]' >" + options + "</select></td>"
                            + "<td><a href='javascript:;' data-sku-id='" + sku + "'  class='dm-more up'><i class='glyph-icon icon-arrow-right'></i></a>&nbsp;<a href='javascript:;'  data-sku-id='" + sku + "' class='dm-delete'><i class='glyph-icon icon-trash'></i></a> </td> ";


                        var more = "<td></td> "
                            + "<td>Cost Price: <span class='price_" + sku + "'></span></td> "
                            + "<td>Commission: <span class='commision_" + sku + "'></span></td> "
                            + "<td>Shipping: <span class='shipping_" + sku + "'></span></td> "
                            + "<td>Gross Profit: <span class='gp_" + sku + "'></span></td> "
                            + "<td>Price w/ subsidy: <span class='pws_" + sku + "'></span></td> "
                            + "<td>Customer Pays: <span class='cp_" + sku + "'></span></td>"
                            + "<td></td>";


                        $(".dm-multi-sku tr:first").after("<tr  class='row-" + sku + "'>" + html + "</tr><tr class='more-" + sku + " hide'>" + more + "</tr>");

//            $(".multi-select-sku").clear();
                        $(".dm-price").val("");
                        $(".dm-qty").val("");
                        $(".dm-reason").val("");
                        $(".dm-margin-per").val("");
                        $(".dm-margin-rm").val("");
                        $(".dm-subsidy").val("");

                        $(".dm-multi-sku input").each(function () {
                            $(this).on('change', function () {
                                var dynamicSku = "DM[s_" + sku + "]";
                                calculateSku($(this), dynamicSku);
                            })
                        });
                    } else {
                        alert(skuName + ' cannot be added as its margin are positives');
                    }
                } else {
                    alert(skuName + ' already Approved state with in start and end dates.')
                }

            }else {
                alert("Values cannot be blank or string");
            }
        } else {
            alert(skuName + " already exists!!");
        }

    });

    $(".btn-req").on('click',function () {
        var len = $('.dm-multi-sku tr').length;
        var isNoError = true;
        if(len < 2)
        {
            alert('At-least one SKU in the list before making request.');
            return false;
        } else {
            //$(this).attr('disabled','disabled');
            $(".form-group").each(function () {
               if($(this).hasClass('has-error')){
                   isNoError = false;
               }
            });
            if(isNoError){
                $(this).attr('disabled','disabled');
                $("#deal-maker-request").submit();
            }

        }
    });


    $(".dm-multi-sku").on('click','a.dm-delete',function () {
        var sku = $(this).attr("data-sku-id");
        var result = confirm("Want to delete this SKU?");
        if (result) {
            $(this).closest('tr').remove();
            $('.more-'+sku).remove();
        }
    });

    $(".dm-multi-sku").on('click','a.dm-more',function () {
            var sku = $(this).attr("data-sku-id");
            if($(this).hasClass('up'))
            {
                $(this).removeClass("up").addClass("down");
                $('tr.more-'+sku).removeClass('hide');
                $("i", this).removeClass("glyph-icon icon-arrow-right").addClass("glyph-icon icon-arrow-down");
                var dynamicSku  = "DM[s_"+sku+"]";
                calculateSku($(this),dynamicSku);
            } else {
                $(this).removeClass("down").addClass("up");
                $('tr.more-'+sku).addClass('hide');
                $("i", this).removeClass("glyph-icon icon-arrow-down").addClass("glyph-icon icon-arrow-right");
            }


        });

    /*$(".dm-multi-sku input").each(function () {
       $(this).on('change',function () {
           calculateSku($(this),0);
       })
    });
*/
    $(".dm-multi-sku input").each(function () {
        $(this).on('change',function () {
            var sku = $(this).attr('data-sku-id');
            if (typeof sku !== typeof undefined && sku !== false) {
                var dynamicSku  = "DM["+sku+"]";
                calculateSku($(this),dynamicSku);
            } else {
                calculateSku($(this),0);
            }

        })
    });





    $(".start_datetime").datetimepicker({
        format: 'yyyy-mm-dd hh:ii',
        autoclose: true,
        startDate: new Date(),
        todayBtn: true
    }).on('changeDate', function (selected) {
        var minDate = new Date(selected.date.valueOf());
        $('.end_datetime').datetimepicker('setStartDate', minDate);
    });
    $(".end_datetime").datetimepicker({
        format: 'yyyy-mm-dd hh:ii',
        startDate: new Date(),
        autoclose: true,
        todayBtn: true,
        minDate: 0
    });



    // PO buttons
    $(".btn-isis-po").on('click',function () {
        if($(this).val() == "Finalize PO")
        {
            var r = confirm("Are you sure to finialse this PO?");
            if (r == true) {
                $(this).attr("disabled","disabeld");
                $(this).val("Adding ER to ISIS...");
                $("#po_isis").submit();
            } else {
                return false;
            }
        } else {
            $("#po_isis").submit();
        }

    });

    $(".btn-blip-po").on('click',function () {
        if($(this).val() == "Finalize PO")
        {
            var r = confirm("Are you sure to finialse this PO?");
            if (r == true) {
                $(this).attr("disabled","disabeld");
                $(this).val("Adding ER to ISIS...");
                $("#po_blip").submit();
            } else {
                return false;
            }
        } else {
            $("#po_blip").submit();
        }
    });

    $(".btn-f909-po").on('click',function () {
        if($(this).val() == "Finalize PO")
        {
            var r = confirm("Are you sure to finialse this PO?");
            if (r == true) {
                $(this).attr("disabled","disabeld");
                $(this).val("Adding ER to ISIS...");
                $("#po_f909").submit();
            } else {
                return false;
            }
        } else {
            $("#po_f909").submit();
        }
    });


    $(".ship_to").on('change',function () {
        var shipTo = $(this).val();
        if(shipTo == 'customer'){
            $('.po_code option').filter(function( index ) {
                return $( this ).attr( "data-sq" ) == 9;
            }).prop('selected', true);
            $("input[name='po_seq']").val('9');
        }
    });

    //$('input[name="Search[created]"]').daterangepicker();
   /* var start = moment().subtract(29, 'days');
    var end = moment();

    function cb(start, end) {
        $('input[name="Search[created]"]').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
    }*/
    if($('div').hasClass('order-items-index'))
    {
        $('input[name="Search[created]"]').daterangepicker({
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' to ',
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });

        // cb(start, end);

        $('.sales-s2').select2();
    }

    $(".alert-close").on('click',function () {
       $(".alert").fadeOut(1000);
    });

    $(".white-modal-80").on('click',function () {
        $("#white-modal-80").removeClass('hide');
    });

    $("[data-toggle=popover]").popover();

    $(".btnClose").click (function () {
        $("#white-modal-80").dialog( "close" );
    });

    if($('input[name="Search[show_category]"]:checked').length > 0)
    {
        $(".cat").removeAttr('disabled');
    } else {
        $(".cat").attr('disabled',true);
    }

    $('input[name="Search[show_category]"]').click(function() {
        if (this.checked) {
            $(".cat").removeAttr('disabled');
        } else {
            $(".cat").attr('disabled',true);
        }
    });

    //fc form validation
    $(".btn-fc-import").on('click',function () {

        var fileVal = $(".csv-file").val();
        if(fileVal == '')
        {
            alert("Please select batch file (csv format)");
            return false;
        } else {
            return true;
        }



    });


});
function Populate() {
    vals = $('input[name="asku[]"]:checked').map(function () {
        return this.value;
    }).get().join(',');

    $('.users_skus').val(vals);
}

function Populate2() {
    vals = $('.chk:checked').map(function () {
        return this.value;
    }).get().join(',');

    $('.isis_po_skuss').val(vals);
}

function Populate3() {
    vals = $('.chk2:checked').map(function () {
        return this.value;
    }).get().join(',');

    $('.blip_po_skuss').val(vals);
}
function Populate4() {
    vals = $('.chk3:checked').map(function () {
        return this.value;
    }).get().join(',');

    $('.f909_po_skuss').val(vals);
}


function save_ss(cur)
{
    var $row = cur.parents('tr');
    var fields = $row.find('form').serialize();
    $.ajax({
        type: "post",
        url: "/sku-margin-settings/save",
        data: fields,
        dataType: "json",
        beforeSend: function () {},
        success: function (data) {
            cur.css('border-color','green');

        },
    });
}

function save_cp(cur)
{
    var $row = cur.parents('tr');
    var fields = $row.find('form').serialize();
   /* var result = window.confirm('Are you sure to make changes for this SKU?');
    if(result != false)
    {
        $.ajax({
            type: "post",
            url: "/cost-price/save",
            data: fields,
            dataType: "json",
            beforeSend: function () {},
            success: function (data) {
                cur.css('border-color','green');

            },
        });
    }*/
    $.ajax({
        type: "post",
        url: "/cost-price/save",
        data: fields,
        dataType: "json",
        beforeSend: function () {},
        success: function (data) {
            cur.css('border-color','green');

        },
    });
}

function calculateSku(obj,isDyn)
{
    if(isDyn == 0)
    {
        var channel = $("#dealsmaker-channel_id").val();
        var sku = $("#multi-sku-sel").val();
        var price = $("#dealsmaker-deal_price").val();
        var subsidy = $("#dealsmaker-deal_subsidy").val();
        var qty = $("#dealsmaker-deal_qty").val();
    } else {
        var channel = $("#dealsmaker-channel_id").val();
        var sku = $("input[name='"+isDyn+"[sku]']").attr("data-sku-id");

        var price = $("input[name='"+isDyn+"[price]']").val();
        var subsidy =$("input[name='"+isDyn+"[subsidy]']").val();
        var qty = $("input[name='"+isDyn+"[qty]']").val();
    }

    if(channel > 0)
    {
        $.ajax({
            async: true,
            type: "post",
            url: "/deals-maker/calculate",
            data: {'sku_id':sku,'channel':channel,price:price,subsidy:subsidy,qty:qty,fbl:0},
            dataType: "json",
            beforeSend: function () {},
            success: function (data) {
                if(isDyn == 0)
                {
                    $(obj).closest('tr').find(".dm-margin-per").val(data.sales_margins);
                    $(obj).closest('tr').find(".dm-margin-rm").val(data.sales_margins_rm);
                } else {
                    $(obj).closest('tr').find("input[name='"+isDyn+"[margin_per]']").val(data.sales_margins);
                    $(obj).closest('tr').find("input[name='"+isDyn+"[margin_rm]']").val(data.sales_margins_rm);
                }
                // add data into more row columns
                $(".price_"+sku).html("RM "+data.cost);
                $(".commision_"+sku).html(data.commission);
                $(".shipping_"+sku).html("RM "+data.shipping);
                $(".gp_"+sku).html(data.gross_profit + "%");
                $(".pws_"+sku).html("RM "+data.price_after_subsidy);
                $(".cp_"+sku).html(data.customer_pays);

            },
        });
    } else {
        alert("Please select Channel");
    }

}



function isNumber(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
}

function checkApproveSku()
{
    var ret = '';
    var channel = $("#dealsmaker-channel_id").val();
    var start_date = $("#dealsmaker-start_date").val();
    var end_date = $("#dealsmaker-end_date").val();
    var sku = $(".multi-select-sku").val();
    $.ajax({
        async: false,
        type: "post",
        url: "/deals-maker/check-approved-sku",
        data: {'sku':sku,'channel':channel,'start_date':start_date,'end_date':end_date},
        dataType: "json",
        beforeSend: function () {},
        success: function (data) {
            ret =   data.allow;
        },
    });
    return ret;
}
function AddDynamicPIdInputs(value){
    //alert(value);
    $('.sku_column').show();
    $('#'+value+' .dynamic_input').text('');
    $('#'+value+' .dynamic_input').html('<br /><label>Lazada Id</label><input type="text" id="lazada_id" class="form-control"/><label>11Street Id</label><input id="elevenstreet_id" type="text" class="form-control"/><br /> <button id="update_skus">Update</button> ');
}
function DeleteProductId(value){
    alert(value);
    $.ajax({
        async: false,
        type: "post",
        url: "/crawl/delete-product-id",
        data: {'pid':value},
        dataType: "json",
        beforeSend: function () {},
        success: function (data) {
            if( data.success=true ){
                alert('successfully deleted');
                $('#'+value).text('');
            }else{
                alert('some thing went wrong');
            }
        },
    });
}

$(document).on('click', '#update_skus', function(){
    $.ajax({
        async: false,
        type: "post",
        url: "/crawl/add-new-skus",
        data: {'lazada_id':$('#lazada_id').val(),'elevenstreet_id':$('#elevenstreet_id').val(),'sku_id':$('#sku_id').val()},
        dataType: "json",
        beforeSend: function () {},
        success: function (data) {
            if( data.lazada=='success' ){
                alert('Lazada product id added successfully');
            }
            if( data.elevenstreet=='success' ){
                alert('11Street product id added successfully');
            }
        },
    });
});


$(function(){
    // bind change event to select
    $('#dynamic_select').on('change', function () {
        var url = $(this).val(); // get selected value
        if (url) { // require a URL
            window.location = url; // redirect
        }
        return false;
    });
});


// Date Picker
