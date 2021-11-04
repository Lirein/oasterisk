<?php

namespace channel;

/**
 * Интерфейс реализующий субьект коллекции
 * Должен содержать набор приватных свойств и геттеры/сеттеры для их обработки
 * Метод save - сохраняет субьект
 * Метод delete вызывает метод delete класса коллекции
 */
abstract class Line extends \module\MorphingSubject {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \channel\Lines $collection
   */
  static $collection = 'channel\\Lines';

  static function getTypeName() {
    return static::$collection::getTypeName();
  }

  static function getTypeTitle() {
    return static::$collection::getTypeTitle();
  }
}
?>