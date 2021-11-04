<?php

namespace core;

class SoundREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'sound';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        $sounds = new \sound\Sounds();
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
      } break;
      case "get": {
        if(self::checkPriv('settings_reader')) {
          if (isset($request_data->id)){
            $sound = \sound\Sounds::find($request_data->id);
            if($sound) {
              $result = self::returnResult($sound->cast());
            } else {
              $result = self::returnError('warning', 'Звука с таким идентификатором не существует');
            }
          }
        } else {
          $result = self::returnError('danger', 'Доступ запрещен');
        }
      } break;
      case "languages": {
        $languagelist = array();
        foreach(\sound\Sounds::getLanguages() as $language){
          switch(strtolower($language)) {
            case 'ru': $langtitle = 'Русский'; break;
            case 'en': $langtitle = 'Английский'; break;
            case 'ua': $langtitle = 'Украинский'; break;
            case 'other': $langtitle = 'Не указан'; break;
            default: $langtitle = strtoupper($language);
          }
          $languagelist[] = (object) array('id' => $language, 'title' => $langtitle);
        }
        $result = self::returnResult($languagelist);
      } break;
      case "formats": {
        $formatlist = array();
        foreach(\sound\Sounds::getFormats() as $format => $title){
          $formatlist[] = (object) array('id' => $format, 'title' => $title);
        }
        $result = self::returnResult($formatlist);
      } break;
      case "stream": {
        //if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_reader')) {
          $sound = \sound\Sounds::find($request_data->id);
          if($sound) {
            if(!isset($request_data->language)) $request_data->language = '';
            if(!isset($request_data->format)) $request_data->format = '';
            $stream = $sound->getStream($request_data->language, $request_data->format);
            if($stream != null) {
              $result = self::returnData($stream, 'audio/wav', basename($request_data->id).'.wav');
            } else {
              $result = self::returnError('warning', 'Не удалось воспроизвести звук');
            }
          } else {
            $result = self::returnError('warning', 'Звука с таким идентификатором не существует');
          }
        // } else {
        //   $result = self::returnError('danger', 'Доступ запрещен');
        // }
      } break;
    }
    return $result;
  }
    
}

?>