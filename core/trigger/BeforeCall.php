<?php

namespace trigger;

/**
 * @ingroup triggers
 * Класс-триггер обработчика до начала звонка контакту или по направлению. Вызывается в канале родителе.
 */
abstract class BeforeCall extends \Module {
 
  /**
   * Метод запускающий пыполнение триггера
   *
   * @return bool Возвращвет истину в случае успешного выполнения триггера
   */
  abstract public function exec();

}
?>