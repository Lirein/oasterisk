<?php

namespace dialplan;

class Variable extends \Module {

  private $name;

  private $from;

  private $length;

  /**
   * Конструктор приложения диалплана, принимает на вход параметры разделенные запятыми
   *
   * @param string $data Параметры диалплана
   */
  public function __construct(string $variable = null) {
    parent::__construct();
    $this->parse($variable);
  }

  /**
   * Получает значение параметра приложения
   *
   * @param string $property Наименование свойства
   * @return mixed Значение свойства
   */
  public function __get(string $property) {
    switch($property) {
      case 'name': return $this->name;
      case 'from': return $this->from;
      case 'length': return $this->length;
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
      case 'name': {
        $this->from = 0;
        $this->length = 0;
        $this->name = (string)$value;
      } break;
      case 'length': {
        $this->length = (int)$value;
      } break;
      case 'from': {
        $this->from = (int)$value;
        $this->length = 0;
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
    $this->length = 0;
    $this->from = 0;
    if(strpos($data, '${') === 0) {
      $data = substr($data, 2, -1);
    }
    $delims = explode(':', $data, 3);
    if(!empty($delims[0])) $this->name = $delims[0];
    if(!empty($delims[1])) $this->from = $delims[1];
    if(!empty($delims[2])) $this->length = $delims[2];
  }

  /**
   * Возвращает свойства приложения диалплана
   *
   * @return \stdClass()
   */
  public function cast() {
    return (object)array("name" => $this->name, 'from' => $this->from, 'length' => $this->length);
  }

  /**
   * Преобразует параметры приложения в строку вида App(params)
   *
   * @return string
   */
  public function __toString() {
    $result = '${'.$this->name;
    if($this->length||$this->from) $result .= ':';
    if($this->from) $result .= $this->from;
    if($this->length) $result .= ':'.$this->length;
    $result .= '}';
    return $result;
  }

  /**
   * Принимает на вход структуру параметров приложения
   *
   * @param \stdClass $data
   * @return void
   */
  public function assign(\stdClass $data) {
    if(isset($data->type)&&(strtolower($data->type)=='variable')&&isset($data->data)) {
      $this->assign($data->data);
    } else {
      foreach($data as $key => $value) {
        $this->__set($key, $value);
      }
    }
  }

}

?>