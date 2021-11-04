<?php

namespace core;

class SecZones extends Module {

  public static function _getSeczones() {
    $result = array();
    $zone_cnt=self::getDB('seczone_persist', 'count');
    for($i=1; $i<=$zone_cnt; $i++) {
      $result[self::getDB('seczone_persist', 'zone_'.$i.'/id')]=self::getDB('seczone_persist', 'zone_'.$i.'/title');
    }
    return $result;
  }

  public function getSeczones() {
    return self::_getSeczones();
  }

  public static function _getSeczone($seczone) {
    $result = null;
    if($seczone===null) return $result;
    $zone_cnt=self::getDB('seczone_persist', 'count');
    for($i=1; $i<=$zone_cnt; $i++) {
      $zone_id=self::getDB('seczone_persist', 'zone_'.$i.'/id');
      if($zone_id==$seczone) {
        $result = new \stdClass();
        $result->id=$zone_id;
        $result->title=self::getDB('seczone_persist', 'zone_'.$i.'/title');
        $result->privs=array();
        $privs_count=self::getDB('seczone_persist', 'zone_'.$i.'/privs/count');
        for($j=1;$j<=$privs_count; $j++) {
          $result->privs[]=self::getDB('seczone_persist', 'zone_'.$i.'/privs/priv_'.$j);
        }
        break;
      }
    }
    return $result;
  }

  public function getSeczone($seczone) {
    return self::_getSeczone($seczone);
  }

  public function setSeczone($seczone, $newid, $title, $privs) {
    $result = 1;
    if(!empty($seczone)&&($seczone!=$newid)) { //rename
      $seczone_cnt=self::getDB('seczone_persist', 'count');
      for($i=1; $i<=$seczone_cnt; $i++) {
        $seczone_id=self::getDB('seczone_persist', 'zone_'.$i.'/id');
        if($seczone_id==$newid) {
          return 2;
        }
      }
      for($i=1; $i<=$seczone_cnt; $i++) {
        $seczone_id=self::getDB('seczone_persist', 'zone_'.$i.'/id');
        if($seczone_id==$seczone) {
          self::setDB('seczone_persist', 'zone_'.$i.'/id', $newid);
          break;
        }
      }
      $result=self::_renameSeczoneObjects($seczone, $newid);
    }
    if((!isset($seczone))||$seczone=='') { //new
      $seczone_cnt=self::getDB('seczone_persist', 'count');
      for($i=1; $i<=$seczone_cnt; $i++) {
        $seczone_id=self::getDB('seczone_persist', 'zone_'.$i.'/id');
        if($seczone_id==$newid) {
          return 2;
        }
      }
      $seczone_cnt++;
      self::setDB('seczone_persist', 'zone_'.$seczone_cnt.'/id',$newid);
      self::setDB('seczone_persist', 'count', $seczone_cnt);
    }
    $seczone_cnt=self::getDB('seczone_persist', 'count');
    for($i=1; $i<=$seczone_cnt; $i++) {
      $seczone_id=self::getDB('seczone_persist', 'zone_'.$i.'/id');
      if($seczone_id==$newid) {
        self::setDB('seczone_persist', 'zone_'.$i.'/title', $title);
        self::deltreeDB('seczone_persist/seczone_'.$i.'/privs');
        self::setDB('seczone_persist', 'zone_'.$i.'/privs/count', count($privs));
        foreach($privs as $k => $priv) {
          self::setDB('seczone_persist', 'zone_'.$i.'/privs/priv_'.($k+1), $priv);
        }
        $result= 0;//$newid;
      }
    }
    return $result;
  }

  public function removeSeczone($seczone) {
    $seczone_cnt=self::getDB('seczone_persist', 'count');
    for($i=1; $i<=$seczone_cnt; $i++) {
      $seczone_id=self::getDB('seczone_persist', 'zone_'.$i.'/id');
      if($seczone_id==$seczone) {
        self::deltreeDB('seczone_persist/seczone_'.$i);
        for($j=$i+1; $j<=$seczone_cnt; $j++) {
          self::setDB('seczone_persist', 'zone_'.($j-1).'/id', self::getDB('seczone_persist', 'zone_'.$j.'/id'));
          self::setDB('seczone_persist', 'zone_'.($j-1).'/title', self::getDB('seczone_persist', 'zone_'.$j.'/title'));
          $privs_count=self::getDB('seczone_persist', 'zone_'.$j.'/privs/count');
          self::setDB('seczone_persist', 'zone_'.($j-1).'/privs/count', $privs_count);
          for($k=1; $k<=$privs_count; $k++) {
            self::setDB('seczone_persist', 'zone_'.($j-1).'/privs/priv_'.$k, self::getDB('seczone_persist', 'zone_'.$j.'/privs/priv_'.$k));
          }
        }
        self::deltreeDB('seczone_persist/seczone_'.$seczone_cnt);
        self::setDB('seczone_persist', 'count', $seczone_cnt-1);
        return self::_clearSeczoneObjects($seczone, null)?0:1;
      }
    }
    return 2;
  }

  public function getSeczoneClasses() {
    $result = array();
    $zonemodules=getModulesByClass('core\SecZone');
    foreach($zonemodules as $module) {
      $result[]=$module->info();
    }
    return $result;
  }

