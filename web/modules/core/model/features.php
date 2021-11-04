<?php

namespace core;

class FeaturesModel extends \Module implements \module\IModel {
  
  private $ini;

  private $applicationmap; 

  private static $defaultparams = '{"featuremap": {
    "blindxfer": "#1",
    "disconnect": "*0",
    "automon": "*1",
    "atxfer": "*2",
    "parkcall": "#72",
    "automixmon": "*3"
  }, "general": {
    "transferdigittimeout": "3",
    "xfersound": "beep",
    "xferfailsound": "beeperr",
    "pickupexten": "*8",
    "pickupsound": "beep",
    "pickupfailsound": "beeperr",
    "featuredigittimeout": "1000",
    "recordingfailsound": "beeperr",
    "atxfernoanswertimeout": "15",
    "atxferdropcall": "!no",
    "atxferloopdelay": "10",
    "atxfercallbackretries": "2",
    "transferdialattempts": "3",
    "transferretrysound": "beep",
    "transferinvalidsound": "beeperr",
    "atxferabort": "*1",
    "atxfercomplete": "*2",
    "atxferthreeway": "*3",
    "atxferswap": "*4"
  }}';

  /**
   * Конструктор без аргументов - инициализирует модель
   */
  public function __construct(){
    parent::__construct();
    $this->ini = self::getINI('/etc/asterisk/features.conf');
    $this->ini->normalize(self::$defaultparams);
    $this->applicationmap = null;
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
    if(isset($defaultparams->general->$property)) return $this->ini->general->$property->getValue();
    if(isset($defaultparams->featuremap->$property)) return $this->ini->featuremap->$property->getValue();
    if($property=='applicationmap') {
      if(!$this->applicationmap) $this->appplicationmap = new FeatureMaps();
      return $this->applicationmap;
    }
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
    if(isset($defaultparams->general->$property)) return $this->ini->general->$property = $value;
    if(isset($defaultparams->featuremap->$property)) return $this->ini->featuremap->$property = $value;
    return null; 
  }

  /**
   * Сохраняет настройки функций канала
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
    return $this->ami->send_request('Command', array('Command' => 'module reload features'))!==false;
  }

  /**
    * Возвращает все свойства в виде объекта со свойствами
    *
    * @return \stdClass
    */
  public function cast() {
    $sections = array();

    foreach(json_decode(self::$defaultparams) as $sectionname => $section) {
      $keys = array();
      foreach ($section as $key => $value){
        $keys[$key] = $this->__get($key);
      }
      $sections[$sectionname] = $keys;
    }
    if(!$this->applicationmap) $this->applicationmap = new FeatureMaps();
    $sections['applicationmap'] = array();
    foreach($this->applicationmap as $k => $v) {
      $sections['applicationmap'][] = $v->cast();
    }  
    return (object) $sections;   
  }
  
  /**
    * Устанавливает все свойства новыми значениями
    *
    * @param stdClass $assign_data Объект со свойствами - ключ→значение 
    */
  public function assign($assign_data){
    foreach(json_decode(self::$defaultparams) as $sectionname => $section) {
      foreach ($section as $key => $value){
        if(isset($assign_data->$sectionname->$key)) $this->__set($key, $assign_data->$sectionname->$key);
      }
    }
    if(is_array($assign_data->applicationmap)) {
      if(!$this->applicationmap) $this->applicationmap = new FeatureMaps();
      foreach($this->applicationmap as $subject) {
        $subject->delete();
      }
      $i = (int)0;
      foreach($assign_data->applicationmap as $feature) {
        if ($feature->id  == ''){
          $feature->id = 'custom'.$i;
          $i++;
        }
        $subject = new FeatureMap($feature->id);
        if($subject->assign($feature)) {
          $subject->save();
        }
      }

      // foreach($assign_data->applicationmap as $feature) {
      //   $subject = new FeatureMap($feature->label);
      //   if($subject->assign($feature)) {
      //     $subject->save();
      //   }
      // }
      // foreach($this->applicationmap as $label => $subject) {
      //   $found = false;
      //   foreach($assign_data->applicationmap as $feature) {
      //     if($feature->label == $label) {
      //       $found = true;
      //       break;
      //     }
      //   }
      //   if(!$found) $subject->delete();
      // }
    } 
  }
}
?>