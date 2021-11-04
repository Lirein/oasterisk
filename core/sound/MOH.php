<?php

namespace sound;

class MOH extends \module\Subject {
  
  private $ini;

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \sound\MOHs $collection
   */
  static $collection = 'sound\\MOHs';

  private static $defaultparams = '{
    "mode": "quietmp3",
    "directory": "moh",
    "digit": "",   
    "announcement": "",
    "sort": "",
    "application": "",
    "format": "",
    "kill_escalation_delay": "500",
    "kill_method": "process_group"
  }';

  /**
   * Конструктор без аргументов - инициализирует модель
   */
  public function __construct(string $id = null) {
    $this->ini = self::getINI('/etc/asterisk/musiconhold.conf');
    parent::__construct($id);
    $defaultparams = json_decode(self::$defaultparams);
    if(isset($this->ini->$id)) {
      $this->ini->$id->normalize(self::$defaultparams);
      foreach($defaultparams as $param => $value) {
        $this->data->$param = $this->ini->$id->$param->getValue();
      }       
      $this->old_id = $id;
      $this->data->title = $this->ini->$id->getComment();
    } else {
      $this->ini->$id->normalize(self::$defaultparams);
      foreach($defaultparams as $param => $value) {
        $this->data->$param = $this->ini->$id->$param->getValue();
      }       
      $this->data->title = null;
      unset($this->ini->$id);
    }
    if(!$this->data->title) $this->data->title = $id;
    $this->id = $id;
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
    $this->lock('moh');
    if (!$this->id) $this->id = (new self::$collection())->newID();
    $sectionname = $this->id;

    if($this->old_id!==null) {
      if($this->id!=$this->old_id) {
        MOHs::rename($this);
        $oldname = $this->old_id;
        $this->ini->$sectionname = $this->ini->$oldname; //Перемещаем секцию под новым именем
        $this->ini->$sectionname->setName($sectionname);
        unset($this->ini->$oldname);
      } else {
        MOHs::change($this);
      }
    } else { //Инициализируем секцию
      MOHs::add($this);
      $this->ini->$sectionname->normalize(self::$defaultparams);
    }
    foreach($this->data as $property => $value) {
      $this->ini->$sectionname->$property = $value;
    }
    if ((!$this->title) || ($this->title == $this->id)) {
      $this->ini->$sectionname->setComment('');
    } else {
      $this->ini->$sectionname->setComment($this->title);
    }
    $this->old_id = $this->id;
    $result = $this->ini->save();
    $this->unlock('moh');
    return $result;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    MOHs::remove($this);
    $sectionname = $this->old_id;
    if(isset($this->ini->$sectionname)) {
      unset($this->ini->$sectionname);
      return $this->ini->save();
    }
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    return $this->ami->send_request('Command', array('Command' => 'module reload res_musiconhold'))!==false;
  }

}
?>