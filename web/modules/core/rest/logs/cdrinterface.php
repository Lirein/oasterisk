<?php

namespace core;

abstract class ICDRRestInterface extends \Module implements \module\IJSON {

  /**
   * Метод возвращает текстовое наименование панели настроек
   *
   * @return string
   */
  abstract public static function getTitle();

  /**
   * Метод получает набор настроек панели интерфейса
   *
   * @return \stdClass
   */
  abstract public function get();

  /**
   * Метод устанавливает набор настроек панели интерфейса
   *
   * @param \stdClass $request_data
   * @return bool
   */
  abstract public function set($request_data);

}

?>