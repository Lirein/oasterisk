<?php

namespace sound;

/**
 * Интерфейс реализующий коллекцию аудиозаписей
 */
class Sounds extends \module\MorphingCollection {

  public function __construct() {
    parent::__construct();
  }

  public static function getSoundsDir() {
    global $_AMI;
    static $data = null;
    static $pattern = 'Data directory:';
    if(!$data) {
      if(isset($_AMI)) {
        $info = $_AMI->command('core show settings');
        if(isset($info['data'])) $info = $info['data'];
        else if(isset($info['Output'])) $info = $info['Output'];
        $info = explode("\n", $info);
      } else {
        exec('asterisk -rx "core show settings"', $info);
      }
      foreach($info as $line) {
        $pos = stripos($line, $pattern);
        if($pos!==false) {
          $data = trim(substr($line, $pos + strlen($pattern))).'/sounds';
          break;
        }
      }
    }
    return $data;
  }

  public static function getLanguages() {
    static $list = array('other');
    static $soundsdir = null;
    if(!$soundsdir) {
      if($soundsdir = self::getSoundsDir()) {
        if($dh = opendir($soundsdir)) {
          while(($file = readdir($dh)) !== false) {
            if(is_dir($soundsdir . '/' . $file)) {
              if(($file[0]!='.') && (strlen($file)==2)) {
                $list[] = $file;
              }
            }
          }
          closedir($dh);
        }
      }
    }
    
    sort($list, SORT_STRING);
    return $list;
  }
  
  public static function getFormats() {
    $list = array();
    global $_AMI;
    static $data = null;
    if(!$data) {
      if(isset($_AMI)) {
        $info = $_AMI->command('core show file formats');
        if(isset($info['data'])) $info = $info['data'];
        else if(isset($info['Output'])) $info = $info['Output'];
        $info = explode("\n", $info);
      } else {
        exec('asterisk -rx "core show file formats"', $info);
      }
      array_splice($info, 0, 2);
      array_pop($info);
      foreach($info as $line) {
        if(preg_match('/([a-z0-9_-]+)\s+([a-z0-9_-]+)\s+([a-z0-9_|-]+)/', $line, $matches)) {
          $ext = explode('|', $matches[3]);
          $list[$ext[0]] = $matches[2];
        }
      }
      if (isset($list['pcm'])){
        $list['ulaw'] = 'ulaw';
        unset($list['pcm']);
      }
      asort($list, SORT_STRING);
      $data = $list;
    }
    return $data;
  }

  public static function getDir($dir = null, $language = 'other') {
    static $soundsdir = null;
    static $dircache = array();
    if(!$soundsdir) $soundsdir = self::getSoundsDir();
    if(strpos($dir, $soundsdir)!==0) $dir = $soundsdir.($dir?('/'.$dir):'');
    $list = array();
    if(isset($dircache[$dir])) {
      return $dircache[$dir];
    }
    if(is_dir($dir)) {
      if($dh = opendir($dir)) {
        while(($file = readdir($dh)) !== false) {
          if(is_dir($dir . '/' . $file)) {
            if($file[0]!='.') {
              if(($dir==$soundsdir)&&(strlen($file)==2)) {
                $tmplist = self::getDir($dir.'/'.$file, $file);
                $list = array_merge_recursive($list, $tmplist);
              } else {
                $list = array_merge_recursive($list, self::getDir($dir.'/'.$file, $language));
              }
            }
          } else {
            if($file[0]!='.') {
              $info = pathinfo($file);
              if(isset($info['extension'])) {
                if(in_array($info['extension'], array_keys(self::getFormats()))) {
                  $name = str_replace(($soundsdir.'/'.($language == 'other'?'':($language.'/'))),'',($dir . '/' . $info['filename']));
                  if (!isset($list[$name])){
                    $list[$name]=array($language => array($info['extension']));
                  } else {
                    $list[$name][$language][] = $info['extension'];
                  } 
                }
              }
            }
          }
        }
        closedir($dh);
      }
    } else {
      $info = pathinfo($dir);
      if(isset($info['extension'])&&in_array($info['extension'], array_keys(self::getFormats()))) {
        $name = str_replace(($soundsdir.'/'.($language == 'other'?'':($language.'/'))),'',($dir . '/' . $info['filename']));
        if (!isset($list[$name])){
          $list[$name]=array($language => array($info['extension']));
        } else {
          $list[$name][$language][] = $info['extension'];
        } 
      }    
    }
    $dircache[$dir] = $list;
    return $list;
  }

}
?>