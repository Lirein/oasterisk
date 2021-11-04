<?php

namespace security;

class User extends \module\Subject {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \security\Users $collection
   */
  static $collection = 'security\\Users';

  public static $restinterface = 'core\UsersREST';

  private static $defaultsettings = '{
    "read": ",",
    "write": ",",
    "secret": "",
    "deny": [],
    "permit": [],
    "guipermit": [],
    "role": "",
    "displayconnects": "!yes",
    "allowmultiplelogin": "!yes",
    "acl": []
  }';

  private $ini;

  public function getViewMode() {
    $mode = self::getDB('users/'.$this->old_id, 'mode');
    switch($mode) {
      case 'advanced':
      case 'basic':
      case 'expert': return $mode;
      default: return 'basic';
    }
  }

  public function setViewMode($mode) {
    if(!$this->old_id) return false;
    switch($mode) {
      case 'advanced':
      case 'basic':
      case 'expert': break;
      default: $mode = 'basic';
    }
    return self::setDB('users/'.$this->old_id, 'mode', $mode);
  }

  public function getUserProperty($property, $json) {
    $value = \config\DB::readData('users/'.$this->old_id.'/'.$property, $json);
    return $value;
  }

  public function setUserProperty($property, $json, $value) {
    if(!$this->old_id) return false;
    return \config\DB::writeData('users/'.$this->old_id.'/'.$property, $json, $value);
  }

  /**
   * Конструктор без аргументов - инициализирует модель
   */
  public function __construct(string $id = null) {
    $this->ini = self::getINI('/etc/asterisk/manager.conf');
    parent::__construct($id);
    if(!isset($this->ini->$id)) $this->data->changed = true;
    $this->ini->$id->normalize(self::$defaultsettings);
    $this->id = $id;
    $comment = $this->ini->$id->getComment();
    $this->data->name = empty($comment)?$id:$comment;
    $this->data->secret = (string) $this->ini->$id->secret; 
    $this->data->permit = $this->ini->$id->guipermit->getValue();
    $this->data->displayconnects = $this->ini->$id->displayconnects->getValue();
    $this->data->multiplelogin = $this->ini->$id->allowmultiplelogin->getValue();
    $this->data->acl = $this->ini->$id->acl->getValue();
    if(isset($this->ini->$id->role)) {
      $this->data->role = (string) $this->ini->$id->role;
      $this->data->group = new \security\Group($this->data->role);
    }
    if(($this->data->group === null)||($this->data->group->id === null)) $this->data->group = \security\Groups::find($this->ini->$id->read, $this->ini->$id->write);
    if($this->data->group->old_id === null) $this->data->group = null;
    $this->old_id = $this->id;
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    if($property=='role') {
      if(is_string($value)) {
        $this->data->group = new \security\Group($value);
      } else {
        $this->data->group = $value;
      }
      if($this->data->group->old_id === null) $this->data->group = null;
      $this->changed = true;
      return true;
    } 
    if(($property=='permit')&&is_array($value)) {
      $this->data->permit = $value;
      $this->data->changed = true;
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
    $this->lock('user');
    if (!$this->id) $this->id = (new self::$collection())->newID();
    $id = $this->id;
    if($this->old_id!==null) {
      if($this->id!=$this->old_id) {
        \security\Users::rename($this);
        $oldid = $this->old_id;
        unset($this->ini->$oldid);
        $this->ini->$id->normalize(self::$defaultsettings);
      } else {
        \security\Users::change($this);
      }
    } else { //Инициализируем секцию
      \security\Users::add($this);
    }
    if ((!$this->data->name) ||($this->id == $this->data->name)) {
      $this->ini->$id->setComment('');
    } else {
      $this->ini->$id->setComment($this->data->name);
    }
    $this->ini->$id->secret = $this->data->secret;
    if($this->group) {
      $privs = \security\Groups::expandPrivs($this->data->group->privileges);
      $this->ini->$id->read = implode(',',$privs->read);
      $this->ini->$id->write = implode(',',$privs->write);
    }
    $this->ini->$id->role = $this->data->group->id;
    $this->ini->$id->deny = array('[::]/0');
    $this->ini->$id->permit = array('[::1]');
    if(count($this->permit) == 0) {
      $this->ini->$id->guipermit = array();  
    } else {
      $this->ini->$id->guipermit = $this->data->permit;
    }
    $this->ini->$id->displayconnects = $this->data->displayconnects;
    $this->ini->$id->allowmultiplelogin = $this->data->multiplelogin;
    $this->ini->$id->acl = $this->data->acl;
    $this->old_id = $this->id;
    $result = $this->ini->save();
    $this->unlock('user');
    return $result;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    \security\Users::remove($this);
    $id = $this->old_id;
    if(isset($this->ini->$id)) {
      unset($this->ini->$id);
      $this->ini->save();
    }
    self::deltreeDB('users/'.$this->old_id);
    return true;
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload() {
    return $this->ami->send_request('Command', array('Command' => 'manager reload'))!==false;
  }

  public function checkPrivilege(string $priv) {
    if(!$this->group) return false;
    return $this->group->checkPrivilege($priv);
  }

  public function checkEffectivePrivilege(string $rest, string $object, string $priv) {
    if(!$this->group) return false;
    return $this->group->checkEffectivePrivilege($rest, $object, $priv);
  }

  public function isReadonly() {
    if(!$this->group) return false;
    return $this->group->isReadonly();
  }

  public function inScope(string $location) {
    if($this->group) return true;
    return $this->group->inScope($location);
  }

  public function checkIP($ip) {
    if(count($this->permit) == 0) return true;
    foreach($this->permit as $ip_allow) {
      // If IP has / means CIDR notation
      if(strpos($ip_allow, '/') === false) {
          // Check Single IP
          if(inet_pton($ip) == inet_pton($ip_allow)) {
              return true;
          }
      }
      else {
          // Check IP range
          list($subnet, $bits) = explode('/', $ip_allow);
  
          // Convert subnet to binary string of $bits length
          $subnet = unpack('H*', inet_pton($subnet)); // Subnet in Hex
          foreach($subnet as $i => $h) $subnet[$i] = base_convert($h, 16, 2); // Array of Binary
          $subnet = substr(implode('', $subnet), 0, $bits); // Subnet in Binary, only network bits
  
          // Convert remote IP to binary string of $bits length
          $ip = unpack('H*', inet_pton($ip)); // IP in Hex
          foreach($ip as $i => $h) $ip[$i] = base_convert($h, 16, 2); // Array of Binary
          $ip = substr(implode('', $ip), 0, $bits); // IP in Binary, only network bits
  
          // Check network bits match
          if($subnet == $ip) {
              return true;
          }
      }
    }
    return false;
  }

}
?>