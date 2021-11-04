<?php

namespace core;

class CustomSounds extends \sound\Sounds {

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
    if($soundsdir = \sound\Sounds::getSoundsDir()) {
      foreach(\sound\Sounds::getLanguages() as $language) {
        if($language == 'other') {
          $this->soundlist = array_merge($this->soundlist, array_keys(\sound\Sounds::getDir($soundsdir.'/custom', $language)));
        } else {
          $this->soundlist = array_merge($this->soundlist, array_keys(\sound\Sounds::getDir($soundsdir.'/'.$language.'/custom', $language)));
        }
      }
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
    return new CustomSound($sound);
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