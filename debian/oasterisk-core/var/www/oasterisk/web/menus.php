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
    $modules = findModulesByClass('core\MenuModule');
    $tmpmenu = array('manage' => array(), 'settings' => array(), 'cdr' => array(), 'statistics' => array(), 'help' => array());
    foreach($modules as $module) {
      if(((strlen($location)===0)||(strpos($module->location, $location)===0))) {
        $subpath=explode('/', $module->location);
        array_pop($subpath);
        $classname = $module->class;
        $islink = count($subpath)?is_subclass_of($classname, 'core\ViewModule'):false;
        $parentmodule = findModuleByPath(implode('/', $subpath));
        $parentclassname = $parentmodule?$parentmodule->class:null;
        if($islink&&$classname::check()&&$classname::checkScope($module->location)) $tmpmenu[$subpath[0]][$module->location]=(object) array('data' => $classname::getMenu(), 'islink' => $islink, 'level' => count($subpath), 'parent' => ($parentclassname?$parentclassname::getMenu():null));
      }
    }
    uasort($tmpmenu['manage'], 'menucmp');
    uasort($tmpmenu['settings'], 'menucmp');
    uasort($tmpmenu['cdr'], 'menucmp');
    uasort($tmpmenu['statistics'], 'menucmp');
    uasort($tmpmenu['help'], 'menucmp');
    $section = null;
    if(!empty($tmpmenu['manage'])) {
      $section = 'manage';
    } elseif(!empty($tmpmenu['settings'])) {
      $section = 'settings';
    } elseif(!empty($tmpmenu['cdr'])) {
      $section = 'cdr';
    } elseif(!empty($tmpmenu['statistics'])) {
      $section = 'statistics';
    } elseif(!empty($tmpmenu['help'])) {
      $section = 'help';
    }
    $newlocation='';
    if($section) {
      reset($tmpmenu[$section]);
      $newlocation = key($tmpmenu[$section]);
    }
    return $newlocation;
  }

  function addMenu(&$menu, $link, $icon, $title, &$submenu=null, $active=false) {
     $menu[]=(object) array('link' => $link, 'icon' => $icon, 'title' => $title, 'submenu' => &$submenu, 'active' => $active);
  }

  function setActiveMenu(&$menu, $link) {
     $result = false;
     foreach($menu as $value) {
       if($value->link==$link) {
         $value->active=true;
         $result = true;
         break;
       } else
         $value->active=false;
       if(isset($value->submenu)&&count($value->submenu)>0) {
         if(setActiveMenu($value->submenu, $link)) {
           $value->active=true;
           $result = true;
           break;
         }
       }
     }
     return $result;
  }

  function genMainMenu(&$menu) {
     $result = '';
     foreach($menu as $entry) {
        $result.=sprintf("<li class='nav-item%s'>\n <a href='%s' class='nav-link d-flex'><i class='%s'></i><span class='d-none d-sm-inline-block'>%s</span></a>\n</li>\n", $entry->active?' active':'', $entry->link, $entry->icon, $entry->title);
     }
     return $result;
  }

  function genLeftMenu(&$menu) {
     $result = '';
     foreach($menu as $entry) {
       if($entry->link||(count($entry->submenu)>0)) {
         $entryid=uniqid('lm-');
         $result.=sprintf("<li class='%s'><a href='%s'><i class='%s'></i><span> %s</span></a>\n", $entry->active?' active':'', $entry->link?($entry->link):("#".$entryid."' data-toggle='collapse"), $entry->icon?$entry->icon:'oi oi-list', $entry->title);
         if(count($entry->submenu)>0) {
           $result.='<ul id="'.$entryid.'" class="list-unstyled collapse" data-parent="#lmenuaccordion">';
           $result.=genLeftMenu($entry->submenu);
           $result.='</ul>';
         }
         $result.='</li>';
       }
     }
     return $result;
  }

  function getMainMenu() {
    $webmodules = findModulesByClass('core\MenuModule');
    $hasmanage=false;
    $hassettings=false;
    $hascdr=false;
    $hasstatistics=false;
    $hashelp=false;
    if(count($webmodules)) {
      $webmoduleclass = $webmodules[0]->class;
      $permissionscope = $webmoduleclass::getPermissionsScope();
      if($permissionscope==null) {
        $hasmanage=true;
        $hassettings=true;
        $hascdr=true;
        $hasstatistics=true;
        $hashelp=true;
      } else {
        foreach($permissionscope as $scope) {
          if(strpos($scope, 'manage')===0) $hasmanage=true;
          if(strpos($scope, 'settings')===0) $hassettings=true;
          if(strpos($scope, 'cdr')===0) $hascdr=true;
          if(strpos($scope, 'statistics')===0) $hasstatistics=true;
          if(strpos($scope, 'help')===0) $hashelp=true;
        }
      }
    }
    $anymanage=false;
    $anysettings=false;
    $anycdr=false;
    $anystatistics=false;
    $anyhelp=false;
    foreach($webmodules as $module) {
      $moduleclass=$module->class;
      $path = $module->location;
      $checkresult = $moduleclass::check();
      if(strpos($path, 'manage')===0) {
        $anymanage |= $checkresult;
      } elseif(strpos($path, 'settings')===0) {
        $anysettings |= $checkresult;
      } elseif(strpos($path, 'cdr')===0) {
        $anycdr |= $checkresult;
      } elseif(strpos($path, 'statistics')===0) {
        $anystatistics |= $checkresult;
      } elseif(strpos($path, 'help')===0) {
        $anyhelp |= $checkresult;
      }
    }
    $hasmanage&=$anymanage;
    $hassettings&=$anysettings;
    $hascdr&=$anycdr;
    $hasstatistics&=$anystatistics;
    $hashelp&=$anyhelp;
    $menu = array();
    if($hasmanage) addMenu($menu,'/manage','oi oi-dashboard','Управление');
    if($hassettings) addMenu($menu,'/settings','oi oi-wrench','Настройки');
    if($hascdr) addMenu($menu,'/cdr','oi oi-calendar','Журналы');
    if($hasstatistics) addMenu($menu,'/statistics','oi oi-bar-chart','Аналитика');
//     if($hashelp) addMenu($menu,'/help','oi oi-book','Справка');
    preg_match('/(\/[a-zA-Z-0-9\-]*)/', '/'.$GLOBALS['location'], $match);
    setActiveMenu($menu,$match[1]);
    return genMainMenu($menu);
  }

  function getLeftMenu() {
     $webmodules = getModulesByClass('core\MenuModule');
     preg_match('/(\/[a-zA-Z-0-9\-]*)/', '/'.$GLOBALS['location'], $match);
     $tmpmenu = array();
     $maxpath = 0;
     foreach($webmodules as $module) {
       $moduleclass = get_class($module);
       $path = $moduleclass::getLocation();
       if(strpos('/'.$path, $match[1])===0) { //get root class menu only
         $item = $moduleclass::getMenu();
         $mp = substr_count($path, '/');
         if($mp > $maxpath) $maxpath=$mp;
         if($item) $tmpmenu[$path]=(object) array('data' => $item, 'islink' => is_subclass_of($moduleclass, 'core\ViewModule'), 'submenu' => array());
       }
     }
     uasort($tmpmenu, 'menucmp');
     $menu = array();
     $submenu = &$menu;
     $base = 'oi\' style=\'width: 2rem; text-align: center; box-sizing: content-box; background: url("data:image/svg+xml;utf8,<svg xmlns=\\"http://www.w3.org/2000/svg\\" xmlns:xlink=\\"http://www.w3.org/1999/xlink\\" version=\\"1.1\\" width=\\"40\\" height=\\"30\\"><text x=\\"0\\" y=\\"18\\">%s</text></svg>"); height: 1rem; background-size: %s;\'';
     for($level=1;$level<=$maxpath; $level++) {
       foreach($tmpmenu as $location => $item) {
         if($level==substr_count($location, '/')) {
           unset($submenu);
           if($level>1) {
             $loc=explode('/',$location);
             array_pop($loc);
             $loc=implode('/',$loc);
             if(isset($tmpmenu[$loc])&&isset($tmpmenu[$loc]->submenu)) {
               $submenu = &$tmpmenu[$loc]->submenu;
             } else {
               $submenu = false;
             }
           } else {
             $submenu = &$menu;
           }
           if(is_array($submenu)) {
             if($moduleclass::checkScope($location)) addMenu($submenu, $item->islink?('/'.$location):false, isset($item->data->icon)?$item->data->icon:sprintf($base,mb_strtoupper(mb_substr($item->data->name,0,3)),'100%'), $item->data->name, $tmpmenu[$location]->submenu);
           }
         }
       }
     }
     setActiveMenu($menu,'/'.$GLOBALS['location']);
     return genLeftMenu($menu);
  }

?>
