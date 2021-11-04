<?php

namespace core;

class IVRActionREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'action';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "get": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_reader')) {
          $ivr = new \core\IVRAction($request_data->id);
          $result = self::returnResult($ivr->cast());
          unset($ivr);
        }
      } break;
      case "set": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_writer')) {
          $ivr = new \core\IVRAction($request_data->id);        
          if($ivr->assign($request_data)) {
            if($ivr->save()){
              $result = self::returnResult((object)array('id' => $ivr->id));
            } else {
              $result = self::returnError('danger', 'Не удалось сохранить действие');
            }
          } else {
            $result = self::returnError('danger', 'Не удалось установить данные действия');
          }   
          unset($ivr);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "add": {
        if((empty($request_data->id)||($request_data->id == 'false'))&&self::checkPriv('settings_writer')) {
          $ivr = new \core\IVRAction();        
          if($ivr->assign($request_data)) {
            if($ivr->save()){
              $result = self::returnResult((object)array('id' => $ivr->id));
            } else {
              $result = self::returnError('danger', 'Не удалось добавить действие');
            }
          } else {
            $result = self::returnError('danger', 'Не удалось установить данные действия');
          }   
          unset($ivr);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "remove": {
        if(isset($request_data->id)&&self::checkPriv('settings_writer')) {
          $ivr = new \core\IVRAction($request_data->id);
          if($ivr->delete()) {
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Не удалось удалить действие');
          }
          unset($ivr);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        } 
      } break;
    }
    return $result;
  }
    
}

?>