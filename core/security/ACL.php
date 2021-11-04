<?php

namespace security;

class ACL extends \module\Subject {
  
  private $ini;

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \security\ACLs $collection
   */
  static $collection = 'security\\ACLs';

  private static $defaultparams = '{
    "deny": [],
    "permit": []
  }';

  /**
   * Конструктор без аргументов - инициализирует модель
   */
  public function __construct(string $id = null) {
    $this->ini = self::getINI('/etc/asterisk/acl.conf');
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
    $this->lock('acl');
    if (!$this->id) $this->id = (new self::$collection())->newID();
    $sectionname = $this->id;

    if($this->old_id!==null) {
      // if($this->id!=$this->old_id) {
      //   ACLs::rename($this);
      //   $oldname = $this->old_id;
      //   $this->ini->$sectionname = $this->ini->$oldname; //Перемещаем секцию под новым именем
      //   $this->ini->$sectionname->setName($sectionname);
      //   unset($this->ini->$oldname);
      // } else {
        ACLs::change($this);
      //}
    } else { //Инициализируем секцию
      ACLs::add($this);
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
    $this->unlock('acl');
    return $result;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    ACLs::remove($this);
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
    return $this->ami->send_request('Command', array('Command' => 'acl reload'));
  }

}
?>