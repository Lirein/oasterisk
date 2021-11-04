<?php

namespace config;

use config\INIProperty;

class INISection implements \Iterator {
  /**
   * Внутреннее хранилище имени секции
   *
   * @var string
   */
  private $_name;
  /**
   * Массив полей
   *
   * @var INIProperty $_fields[]
   */
  private $_fields;
  /**
   * Описание секции
   *
   * @var string
   */
  private $_description;
  /**
   * Комментарий к секции
   *
   * @var string
   */
  private $_comment;
  /**
   * Указывает является ли секция шаблоном
   *
   * @var bool
   */
  private $_isTemplate;
  /**
   * Список шаблоном секции
   *
   * @var array
   */
  private $_templateList;

  private $_defaults;

  /**
   * Конструктор
   *
   * @param string $name Имя секции
   */
  public function __construct(string $name) {
    $this->_name = $name;
    $this->_fields = array();
    $this->_description = '';
    $this->_comment = '';
    $this->_isTemplate = false;
    $this->_templateList = array();
    $this->_defaults = null;
  }

  /**
   * Деструктор
   */
  public function __destruct() {
    foreach($this->_fields as $field) {
      unset($field);
    }
    unset($this->_fields);
  }

  /**
   * Присваивает значение существующему полю или создаёт новое
   *
   * @param string $property Ключ поля
   * @param mixed $value Значение поля
   */
  public function __set(string $property, $value) {
    if(isset($this->_fields[$property])) {
      if(!$this->_fields[$property]->setValue($value)) {
        unset($this->_fields[$property]);
        $this->addField($property, $value);
      } else {
        $this->_checkField($property);
      }
    } else {
      $this->addField($property, $value);
    }
  }

  /**
   * Возвращает значение поля
   *
   * @param string $property Ключ поля
   * @return mixed Возвращает значение поля, если оно существующет. Иначе возвращает NULL
   */
  public function __get(string $property) {
    if(isset($this->_fields[$property])) {
      return $this->_fields[$property];
    } else {
      foreach(array_reverse($this->_templateList) as $template) {
        if(isset($template->$property)) return $template->$property;
      }
    }
    return null;
  }

  /**
   * Определяет, было ли установлено поле значением, отличным от NULL
   *
   * @param string $property Ключ поля
   * @return boolean Возвращает true, если поле определено в текущей секции или одном из её шаблонов
   */
  public function __isset(string $property) {
    if(isset($this->_fields[$property])) {
      return true;
    } else {
      foreach(array_reverse($this->_templateList) as $template) {
        if(isset($template->$property)) return true;
      }
    }
    return false;
  }

  /**
   * Удаляет указанное поле
   *
   * @param string $property Ключ поля
   */
  public function __unset(string $property) {
    if(isset($this->_fields[$property])) {
      unset($this->_fields[$property]);
    }
  }

  private function _sortExtents(INIProperty $extena, INIProperty $extenb) {
    $aname = $extena->getName();
    $bname = $extenb->getName();
    if(($extena instanceof INIPropertyExten)&&($extenb instanceof INIPropertyExten)) {
      if((($aname[0]=='_')&&($bname[0]=='_'))||(($aname[0]!='_')&&($bname[0]!='_'))) {
        if(strlen($aname)<strlen($bname)) {
          return 1;
        } elseif(strlen($aname)>strlen($bname)) {
          return -1;
        } else {
          return strnatcasecmp($aname, $bname);
        }
      } else {
        if($aname[0]=='_') {
          return -1;
        } else {
          return 1;
        }
      }
    } elseif(($extena instanceof INIPropertyExten)||($extenb instanceof INIPropertyExten)) {
      if(($extena instanceof INIPropertyExten)) {
        return -1;
      } else {
        return 1;
      }
    }

  }

