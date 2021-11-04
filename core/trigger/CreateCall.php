<?php

namespace trigger;

/**
 * @ingroup triggers
 * Класс-триггер обработчика генерирующий дополнительные вызываемые номера параллельно с контактом.
 */
abstract class CreateCall extends \Module {
 
  /**
   * Метод запускающий пыполнение триггера, возвращает Dial строку вызываемого контакта
   *
   * @return string Возвращвет Dial строку, либо null или false если дополнительный вызов не осуществляется
   */
  abstract public function exec();

}
?>