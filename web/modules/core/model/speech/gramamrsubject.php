<?php

namespace core;

class Grammar extends \module\Subject {
  
  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \core\Grammars $collection
   */
  static $collection = 'core\\Grammars';

  /**
   * Параметры грамматики:
   * title - заголовок
   * grams - набор грамматик вида ключ=значения
   * 
   * @var string $defaultparams
   */
  private static $defaultparams = '{
    "title": "",
    "grams": [{"value": [""], "id": ""}]
  }';
 
  /**
   * Конструктор с идентификатором - инициализирует модель
   */
  public function __construct(string $id = null) {
    parent::__construct();
    $this->data = \config\DB::readDataItem('grammars', 'id', $id, self::$defaultparams);
    if($this->data != null) {
      $grams = array();
      foreach($this->data->grams as $gram) {
        $grams[$gram->id] = $gram->value;
      }
      $this->data->grams = $grams;
      $this->old_id = $id;
    } else {
      $this->data = json_decode(self::$defaultparams);
      $id = (new static::$collection())->newID();
      $this->data->grams = array();
    }
    if(!$this->data->title) $this->data->title = $id;
    $this->id = $id;
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    if($property=='grams') {
      $this->data->grams = array();
      if($value) foreach($value as $gram) {
        if(!is_numeric($gram->key)&&is_array($gram->value)) $this->data->grams[$gram->key] = $gram->value;
      }
      return true;
    } 
    return parent::__set($property, $value);
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save() {
    $this->lock('grammar');
    if (!$this->id) $this->id = (new self::$collection())->newID();
    $sectionname = $this->id;

    if($this->old_id!==null) {
      if($this->id!=$this->old_id) {
        Grammars::rename($this);
        $oldname = $this->old_id;
        $olddata = \config\DB::readDataItem('grammars', 'id', $oldname, self::$defaultparams);
        \config\DB::deleteDataItem('grammars', 'id', $oldname, self::$defaultparams);
        \config\DB::writeDataItem('grammars', 'id', $sectionname, self::$defaultparams, $olddata);
      } else {
        Grammars::change($this);
      }
    } else { //Создаем расписание
      Grammars::add($this);
    }
    $olddata = clone $this->data;
    $grams = array();
    foreach($olddata->grams as $key => $gram) {
      $grams[] = (object)array('value' => $gram, 'id' => $key);
    }
    $olddata->grams = $grams;
    \config\DB::writeDataItem('grammars', 'id', $sectionname, self::$defaultparams, $olddata);
    $this->old_id = $this->id;
    $this->unlock('grammar');
    return true;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    $subjectid = $this->old_id;
    $result = \config\DB::deleteDataItem('grammars', 'id', $subjectid, self::$defaultparams);
    if($result) Grammars::remove($this);
    return $result;
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    return false;
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast() {
    $keys = array();
    $keys['id'] = $this->id;
    $keys['old_id'] = $this->old_id;
    foreach($this->data as $key => $value) {
      $keys[$key] = $this->__get($key);
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

  public function __toString() {
    $result = "#JSGF V1.0;\ngrammar ".$this->id.";\n";
    $public = array();
    foreach($this->data->grams as $gram => $gramvalue) {
      $sentences = array();
      $i = 1;
      foreach($gramvalue as $sentence) {
        $sentences[] = "<".$gram.$i.">";
        $result .= "<".$gram.$i."> = ".$sentence.";\n";
        $i++;
      }
      if(count($sentences)) {
        $result .= "<$gram> = ".implode(' | ', $sentences).";\n";
        $public[] = "<$gram>";
      }
    }
    $result .= "public <".$this->id."> = ".implode(' | ', $public).";\n";
    return $result;
  }

}

?>