<?php
use yii\web\View;
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales', 'url' => ['/sales/dashboard']];
$this->params['breadcrumbs'][] = 'Sales Targets';
if(isset($channels) && !empty($channels))
{
    $available_marketplaces=array_column($channels,'marketplace');
    $available_marketplaces=array_unique($available_marketplaces);
}

?>
<!---css file----->
<style>
   .sub_level_span .form-group
    {
       margin:1%;
    }
   .sub_level_span .first-input-group
   {
     /*  width:170px;*/
       background: transparent;
   }
   .first-input-group
   {
       width:170px;
   }
   .sub_level_span
   {
       max-height:300px;
       overflow-y:scroll;
   }
   .sub_level_span::-webkit-scrollbar-thumb {
       background: #CFD2D5;
       border-radius: 10px;
   }
</style>
<link href="/../css/sales_v1.css" rel="stylesheet">

<div class="row">
    <div class="col-lg-12">
        <div class="card" >
            <div class="card-body" >
                <div class="panel panel-default">
                    <?= \yii\widgets\Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                    <!--<h5>Sales Targets <button class="btn btn-info btn-sm"><span class="fa fa-plus"></span> Target</button></h5>-->
                     <button type="button" class="btn btn-outline-info btn-sm create-target-btn"><i class="fa fa-plus"></i> Create Target</button>
                </div>

            </div>


        </div>
    </div>
</div>
<!----------form---------->
<div class="create-form-div" style="display:none">
    <div class="card">

        <div class="card-body">
           <!-- <h4 class="card-title">Create Target</h4>-->
            <form class="form-horizontal p-t-20 create_target_form" action="/sales/create-sale-target" method="post">
            <div class="row">

                <div class="col-lg-5">

                <div class="form-group row">
                    <div class="col-sm-12">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                    <span class="input-group-text first-input-group" id="basic-addon1">
                                        <i class="ti-calendar"> Year</i>
                                    </span>
                            </div>
                            <select class="form-control" name="prior_year">
                                <option value=""> -- Select Prior Year--</option>
                                <option value="<?= date('Y') -1;?>"><?= date('Y') -1;?></option>
                                <option value="<?= date('Y') -2;?>"><?= date('Y') -2;?></option>
                                <option value="<?= date('Y') -3;?>"><?= date('Y') -3;?></option>
                            </select>
                        </div>
                    </div>



                </div>


                <div class="form-group row">
                    <div class="col-sm-12">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                    <span class="input-group-text first-input-group" id="basic-addon1">
                                        <i class="ti-settings"> Based on</i>
                                    </span>
                            </div>
                            <select class="form-control cal-type-dd" name="calculation_type">
                                <option value=""> -- Select Prior Mapping--</option>
                                <option value="year">Year</option>
                                <option value="quarter">Quarter</option>
                                <option value="month">Monthly</option>
                            </select>
                        </div>
                    </div>

                </div>

                <!------------------>
                <span class="cal_subtype_span"></span>
                <!------------------>
               <div class="form-group row">
                    <div class="col-sm-12">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                    <span class="input-group-text first-input-group" id="basic-addon1">
                                        <i class="ti-shopping-cart"> Apply By </i>
                                    </span>
                            </div>
                            <select class="form-control level_dd" name="apply_to">
                                <option value=""> -- Select level--</option>
                                <option value="marketplace">Marketplace wise</option>
                                <option value="channel">Channel wise</option>
                                <option value="category">Category wise</option>
                                <option value="overall">Overall</option>
                            </select>
                        </div>
                    </div>
                </div>
                <!------------------>

                <div class="form-group row m-b-0">
                    <div class="col-sm-9">

                        <button type="submit" class="btn btn-success waves-effect waves-light btn-sm btn_submit_target">Submit</button>

                    </div>
                </div>

        </div>
                <div class="col-lg-5 offset-lg-1 sub_level_span">

                    <img src="/images/graph.jpg"  style="opacity:0.2;padding-left:15%">
                </div>


        </div>
            </form>
