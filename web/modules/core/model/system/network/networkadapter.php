<?php

namespace core;

/**
 * Интерфейс реализующий субьект коллекции
 * Должен содержать набор приватных свойств и геттеры/сеттеры для их обработки
 * Метод save - сохраняет субьект
 * Метод delete вызывает метод delete класса коллекции
 */
class NetworkAdapter extends \module\MorphingSubject {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \core\NetworkAdapters $collection
   */
  static $collection = 'core\\NetworkAdapters';

  public $name;
  
  public $ipv4;

  public $ipv6;


  public function __construct(string $id = null) {
    $this->id = $id;
    $this->ipv4 = array();
    $this->ipv6 = array();
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    
  }

  public function __serialize() {
    $keys = array();
    $keys['id'] = $this->id;
    return $keys;
  }

  public function __unserialize(array $keys) {
    
  }

  public function __isset($property){
    if(in_array($property, array('id', 'title', 'name', 'link', 'type', 'speed', 'mac', 'mtu'))) return true;
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
    if(($property=='title') || ($property=='name')) return basename($this->id);
    if ($property=='link') return exec ('cat /sys/class/net/'.$this->id.'/carrier');
    if ($property=='type') {
      // $type = exec ('cat /sys/class/net/'.$this->id.'/type');
      // if ($type == '1') return 'ethernet';
      // if ($type == '801') return 'wireless';
      $type = exec ('cat /sys/class/net/'.$this->id.'/device/class');
      if ($type == '0x020000') return 'ethernet';
      if ($type == '0x028000') return 'wireless';
      return $type;
    }
    if ($property=='speed') return exec ('cat /sys/class/net/'.$this->id.'/speed');
    if ($property=='mac') return exec ('cat /sys/class/net/'.$this->id.'/address');
    if ($property=='mtu') return exec ('cat /sys/class/net/'.$this->id.'/mtu');
    if ($property=='addresses') return $this->ipv4;
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    return false;
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save() {
        
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    return false;
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    return false;
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast() {
    $keys = array();
    $keys['id'] = $this->__get('id');
    $keys['title'] = $this->__get('title');
    $keys['link'] = $this->__get('link');
    $keys['type'] = $this->__get('type');
    $keys['mac'] = $this->__get('mac');
    $keys['speed'] = $this->__get('speed');
    $keys['mtu'] = $this->__get('mtu');
    $keys['addresses'] = $this->ipv4;
    return (object)$keys;
  }
    
  /**
   * Устанавливает все свойства новыми значениями
   *
   * @param stdClass $request_data Объект со свойствами - ключ→значение 
   */
  public function assign($request_data){   
    return false;
  }

}
?>