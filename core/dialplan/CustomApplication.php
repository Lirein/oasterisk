<?php

namespace dialplan;

class CustomApplication extends Application {

  static public $name = null;

  private $arguments;
  private $appname;

  /**
   * Конструктор приложения диалплана, принимает на вход приложение(параметры разделенные запятыми)
   *
   * @param string $data Параметры диалплана
   */
  public function __construct(string $data) {
    $this->appname = 'NoOp';
    $this->arguments = array();
    parent::__construct(0);
    if(is_string($data)&&preg_match('/([A-Za-z_]+)\((.*)\)/', $data, $match)) {
      $this->appname = trim($match[1]);
      $this->parse(trim($match[2]));
    } elseif($data instanceof \stdClass) {
      $this->appname = $data->name;
      $this->assign($data);
    }
  }

  /**
   * Получает значение параметра приложения
   *
   * @param string $property Наименование свойства
   * @return mixed Значение свойства
   */
  public function __get(string $property) {
    switch($property) {
      case 'name': return $this->appname;
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
    return null;
  }

  /**
   * Производит разбор данных и сохраняет их в свойствах приложения, вызывается в том числе из конструктора
   *
   * @param string $data Набор параметров диалплана, разделенных запятыми
   * @return void
   */
  protected function parse(string $data) {
    $this->arguments = explode(',', $data);
    foreach($this->arguments as $key => $value) {
      $this->arguments[$key] = trim($value);
    }
  }

  /**
   * Возвращает свойства приложения диалплана
   *
   * @return \stdClass()
   */
  public function cast() {
    return (object)array(
      'name' => $this->appname,
      'arguments' => $this->arguments
    );
  }

  /**
   * Преобразует параметры приложения в строку вида App(params)
   *
   * @return string
   */
  public function __toString() {
    return $this->appname.'('.implode(', ', $this->arguments).')';
  }

  /**
   * Принимает на вход структуру параметров приложения
   *
   * @param \stdClass $data
   * @return void
   */
  public function assign(\stdClass $data) {
    if(isset($data->arguments)&&is_array($data->arguments)) {
      $this->arguments = array_splice($data->arguments, 0);
    }
  }

}

?>