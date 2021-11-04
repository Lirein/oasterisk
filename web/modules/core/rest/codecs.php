<?php

namespace core;

class CodecsREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'general/codecs';
  }
 
  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        //   $codecs = new \core\Codecs();
        //   $codecs = $codecs->get();
        //   $objects = array();
        //   foreach($codecs as $codec) {
        //     $objects[]=(object)array('id' => $codec->name, 'title' => $codec->title);
        //   }
        $codecs = new \module\Codecs();
        $objects = array();
        foreach($codecs as $codec) {
          $objects[]=(object)array('id' => $codec->id, 'title' => $codec->title);
        }
        $result = self::returnResult($objects);
        unset($codecs);
      } break;
      case "get": {
        //  if(isset($request_data->id)) {
        //     $codecs = new \core\Codecs();
        //     $codec = $codecs->get($request_data->id);
        //     if($codec) {
        //       $codecinfo = new \stdClass();
        //       $codecinfo->title = $codec->title;
        //       $codecinfo->props = $codec->codec->getProp();
        //       $result = self::returnResult($codecinfo);
        //     } else {
        //       $result = self::returnError('danger', 'Кодек не найден');
        //     }
        //   }
        if(isset($request_data->id)) {
          //$codec = new CodecSubject($request_data->id);
          $codecs = new \module\Codecs();
          $codec = $codecs->find($request_data->id);
          if($codec) {
            $result = self::returnResult($codec->cast());
          } else {
            $result = self::returnError('danger', 'Кодек не найден');
          }
        }
      } break;
      case "set": {
      //   if(isset($request_data->id)&&isset($request_data->props)&&($request_data->props!=='false')) {
      //     if(self::checkPriv('settings_writer')) {
      //       $codecs = new \core\Codecs();
      //       $codec = $codecs->get($request_data->id);
      //       if($codec) {
      //         $status = $codec->codec->setProp($request_data->props);
      //         if($status) {
        if(isset($request_data->id)) {
          if(self::checkPriv('settings_writer')) {
            //$codec = new CodecSubject($request_data->id);
            $codecs = new \module\Codecs();
            $codec = $codecs->find($request_data->id);
            if($codec->assign($request_data)) {
              if($codec->save()) {
                $result = self::returnSuccess();
              } else {
                $result = self::returnError('danger', 'Не удалось установить параметры кодека');
              }
            } else {
              $result = self::returnError('danger', 'Кодек не найден');
            }
          } else {
            $result = self::returnError('danger', 'Отказано в доступе');
          }
        } else {
          $result = self::returnError('danger', 'Не передан класс кодека или его параметры');
        }
      } break;
    }
    return $result;
  }
    
}

?>