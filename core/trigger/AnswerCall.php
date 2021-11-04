<?php

namespace trigger;

/**
 * @ingroup triggers
 * Класс-триггер обработчика Ответа на вызов. Вызывается в канале назначения.
 */
abstract class AnswerCall extends \Module {
 
  /**
   * Метод запускающий пыполнение триггера
   *
   * @return bool Возвращвет истину в случае успешного выполнения триггера
   */
  abstract public function exec();

}
?>