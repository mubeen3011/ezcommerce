<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 8/27/2019
 * Time: 11:24 AM
 */
?>
<?php
$_GET['page']=isset($_GET['page']) ? $_GET['page']:"1";
$_GET['record_per_page'] = isset($_GET['record_per_page']) ? $_GET['record_per_page']:"10";
$record_per_page = $_GET['record_per_page'];
//$route;
if ($_GET['page']!='All' && $total_records >10){
?>
    <br>
   <nav aria-label="..." class="remove_when_pagination_used">

        <ul class="pagination pull-right">
            <?php
            $Totalrec = ceil($total_records / $record_per_page);
            $page = $_GET['page'];
            $reverse = $page - 1;
            $previsou=0;
            if( $reverse < 1 ){
                $reverse = 1;
            }
            if( $_GET['page']>=2 ){
                $prevId=$reverse;
                $prevId = $_GET['page']-1;
                $previsou = 1;
            }
            $forward = $page + 1;
            if( $forward > $Totalrec ){
                $forward  = $Totalrec-1;
            }
            if( $_GET['page']<$Totalrec-1 ){
                $forId=$forward;
                $forId = $_GET['page'] + 1;
                $forwardou=1;
            }else{
                $forwardou=0;
            }
            $previsou = $_GET['page']-1;
            /*if($previsou){
                $_GET['page']=$previsou;
                //   print_r(http_build_query($_GET)); die();
                */?>
                <!--<a href="/subsidy/skus?<?/*=http_build_query($_GET).'&page='.$prevId*/?>'" class="paginate_button next" aria-controls="example23" data-dt-idx="7" tabindex="0" id="example23_next">
                                    Prev
                                </a>-->
                <!--<li class="page-item paginate_button">
                    <a class="page-link" href="/<?/*=$route*/?>?<?/*=http_build_query($_GET)*/?>" tabindex="-1">Previous</a>
                </li>-->
               <!-- --><?php
/*            }*/
            if($_GET['page'] == 1){
                $_GET['page']=1;
                //   print_r(http_build_query($_GET)); die();
                ?>
                <!--<a href="/subsidy/skus?<?/*=http_build_query($_GET).'&page='.$prevId*/?>'" class="paginate_button next" aria-controls="example23" data-dt-idx="7" tabindex="0" id="example23_next">
                                    Prev
                                </a>-->
                <li class="page-item paginate_button">
                    <a class="page-link" href="?view=skus&page=<?php echo $_GET['page'] ; ?>&record_per_page=<?php echo $record_per_page; ?>" tabindex="-1">Start</a>
                </li>
                <?php
            }
            else
            {
                $_GET['page']=1;
             ?>
                <li class="page-item paginate_button">
                    <a class="page-link" href="?view=skus&page=<?php echo $_GET['page']; ?>&record_per_page=<?= $record_per_page."&".http_build_query($_GET);; ?>" tabindex="-1">Start</a>
                </li>
             <?php
            }
            for ( $i = $reverse; $i<=$forward; $i++ ){
                ?>
                <li class="page-item <?php if( $i == $page ){echo 'active';} ?>">
                    <?php  $_GET['page']= $i; ?>
                    <a class="page-link" href="/<?=$route?>?<?=http_build_query($_GET)?>">
                        <?=$i?>
                    </a>
                </li>
                <?php
            }
         /*   if($forwardou){
                $_GET['page']=$forId;
                */?>
                <li class="page-item">
                    <?php
                            if($_GET['page'] === $Totalrec )
                            {
                                $_GET['page'] = $Totalrec;
                            }
                            else
                            {
                                $_GET['page'] = $Totalrec;
                            }
                    ?>
                    <a class="page-link" href="?view=skus&page=<?php echo  $_GET['page']; ?>&record_per_page=<?= $record_per_page."&".http_build_query($_GET); ?>">End</a>
                </li>
               <!-- --><?php
/*            }
            */?>
        </ul>
        <?php /*$_GET['page']='All'*/?><!--
        <a href="/<?/*=$route*/?>?<?/*=http_build_query($_GET)*/?>">Show All Records</a>
        <br>-->
       <br>
       <?php

       $max_limit_showing = $page * $record_per_page;

       $min_limit_showing = $max_limit_showing - $record_per_page;

       ?>
       <p  class="record-per-page-label" style="float: left; ">Showing <?= $min_limit_showing ?> to <?= $max_limit_showing ?></p>
        <label class="form-inline hidden-sm-down record-per-page-label" style="float: left;  margin-left: 15px; margin-top:-3px;">

           <!-- <select id="record_per_page"   onchange="records_per_page()" style="height: 25px;" class=" input-sm">
                <a href="something.php"><option  value="50">50</option></a>
                <option value="100">100</option>
                <option value="200">200</option>
                <option  value="500">500</option>
                <option  value="1000">1000</option>
            </select>-->
            <?php
                    $_GET['page'] = $page;
            ?>
              <select name="forma" onchange="location = this.value;" class="form-control form-control-sm">
                <option <?php if ($record_per_page == 10) { echo "selected"; } ?> value="?view=skus&<?= http_build_query($_GET)."&".$page; ?>&record_per_page=10">10</option>
                <option <?php if ($record_per_page == 20) { echo "selected"; } ?> value="?view=skus&<?= http_build_query($_GET)."&".$page;; ?>&record_per_page=20">20</option>
                <option <?php if ($record_per_page == 50) { echo "selected"; } ?> value="?view=skus&<?= http_build_query($_GET)."&".$page; ?>&record_per_page=50">50</option>
                <option <?php if ($record_per_page == 100) { echo "selected"; } ?> value="?view=skus&<?= http_build_query($_GET)."&".$page; ?>&record_per_page=100">100</option>
                  <option <?php if ($record_per_page == 100) { echo "selected"; } ?> value="?view=skus&<?= http_build_query($_GET)."&".$page; ?>&record_per_page=500">500</option>

            </select>
        </label>
       <br>
        <p style="clear:both;"></p>
       <p class="pagination-total-record-span">Total Records : <?php echo $total_records ?></p>
    </nav>
    <?php
}
?>

