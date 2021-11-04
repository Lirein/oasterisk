<?php

namespace sip;

class SIPPeer extends \channel\Peer {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \core\SIPPeers $collection
   */
  static $collection = 'core\\SIPPeers';

  private $ini;

  private $name = null;

  private static $defaultparams = '{
    "transport": ",",
    "type": "",
    "host": "",
    "qualify": "!no",
    "encryption": "!no",     
    "secret": "",
    "fromdomain": "",
    "callerid": "",
    "context": "",
    "dtmfmode": "",
    "insecure": "no",
    "disallow": [],
    "allow": [],
    "nat": "no"
  }';

  /**
   * Набор хранимых данные субъекта
   *
   * @var \stdClass $data
   */
  private $data;

  public function __construct(string $id = null) {
    parent::__construct($id);
    $this->ini = self::getINI('/etc/asterisk/sip.conf');
    $this->data = new \stdClass();
    $defaultparams = json_decode(self::$defaultparams);
    if(isset($this->ini->$id)) {
      $v = $this->ini->$id;
      if(isset($v->type)&&!isset($v->remotesecret)&&(($v->type=='friend')||($v->type=='user'))) {
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
    } 
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
    if(isset($this->data->$property)) return $this->data->$property;
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
    foreach($this->data as $property => $value) {
      $this->ini->$peer->$property = $value;
    }
    if($this->title == $this->id) {
      $this->ini->$peer->setComment('');
    } else {
      $this->ini->$peer->setComment($this->title);
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
    foreach($this->data as $key => $value) {
      $keys[$key] = $value;
    }
    return (object)$keys;
  }
    
  /**
   * Устанавливает все свойства новыми значениями
   *
   * @param stdClass $request_data Объект со свойствами - ключ→значение 
   */
  public function assign($request_data){
    foreach($request_data as $key => $value) {
      if($key == 'id') $this->id = $value; 
      elseif($key == 'name') $this->name = $value; else
      if(isset($this->data->$key)) $this->data->$key = $value;
    }
    return true;
  }

  public function getDial() {
    return 'SIP/'.$this->id;
  }

  public function checkDial(string $dial) {
    $dials = explode('&', $dial);
    $result = false;
    foreach($dials as $dialentry) {
      if(strpos($dialentry, 'SIP/'.$this->id)===0) {
        $result=true;
        break;
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
