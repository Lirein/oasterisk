<?php

namespace core;

class NetworkRoutesREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'system/network/routes';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
  
    switch($request) {
      case "menuItems":{
        $objects = array();
        $objects[] = (object)array('id' => 1, 'to' => '0.0.0.0', 'scope' => 'link', 'prefix' => '24', 'via' => '9.9.9.9', 'on-link' => true, 'readonly' => false);
        $result = self::returnResult($objects);
        unset($addresses);
      } break;
      case "get":{
        if(!isset($request_data->id)) {
          $result = self::returnResult(array('readonly' => !$this->checkPriv('settings_writer')));
        } else {
          // $address = \core\NetworkAdddresses::find($request_data->id);
          // if($adddress) {
          //   $result = self::returnResult($address->cast());
          // } else {
            $result = self::returnError('warning', 'Такого сетевого адреса не существует');
          // }
        }
      } break;
      case "addresses":{
        if(!isset($request_data->id)) {
          $result = self::returnResult(array('readonly' => !$this->checkPriv('settings_writer')));
        } else {
          $adapter = \core\NetworkAdapters::find($request_data->id);
          if($adapter) {
            $addressList = array();
            foreach($adapter->addresses as $address) {
              $addressList[] = (object) array('id' => $address, 'title' => $address);
            }
            $addressList = array_values($addressList);
            $result = self::returnResult($addressList);
          } else {
            $result = self::returnError('warning', 'Такого сетевого адреса не существует');
          }
        }
      } break;
    }
    return $result;
  }
    
}

?>