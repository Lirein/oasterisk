<?php

namespace core;

/**
 * Класс многоканального (транкового) подключения
 */
abstract class ChannelTrunk extends Module {
  
  /**
   * Объявляет бункцию которая должна возвращать список транков
   *
   * @return array Список транков
   */
  abstract public function getTrunks();
  
}

?>