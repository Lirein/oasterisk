<?php

namespace core;

/**
 * Интерфейс реализующий субьект коллекции
 * Должен содержать набор приватных свойств и геттеры/сеттеры для их обработки
 * Метод save - сохраняет субьект
 * Метод delete вызывает метод delete класса коллекции
 */
class AddressBookGroup extends \module\Subject implements \module\ICollection {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \core\AddressBookGroups $collection
   */
  static $collection = 'core\\AddressBookGroups';

  public static $restinterface = 'core\AddressbookREST';

  private $contactList = array();

  /**
  * Функция должна возвращать тип cубъектов коллекции
  *
  * @return string
  */
  static function getTypeName() {
    return 'Record';
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
   * Конструктор с идентификатором - инициализирует субьект коллекции
   * 
   * @param string $id Идентификатор элемента коллекции. Если идентификатор не задан, генерирует новый идентификатор, прежний идентификатор равен null. Если идентификатор задан - ищет субьект с указанным идентификатором или возвращает исключение в случае его отсутствия.
   */
  public function __construct(string $id = null){
    AddressBookGroups::init();
    parent::__construct($id);
    $this->id = null;
    $this->data->name = '';
    $this->rewind();
    $stmt = @AddressBookGroups::$database->prepare('select * from `book` WHERE `id` = :id');
    $res = false;
    if($stmt) {
      $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
      $res = $stmt->execute();
    }
    if ($res){
      $data = $res->fetchArray(SQLITE3_ASSOC);
      $this->id = $data['id'];
      $this->old_id = $data['id'];
      $this->data->name = $data['name'];
    } 
    if(!$this->data->name) $this->data->name = $id;
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */

  public function __get($property){
    if(($property == 'name') || ($property == 'title')) return $this->data->name; 
    return parent::__get($property);
  }

  
  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){ 
    if(($property=='name') || ($property == 'title')) {
      $this->name = $value;
      $this->changed = true;
      return true;
    } 
    return parent::__set($property, $value);
  }

  /**
   * Сохраняет субьект в коллекции
   *
   * @return bool Возвращает истину в случае успешного сохранения субъекта
   */
  public function save() {
    $result = false;
    if(empty($this->old_id)) {
      $stmt = AddressBookGroups::$database->prepare('INSERT INTO `book` (`name`) VALUES (:name)');
    } else {
      $stmt = @AddressBookGroups::$database->prepare('UPDATE `book` SET `name` = :name WHERE `id` = :id'); 
      if($stmt){
        $stmt->bindValue(':id', $this->id, SQLITE3_INTEGER);
      } 
    }
    if($stmt) {
      $stmt->bindValue(':name', $this->name, SQLITE3_TEXT);
      
      $result = $stmt->execute();
    }
    if($result) {
      if(empty($this->old_id)) {
        $this->id = AddressBookGroups::$database->lastInsertRowID();
        \core\AddressBookGroups::add($this); 
      } else {
        \core\AddressBookGroups::change($this);
      }
      $this->old_id = $this->id;
    }
    return $result;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    $stmt = AddressBookGroups::$database->prepare("DELETE FROM `book` WHERE `id` = :id");
    $result = false;
    if($stmt) {
      $stmt->bindValue(':id', $this->old_id, SQLITE3_INTEGER);
      $result = $stmt->execute();
    }
    if($result) {
      \core\AddressBookGroups::remove($this);
      return true;
    }
    return false;
  }

  public function reload(){
    
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast(){
    $keys = array();
    $keys['id'] = $this->__get('id');
    $keys['name'] = $this->__get('name');
    $keys['contacts'] = array();
    foreach($this as $contact) {
      $keys['contacts'][] = $contact->cast();
    }
    return (object)$keys;
  }

  /**
   * Создает новый элемент коллекции
   *
   * @param \core\AddressBookContact $subject Субъект который необходимо добавить в коллекцию. Контроль типов обязателен.
   * @return bool Возвращает истину если удалось добавить субьект в коллекцию.
   */
  public static function add(\module\ISubject &$subject) {
    self::notify(self::ADD, $subject);
  }

  /**
   * Переименовывает субьект коллекции
   *
   * @param \core\AddressBookContact $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function rename(\module\ISubject &$subject) {

    self::notify(self::RENAME, $subject);
  }

  /**
   * Изменяет субьект коллекции
   *
   * @param \core\AddressBookContact $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function change(\module\ISubject &$subject) {
    self::notify(self::CHANGE, $subject);
  }

  /**
   * Удаляет субьект из коллекции
   *
   * @param \core\AddressBookContact $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function remove(\module\ISubject &$subject) {
    self::notify(self::REMOVE, $subject);    
  }

  /**
   * Метод возвращает количество элементов коллекции
   *
   * @return integer Возвращает количество элементов коллекции
   */
  public function count() {
    return count($this->contactList);
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $stmt = @AddressBookGroups::$database->prepare('select * from `contact` where `book_id` = :book');
    $result = false;
    if($stmt) {
      $stmt->bindValue(':book', $this->id, SQLITE3_INTEGER);
      $result = $stmt->execute();
    }
    $this->contactList = array();
    while($contact = $result->fetchArray(SQLITE3_ASSOC)) {
      $this->contactList[] = $contact['id'].'@'.$this->id;
    }
    reset($this->contactList);   
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $contact= current($this->contactList);
    return new AddressBookContact($contact);
  }

  /**
   * Возвращает ключ текущего элемента массива полей
   *
   * @return int|string|null Возвращает ключ текущего элемента или же NULL при неудаче. 
   */
  public function key() {
    return current($this->contactList);
  }

  /**
   * Передвигает текущую позицию к следующему элементу массива полей
   *
   * @return void
   */
  public function next() {
    return next($this->contactList);
  }

  /**
   * Проверяет корректность текущей позиции массива полей
   *
   * @return bool Возвращает TRUE в случае успешного завершения или FALSE в случае возникновения ошибки
   */
  public function valid() {
    return (key($this->contactList) !== null);
  }

  /**
   * Возвращает перечень ключей идентификаторов 
   *
   * @return string[]
   */
  public function keys() {
    $keys = array();
    $this->rewind();
    while($key = $this->key()) {
      $keys[] = $key;
      $this->next();
    }
    return $keys;
  }

  /**
   * Осуществляет поиск субьекта с указанным идентификатором
   *
   * @param string $id Идентификатор субьекта
   * @return \core\AddressBookContact
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