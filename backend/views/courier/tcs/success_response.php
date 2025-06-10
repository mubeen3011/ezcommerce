<table class="table table-striped">
    <thead>
    <tr colspan>
        <th>CN/Tracking#</th>
        <th>Order Number</th>
        <th>Charges </th>
    </tr>
    </thead>
    <tbody>
    <?php if(isset($status) && ($status=='success')) { ?>
        <tr>
            <td><?= isset($tracking_number) ? $tracking_number : " -- " ;?></td>
            <td><?= isset($additional_info['order_code']) ? $additional_info['order_code']:"-";?></td>
            <td>Total Charges : <?= isset($amount_inc_taxes) ? ($amount_inc_taxes + $extra_charges):"-";?></td>
        </tr>


    <?php } else { ?>
        <tr><td colspan="3"><?= isset($msg) ? $msg:"";?></td></tr>
    <?php } ?>
    </tbody>
</table>

<!-------------->
<div class="row">
    <div class="col-md-12">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item"> <a class="nav-link active" data-toggle="tab" href="#label" role="tab">Label</a> </li>
            <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#packingslip" role="tab">packing Slip</a> </li>
        </ul>
        <!-- Tab panes -->
        <div class="tab-content tabcontent-border">
            <div class="tab-pane active" id="label" role="tabpanel">
                <?php if(isset($label) && !empty($label)) : ?>
                    <div id="label_printing_div" style="margin: 10% 35%">
                        <h1 class="fa fa-print">
                            <a href="<?= $label;?>" target="_blank">Click to print Label</a>
                        </h1>
                    </div>
                <?php endif;?>
            </div>
            <div class="tab-pane  p-20" id="packingslip" role="tabpanel">
                <?php if(isset($packing_slip) && !empty($packing_slip)) : ?>
                    <div id="packing_slip_div" style="margin-left: 7%">

                        <embed src="/shipping-labels/<?= $packing_slip?>" width="100%" style="max-height:700px;overflow-y:scroll;" height="700px"/>
                    </div>
                <?php endif;?>
            </div>
        </div>

    </div>
</div>
<!---------------->

