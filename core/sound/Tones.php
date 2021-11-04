<?php

namespace sound;

class Tones extends \module\Collection {
  
  /**
   * Открытый INI файл с доступными модулями MusicOnHold
   *
   * @var \config\INI $ini
   */
  private $ini = null;

  public function __construct() {
    $this->ini = self::getINI('/etc/asterisk/indications.conf');
    parent::__construct();
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->items = array();
    foreach($this->ini as $sectionname => $section) {
      if($sectionname != 'globals') {
        foreach($section as $key => $value) {
          if(!in_array($key, $this->items)) $this->items[] = $key;
        }
      }
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
    return new Tone($section);
  }

}
?>