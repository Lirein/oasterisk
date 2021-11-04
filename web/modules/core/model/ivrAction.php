<?php

namespace core;

class IVRAction extends \module\Subject {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \core\IVR $collection
   */
  static $collection = 'core\\IVR';

  private $context;

  /**
   * Конструктор с идентификатором - инициализирует субьект коллекции
   * 
   * @param string $id Идентификатор элемента коллекции. Если идентификатор не задан, генерирует новый идентификатор, прежний идентификатор равен null. Если идентификатор задан - ищет субьект с указанным идентификатором или возвращает исключение в случае его отсутствия.
   */
  public function __construct(string $id = null){
    parent::__construct($id);
    $this->context = \dialplan\Dialplan::find('ivr-'.$id);
    $this->data->steps = $this->context->cast();
    //$this->data->steps = $this->context->$exten;

    
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
    if(($property=='name') || ($property=='title')) {
      $this->data->title = $value;
      $this->changed = true;
      return true;
    }
    if($property=='steps') {
      $this->data->steps = $value;
      $this->changed = true;
    }
    return parent::__set($property, $value);
  }

  /**
   * Сохраняет субьект в коллекции
   *
   * @return bool Возвращает истину в случае успешного сохранения субъекта
   */
  public function save(){
    $this->lock('ivraction');
    if (!$this->id) $this->id = (new self::$collection())->newID();
    
    $result = false;
    $steps = array();
    $i = 1;
    foreach($this->data->steps as $step) {
      $entry = new \stdClass();
      $entry->synonym = '';
      if(isset($step->synonym)) $entry->synonym = $step->synonym;
      $entry->value = (string)$step;
      $steps[$i++] = $entry;
    }
    
    $this->context->id = 'ivr-'.$this->id;
    $this->context->assign($steps);
    if(empty($this->old_id)) {
      \core\IVR::add($this); 
      $result = $this->context->save();
    } else {
      if($this->old_id!=$this->id) {
        $result = \core\IVR::rename($this);
        if(!$this->title) $this->title = $this->context->title;
        self::deltreeDB('ivr/'.$this->old_id);
        $result = $this->context->save();
      } else {
        \core\IVR::change($this);
        $result = $this->context->save();
      }
    }            
    $this->old_id = $this->id;
    $this->unlock('ivraction');
    return $result;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete(){
    return false;
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast(){
    return parent::cast();
  }

}
?>
