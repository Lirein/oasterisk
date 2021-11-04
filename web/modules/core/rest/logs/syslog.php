<?php

namespace core;

class SyslogREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'logs/syslog';
  }
 
  public function reloadConfig() {
    $this->ami->send_request('Command', array('Command' => 'logger reload'));
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "get":{
        $syslog = new SyslogModel();
        $result = self::returnResult($syslog->cast());
      } break;
      case "set":{
        if($this->checkPriv('settings_writer')) {
          $syslog = new SyslogModel();
          $syslog->assign($request_data);
          if($syslog->save()) {
            $result = self::returnSuccess();
            $this->reloadConfig();
          } else {
            $result = self::returnError('danger', 'Невозможно сохранить настройки');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
    }
    return $result;
  }
    
}

?>