<?php

namespace security;

class ACLs extends \module\Collection {
  
  /**
   * Открытый INI файл
   *
   * @var \config\INI $ini
   */
  private $ini = null;

  public function __construct() {
    $this->ini = self::getINI('/etc/asterisk/acl.conf');
    parent::__construct();
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->items = array();
    foreach($this->ini as $key => $value) {
      $this->items[] = $key;
    }
    reset($this->items);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $acl = current($this->items);
    return new \security\ACL($acl);
  }

}
?>