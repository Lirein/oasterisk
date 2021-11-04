<?php

namespace core;

class CDRSettingsModel extends \module\Model {
  
  private $ini;

  private $activeengines;

  private static $defaultparams = '{
    "enable": "!yes",
    "unanswered": "!yes",
    "congestion": "!no",
    "endbeforehexten": "!no",
    "initiatedseconds": "!no",
    "batch": "!no",
    "size": "100",
    "time": "300",
    "scheduleronly": "!no",
    "safeshutdown": "!yes"
  }';

  /**
   * Конструктор без аргументов - инициализирует модель
   */
  public function __construct(){
    $this->ini = self::getINI('/etc/asterisk/cdr.conf');
    $this->ini->general->normalize(self::$defaultparams);
    $this->activeengines = array();
    $modules = findModulesByClass('core\CdrEngine', true);
    foreach($modules as $module) {
      $classname = $module->class;
      $classinfo = $classname::info();
      $this->activeengines[]= $classinfo->name;
      error_log('add '.$classinfo->name);
    }
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
    if ($property == 'activeengines') return $this->activeengines;
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
    if ($property == 'activeengines') {
      $modules = getModulesByClass('core\CdrEngineSettings', true);
      $passiveengines = array();
      if(isset($value)) {
        if (!is_array($value)) {
          $value = array();
        }
        if (!empty($value)) {
          foreach($value as $engine) {
            foreach($modules as $module) {
              $classinfo = $module::info();
              if($classinfo->id == $engine) {
                $module->enable();
              } else {
                if(!in_array($module, $passiveengines)) $passiveengines[] = $module;
              }
            }
          }
        } else {
          $passiveengines = $modules;
        }
      }
      foreach($passiveengines as $engine) $engine->disable();
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
    return $this->ami->send_request('Command', array('Command' => 'cdr reload'));
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
    $keys['activeengines']=$this->__get('activeengines');
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
    if(isset($assign_data->activeengines)) $this->__set('activeengines', $assign_data->activeengines);
  }
}
?>