  /**
   * Преобразует экземпляр класса в строку
   *
   * @return string Возвращает преобразованную в строку секцию
   */
  public function __toString() {
    $ini = '';
    if($this->_description != null) {
      $ini .= self::_postproneComments($this->_description);
    }
    
    $ini .= "[".$this->_name."]";
    if(($this->_isTemplate) || (count($this->_templateList))) {
      $ini .= "(";
      if($this->_isTemplate) {
        $ini .= "!";
      }
      if(count($this->_templateList)) {
        $num = 0;
        if($this->_isTemplate) {
          $num = 1;
        }
        foreach($this->_templateList as $template) {
          if($num > 0) {
            $ini .= ",";
          }
          $num++;
          $ini .= $template->getName();
        }
      }
      $ini .= ")";
    }
    if($this->_comment != null) {
      $ini .= "\t;".$this->_comment;
    }
    $ini .= "\n";

    uasort($this->_fields, array($this, '_sortExtents'));
    $fieldlist = $this->_fields;
    if (isset($this->_defaults)) {
      foreach($this->_defaults as $key => $defvalue) {
        if (isset($fieldlist[$key])){
          $value = $fieldlist[$key];
          if ($value->getValue() != $defvalue){
            $description = $value->getDescription();
            if($description) $ini .= self::_postproneComments($description);
            $ini .= $value->castString();
            $comment = $value->getComment();
            if($comment) $ini .= "\t;".$comment;
            if(!(($value instanceof INIPropertyExten) || ($value instanceof INIPropertyArray))) $ini .= "\n";
          }
          unset($fieldlist[$key]);
        }
      }
    } 
    foreach($fieldlist as $key => $value) {
      $description = $value->getDescription();
      if($description) $ini .= self::_postproneComments($description);
      $ini .= $value->castString();
      $comment = $value->getComment();
      if($comment) $ini .= "\t;".$comment;
      if(!(($value instanceof INIPropertyExten) || ($value instanceof INIPropertyArray))) $ini .= "\n";
    }
    unset($fieldlist);

    $ini .= "\n";
    return $ini;
  }

  /**
   * Преобразует многострочные описания для вывода
   *
   * @param string $comment Комментарий (описание)
   * @return string Возвращает 
   */
  public static function _postproneComments(string $comment) {
    $result = "";
    if(isset($comment)) {
      $commentArray = explode("\n", $comment);
      $next = array_shift($commentArray);
      while ($next !== null) {
        if($next !== "") {
          $result .= ";".$next."\n";
        }
        $next = array_shift($commentArray);
      }
    }
    return $result;
  }