</div>
</div>
</div>
<!----------------------->
<div class="listing-page">
    <!--------status options----->
    <?= Yii::$app->controller->renderPartial('targets/records-view',$_params_); ?>
</div>
<?php
$available_marketplaces=json_encode($available_marketplaces);
$channels=json_encode($channels);
$categories=json_encode($categories);
$this->registerJsFile('monster-admin/assets/plugins/tablesaw-master/dist/tablesaw.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('monster-admin/assets/plugins/tablesaw-master/dist/tablesaw-init.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJs(<<< EOT_JS_CODE
var available_marketplaces=$available_marketplaces;
var channels=$channels;
var categories=$categories;
//// for btn toggle display target detail
$(function(){
    $('.show_items').on('click',function(){
        let target_id_pk=$(this).attr('data-target-id-pk');
        $('#target-items-record-' + target_id_pk).toggle();
    });
 
});
$('.create-target-btn').click(function(){
$('.create-form-div').toggle();
});

/// if target type changed
$('.cal-type-dd').on('change',function(){
let target_type=$(this).val();
if(target_type =='quarter')
    populate_target_quarter_type();
else
   $('.cal_subtype_span').html(''); 
});

function populate_target_quarter_type()
{
    let html='<div class="form-group row">';
    html +='<div class="col-sm-12">';
     html +='<div class="input-group">';
     html +='<div class="input-group-prepend">';
            html +='<span class="input-group-text" id="basic-addon1"> <i class="ti-dashboard"> Quarter</i></span>';
        html +='</div>';
         html +='<select class="form-control" name="calculation_subtype">';
         html +='<option value="first">First Quarter</option>';
         html +='<option value="second">Second Quarter</option>';
         html +='<option value="third">Third Quarter</option>';
         html +='<option value="fourth">Fourth Quarter</option>';
         html +='</select>';
         html +='</div>';
         html +='</div>';
        html +='</div>';
      html +='</div>';
            
       $('.cal_subtype_span').append(html);     
} 

////////////////////////////////////////////////////////////
//if level/apply to changed
 $('.level_dd').change(function(){
 let html="";
 let level_type=$(this).val();
 if(level_type=='marketplace') {
   html=make_marketplaces_html(); // make html
   populate_sub_level_span(html); // append it to span
 }
    
 if(level_type=='channel') {
    html=make_channels_html();
    populate_sub_level_span(html); // append it to span
 }
 
  if(level_type=='overall') {
    html=make_overall_markup_html();
    populate_sub_level_span(html); // append it to span
 }
 
 if(level_type=='category') {
    html=make_category_html();
    populate_sub_level_span(html); // append it to span
 }
 });
 
 function populate_sub_level_span(html)
 {
    $('.sub_level_span').html(html);
 }

function make_marketplaces_html()
{
    let html="";
    available_marketplaces.forEach(function (item, index) {
            html +='<div class="form-group row">';
              html +='<div class="col-sm-12">';
                  html +='<div class="input-group">';
                 html +='<div class="input-group-prepend ">';
                 html +='<span class="input-group-text first-input-group" id="basic-addon1">';
                 html +='<i class="ti-control-record"> ' + item + '</i>';
                 html +='</span>';
           html +='</div>';
              html +=' <input type="number" name="markup[]" required min="1" step=".5" class="form-control" placeholder="Markup">';
              html +=' <input type="hidden" name="markup_for_name[]" required  class="form-control" value="' + item + '">';
           html +=' <div class="input-group-prepend">';
               html +='<span class="input-group-text" id="basic-addon1">';
               html +='<i class=""> %</i>';
               html +='</span>';
             html +='</div>';
             html +='</div>';
           html +='</div>';
        html +='</div>';
        });
   
    return html;
}


function make_channels_html()
{
    let html="";
   channels.forEach(function (item, index) {
            html +='<div class="form-group row">';
              html +='<div class="col-sm-12">';
                  html +='<div class="input-group">';
                 html +='<div class="input-group-prepend">';
                 html +='<span class="input-group-text first-input-group" id="basic-addon1">';
                 html +='<i class="ti-control-record"> ' + item.name + '</i>';
                 html +='</span>';
           html +='</div>';
              html +=' <input type="number" name="markup[]" required min="1" step=".5" class="form-control" placeholder="Markup">';
              html +=' <input type="hidden" name="markup_for_name[]" required  class="form-control" value="' + item.name + '">';
              html +=' <input type="hidden" name="markup_for_id[]" required  class="form-control" value="' + item.id + '">';
           html +=' <div class="input-group-prepend">';
               html +='<span class="input-group-text" id="basic-addon1">';
               html +='<i class=""> %</i>';
               html +='</span>';
             html +='</div>';
             html +='</div>';
           html +='</div>';
        html +='</div>';
        });
   
    return html;
}

function make_category_html()
{
    let html="";
    if(!categories)
        return;
        
   categories.forEach(function (item, index) {
            html +='<div class="form-group row">';
              html +='<div class="col-sm-12">';
                  html +='<div class="input-group">';
                 html +='<div class="input-group-prepend">';
                 html +='<span class="input-group-text first-input-group" id="basic-addon1">';
                 html +='<i class="ti-control-record"> ' + item.name + '</i>';
                 html +='</span>';
           html +='</div>';
              html +=' <input type="number" name="markup[]" required min="1" step=".5" class="form-control" placeholder="Markup">';
              html +=' <input type="hidden" name="markup_for_name[]" required  class="form-control" value="' + item.name + '">';
              html +=' <input type="hidden" name="markup_for_id[]" required  class="form-control" value="' + item.id + '">';
           html +=' <div class="input-group-prepend">';
               html +='<span class="input-group-text" id="basic-addon1">';
               html +='<i class=""> %</i>';
               html +='</span>';
             html +='</div>';
             html +='</div>';
           html +='</div>';
        html +='</div>';
        });
   
    return html;
}

function make_overall_markup_html()
{
    
            let html='<div class="form-group row">';
              html +='<div class="col-sm-12">';
                  html +='<div class="input-group">';
                 html +='<div class="input-group-prepend">';
                 html +='<span class="input-group-text first-input-group" id="basic-addon1">';
                 html +='<i class="ti-bar-chart"> Markup </i>';
                 html +='</span>';
           html +='</div>';
              html +=' <input type="number" name="markup" required min="1" step=".5" class="form-control" placeholder="Markup">';
           html +=' <div class="input-group-prepend">';
               html +='<span class="input-group-text" id="basic-addon1">';
               html +='<i class=""> %</i>';
               html +='</span>';
             html +='</div>';
             html +='</div>';
           html +='</div>';
        html +='</div>';
        
   
    return html;
}
////////////////////////////////// form submission///////////////////
$('.create_target_form').on('submit',function(e){
e.preventDefault();
var submit_btn=".btn_submit_target";
$.ajax({
        type: "POST",
        url: $(this).attr('action'),
        data: $(this).serialize(),
        dataType: 'json',
        beforeSend: function(){
            $(submit_btn).html("<span class='fa fa-spinner fa-spin'></span> Wait Long queue of skus in progress...");
            $(submit_btn).attr("disabled", true);
        },
        success: function(msg){
            if(msg.status=="success") {
                location.href='/sales/target-detail/?id=' + msg.target_id ;
            } else {
                display_notice('failure',msg.msg);
            }
            $(submit_btn).html('Submit');
            $(submit_btn).removeAttr("disabled");

        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            $(submit_btn).html('Submit');
            $(submit_btn).removeAttr("disabled");
            display_notice('failure',errorThrown);
        }
    });
});
EOT_JS_CODE
);
?>
