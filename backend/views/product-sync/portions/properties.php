<div class="card">
    <div class="card-body">
        <div class="vtabs customvtab">
            <ul class="nav nav-tabs tabs-vertical" role="tablist">
                <li class="nav-item"> <a class="nav-link active" data-toggle="tab" href="#common-metadata-tab" role="tab">
                        <span class="hidden-sm-up"><i class="ti-home"></i></span>
                        <span class="hidden-xs-down">Common</span> </a>
                </li>
                <li class="nav-item"> <a class="nav-link " data-toggle="tab" href="#amazon-attributes-tab" role="tab">
                        <span class="hidden-sm-up"><i class="ti-home"></i></span>
                        <span class="hidden-xs-down">Amazon</span> </a>
                </li>

            </ul>
            <!-- Tab panes -->
            <div class="tab-content">
                <div class="tab-pane active" id="common-metadata-tab" role="tabpanel">
                    <div class="pull-right p-1">
                        <a class="btn-sm btn btn-secondary" onclick="display_notice('failure','yet not allowed')"><span class="fa fa-plus"> </span> Add new</a>
                        <!--<button class="btn-sm btn btn-secondary"><span class="fa fa-trash"> </span> Delete Selected</button>-->
                    </div>

                    <div class="table-responsive">

                        <!-------table------->
                        <table id="myTable" class="display nowrap table table-hover table-striped table-bordered dataTable" style=" width:100%;">
                            <thead style="background-color: #ABB0B6;color:white">
                            <tr>

                                <th >Name</th>
                                <th>Value</th>
                                <th>Type</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>

                                <td>Brand</td>
                                <td><input type="text" required value="adidas" name="p360[amazon-attributes][0][brand]" class="form-control form-control-sm"></td>
                                <td>Attribute</td>
                            </tr>
                            <tr>

                                <td>Manufacturer</td>
                                <td><input type="text" required value="adidas" name="p360[amazon-attributes][1][manufacturer]" class="form-control form-control-sm"></td>
                                <td>Attribute</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane  p-20" id="amazon-attributes-tab" role="tabpanel">
                    <div class="pull-right p-1">
                        <a class="btn-sm btn btn-secondary" data-toggle="modal" data-target="#exampleModal"><span class="fa fa-plus"> </span> Add new</a>
                        <!--<button class="btn-sm btn btn-secondary"><span class="fa fa-trash"> </span> Delete Selected</button>-->
                    </div>
                    <div class="table-responsive">

                        <!-------table------->
                        <table id="myTable" class="display nowrap table table-hover table-striped table-bordered dataTable" style=" width:100%;">
                            <thead style="background-color: #ABB0B6;color:white">
                            <tr>
                                <th ></th>
                                <th >Name</th>
                                <th>Value</th>
                                <th>Type</th>
                            </tr>
                            </thead>
                            <tbody id="append-amazon-att-span">
                                <?php
                                $item_type_selected="";
                                if(isset($fields['p360']['amazon-attributes'])):

                                foreach ($fields['p360']['amazon-attributes'] as $att)
                                {
                                    if(array_key_first($att)=="item_type") {
                                        $att_val= array_values($att);
                                        $item_type_selected=$att_val[0];
                                        break;
                                    }

                               } endif; ?>



                              <!------------------>
                                    <tr>
                                        <td><span class="fa fa-trash remove-amazon-att" style="color: gray;"></td>
                                        <td> Item Type</td>
                                        <!--  <td> <input class="form-control form-control-sm" type="text" name="p360[amazon-attributes][<?/*= $att_count++; */?>]['item_type']" value="<?/*= $att_val[0];*/?>">  </td>-->
                                        <td>
                                            <select class="form-control form-control-sm"  name="p360[amazon-attributes][2][item_type]">
                                                <option value="jump-ropes" <?= $item_type_selected=="jump-ropes" ? "selected":"";?>>jump-ropes</option>
                                                <option value="karate-belts" <?= $item_type_selected=="karate-belts" ? "selected":"";?>>karate-belts</option>
                                                <option value="karate-belts" <?= $item_type_selected=="Belts" ? "selected":"";?>>Belts</option>
                                                <option value="Boxing" <?= $item_type_selected=="Boxing" ? "selected":"";?>>Boxing</option>
                                                <option value="boxing-and-martial-arts-hand-wraps" <?= $item_type_selected=="boxing-and-martial-arts-hand-wraps" ? "selected":"";?>>boxing-and-martial-arts-hand-wraps</option>
                                                <option value="multisport-use-mouth-guards" <?= $item_type_selected=="multisport-use-mouth-guards" ? "selected":"";?>>multisport-use-mouth-guards</option>
                                                <option value="boxing-and-martial-arts-protective-gear" <?= $item_type_selected=="boxing-and-martial-arts-protective-gear" ? "selected":"";?>>boxing-and-martial-arts-protective-gear</option>
                                                <option value="boxing-shoes" <?= $item_type_selected=="boxing-shoes" ? "selected":"";?>>boxing-shoes</option>
                                                <option value="bag-gloves" <?= $item_type_selected=="bag-gloves" ? "selected":"";?>>bag-gloves</option>
                                                <option value="bag-boxing-gloves" <?= $item_type_selected=="bag-boxing-gloves" ? "selected":"";?>>bag-boxing-gloves</option>
                                                <option value="karate-uniform-sets" <?= $item_type_selected=="karate-uniform-sets" ? "selected":"";?>>karate-uniform-sets</option>
                                                <option value="martial-arts-training-gloves" <?= $item_type_selected=="martial-arts-training-gloves" ? "selected":"";?>>martial-arts-training-gloves</option>
                                                <option value="boxing-gloves" <?= $item_type_selected=="boxing-gloves" ? "selected":"";?>>boxing-gloves</option>
                                                <option value="protective-gear" <?= $item_type_selected=="protective-gear" ? "selected":"";?>>protective-gear</option>
                                                <option value="training-gloves" <?= $item_type_selected=="training-gloves" ? "selected":"";?>>training-gloves</option>

                                            </select>
                                        </td>
                                        <td>Attribute</td>
                                    </tr>
                              <!------------------>
                               <?php
                               $this->params['$att_count']  =3;
                               if(isset($fields['p360']['amazon-attributes'])):

                                    foreach ($fields['p360']['amazon-attributes'] as $att)
                                    {
                                    if(in_array(array_key_first($att),['brand','manufacturer','item_type']))
                                    continue;
                                    $att_val= array_values($att);
                                    ?>

                                    <tr>
                                        <td><span class="fa fa-trash remove-amazon-att" style="color: gray;"></td>
                                        <td> <?= array_key_first($att);?></td>
                                        <td> <input class="form-control form-control-sm" type="text" name="p360[amazon-attributes][<?=  $this->params['$att_count']++; ?>][<?= array_key_first($att);?>]" value="<?= $att_val[0];?>">  </td>
                                        <td>Attribute</td>
                                    </tr>
                                    <?php  } ?>
                              <!------------------>

                              <?php endif;  ?>

                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>