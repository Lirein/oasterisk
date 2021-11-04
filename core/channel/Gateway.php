<?php

namespace channel;

/**
 * Класс шлюза
 */
abstract class Gateway extends Trunk {
  
  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \channel\Gateways $collection
   */
  static $collection = 'channel\\Gateways';

}

?>