  /**
   * Проверяет, пуста ли секция
   *
   * @return boolean Возвращает FALSE, если секция существует и имеет непустое имя. В противном случае возвращает TRUE. 
   */
  public function isEmpty() {
    if(!isset($this->_name)||($this->_name===null)||($this->_name==='')) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Добавляет новое поле в секцию
   *
   * @param string $key Ключ поля
   * @param mixed $value Значение поля
   * @return INIProperty Созданное свойство
   */
  public function addField(string $key, $value) {
    $result = null;
    switch($key) {
      case 'exten':{
        $result = new INIPropertyExten($this->_fields, $key, $value);
      } break;
      case 'same':{
        $lastexten = array_key_last($this->_fields);
        if($lastexten) {
          $result = new INIPropertyExten($this->_fields, $key, $lastexten.','.$value);
        }
      } break;
      case 'load':
      case 'noload':
      case 'preload':{
        if($value === '') return null;
        $result = new INIPropertyModule($this->_fields, $key, $value);
      } break;
      default:{
        // if($value === '') return;
        if(($value === 'true') || ($value === 'false')) {
          $result = new INIPropertyTrueFalse($this->_fields, $key, $value);
        } elseif(($value === 'yes') || ($value === 'no')) {
          $result = new INIPropertyYesNo($this->_fields, $key, $value);
        } elseif(($value === 'on') || ($value === 'off')) {
          $result = new INIPropertyOnOff($this->_fields, $key, $value);
        } elseif(is_array($value)) {
          if(count($value)<=1) {
            $result = new INIPropertyArray($this->_fields, $key, $value);
          } else {
            foreach($value as $entry) {
              new INIPropertyString($this->_fields, $key, $entry);
            }
            $result = $this->_fields[$key];
          }
        } else {
          $result = new INIPropertyString($this->_fields, $key, $value);
        }
        $this->_checkField($key);
      }
    }
    return $result;
  }

  /**
   * Проверяет совпадает ли поле с полями из шаблонов. Если совпадение найдено, поле удаляется.
   *
   * @param string $key Ключ поля
   * @return void
   */
  private function _checkField(string $key) {
    if (isset($this->_fields[$key])) {
      $realvalue = $this->_fields[$key]->getValue();
      foreach(array_reverse($this->_templateList) as $template) {
        if(isset($template->$key)){
          $templatevalue = $template->$key->getValue();
          if (isset($this->_defaults)&&isset($this->_defaults->$key)&&(is_array($this->_defaults->$key)||((strlen($this->_defaults->$key)>0)&&($this->_defaults->$key[0]==',')))){
            if (!is_array($templatevalue)) {
              if (strpos($templatevalue,",")!=-1) {
                $templatevalue = explode(",",$templatevalue);
              } else {
                $templatevalue = array($templatevalue);
              }
            }
          } 
          if ($realvalue === $templatevalue) {
            unset($this->_fields[$key]);
            
            break;
          }
        }
      }
    }
  }

  private function _moveFieldToEnd(string $key) {
    if(isset($this->_fields[$key])) {
      $tmp = $this->_fields[$key];
      unset($this->_fields[$key]);
      $this->_fields[$key] = $tmp;
    }
  }

  /**
   * Задаёт комментарий к секции
   *
   * @param string $value Новый комментарий
   * @return void
   */
  public function setComment(string $value) {
    $this->_comment = $value;
  }

  /**
   * Возвращает комментарий к секции
   *
   * @return string Возвращает комментарий к секции
   */
  public function getComment() {
    return $this->_comment;
  }

  /**
   * Задаёт описание секции
   *
   * @param string $value Новое описание
   * @return void
   */
  public function setDescription(string $value) {
    $this->_description = $value;
  }

  /**
   * Возвращает описание секции
   *
   * @return string Возвращает описание секции
   */
  public function getDescription() {
    return (trim($this->_description)=='')?'':$this->_description;
  }

  /**
   * Устанавливает имя секции
   *
   * @param string $name Новое имя секции
   * @return void
   */
  public function setName(string $name) {
    $this->_name = $name;
  }
  
  /**
   * Возвращает имя секции
   *
   * @return string Возвращает имя секции
   */
  public function getName() {
    return $this->_name;
  }
  
  /**
   * Устанавливает комментарий последнему полю
   *
   * @param string $value Комментарий
   * @return void
   */
  public function setLastComment(string $value) {
    $key = array_key_last($this->_fields);
    if($key===null) return;
    $this->_fields[$key]->setComment($value);
    $this->_checkField($key);
  }

  /**
   * Устанавливает описание последнему полю
   *
   * @param string $value Описание
   * @return void
   */
  public function setLastDescription(string $value) {
    $key = array_key_last($this->_fields);
    if($key===null) return;
    $this->_fields[$key]->setDescription($value);
    $this->_checkField($key);
  }

  /**
   * Преобразует последнее поле в расширенное или обычное.
   *
   * @param boolean $extended Преобразовать поле в расширенное - true, обычное - false. 
   * @return void
   */
  public function setLastExtended(bool $extended) {
    $key = array_key_last($this->_fields);
    if($key===null) return;
    $this->_fields[$key]->setExtended($extended);
    $this->_checkField($key);
  }

  /**
   * Преобразует секцию в шаблон
   *
   * @param boolean $value Преобразовать секцию в шаблон - true, иначе - false. 
   * @return void
   */
  public function setTemplate(bool $value = true) {
    $this->_isTemplate = $value;
  }

  /**
   * Проверяет, является ли секция шаблоном
   *
   * @return boolean Возвращает true, если секция являтся шаблоном. Иначе - false
   */
  public function isTemplate() {
    return $this->_isTemplate;
  }

  /**
   * Возвращает набор шаблонов использованых в описании секции
   *
   * @return array Набор шаблонов
   */
  public function getTemplates() {
    return $this->_templateList;
  }

  /**
   * Возвращает набор шаблонов использованых в описании секции включая всю цепочку наследования
   *
   * @return array Набор шаблонов
   */
  public function getAllTemplates() {
    $result = array();
    foreach($this->_templateList as $template) {
      $result[] = $template;
      $result = array_merge($result, $template->getAllTemplates());
    };
    return $result;
  }

  /**
   * Возвращает имена шаблонов использованых в описании секции
   *
   * @return array Имена шаблонов
   */
  public function getTemplateNames() {
    $result = array();
    foreach($this->_templateList as $template) {
      $result[] = $template->getName();
    }
    return $result;
  }

  /**
   * Добавляет шаблон для секции
   *
   * @param INISection $template Новый шаблон
   * @return void
   */
  public function addTemplate(INISection $template) {
    if(!in_array($template, $this->_templateList, true)) {
      $this->_templateList[] = $template;
    }
  }

  /**
   * Удаляет шаблон для секции
   *
   * @param INISection $template Удаляемый шаблон
   * @return void
   */
  public function removeTemplate(string $template) {
    foreach($this->_templateList as $index => $template) {
      if($template->getName() == $template) {
        unset($this->_templateList[$index]);
      }
    }
  }

  /**
   * Удаляет шаблоны секции
   *
   * @return void
   */
  public function clearTemplates() {
    $this->_templateList = array();
  }

  //Методы итератора
  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    reset($this->_fields);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    return current($this->_fields);
  }

