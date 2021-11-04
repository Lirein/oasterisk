<?php

namespace core;

class GroupsREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'security/group';
  }

  public static function &addMenu(&$menu, $link, $icon, $title, &$submenu=null, $type='menu') {
    $entry=&$menu[];
    $entry=(object) array('id' => $link, 'icon' => $icon, 'title' => $title, 'value' => &$submenu, 'type' => $type);
    return $entry;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        $groups = new \security\Groups();
        $objects = array();
        foreach($groups as $id => $data) {
          if(self::checkEffectivePriv(self::getServiceLocation(), $id, 'security_reader')) $objects[]=(object)array('id' => $id, 'title' => $data->name, 'readonly' => !(self::checkEffectivePriv(self::getServiceLocation(), $id, 'security_writer')&&(!isset(\security\Groups::$internal_roles[$id]))));
        }
        $result = self::returnResult($objects);
        unset($groups);
      } break;
      case "get": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'security_reader')) {
          $group = new \security\Group($request_data->id);
          $data = $group->cast();
          $data->users = array();
          foreach($group as $login => $user) {
            $data->users[] = (object) array('id' => $login, 'title' => $user->name);
          }
          $data->readonly = !self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'security_writer');
          $result = self::returnResult($data);
          unset($group);
        }
      } break;
      case "set": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'security_writer')) {
          $group = new \security\Group($request_data->id);        
          if($group->assign($request_data)) {
            if($group->save()){
              $group->reload();
              $result = self::returnResult((object)array('id' => $group->id));
            } else {
              $result = self::returnError('danger', 'Не удалось добавить группу');
            }   
          } else {
            $result = self::returnError('danger', 'Не удалось установить данные группы');
          }
          unset($group);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "add": {
        if(self::checkPriv('security_writer')) {
          $group = new \security\Group($request_data->id);        
          if($group->assign($request_data)) {
            if($group->save()){
              $result = self::returnResult((object)array('id' => $group->id));
            } else {
              $result = self::returnError('danger', 'Не удалось добавить группу');
            }   
          } else {
            $result = self::returnError('danger', 'Не удалось установить данные группы');
          }
          unset($group);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "remove": {
        if(isset($request_data->id)&&self::checkPriv('security_writer')) {
          $group = new \security\Group($request_data->id);
          if($group->delete()) {
            $group->reload();
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Не удалось удалить группу');
          }
          unset($group);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "privileges": {
        $result = self::returnResult(array_keys(\security\Groups::$internal_priveleges));
      } break;
      case "scopes": {
        $webmodules = findModulesByClass('view\Menu');
        $tmpmenu = array();
        $tmpmenu['manage']=(object) array('data'=> (object) array('name' => 'Управление', 'prio' => 1, 'icon' => 'oi oi-dashboard'), 'islink' => false, 'submenu' => array(), 'objects' => null, 'type' => 'menu');
        $tmpmenu['statistics']=(object) array('data'=> (object) array('name' => 'Статистика', 'prio' => 4, 'icon' => 'oi oi-bar-chart'), 'islink' => false, 'submenu' => array(), 'objects' => null, 'type' => 'menu');
        $tmpmenu['settings']=(object) array('data'=> (object) array('name' => 'Настройки', 'prio' => 2,'icon' => 'oi oi-wrench'), 'islink' => false, 'submenu' => array(), 'objects' => null, 'type' => 'menu');
        $maxpath=0;
        foreach($webmodules as $module) {
          $path = $module->location;
          $moduleclass = $module->class;
          $item = $moduleclass::getMenu();
          $mp = substr_count($path, '/');
          if($mp>$maxpath) $maxpath=$mp;
          if($item) {
            $type = 'menu';
            if(is_subclass_of($moduleclass, 'view\View')) $type = 'settings';
            if(is_subclass_of($moduleclass, 'view\Collection')) $type = 'collection';
            $tmpmenu[$path]=(object) array('data' => $item, 'islink' => is_subclass_of($moduleclass, 'view\View'), 'submenu' => array(), 'objects' => null, 'type' => $type);
            $tmpmenu[$path]->zoneclass = null;
            if(is_subclass_of($moduleclass, 'view\Collection')) {
              $resturi = $moduleclass::getAPI();
              $restmodule = getModuleByPath('rest/'.$resturi);
              if($restmodule) {
                $objects = $restmodule->json('menuItems', new \stdClass())->result;
              } else {
                $objects = array();
              }
              $tmpmenu[$path]->objects = $objects;
            }
          }
        }
        uasort($tmpmenu, 'menucmp');
        $menu = array();
        $submenu = &$menu;
        $base = 'oi\' style=\'width: 2rem; text-align: center; box-sizing: content-box; background: url("data:image/svg+xml;utf8,<svg xmlns=\\"http://www.w3.org/2000/svg\\" xmlns:xlink=\\"http://www.w3.org/1999/xlink\\" version=\\"1.1\\" width=\\"40\\" height=\\"30\\"><text x=\\"0\\" y=\\"18\\">%s</text></svg>"); height: 1rem; background-size: %s;\'';
        for($level=0;$level<=$maxpath; $level++) {
          foreach($tmpmenu as $location => $item) {
            if($level==substr_count($location, '/')) {
              unset($submenu);
              if($level>0) {
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
                if((!$item->islink)||self::checkScope($location)) {
                  $entry=&self::addMenu($submenu, $location, isset($item->data->icon)?$item->data->icon:sprintf($base, mb_strtoupper(mb_substr($item->data->name,0,3)),'100%'), $item->data->name, $tmpmenu[$location]->submenu, $tmpmenu[$location]->type);
                  if($item->objects) {
                    foreach($item->objects as $object) {
                      $dummy = null;
                      $objentry=&self::addMenu($entry->value, $location.'/'.$object->id, sprintf($base,mb_strtoupper(mb_substr($object->title,0,3)),'100%'), $object->title, $dummy, 'object');
                    }
                  }
                }
              }
            }
          }
        }
        $result = self::returnResult($menu);
      } break;
    }
    return $result;
  }
    
}

?>