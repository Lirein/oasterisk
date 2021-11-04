<?php

namespace channel;
/**
 * Интерфейс реализующий коллекцию линий связи
 */
class Trunks extends Lines {

  public function __construct() {
    parent::__construct();
  }

  /**
   * Осуществляет поиск транка с указанным идентификатором
   *
   * @param string $id Идентификатор транка
   * @return \channel\Trunk Найденный транк или null
   */
  public static function find(string $id) {
    return parent::find($id);
  }

}
?>