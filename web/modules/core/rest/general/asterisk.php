<?php

namespace core;

class AsteriskCoreREST extends \module\Rest {
  private static $interfacedir = '/sys/class/net';

  public static function getServiceLocation() {
    return 'general/core';
  }

  public function reload(){
    return $this->ami->send_request('Command', array('Command' => 'module reload core'))!==false;
  }
 
  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "get":{
        $asterisk = new AsteriskCoreModel();   
        $result = self::returnResult($asterisk->cast());
      } break;
      case "set":{
        if($this->checkPriv('settings_writer')) {
          $asterisk = new AsteriskCoreModel();     
          $asterisk->assign($request_data);
          if($asterisk->save()) {
            $result = self::returnSuccess();
            $this->reload();
          } else {
            $result = self::returnError('danger', 'Невозможно сохранить настройки');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "mac-get":{
        $list = array();
        if($dh = opendir(self::$interfacedir)) {
          while(($file = readdir($dh)) !== false) {
            if(is_dir(self::$interfacedir . '/' . $file)) {
              if($file[0]!='.') {
                if(file_exists(self::$interfacedir . '/' . $file . '/address')) {
                  $mac = trim(file_get_contents(self::$interfacedir . '/' . $file . '/address'));
                  $list[] = (object) array('id' => $mac, 'title' => $mac);
                }
              }
            }
          }
          closedir($dh);
        }
        $result = self::returnResult($list);
      } break;
      case "user-get":{
        $list = array();
        $data = file_get_contents('/etc/passwd');
        $lines = explode("\n", $data);
        foreach($lines as $line) {
          $pos = strpos($line, ':');
          if($pos!==false) {
            $user = substr($line, 0, $pos);
            $list[] = (object) array('id' => $user, 'title' => $user);
          }
        }
        $result = self::returnResult($list);
      } break;
      case "group-get":{
        $list = array();
        $data = file_get_contents('/etc/group');
        $lines = explode("\n", $data);
        foreach($lines as $line) {
          $pos = strpos($line, ':');
          if($pos!==false) {
            $user = substr($line, 0, $pos);
            $list[] = (object) array('id' => $user, 'title' => $user);
          }
        }
        $result = self::returnResult($list);
      } break;
    }
    return $result;
  }
    
}

?>