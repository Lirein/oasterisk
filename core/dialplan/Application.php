<?php

namespace dialplan;

abstract class Application extends \Module {

  static public $name;

  public $title;

  public $alias;

  /**
   * Осуществляет поиск приложения по его строке спецификации вида App(arguments) и возвращает соответствующий экземпляр приложения
   * Если аргумент передан не верно, возвращает NULL
   *
   * @param string $application Строка представления для вызова приложения диалплана
   * @return \dialplan\Application Экземпляр приложения диалплана
   */
  public static function find(string $application) {
    $result = null;
    $applicationname = null;
    if(is_string($application)&&preg_match('/([A-Za-z_]+)\((.*)\)/', $application, $match)) {
      $applicationname = strtolower(trim($match[1]));
      $applicationdata = trim($match[2]);
    } elseif($application instanceof \stdClass) {
      $applicationname = $application->name;
      $applicationdata = $application;
    }
    if($applicationname) {
      $applications = findModulesByClass('dialplan\\Application');
      foreach($applications as $module) {
        $classname = $module->class;
        if(strtolower($classname::$name)==$applicationname) {
          $result = new $classname($applicationdata);
          break;
        }
      }
      if(!$result) {
        $result = new \dialplan\CustomApplication($application);
      }
    }
    return $result;
  }

  /**
   * Конструктор приложения диалплана, принимает на вход параметры разделенные запятыми
   *
   * @param string $data Параметры диалплана
   */
  public function __construct(string $data) {
    parent::__construct();
    if(is_string($data)) {
      $this->parse($data);
    } elseif($data instanceof \stdClass) {
      $this->assign($data);
    }
  }

  /**
   * Получает значение параметра приложения
   *
   * @param string $property Наименование свойства
   * @return mixed Значение свойства
   */
  abstract public function __get(string $property);

  /**
   * Устанавливает новое значение свойства приложения диалплана
   *
   * @param string $property Наименование свойства
   * @param mixed $value Новое значение свойства
   */
  abstract public function __set(string $property, $value);

  /**
   * Производит разбор данных и сохраняет их в свойствах приложения, вызывается в том числе из конструктора
   *
   * @param string $data Набор параметров диалплана, разделенных запятыми
   * @return void
   */
  abstract protected function parse(string $data);

  /**
   * Возвращает свойства приложения диалплана
   *
   * @return \stdClass()
   */
  abstract public function cast();

  /**
   * Преобразует параметры приложения в строку вида App(params)
   *
   * @return string
   */
  abstract public function __toString();

  /**
   * Принимает на вход структуру параметров приложения
   *
   * @param \stdClass $data
   * @return void
   */
  abstract public function assign(\stdClass $data);

}

?>