  /**
   * Возвращает ключ текущего элемента массива полей
   *
   * @return int|string|null Возвращает ключ текущего элемента или же NULL при неудаче. 
   */
  public function key() {
    return key($this->_fields);
  }

  /**
   * Передвигает текущую позицию к следующему элементу массива полей
   *
   * @return void
   */
  public function next() {
    next($this->_fields);
  }

  /**
   * Проверяет корректность текущей позиции массива полей
   *
   * @return bool Возвращает TRUE в случае успешного завершения или FALSE в случае возникновения ошибки
   */
  public function valid() {
    return (key($this->_fields) !== null);
  }

  /**
   * Нормализация типов данных секций
   *
   * @param mixed $json Массив или json строка
   * @return bool Возвращает истину в случае успешного выполнения операции
   */
  public function normalize($json) {
    $result = true;
    if(is_array($json)) $json = json_encode($json);
    if(is_string($json)) $json = json_decode($json);
    $this->_defaults = $json;
    foreach($json as $key => $value) {
      if(isset($this->$key)) {
        if(is_array($value)) {
          if(!($this->$key instanceof INIPropertyArray)) {
            $oldValue = $this->$key->getValue();
            if(isset($this->_fields[$key])) {
              $oldComment = $this->$key->getComment();
              $oldDescription = $this->$key->getDescription();
              unset($this->_fields[$key]);
            } else {
              $oldComment = null;
              $oldDescription = null;
            }
            $newprop = new INIPropertyArray($this->_fields, $key, $oldValue);
            if($oldComment) $newprop->setComment($oldComment);
            if($oldDescription) $newprop->getDescription($oldDescription);
          } else {
            $this->_moveFieldToEnd($key);
          }
        } elseif(is_bool($value)) {
          if(!(($this->$key instanceof INIPropertyOnOff) || ($this->$key instanceof INIPropertyYesNo) || ($this->$key instanceof INIPropertyTrueFalse))) {
            $oldValue = (string) $this->$key;
            if(isset($this->_fields[$key])) {
              $oldComment = $this->$key->getComment();
              $oldDescription = $this->$key->getDescription();
              unset($this->_fields[$key]);
            } else {
              $oldComment = null;
              $oldDescription = null;
            }
            $newprop = new INIPropertyTrueFalse($this->_fields, $key, $oldValue);
            if($oldComment) $newprop->setComment($oldComment);
            if($oldDescription) $newprop->getDescription($oldDescription);
          } else {
            $this->_moveFieldToEnd($key);
          }
        } elseif($value === null) {
        } elseif(in_array($value, array('!true', '!false'))) {
          if(!($this->$key instanceof INIPropertyTrueFalse)) {
            $oldValue = (string) $this->$key;
            if(isset($this->_fields[$key])) {
              $oldComment = $this->$key->getComment();
              $oldDescription = $this->$key->getDescription();
              unset($this->_fields[$key]);
            } else {
              $oldComment = null;
              $oldDescription = null;
            }
            $newprop = new INIPropertyTrueFalse($this->_fields, $key, $oldValue);
            if($oldComment) $newprop->setComment($oldComment);
            if($oldDescription) $newprop->getDescription($oldDescription);
          } else {
            $this->_moveFieldToEnd($key);
          }
        } elseif(in_array($value, array('!on', '!off'))) {
          if(!($this->$key instanceof INIPropertyOnOff)) {
            $oldValue = (string) $this->$key;
            if(isset($this->_fields[$key])) {
              $oldComment = $this->$key->getComment();
              $oldDescription = $this->$key->getDescription();
              unset($this->_fields[$key]);
            } else {
              $oldComment = null;
              $oldDescription = null;
            }
            $newprop = new INIPropertyOnOff($this->_fields, $key, $oldValue);
            if($oldComment) $newprop->setComment($oldComment);
            if($oldDescription) $newprop->getDescription($oldDescription);
          } else {
            $this->_moveFieldToEnd($key);
          }
        } elseif(in_array($value, array('!yes', '!no'))) {
          if(!($this->$key instanceof INIPropertyYesNo)) {
            $oldValue = (string) $this->$key;
            if(isset($this->_fields[$key])) {
              $oldComment = $this->$key->getComment();
              $oldDescription = $this->$key->getDescription();
              unset($this->_fields[$key]);
            } else {
              $oldComment = null;
              $oldDescription = null;
            }
            $newprop = new INIPropertyYesNo($this->_fields, $key, $oldValue);
            if($oldComment) $newprop->setComment($oldComment);
            if($oldDescription) $newprop->getDescription($oldDescription);
          } else {
            $this->_moveFieldToEnd($key);
          }
        } elseif((strlen($value)>0)&&($value[0]==',')) {
          if(!($this->$key instanceof INIPropertyArray)) {
            $oldValue = $this->$key->getValue();
            if(isset($this->_fields[$key])) {
              $oldComment = $this->$key->getComment();
              $oldDescription = $this->$key->getDescription();
              unset($this->_fields[$key]);
            } else {
              $oldComment = null;
              $oldDescription = null;
            }
            $newprop = new INIPropertyArray($this->_fields, $key, explode(',', $oldValue));
            $newprop->setComma(true);
            if($oldComment) $newprop->setComment($oldComment);
            if($oldDescription) $newprop->getDescription($oldDescription);
          } else {
            $this->_moveFieldToEnd($key);
            $this->$key->setComma(true);
          }
        } else {
          if(!(
              (($this->$key instanceof INIPropertyString) || ($this->$key instanceof INIPropertyModule))
           || (($this->$key instanceof INIPropertyYesNo) && in_array($value, array(null, "", "!yes", "!no")))
           || (($this->$key instanceof INIPropertyOnOff) && in_array($value, array(null, "", "!on", "!off")))
           || (($this->$key instanceof INIPropertyTrueFalse) && in_array($value, array(null, "", "!true", "!false")))
            )) {
            $oldValue = (string) $this->$key;
            if(isset($this->_fields[$key])) {
              $oldComment = $this->$key->getComment();
              $oldDescription = $this->$key->getDescription();
              unset($this->_fields[$key]);
            } else {
              $oldComment = null;
              $oldDescription = null;
            }
            $newprop = new INIPropertyString($this->_fields, $key, $oldValue);
            if($oldComment) $newprop->setComment($oldComment);
            if($oldDescription) $newprop->getDescription($oldDescription);
          } else {
            $this->_moveFieldToEnd($key);
          }
        }
      } else {
        if(is_string($value)&&in_array($value, array('!yes', '!no', '!true', '!false', '!on', '!off'))) {
          $this->addField($key, substr($value, 1));
        } elseif(is_array($value)&&(count($value)==0)) {
          $this->addField($key, array());
        } elseif(is_string($value)&&(strlen($value)>0)&&$value[0]==',') {
          $value = explode(',', $value);
          array_splice($value, 0, 1);
          if(count($value)&&($value[0]=='')) array_splice($value, 0, 1);
          /**
           * @var INIPropertyArray $property
           */
          $property = $this->addField($key, $value);
          $property->setComma(true);
        } else {
          $this->addField($key, $value);
        }
      }
      if(in_array($value, array('!yes', '!no', '!true', '!false', '!on', '!off'))) {
        $this->_defaults->$key = in_array($value, array('!yes', '!true', '!on'));
      }
    }

    return $result;
  }

}
