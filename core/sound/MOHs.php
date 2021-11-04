<?php

namespace sound;

class MOHs extends \module\Collection {
  
  /**
   * Открытый INI файл с доступными модулями MusicOnHold
   *
   * @var \config\INI $ini
   */
  private $ini = null;

  public function __construct() {
    $this->ini = self::getINI('/etc/asterisk/musiconhold.conf');
    parent::__construct();
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->items = array();
    foreach($this->ini as $sectionkey => $section) {
      $this->items[] = $sectionkey;
    }
    reset($this->items);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $section = current($this->items);
    return new MOH($section);
  }

  public static function getMOHDirectory() {
    $ini = self::getINI('/etc/asterisk/asterisk.conf');
    $return = (string) $ini->directories->astdatadir;
    unset($ini);
    return $return;
  }

  private function sortlist($a, $b) {
    return strcmp($a->title, $b->title);
  }

  public function getMOHDirectories($dir = '', $rootdir = '') {
    if($rootdir == '') $rootdir = self::getMOHDirectory();
    $list = array();
    if($dh = opendir($rootdir . ($dir?('/'.$dir):''))) {
      while(($file = readdir($dh)) !== false) {
        if(is_dir($rootdir . ($dir?('/'.$dir):'') . '/' . $file)) {
          if($file[0]!='.') {
            $directryinfo = (object) array('id' => ($dir?($dir.'/'):'').$file, 'title' => $file, 'icon' => 'FolderIcon');
            $directryinfo->value = $this->getMOHDirectories(($dir?($dir.'/'):'').$file, $rootdir);
            if(count($directryinfo->value)==0) unset($directryinfo->value);
            $list[] = $directryinfo;
          }
        }
      }
      closedir($dh);
    }
    usort($list, array($this, 'sortlist'));
    return $list; 
  }

  public function getMOHFiles($dir = '', $rootdir = '') {
    if($rootdir == '') $rootdir = self::getMOHDirectory();
    $list = array();
    if($dh = opendir($rootdir . ($dir?('/'.$dir):''))) {
      while(($file = readdir($dh)) !== false) {
        if(is_dir($rootdir . ($dir?('/'.$dir):'') . '/' . $file)) {
          if($file[0]!='.') {
            $directoryinfo = (object) array('id' => ($dir?($dir.'/'):'').$file, 'title' => $file, 'icon' => 'FolderIcon');
            $directoryinfo->value = $this->getMOHFiles(($dir?($dir.'/'):'').$file, $rootdir);
            $list[] = $directoryinfo;
          }
        } else {
          $info = pathinfo($file);
          if(isset($info['extension'])&&in_array($info['extension'], array_keys(\sound\Sounds::getFormats()))) {
          //if(strpos(mime_content_type($rootdir . ($dir?('/'.$dir):'') . '/' . $file), 'audio')!==false) {
            $directoryinfo = (object) array('id' => ($dir?($dir.'/'):'').$file, 'title' => $file, 'icon' => 'AudiotrackIcon');
            $list[] = $directoryinfo;
          }
        }
      }
      closedir($dh);
    }
    usort($list, array($this, 'sortlist'));
    return $list; 
  }

}
?>