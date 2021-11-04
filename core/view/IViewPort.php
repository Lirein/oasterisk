<?php

namespace view;

interface IViewPort {
/**
 * Метод возвращающий тело класса-вьюпорта. Обязателен к реализации метод constructor(parent),
 * где parent используется для создания дочерних виджетов, методы возвращается в стандартный вывод
 * @return void
 */
  public function implementation();

  /**
   * Должен вернуть путь к DefaultAPI URI для запросов к REST интерфейсу вида /rest/class/interface
   *
   * @return string
   */
  // abstract public function getAPI();
  public static function getAPI();

}
?>