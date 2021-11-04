<?php

namespace view;

/**
 * @ingroup coreapi
 * Класс визуализации панели управления.
 * Предназначен для генерации фронтенд части панели настроек, управления, журналов детализации.
 */
abstract class View extends Menu implements IViewPort {

  /**
   * Должен вернуть путь к DefaultAPI URI для запросов к REST интерфейсу вида /rest/class/interface
   *
   * @return string
   */
  // abstract public function getAPI();
  public static function getAPI() {
    return '';
  }

  /**
   * Должен вернуть путь к ViewPort 
   *
   * @return void
   */
  public static function getViewLocation() {
    return null;
  }

  public function scripts() {
    $viewlocation = static::getViewLocation();
    if(empty($viewlocation)) $viewlocation = static::getLocation();
    if(!empty($viewlocation)) printf("await require('%s', rootcontent);\n", $viewlocation);
  }

}

?>