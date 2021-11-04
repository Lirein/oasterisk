<?php

namespace core;

class Sounds extends \Module {

  private static $soundsdir = false;

  private static function getsoundsdir() {
    if(is_dir('/usr/share/asterisk/sounds')) return '/usr/share/asterisk/sounds';
    if(is_dir('/var/lib/asterisk/sounds')) return '/var/lib/asterisk/sounds';
    return false;
  }

  private static function getdir($dir) {
    $list = array();
    if($dh = opendir($dir)) {
      while(($file = readdir($dh)) !== false) {
        if(is_dir($dir . '/' . $file)) {
          if($file[0]!='.') {
            $tmplist = self::getdir($dir.'/'.$file);
            if(($dir==self::$soundsdir)&&(in_array($file, ['ru','en']))) {
              $newlist=array();
              foreach($tmplist as $k => $v) {
                $newlist[substr($k,3)]=$v;
              }
              $tmplist = $newlist;
            }
            $list=array_merge($list,$tmplist);
          }
        } else {
          if($file[0]!='.') {
            $info = pathinfo($file);
            if(in_array($info['extension'], ['gsm','wav','g729','g722','siren7','siren14','alaw','ulaw','sln16'])) {
              $list[str_replace(self::$soundsdir.'/','',$dir . '/' . $info['filename'])]=true;
            }
          }
        }
      }
      closedir($dh);
    }
    return $list;
  }

  public function getLanguages() {
    $list = array();
    if(self::$soundsdir = self::getsoundsdir()) {
      $list = array();
      if($dh = opendir(self::$soundsdir)) {
        while(($file = readdir($dh)) !== false) {
          if(is_dir(self::$soundsdir . '/' . $file)) {
            if($file[0]!='.') {
              $list[] = $file;
            }
          }
        }
        closedir($dh);
      }
    }
    ksort($list, SORT_STRING);
    return $list;
  }

  public function get() {
    $list = array();
    if(self::$soundsdir = self::getsoundsdir()) {
      $list = array_merge($list, self::getdir(self::$soundsdir));
    }
    ksort($list, SORT_STRING);
    return $list;
  }

}

?>