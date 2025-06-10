<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2/13/2020
 * Time: 12:01 PM
 */
?>
<div class="row">
    <div class="col-12">
        <!-- Column -->
        <div class="card">
        <div class="card-body table-responsive">
        <table class="tablesaw table-bordered table-hover table" data-tablesaw-mode="swipe" data-tablesaw-sortable data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
    <thead>
    <tr>
        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist">Target#</th>
        <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist">Created at</th>
        <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1">Status</th>
        <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="3">Prior Year</th>
        <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="3">Target for</th>
        <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4">Type</th>
        <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="5">Action</th>

    </tr>
    </thead>
    <tbody>
    <?php if(isset($records) && !empty($records)) {
        //print_r($records); die();
        foreach($records as $record) {  ?>
            <tr>

                <td class="title first-col static">
                        <a data-target-id-pk='<?= $record['id']; ?>' href="javascript:void(0)" class="show_items">
                            <?= "UT".$record['id'];?>
                        </a>
                </td>
                <td  class="static scale"> <?= $record['created_at'];?> </td>
                <td  class="static scale"> <?= $record['status']=="approved" ? "<span class='badge badge-success'>".strtoupper($record['status'])."</span>":$record['status'];?></td>
                <td  class="static scale"> <?= $record['year_compared'];?></td>
                <td  class="static scale"> <?= $record['year'];?></td>
                <td  class="static scale"> <?= $record['calculation_subtype']. " " . $record['calculation_type'];?></td>
                <td  class="static scale">
                    <a data-toggle="tooltip" title="Detail"  href="/sales/target-detail/?id=<?= $record['id'];?>"><span class="fa fa-eye"></span></a>
                    <?php if($record['status']!='approved') { ?>
                       &nbsp;&nbsp; <a data-toggle="tooltip"  title="Delete" href="/sales/delete-target/?id=<?= $record['id'];?>">
                                <span class="fa fa-trash" style="color:red"></span>
                        </a>
                    <?php } ?>
                </td>
            </tr>
            <tr id="target-items-record-<?= $record['id']; ?>" style="display:none">
                <td>
                    <?= $record['type'];?>
                </td>
                <td colspan="7">
                    <?php
                    $markups=explode(",",$record['markups']);
                    $applid_to=explode(",",$record['applied_to']); ?>
                    <div class="row">
                        <?php for($sub=0;$sub<count($markups);$sub++) {  ?>
                                <div class="col-sm-3">
                                    <span class="text-muted"> <?= $applid_to[$sub] . " : " .$markups[$sub] . " %"; ?></span>
                                </div>
                        <?php } ?>
                    </div>
                </td>


            </tr>
    <?php }} ?>
    </tbody>
</table>
        </div>
        </div>
    </div>
</div>
