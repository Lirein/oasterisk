<?php

  function menucmp(&$a, &$b) {
    if($a->data->prio==$b->data->prio) {
      if(isset($a->parent)&&isset($b->parent)&&isset($a->level)&&isset($b->level)&&($a->level==$b->level)) {
        if($a->parent->prio==$b->parent->prio) {
          if($a->data->name==$b->data->name) {
            return 0;
          }
          return ($a->data->name<$b->data->name)?-1:1;
        }
        return ($a->parent->prio<$b->parent->prio)?-1:1;
      }
      if($a->data->name==$b->data->name) {
        return 0;
      }
      return ($a->data->name<$b->data->name)?-1:1;
    }
    return ($a->data->prio<$b->data->prio)?-1:1;
  }

  function getSiblingPath($location) {
    $mode = null;
    $maxlevel = 0;
    $modules = findModulesByClass('view\Menu');
    $tmpmenu = array('manage' => array(), 'settings' => array(), 'statistics' => array());
    if(!\Module::$user) \Module::initPermissions();
    foreach($modules as $module) {
      if(((strlen($location)===0)||(strpos($module->location, $location)===0))) {
        $subpath=explode('/', $module->location);
        array_pop($subpath);
        $classname = $module->class;
        if(!$mode) $mode=$classname::$user->getViewMode();
        $item = $classname::getMenu();
        if(!isset($item->mode)) $item->mode = 'basic';
        $islink = count($subpath)?is_subclass_of($classname, 'view\View'):false;
        $parentmodule = findModuleByPath(implode('/', $subpath));
        $parentclassname = $parentmodule?$parentmodule->class:null;
        if(count($subpath)>$maxlevel) $maxlevel = count($subpath);
        if($islink&&$classname::check()&&$classname::checkScope($module->location)) $tmpmenu[$subpath[0]][$module->location]=(object) array('data' => $item, 'islink' => $islink, 'level' => count($subpath), 'parent' => ($parentclassname?$parentclassname::getMenu():null));
      }
    }
    uasort($tmpmenu['settings'], 'menucmp');
    uasort($tmpmenu['manage'], 'menucmp');
    uasort($tmpmenu['statistics'], 'menucmp');
    $section = null;
    if(!empty($tmpmenu['manage'])) {
      $section = 'manage';
    } elseif(!empty($tmpmenu['settings'])) {
      $section = 'settings';
    } elseif(!empty($tmpmenu['statistics'])) {
      $section = 'statistics';
    }
    $newlocation='';
    if($section) {
      $level = 1;
      while(($newlocation=='')&&($level<=$maxlevel)) {
        reset($tmpmenu[$section]);
        $visible = false;
        while(!$visible) {
          $visible = true;
          $entry = current($tmpmenu[$section]);
          switch($entry->data->mode) {
            case 'advanced': {
              if($mode == 'basic') $visible = false;
            } break;
            case 'expert': {
              if($mode != 'expert') $visible = false;
            } break;
          }
          if($entry->level!=$level) $visible = false;
          if(!$visible&&(next($tmpmenu[$section])===false)) break;
        }
        if($visible) $newlocation = key($tmpmenu[$section]);
        $level++;
      }
    }
    return $newlocation;
  }

  function addMenu(&$menu, $link, $iscollection, $icon, $title, &$submenu=null, $active=false, $mode = 'basic') {
     $menu[]=(object) array('link' => $link, 'collection' => $iscollection, 'icon' => $icon, 'title' => $title, 'value' => &$submenu, 'active' => $active, 'mode' => $mode);
  }

  function getLeftMenu() {
    $webmodules = getModulesByClass('view\Menu');
    $tmpmenu = array();
    $maxpath = 0;
    foreach($webmodules as $module) {
      $moduleclass = get_class($module);
      $path = $moduleclass::getLocation();
      $item = $moduleclass::getMenu();
      if(!isset($item->mode)) $item->mode = 'basic';
      $mp = substr_count($path, '/');
      if($mp > $maxpath) $maxpath=$mp;
      if($item) $tmpmenu[$path]=(object) array('data' => $item, 'islink' => is_subclass_of($moduleclass, 'view\View'), 'iscollection' => is_subclass_of($moduleclass, 'view\Collection'), 'value' => array());
    }
    uasort($tmpmenu, 'menucmp');
    $menu = array();
    $submenu = &$menu;
    for($level=1;$level<=$maxpath; $level++) {
      foreach($tmpmenu as $location => $item) {
        if($level==substr_count($location, '/')) {
          unset($submenu);
          if($level>1) {
            $loc=explode('/',$location);
            array_pop($loc);
            $loc=implode('/',$loc);
            if(isset($tmpmenu[$loc])&&isset($tmpmenu[$loc]->value)) {
              $submenu = &$tmpmenu[$loc]->value;
            } else {
              $submenu = false;
            }
          } else {
            $submenu = &$menu;
          }
          if(is_array($submenu)) {
            if($moduleclass::checkScope($location)) addMenu($submenu, $item->islink?('/'.$location):false, $item->iscollection, isset($item->data->icon)?$item->data->icon:null, $item->data->name, $tmpmenu[$location]->value, false, $item->data->mode);
          }
        }
      }
    }
    return $menu;
  }

?>
