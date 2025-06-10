<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 5/22/2018
 * Time: 10:39 AM
 */
include('database.php');
include "sdk/LazopSdk.php";
// lazada blip
$url = "https://auth.lazada.com/rest";
$appKey = "102471";
$appSecret = "Mb5eohdsv5znq8xRyfLdTKqmfyCvtJN9";
$code = $_REQUEST['code'];
$c = new LazopClient($url,$appKey,$appSecret);
$request = new LazopRequest('/auth/token/create');
$request->addApiParam('code',$code);
$auth_params = $c->execute($request);
$sql = "UPDATE aoa_philips_official_store.channels set auth_params = '{$auth_params}' where id = '1'";
$result = mysqli_query ($conn,$sql) or die(mysqli_error($conn));