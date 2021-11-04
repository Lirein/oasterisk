<?php

namespace dialplan;

use Iterator;

class Context extends \module\Subject implements Iterator {
  
  /**
   * Ссылка на INI файл настроек
   *
   * @var \INIProcessor $ini
   */
  private $ini = null;
 
  private $minimal;

  protected $items;

  /**
   * Конструктор с идентификатором - инициализирует модель
   */
  public function __construct(string $id = null, bool $minimal = false) {
    $this->ini = self::getINI('/etc/asterisk/extensions.conf');
    $this->minimal = $minimal;
    parent::__construct($id);
    if(isset($this->ini->$id)) {
      $this->data->title = $this->ini->$id->getComment();
      if(empty($this->data->title)) $this->data->title = $id;
      $this->old_id = $id;
    } else {
      $this->data->title = $id;
    }
    $this->rewind();
    $this->id = $id;
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    parent::__destruct();
    unset($this->ini);
  }

  public function __get($property){
    if($property=='title') return parent::__get($property);
    if(isset($this->data->$property)) return $this->_extract($this->data->$property, $property);
    return parent::__get($property);
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    if(is_array($value)) {
      $this->data->$property = $value;
      $this->changed = true;
      return true;
    }
    return parent::__set($property, $value);
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save() {
    $sectionname = $this->id;
    if(!$sectionname) return false;
    if(!$this->changed) return false;

    if($this->old_id!==null) {
      if($this->id!=$this->old_id) {
        Dialplan::rename($this);
      } else {
        Dialplan::change($this);
      }
    } else { //Создаем расписание
      Dialplan::add($this);
    }
    return true;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    $sectionname = $this->old_id;
    Dialplan::remove($this);
    return true;
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    if($this->ami) {
      $this->ami->send_request('Command', array('Command' => 'dialplan reload'));
      return true;
    } else {
      system('asterisk -rx "dialplan reload"');
      return true;
    }
  }

  public function count() {
    return count($this->items);
  }

  /**
   * Возвращает ключ текущего элемента массива полей
   *
   * @return int|string|null Возвращает ключ текущего элемента или же NULL при неудаче. 
   */
  public function key() {
    return current($this->items);
  }

  /**
   * Передвигает текущую позицию к следующему элементу массива полей
   *
   * @return void
   */
  public function next() {
    next($this->items);
  }

  /**
   * Проверяет корректность текущей позиции массива полей
   *
   * @return bool Возвращает TRUE в случае успешного завершения или FALSE в случае возникновения ошибки
   */
  public function valid() {
    return (key($this->items) !== null);
  }

    /**
   * Возвращает перечень ключей идентификаторов 
   *
   * @return string[]
   */
  public function keys() {
    $this->rewind();
    return array_values($this->items);
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->items = array();
    if($this->old_id) {
      $id = $this->old_id;
      foreach($this->ini->$id as $exten => $value) {
        if($value instanceof \config\INIPropertyExten) {
          $this->data->$exten = $value->getValue();
          $this->items[] = $exten;
        }
      }
    }
    reset($this->items);
  }

  protected function _extract($actions, $exten) {
    $actiondata = array();
    foreach($actions as $prio => $action) {
      if($this->minimal) {
        if(strpos($action, 'AGI(')===0) {
          $app = Application::find($action);
        } else {
          $app = new CustomApplication($action);
        }
        if($app) {
          $app->alias = $action->getAlias();
          $app->title = $action->getComment()?$action->getComment():$exten;
          $actiondata[$prio] = $app;
        }
      } else {
        $app = Application::find($action);
        if($app) {
          $app->alias = $action->getAlias();
          $app->title = $action->getComment()?$action->getComment():$exten;
          $actiondata[$prio] = $app;
        }
      }
    }
    return $actiondata;
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return Application[]
   */
  public function current() {
    $exten = current($this->items);
    if(isset($this->data->$exten)) {
      return $this->_extract($this->data->$exten, $exten);
    }
    return null;
  }

}

?>