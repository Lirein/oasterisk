<?php

namespace core;

class FeaturesREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'features';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
  
    switch($request) {
      case "get":{
        $features = new FeaturesModel();
        $return_data = $features->cast();
        $return_data->featuremap['pickupexten'] = $return_data->general['pickupexten'];
        unset($return_data->general['pickupexten']);
        $return_data->featuremap['atxferabort'] = $return_data->general['atxferabort'];
        unset($return_data->general['atxferabort']);
        $return_data->featuremap['atxfercomplete'] = $return_data->general['atxfercomplete'];
        unset($return_data->general['atxfercomplete']);
        $return_data->featuremap['atxferthreeway'] = $return_data->general['atxferthreeway'];
        unset($return_data->general['atxferthreeway']);
        $return_data->featuremap['atxferswap'] = $return_data->general['atxferswap'];
        unset($return_data->general['atxferswap']);
        $result = self::returnResult($return_data);
      } break;
      case "set":{
        if($this->checkPriv('settings_writer')) {
          $features = new FeaturesModel();
          $request_data->general->pickupexten = $request_data->featuremap->pickupexten;
          unset($request_data->featuremap->pickupexten);
          $request_data->general->atxferabort = $request_data->featuremap->atxferabort;
          unset($request_data->featuremap->atxferabort);
          $request_data->general->atxfercomplete = $request_data->featuremap->atxfercomplete;
          unset($request_data->featuremap->atxfercomplete);
          $request_data->general->atxferthreeway = $request_data->featuremap->atxferthreeway;
          unset($request_data->featuremap->atxferthreeway);
          $request_data->general->atxferswap = $request_data->featuremap->atxferswap;
          unset($request_data->featuremap->atxferswap);
          $features->assign($request_data);
          if($features->save()) {
            $result = self::returnSuccess();
            $features->reload();
          } else {
            $result = self::returnError('danger', 'Невозможно сохранить настройки');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      // case "sounds":{
      //   $sounds = new \core\Sounds();
      //   $soundList = array();
      //   foreach($sounds->get() as $v => $dummy) {
      //     $soundList[] = (object) array('id' => $v, 'text' => $v);
      //   }
      //   $result = self::returnResult($soundList);
      // } break;
      // case "contexts":{
      //   $dialplan = new \core\Dialplan();
      //   $contextList = array();
      //   foreach($dialplan->getContexts() as $v) {
      //     $contextList[] = (object) array('id' => $v->id, 'text' => $v->title);
      //   }
      //   $result = self::returnResult($contextList);
      // } break;
    }
    return $result;
  }
    
}

?>