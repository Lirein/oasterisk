<?php

namespace pjsip;

class Globals extends \module\Model {

  private $ini;

  private $changed;

  private static $defaultparams = '{
    "max_forwards": 70,
    "keep_alive_interval": 90,
    "contact_expiration_check_interval": 30,
    "disable_multi_domain": "!no",
    "max_initial_qualify_time": 0,
    "unidentified_request_period": 5,
    "unidentified_request_count": 5,
    "unidentified_request_prune_interval": 30,
    "user_agent": "",
    "regcontext": "",
    "default_outbound_endpoint": "default_outbound_endpoint",
    "default_voicemail_extension": "",
    "debug": "!no",
    "endpoint_identifier_order": ",ip,username,anonymous",
    "default_from_user": "asterisk",
    "default_realm": "asterisk",
    "mwi_tps_queue_high": 500,
    "mwi_tps_queue_low": -1,
    "mwi_disable_initial_unsolicited": "!no",
    "ignore_uri_user_options": "!no",
    "use_callerid_contact": "!no",
    "send_contact_status_on_update_registration": "!no",
    "taskprocessor_overload_trigger": "global",
    "norefersub": "!yes"
  }';

  public function __construct() {
    $this->changed = false;
    $this->ini = self::getINI('/etc/asterisk/pjsip.conf');
    $defaultparams = json_decode(self::$defaultparams);
    if(isset($this->ini->global)) {
      $this->ini->global->normalize(self::$defaultparams);
      foreach($defaultparams as $param => $value) {
        $this->data->$param = $this->ini->global->$param->getValue();
      }    
    } 
  }

  /**
   * Сериализация объекта. В дочернем классе вначала вызывается родительский метод, потом сериализуются дополнительные атрибуты
   *
   * @return string[]
   */
  public function __serialize() {
    $keys = array();
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

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
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
    if($property=='taskprocessor_overload_trigger') {
      switch($value) {
        case 'none':
        case 'pjsip_only': {
          $this->data->$property = $value;
        } break;
        default: {
          $this->data->$property = 'global';
        } break;
      }
      return true;
    }
    if($property=='endpoint_identifier_order') {
      if(is_array($value)) {
        $this->data->$property = array();
        foreach($value as $ventry) {
          switch($ventry) {
            case 'auth_username':
            case 'username':
            case 'ip': {
              $this->data->$property[] = $value;
            } break;
            default: {
              $this->data->$property[] = 'anonymous';
            } break;
          }
        }
        $this->data->$property = array_unique($this->data->$property);
      } else {
        $this->data->$property = array('username', 'ip');
      }
      return true;
    }
    if(isset($this->data->$property)) {
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

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save() {
    $this->ini->global->normalize(self::$defaultparams);
    foreach($this->data as $property => $value) {
      if($value instanceof \module\Subject) {
        $this->ini->global->$property = $value->old_id;  
      } else if(is_array($value)) {
        $entries = array();
        foreach($value as $ventry) {
          if($ventry instanceof \module\Subject) {
            $entries[] = $ventry->old_id;
          } else {
            $entries[] = $ventry;
          }
        }
        $this->ini->global->$property = $entries;
      } else {
        $this->ini->global->$property = $value;
      }
    }
    return $this->ini->save();
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    return $this->ami->send_request('Command', array('Command' => 'pjsip reload res_pjsip.so'))!==false;
  }

}

?>
