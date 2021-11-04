<?php

namespace sip;

class SIPPeers extends \channel\Peers {
  
  /**
   * Открытый INI файл с доступными модулями MusicOnHold
   *
   * @var \config\INI $ini
   */
  private static $ini = null;

  private $peerlist = null;

  public static function getTypeName() {
    return 'sip';
  }

  public static function getTypeTitle() {
    return 'SIP';
  }

  private static function init() {
    if(!self::$ini) self::$ini = self::getINI('/etc/asterisk/sip.conf');
  }

  public function __construct() {
    self::init();
    $this->rewind();
  }
  
  public function count() {
    return count($this->peerlist);
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->peerlist = array();
    foreach(self::$ini as $k => $v) { 
      if(self::checkEffectivePriv('sip', $k, 'settings_reader')&&isset($v->type)&&!isset($v->remotesecret)&&(($v->type=='friend')||($v->type=='user'))) {
        $this->peerlist[] = $k;
      }
    }
    reset($this->peerlist);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $peer = current($this->peerlist);
    return new SIPPeer($peer);
  }

  /**
   * Возвращает ключ текущего элемента массива полей
   *
   * @return int|string|null Возвращает ключ текущего элемента или же NULL при неудаче. 
   */
  public function key() {
    return current($this->peerlist);
  }

  /**
   * Передвигает текущую позицию к следующему элементу массива полей
   *
   * @return void
   */
  public function next() {
    next($this->peerlist);
  }

  /**
   * Проверяет корректность текущей позиции массива полей
   *
   * @return bool Возвращает TRUE в случае успешного завершения или FALSE в случае возникновения ошибки
   */
  public function valid() {
    return (key($this->peerlist) !== null);
  }

}
?>