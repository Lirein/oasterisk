<?php

namespace trigger;

/**
 * @ingroup triggers
 * Класс-триггер обработчкиа окончания цепочки вызовов.
 */
abstract class EndCall extends \Module {
 
  /**
   * Метод запускающий пыполнение триггера
   *
   * @return bool Возвращвет истину в случае успешного выполнения триггера
   */
  abstract public function exec();

}
?>