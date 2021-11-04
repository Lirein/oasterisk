<?php

namespace core;

class NoOpApplication extends \dialplan\Application {

  static public $name = 'NoOp';

  private $arguments;

  /**
   * Конструктор приложения диалплана, принимает на вход параметры разделенные запятыми
   *
   * @param string $data Параметры диалплана
   */
  public function __construct(string $data) {
    parent::__construct($data);
  }

  /**
   * Получает значение параметра приложения
   *
   * @param string $property Наименование свойства
   * @return mixed Значение свойства
   */
  public function __get(string $property) {
    switch($property) {
      case 'arguments': return $this->arguments;
    }
    return null;
  }

  /**
   * Устанавливает новое значение свойства приложения диалплана
   *
   * @param string $property Наименование свойства
   * @param mixed $value Новое значение свойства
   */
  public function __set(string $property, $value) {
    switch($property) {
      case 'arguments': {
        $this->arguments = $value;
      } break;
    }
  }

  /**
   * Производит разбор данных и сохраняет их в свойствах приложения, вызывается в том числе из конструктора
   *
   * @param string $data Набор параметров диалплана, разделенных запятыми
   * @return void
   */
  protected function parse(string $data) {
    $this->arguments = $data;
  }

  /**
   * Возвращает свойства приложения диалплана
   *
   * @return \stdClass()
   */
  public function cast() {
    $result = new \stdClass();
    $result->name = self::$name;
    $result->arguments = $this->arguments;
    return $result;
  }

  /**
   * Преобразует параметры приложения в строку вида App(params)
   *
   * @return string
   */
  public function __toString() {
    return self::$name.'('.$this->arguments.')';
  }

  /**
   * Принимает на вход структуру параметров приложения
   *
   * @param \stdClass $data
   * @return void
   */
  public function assign(\stdClass $data) {
    foreach($data as $key => $value) {
      if($key == 'arguments') $this->arguments = $value;
    }
  }

}

?>