<?php

namespace core;

class FaxREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'general/fax';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "get":{
        $fax = new FaxModel();
        $result = self::returnResult($fax->cast());
      } break;
      case "set":{
        if($this->checkPriv('settings_writer')) {
          $fax = new FaxModel();
          if ($request_data->fax->maxrate < $request_data->fax->minrate){
            $tmp = $request_data->fax->maxrate;
            $request_data->fax->maxrate = $request_data->fax->minrate;
            $request_data->fax->minrate = $tmp;
          }
          if ($request_data->udptl->udptlend < $request_data->udptl->udptlstart){
            $tmp = $request_data->udptl->udptlend;
            $request_data->udptl->udptlend = $request_data->udptl->udptlstart;
            $request_data->udptl->udptlstart = $tmp;
          }
          $fax->assign($request_data);
          if($fax->save()) {
            $result = self::returnSuccess();
            $fax->reload();
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