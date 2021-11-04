<?php

namespace core;

class SyslogModel extends \Module implements \module\IModel {
  
  private $ini;

  private static $defaultparams = '{
    "dateformat": "%Y-%m-%d %H:%M:%S",
    "use_callids": "!yes",
    "appendhostname": "!yes",
    "queue_log": "!yes",
    "queue_log_to_file": "!no",
    "queue_log_name": "queue_log",
    "queue_log_realtime_use_gmt": "!no",
    "rotatestrategy": "sequential",
    "exec_after_rotate": "",
    "logger_queue_limit": "1000"
  }';

  /**
   * Конструктор без аргументов - инициализирует модель
   */
  public function __construct(){
    $this->ini = self::getINI('/etc/asterisk/logger.conf');
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
    switch($property) {
      case "dateformat": return ((string)$this->ini->general->dateformat);
      case "use_callids": return ((bool)$this->ini->general->use_callids->getValue());
      case "appendhostname": return ((bool)$this->ini->general->appendhostname->getValue());
      case "queue_log": return ((bool)$this->ini->general->queue_log->getValue());
      case "queue_log_to_file": return ((bool)$this->ini->general->queue_log_to_file->getValue());
      case "queue_log_name": return ((string)$this->ini->general->queue_log_name);
      case "queue_log_realtime_use_gmt": return ((bool)$this->ini->general->queue_log_realtime_use_gmt->getValue());
      case "rotatestrategy": return ((string)$this->ini->general->rotatestrategy);
      case "exec_after_rotate": return ((string)$this->ini->general->exec_after_rotate);
      case "logger_queue_limit": return (string)$this->ini->general->logger_queue_limit;
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
    $defaultparams = json_decode(self::$defaultparams);
    if (isset($defaultparams->$property)) {
      return $this->ini->general->$property = $value;
    }
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
    return $this->ami->send_request('Command', array('Command' => 'logger reload'));
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

    $returnData = new \stdClass();
    $returnData->general = (object) $keys;
    
    if(!isset($this->ini->logfiles->console)) $this->ini->logfiles->console = ',*';
    $returnData->logfiles = new \stdClass();
    foreach($this->ini->logfiles as $file => $value){
      $value = explode(',', $value);
      $value = array_filter($value);
      if(($pos = array_search('*', $value)) !== false) {
        unset($value[$pos]);
        if(count($value)==0) $value = array("verbose");
        $value = array_merge($value, array("notice","warning","error","debug","security","dtmf","fax"));
      }
      $returnData->logfiles->$file = $value;
    }
    return $returnData;
  }
  
  /**
    * Устанавливает все свойства новыми значениями
    *
    * @param stdClass $assign_data Объект со свойствами - ключ→значение 
    */
  public function assign($assign_data){
    foreach(json_decode(self::$defaultparams) as $key => $defvalue) {
      if(isset($assign_data->general->$key)) $this->__set($key, $assign_data->general->$key);
    } 
    if(isset($this->ini->logfiles)) unset($this->ini->logfiles);
    if(!isset($assign_data->logfiles)) $assign_data->logfiles = new \stdClass();
    //$assign_data->logfiles->extra->console = $assign_data->logfiles->console;
    foreach($assign_data->logfiles as $logfile) {
      if (isset($logfile->value)) {
        if(!is_numeric($logfile->key)&&is_array($logfile->value)) {
          $key = $logfile->key;
          $this->ini->logfiles->$key = implode(',',$logfile->value);
        }
      }
    }
    return true;
  }

  public function getSysLogDirectory() {
    $ini = self::getINI('/etc/asterisk/asterisk.conf');
    $return = (string) $ini->directories->astlogdir;
    unset($ini);
    return $return;
  }
}
?>