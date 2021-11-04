<?php

namespace staff;

use Error;

/**
 * Интерфейс реализующий субьект коллекции
 * Должен содержать набор приватных свойств и геттеры/сеттеры для их обработки
 * Метод save - сохраняет субьект
 * Метод delete вызывает метод delete класса коллекции
 */
class Group extends \channel\Peers implements \module\ISubject {

  public static $restinterface = 'core\StaffREST';

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var Collection $collection
   */
  static $collection = 'staff\\Groups';

  private $id;

  private $old_id = null;

  private $title;

  private $domain;

  private $contactList = array();

  private $context;

  public static function getTypeName() {
    return 'Contact';
  }

  public static function getTypeTitle() {
    return 'Контакт';
  }

  /**
   * Конструктор с идентификатором - инициализирует субьект коллекции
   * 
   * @param string $id Идентификатор элемента коллекции. Если идентификатор не задан, генерирует новый идентификатор, прежний идентификатор равен null. Если идентификатор задан - ищет субьект с указанным идентификатором или возвращает исключение в случае его отсутствия.
   */
  public function __construct(string $id = null) {
    \Module::__construct();
    $this->context = \dialplan\Dialplan::find('staff-'.$id);
    $this->title = null;
    $this->domain = null;
    if($this->context->old_id) {
      $this->old_id = $id;
      $this->title = $this->context->title;
      if($semicolon = strpos($this->title, ':')!==false) {
        $this->domain = substr($this->title, $semicolon+1);
        $this->title = substr($this->title, 0, $semicolon);
      }
    }
    if(!$this->title) $this->title = $id;
    $this->id = $id;
  }

  public function __serialize() {
    $keys = array();
    $keys['id'] = $this->id;
    $keys['old_id'] = $this->old_id;
    $keys['title'] = $this->title;
    $keys['domain'] = $this->domain;
    $keys['context'] = serialize($this->context);
    return $keys;
  }

  public function __unserialize(array $keys) {
    $this->id = $keys['id'];
    $this->old_id = $keys['old_id'];
    $this->title = $keys['title'];
    $this->domain = $keys['domain'];
    $this->context = unserialize($keys['context']);
    $this->rewind();
  }
  
  public function __isset($property){
    if(in_array($property, array('id', 'old_id', 'title', 'domain', 'context'))) return true;
    return false;
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
    if($property=='id') return $this->id;
    if($property=='old_id') return $this->old_id;
    if($property=='title') return $this->title;
    if($property=='domain') return $this->domain;
    if($property=='context') return $this->context;
  }
  
  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){ 
    if($property=='id') {
      if($this->id == $this->title) {
        $this->title = $value;
      }
      $this->id = $value;
      return true;
    } 
    if($property=='title') {
      $this->title = $value;
      return true;
    } 
    if($property=='domain') {
      $this->domain = $value;
      return true;
    } 
    return false;
  }

  public function copyGroup($group) {
    $oldcontext = \dialplan\Dialplan::find('staff-'.$group);
    $result = false;
    if($oldcontext) {
      if(!$this->title) $this->title = $oldcontext->title;
      if($semicolon = strpos($this->title, ':')!==false) {
        $this->domain = substr($this->title, $semicolon+1);
        $this->title = substr($this->title, 0, $semicolon);
      }
      if($this->title == $group) $this->title = $this->id;
      $oldkeys = $oldcontext->keys();
      foreach($oldkeys as $exten) {
        // $this->
      }
      $modules = findModulesByClass('core\StaffContactPropertyModule', true);
      if($modules&&count($modules)) {
        foreach($modules as $module) {
          $classname = $module->class;
          foreach($oldkeys as $exten) {
            $contactprops = new $classname($exten.'@staff-'.$group);
            $oldprop = $contactprops->getProperties();
            unset($contactprops);
            $contactprops = new $classname($exten.'@staff-'.$this->id);
            $contactprops->setProperties($oldprop);
          }
        }
      }
    }    
    return $result;
  }

  /**
   * Сохраняет субьект в коллекции
   *
   * @return bool Возвращает истину в случае успешного сохранения субъекта
   */
  public function save() {
    $result = new \stdClass();
    if(empty($this->id)) return false;
    $this->context->id = 'staff-'.$this->id;
    if(empty($this->old_id)) {
      \staff\Groups::add($this); 
      $result = $this->context->save();
    } else {
      if($this->old_id!=$this->id) {
        $result = \staff\Groups::rename($this);
        if(!$this->title) $this->title = $this->context->title;
        self::deltreeDB('staff/'.$this->old_id);
        $result = $this->context->save();
      } else {
        \staff\Groups::change($this);
        $result = $this->context->save();
      }
    }            
    $this->old_id = $this->id;
    return $result;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    \staff\Groups::remove($this);
    self::deltreeDB('staff/'.$this->id);
    return $this->context->delete();
  }

  public function reload(){
    $this->context->reload();
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast(){
    $keys = array();
    $keys['id'] = $this->__get('id');
    $keys['old_id'] = $this->__get('old_id');
    $keys['title'] = $this->__get('title');
    $keys['contacts'] = array();
    foreach($this->keys() as $contactid) {
      $contact = new Contact($contactid, true);
      $keys['contacts'][] = $contact->cast();
    }
    return (object)$keys;
  }

  public function assign($request_data){
    foreach($request_data as $key => $value) {
      if(($key == 'id')||($key == 'title')) $this->__set($key,$value);
    }
    return true;
  }

  /**
   * Создает новый элемент коллекции
   *
   * @param \staff\Contact $subject Субъект который необходимо добавить в коллекцию. Контроль типов обязателен.
   * @return bool Возвращает истину если удалось добавить субьект в коллекцию.
   */
  public static function add(\module\ISubject &$subject) {
    self::notify(self::ADD, $subject);
  }

  /**
   * Переименовывает субьект коллекции
   *
   * @param \staff\Contact $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function rename(\module\ISubject &$subject) {

    self::notify(self::RENAME, $subject);
  }

  /**
   * Изменяет субьект коллекции
   *
   * @param \staff\Contact $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function change(\module\ISubject &$subject) {
    self::notify(self::CHANGE, $subject);
  }

  /**
   * Удаляет субьект из коллекции
   *
   * @param \staff\Contact $subject Субьект который необходимо удалить из коллекции.
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
    $this->contactList = $this->cache->get('contactgroup_peers_'.$this->old_id);
    if($this->contactList == false) {
      $this->contactList = array();
      foreach($this->context->keys() as $exten) {  
        if(is_numeric($exten)) $this->contactList[] = $exten;
      }
      $this->cache->set('contactgroup_peers_'.$this->old_id, $this->contactList, 30);
    }
    reset($this->contactList);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current(bool $minimal = true) {
    $contact= current($this->contactList).'@'.$this->old_id;
    if($this->interTestLock($contact)) return null;
    return new Contact($contact, $minimal);
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
      $keys[] = $key.'@'.$this->old_id;
      $this->next();
    }
    return $keys;
  }

  /**
   * Осуществляет поиск субьекта с указанным идентификатором
   *
   * @param string $id Идентификатор субьекта
   * @return \staff\Contact
   */
  public static function find(string $id) {
    $class = get_called_class();
    list($contact, $group) = explode('@', $id, 2);
    $iterator = new $class($group);
    $result = null;
    $iterator->rewind();
    while($key = $iterator->key()) {
      if($key == $contact) {
        $result = $iterator->current(false);
        break;
      }
      $iterator->next();
    }
    return $result;
  }

}
?>