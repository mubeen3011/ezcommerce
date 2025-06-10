<?php
    if ( $page_no != 'All' ){


?>
<div id="pagination-footer">
    <div class="dataTables_info remove_when_pagination_used" id="example23_info" role="status" aria-live="polite" style="float: left;">
        <?php
        $max_limit_showing = $page_no * $records_per_pages;
        $min_limit_showing = $max_limit_showing - $records_per_pages;

        $max_page= ceil($total_records/$records_per_pages);
        $page = $page_no;
        $reverse = $page - 1;
        if( $reverse < 1 ){
            $reverse = 1;
        }
        $forward = $page + 1;
        if( $forward > $max_page ){
            $forward  = $max_page;
        }
        ?>
        Showing <?=$min_limit_showing?> to <?=$max_limit_showing?>
    </div>
    <nav aria-label="..." class="remove_when_pagination_used">
        <ul class="pagination pull-right">
            <?php
            if($page_no>1){
                ?>
                <li class="page-item paginate_button">
                    <a class="page-link" href="javascript:;" tabindex="-1" onclick="PageNo(<?=$page_no-1?>)">Previous</a>
                </li>
                <?php
            }
            ?>

            <?php
            $page_counter_no = $page_no;
            for ( $i=$reverse;$i<=$forward;$i++ ){
                ?>
                <li class="page-item <?php if( $i==$page_no ){echo 'active';} ?>"><a class="page-link" href="javascript:;" onclick="PageNo(<?=$i?>)"><?=$i?></a></li>
                <?php
            }
            ?>
            <?php
            if( $page_no != $max_page ){
                ?>

                <li class="page-item <?php if( $pagination_pages==$page_no ){echo 'disabled';} ?>">
                    <a class="page-link" href="javascript:;" onclick="PageNo(<?=$page_no+1?>)">Next</a>
                </li>
                <?php
            }
            ?>
        </ul>
    </nav>
    <br />
    <a href="javascript:;" onclick="PageNo('All')">Show All Records</a>
</div>
<?php
    }