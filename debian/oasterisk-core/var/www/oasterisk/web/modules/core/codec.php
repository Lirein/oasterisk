<?php

namespace core;

abstract class Codec extends Module {

  abstract public function info();

  public function getProp() {
    $props = new \stdClass();
    $ini = new \INIProcessor('/etc/asterisk/codecs.conf');
    $moduleInfo = $this->info();  
    $propinfo = null;
    $moduleName = $moduleInfo->name;
    if(isset($ini->$moduleName)) $propinfo = $ini->$moduleName;
    else if(isset($ini->plc)) $propinfo = $ini->plc;
    if($propinfo !== false) {
      foreach($propinfo as $k => $v) {
        $props->$k = (string) $v;
      }
    }
    return $props;
  }

  public function setProp(\stdClass $props) {
    $ini = new \INIProcessor('/etc/asterisk/codecs.conf');
    $moduleInfo = $this->info();  
    $moduleName = $moduleInfo->name;
    $propinfo = $ini->$moduleName;
    foreach($props as $prop => $value) {
      $propinfo->$prop = $value;
    }
    return $ini->save();
  }

}

?>