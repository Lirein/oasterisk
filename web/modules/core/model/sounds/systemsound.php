<?php

namespace core;

class SystemSound extends \sound\Sound {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \core\SystemSounds $collection
   */
  static $collection = 'core\\SystemSounds';

  public function __construct(string $id = null) {
    parent::__construct($id);
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
    return parent::__get($property);
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    return parent::__set($property, $value);
  }

}

?>
