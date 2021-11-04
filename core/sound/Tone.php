<?php

namespace sound;

class ToneCadence {
  public $mainfreq;
  public $subfreq;
  public $addmode;
  public $duration;
  public $once;

  public function __construct($tone = null) {
    $this->reset();
    if($tone) $this->parse($tone);
  }

  public function reset() {
    $this->mainfreq = 0;
    $this->subfreq = 0;
    $this->duration = 0;
    $this->addmode = true;
    $this->once = false;
  }

  private function parse(string $tone) {
    $this->reset();
    $tone = trim($tone);
    if(preg_match('/^(!{0,1})([0-9]+)(|(\+|\*)([0-9]+))(|\/([0-9]+))$/m', $tone, $match)) {
      if($match[1]=='!') $this->once = true;
      $this->mainfreq = (int)$match[2];
      if($match[3]) {
        $this->addmode = $match[4]=='+';
        $this->subfreq = (int)$match[5];
      }
      if($match[6]) {
        $this->duration = (int)$match[7];
      }
    }
  }

  public function __toString() {
    $result = '';
    if($this->once) $result .= '!';
    $result .= $this->mainfreq;
    if($this->subfreq) {
      $result .= ($this->addmode?'+':'*').$this->subfreq;
    }
    if($this->duration) $result .= '/'.$this->duration;
    return $result;
  }
}

class Tone extends \module\Subject {
  
  private $ini;

  /**
   * Конструктор без аргументов - инициализирует модель
   */
  public function __construct(string $id = null) {
    $this->ini = self::getINI('/etc/asterisk/indications.conf');
    parent::__construct($id);
    $this->data->languages = array();
    $hasentry = false;
    foreach($this->ini as $sectionname => $section) {
      if($sectionname != 'globals') {
        if(isset($section->$id)) {
          $this->data->languages[$sectionname] = $this->StringToCadence($section->$id);
          $hasentry = true;
        } else {
          $this->data->languages[$sectionname] = null;
        }
      }
    }
    if($hasentry) $this->old_id = $id;
    if(!$this->data->title) $this->data->title = $id;
    $this->id = $id;
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    parent::__destruct();
    unset($this->ini);
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save() {
    $sectionname = $this->id;
    if(!$sectionname) return false;
    if($this->old_id!==null) {
      if($this->id!=$this->old_id) {
        Tones::rename($this);
        $oldname = $this->old_id;
        foreach($this->ini as $sectionname => $section) {
          if($sectionname != 'globals') {
            if(isset($section->$oldname)) {
              unset($section->$oldname);
            }
          }
        }
      } else {
        Tones::change($this);
      }
    } else { //Инициализируем секцию
      Tones::add($this);
    }
    foreach($this->data->languages as $language => $cadences) {
      $this->ini->$language = $this->CadenceToString($cadences);
    }
    $this->old_id = $this->id;
    return $this->ini->save();
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    Tones::remove($this);
    $oldname = $this->old_id;
    foreach($this->ini as $sectionname => $section) {
      if($sectionname != 'globals') {
        if(isset($section->$oldname)) {
          unset($section->$oldname);
        }
      }
    }
    return $this->ini->save();
  }

  /**
   * Метод преобразеут массив тонаьноестй в строку для Digium Asterisk™
   * 
   * @param ToneCadence[] $cadences
   * @return string
   */
  public function CadenceToString(array $cadences) {
    $tones = array();
    foreach($cadences as $tone) {
      $tones[] = (string)$tone;
    }
    return implode(',', $tones);
  }

  public function StringToCadence(string $cadences) {
    $tones = array();
    foreach(explode(',', $cadences) as $tone) {
      $tones = new ToneCadence($tone);
    }
    return $tones;
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    return $this->ami->send_request('Command', array('Command' => 'module reload res_indications'))!==false;
  }

}
?>