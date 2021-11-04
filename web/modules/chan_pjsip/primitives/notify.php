<?php

namespace pjsip;

class Notify extends \module\Model {

  private $ini;

  private $changed;

  public function __construct() {
    $this->changed = false;
    $this->ini = self::getINI('/etc/asterisk/pjsip.conf');
    if(isset($this->ini->notify)) {
      foreach($this->ini->notify as $param => $value) {
        $this->data->$param = $value->getValue();
      }
    } 
  }

  /**
   * Сериализация объекта. В дочернем классе вначала вызывается родительский метод, потом сериализуются дополнительные атрибуты
   *
   * @return string[]
   */
  public function __serialize() {
    $keys = array();
    $keys['changed'] = $this->changed;
    foreach($this->data as $key => $value) {
      $keys[$key] = serialize($value);
    }
    return $keys;
  }

  /**
   * Десериализация объекта. В дочернем классе вначале инициализируется объект, потом вызывается родительский метод и потом при необходимости восстанавливаются ресурсы.
   *
   * @param array $keys сериализованные ключи
   * @return void
   */
  public function __unserialize(array $keys) {
    $this->changed = $keys['changed'];
    foreach(array_keys($keys) as $key) {
      if(isset($this->data->$key)) {
        $this->data->$key = unserialize($keys[$key]);
      }
    }
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    foreach($this->data as $key => $value) unset($this->data->$key);
    unset($this->data);
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
    if($property=='changed') return $this->changed;
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
      $this->id = $value;
      $this->changed = true;
      return true;
    }
    $this->data->$property = $value;
    $this->changed = true;
    return true;
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast() {
    $keys = array();
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
      $this->__set($key, $value);
    }
    return true;
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save() {
    if(isset($this->ini->notify)) unset($this->ini->notify);
    foreach($this->data as $property => $value) {
      $this->ini->notify->$property = $value;
    }
    return $this->ini->save();
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    return $this->ami->send_request('Command', array('Command' => 'pjsip reload res_pjsip.so'))!==false;
  }

}

?>
