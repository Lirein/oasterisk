<?php

namespace core;

class FaxModel extends \Module implements \module\IModel {
  
  /**
   * Модель настроек факса
   *
   * @var \core\FaxSettingsModel $fax
   */
  private $fax;

  /**
   * Модель насроек udptl
   *
   * @var \core\FaxUdptlModel $udptl
   */
  private $udptl;

  /**
   * Конструктор без аргументов - инициализирует модель
   */
  public function __construct(){
     $this->fax = null;
     $this->udptl = null;   
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
    switch($property) {
      case "fax": {
        if(!$this->fax) $this->fax = new FaxSettingsModel();
        return $this->fax;
      } break; 
      case "udptl": {
        if(!$this->udptl) $this->udptl = new FaxUdptlModel();
        return $this->udptl;
      } break; 
      default: return null; 
    }
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    switch($property) {
      default: return null; 
    }
  }

  /**
   * Сохраняет настройки факса
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save(){
    $result = true;
    if($this->fax instanceof FaxSettingsModel) $result &= $this->fax->save();
    if($this->udptl instanceof FaxUdptlModel) $result &= $this->udptl->save();
    return $result;
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    $result = true;
    if($this->fax instanceof FaxSettingsModel) $result &= $this->fax->reload();
    if($this->udptl instanceof FaxUdptlModel) $result &= $this->udptl->reload();
    return $result;
  }

  /**
    * Возвращает все свойства в виде объекта со свойствами
    *
    * @return \stdClass
    */
  public function cast() {
    $keys = array();
    $keys['fax'] = ($this->__get('fax'))->cast();
    $keys['udptl'] = ($this->__get('udptl'))->cast();
    return (object) $keys;
  }

  /**
    * Устанавливает все свойства новыми значениями
    *
    * @param stdClass $assign_data Объект со свойствами - ключ→значение 
    */
  public function assign($assign_data){
    ($this->__get('fax'))->assign($assign_data->fax);
    ($this->__get('udptl'))->assign($assign_data->udptl);
  }
}
?>