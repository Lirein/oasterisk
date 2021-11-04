<?php

namespace core;

class IVRREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'ivr';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        $ivrs = new \core\IVRs();
        $returnData = array();
        foreach($ivrs as $id => $entry) {
          if(self::checkEffectivePriv(self::getServiceLocation(), $id, 'settings_reader')) $returnData[] = (object)array('id' => $entry->id, 'title' => $entry->title);
        }
        $result = self::returnResult($returnData);
      } break;
      case "get": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_reader')) {
          $ivr = new \core\IVR($request_data->id);
          $result = self::returnResult($ivr->cast());
          unset($ivr);
        }
      } break;
      case "set": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_writer')) {
          $ivr = new \core\IVR($request_data->id);        
          if($ivr->assign($request_data)) {
            if($ivr->save()){
              $ivr->reload();
              $result = self::returnResult((object)array('id' => $ivr->id));
            } else {
              $result = self::returnError('danger', 'Не удалось сохранить сценарий');
            }
          } else {
            $result = self::returnError('danger', 'Не удалось установить данные сценария');
          }   
          unset($ivr);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "add": {
        if((empty($request_data->id)||($request_data->id == 'false'))&&self::checkPriv('settings_writer')) {
          $ivr = new \core\IVR();        
          if($ivr->assign($request_data)) {
            if($ivr->save()){
              $result = self::returnResult((object)array('id' => $ivr->id));
            } else {
              $result = self::returnError('danger', 'Не удалось добавить сценарий');
            }
          } else {
            $result = self::returnError('danger', 'Не удалось установить данные сценария');
          }   
          unset($ivr);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "remove": {
        if(isset($request_data->id)&&self::checkPriv('settings_writer')) {
          $ivr = new \core\IVR($request_data->id);
          if($ivr->delete()) {
            $ivr->reload();
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Не удалось удалить сценарий');
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