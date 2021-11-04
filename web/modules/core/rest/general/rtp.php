<?php

namespace core;

class RTPREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'general/rtp';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "get":{
        $rtp = new RTPModel();
        $result = self::returnResult($rtp->cast());
      } break;
      case "set":{
        if($this->checkPriv('settings_writer')) {
          $rtp = new RTPModel();
          if ((int)$request_data->dtls_mtu <256) $request_data->dtls_mtu = 256;
          $rtp->assign($request_data);
          if($rtp->save()) {
            $result = self::returnSuccess();
            $rtp->reload();
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