<?php

namespace core;

class FaxUdptlModel extends \Module implements \module\IModel {

  private $ini;

  private static $defaultparams = '{
    "udptlstart": "4000",
    "udptlend": "4999",
    "udptlchecksums": "!no",
    "udptlfecentries": "3",
    "udptlfecspan": "3",
    "use_even_ports": "!no"
  }';

  /**
   * Конструктор без аргументов - инициализирует модель
   */
  public function __construct(){
    parent::__construct();
    $this->ini = self::getINI('/etc/asterisk/udptl.conf');   
    $this->ini->general->normalize(self::$defaultparams);
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    unset($this->ini);
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
    $defaultparams = json_decode(self::$defaultparams);
    if(isset($defaultparams->$property)) return $this->ini->general->$property->getValue();
    return null;
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    $defaultparams = json_decode(self::$defaultparams);
    if(isset($defaultparams->$property)) return $this->ini->general->$property = $value;
    return null; 
  }

  /**
   * Сохраняет настройки UPDTL
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save(){
    return ($this->ini->save());
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    return $this->ami->send_request('Command', array('Command' => 'module reload udptl'))!==false;
  }

  /**
    * Возвращает все свойства в виде объекта со свойствами
    *
    * @return \stdClass
    */
  public function cast() {
    $keys = array();
    foreach(json_decode(self::$defaultparams) as $key => $defvalue) {
      $keys[$key] = $this->__get($key);
    }
    return (object) $keys;
  }
  
  /**
    * Устанавливает все свойства новыми значениями
    *
    * @param stdClass $assign_data Объект со свойствами - ключ→значение 
    */
  public function assign($assign_data){
    foreach(json_decode(self::$defaultparams) as $key => $defvalue) {
      if(isset($assign_data->$key)) $this->__set($key, $assign_data->$key);
    }
  }
}
?>