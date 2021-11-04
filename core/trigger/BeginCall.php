<?php

namespace trigger;

/**
 * @ingroup triggers
 * Класс-триггер обработчика вызываемый при инициализации звонка состоящего из серии вызовов
 */
abstract class BeginCall extends \Module {
 
  /**
   * Метод запускающий пыполнение триггера
   *
   * @return bool Возвращвет истину в случае успешного выполнения триггера
   */
  abstract public function exec();

}
?>