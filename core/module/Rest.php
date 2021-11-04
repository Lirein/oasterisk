<?php

namespace module;

/**
 * @ingroup coreapi
 * Класс визуализации панели управления.
 * Предназначен для генерации фронтенд части панели настроек, управления, журналов детализации.
 * Класс содержит два обязательных метода: scripts и render для вставки JS сценария и отрисовки
 * произвольной части кода страницы.
 */
abstract class Rest extends \Module implements IJSON {

  /**
   * Сценарии страницы. Функция должна отправить в стандартный вывод JavaScript сценарий конструктора
   * и логики обработки страницы. Модель обработки и демонстрационный код смотрите в @ref corejs_example "соотвествующем разделе справки". 
   *
   * @return string
   */
  abstract public static function getServiceLocation();

  public static function getLocation() {
    return 'rest/'.static::getServiceLocation();
  }

}

?>