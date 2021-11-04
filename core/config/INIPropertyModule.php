<?php

namespace config;

/**
 * Класс полей для хранения модулей
 */
class INIPropertyModule extends INIProperty {
  public function __construct(array &$fields, string $key, $value) {
    $fields[$value] = $this;
    $this->_parse($key, $value);
  }

  protected function _parse(string $key, string $value) {
    $this->_name = $value;
    $this->setValue($key);
  }

  public function setValue($value) {
    if($value === '') return false;
    $this->_value = $value;
    return true;
  }

  public function castString() {
    return $this->_value.($this->_isExt?' => ':' = ').$this->_name;
  }

  public function __toString() {
    return $this->_name;
  }
}
  
?>