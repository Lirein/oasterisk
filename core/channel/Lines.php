<?php

namespace channel;

/**
 * Интерфейс реализующий коллекцию линий связи
 */
class Lines extends \module\MorphingCollection {

  public function __construct() {
    parent::__construct();
  }

  /**
   * Функция должна возвращать натименование типа канального драйвера для отображения в списках
   *
   * @return string
   */
  static function getTypeTitle() {
    return null;
  }

  /**
   * Осуществляет поиск субьекта с указанным идентификатором
   *
   * @param string $id Идентификатор субьекта
   * @return Line
   */
  public static function find(string $id) {
    list($linetype, $lineid) = explode('/', $id, 2);
    if(empty($lineid)) return null;
    $linetype = strtoupper($linetype);
    $class = get_called_class();
    $iterator = new $class();
    $result = null;
    $lastcollectionclass = null;
    $lasttitle = null;
    $iterator->rewind();
    while($key = $iterator->key()) {
      $collectionclass = $iterator->getCurrentClass();
      if($lastcollectionclass != $collectionclass) {
        $lasttitle = strtoupper($collectionclass::getTypeName());
        $lastcollectionclass = $collectionclass;
      }
      if(($lasttitle == $linetype) && ($key == $lineid)) {
        $result = $iterator->current();
        break;
      }
      $iterator->next();
    }
    return $result;
  }

}
?>