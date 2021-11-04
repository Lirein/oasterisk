<?php

namespace pjsip;

class Peer extends \channel\Peer {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \pjsip\Peers $collection
   */
  static $collection = 'pjsip\\Peers';

  /**
   * Оконечное оборудование
   *
   * @var \pjsip\Endpoint $endpoint
   */
  private $endpoint;

  public function __construct(string $id = null) {
    parent::__construct($id);
    $this->endpoint = Endpoints::find($id);
    if(!$this->endpoint) {
      $this->endpoint = new Endpoint($id);
    }
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    unset($this->endpoint);
  }

  public function __serialize() {
    return $this->endpoint->__serialize();
  }

  public function __unserialize(array $keys) {
    $this->endpoint = new Endpoint();
  }

  public function __isset($property){
    return isset($this->endpoint->$property);
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
    if($property=='id') return $this->endpoint->id;
    if($property=='old_id') return $this->endpoint->old_id;
    return $this->endpoint->$property;
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    return $this->endpoint->__set($property, $value);
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save() {
    $peer = $this->endpoint->id;
    if(!$peer) return false;
    if($this->endpoint->old_id!==null) {
      if($this->endpoint->id!=$this->endpoint->old_id) {
        self::$collection::rename($this);
      } else {
        self::$collection::change($this);
      }
    } else { //Инициализируем секцию
      self::$collection::add($this);
    }
    $this->cache->delete('pjsip_peers');
    return $this->endpoint->save();
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->endpoint->old_id) return false;
    if($this->endpoint->delete()) {
      $this->cache->delete('pjsip_peers');
      return parent::delete();
    }
    return false;
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    return $this->endpoint->reload();
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast() {
    $keys = $this->endpoint->cast();
    $keys->type = 'pjsip';
    return (object)$keys;
  }
    
  /**
   * Устанавливает все свойства новыми значениями
   *
   * @param stdClass $request_data Объект со свойствами - ключ→значение 
   */
  public function assign($request_data){
    return $this->endpoint->assign($request_data);
  }

  public function getDial() {
    return '${PJSIP_DIAL_CONTACTS('.$this->id.')}';
  }

  public function checkDial(string $dial) {
    $dials = explode('&', $dial);
    $result = false;
    foreach($dials as $dialentry) {
      if(strpos($dialentry, '${PJSIP_DIAL_CONTACTS('.$this->endpoint->id.')}')===0) {
        $result=true;
        break;
      }
    }
    return $result;
  }

  public function checkChannel(string $channel, string $phone) {
    $result = false;
    if(strpos($channel, 'PJSIP/'.$this->id)===0) $result=true;
    return $result;
  }

}

?>
