<?php

namespace core;

class IVRs extends \module\Collection {
  
  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $jsondata = '[{"id": ""}]';
    $this->items = array();
    $items = \config\DB::readData('ivr', $jsondata);
    if($items) foreach($items as $ivr) {
      $this->items[] = $ivr->id;
    }
    reset($this->items);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $ivr = current($this->items);
    return new \core\IVR($ivr);
  }

}
?>