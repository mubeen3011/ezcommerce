<?php
namespace app\components;


use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

class PermissionComponent extends Component
{


    public function check_logged_in_user_authorization($module)
    {
      //  print_r($module); die();
        $roleId = Yii::$app->user->identity->role_id;
        if($roleId ==1)  // super admin has all access
            return true;

        return self::is_user_authorized_to_module($module,$roleId); // true false;

    }



    private function get_user_permissions($roleId)
    {
        $result=array();
        $permissions=\common\models\Permissions::find()
            ->select('modules.id,modules.parent_id,modules.name,modules.id,modules.controller_id,modules.view_id,modules.update_id,modules.create_id,modules.delete_id,modules.allowed_view_ids,modules.allowed_update_ids,modules.allowed_create_ids,modules.allowed_delete_ids,permissions.create,permissions.update,permissions.view,permissions.delete')
            ->innerJoin('modules', '`modules`.`id` = `permissions`.`module_id`')
            ->orderBy(['modules.display_order'=>SORT_ASC])
            ->where(['permissions.role_id'=>$roleId,/*'modules.visibility'=>1*/])->asArray()->all();
        if($permissions)
        {
            foreach($permissions as $permission)
            {
                $controller_name=str_replace('/','',$permission['controller_id']);
                $result[]=
                        [
                            'module'=>$permission['name'],
                            'view'=>(isset($permission['view']) && $permission['view']) ?  $controller_name. $permission['view_id']:NULL,
                            'create'=>(isset($permission['create']) && $permission['create']) ? $controller_name . $permission['create_id']:NULL,
                            'update'=>(isset($permission['update']) && $permission['update']) ? $controller_name  . $permission['update_id']:NULL,
                            'delete'=>(isset($permission['delete']) && $permission['delete']) ? $controller_name  . $permission['delete_id']:NULL,
                            'allowed_view_urls'=>(isset($permission['view']) && $permission['view'] && $permission['allowed_view_ids']) ?  json_decode($permission['allowed_view_ids']):NULL,
                            'allowed_update_urls'=>(isset($permission['update']) && $permission['update'] && $permission['allowed_update_ids']) ?  json_decode($permission['allowed_update_ids']):NULL,
                            'allowed_create_urls'=>(isset($permission['create']) && $permission['create'] && $permission['allowed_create_ids']) ?  json_decode($permission['allowed_create_ids']):NULL,
                            'allowed_delete_urls'=>(isset($permission['delete']) && $permission['delete'] && $permission['allowed_delete_ids']) ?  json_decode($permission['allowed_delete_ids']):NULL,
                            'global_allowed_urls'=>[$controller_name . "/" . 'generic-info', $controller_name . "/" . 'generic-info-filter'],
                        ];
            }
        }
        //echo "<pre>";
      // print_r($result); die();
        return $result;

    }

    private function is_user_authorized_to_module($module,$roleId)
    {
        $permissions=self::get_user_permissions($roleId);

       // print_r($permissions); die();
        if($permissions)
        {
            $allowed_view_ids=array_filter(array_column($permissions, 'allowed_view_urls'));  // remove indexes having null values and get others
            $allowed_update_ids=array_filter(array_column($permissions, 'allowed_update_urls'));
            $allowed_create_ids=array_filter(array_column($permissions, 'allowed_create_urls'));
            $allowed_delete_ids=array_filter(array_column($permissions, 'allowed_delete_urls'));

            if(in_array($module, array_column($permissions, 'view'))) {
                 return true;
            } else if(in_array($module, array_column($permissions, 'create'))) {
                return true;
            } else if(in_array($module, array_column($permissions, 'update'))) {
                return true;
            }  else if(in_array($module, array_column($permissions, 'delete'))) {
                return true;
            } else if(in_array($module, array_reduce($allowed_view_ids,'array_merge', array()))) {
                return true;
            } else if(in_array($module, array_reduce($allowed_update_ids,'array_merge', array()))) {
                return true;
            } else if(in_array($module, array_reduce($allowed_create_ids,'array_merge', array()))) {
                return true;
            } else if(in_array($module, array_reduce($allowed_delete_ids,'array_merge', array()))) {
                return true;
            } else if(in_array($module,array_reduce(array_column($permissions, 'global_allowed_urls'), 'array_merge', array()))) {
                return true;
            }
        }
        return false;

    }

    public static function getSidebarMenu()
    {
        $roleId = Yii::$app->user->identity->role_id;
        $user_menu=\common\models\Permissions::find()->select('modules.id,modules.parent_id,modules.name,modules.icon,modules.id,modules.controller_id,modules.view_id,modules.params,modules.visibility,permissions.create,permissions.update,permissions.view,permissions.delete')
            ->innerJoin('modules', '`modules`.`id` = `permissions`.`module_id`')
            ->orderBy(['modules.display_order'=>SORT_ASC])
            ->where(['permissions.role_id'=>$roleId,'modules.visibility'=>1,'modules.menu_position'=>'sidebar'])->asArray()->all();
        return self::make_module_tree($user_menu);
    }

    public  static function make_module_tree(array $elements, $parentId = 0)
    {
        $result = array();

        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = self::make_module_tree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $result[] = $element;
            }
        }
        // print_r($result); die();
        return $result;
    }
}