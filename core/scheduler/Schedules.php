<?php

namespace scheduler;

/**
 * Класс определяющий коллекцию расписаний 
 */

class Schedules extends \module\Collection {
  
  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $schedjson = '[{"id": ""}]';
    $this->items = array();
    $items = \config\DB::readData('schedules', $schedjson);
    foreach($items as $schedule) {
      $this->items[] = $schedule->id;
    }
    reset($this->items);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $schedule = current($this->items);
    return new Schedule($schedule);
  }

}

?>