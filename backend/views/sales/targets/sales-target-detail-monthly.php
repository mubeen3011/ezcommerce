<?php
$month=['january','february','march','april','may','june','july','august','september','october','november','december'];
?>
<table class="tablesaw table-bordered table-hover table" data-tablesaw-mode="swipe"  data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
    <form  method="GET">
        <input type="hidden" name="id" value="<?= $_GET['id'];?>">
        <?php if(isset($_GET['channel_id'])) { ?>
            <input type="hidden" name="channel_id" value="<?= $_GET['channel_id'];?>">
        <?php } ?>
        <?php if(isset($_GET['view_type'])) {  ?>
        <input type="hidden" name="view_type" value="<?= $_GET['view_type'];?>">
        <?php } ?>
        <?php if(isset($_GET['display_view'])) {  ?>
            <input type="hidden" name="display_view" value="<?= $_GET['display_view'];?>">
        <?php } ?>
    <thead>
    <tr>

        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist" class="static">SKU
            <div>
                <input type="text" data-toggle="tooltip" title="<?= isset($_GET['sku']) ? $_GET['sku']:"";?>" style="width: 100%;height:25px" name="sku" value="<?= isset($_GET['sku']) ? $_GET['sku']:"";?>" class="form-control-sm header-filter-inputs">
            </div>
        </th>
        <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist" class="static">Scale
            <div>
                <select style="height:25px" class="form-control-sm header-filter-inputs" name="search-scale">
                    <option value="target" <?= (isset($_GET['search-scale']) && $_GET['search-scale']=='target' ) ? 'selected':'';?>>Target</option>
                    <option value="prior" <?= (isset($_GET['search-scale']) && $_GET['search-scale']=='prior' ) ? 'selected':'';?>>Prior</option>
                </select>
            </div>
        </th>
        <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1">Total
            <div><input type="text" pattern="[0-9/<>=]+" data-toggle="tooltip" title="<?= isset($_GET['search-total']) ? $_GET['search-total']:"";?>" style="width: 80%;height:25px" name="search-total" value="<?= isset($_GET['search-total']) ? $_GET['search-total']:"";?>" class="form-control-sm header-filter-inputs"></div>
        </th>
        <?php for($h=0;$h<12;$h++){ ?>

            <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="<?= $h + 2 ?>">
                <?= substr($month[$h],0,3);?>
                <div>
                    <input type="text" pattern="[0-9/<>=]+" data-toggle="tooltip" title="<?= isset($_GET["search-month-".$month[$h]]) ? $_GET["search-month-".$month[$h]]:""; ?>" name="search-month-<?= $month[$h]; ?>" style="width: 90%;height:25px" value="<?= isset($_GET["search-month-".$month[$h]]) ? $_GET["search-month-".$month[$h]]:""; ?>" class="form-control-sm header-filter-inputs">
                </div>
            </th>

         <?php }  ?>

    </tr>
    </thead>
        <input type="submit" style="display: none;">
    </form>
    <tbody>
    <?php if (isset($records) && !empty($records)) {
        $prefix_prior='_sales'; // previous sales
        $prefix_target='_sales_target'; // target
        $prefix_current='_current_sales'; // current
        $symbol="$ ";
        if(isset($_GET['view_type']) && $_GET['view_type']=="stock"){
            $prefix_prior='_qty_sold';  //previous stock sold
            $prefix_target='_qty_target'; // current target set
            $prefix_current='_current_qty_sold'; // current
            $symbol="";
        }

        $count=1;

        foreach($records as $record) {
            $summary[$record['sku']]=['prior'=>0,'target'=>0,'current'=>0]; // to count total of row and display at first col
            $bar_line[$record['sku']]=['target'=>0,'current'=>0]; // to count total until today date for progress bar
            ?>
            <tr id="parent_row_<?= $count ?>" data-id-pk="<?= $count ?>">
                <td class="title first-col static">
                    <span  data-id-pk='<?= $count; ?>' class="fa fa-plus show_items"></span>
                    <a class="text-muted" href="javascript:void(0)" style="padding-left:10px;color:white" >
                        <?= $record['sku'];?>

                    </a>
                </td>
                <td style="font-weight: bold;" class="static scale">
                    <a>sales Goal</a>

                </td>

                <?php
                    $target_td="";  // target columns
                    $prior_year_td=""; //
                    for($i=0 ; $i<12;$i++) // 12 columns
                    {
                        $summary[$record['sku']]['prior'] +=$record[$month[$i].$prefix_prior]; // sum of all prior year
                        $summary[$record['sku']]['target'] +=$record[$month[$i].$prefix_target];    // sum of all targets
                        $summary[$record['sku']]['current'] +=$record[$month[$i].$prefix_current];    // sum of all current

                        $target_td .="<td>" . $symbol. $record[$month[$i].$prefix_target] ."</td>";

                        $prior_year_td .="<td>" ;
                         $prior_year_td .=  $symbol. $record[$month[$i].$prefix_prior];
                         $prior_year_td .=  "<hr>";
                         $prior_year_td .=  "<b>". $record['markup'] ."</b>";
                        $prior_year_td .="</td>";
                      }

                      ////////////////////////calculate progress bar//////////////////
                        $bar=0; // 0 percent progress
                        if($record['year']!=date('Y'))  // if year is not current
                        {
                            /**
                             * progresss in percent = (total_sales/total_target) * 100
                             */
                               $total_target=$summary[$record['sku']]['target'] > 0 ? $summary[$record['sku']]['target']:1;
                                $bar=($summary[$record['sku']]['current']/$total_target)*100;

                        }
                        if($record['year']==date('Y'))
                        {
                            $month_name=strtolower(date('F'));
                            $current_month=array_search($month_name,$month);
                            for($t=0;$t<=$current_month;$t++)
                            {
                                if($t==$current_month && $record[$month[$t].$prefix_target] > 0 ) {  // calculate
                                    $target_to_add=($record[$month[$t].$prefix_target]/30) * date('d');  // until day of that month
                                } else {
                                    $target_to_add=$record[$month[$t].$prefix_target];
                                }

                                $bar_line[$record['sku']]['target'] +=$target_to_add;
                                $bar_line[$record['sku']]['current'] +=$record[$month[$t].$prefix_current];
                            }
                            $total_target=$bar_line[$record['sku']]['target'] > 0 ? $bar_line[$record['sku']]['target']:1;
                            $bar=($bar_line[$record['sku']]['current'] /$total_target)*100;

                        }

                $bar =(int)$bar;
                $bar_width=$bar <=15 ? 15:$bar;
                        // bar color
                $bar_class='bg-secondary';
                if($bar < 50 )
                    $bar_class='bg-danger';
                if($bar >= 50 )
                    $bar_class='bg-info';
                if($bar >= 90 )
                    $bar_class='bg-success';
                      ////////////////////////////////////////////////////////////////
                      ?>
                <td style="font-weight: bold;">
                     <small class="text-muted" ><?= $summary[$record['sku']]['target'];?> </small>
                </td>
                <?= $target_td; //columns embedded?>
            </tr>

            <!--- toggle row----->

            <tr id="child_row_<?= $count ?>" style="display:none" class="child-row">
                <td class="title first-col static">
                   <!-- <div class="progress m-t-10 m-l-15">
                        <div class="progress-bar bg-info" style="width:55%; height:10px;" role="progressbar"><span style="font-size: 10px">75%</span></div>
                    </div>-->
                 <div class="row">
                        <div class="col-lg-4">
                            <!--<img src="/images/no_image-copy.jpg" alt="IMG" width="60" class="img-thumbnail pull-left">-->
                            <img src="<?=$record['image'] ? $record['image']:'/images/no_image-copy.jpg'; ?>" alt="IMG" width="60" class="img-thumbnail pull-left">
                        </div>

                        <div class="col-lg-8">
                            <span  class="text-muted" data-toggle="tooltip" title=" <?=$record['product_name'];?>"> <?= substr($record['product_name'],0,15)."...";?></span>
                            <div class="progress m-t-5 " style="box-shadow: 2px 2px 2px gray">
                            <div class="progress-bar <?= $bar_class;?>" style="width: <?= $bar_width;?>%; height:10px;" role="progressbar">
                                <span style="font-size: 10px">
                                    <?= $bar;?>%
                                </span>
                            </div>
                        </div>
                        </div>

                    </div>
                </td>
                <td style="font-weight: bold;" class="static scale">
                    Prior Year
                    <hr>

                    <b>% Markup</b>
                </td>
                <td style="font-weight: bold;">
                    <small class="text-muted"><?= $summary[$record['sku']]['prior'];?></small>
                    <hr>
                    <b class="text-muted" > - </b>
                </td>

                <?= $prior_year_td ; // all td ?>


            </tr>

        <?php $count++;}} else {  ?>
        <tr>
            <td colspan="14">
                <h4 style="text-align:center;text-shadow:1px 2px 2px black;color:#90A4AE">
                    No Record Found
                </h4>
            </td>
        </tr>
    <?php } ?>
    </tbody>

</table>
<?php if (isset($records) && !empty($records)) { ?>
    <table class="table-bordered table">

        <tbody>
        <tr>
            <td colspan="14">
                <!----pagination------>
                <?= Yii::$app->controller->renderPartial('../layouts/dt-pagination',['total_records'=>$total_records,'route'=>\Yii::$app->controller->module->requestedRoute])?>
                <!---------->
            </td>
        </tr>
        </tbody>
    </table>
<?php } ?>