<?php

namespace core;

class AsteriskCoreModel extends \Module implements \module\IModel {
  
  private $ini;

  private static $defaultparams = '{
    "verbose": "0",
    "debug": "0",
    "alwaysfork": "!no",
    "nofork": "!no",
    "quiet": "!no",
    "timestamp": "!no",
    "execincludes": "!no",
    "console": "!no",
    "highpriority": "!no",
    "initcrypto": "!yes",
    "nocolor": "!no",
    "dontwarn": "!no",
    "dumpcore": "!no",
    "languageprefix": "!no",
    "systemname": "",
    "autosystemname": "!no",
    "mindtmfduration": "80",
    "maxcalls": "",
    "maxload": "1",
    "maxfiles": "1024",
    "minmemfree": "0",
    "cache_media_frames": "!yes",
    "cache_record_files": "!no",
    "record_cache_dir": "/tmp",
    "transmit_silence": "!no",
    "transcode_via_sln": "!no",
    "runuser": "root",
    "rungroup": "root",
    "lightbackground": "!no",
    "forceblackbackground": "!no",
    "defaultlanguage": "en",
    "documentation_language": "en_US",
    "hideconnect": "!yes",
    "lockconfdir": "!no",
    "stdexten": "gosub",
    "live_dangerously": "!no",
    "entityid": "",
    "rtp_pt_dynamic": "96"
  }';

  /**
   * Конструктор без аргументов - инициализирует модель
   */
  public function __construct(){
    $this->ini = self::getINI('/etc/asterisk/asterisk.conf');
    $this->ini->options->normalize(self::$defaultparams);
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
    if(isset($defaultparams->$property)) return $this->ini->options->$property->getValue();
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
    if(isset($defaultparams->$property)) return $this->ini->options->$property = $value;
    return null; 
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save(){
    return $this->ini->save();
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
   //return $this->ami->send_request('Command', array('Command' => 'core reload'))!==false;
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