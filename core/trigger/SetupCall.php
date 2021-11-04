<?php

namespace trigger;

/**
 * @ingroup triggers
 * Класс-триггер обработчика инициализации канала назначения. Вызывается в канале назначения.
 * Может выставлять параметры канала, в том числе SIP заголовки.
 */
abstract class SetupCall extends \Module {
 
  /**
   * Метод запускающий пыполнение триггера
   *
   * @return bool Возвращвет истину в случае успешного выполнения триггера
   */
  abstract public function exec();

}
?>