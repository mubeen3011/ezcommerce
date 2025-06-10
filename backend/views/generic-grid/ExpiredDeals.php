<?php

use common\models\Settings;
//$settings = Settings::find()->where(['name' => 'last_stock_api_update'])->one();
?>
    <style type="text/css">
        .filters-visible{
            display: none;
        }

        .inputs-margin{
            margin-top:15px;
        }
        pre{
            display: none;
        }
        .blockPage{
            border:0px !important;
            background-color: transparent !important;
        }



        input.filter {
            text-align: center;
            font-size: 12px !important;
            font-weight: normal !important;
            color: #007fff;

        }
        .remove-margin-generic-grid{
         margin-bottom: 0px !important;
        }
    </style>
    <div class="row">
        <div id="displayBox" style="display: none;">
            <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
        </div>
        <div class="col-12">
            <div class="card remove-margin-generic-grid">
                <div class="">
                    <div class="">
                        <div id="example23_wrapper" class="dataTables_wrapper">

                            <div id="example23_filter" class="dataTables_filter">
                                    <?php
                                    if(isset($Modal)){
                                        ?>
                                        <!--<a href="javasccript:;" class="dt-button buttons-csv buttons-html5" alt="default" data-toggle="modal" data-target="#myModal" id="import" tabindex="0" aria-controls="example23"><i class="mdi mdi-upload"></i> Import</a>-->
                                        <button type="button" class=" btn btn-info" data-toggle="modal" data-target="#myModal" data-whatever="@mdo">
                                            <i class="mdi mdi-upload"></i> Import
                                        </button>
                                        <?php
                                    }
                                    ?>

                                <!--<button class="btn btn-info pull-right" style="    padding: 1px;
    margin-top: 1px;margin-right: 5px;float: left;"  data-toggle="modal" data-target="#exampleModal" data-whatever="@mdo">Advanced Filters</button>-->
                                <button type="button" id="export-table-csv" class=" btn btn-info" >
                                    <i class="fa fa-download"></i> Export
                                </button>
                                <button type="button" class=" btn btn-info" id="filters">
                                    <i class="fa fa-filter"></i>
                                </button>
                                <a href="javascript:;" class=" btn btn-info  clear-filters hide" id="filters">
                                    <i class="fa fa-filter"></i>
                                </a>


                                <div class="dt-buttons margin-right-filters-section" style="float: left;">
                                    <!--<a class="dt-button buttons-csv buttons-html5" id="export" tabindex="0" aria-controls="example23" href="javasccript:;">
                                        <span>Download CSV</span>
                                    </a>-->

                                    <!--<a href="javasccript:;" class="dt-button buttons-csv buttons-html5" id="export" tabindex="0" aria-controls="example23"><i class="mdi mdi-download"></i> Export</a>-->
                                </div>
                                <label class="form-inline hidden-sm-down" style="float: right;display: none;">

                                    <select id="records_per_page" style="height: 25px;" class=" input-sm">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="75">75</option>
                                        <option value="100">100</option>
                                    </select>

                                </label>


                            </div>
                            <table id="tablesaw-datatable" class="export-csv generic-table tablesaw table-bordered table-hover table tablesaw-swipe tablesaw-sortable" data-tablesaw-mode="swipe" data-tablesaw-mode-exclude="stack" data-tablesaw-sortable="" data-tablesaw-minimap="" data-tablesaw-mode-switch="">
                                <thead class="" id="generic-thead">

                                <tr role="row">
                                    
                                    <?php
                                    $counter=1;
                                    
                                    foreach ( $config['thead'] as $key=>$value ){
                                        if( isset($value['visibility']) &&  $value['visibility']==false){
                                            echo '<th tabindex="0" aria-controls="example23" rowspan="1" colspan="1"></th>';
                                            continue;
                                        }
                                        if( $counter ==1 ) {
                                            $persist = 'data-tablesaw-priority="persist"';
                                        }else{
                                            $persist = '';
                                        }
                                        ?>
                                        <th <?=$persist?> scope="col" data-tablesaw-priority="<?=$counter?>" class="min-th-width footable-sortable tablesaw-sortable-ascending" ><?=$key?>
                                            <i class="sort-arrows fa fa-sort sort sorting" data-field="<?=$value['data-field']?>" data-sort="desc"></i>
                                            <br />
                                            <?php
                                            $inputtype  = $value['input-type'];
                                            if( $inputtype=='text' ){
                                            ?>
                                            <input type="<?=$inputtype?>" data-filter-field="<?=$value['data-filter-field']?>" data-filter-type="<?=$value['data-filter-type']?>"
                                                    class=" inputs-margin filters-visible filter form-control <?=$value['input-type-class']?>">
                                                <?php
                                            }elseif ( $inputtype=='hidden' ){
                                                continue;
                                            }
                                            else if( $inputtype=='select' ){
                                                if( $value['data-filter-type']=='like' ){
                                                    $sign='';
                                                }else{
                                                    $sign='=';
                                                }
                                                ?>
                                            <select data-filter-field="<?=$value['data-filter-field']?>" data-filter-type="<?=$value['data-filter-type']?>"
                                                    class=" inputs-margin filters-visible filter form-control select-filter">
                                                <option></option>
                                                <?php
                                                foreach ( $value['options'] as $valuees ){
                                                    ?>
                                                    <option value="<?=$sign.$valuees['key']?>"><?=$valuees['value']?></option>
                                                    <?php
                                                }
                                                ?>
                                            </select>
                                            <?php
                                            }
                                            ?>
                                        </th>
                                        <?php
                                        $counter++;
                                    }
                                    ?>
                                </tr>
                                </thead>
                            </table>



                        </div>
                    </div>
                </div>

            </div>
        </div>


        <script type="text/javascript">
            var defaultUrl = '<?=$config['UrlSetting']['defualtUrl']?>';
            var sortUrl = '<?=$config['UrlSetting']['sortUrl']?>';
            var filterUrl = '<?=$config['UrlSetting']['filterUrl']?>';
            var jsUrl = '<?=$config['UrlSetting']['jsUrl']?>';
            var pdqs = '<?= isset($_GET['pdqs']) ? $_GET['pdqs'] : '0' ?>';
            var pageName = '<?= isset($config['UrlSetting']['pageName']) ? $config['UrlSetting']['pageName'] : '' ?>';
        </script>
    </div>

<?php

$this->registerJs("
    ");

?>