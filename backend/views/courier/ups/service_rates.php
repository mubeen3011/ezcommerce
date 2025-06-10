<table class="table table-striped">
    <thead>
    <tr colspan>
        <th>Service Name</th>
        <th>Currency</th>
        <th>
            Charges
        </th>
    </tr>
    </thead>
    <tbody>
<?php if(isset($status) && ($status=='success')) { ?>
        <tr>
            <td><?= isset($service) ? $service : " -- " ;?><input type="hidden" value="<?= $service ?>" name="service_name"></td>
            <td><?= isset($charges['CurrencyCode']) ? $charges['CurrencyCode']:"-";?></td>
            <td><?= isset($charges['amount']) ? $charges['amount']:"-";?></td>
        </tr>
<?php } else { ?>
    <tr><td colspan="3"><?= isset($msg) ? $msg:"";?></td></tr>
<?php } ?>
    </tbody>
</table>
<input type="hidden" value="<?= $courier->id ?>" name="courier">