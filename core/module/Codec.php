<?php

namespace module;

abstract class Codec extends \module\Subject {
  
  protected $ini;

  abstract static public function info();

  /**
   * Конструктор 
   */
  public function __construct(string $id = null) {
    $this->ini = self::getINI('/etc/asterisk/codecs.conf');
    $info = get_class($this)::info(); 
    $id = $info->name;
    parent::__construct($id);
    $propinfo = false;
    if(isset($this->ini->$id)) {
      $propinfo = $this->ini->$id;
    }
    else if(($this instanceof \codec\IPCM) && isset($this->ini->plc)) $propinfo = $this->ini->plc;
    if($propinfo) {
      foreach($propinfo as $k => $v) {
        $this->data->$k = (string) $v;
      }
    }
    $this->data->title=$info->title;;
    
    $this->id = $id;
    $this->old_id = $id;
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    parent::__destruct();
    unset($this->ini);
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save() {
    $sectionname = $this->old_id;
    if(!$sectionname) return false;     
    if(!$this->changed) return false;

    Codecs::change($this);

    if(isset($this->data->genericplc) && $this->data->genericplc && ($this instanceof \codec\IPCM)) {
      if(isset($this->ini->$sectionname)) unset($this->ini->$sectionname);
    } else {
      foreach($this->data as $property => $value) {
        if($property=='title') continue;
        $this->ini->$sectionname->$property = (string)$value;
      }
      $this->ini->$sectionname->setComment(get_class($this)::info()->title);
    }    
    
    return $this->ini->save();
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    return false;
  }

}
?>