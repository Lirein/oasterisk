<?php

namespace core;

/**
 * @ingroup coreapi
 * Класс реализующий "класс зоны безопасности". Содержит базовый набор методов работы с объектами зоны безопасности.
 * Класс зоны безопасности описывает отдельный вид объектов, которыми может оперировать такой класс.
 * Для каждого объекта, такого как абонент, транк, пользователь, очередь вызовов и т.д. должен быть
 * реализован свой "класс зоны безопаности".
 */
class SecZone extends Module {
  /**
   * Свойство описывающее уникальный идентификатор класса зоны безопасности, например dialplan_context
   * Свойство должно быть задано в обязательном порядке
   *
   * @var string $zoneclass
   */
  protected static $zoneclass = 'dummy';

  /**
   * Свойство описывающее отображаемое наименование класса зоны безопасности
   * Свойство должно быть задано в обязательном порядке
   *
   * @var string
   */
  protected static $zonename = 'Dummy';

  /**
   * Метод возвращающий идентификатор и наименование класса. Не рекомендуется к переопределению.<br>
   * <b>class</b> - Идентификатор класса зоны безопасности<br>
   * <b>name</b> - Наименование класса зоны безопасности<br>
   *
   * @return array
   */
  public final static function info() {
    return (object) array("class" => static::$zoneclass, "name" => static::$zonename);
  }

  /**
   * Возвращает список идентиифкаторов зон безопасности на базе текущего класса зоны безопасности
   *
   * @return array Массив идентификаторов зон безопасности
   */
  public final function getSeczones() {
    $result = array();
    $zone_cnt = self::getDB('seczone_persist/'.static::$zoneclass, 'count');
    for($i = 1; $i <= $zone_cnt; $i++) {
      $id = self::getDB('seczone_persist/'.static::$zoneclass, 'zone_'.$i.'/id');
      $allzone_cnt = self::getDB('seczone_persist', 'count');
      for($j = 1; $j <= $allzone_cnt; $j++) {
        $zone_id = self::getDB('seczone_persist', 'seczone_'.$j.'/id');
        if($zone_id == $id) {
          $result[$zone_id] = self::getDB('seczone_persist', 'seczone_'.$j.'/title');
        }
      }
    }
    return $result;
  }

