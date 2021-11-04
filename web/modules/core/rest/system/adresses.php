<?php

namespace core;

class NetworkAddressesREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'system/network/addresses';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
  
    switch($request) {
      case "menuItems":{
        // $addresses = new NetworkAddresses();
        $objects = array();
        // foreach($addresses as $id => $data) {
        //   $objects[]=(object)array('id' => $id, 'title' => $data->title);
        // }
        $objects[] = (object)array('id' => 1, 'family' => 'ipv4', 'address' => '192.168.0.2', 'prefix' => '24', 'gateway' => '192.168.0.254', 'adapter' => 'ens3', 'readonly' => false);
        $objects[] = (object)array('id' => 2, 'family' => 'ipv4', 'address' => '192.168.1.2', 'prefix' => '24', 'gateway' => '0.0.0.0', 'adapter' => 'ens3', 'readonly' => true);
        $objects[] = (object)array('id' => 3, 'family' => 'ipv4', 'address' => '0.0.0.0', 'prefix' => '0', 'gateway' => '0.0.0.0', 'adapter' => 'ens3', 'readonly' => true);
        $objects[] = (object)array('id' => 4, 'family' => 'ipv6', 'address' => '0.0.0.0', 'prefix' => '0', 'gateway' => '0.0.0.0', 'adapter' => 'ens3', 'readonly' => true);
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
    }
    return $result;
  }
    
}

?>