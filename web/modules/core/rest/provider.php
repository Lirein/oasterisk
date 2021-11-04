<?php

namespace core;

class ProviderREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'provider';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        $providers = new \channel\Providers();
        $returnData = array();
        foreach($providers as $id => $entry) {
          if(self::checkEffectivePriv(self::getServiceLocation(), $id, 'settings_reader')) $returnData[] = (object)array('id' => $id.'@'.$entry::getTypeName(), 'title' => $entry->name);
        }
        $result = self::returnResult($returnData);
      } break;
      case "get": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_reader')) {
          list($id, $type) = explode('@', $request_data->id, 2);
          $result = self::returnError('danger', 'Не удалось найти провайдера с указанным типом');
          $providers = findModulesByClass('core\Provider');
          $canprocess = true;
          foreach($providers as $module) {
            $classname = $module->class;
            if($classname::getTypeName() == $type) {
              $provider = new $classname($id);
              $profile = new \stdClass();
              $profile->account = $provider->cast();
              $profile->id = $profile->account->id.'@'.$provider::getTypeName();
              $profile->name = $profile->account->name;
              $profile->phone = $profile->account->phone;
              unset($profile->account->id);
              unset($profile->account->old_id);
              unset($profile->account->name);
              $profile->readonly = !self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_writer');
              $profile->type = $type;
              $result = self::returnResult($profile);
            }
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "set": {
        if(!empty($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_writer')) {
          list($id, $type) = explode('@', $request_data->id, 2);
          unset($request_data->id);
          $result = self::returnError('danger', 'Не удалось найти провайдера с указанным типом');
          $providers = findModulesByClass('core\Provider');
          $canprocess = true;
          if(($type != $request_data->type)) {
            $canprocess = false;
            foreach($providers as $module) {
              $classname = $module->class;
              if($classname::getTypeName() == $type) {
                $provider = new $classname($id);
                if($provider->delete()) {
                  $provider->reload();
                  $canprocess = true;
                }
              }
            }
          }
          $type = $request_data->type;
          unset($request_data->type);
          unset($request_data->old_id);
          $request_data->account->id = $id;
          if($canprocess) { 
            foreach($providers as $module) {
              $classname = $module->class;
              if($classname::getTypeName() == $type) {
                $provider = new $classname($id);
                if($provider->assign($request_data->account)) {
                  if($provider->save()) {
                    $provider->reload();
                    $result = self::returnResult($provider->id.'@'.$type);
                  } else {
                    $result = self::returnError('danger', 'Не удалось сохранить провайдера');
                  }
                } else {
                  $result = self::returnError('danger', 'Не удалось установить данные провайдера');
                }                 
                break;
              }
            }
          } else {
            $result = self::returnError('danger', 'Не удалось удалить старого провайдера');
          }
          unset($providers);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }              
      } break;
      case "add": {
        if((empty($request_data->id)||($request_data->id == 'false'))&&!empty($request_data->type)&&self::checkPriv('settings_writer')) {
          $result = self::returnError('danger', 'Не удалось найти провайдера с указанным типом');
          $type = $request_data->type;
          unset($request_data->type);
          if(isset($request_data->id)) unset($request_data->id);
          if(isset($request_data->old_id)) unset($request_data->old_id);
          $providers = findModulesByClass('core\Provider');
          foreach($providers as $module) {
            $classname = $module->class;
            if($classname::getTypeName() == $type) {
              $provider = new $classname(null);
              if($provider->assign($request_data->account)) {
                if($provider->save()) {
                  $provider->reload();
                  $result = self::returnResult($provider->id.'@'.$type);
                } else {
                  $result = self::returnError('danger', 'Не удалось сохранить провайдера');
                }
              } else {
                $result = self::returnError('danger', 'Не удалось установить данные провайдера');
              }                 
              break;
            }
          }
          unset($providers);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }              
      } break;
      case "remove": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_writer')) {
          list($id, $type) = explode('@', $request_data->id, 2);
          $result = self::returnError('danger', 'Не удалось найти провайдера указанного типа');
          $providers = findModulesByClass('core\Provider');
          foreach($providers as $module) {
            $classname = $module->class;
            if($classname::getTypeName() == $type) {
              $provider = new $classname($id);
              if($provider->delete()) {
                $provider->reload();
                $result = self::returnSuccess();
              } else {
                $result = self::returnError('danger', 'Не удалось удалить провайдера');
              }
            }
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "types": {
        if(self::checkPriv('settings_reader')) {
          $return_data = array();
          $providers = findModulesByClass('core\Provider');
          foreach($providers as $module) {
            $classname = $module->class;
            $return_data[] = (object)array('id' => $classname::getTypeName(), 'title' => $classname::getTypeTitle());
          }
          $result = self::returnResult($return_data);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
    }
    return $result;
  }
    
}

?>