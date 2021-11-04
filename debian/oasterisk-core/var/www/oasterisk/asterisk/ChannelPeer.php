<?php

namespace core;

/**
 * Класс одноканального (абонентского) подключения
 */
abstract class ChannelPeer extends Module {
  
  /**
   * Объявляет функцию которая должна возвращать список абонентов
   *
   * @return array Список каналов
   */
  abstract public function getPeers();

}

?>