  /**
   * Возвращает идентификаторы объектов, включенных в указанную зону безопасности
   *
   * @param string $seczone Идентификатор зоны безопасности
   * @return array Массив идентификаторов объектов
   */
  public function getObjects(string $seczone) {
    $result = array();
    $zone_cnt = self::getDB('seczone_persist/'.static::$zoneclass, 'count');
    for($i = 1; $i <= $zone_cnt; $i++) {
      $id = self::getDB('seczone_persist/'.static::$zoneclass, 'zone_'.$i.'/id');
      if($id == $seczone) {
        $role_cnt = self::getDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'count');
        for($j = 1; $j <= $role_cnt; $j++) {
          $role_id = self::getDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'entry_'.$j.'/id');
          $title = $role_id;
          $result[$role_id] = $title;
        }
      }
    }
    return $result;
  }

  /**
   * Проверяет наличие объекта в зоне безопасности
   *
   * @param string $seczone Идентификатор зоны безопасности
   * @param string $object Идентификатор объекта
   * @return boolean Результат проверки наличия объекта
   */
  public final function hasObject(string $seczone, string $object) {
    $result = false;
    $zone_cnt = self::getDB('seczone_persist/'.static::$zoneclass, 'count');
    for($i = 1; $i <= $zone_cnt; $i++) {
      $id = self::getDB('seczone_persist/'.static::$zoneclass, 'zone_'.$i.'/id');
      if($id == $seczone) {
        $role_cnt = self::getDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'count');
        for($j = 1; $j <= $role_cnt; $j++) {
          $role_id = self::getDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'entry_'.$j.'/id');
          if($role_id == $object) {
            $result = true;
            break;
          }
        }
        break;
      }
    }
    return $result;
  }

  /**
   * Добавляет объект в зону безопасности
   *
   * @param string $seczone Идентификатор зоны безопасности
   * @param string $object Идентификатор объекта
   * @return boolean Результат добавления объекта
   */
  public final function addObject(string $seczone, string $object) {
    $result = false;
    $zone_cnt = self::getDB('seczone_persist/'.static::$zoneclass, 'count');
    if(empty($zone_cnt)) {
      $zone_cnt = 0;
    }
    for($i = 1; $i <= $zone_cnt; $i++) {
      $id = self::getDB('seczone_persist/'.static::$zoneclass, 'zone_'.$i.'/id');
      if($id == $seczone) {
        $role_cnt = self::getDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'count');
        for($j = 1; $j <= $role_cnt; $j++) {
          $role_id = self::getDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'entry_'.$j.'/id');
          if($role_id == $object) {
            $result = true;
            break;
          }
        }
        if($result) {
          $result = ';exists';
          break;
        }
        $role_cnt++;
        self::setDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'entry_'.$role_cnt.'/id', $object);
        self::setDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'count', $role_cnt);
        $result = true;
        break;
      }
    }
    if(!$result) {
      self::setDB('seczone_persist/'.static::$zoneclass, 'zone_'.($zone_cnt + 1).'/id', $seczone);
      self::setDB('seczone_persist/'.static::$zoneclass.'/zone_'.($zone_cnt + 1), 'count', 1);
      self::setDB('seczone_persist/'.static::$zoneclass, 'count', $zone_cnt + 1);
      $role_cnt = 1;
      self::setDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'entry_'.$role_cnt.'/id', $object);
      self::setDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'count', $role_cnt);
      $result = true;
    }
    return $result;
  }

  /**
   * Удаляет объект из зоны безопасности
   *
   * @param string $seczone Идентификатор зоны безопасности
   * @param string $object Идентификатор объекта
   * @return boolean Результат удаления объекта
   */
  public final function removeObject(string $seczone, string $object) {
    $zone_cnt = self::getDB('seczone_persist/'.static::$zoneclass, 'count');
    for($i = 1; $i <= $zone_cnt; $i++) {
      $id = self::getDB('seczone_persist/'.static::$zoneclass, 'zone_'.$i.'/id');
      if($id == $seczone) {
        $role_cnt = self::getDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'count');
        for($j = 1; $j <= $role_cnt; $j++) {
          $role_id = self::getDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'entry_'.$j.'/id');
          if($role_id == $object) {
            if(1 == $role_cnt) {
              return self::clearObjects($seczone);
            }

            self::deltreeDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i.'/entry_'.$j);
            for($k = $j + 1; $k <= $role_cnt; $k++) {
              self::setDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'entry_'.($k - 1).'/id', self::getDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'entry_'.$k.'/id'));
            }
            self::deltreeDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i.'/entry_'.$role_cnt);
            self::setDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i, 'count', $role_cnt - 1);
            return true;
          }
        }
      }
    }
    return false;
  }

  /**
   * Очищает зону безопасности от объектов
   *
   * @param string $seczone Идентификатор зоны безопасности
   * @return boolean Результат очистки зоны безопасности
   */
  public final function clearObjects(string $seczone) {
    $result = true;
    $zone_cnt = self::getDB('seczone_persist/'.static::$zoneclass, 'count');
    for($i = 1; $i <= $zone_cnt; $i++) {
      $id = self::getDB('seczone_persist/'.static::$zoneclass, 'zone_'.$i.'/id');
      if($id == $seczone) {
        self::deltreeDB('seczone_persist/'.static::$zoneclass.'/zone_'.$i);
        for($j = $i + 1; $j <= $zone_cnt; $j++) {
          self::setDB('seczone_persist/'.static::$zoneclass, 'zone_'.($j - 1).'/id', self::getDB('seczone_persist/'.static::$zoneclass, 'zone_'.$j.'/id'));
          $role_cnt = self::getDB('seczone_persist/'.static::$zoneclass.'/zone_'.$j, 'count');
          for($k = 1; $k <= $role_cnt; $k++) {
            self::setDB('seczone_persist/'.static::$zoneclass.'/zone_'.($j - 1), 'entry_'.$k.'/id', self::getDB('seczone_persist/'.static::$zoneclass.'/zone_'.$j, 'entry_'.$k.'/id'));
          }
          self::setDB('seczone_persist/'.static::$zoneclass.'/zone_'.($j - 1), 'count', $role_cnt);
        }
        self::deltreeDB('seczone_persist/'.static::$zoneclass.'/zone_'.$zone_cnt);
        self::setDB('seczone_persist/'.static::$zoneclass, 'count', $zone_cnt - 1);
        return true;
      }
    }
    return $result;
  }

  /**
   * Переименовывает идентификатор зоны безопасности
   *
   * @param string $seczone Текущий идентификатор зоны безопасности
   * @param string $newzone Новый идентификатор зоны безопасности
   * @return boolean Результат переименования зоны безопасности
   */
  public final function renameSeczone(string $seczone, string $newzone) {
    $result = false;
    $zone_cnt = self::getDB('seczone_persist/'.static::$zoneclass, 'count');
    for($i = 1; $i <= $zone_cnt; $i++) {
      $id = self::getDB('seczone_persist/'.static::$zoneclass, 'zone_'.$i.'/id');
      if($id == $seczone) {
        self::setDB('seczone_persist/'.static::$zoneclass, 'zone_'.$i.'/id', $newzone);
        break;
      }
    }
    return $result;
  }
}

?>