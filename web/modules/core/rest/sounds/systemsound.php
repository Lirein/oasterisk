<?php

namespace core;

class SystemSoundREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'sound/system';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems": {
        $sounds = new \core\SystemSounds();
        $soundList = array();
        foreach($sounds as $k => $v) {
          $soundList[$k] = (object) array('id' => $k, 'title' => $v->title, 'system' => ($v instanceof \core\SystemSound));
          if(dirname($k)!='.') {
            if(!isset($soundList[dirname($k)])) {
              $soundList[dirname($k)] = (object) array('id' => dirname($k), 'title' => basename(dirname($k)));
            }
            if(!isset($soundList[dirname($k)]->value)) $soundList[dirname($k)]->value = array();
            $soundList[dirname($k)]->value[] = $soundList[$k];
            $soundList[$k]->remove = true;
          }
        }
        foreach($soundList as $k => $v) {
          if(isset($v->remove)) {
            unset($soundList[$k]->remove);
            unset($soundList[$k]);
          }
        }
        $soundList = array_values($soundList);
        $result = self::returnResult($soundList);
      }
      case "get": {
        if(self::checkPriv('settings_reader')) {
          if (isset($request_data->id)){
            $sound = new \core\SystemSound($request_data->id);
            if($sound->old_id) {
              $result = self::returnResult($sound->cast());
            } else {
              $result = self::returnError('warning', 'Звука с таким идентификатором не существует');
            }
          }
        } else {
          $result = self::returnError('danger', 'Доступ запрещен');
        }
      } break;
    }
    return $result;
  }
    
}

?>