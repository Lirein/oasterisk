<?php

namespace config;

abstract class NetPlanAdapter{

  /**
   * Идентификатор - всегда должен быть задан
   *
   * @var string $id
   */
  protected $id;

  /**
   * Набор хранимых данных субъекта
   *
   * @var \stdClass $data
   */
  protected $data;

  /**
   * Конструктор с идентификатором - инициализирует субьект коллекции
   * 
   * @param string $id Идентификатор элемента коллекции. Если идентификатор не задан, генерирует новый идентификатор, прежний идентификатор равен null. Если идентификатор задан - ищет субьект с указанным идентификатором или возвращает исключение в случае его отсутствия.
   */
  public function __construct(string $id = null) {
    $this->id = $id;
    $this->data = new \stdClass();
  }

  /**
   * Сериализация объекта. В дочернем классе вначала вызывается родительский метод, потом сериализуются дополнительные атрибуты
   *
   * @return string[]
   */
  public function __serialize() {
    $keys = array();
    $keys['id'] = $this->id;
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
    $this->id = $keys['id'];
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

  public function __isset($property){
    if($property == 'id') return true;
    if(property_exists($this->data, $property)) return true;
    return null;
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
    if($property=='id') return $this->id;
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
    }
    $this->data->$property = $value;
    return true;
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast() {
    $keys = array();
    $keys['id'] = $this->id;
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

  public function toArray() {
    $struct = array();
    foreach($this->data as $key => $value) {
      $struct[$key] = $value;
    }
    return $struct;
  }

}
?>
