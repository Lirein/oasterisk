<?php

namespace core;

class CoreREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'core';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
  
    switch($request) {
      case "getview": {
        if(strpos($request_data->location, '/')===0) $request_data->location = substr($request_data->location, 1);
        $module = getModuleByPath($request_data->location);
        if(!$module) {
          $newlocation = getSiblingPath($request_data->location);
          if($newlocation) $request_data->location = $newlocation;
          $module = getModuleByPath($request_data->location);
        }
        if($module) {
          $location = $module::getLocation();
          $viewlocation = $module::getViewLocation();
          if(empty($viewlocation)) $viewlocation = $module::getLocation();
          $result = self::returnResult((object)array('location' => $location, 'view' => $viewlocation, 'collection' => $module instanceof \view\Collection));
        } else {
          $result = self::returnError('danger', 'Модуль отвечающий за путь '.$request_data->location.' не найден');
        }
      } break;
      case "getmenu": {
        $result = self::returnResult(getLeftMenu());
      } break;
      case "setviewmode":{
        if(self::$user->setViewMode($request_data->mode)) {
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Невозможно сохранить режим работы интерфейса');
        }
      } break;
    }
    return $result;
  }
    
}

?>