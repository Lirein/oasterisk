<?php

namespace core;

class UsersREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'security/user';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        $users = new \security\Users();
        $objects = array();
        foreach($users as $id => $data) {
          if(self::checkEffectivePriv(self::getServiceLocation(), $id, 'security_reader')) $objects[]=(object)array('id' => $id, 'title' => $data->name, 'icon' => 'PersonOutlineSharpIcon');
        }
        $result = self::returnResult($objects);
        unset($users);
      } break;
      case "get": {
        if(isset($request_data->id)&&(self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'security_reader')||(self::$user->id==$request_data->id))) {
          $user = new \security\User($request_data->id);
          $data = $user->cast();
          $data->iscurrentuser = ($user->id == self::$user->id); 
          $data->viewmode = $user->getViewMode();
          $data->readonly = !(self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'security_writer')||(self::$user->id == $request_data->id));
          $result = self::returnResult($data);
          unset($user);
        }
      } break;
      case "add": {
        if(isset($request_data->id)&&self::checkPriv('security_writer')) {
          $user = new \security\User($request_data->id);
          if($user->old_id == null) {
            if(!empty($request_data->secret)) {
              if($user->assign($request_data)) {
                if($user->save()){
                  if(isset($request_data->viewmode)) $user->setViewMode($request_data->viewmode);
                  $user->reload();
                  $result = self::returnResult((object) array('id' => $user->id));
                } else {
                  $result = self::returnError('danger', 'Не удалось добавить пользователя');
                }
              } else {
                $result = self::returnError('danger', 'Не удалось установить данные пользователя');
              }
            } else {
              $result = self::returnError('danger', 'Пароль не может быть пустым');
            }
          } else {
            $result = self::returnError('danger', 'Такой пользователь уже существует');
          }
          unset($user);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "set": {
        if(isset($request_data->id)&&(self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'security_writer')||((self::$user->id == $request_data->id) && (self::$user->group == $request_data->group)))) {
          if(!empty($request_data->secret)) {
            $user = new \security\User($request_data->id);        
            if($user->assign($request_data)) {
              if($user->save()){
                if(isset($request_data->viewmode)) $user->setViewMode($request_data->viewmode);
                $user->reload();
                $result = self::returnSuccess();
              } else {
                $result = self::returnError('danger', 'Не удалось сохранить пользователя');
              }
            } else {
              $result = self::returnError('danger', 'Не удалось установить данные пользователя');
            }
            unset($user);
          } else {
            $result = self::returnError('danger', 'Пароль не может быть пустым');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "remove": {
        if(isset($request_data->id)&&self::checkPriv('security_writer')&&(self::$user->id != $request_data->id)) {
          $user = new \security\User($request_data->id);
          if($user->delete()) {
            $user->reload();
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Не удалось удалить пользователя');
          }
          unset($user);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
    }
    return $result;
  }

  public static function GroupChange(int $event, \security\Group &$subject) {
    foreach($subject as $user) {
      $user->save();
    }
  }

  public static function GroupRemove(int $event, \security\Group &$subject) {
    foreach($subject as $user) {
      $user->delete();
    }
  }

  public static function ACLRename(int $event, \security\ACL &$subject) {
    $users = new \security\Users();
    foreach($users as $user) {
      $acls = array();
      foreach($user->acl as $aclid) {
        if($aclid != $subject->old_id) {
          $acls[] = $aclid;
        } else {
          $acls[] = $subject->id;
        }
      }
      if(count(array_diff($user->acl, $acls))!=0) {
        $user->acl = $acls;
        $user->save();
      }
    }
  }

  public static function ACLRemove(int $event, \security\ACL &$subject) {
    $users = new \security\Users();
    foreach($users as $user) {
      $acls = array();
      foreach($user->acl as $aclid) {
        if($aclid != $subject->old_id) $acls[] = $aclid;
      }
      if(count(array_diff($user->acl, $acls))!=0) {
        $user->acl = $acls;
        $user->save();
      }
    }
  }

  public static function register() {
    self::setHandler(self::CHANGE, 'security\Group', array(__CLASS__, 'GroupChange'));
    self::setHandler(self::RENAME, 'security\Group', array(__CLASS__, 'GroupChange'));
    self::setHandler(self::REMOVE, 'security\Group', array(__CLASS__, 'GroupRemove'));
    self::setHandler(self::RENAME, 'security\ACL', array(__CLASS__, 'ACLRename'));
    self::setHandler(self::REMOVE, 'security\ACL', array(__CLASS__, 'ACLRemove'));
  }

}

?>