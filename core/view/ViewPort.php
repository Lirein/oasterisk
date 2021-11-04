<?php

namespace view;

/**
 * @ingroup coreapi
 * Класс визуализации панели управления.
 * Предназначен для генерации фронтенд части панели настроек, управления, журналов детализации.
 * Класс содержит два обязательных метода: scripts и render для вставки JS сценария и отрисовки
 * произвольной части кода страницы.
 */
abstract class ViewPort extends \Module implements IViewPort {

  /**
   * Должен вернуть путь к DefaultAPI URI для запросов к REST интерфейсу вида /rest/class/interface
   *
   * @return string
   */
  // abstract public function getAPI();
  public static function getAPI() {
    return '';
  }

  abstract public static function getViewLocation();

  public static function getLocation() {
    return 'view/'.static::getViewLocation();
  }
   
}

?>