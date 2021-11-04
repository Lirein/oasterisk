<?php

namespace pjsip;

class PhoneProvisions extends \module\Collection {
  
  /**
   * Открытый INI файл с настройками pjsip
   *
   * @var \config\INI $ini
   */
  private $ini = null;

  public function __construct() {
    $this->ini = self::getINI('/etc/asterisk/pjsip.conf');
    parent::__construct();
  }
  
  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->items = array();
    foreach($this->ini as $k => $v) { 
      if(self::checkEffectivePriv('pjsip', $k, 'settings_reader')&&isset($v->type)&&($v->type=='phoneprov')) {
        $this->items[] = $k;
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
    $entry = current($this->items);
    if($this->interTestLock($entry)) return null;
    return new PhoneProvision($entry);
  }

}
?>