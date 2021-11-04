<?php

namespace module;

use Error;

/**
 * Интерфейс реализующий коллекцию субьектов
 */
class MorphingCollection extends \Module implements \module\ICollection {

  /**
   * Список экземпляров объектов дочерних коллекций
   *
   * @var MorphingCollection $_subcollections[]
   */
  private $_subcollections = null;

  /**
   * Текущий выбранный подкласс мутирующей коллекции
   *
   * @var MorphingCollection $_currentclass
   */
  private $_currentclass = null;

  static $type = null;

  /**
  * Функция должна возвращать тип cубъектов коллекции
  *
  * @return string
  */
  static function getTypeName() {
    return null;
  }

  /**
  * Функция должна возвращать новый идентификатор коллекции
  *
  * @return string
  */
  function newID() {
    static $basename = null;
    if($basename === null) $basename = static::getTypeName().'_';
    if($basename === null) return null;
    $cb = function($value) {
      static $basename = null;
      if($basename === null) $basename = static::getTypeName().'_';
      if((strpos($value, $basename)===0)&&(is_numeric(substr($value, strlen($basename))))) return true;
      return false;
    };
    if(count($this->items)==0) $this->rewind();
    $keys = array_filter(array_values($this->items), $cb);
    natsort($keys);
    $lastkey = array_pop($keys);
    $result = 1;
    if($lastkey) {
      $result = substr($lastkey, strlen($basename))+1;
    }
    $result = $basename.$result;
    return $result;
  }

  /**
   * Конструктор без аргументов - инициализирует коллекцию объектов
   */
  public function __construct() {
    parent::__construct();
    $this->_subcollections = array();
    $subcollections = findModulesByMainClass(get_called_class());
    if(!$subcollections) {
      self::log('WARNING', 'Abstract collection class detected: '.get_called_class());
    } else {
      foreach($subcollections as $module) {
        $classname = $module->class;
        if(in_array('module\\ISubject', $module->parentclass)) {
          $classname = $classname::$collection;
          $mainmodule = new $classname();
          foreach($mainmodule as $collection) {
            $this->_subcollections[] = $collection;
          }
        } else {
          $this->_subcollections[] = new $classname();
        }
      }    
    }
  }

  /**
   * Возвращает текущий подкласс коллекции
   *
   * @return MorphingCollection
   */
  function getCurrentClass() {
    return $this->_currentclass;
  }

  /**
   * Создает новый элемент коллекции
   *
   * @param MorphingSubject $subject Субъект который необходимо добавить в коллекцию. Контроль типов обязателен.
   * @return bool Возвращает истину если удалось добавить субьект в коллекцию.
   */
  public static function add(\module\ISubject &$subject) {
    self::notify(self::ADD, $subject);
  }

  /**
   * Переименовывает субьект коллекции
   *
   * @param MorphingSubject $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function rename(\module\ISubject &$subject) {
    self::notify(self::RENAME, $subject);
  }

  /**
   * Изменяет субьект из коллекции
   *
   * @param \MorphingSubject $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function change(\module\ISubject &$subject) {
    self::notify(self::CHANGE, $subject);
  }

  /**
   * Удаляет субьект из коллекции
   *
   * @param \MorphingSubject $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function remove(\module\ISubject &$subject) {
    self::notify(self::REMOVE, $subject);
    return true;
  }

  /**
   * Метод возвращает количество элементов коллекции
   *
   * @return integer Возвращает количество элементов коллекции
   */
  public function count() {
    $count = 0;
    foreach($this->_subcollections as $subcollection) {
      $count += $subcollection->count();
    }
    return $count;
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    foreach($this->_subcollections as $subcollection) $subcollection->rewind();
    reset($this->_subcollections);
    $this->_currentclass = current($this->_subcollections);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $subcollection = current($this->_subcollections);
    if($subcollection) return $subcollection->current();
    return null;
  }

  /**
   * Возвращает ключ текущего элемента массива полей
   *
   * @return int|string|null Возвращает ключ текущего элемента или же NULL при неудаче. 
   */
  public function key() {
    $subcollection = current($this->_subcollections);
    if($subcollection&&$subcollection->count()) {
      return $subcollection->key();
    } else {
      $this->next();
      $subcollection = current($this->_subcollections);
      if($subcollection) {
        return $subcollection->key();
      }
    }
    return null;
  }

  /**
   * Передвигает текущую позицию к следующему элементу массива полей
   *
   * @return void
   */
  public function next() {
    $subcollection = current($this->_subcollections); //Текущая коллекция
    if($subcollection) {
      $this->_currentclass = $subcollection;
      $subcollection->next(); //Получаем следующий элемент коллекции
      $next = $subcollection->key();
      if($next) return; //Если последний элемент - ищем первый элемент следующей коллекции
      next($this->_subcollections);
      while($subcollection = current($this->_subcollections)) {
        $this->_currentclass = $subcollection;
        if($subcollection->count()>0) return;
        next($this->_subcollections);
      }
    } else {
      $this->_currentclass = null;
    }
    return;
  }

  /**
   * Проверяет корректность текущей позиции массива полей
   *
   * @return bool Возвращает TRUE в случае успешного завершения или FALSE в случае возникновения ошибки
   */
  public function valid() {
    $subcollection = current($this->_subcollections);
    if($subcollection&&$subcollection->count()) {
      return $subcollection->valid();
    } else {
      $this->next();
      $subcollection = current($this->_subcollections);
      if($subcollection) {
        return $subcollection->valid();
      }
    }
    return null;
  }

  /**
   * Возвращает перечень ключей идентификаторов 
   *
   * @return string[]
   */
  public function keys() {
    $keys = array();
    $this->rewind();
    foreach($this->_subcollections as $collection) {
      $keys += $collection->keys();
    }
    return $keys;
  }

  /**
   * Осуществляет поиск субьекта с указанным идентификатором
   *
   * @param string $id Идентификатор субьекта
   * @return MorphingSubject
   */
  public static function find(string $id) {
    $class = get_called_class();
    $iterator = new $class();
    $result = null;
    $iterator->rewind();
    while($key = $iterator->key()) {
      if($key == $id) {
        $result = $iterator->current();
        break;
      }
      $iterator->next();
    }
    return $result;
  }

}
?>