<?php

namespace config;

/**
 * Undocumented class
 */
class INIAction extends INIProperty {
  private $_alias;

  public function __construct(array &$fields, string $key, $value) {
    $alias = explode('(', $key);
    $priolevel = explode('+', $alias[0]);
    if(trim($priolevel[0]) == 'n') {
      $baseprio = array_key_last($fields) + 1;
    } else {
      $baseprio = (int) trim($priolevel[0]);
    }
    if(isset($priolevel[1])) {
      $baseprio += (int) trim($priolevel[1]);
    }
    $fields[$baseprio] = $this;
    $this->_name = $baseprio;
    $this->_alias = null;
    if(isset($alias[1])) {
      $this->_alias = trim(substr($alias[1], 0, strpos($alias[1], ')')));
      if($this->_alias === '') $this->_alias = null;
    }
    $this->_value = $value;
  }

  public function getAlias() {
    return $this->_alias;
  }

  public function setAlias(string $alias) {
    $this->_alias = $alias;
  }

  public function setValue($value) {
    $this->_value = $value;
    return true;
  }

  public function castString() {
    return $this->_value;
  }
}
  
?>