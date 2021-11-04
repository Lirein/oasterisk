<?php

namespace core;

class AppearenceModule extends \Module {

  private static $defaultparams = '{
    "primary": "#689f38",
    "secondary": "#4e342e",
    "success": "#4caf50",
    "info": "#2196f3",
    "warning": "#ff9800",
    "error": "#f44336"
  }';

  public static function getCurrentSettings() {
    $result = new \stdClass();
    $defaults = json_decode(self::$defaultparams);

    $ini = self::getINI('/etc/asterisk/manager.conf');
    $login = $_SESSION['login'];
    if(isset($ini->$login)) {
      $user = array();
      foreach($defaults as $key => $value) {
        if(isset($ini->$login->$key)) $user[$key] = (string) $ini->$login->$key;
      }
      if (count($user) > 0) {
        foreach($defaults as $key => $value) {
          if(!isset($user[$key])) $user[$key] = $value;
        }
        $result->user = (object)$user;
      }
    }
    unset($ini);
    

    if(!self::$user) self::initPermissions();
    $role = self::$user->group->id;
    $group = array();
    foreach($defaults as $key => $value) {
      $v = self::getDB('appearence/'.$role, $key);
      if(!empty($v)) $group[$key] = $v;
    }
    if (count($group) > 0) {
      foreach($defaults as $key => $value) {
        if(!isset($group[$key])) $group[$key] = $value;
      }
      $result->group = (object)$group;
    }

    $ini = self::getINI('/etc/asterisk/asterisk.conf');
    if(isset($ini->appearence)) {
      $result->system = $defaults;
      foreach($ini->appearence as $key => $value) {
        $result->system->$key = (string) $value;
      }
    }
    unset($ini);
    
    return $result;
  }

  public static function getDefaultSettings() {
    return json_decode(self::$defaultparams);
  }
    
}