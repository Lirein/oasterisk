<?php

namespace staff;

/**
 * Ккасс реализующий коллекцию адресных книг
 */
class Groups extends \module\Collection {

  private static $groups = null;

  private static $groupcache = array();

  private static function locateGroup(string $id) {
    if(empty($id)) return null;
    if(!isset(self::$groupcache[$id])) {
      if(empty($id)) return null;
      self::$groupcache[$id] = null;
      if(self::$groups == null) self::$groups = new Groups();
      $keys = self::$groups->keys();
      if(in_array($id, $keys)) self::$groupcache[$id] = new Group($id);
    }
    return self::$groupcache[$id];
  }

  /**
   * Конструктор без аргументов - инициализирует коллекцию объектов
   */
  public function __construct() {
    if(\dialplan\Dialplan::$dlpln == null) \dialplan\Dialplan::$dlpln = new \dialplan\Dialplan();
    parent::__construct();
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $contexts = \dialplan\Dialplan::$dlpln->keys();
    $this->items = array();
    foreach($contexts as $context) {
      if(strpos($context, 'staff-')===0) {
        $this->items[] = substr($context, 6);
      }
    }
    reset($this->items);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $staffgrouplist = current($this->items);
    return self::locateGroup($staffgrouplist);
  }

}
?>