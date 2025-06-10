<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 5/22/2018
 * Time: 11:41 AM
 */
include "sdk/LazopSdk.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = "https://auth.lazada.com/rest";
    $appkey = $_POST['app_key'];
    $appSecret = $_POST['app_secret'];
    $refreshToken = $_POST['refresh_token'];
    $c = new LazopClient($url, $appkey, $appSecret);
    $request = new LazopRequest('/auth/token/refresh');
    $request->addApiParam('refresh_token', $refreshToken);
    echo ($c->execute($request));
} else {
    echo "this url only support POST request.";
    die();
}