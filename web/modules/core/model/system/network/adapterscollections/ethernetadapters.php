<?php

namespace core;

/**
 * Интерфейс реализующий субьект коллекции
 * Должен содержать набор приватных свойств и геттеры/сеттеры для их обработки
 * Метод save - сохраняет субьект
 * Метод delete вызывает метод delete класса коллекции
 */
class EthernetAdapters extends \core\NetworkAdapters {

  private $adapterlist = null;

  public function __construct() {
    \Module::__construct();
    $this->rewind();
  }

  public function count() {
    return count($this->adapterlist);
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->adapterlist = array();
    $dir = '/sys/class/net/';
    if($dh = opendir($dir)) {
      while(($file = readdir($dh)) !== false) {
        if(is_dir($dir. '/'.$file.'/device')&&(trim(file_get_contents($dir. '/'.$file.'/device/class')) == "0x020000")) {
          $this->adapterlist[]=$file;
        }
      }
      closedir($dh);
    }
    sort($this->adapterlist, SORT_STRING);
    reset($this->adapterlist);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $adapter = current($this->adapterlist);
    return new EthernetAdapter($adapter);
  }

  /**
   * Возвращает ключ текущего элемента массива полей
   *
   * @return int|string|null Возвращает ключ текущего элемента или же NULL при неудаче. 
   */
  public function key() {
    return current($this->adapterlist);
  }

  /**
   * Передвигает текущую позицию к следующему элементу массива полей
   *
   * @return void
   */
  public function next() {
    next($this->adapterlist);
  }

  /**
   * Проверяет корректность текущей позиции массива полей
   *
   * @return bool Возвращает TRUE в случае успешного завершения или FALSE в случае возникновения ошибки
   */
  public function valid() {
    return (key($this->adapterlist) !== null);
  }

  

}
?>