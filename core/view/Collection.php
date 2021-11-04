<?php

namespace view;

/**
 * @ingroup coreapi
 * Класс визуализации панели управления.
 * Предназначен для генерации фронтенд части панели настроек, управлениня реализующих множество сущностей (субъектов).
 */
abstract class Collection extends View {

  public function scripts() {
    $viewlocation = static::getViewLocation();
    if(empty($viewlocation)) $viewlocation = static::getLocation();
    if(!empty($viewlocation)) printf("await require('collection', rootcontent, {modalview: '%s'});\n", $viewlocation);
  }   

}

?>