  public function getSeczonesByClass($class) {
    $zonemodules=getModulesByClass('core\SecZone');
    foreach($zonemodules as $module) {
      $info=$module->info();
      if($info->class==$class) {
        return $module->getSeczones();
      }
    }
    return false;
  }

  public function getSeczoneObjects($seczone, $class) {
    $zonemodules=getModulesByClass('core\SecZone');
    foreach($zonemodules as $module) {
      $info=$module->info();
      if($info->class==$class) {
        return $module->getObjects($seczone);
      }
    }
    return false;
  }

  public function hasSeczoneObject($seczone, $class, $object) {
    $zonemodules=getModulesByClass('core\SecZone');
    foreach($zonemodules as $module) {
      $info=$module->info();
      if($info->class==$class) {
        return $module->hasObject($seczone, $object);
      }
    }
    return false;
  }

  public static function _getObjectCurrentSeczones($class, $object) {
    if(self::checkZones()) {
      $zones = self::_getZones();
    } else {
      $zones = self::_getCurrentSeczones();
    }
    $result = array();
    $zonemodules=getModulesByClass('core\SecZone');
    foreach($zonemodules as $module) {
      $info=$module->info();
      if($info->class==$class) {
        foreach($zones as $zone) {
          if($module->hasObject($zone, $object)) {
            $result[]=$zone;
          }
        }
      }
    }
    return $result;
  }

  public static function _getObjectSeczones($class, $object) {
    $zones = self::_getSeczones();
    $result = array();
    $zonemodules=getModulesByClass('core\SecZone');
    foreach($zonemodules as $module) {
      $info=$module->info();
      if($info->class==$class) {
        foreach($zones as $zone => $title) {
          if($module->hasObject($zone, $object)) {
            $result[]=$zone;
          }
        }
      }
    }
    return $result;
  }

  public function getObjectCurrentSeczones($class, $object) {
    return self::_getObjectCurrentSeczones($class, $object);
  }

  public function getObjectSeczones($class, $object) {
    return self::_getObjectSeczones($class, $object);
  }

  public function addSeczoneObject($seczone, $class, $object) {
    $zonemodules=getModulesByClass('core\SecZone');
    foreach($zonemodules as $module) {
      $info=$module->info();
      if($info->class==$class) {
        return $module->addObject($seczone, $object);
      }
    }
    return false;
  }

  public function removeSeczoneObject($seczone, $class, $object) {
    $zonemodules=getModulesByClass('core\SecZone');
    foreach($zonemodules as $module) {
      $info=$module->info();
      if($info->class==$class) {
        return $module->removeObject($seczone, $object);
      }
    }
    return false;
  }

  public static function _clearSeczoneObjects($seczone, $class) {
    $result = true;
    $zonemodules=getModulesByClass('core\SecZone');
    foreach($zonemodules as $module) {
      $info=$module->info();
      if(($class===null)||($info->class==$class)) {
        $result&=$module->clearObjects($seczone);
      }
    }
    return $result;
  }

  public static function _renameSeczoneObjects($seczone, $newzone) {
    $result = true;
    $zonemodules=getModulesByClass('core\SecZone');
    foreach($zonemodules as $module) {
//      $info=$module->info();
      $result&=$module->renameSeczone($seczone, $newzone);
    }
    return $result;
  }

  public function clearSeczoneObjects($seczone, $class) {
    return self::_clearSeczoneObjects($seczone, $class);
  }

  public function renameSeczoneObjects($seczone, $newzone) {
    return self::_renameSeczoneObjects($seczone, $newzone);
  }

  public static function _getCurrentSeczones() {
    $result = array();
    if(count(self::_getZones())==0) $result=self::_getObjectSeczones('security_role',self::_getRole());
      else $result=self::_getZones();
    self::_setZones($result);
    return $result;
  }

  public function getCurrentSeczones() {
    return self::_getCurrentSeczones();
  }

  public function getCurrentPrivs() {
    $privs = self::_getPrivs();
    $result = array();
    $zones = self::_getCurrentSeczones();
    if(count($zones)==0) {
      $result=array_fill_keys($privs, false);
    } else {
      foreach($zones as $zone) {
        $zoneinfo=self::_getSeczone($zone);
        if(count($zoneinfo->privs)==0) $zoneinfo->privs=$privs;
        $result=array_merge(array_fill_keys($zoneinfo->privs, $zone),$result);
      }
    }
    return $result;
  }

  public function getEffectivePrivs($class, $object) {
    $result = array();
    $zones = self::_getObjectCurrentSeczones($class, $object);
    $privs = self::_getPrivs();
    if(count($zones)==0) {
      $userzones=self::_getCurrentSeczones();
      if(count($userzones)>0) {
        $result=array_fill_keys($privs, false);
      } else {
        $result=array_fill_keys($privs, true);
      }
    } else {
      foreach($zones as $zone) {
        $zoneinfo=self::_getSeczone($zone);
        if(count($zoneinfo->privs)==0) $zoneinfo->privs=$privs;
        $result=array_merge(array_fill_keys($zoneinfo->privs, $zone),$result);
      }
    }
    return $result;
  }
}

?>