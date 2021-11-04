<?php

namespace core;

class FeatureMap extends \module\Subject {
  
  private $ini;

  /**
   * Конструктор без аргументов - инициализирует модель
   */
  public function __construct(string $id = null) {
    $this->ini = self::getINI('/etc/asterisk/features.conf');
    parent::__construct($id);
    if(isset($this->ini->applicationmap->$id)) {
      $value = $this->ini->applicationmap->$id->getValue();
      $this->old_id = $id;
    } else {
      $value = ',self,NoOp';
    }
    $params = explode(',', $value);
    $this->data->dtmf = $params[0];
    $activateon = explode('/', $params[1]);
    $this->data->activateon = $activateon[0];
    $appdata = explode('(', $params[2]);
    $this->data->action = $appdata[0];
    $this->data->moh = '';
    if(count($appdata)>1) { //have a open brace
      $this->data->actiondata = substr($appdata[1], 0, strpos($appdata[1], ')'));
      if(count($params)>3) $this->data->moh = $params[3];
    } else {
      $this->data->actiondata = '';
      if(count($params)>3) $this->data->actiondata = $params[3];
      if(count($params)>4) $this->data->moh = $params[4];
    }
    $this->id = $id;
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    parent::__destruct();
    unset($this->ini);
  }

  public function __toString() {
    return $this->data->dtmf.','.$this->data->activateon.','.$this->data->action.'('.$this->data->actiondata.')'.($this->data->moh?(','.$this->data->moh):'');
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save() {
    $featurelabel = $this->id;
    if(!$featurelabel) return false;
    if($this->old_id!==null) {
      if($this->id!=$this->old_id) {
        FeatureMaps::rename($this);
        $oldlabel = $this->old_id;
        unset($this->ini->applicationmap->$oldlabel);
      } else {
        FeatureMaps::change($this);
      }
    } else { //Инициализируем секцию
      FeatureMaps::add($this);
    }
    $this->ini->applicationmap->$featurelabel = $this->data->dtmf.','.$this->data->activateon.','.$this->data->action.'('.$this->data->actiondata.')'.($this->data->moh?(','.$this->data->moh):'');
    $this->old_id = $this->id;
    return $this->ini->save();
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    FeatureMaps::remove($this);
    $featurelabel = $this->old_id;
    if(isset($this->ini->applicationmap->$featurelabel)) {
      unset($this->ini->applicationmap->$featurelabel);
      $this->ini->save();
    }
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    return $this->ami->send_request('Command', array('Command' => 'module reload features'))!==false;
  }

}
?>