<?php

namespace core;

class Grammars extends \module\Collection {
  
  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $gramjson = '[{"id": ""}]';
    $this->items = array();
    $items = \config\DB::readData('grammars', $gramjson);
    foreach($items as $grammar) {
      $this->items[] = $grammar->id;
    }
    reset($this->items);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $grammar = current($this->items);
    return new Grammar($grammar);
  }

}

?>