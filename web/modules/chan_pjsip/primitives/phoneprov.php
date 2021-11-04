<?php

namespace pjsip;

class PhoneProvision extends \module\Subject {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \pjsip\PhoneProvisions $collection
   */
  public static $collection = 'pjsip\\PhoneProvisions';

  private $ini;

  private $title = null;

  private static $defaultparams = '{
    "endpoint": "",          
    "MAC": "",
    "PROFILE": ""
  }';

  public function __construct(string $id = null) {
    $this->ini = self::getINI('/etc/asterisk/pjsip.conf');
    parent::__construct($id);
    $defaultparams = json_decode(self::$defaultparams);
    if(isset($this->ini->$id)) {
      $v = $this->ini->$id;
      if(isset($v->type)&&($v->type=='phoneprov')) {
        $this->ini->$id->normalize(self::$defaultparams);
        foreach($defaultparams as $param => $value) {
          $this->data->$param = $this->ini->$id->$param->getValue();
        }
        foreach($this->ini->$id as $param => $value) {
          if(!isset($defaultparams->$param)) $this->data->$param = $value->getValue();
        }
        $this->interLock($id);
        $this->data->endpoint = Endpoints::find($this->data->endpoint);
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
        if(isset($this->ini->$entry)) unset($this->ini->$entry);
        $this->ini->$entry->type = 'phoneprov';
        $this->ini->$entry->normalize(self::$defaultparams);
        unset($this->ini->$oldname);
      } else {
        if(isset($this->ini->$entry)) unset($this->ini->$entry);
        $this->ini->$entry->type = 'phoneprov';
        $this->ini->$entry->normalize(self::$defaultparams);
        self::$collection::change($this);
      }
    } else { //Инициализируем секцию
      self::$collection::add($this);
      if(isset($this->ini->$entry)) unset($this->ini->$entry);
      $this->ini->$entry->type = 'phoneprov';
      $this->ini->$entry->normalize(self::$defaultparams);
    }
    $this->ini->$entry->type='phoneprov';
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
