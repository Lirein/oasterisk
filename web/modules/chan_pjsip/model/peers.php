<?php

namespace pjsip;

class Peers extends \channel\Peers {
  
  /**
   * Открытый INI файл с доступными модулями MusicOnHold
   *
   * @var \pjsip\Endpoints $ini
   */
  private $endpoints = null;

  private $items;

  public static function getTypeName() {
    return 'pjsip';
  }

  public static function getTypeTitle() {
    return 'SIP 2.0';
  }

  public function __construct() {
    \Module::__construct();
    $this->endpoints = new Endpoints();
    $this->rewind();
  }
  
  public function count() {
    return count($this->items);
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->items = $this->cache->get('pjsip_peers');
    if($this->items==false) {
      $this->items = array();
      foreach($this->endpoints->keys() as $k) { 
        if(!((strstr($k, 'prov_')===0)||(strstr($k, 'gw_')===0))&&self::checkEffectivePriv('pjsip', $k, 'settings_reader')) {
          $this->items[] = $k;
        }
      }
      $this->cache->set('pjsip_peers', $this->items, 30);
    }
    reset($this->items);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $entry = current($this->items);
    return new Peer($entry);
  }

  /**
   * Возвращает ключ текущего элемента массива полей
   *
   * @return int|string|null Возвращает ключ текущего элемента или же NULL при неудаче. 
   */
  public function key() {
    return current($this->items);
  }

  /**
   * Передвигает текущую позицию к следующему элементу массива полей
   *
   * @return void
   */
  public function next() {
    next($this->items);
  }

  /**
   * Проверяет корректность текущей позиции массива полей
   *
   * @return bool Возвращает TRUE в случае успешного завершения или FALSE в случае возникновения ошибки
   */
  public function valid() {
    return (key($this->items) !== null);
  }

}
?>