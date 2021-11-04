<?php

namespace core;

class IPv4Collection extends \module\Collection {
  
  /**
   * Открытый INI файл с 
   *
   * @var \config\NetPlan $ini
   */
  private $netplan = null;

  public function __construct() {
    $this->netplan = new \config\NetPlan('/etc/netplan/test.yaml');
    parent::__construct();
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->items = array();
    foreach ($this->netplan->ethernets as $adapter) {
      if (isset($adapter->addresses)) {
        foreach ($adapter->addresses as $ip) {
          
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
    $ip = current($this->items);
    return new IPv4($ip);
  }

}
?>