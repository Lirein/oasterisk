<?php

namespace config;

class INI implements \Iterator {
  /**
   * Внутреннее хранилище имени файла
   *
   * @var string
   */
  private $_filename;
  /**
   * Внутреннее хранилище массива загруженных секций
   *
   * @var array
   */
  private $_sections = array();

  /**
   * Конструктор
   *
   * @param string $filename Имя загружаемого файла
   */
  public function __construct(string $filename) {
    $this->_filename = $filename;
    $this->load();
  }

  /**
   * Деструктор
   */
  public function __destruct() {
    foreach($this->_sections as $value) {
      unset($value);
    }
  }

  /**
   * Присваивает значение существующей секции или создаёт новую
   *
   * @param string $sectionName Имя секции
   * @param mixed $value Экземпляр класса INISection или ассоциативный массив
   */
  public function __set(string $sectionName, $value) {
    //Если передана секция, то клонирует её.
    if($value instanceof INISection) {    
      $this->_sections[$sectionName] = clone $value;
    } else {
      //Если передан массив, то пересоздаёт секцию с элементами массива.
      if(is_array($value)) {
        //Если секция существует, то очищает её
        if(isset($this->_sections[$sectionName])) {
          unset($this->_sections[$sectionName]);
        }
        //Если секция не существует или была удалена, то создаёт её
        if(!isset($this->_sections[$sectionName])) {
          $this->_sections[$sectionName] = new INISection($sectionName);
        }
        foreach($value as $fieldKey => $fieldValue) {
          $this->_sections[$sectionName]->$fieldKey = $fieldValue;
        }
      }
    }
  }

  /**
   * Возвращает значение существующей секции или создаёт новую
   *
   * @param string $sectionName Имя секции
   * @return \INISection Возвращает экземпляр класса INISection
   */
  public function __get(string $sectionName) {
    if(isset($this->_sections[$sectionName])) {
      return $this->_sections[$sectionName];
    } else {
      return $this->_sections[$sectionName] = new INISection($sectionName);
    }
  }

  /**
   * Определяет, была ли установлена секция значением, отличным от NULL
   *
   * @param string $section Имя секции
   * @return boolean Возвращает true, если секция определена
   */
  public function __isset(string $section) {
    return isset($this->_sections[$section]);
  }

  /**
   * Удаляет указанную секцию
   *
   * @param string $section Имя секции
   */
  public function __unset(string $section) {
    unset($this->_sections[$section]);
  }

  private function _sortSections(INISection $sectiona, INISection $sectionb) {
    if($sectiona->isTemplate()&&$sectionb->isTemplate()) {
      if(in_array($sectionb, $sectiona->getAllTemplates())) {
        return 1;
      } elseif(in_array($sectiona, $sectionb->getAllTemplates())) {
        return -1;
      } else {
        return strnatcasecmp($sectiona->getName(), $sectionb->getName());
      }
    } elseif(!$sectiona->isTemplate()&&!$sectionb->isTemplate()) {
      return strnatcasecmp($sectiona->getName(), $sectionb->getName());
    } else {
      if($sectiona->isTemplate()) {
        return -1;
      } else {
        return 1;
      }
    }
  }

  /**
   * Преобразует экземпляр класса в строку
   *
   * @return string Возвращает список секций в виде строки
   */
  public function __toString() {
    $ini = '';
    uasort($this->_sections, array($this, '_sortSections'));
    foreach($this as $section) {
      // no point in writing empty sections
      if($section->isEmpty()) {
        continue;
      }
      $ini .= $section;
    }
    return $ini;
  }

  
  //методы итератора
  /**
   *  Перематывает итератор на первый элемент массива секций
   *
   * @return void 
   */
  public function rewind() {
    reset($this->_sections);
  }

  /**
   * Возвращает текущий элемент массива секций
   *
   * @return INISection
   */
  public function current() {
    return current($this->_sections);
  }

  /**
   * Возвращает ключ текущего элемента массива секций
   *
   * @return int|string|null Возвращает ключ текущего элемента массива секций или же NULL при неудаче. 
   */
  public function key() {
    return key($this->_sections);
  }

  /**
   * Передвигает текущую позицию к следующему элементу массива секций
   *
   * @return void
   */
  public function next() {
    next($this->_sections);
  }

  /**
   * Проверяет корректность текущей позиции массива секций
   *
   * @return bool Возвращает TRUE в случае успешного завершения или FALSE в случае возникновения ошибки
   */
  public function valid() {
    return (key($this->_sections) !== null);
  }

