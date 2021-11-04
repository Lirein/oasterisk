<?php

namespace core;

class GatewayREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'gateway';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        $gateways = new \channel\Gateways();
        $returnData = array();
        foreach($gateways as $id => $entry) {
          if(self::checkEffectivePriv(self::getServiceLocation(), $id, 'settings_reader')) $returnData[] = (object)array('id' => $id.'@'.$entry::getTypeName(), 'title' => $entry->name);
        }
        $result = self::returnResult($returnData);
      } break;
      case "get": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_reader')) {
          list($id, $type) = explode('@', $request_data->id, 2);
          $result = self::returnError('danger', 'Не удалось найти шлюз с указанным типом');
          $gateways = findModulesByClass('core\Gateway');
          $canprocess = true;
          foreach($gateways as $module) {
            $classname = $module->class;
            if($classname::getTypeName() == $type) {
              $gateway = new $classname($id);
              $profile = $gateway->cast();
              $profile->id = $profile->id.'@'.$gateway::getTypeName();
              unset($profile->old_id);
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
          $result = self::returnError('danger', 'Не удалось найти шлюз с указанным типом');
          $gateways = findModulesByClass('core\Gateway');
          $canprocess = true;
          if(($type != $request_data->type)) {
            $canprocess = false;
            foreach($gateways as $module) {
              $classname = $module->class;
              if($classname::getTypeName() == $type) {
                $gateway = new $classname($id);
                if($gateway->delete()) {
                  $gateway->reload();
                  $canprocess = true;
                }
              }
            }
          }
          if($canprocess) { 
            foreach($gateways as $module) {
              $classname = $module->class;
              if($classname::getTypeName() == $request_data->type) {
                $gateway = new $classname($request_data->id);
                unset($request_data->id);
                if($gateway->assign($request_data)) {
                  if($gateway->save()) {
                    $gateway->reload();
                    $result = self::returnResult($gateway->id.'@'.$request_data->type);
                  } else {
                    $result = self::returnError('danger', 'Не удалось сохранить шлюз');
                  }
                } else {
                  $result = self::returnError('danger', 'Не удалось установить данные шлюза');
                }                 
                break;
              }
            }
          } else {
            $result = self::returnError('danger', 'Не удалось удалить старый шлюз');
          }
          unset($gateways);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }              
      } break;
      case "add": {
        if((empty($request_data->id)||($request_data->id == 'false'))&&!empty($request_data->type)&&self::checkPriv('settings_writer')) {
          $result = self::returnError('danger', 'Не удалось найти шлюз с указанным типом');
          $gateways = findModulesByClass('core\Gateway');
          foreach($gateways as $module) {
            $classname = $module->class;
            if($classname::getTypeName() == $request_data->type) {
              $gateway = new $classname(null);
              if(isset($request_data->id)) unset($request_data->id);
              if(isset($request_data->old_id)) unset($request_data->old_id);
              if($gateway->assign($request_data)) {
                if($gateway->save()) {
                  $gateway->reload();
                  $result = self::returnResult($gateway->id.'@'.$request_data->type);
                } else {
                  $result = self::returnError('danger', 'Не удалось сохранить шлюз');
                }
              } else {
                $result = self::returnError('danger', 'Не удалось установить данные шлюза');
              }                 
              break;
            }
          }
          unset($gateways);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }              
      } break;
      case "remove": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_writer')) {
          list($id, $type) = explode('@', $request_data->id, 2);
          $result = self::returnError('danger', 'Не удалось найти шлюз указанного типа');
          $gateways = findModulesByClass('core\Gateway');
          foreach($gateways as $module) {
            $classname = $module->class;
            if($classname::getTypeName() == $type) {
              $gateway = new $classname($id);
              if($gateway->delete()) {
                $gateway->reload();
                $result = self::returnSuccess();
              } else {
                $result = self::returnError('danger', 'Не удалось удалить шлюз');
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
          $gateways = findModulesByClass('core\Gateway');
          foreach($gateways as $module) {
            $classname = $module->class;
            $return_data[] = (object)array('id' => $classname::getTypeName(), 'title' => $classname::getTypeTitle());
          }
          $result = self::returnResult($return_data);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "directions": {
        if(self::checkPriv('settings_reader')) {
          $return_data = array();
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