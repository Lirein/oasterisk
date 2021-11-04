<?php

namespace core;

class DateTimeModule extends \Module {

  public static function getTimezonelist() {
    static $data = null;
    exec('timedatectl list-timezones', $info);
    foreach($info as $line) {
      $pos = stripos($line, '/');
      if($pos!==false) {
        $data[trim(substr($line, 0, $pos))][] = trim(substr($line, $pos+1));
      } else {
        $data[$line] = null;
      }
    }
    return $data;
  }

  public static function getCurrentSettings() {
    $result = new \stdClass();
    $result->timezone = exec('cat /etc/timezone');
    //$result->unixtimestamp = exec('sudo hwclock');
    $result->unixtimestamp = time();

    exec('timedatectl',$info);
    $pattern = 'Network time on:';
    foreach($info as $line) {
      $pos = stripos($line, $pattern);
      if($pos!==false) {
        $result->mode = trim(substr($line, $pos + strlen($pattern)));
      }
    }

    $result->ntpserver = null;
    return $result;
  }

  public static function setCurrentSettings($assign_data) {
    $result = true;
    foreach($assign_data as $key => $value) {
      if ($key == 'timezone') $result &= exec('sudo timedatectl set-timezone '.$value);
      if ($key == 'unixtimestamp') $result &= exec('date -s '.$value);
      //if ($key == 'unixtimestamp') $result &= exec('sudo hwclock --set --date='.$value);
      if ($key == 'mode') $result &= exec('sudo timedatectl set-ntp '.$value); 
    }
    return $result;
  }
}
?>