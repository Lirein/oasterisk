<?php

namespace config;

/**
 * Класс полей содержащих массивы
 */
class INIPropertyArray extends INIProperty {

  private $_isComma;

  public function __construct(array &$fields, string $key, $value) {
    $this->_name = $key;
    $this->_value = array();
    $this->_isComma = false;
    if(isset($fields[$key])) $this->setValue($fields[$key]);
    $fields[$key] = $this;
    if($value!==null) $this->addValue($value);
  }

  public function setComment(string $comment) {
    $this->_value[array_key_last($this->_value)]->setComment($comment);
  }

  public function setDescription(string $value) {
    $this->_value[array_key_last($this->_value)]->setDescription($value);
  }

  public function setComma(bool $isComma = false) {
    $this->_isComma = $isComma;
  }

  public function isComma() {
    return $this->_isComma;
  }

  /**
   * Возвращает значение в виде массива реальных значений
   *
   * @return mixed Возвращает массив
   */
  public function getValue() {
    $result = array();
    foreach($this->_value as $element) {
      $result[] = $element->getValue();
    }
    return $result;
  }

  public function addValue($value) {
    if(is_array($value)) {
      foreach($value as $subvalue) {
        if($subvalue instanceof INIProperty) {
          $this->_value[] = $subvalue;
        } else {
          $dummy = array();
          if($subvalue!=='') $this->_value[] = new INIPropertyString($dummy, $this->_name, $subvalue);
          unset($dummy);
        }
      }
    } else {
      if($value instanceof INIProperty) {
        $this->_value[] = $value;
      } else {
        $dummy = array();
        $this->_value[] = new INIPropertyString($dummy, $this->_name, $value);
        unset($dummy);
      }
  }
  }

  public function setValue($value) {
    if(is_array($value)) {
      foreach($value as $entry) {
        if(!$entry instanceof INIProperty) return false;
      }
      $this->_value = $value;
    } elseif($value instanceof INIProperty) {
      $this->_value = array($value);
    } else {
      return false;
    }
    return true;
  }

  public function castString() {
    $ini = '';
    if($this->_isComma) {
      $ini .= $this->_name.($this->_isExt?' => ':' = ');
      $values = array();
      foreach($this->_value as $key => $value) {
        $values[] = (string) $value;
      }
      $ini .= implode(',', $values);
      $comment = $this->getComment();
      if($comment) $ini .= "\t;".$comment;
      $ini .= "\n";
    } else {
      foreach($this->_value as $key => $value) {
        $description = $value->getDescription();
        if($description) $ini .= INISection::_postproneComments($description);
        $ini .= $value->castString();
        $comment = $value->getComment();
        if($comment) $ini .= "\t;".$comment;
        if(!($value instanceof INIPropertyExten)) $ini .= "\n";
      }       
    }
    return $ini;
  }

  public function __toString() {
    return print_r($this->_value, true);
  }
}
  
?>