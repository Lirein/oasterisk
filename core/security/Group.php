<?php

namespace security;

class Group extends \module\Subject implements \module\ICollection {

  /**
   * Открытый INI файл с 
   *
   * @var \config\INI $ini
   */
  private $ini = null;

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \security\Groups $collection
   */
  static $collection = 'security\\Groups';

  private static $defaultparams = '{
    "name": "",
    "scope": [""],
    "objects": [{"id": "", "rest": "", "mode": false}],
    "privs": [""]
  }';

  public static $restinterface = 'core\GroupsREST';
  /**
   * Набор полей задания
   *
   * @var object $data
   */
 
  private $users = null;

  /**
  * Функция должна возвращать тип cубъектов коллекции
  *
  * @return string
  */
  static function getTypeName() {
    return 'Contact';
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
   * Конструктор с идентификатором - инициализирует модель
   */
  public function __construct(string $id = null) {
    $this->ini = self::getINI('/etc/asterisk/manager.conf');
    parent::__construct($id);
    if($id) {
      if(isset(Groups::$internal_roles[$id])) {
        $this->data = new \stdClass();
        switch($id) {
          case "full_control": $this->data->name = "Полный доступ"; break;
          case "admin": $this->data->name = "Администратор"; break;
          case "technician": $this->data->name = "Техник/Связист"; break;
          case "operator": $this->data->name = "Оператор"; break;
          case "manager": $this->data->name = "Руководитель"; break;
        }
        $this->data->scope = array();
        $this->data->objects = array();
        $this->data->privs = Groups::$internal_roles[$id];
      } else {
        $this->data = \config\DB::readDataItem('customgroup', 'id', $id, self::$defaultparams);
      }
    } 
    if(!empty((array)$this->data)) {
      $this->old_id = $id;
    } else {
      $this->data = json_decode(self::$defaultparams);
      $this->data->scope = array();
      $this->data->objects = array();
      $this->data->privs = array();
      $this->changed = true;
    }
    if(!$this->data->name) $this->data->name = $id;
    $this->id = $id;
    $this->rewind();
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    unset($this->data);
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
    if($property=='privileges') return $this->data->privs;
    if($property=='objects') {
      $objects = array();
      foreach($this->data->objects as $object) {
        $objects[$object->rest.'/'.$object->id] = $object->mode;
      }
      return $objects;
    }
    return parent::__get($property);
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    if(($property=='scope')&&is_array($value)) {
      $this->data->scope = $value;
      $this->changed = true;
      return true;
    } 
    if(($property=='privileges')&&is_array($value)) {
      $this->data->privs = $value;
      $this->changed = true;
      return true;
    } 
    if(($property=='objects')&&is_array($value)) {
      $objects = array();
      foreach($value as $object => $mode) {
        $objects[] = (object) array('rest' => dirname(($object)), 'id' => basename($object), 'mode' => $mode);
      }
      $this->data->objects = $objects;
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
    if(!$this->changed) return true;
    if(isset(Groups::$internal_roles[$this->old_id]) || isset(Groups::$internal_roles[$this->id])) return false;
    
    $this->lock('group');
    if (!$this->id) $this->id = (new self::$collection())->newID();
    $id = $this->id;
    if($this->old_id!==null) {
      if($this->id!=$this->old_id) {
        Groups::rename($this);
        $oldid = $this->old_id;
        $olddata = \config\DB::readDataItem('customgroup', 'id', $oldid, self::$defaultparams);
        \config\DB::deleteDataItem('customgroup', 'id', $oldid, self::$defaultparams);
      } else {
        Groups::change($this);
      }
    } else { //Создаем расписание
      Groups::add($this);
    }
    $olddata = clone $this->data;
    \config\DB::writeDataItem('customgroup', 'id', $id, self::$defaultparams, $olddata);
    $this->old_id = $this->id;
    $this->unlock('group');
    return true;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    $subjectid = $this->old_id;
    $result = \config\DB::deleteDataItem('customgroup', 'id', $subjectid, self::$defaultparams);
    Groups::remove($this);
    return $result;
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload() {
    return $this->ami->send_request('Command', array('Command' => 'manager reload'))!==false;
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast() {
    $keys = array();
    $keys['id'] = $this->id;
    $keys['old_id'] = $this->old_id;
    foreach($this->data as $key => $value) {
      if($key=='privs') $key = 'privileges';
      $keys[$key] = $this->__get($key);
    }
    return (object)$keys;
  }

  /**
   * Создает новый элемент коллекции
   *
   * @param User $subject Субъект который необходимо добавить в коллекцию. Контроль типов обязателен.
   * @return bool Возвращает истину если удалось добавить субьект в коллекцию.
   */
  public static function add(\module\ISubject &$subject) {
    self::notify(self::ADD, $subject);
    return true;
  }

  /**
   * Переименовывает элемент коллекции
   *
   * @param User $subject Субъект который необходимо переименовать. Контроль типов обязателен.
   * @return bool Возвращает истину если удалось добавить переименовать субьект.
   */
  public static function rename(\module\ISubject &$subject) {
    self::notify(self::RENAME, $subject);
    return true;
  }

  /**
   * Изменяет элемент коллекции
   *
   * @param User $subject Субъект который необходимо изменить. Контроль типов обязателен.
   * @return bool Возвращает истину если удалось изменить субьект.
   */
  public static function change(\module\ISubject &$subject) {
    self::notify(self::CHANGE, $subject);
    return true;
  }

  /**
   * Удаляет субьект из коллекции
   *
   * @param User $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function remove(\module\ISubject &$subject) {
    self::notify(self::REMOVE, $subject);
    return true;
  }

  public function count() {
    return count($this->users);
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->users = array();
    foreach($this->ini as $key => $value) {
      if(isset($value->secret)&&isset($value->role)&&($value->role == $this->id)) $this->users[] = $key;
    }
    reset($this->users);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $user = current($this->users);
    return new User($user);
  }

  /**
   * Возвращает ключ текущего элемента массива полей
   *
   * @return int|string|null Возвращает ключ текущего элемента или же NULL при неудаче. 
   */
  public function key() {
    return current($this->users);
  }

  /**
   * Передвигает текущую позицию к следующему элементу массива полей
   *
   * @return void
   */
  public function next() {
    next($this->users);
  }

  /**
   * Проверяет корректность текущей позиции массива полей
   *
   * @return bool Возвращает TRUE в случае успешного завершения или FALSE в случае возникновения ошибки
   */
  public function valid() {
    return (key($this->users) !== null);
  }

  public function checkPrivilege(string $priv) {
    return in_array($priv, $this->data->privs);
  }

  public function checkEffectivePrivilege(string $rest, string $object, string $priv) {
    $result = false;
    if(!empty($this->data->objects)) {
      foreach($this->data->objects as $objectspec) {
        if(($objectspec->rest == $rest) && ($objectspec->object == $object)) {
          if(($priv == 'settings_writer')||($priv == 'security_writer')||($priv == 'dialplan_writer')) {
            $result = $objectspec->mode;
          } else $result = true;
          break;
        }
      }
    } else {
      $result = in_array($priv, $this->data->privs);
    }
    return $result;
  }

  public function isReadonly() {
    if(in_array('settings_writer', $this->data->privs) || in_array('dialplan_writer', $this->data->privs) || in_array('security_writer', $this->data->privs)) return false;
    foreach($this->data->objects as $objectspec) {
      if($objectspec->mode) return false;
    }
    return true;
  }

  public function inScope(string $location) {
    if(empty($this->data->scope)) return true;
    $result = false;
    foreach($this->data->scope as $scope) {
      $result = strpos($scope, $location)===0;
      if($result!=false) break;
    }
    return $result;
  }

  public function addObject($rest, $object) {
    if(empty($this->data->objects)) return;
    foreach($this->data->objects as $objectspec) {
      if(($objectspec->rest == $rest) && ($objectspec->object == $object)) {
        return;
      }
    }
    $this->data->objects[] = (object) array('rest' => $rest, 'object' => $object, 'mode' => true);
    $this->changed = true;
  }

  public function removeObject($rest, $object) {
    if(empty($this->data->objects)) return;
    foreach($this->data->objects as $key => $objectspec) {
      if(($objectspec->rest == $rest) && ($objectspec->object == $object)) {
        unset($this->data->objects[$key]);
        $this->changed = true;
        break;
      }
    }
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
   * @return \security\User
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