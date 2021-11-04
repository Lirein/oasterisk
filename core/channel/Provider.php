<?php

namespace channel;

/**
 * Класс провайдера
 */
abstract class Provider extends Trunk {
  
  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \channel\Providers $collection
   */
  static $collection = 'channel\\Providers';

}

?>