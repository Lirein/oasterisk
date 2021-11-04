<?php

namespace module;

class Licenses extends \module\Collection {

  private $licenses;

  public function __construct() {
    $licenses = getModulesByClass('module\License');
    $this->licenses = array();
    foreach($licenses as $license) {
      $info = $license->info();
      $this->licenses[$info->codename] = $license;
    }
    parent::__construct();
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->items = array_keys($this->licenses);
    reset($this->items);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $license = current($this->items);
    return $this->licenses[$license];
  }

}
?>