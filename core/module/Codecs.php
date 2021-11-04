<?php

namespace module;

class Codecs extends \module\Collection {

  private $codecinfo;

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->items = array();
    $this->codecinfo = array();
    $codeclist = findModulesByClass('module\Codec');
    foreach($codeclist as $module) {
      $classname = $module->class;
      $info = $classname::info();
      $this->items[] = $info->name;
      $this->codecinfo[$info->name] = $classname;
    }
    reset($this->items);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return \module\Codec
   */
  public function current() {
    $codec = current($this->items);
    if(isset($this->codecinfo[$codec])) {
      $classname = $this->codecinfo[$codec];
      if(!class_exists($classname)) return null;
      return new $classname($codec);
    }
    return null;
  }

}
?>