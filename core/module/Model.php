<?php

namespace module;

/**
 * Интерфейс реализующий модель
 * Должен содержать набор приватных свойств и геттеры/сеттеры для их обработки
 * Метод save - сохраняет модель
 * Метод reload 
 */
abstract class Model extends \Module implements IModel {
    
  // /**
  //  * Конструктор без аргументов - инициализирует модель
  //  */
  // public function __construct();

  // /**
  //  * Метод осуществляет проверку существования приватного свойства и возвращает его значение
  //  *
  //  * @param mixed $property Имя свойства
  //  * @return mixed Значение свойства
  //  */
  // public function __get($property);

  // /**
  //  * Метод осуществляет установку нового значения приватного свойства
  //  *
  //  * @param mixed $property Имя свойства
  //  * @param mixed $value Значение свойства
  //  */
  // public function __set($property, $value);

  // /**
  //  * Сохраняет модель
  //  *
  //  * @return bool Возвращает истину в случае успешного сохранения модели
  //  */
  // public function save();

  // /**
  //  * Перезагружает модель
  //  *
  //  * @return bool Возвращает истину в случае успешной перезагрузки
  //  */
  // public function reload();

  // /**
  //   * Возвращает все свойства в виде объекта со свойствами
  //   *
  //   * @return \stdClass
  //   */
  // public function cast();

  // /**
  //   * Устанавливает все свойства новыми значениями
  //   *
  //   * @param stdClass $assign_data Объект со свойствами - ключ→значение 
  //   */
  // public function assign($assign_data);

}
?>