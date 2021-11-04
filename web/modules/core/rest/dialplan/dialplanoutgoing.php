<?php

namespace core;

class DialplanOutgoingREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'dialplan/outgoing';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
  
    switch($request) {
      case "menuItems":{
        $return_data = array();
        $result = self::returnResult($return_data);
      } break;
      case "get":{
        $return_data = array();
        $result = self::returnResult($return_data);
      } break;
      case "set":{
        $return_data = array();
        $result = self::returnResult($return_data);
      } break;
      case "add":{
        $return_data = array();
        $result = self::returnResult($return_data);
      } break;
      case "remove":{
        $return_data = array();
        $result = self::returnResult($return_data);
      } break;
    }
    return $result;
  }
    
}

?>