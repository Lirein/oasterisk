<?php

namespace core;

class AGIApplication extends \dialplan\Application {

  static public $name = 'AGI';

  private $scriptname;

  private $arguments;

  private $appclass;

  /**
   * Конструктор приложения диалплана, принимает на вход параметры разделенные запятыми
   *
   * @param string $data Параметры диалплана
   */
  public function __construct(string $data) {
    $this->scriptname = '';
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
      case 'script': return $this->scriptname;
      case 'agiclass': return $this->appclass;
      case 'native': return $this->appclass!==null;
      default: {
        if(isset($this->arguments->$property)) {
          return $this->arguments->$property;
        }
      } break;
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
      case 'script': {
        $this->scriptname = $value;
        $this->findApp();
      } break;
      default: {
        if($value === false) {
          if(isset($this->arguments->$property)) unset($this->arguments->$property);
        } else {
          $this->arguments->$property = $value;
        }
        if($property == 'agiclass') $this->findApp();
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
    $arguments = explode(',', $data);
    $this->scriptname = array_shift($arguments);
    $this->arguments = new \stdClass();
    foreach($arguments as $argument) {
      $argument = trim($argument);
      if(($eqpos = strpos($argument, '='))!==0) {
        $argumentname = trim(substr($argument,0,$eqpos));
        $this->arguments->$argumentname = trim(substr($argument,$eqpos+1));
      } else {
        $this->arguments->$argument = true;
      }
    }
    $this->findApp();
  }

  private function findApp() {
    $this->appclass = null;
    if($this->scriptname == 'oasterisk.php') {
      if(isset($this->arguments->agi)) {
        $agiclasses = findModulesByNamespace($this->arguments->agi);
        foreach($agiclasses as $module) {
          if(is_subclass_of($module->class, '\module\IAGI')) {
            $this->appclass = $module->class;
            break;
          }
        }
      }
    }
  }

  /**
   * Возвращает свойства приложения диалплана
   *
   * @return \stdClass()
   */
  public function cast() {
    $result = new \stdClass();
    $result->name = self::$name;
    $result->script = $this->scriptname;
    $result->native = $this->appclass!=null;
    foreach($this->arguments as $argument => $value) {
      $result->$argument = $value;
    }
    return $result;
  }

  /**
   * Преобразует параметры приложения в строку вида App(params)
   *
   * @return string
   */
  public function __toString() {
    $params = array();
    $params[] = $this->scriptname;
    foreach($this->arguments as $argument => $value) {
      if($value===true) {
        $params[] = $argument;
      } elseif($value!==false) {
        $params[] = $argument.'='.$value;
      }
    }
    return self::$name.'('.implode(', ', $params).')';
  }

  /**
   * Принимает на вход структуру параметров приложения
   *
   * @param \stdClass $data
   * @return void
   */
  public function assign(\stdClass $data) {
    foreach($data as $key => $value) {
      if(!in_array($key, array('native', 'name', 'title', 'alias', 'arguments'))) $this->__set($key, $value);
    }
  }

}

?>