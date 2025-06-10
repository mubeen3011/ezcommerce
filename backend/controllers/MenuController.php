<?php

namespace backend\controllers;

use backend\util\HelpUtil;
use Yii;
use common\models\Modules;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

class MenuController extends MainController
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ]
        ];
    }

    public function actionIndex()
    {
        $modules=Modules::find()->asArray()->all();
        $data=Yii::$app->permissionCheck->getSidebarMenu();
        return $this->render('index', [
            'data' =>$data ? $this->make_tree_display($data):NULL,
        ]);
       /* return $this->render('index', [
            'data' =>($modules) ? Yii::$app->permissionCheck->getSidebarMenu():NULL,
        ]);*/
    }

    public function actionUpdateAndSort()
    {
        $request = Yii::$app->request;
        $data=$request->post('data');
        if($data)
        {
            $result=$this->set_menu_position_and_parent($data);  // set display order
            //$this->set_menu_parent($data); // set child parent
            return $this->asJson(['status'=>'success','msg'=>'Rearranged','error'=>$result['error'],'done'=>$result['success']]);
        }

        return $this->asJson(['status'=>'failure','msg'=>'Failed to update']);
    }

    private function set_menu_position_and_parent($data)
    {
        $response=['success'=>0,'error'=>0];
        Modules::updateAll(['display_order'=>999]);  // set all to 999 so that the modules not in menu remains at end
        foreach ($data as $key=>$val)
        {
           $result= Modules::updateAll(['display_order'=>$key+1,'parent_id'=>$val['parent'] ? $val['parent']:NULL ],['id'=>$val['id']]);
            $result ? $response['success']++:$response['error']++;
        }
        return $response;

    }

    private function make_tree_display($data)
    {
        $out=NULL;
        foreach($data as $v){
            $out .= '<li id="'.$v['id'].'">';
            $out .= '<div>'.$v['name'].'</div>';

            if(isset($v['children']) && is_array($v['children'])){
                //  $out .= '<tr>'.self::make_tree_display($v['children']).'</tr>';
                  $out .= '<ul>'.self::make_tree_display($v['children']).'</ul>';
                //$out .= self::make_tree_display($v['children']);
            }
            $out .= '<ul><li style="visibility:hidden"></li></ul>';
            $out .= '</li>';
        }

        return $out;
    }

}
