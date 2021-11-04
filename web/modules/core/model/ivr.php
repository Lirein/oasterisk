<?php

namespace core;

class IVR extends \module\Subject implements \module\ICollection {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \core\IVRs $collection
   */
  static $collection = 'core\\IVRs';

  public static $restinterface = 'core\IVRREST';

  private $items;

  private static $jsondata = '{
    "id": "",
    "title": "",
    "main": ""
  }';

  /**
  * Функция должна возвращать тип cубъектов коллекции
  *
  * @return string
  */
  static function getTypeName() {
    return 'Action';
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
    parent::__construct($id);
    $this->rewind();
    $item = \config\DB::readDataItem('ivr','id', $id, self::$jsondata);
    if($item) {
      $this->old_id = $id;
      $this->data = $item;
      if(empty($this->data->title)) $this->data->title = $id;
    }
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */

  public function __get($property){
    if(($property == 'name') || ($property == 'title')) return $this->data->title; 
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
      $this->data->title = $value;
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
    if(!$this->changed) return true;

    $this->lock('ivr');
    if (!$this->id) $this->id = (new self::$collection())->newID();
    $id = $this->id;
    if($this->old_id!==null) {
      if($this->id!=$this->old_id) {
        \core\IVRs::rename($this);
        $oldid = $this->old_id;
        foreach($this as $action) {
          $action->ivr = $this->id;
          $action->save();
        }
        \config\DB::deleteDataItem('ivr', 'id', $oldid, self::$jsondata);
      } else {
        \core\IVRs::change($this);
      }
    } else { //Создаем расписание
      \core\IVRs::add($this);
    }
    $olddata = clone $this->data;
    \config\DB::writeDataItem('ivr', 'id', $id, self::$jsondata, $olddata);
    $this->old_id = $this->id;
    $this->unlock('ivr');
    return true;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    $result = true;
    foreach($this as $action) {
      $result &= $action->delete();
    }
    if(\config\DB::deleteDataItem('ivr', 'id', $this->old_id, self::$jsondata)) {
      \core\IVRs::remove($this);
      return $result;
    }
    return false;
  }

  public function reload(){
    if($this->ami) {
      $this->ami->send_request('Command', array('Command' => 'dialplan reload'));
      return true;
    } else {
      system('asterisk -rx "dialplan reload"');
      return true;
    }
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast(){
    $keys = array();
    $keys['id'] = $this->__get('id');
    $keys['title'] = $this->__get('title');
    $keys['actions'] = array();
    foreach($this as $action) {
      $keys['actions'][] = $action->cast();
    }
    return (object)$keys;
  }

  /**
   * Создает новый элемент коллекции
   *
   * @param \core\IVRAction $subject Субъект который необходимо добавить в коллекцию. Контроль типов обязателен.
   * @return bool Возвращает истину если удалось добавить субьект в коллекцию.
   */
  public static function add(\module\ISubject &$subject) {
    self::notify(self::ADD, $subject);
  }

  /**
   * Переименовывает субьект коллекции
   *
   * @param \core\IVRAction $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function rename(\module\ISubject &$subject) {

    self::notify(self::RENAME, $subject);
  }

  /**
   * Изменяет субьект коллекции
   *
   * @param \core\IVRAction $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function change(\module\ISubject &$subject) {
    self::notify(self::CHANGE, $subject);
  }

  /**
   * Удаляет субьект из коллекции
   *
   * @param \core\IVRAction $subject Субьект который необходимо удалить из коллекции.
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
    return count($this->items);
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->items = array();
    $dialplan = new \dialplan\Dialplan();  
    foreach($dialplan->keys() as $context) {
      if(strpos($context, 'ivr-'.$this->old_id.'-')===0) {
        $this->items[] = substr($context, strlen('ivr-'.$this->old_id.'-'));
      }
    }
    reset($this->items);   
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $action= current($this->items);
    return new IVRAction($action);
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
    return next($this->items);
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
   * @return \core\IVRAction
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