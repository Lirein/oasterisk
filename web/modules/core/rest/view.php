<?php

namespace core;

class ViewREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'view';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        if(isset($request_data->id)) {
          $views = findModulesByPath('view/'.$request_data->id);
          $returnData = array();
          foreach($views as $id => $entry) {
            $title = basename($entry->location);
            $classname = $entry->class;
            if(method_exists($classname, 'info')) $title = $classname::info();
            $returnData[] = (object)array('id' => basename($entry->location), 'title' => $title);
          }
          $result = self::returnResult($returnData);
        } else {
          $result = self::returnError('warning', 'Не указан путь к представлениям');
        }
      } break;
    }
    return $result;
  }
    
}

?>