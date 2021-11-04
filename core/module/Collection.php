<?php

namespace module;

/**
 * Интерфейс реализующий коллекцию субьектов
 */
abstract class Collection extends \Module implements ICollection {

  protected $items;

  /**
  * Функция должна возвращать тип cубъектов коллекции
  *
  * @return string
  */
  static function getTypeName() {
    $class = explode('\\', get_called_class());
    $class = array_pop($class);
    if(substr($class, -1) == 's') $class = substr($class, 0, -1);
    return $class;
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
    $this->items = array();
    $this->rewind();
  }

  /**
   * Создает новый элемент коллекции
   *
   * @param ISubject $subject Субъект который необходимо добавить в коллекцию. Контроль типов обязателен.
   * @return bool Возвращает истину если удалось добавить субьект в коллекцию.
   */
  public static function add(\module\ISubject &$subject) {
    self::notify(self::ADD, $subject);
    return true;
  }

  /**
   * Переименовывает элемент коллекции
   *
   * @param ISubject $subject Субъект который необходимо переименовать. Контроль типов обязателен.
   * @return bool Возвращает истину если удалось добавить переименовать субьект.
   */
  public static function rename(\module\ISubject &$subject) {
    self::notify(self::RENAME, $subject);
    return true;
  }

  /**
   * Изменяет элемент коллекции
   *
   * @param ISubject $subject Субъект который необходимо изменить. Контроль типов обязателен.
   * @return bool Возвращает истину если удалось изменить субьект.
   */
  public static function change(\module\ISubject &$subject) {
    self::notify(self::CHANGE, $subject);
    return true;
  }

  /**
   * Удаляет субьект из коллекции
   *
   * @param ISubject $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function remove(\module\ISubject &$subject) {
    self::notify(self::REMOVE, $subject);
    return true;
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
   * Осуществляет поиск субьекта с указанным идентификатором
   *
   * @param string $id Идентификатор субьекта
   * @return Subject
   */
  public static function find(string $id) {
    if(empty($id)) return null;
    $class = get_called_class();
    $iterator = new $class();
    $result = null;
    $iterator->rewind();
    while($key = $iterator->key()) {
      if($key == $id) {
        $result = $iterator->current(false);
        break;
      }
      $iterator->next();
    }
    return $result;
  }

}
?>