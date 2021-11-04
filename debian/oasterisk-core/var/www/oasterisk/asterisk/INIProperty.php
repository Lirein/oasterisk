<?php

abstract class INIProperty {
  /**
   * Внутреннее хранилище ключа поля
   *
   * @var string
   */
  protected $_name;
  /**
   * Внутреннее хранилище значения поля
   *
   * @var mixed
   */
  protected $_value;

  /**
   * Комментарий поля
   *
   * @var string
   */
  protected $_comment;

  /**
   * Описание поля
   *
   * @var string
   */
  protected $_description;

  /**
   * Является ли поле расширенным
   *
   * @var boolean
   */
  protected $_isExt = false;

  /**
   * Конструктор
   * 
   * @param array $fields Массив полей секции
   * @param string $key Ключ поля
   * @param mixed $value Значение поля
   */
  public abstract function __construct(array &$fields, string $key, $value);

  /**
   * Присваивает значение полю
   *
   * @param mixed $value Значение поля
   * @return bool
   */
  public abstract function setValue($value);

  /**
   * Возвращает значение поля
   *
   * @return mixed Возвращает значение поля
   */
  public function getValue() {
    return $this->_value;
  }

  /**
   * Преобразует экземпляр класса в строку
   *
   * @return string Возвращает поле в формате "Ключ = Значение"
   */
  public abstract function castString();

  /**
   * Преобразует значение поля в строку
   *
   * @return string
   */
  public function __toString() {
    return $this->_value;
  }

  // Общие методы

  /**
   * Задаёт комментарий поля
   *
   * @param string $comment Комментарий
   */
  public function setComment(string $comment) {
    $this->_comment = $comment;
  }

  /**
   * Возвращает комментарий поля
   *
   * @return string Возвращает комментарий поля
   */
  public function getComment() {
    return $this->_comment;
  }

  /**
   * Задаёт описание поля
   *
   * @param string $value Новое описание
   * @return void
   */
  public function setDescription(string $value) {
    $this->_description = $value;
  }

  /**
   * Возвращает описание поля
   *
   * @return string Возвращает описание поля
   */
  public function getDescription() {
    return $this->_description;
  }

  /**
   * Устанавливает ключ поля
   *
   * @param string $name Ключ поля
   * @return void
   */
  public function setName(string $name) {
    $this->_name = $name;
  }
  
  /**
   * Возвращает ключ поля
   *
   * @return string Возвращает ключ поля
   */
  public function getName() {
    return $this->_name;
  }
  
  /**
   * Преобразует поле в расширенное или обычное.
   *
   * @param boolean $extended Преобразовать поле в расширенное - true, обычное - false. Если параметр не указан, то преобразует в расширенное.
   * @return void
   */
  public function setExtended(bool $extended = true) {
    $this->_isExt = $extended;
  }

  /**
   * Проверяет является ли поле расширенным.
   *
   * @return bool Возвращает true, если поле расширенное. Иначе - false
   */
  public function getExtended() {
    return $this->_isExt;
  }
}

/**
 * Класс полей со значением true\false
 */
class INIPropertyTrueFalse extends INIProperty {
  public function __construct(array &$fields, string $key, $value) {
    if(!isset($fields[$key])) {
      $fields[$key] = $this; 
    } else {
      if($fields[$key] instanceof \INIPropertyArray) {
        $fields[$key]->addValue($this);
      } else {
        new \INIPropertyArray($fields, $key, $this);
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

/**
 * Класс полей со значением Yes\No
 */
class INIPropertyYesNo extends INIProperty {
  public function __construct(array &$fields, string $key, $value) {
    if(!isset($fields[$key])) {
      $fields[$key] = $this; 
    } else {
      if($fields[$key] instanceof \INIPropertyArray) {
        $fields[$key]->addValue($this);
      } else {
        new \INIPropertyArray($fields, $key, $this);
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
    $this->_value = ($value === true || strtolower($value) == 'yes' || strtolower($value) == 'true');
    return true;
  }

  public function castString() {
    return $this->_name.($this->_isExt?' => ':' = ').$this;
  }

  public function __toString() {
    return $this->_value ? 'yes' : 'no';
  }
}

/**
 * Класс полей со значением On\Off
 */
class INIPropertyOnOff extends INIProperty {
  public function __construct(array &$fields, string $key, $value) {
    if(!isset($fields[$key])) {
      $fields[$key] = $this; 
    } else {
      if($fields[$key] instanceof \INIPropertyArray) {
        $fields[$key]->addValue($this);
      } else {
        new \INIPropertyArray($fields, $key, $this);
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
    $this->_value = ($value === true || strtolower($value) == 'on' || strtolower($value) == 'true');
    return true;
  }

  public function castString() {
    return $this->_name.($this->_isExt?' => ':' = ').$this;
  }

  public function __toString() {
    return $this->_value ? 'on' : 'off';
  }
}

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

/**
 * Класс полей с текстовым значением
 */
class INIPropertyString extends INIProperty {
  public function __construct(array &$fields, string $key, $value) {
    if(!isset($fields[$key])) {
      $fields[$key] = $this; 
    } else {
      if($fields[$key] instanceof \INIPropertyArray) {
        $fields[$key]->addValue($this);
      } else {
        new \INIPropertyArray($fields, $key, $this);
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
    $this->_value = $value;
    return true;
  }

  public function castString() {
    return $this->_name.($this->_isExt?' => ':' = ').$this->_value;
  }
}

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
        if($subvalue instanceof \INIProperty) {
          $this->_value[] = $subvalue;
        } else {
          $dummy = array();
          if($subvalue!=='') $this->_value[] = new \INIPropertyString($dummy, $this->_name, $subvalue);
          unset($dummy);
        }
      }
    } else {
      if($value instanceof \INIProperty) {
        $this->_value[] = $value;
      } else {
        $dummy = array();
        $this->_value[] = new \INIPropertyString($dummy, $this->_name, $value);
        unset($dummy);
      }
  }
  }

  public function setValue($value) {
    if(is_array($value)) {
      foreach($value as $entry) {
        if(!$entry instanceof \INIProperty) return false;
      }
      $this->_value = $value;
    } elseif($value instanceof \INIProperty) {
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
        if($description) $ini .= \INISection::_postproneComments($description);
        $ini .= $value->castString();
        $comment = $value->getComment();
        if($comment) $ini .= "\t;".$comment;
        if(!($value instanceof \INIPropertyExten)) $ini .= "\n";
      }       
    }
    return $ini;
  }

  public function __toString() {
    return print_r($this->_value, true);
  }
}

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
