<?php

namespace security;

class Users extends \module\Collection {
  
  /**
   * Открытый INI файл с 
   *
   * @var \config\INI $ini
   */
  private static $ini = null;

  private static function init() {
    if(!self::$ini) self::$ini = self::getINI('/etc/asterisk/manager.conf');
  }

  public function __construct() {
    self::init();
    parent::__construct();
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->items = array();
    foreach(self::$ini as $key => $value) {
      if(isset($value->secret)) $this->items[] = $key;
    }
    reset($this->items);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $user = current($this->items);
    return new \security\User($user);
  }

}
?>