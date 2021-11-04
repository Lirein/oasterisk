<?php

namespace config;

/**
 * Класс полей со значением true\false
 */
class INIPropertyTrueFalse extends INIProperty {
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

  protected function _parse(string $key, string $value) {
    $this->_name = $key;
    $this->setValue($value);
  }

  public function setValue($value) {
    if($value === '') return false;
    $this->_value = ($value === true || strtolower($value) == 'true');
    return true;
  }

  public function castString() {
    return $this->_name.($this->_isExt?' => ':' = ').$this;
  }

  public function __toString() {
    return $this->_value ? 'true' : 'false';
  }
}
  
?>