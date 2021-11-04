<?php

namespace pjsip;

class AOR extends \module\Subject {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \pjsip\AORs $collection
   */
  public static $collection = 'pjsip\\AORs';

  private $ini;

  private $title = null;

  private static $defaultparams = '{
    "contact": [],
    "default_expiration": 3600,
    "minimum_expiration": 60,
    "maximum_expiration": 7200,
    "mailboxes": ",",
    "voicemail_extension": "",     
    "secret": "",
    "max_contacts": 0,
    "remove_existing": "!no",
    "qualify_frequency": 0,
    "qualify_timeout": 3.0,
    "authenticate_qualify": "!no",
    "outbound_proxy": "",
    "support_path": "!no"
  }';

  public function __construct(string $id = null) {
    $this->ini = self::getINI('/etc/asterisk/pjsip.conf');
    parent::__construct($id);
    $defaultparams = json_decode(self::$defaultparams);
    if(isset($this->ini->$id)) {
      $v = $this->ini->$id;
      if(isset($v->type)&&($v->type=='aor')) {
        $this->ini->$id->normalize(self::$defaultparams);
        foreach($defaultparams as $param => $value) {
          $this->data->$param = $this->ini->$id->$param->getValue();
        }    
        $this->interLock($id);
        $contacts = array();
        foreach($this->data->contact as $contact) {
          $entry = Contacts::find($contact);
          if($entry !== null) $contacts[] = $entry;
        }
        $this->data->contact = $contacts;
        $this->interUnlock($id);
        $this->data->templates = $v->getTemplateNames();
        $this->data->istemplate = $v->isTemplate();
        $this->old_id = $id;
        $this->title = $this->ini->$id->getComment();
      }
    } 
    if(!$this->title) $this->title = $id;
    $this->id = $id;
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    parent::__destruct();
    unset($this->ini);
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
    if($property=='title') return $this->title;
    return parent::__get($property);
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
        $this->title = $value;
      }
      $this->id = $value;
      return true;
    } 
    if($property=='title') {
      $this->title = $value;
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
    $entry = $this->id;
    if(!$entry) return false;
    if($this->old_id!==null) {
      if($this->id!=$this->old_id) {
        self::$collection::rename($this);
        $oldname = $this->old_id;
        $this->ini->$entry = $this->ini->$oldname; //Перемещаем секцию под новым именем
        $this->ini->$entry->setName($entry);
        unset($this->ini->$oldname);
      } else {
        self::$collection::change($this);
      }
    } else { //Инициализируем секцию
      self::$collection::add($this);
      $this->ini->$entry->normalize(self::$defaultparams);
    }
    $this->ini->$entry->type='aor';
    foreach($this->data as $property => $value) {
      if($value instanceof \module\Subject) {
        $this->ini->$entry->$property = $value->old_id;  
      } else if(is_array($value)) {
        $entries = array();
        foreach($value as $ventry) {
          if($ventry instanceof \module\Subject) {
            $entries[] = $ventry->old_id;
          } else {
            $entries[] = $ventry;
          }
        }
        $this->ini->$entry->$property = $entries;
      } else {
        $this->ini->$entry->$property = $value;
      }
    }
    if($this->title == $this->id) {
      $this->ini->$entry->setComment('');
    } else {
      $this->ini->$entry->setComment($this->title);
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
    $entry = $this->old_id;
    if(isset($this->ini->$entry)) {
      unset($this->ini->$entry);
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
    return $this->ami->send_request('Command', array('Command' => 'pjsip reload res_pjsip.so'))!==false;
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast() {
    $keys = parent::cast();
    $keys->title = $this->title;
    return $keys;
  }
    
  /**
   * Устанавливает все свойства новыми значениями
   *
   * @param stdClass $request_data Объект со свойствами - ключ→значение 
   */
  public function assign($request_data){
    parent::assign($request_data);
    foreach($request_data as $key => $value) {
      if($key == 'title') $this->title = $value;
    }
    return true;
  }

}

?>
