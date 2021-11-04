<?php

namespace core;

class DateTimeREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'system/datetime';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
  
    switch($request) {
      case "get":{
        $return = new \stdClass();
        $return = \core\DateTimeModule::getCurrentSettings();
        $result = self::returnResult($return);
      } break;
      case "set":{
        if($this->checkPriv('settings_writer')) {
          if(\core\DateTimeModule::setCurrentSettings($request_data)) {
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Невозможно сохранить настройки');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "timezones":{
        $regionlist = array();
        foreach(\core\DateTimeModule::getTimezonelist() as $key => $value){
          $zonelist = array();
          if (!empty($value)) {
            foreach($value as $zone => $title){
              $zonelist[] = (object) array('id' => $title, 'title' => $title);
            }
          }
          $regionlist[] = (object) array('id' => $key, 'title' => $key, 'value' => $zonelist);
        }
        $result = self::returnResult($regionlist);
      } break;
    }
    return $result;
  }
    
}

?>