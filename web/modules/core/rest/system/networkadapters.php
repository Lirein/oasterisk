<?php

namespace core;

class NetworkAdaptersREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'system/network/adapters';
    //return 'system/hardware/networkadaptors';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
  
    switch($request) {
      case "menuItems":{
        $adapters = new NetworkAdapters();
        $objects = array();
        foreach($adapters as $id => $data) {
          $objects[]=(object)array('id' => $id, 'title' => $data->title);
        }
        // $objects['test']=$test->test();
        // $test->save();
        $result = self::returnResult($objects);
        unset($adapters);
      } break;
      case "get":{
        $test = new \config\NetPlan('/etc/netplan/test.yaml');
        $adapter = \core\NetworkAdapters::find($request_data->id);
        $objects = $adapter->cast();
        if($adapter) {
          $result = self::returnResult($objects);
        } else {
          $result = self::returnError('warning', 'Сетевой платы с таким идентификатором не существует');
        }
      } break;
      case "set":{
        if($this->checkPriv('settings_writer')) {
          $adapter = new NetworkAdapter();
          $adapter->assign($request_data);
          if($adapter->save()) {
            $result = self::returnSuccess();
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