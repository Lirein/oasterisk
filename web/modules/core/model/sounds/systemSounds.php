<?php

namespace core;

class SystemSounds extends \sound\Sounds {
  
  private $soundlist = null;

  public function __construct() {
    $this->rewind();
  }

  public function count() {
    return count($this->soundlist);
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->soundlist = array();
    static $soundsdir = null;
    foreach(\sound\Sounds::getLanguages() as $language) {
      if($soundsdir = \sound\Sounds::getSoundsDir()) {
        $this->soundlist = array_merge($this->soundlist, array_keys(\sound\Sounds::getDir($soundsdir.'/'.$language, $language)));
      }
    }
    foreach(array_keys($this->soundlist) as $i) {
      if((strpos($this->soundlist[$i], 'custom')===0) || (strpos($this->soundlist[$i], 'recordings')===0)) unset($this->soundlist[$i]);
    }
    sort($this->soundlist, SORT_STRING);
    $this->soundlist = array_unique($this->soundlist, SORT_STRING);
    reset($this->soundlist);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $sound = current($this->soundlist);
    return new SystemSound($sound);
  }

  /**
   * Возвращает ключ текущего элемента массива полей
   *
   * @return int|string|null Возвращает ключ текущего элемента или же NULL при неудаче. 
   */
  public function key() {
    return current($this->soundlist);
  }

  /**
   * Передвигает текущую позицию к следующему элементу массива полей
   *
   * @return void
   */
  public function next() {
    return next($this->soundlist);
  }

  /**
   * Проверяет корректность текущей позиции массива полей
   *
   * @return bool Возвращает TRUE в случае успешного завершения или FALSE в случае возникновения ошибки
   */
  public function valid() {
    return (key($this->soundlist) !== null);
  }

}
?>