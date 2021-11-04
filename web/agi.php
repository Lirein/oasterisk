#!/usr/bin/php
<?php

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__DIR__));

$request_data = new \stdClass();

array_shift($_SERVER['argv']);
foreach($_SERVER['argv'] as $arg) {
  $pieces = explode('=',$arg);
  if(count($pieces) >= 2) {
    $real_key = $pieces[0];
    array_shift($pieces);
    $real_value = implode('=', $pieces);
    $request_data->$real_key = $real_value;
  } else {
    $real_key = $pieces[0];
    $request_data->$real_key = true;
  }
}

session_start();
if(!isset($request_data->agi)) {
 exit(201);
}
$location=$request_data->agi;

require 'core/asterisk.php';
require 'web/modules.php';

$privileges = array();

function startProcessing() {
  global $_AGI;
  global $_CACHE;
  global $location;
  global $request_data;
  $_AGI = new \AGI();

  $_CACHE = new \Memcached();
  $_CACHE->addServer('localhost',0);

  if(isset($_AGI)) {
    updateModules();
    $modules = findModulesByNamespace($location);
    if(count($modules)>0) {
      session_write_close();
      $retcode=0;
      foreach($modules as $module) {
        $classname = $module->class;
        if(is_subclass_of($classname, '\module\IAGI')) {
          $instance = new $classname();
          $retcode|=$instance->agi($request_data);
          unset($instance);
        }
      }
      return $retcode;
    } else {
      return 203;
    }
  }
}

unset($_AGI);

$agi_return=200;

if(!isset($_AGI)) {
  $agi_return = startProcessing();
}

exit($agi_return);

?>