  /**
   * Читает файл конфигурации. Заполняет массив секций
   *
   * @throws \Exception Выдаёт исключение, если файл не существует или нечитабельный.
   * @return void
   */
  public function load() {
    if(!file_exists($this->_filename) || !is_readable($this->_filename)) {
      throw new \Exception("The file ".$this->_filename." doesn't exist or is not readable");
    }

    $ini = file_get_contents($this->_filename);

    if($ini === false) {
      throw new \Exception("Impossible to read the file ".$this->_filename);
    }

    $start_pos = -1;
    $end_pos = 0;

//    //* TODO: Remove or comment
    //    $currentSection = 'globals';
    //    $this->_sections[$currentSection] = new INISection($currentSection);

    $lastComment = '';
    $currentSection = '';

    $iniLength = strlen($ini);

    while ($end_pos < $iniLength) {
      if($ini[$end_pos] == "\n") {
        $lastComment .= "\n";
      } else {
        switch($ini[$end_pos]) {
          case ';':{
            // Comments
            $end_pos++;
            $start_pos = $end_pos;
            while (($end_pos < $iniLength) && ($ini[$end_pos] != "\n")) {
              $end_pos++;
            }
            $lastComment .= substr($ini, $start_pos, $end_pos - $start_pos)."\n";
          } break;
          case ' ':
          case "\t":
            break;
          case '[':{
            // Sections
            $end_pos++;
            $start_pos = $end_pos;
            while (($end_pos < $iniLength) && ($ini[$end_pos] != "]")) {
              if(!($ini[$end_pos] !== "\n")) { //some lowlevel optimization
                throw new \Exception("Нет закрывающей скобки в позиции $end_pos");
              }
              $end_pos++;
            }
            $closeBracket = $end_pos;
            $currentSection = substr($ini, $start_pos, $closeBracket - $start_pos);
            $end_pos++;
            if(!isset($this->_sections[$currentSection])) {
              $this->_sections[$currentSection] = new INISection($currentSection);
              $this->_sections[$currentSection]->setDescription($lastComment);
            } else {
              $this->_sections[$currentSection]->setDescription($this->_sections[$currentSection]->getDescription($lastComment)."\n".$lastComment);
            }
            $lastComment = '';

            if(($end_pos < $iniLength) && ($ini[$end_pos] == "(")) {
              $end_pos++;
              $start_pos = $end_pos;
              $templateList = array();
              while (($end_pos < $iniLength) && ($ini[$end_pos] != ")")) {
                switch($ini[$end_pos]) {
                  case ",":{
                    $templateName = substr($ini, $start_pos, $end_pos - $start_pos);
                    if($templateName != '') {
                      if($templateName == '!') {
                        $this->_sections[$currentSection]->setTemplate();
                      } else {
                        $templateList[] = $templateName;
                      }
                    }
                    $start_pos = $end_pos + 1;
                  } break;
                  case "\n":{
                    throw new \Exception("Нет закрывающей скобки в шаблонах $currentSection");
                  } break;
                }
                $end_pos++;
              }
              $templateName = substr($ini, $start_pos, $end_pos - $start_pos);
              if($templateName != '') {
                if($templateName == '!') {
                  $this->_sections[$currentSection]->setTemplate(true);
                } else {
                  $templateList[] = $templateName;
                }
              }
              foreach($templateList as $template) {
                if(isset($this->_sections[$template])) {
                  $this->_sections[$currentSection]->addTemplate($this->_sections[$template]);
                }
              }
              unset($templateList);
            }

            $isComment = false;
            while (($end_pos < $iniLength) && ($ini[$end_pos] != "\n")) {
              if($ini[$end_pos] === ";") {
                $start_pos = $end_pos + 1;
                $isComment = true;
              }

              $end_pos++;
            }
            if($isComment) {
              $lastComment = substr($ini, $start_pos, $end_pos - $start_pos);
              if($lastComment !== '') {
                $this->_sections[$currentSection]->setComment($lastComment);
                $lastComment = '';
              }
            }
            break;
          }
          default:{
              $start_pos = $end_pos;
              $key = '';
              $value = '';
              $currentComment = '';
              $end_val = -1;
              while (($end_pos < $iniLength) && ($ini[$end_pos] != "\n")) {
                switch($ini[$end_pos]) {
                  case '=':
                    if($key === '') {
                    $key = trim(substr($ini, $start_pos, $end_pos - $start_pos));
                    if(($end_pos + 1 < $iniLength) && ($ini[$end_pos + 1] == '>')) {
                      $isExt = true;
                      $end_pos++;
                    } else {
                      $isExt = false;
                    }
                    $start_pos = $end_pos + 1;
                  }
                    break;
                  case ';':
                    $end_val = $end_pos;
                    $end_pos++;
                    $comment_pos = $end_pos;
                    while (($end_pos < $iniLength) && ($ini[$end_pos] != "\n")) {
                    $end_pos++;
                  }
                    $currentComment = trim(substr($ini, $comment_pos, $end_pos - $comment_pos));
                    $end_pos--;
                    break;
                  case ' ':
                  case "\t":
                    break;
                }
                $end_pos++;
              }
              if($key !== '') {
                if($currentComment == '') {
                  $end_val = $end_pos;
                }
                $value = trim(substr($ini, $start_pos, $end_val - $start_pos));
                $this->_sections[$currentSection]->addField($key, $value);
                if($currentComment) {
                  $this->_sections[$currentSection]->setLastComment($currentComment);
                }

                if($lastComment) {
                  $this->_sections[$currentSection]->setLastDescription($lastComment);
                }

                $this->_sections[$currentSection]->setLastExtended($isExt);
                $lastComment = '';
                $currentComment = '';
              }
            }
        }
      }
      $end_pos++;
    }
  }

  /**
   * Сохраняет конфигурацию
   *
   * @return bool Возвращает true в случае успеха операции
   */
  public function save() {
    global $_CACHE;
    $_CACHE->delete('INIFILES');
    return $this->saveAs($this->_filename);
  }

  /**
   * Сохраняет конфигурацию в указанный файл
   *
   * @param string $filename Имя файла для записи
   * @throws \Exception Возвращает исключение, если запись в файл невозможна
   * @return bool Возвращает true в случае успеха операции
   */
  public function saveAs(string $filename) {
    if(file_put_contents($filename, (string) $this) === false) {
      throw new \Exception("Impossible to write to file ".$filename);
    }
    return true;
  }

  /**
   * Осуществляет нормализацию секций INI-файла согласно шаблону и назначает значения по умолчанию
   *
   * @param mixed $json Массив или json строка
   * @return bool Результат нормализации значений
   */
  public function normalize($json) {
    $result = new \stdClass();
    if(is_array($json)) $json = json_encode($json);
    if(is_string($json)) $json = json_decode($json);
    foreach($json as $sectionName => $section) {
      $this->$sectionName->normalize($section);
    }
    return $result;
  }

}
