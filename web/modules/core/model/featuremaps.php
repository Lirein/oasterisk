<?php

namespace core;

class FeatureMaps extends \module\Collection {
  
  /**
   * Открытый INI файл с 
   *
   * @var \config\INI $ini
   */
  private $ini = null;

  public function __construct() {
    $this->ini = self::getINI('/etc/asterisk/features.conf');
    parent::__construct();
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->items = array();
    foreach($this->ini->applicationmap as $key => $value) {
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
    $applicationmap = current($this->items);
    return new FeatureMap($applicationmap);
  }

}
?>