<?php

namespace module;

/**
 * Интерфейс реализующий субьект коллекции
 * Должен содержать набор приватных свойств и геттеры/сеттеры для их обработки
 * Метод save - сохраняет субьект
 * Метод delete вызывает метод delete класса коллекции
 */
abstract class Subject extends \Module implements ISubject {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \Collection $collection
   */
  static $collection = '\module\Collection';
  
  /**
   * Идентификатор субьекта - всегда должен быть задан
   *
   * @var string $id
   */
  protected $id;

  /**
   * Прежний идентификатор субъекта коллекции - задается равный null если это новый субьект, иначе принимает значение ID существующего субьекта
   *
   * @var string $old_id
   */
  protected $old_id;

  /**
   * Набор хранимых данных субъекта
   *
   * @var \stdClass $data
   */
  protected $data;

  /**
   * Признак изменения данных
   *
   * @var bool $changed
   */
  protected $changed;

  /**
   * Конструктор с идентификатором - инициализирует субьект коллекции
   * 
   * @param string $id Идентификатор элемента коллекции. Если идентификатор не задан, генерирует новый идентификатор, прежний идентификатор равен null. Если идентификатор задан - ищет субьект с указанным идентификатором или возвращает исключение в случае его отсутствия.
   */
  public function __construct(string $id = null) {
    parent::__construct();
    //if(!$id) return;
    $this->changed = false;
    $this->id = $id;
    $this->old_id = null;
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
    $keys['old_id'] = $this->old_id;
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
    $this->id = $keys['id'];
    $this->old_id = $keys['old_id'];
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

  public function __isset($property){
    if(in_array($property, array('id', 'old_id', 'changed'))) return true;
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
    if($property=='old_id') return $this->old_id;
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
    if(property_exists($this->data, $property)) {
      $this->data->$property = $value;
      $this->changed = true;
      return true;
    }
    return false;
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
      if($value instanceof \module\Subject) {
        $keys[$key] = $value->old_id;  
      } else if(is_array($value)) {
        $entries = array();
        foreach($value as $ventry) {
          if($ventry instanceof \module\Subject) {
            $entries[] = $ventry->old_id;
          } else {
            if($ventry instanceof \module\Model) {
              $entries[] = $ventry->cast();
            } else {
              $entries[] = $ventry;
            }
          }
        }
        $keys[$key] = $entries;
      } else {
        if($value instanceof \module\Model) {
          $keys[$key] = $value->cast();
        } else {
          $keys[$key] = $value;
        }
      }
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

}
?>