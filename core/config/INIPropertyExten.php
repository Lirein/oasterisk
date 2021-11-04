<?php

namespace config;

/**
 * Undocumented class
 */
class INIPropertyExten extends INIProperty {
  public function __construct(array &$fields, string $key, $value) {
    list($exten, $priority, $action) = explode(',', $value, 3);
    if(!isset($fields[trim($exten)])) {
      $fields[trim($exten)] = $this;
      $this->_name = $exten;
      $this->_value = array();
    }
    $fields[trim($exten)]->append($priority, $action);
  }

  public function append(string $priority, string $action) {
    new INIAction($this->_value, $priority, $action);
  }

  public function setValue($value) {
    $this->_value = $value;
    return true;
  }

  public function setComment(string $comment) {
    if(count($this->_value)) {
      $keys = array_keys($this->_value);
      $this->_value[$keys[count($keys)-1]]->setComment($comment);
    }
  }

  public function castString() {
    $ini = '';
    $first = true;
    $lastKey = -1;
    foreach($this->_value as $key => $action) {
      $alias = $action->getAlias();
      if($alias) $alias = "($alias)";
      if($first) {
        $ini .= 'exten'.($this->_isExt?' => ':' = ').$this->_name.','.$key.$alias.','.$action;
        $first = false;
      } else {
        if($key==$lastKey+1) {
          $newkey='n';
        } else {
          $newkey='n+'.($key-$lastKey+1);
        }
        $ini .= 'same'.($this->_isExt?' => ':' = ').$newkey.$alias.','.$action;
      }
      if(!empty($action->getComment())) $ini .= ' ;'.$action->getComment();
      $ini .= "\n";
      $lastKey = $key;
    }
    return $ini;
  }

  public function __toString() {
    $ini = '';
    $first = true;
    $lastKey = -1;
    foreach($this->_value as $key => $action) {
      $alias = $action->getAlias();
      if($alias) $alias = "($alias)";
      if($first) {
        $ini .= 'exten'.($this->_isExt?' => ':' = ').$this->_name.','.$key.$alias.','.$action."\n";
        $first = false;
      } else {
        if($key==$lastKey+1) {
          $key='n';
        } else {
          $key='n+'+($key-$lastKey+1);
        }
        $ini .= 'same'.($this->_isExt?' => ':' = ').$key.$alias.','.$action."\n";
      }
      $lastKey = $key;
    }
    return $ini;
  }
}
  
?>