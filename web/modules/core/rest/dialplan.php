<?php

namespace core;

class DialplanREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'dialplan';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
  
    switch($request) {
      case "applications":{
        $return_data = array();
        $dialplan = new \core\Dialplan();
        $applications = $dialplan->getApplications();
        foreach($applications as $application => $description) {
          $return_data[] = (object)array('id' => strtolower($application), 'title' => $application, 'description' => $description);
        }
        $result = self::returnResult($return_data);
      } break;
      case "functions":{
        $return_data = array();
        $dialplan = new \core\Dialplan();
        $functions = $dialplan->getFunctions();
        foreach($functions as $function => $description) {
          $return_data[] = (object)array('id' => strtolower($function), 'title' => $function, 'description' => $description);
        }
        $result = self::returnResult($return_data);
      } break;
      case "actions":{
        $return_data = array();
        $actions = findModulesByClass('core\ActionViewPort');
        foreach($actions as $module) {
          $classname = $module->class;
          $return_data[] = (object)array('id' => $classname::getActionName(), 'title' => $classname::getActionTitle());
        }
        $result = self::returnResult($return_data);
      } break;
    }
    return $result;
  }
    
}

?>