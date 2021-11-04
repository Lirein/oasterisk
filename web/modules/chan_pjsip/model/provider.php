<?php

namespace pjsip;

class SIPProvider extends \channel\Provider {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \sip\SIPProviders $collection
   */
  static $collection = 'sip\\SIPProviders';

  private $ini;

  private $name = null;

  private static $defaultparams = '{
    "type": "",
    "transport": ",",
    "host": "",
    "qualify": "!no",
    "encryption": "!no",
    "authuser": "",
    "remotesecret": "",
    "username": "",
    "fromuser": "",
    "fromdomain": "",
    "callerid": "",
    "context": "",
    "dtmfmode": "",
    "insecure": "no",
    "disallow": [],
    "allow": [],
    "nat": "no",
    "callbackextension": ""
  }';

  /**
   * Набор хранимых данные субъекта
   *
   * @var \stdClass $data
   */
  private $data;

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function __construct(string $id = null) {
    parent::__construct($id);
    $this->ini = self::getINI('/etc/asterisk/sip.conf');
    $defaultparams = json_decode(self::$defaultparams, true);
    if(isset($this->ini->$id)) {
      $v = $this->ini->$id;
      if(isset($v->type)&&isset($v->fromuser)&&($v->type=='peer')) {
        $this->ini->$id->normalize(self::$defaultparams);
        foreach($defaultparams as $param => $value) {
          $this->data->$param = $this->ini->$id->$param->getValue();
        }    
        $this->data->templates = $v->getTemplateNames();
        $this->data->istemplate = $v->isTemplate();
        if($this->data->insecure=='very') $this->data->insecure='port,invite';
        $this->old_id = $id;
        $this->name = $this->ini->$id->getComment();
      }
    } else {
      $this->data = $defaultparams;
      $this->data->callbackextension = 's';
    }
    if(isset($this->data->type)) unset($this->data->type);
    if(!$this->name) $this->name = $id;
    $this->id = $id;
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    unset($this->data);
    unset($this->ini);
  }

  public function __serialize() {
    
  }

  public function __unserialize(array $keys) {
    
  }

  public function __isset($property) {
    if(in_array($property, array('id', 'old_id', 'name'))) return true;
    if(isset($this->data->$property)) return true;
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
    if($property=='name') return $this->name;
    if($property=='phone') {
      if($this->data->callbackextension=='s') return '';
      return $this->data->callbackextension;
    }
    if($property=='secret') return $this->data->remotesecret;
    if($property=='remotesecret') return null;
    if($property=='callbackextension') return null;
    if(isset($this->data->$property)) return $this->data->$property;
    return null;
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    if($property=='id') {
      if($this->id == $this->name) {
        $this->name = $value;
      }
      $this->id = $value;
      return true;
    } 
    if($property=='name') {
      $this->name = $value;
      return true;
    } 
    if($property=='phone') {
      if($value=='') $value = 's';
      $this->data->callbackextension = $value;
      return true;
    }
    if($property=='secret') {
      $this->data->remotesecret = $value;
      return true;
    } 
    if(isset($this->data->$property)) {
      $this->data->$property = $value;
      return true;
    }
    return false;
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save() {
    $peer = $this->id;
    if(!$peer) return false;
    if($this->old_id!==null) {
      if($this->id!=$this->old_id) {
        self::$collection::rename($this);
        $oldname = $this->old_id;
        $this->ini->$peer = $this->ini->$oldname; //Перемещаем секцию под новым именем
        $this->ini->$peer->setName($peer);
        unset($this->ini->$oldname);
      } else {
        self::$collection::change($this);
      }
    } else { //Инициализируем секцию
      self::$collection::add($this);
      $this->ini->$peer->normalize(self::$defaultparams);
    }
    $this->ini->$peer->type = 'peer';
    foreach($this->data as $property => $value) {
      $this->ini->$peer->$property = $value;
    }
    if($this->name == $this->id) {
      $this->ini->$peer->setComment('');
    } else {
      $this->ini->$peer->setComment($this->name);
    }
    $this->old_id = $this->id;
    return $this->ini->save();
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    $peer = $this->old_id;
    if(isset($this->ini->$peer)) {
      unset($this->ini->$peer);
      $this->ini->save();
      parent::delete();
    }
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    return $this->ami->send_request('Command', array('Command' => 'sip reload'))!==false;
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
    $keys['name'] = $this->name;
    $keys['phone'] = $this->__get('phone');
    $keys['secret'] = $this->__get('secret');
    foreach($this->data as $key => $value) {
      $keys[$key] = $value;
    }
    unset($keys['remotesecret']);
    unset($keys['callbackextension']);
    return (object)$keys;
  }
    
  /**
   * Устанавливает все свойства новыми значениями
   *
   * @param \stdClass $request_data Объект со свойствами - ключ→значение 
   */
  public function assign($request_data){
    foreach($request_data as $key => $value) {
      if($key == 'id') $this->id = $value; 
      elseif($key == 'name') $this->name = $value;
      elseif($key == 'secret') $this->data->remotesecret = $value;
      elseif($key == 'phone') $this->__set('phone', $value); else
      if(isset($this->data->$key)) $this->data->$key = $value;
    }
    return true;
  }
  
  public function getDial(string $number) {
    return 'SIP/'.$this->id.'/'.$number;
  }

  public function checkDial(string $dial, string &$number) {
    $dials = explode('&', $dial);
    $result = false;
    foreach($dials as $dialentry) {
      if(strpos($dialentry, 'SIP/'.$this->id.'/'.$number)===0) {
        $result=true;
        break;
      }
    }
    if(!$result) {
      foreach($dials as $dialentry) {
        if(strpos($dialentry, 'SIP/'.$this->id.'/')===0) {
          $number = substr($dialentry, strlen('SIP/'.$this->id.'/'));
          $result=true;
          break;
        }
      }
    }
    return $result;
  }

  public function checkChannel(string $channel, string $phone) {
    $result = false;
    if(strpos($channel, 'SIP/'.$this->id)===0) $result=true;
    return $result;
  }

}

?>
