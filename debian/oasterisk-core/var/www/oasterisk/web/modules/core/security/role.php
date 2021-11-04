<?php

namespace core;

/**
 * Класс зоны безопасности для объектов профилей безопасности
 */
class GroupSecZone extends SecZone {

  protected static $zoneclass = 'security_role';
  protected static $zonename = 'Профили безопасности';

  /**
   * Перегруженная функция информации об объектах, с дополнительной расшифровной втроенных профилей
   *
   * @param string $seczone Идентификатор зоны безопасности
   * @return array Ассоциативный массив идентификаторов ролей безопасности и их текстового описания
   */
  public function getObjects(string $seczone) {
    $result = array();
    $zone_cnt=self::getDB('seczone_persist/'.static::$zoneclass, 'count');
    for($i=1; $i<=$zone_cnt; $i++) {
      $id = self::getDB('seczone_persist/'.static::$zoneclass, 'zone_'.$i.'/id');
      if($id==$seczone) {
        $role_cnt=self::getDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'count');
        for($j=1; $j<=$role_cnt; $j++) {
          $role_id = self::getDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'entry_'.$j.'/id');
          $role = self::getRole($role_id);
          $title = $role->title;
          switch($role_id) {
            case 'full_control': $title='Полный доступ'; break;
            case 'admin': $title='Администратор'; break;
            case 'technician': $title='Проектировщик'; break;
            case 'operator': $title='Оператор'; break;
            case 'agent': $title='Агент'; break;
            case 'manager': $title='Руководитель'; break;
          }
          $result[$role_id]=$title;
        }
      }
    }
    return $result;
  }

}

?>