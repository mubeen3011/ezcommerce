<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 5/22/2018
 * Time: 1:53 PM
 */
include "sdk/LazopSdk.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = "https://auth.lazada.com/rest";
    $appkey = $_POST['app_key'];
    $appSecret = $_POST['app_secret'];
    $accessToken = $_POST['access_token'];
    $action = $_POST['action'];
    $params = $_POST['params'];
    $method = $_POST['method'];
    $c = new LazopClient($url, $appkey, $appSecret);
    $request = new LazopRequest($action,$method);
    foreach ($params as $k=>$v)
    {
        $request->addApiParam($k, $v);
    }
    echo ($c->execute($request,$accessToken));
} else {
    echo "this url only support POST request.";
    die();
}