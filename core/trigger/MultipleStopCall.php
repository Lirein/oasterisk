<?php

namespace trigger;

/**
 * @ingroup triggers
 * Класс-триггер обработчика окончания параллельного вызова по контакту для служебных каналов. Вызывается в канале назначения.
 */
abstract class MultipleStopCall extends \Module {
 
  /**
   * Метод запускающий пыполнение триггера
   *
   * @return bool Возвращвет истину в случае успешного выполнения триггера
   */
  abstract public function exec();

}
?>