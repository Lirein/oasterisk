<?php

namespace core\cdr;

class Settings extends \core\ICDRRestInterface {

  public static function getTitle() {
    return 'Настройки журналов';
  }

  public function get() {
    $cdr = new \core\CDRSettingsModel(); 
    return $cdr->cast();
  }

  public function set($request_data) {
    $cdr = new \core\CDRSettingsModel(); 
    $cdr->assign($request_data);
    return $cdr->save();
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "get-modules":{
        $moduleList = array();
        $modules = findModulesByClass('core\CdrEngineSettings', true);
        foreach($modules as $module) {
          $classname = $module->class;
          $classinfo = $classname::info();
          if($classname::selectable()) {
            $moduleList[]=(object) array('id' => $classinfo->id, 'title' => $classinfo->title);
          }
        }
        $result = self::returnResult($moduleList);
      } break;
    }
    return $result;
  }  
}

?>