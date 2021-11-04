<?php

namespace config;

/**
 * Класс полей с текстовым значением
 */
class INIPropertyString extends INIProperty {
  public function __construct(array &$fields, string $key, $value) {
    if(!isset($fields[$key])) {
      $fields[$key] = $this; 
    } else {
      if($fields[$key] instanceof INIPropertyArray) {
        $fields[$key]->addValue($this);
      } else {
        new INIPropertyArray($fields, $key, $this);
      }
    }
    $this->_parse($key, $value);
  }

  protected function _parse(string $key, $value) {
    $this->_name = $key;
    $this->setValue($value);
  }

  public function setValue($value) {
    $this->_value = $value;
    return true;
  }

  public function __toString() {
    return (string) $this->_value;
  }

  public function castString() {
    return $this->_name.($this->_isExt?' => ':' = ').$this->_value;
  }
}
  
?>