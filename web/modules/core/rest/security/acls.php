<?php

namespace core;

class ACLsSecurityREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'security/acl';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems": {
        $acls = new \security\ACLs();
        $objects = array();
        foreach($acls as $id => $data) {
          if(self::checkEffectivePriv(self::getServiceLocation(), $id, 'settings_reader')) $objects[]=(object)array('id' => $id, 'title' => $data->title, 'readonly' => !self::checkEffectivePriv(self::getServiceLocation(), $id, 'settings_writer'));
        }
        $result = self::returnResult($objects);
        unset($acls);
      } break;
      case "get": {
        if(isset($request_data->id)) {
          $acl = new \security\ACL($request_data->id);
          $profile = $acl->cast();
          $profile->readonly = !self::checkEffectivePriv(self::getServiceLocation(), $profile->id, 'settings_writer');
          $result = self::returnResult($profile);
          unset($acl);
        }
        //   // if($profile->deny == null) $profile->deny = array();
        //   // if($profile->permit == null) $profile->permit = array();
        //   // foreach($profile->permit as $key => $val) {
        //   //   $parts = explode('/', $val);
        //   //   if(strpos($parts[1],'.')!==false) $parts[1]=self::mask2cidr($parts[1]);
        //   //   $profile->permit[$key] = implode('/',$parts);
        //   // }
      } break;
      case "set": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_writer')) {
          $acl = new \security\ACL($request_data->id);
          $acl->assign($request_data);
          if ($acl->save()) {
            $acl->reload();
            $result = self::returnResult((object)array('id' => $acl->id));
          } else {
            $result = self::returnError('danger', 'Не удалось сохранить фильтр');
          }
          unset($acl);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
          
          // $profiledata = self::getPermissions($request_data->id);
          // $haspriv = false;
          // if($profiledata !== null) {
          //   $haspriv = self::checkEffectivePriv('security_role', $profiledata->role, 'security_writer');
          // } else {
          //   $haspriv = true;
          // }
          // if($haspriv&&
          //    (!isset($request_data->role)||
          //     (isset($request_data->role)&&self::checkEffectivePriv('security_role', $request_data->role, 'security_writer'))
          //    )
          //   ) {
          //   $id = $request_data->id;
          //   $ini = self::getINI('/etc/asterisk/acl.conf');
          //   if(isset($request_data->orig_id)&&($request_data->orig_id!='')&&($request_data->orig_id!=$request_data->id)) {
          //     $orig_id = $request_data->orig_id;
          //     if(isset($ini->$id)) {
          //       $result = self::returnError('danger', 'Фильтр уже существует');
          //       break;
          //     }
          //     if(isset($ini->$orig_id))
          //       unset($ini->$orig_id);
          //   }
          //   if((!isset($request_data->orig_id))||$request_data->orig_id=='') {
          //     if(isset($ini->$id)) {
          //       $result = self::returnError('danger', 'Фильтр уже существует');
          //       break;
          //     }
          //   }
          //   $profile = new \stdClass();
          //     $profile->permit = $request_data->permit; 
          //     $profile->deny = $request_data->deny;            
            
          //   if(isset($request_data->title)) $ini->$id->setComment($request_data->title);
          //   $ini->$id->setDefaults($generalparams, $profile);
          //   $ini->save();
          //   $result = self::returnSuccess();
          //   $this->reloadConfig();
          // } else {
          //   $result = self::returnError('danger', 'Доступ запрещён');
          // }
        
      } break;
      case "add": {
        if((empty($request_data->id)||($request_data->id == 'false'))&&self::checkPriv('settings_writer')) {
          $acl = new \security\ACL();
          $acl->assign($request_data);
          if ($acl->save()) {
            $acl->reload();
            $result = self::returnResult((object)array('id' => $acl->id));
          } else {
            $result = self::returnError('danger', 'Не удалось сохранить фильтр');
          }
          unset($acl);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        } 
      } break;
      case "remove": {
        if(isset($request_data->id)&&self::checkPriv('settings_writer')) {
          $acl = new \security\ACL($request_data->id);
          if($acl->delete()) {
            $acl->reload();
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Не удалось удалить фильтр');
          }
          unset($acl);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
    }
    return $result;
  }
    
}

?>