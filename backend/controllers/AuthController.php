<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 2/7/2020
 * Time: 10:57 AM
 */

namespace backend\controllers;

use common\models\Channels;

class AuthController extends MainController{

    public function actionLazada(){
        $channel = $this->LazadaAuthValidation();
        if ($channel->marketplace=='lazada'){
            include_once ('../web/lzdop/sdk/LazopSdk.php');
            $code = $_REQUEST['code'];
            $c = new \LazopClient($channel->api_url,$channel->api_key,$channel->api_user);
            $request = new \LazopRequest('/auth/token/create');
            $request->addApiParam('code',$code);
            $auth_params = $c->execute($request);
            //$this->debug($auth_params);
            $UpdateAuth = Channels::findOne($channel->id);
            $UpdateAuth->auth_params = $auth_params;
            $UpdateAuth->update();
            $token = json_decode($auth_params,1);
            if (isset($token['access_token'])){
                echo 'Successfully generated new token';
            }
            if (empty($UpdateAuth->errors)){
                echo '<br />Tokens successfully updated to auth_params column of channel.';
            }
        }
    }
    private function LazadaAuthValidation(){
        if (!isset($_GET['channel_name'])){
            echo 'Please set the channel name in url for exmapl : &channel_name=Deals4All';
            die;
        }
        if ( !isset($_REQUEST['code']) ){
            echo 'code parameter undefined. <br />';
            echo 'You cannot access this url directly. If you want to generate new tokens for lazada please go to https://open.lazada.com/ <br />
                Go into APP CONSOLE <br/>
                Go into API Explorer<br />
                Click on Get Token<br />
                It will redirect you to the new tab <br />
                Enter your username and password. After that lazada will redirect you to the current link with $_REQUEST["code"] parameter <br />
                Thank you';
            die;
        }
        $channel = Channels::find()->where(['name'=>$_GET['channel_name']])->one();
        if (empty($channel)){
            echo 'There is no channel exist with the name '.$_GET['channel_name'];
            die;
        }else if ( $channel->marketplace!='lazada' ){
            echo 'Channel exist but marketplace is not lazada. We are getting '.$channel->marketplace.' with this channel name';
            die;
        }else{
            return $channel;
        }
    }

}