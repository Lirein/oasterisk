<?php

namespace core;

/**
 * Класс зоны безопасности для панелей состояния
 */
class DashboardSecZone extends SecZone {

  protected static $zoneclass = 'dashboard_panel';
  protected static $zonename = 'Панель состояния';

  public function getObjects(string $seczone) {
    $result = parent::getObjects($seczone);
    $zoneInfo = \core\DashboardManage::getZoneInfo();
    $objects = $zoneInfo->getObjects();
    foreach($objects as $object) {
      if(isset($result[$object->id])) $result[$object->id] = $object->text;
    }
    return $result;
  }

}

?>