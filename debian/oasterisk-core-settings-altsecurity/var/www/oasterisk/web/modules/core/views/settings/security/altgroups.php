<?php

namespace core;

class AltGroupSecuritySettings extends ViewModule {

  public static function getLocation() {
    return 'settings/security/altgroups';
  }

  public static function getMenu() {
    return (object) array('name' => 'Профили безопасности', 'prio' => 2, 'icon' => 'oi oi-people');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('security_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function reloadConfig() {
    $this->ami->send_request('Command', array('Command' => 'manager reload'));
  }

  public static function &addMenu(&$menu, $link, $icon, $title, &$submenu=null, $active=false) {
     $entry=&$menu[];
     $entry=(object) array('id' => $link, 'icon' => $icon, 'text' => $title, 'value' => &$submenu, 'active' => $active);
     return $entry;
  }

  public function getScopes() {
     $webmodules = findModulesByClass('core\MenuModule');
     $tmpmenu = array();
     $tmpmenu['manage']=(object) array('data'=> (object) array('name' => 'Управление', 'prio' => 1, 'icon' => 'oi oi-dashboard'), 'islink' => false, 'submenu' => array(), 'objects' => null);
     $tmpmenu['settings']=(object) array('data'=> (object) array('name' => 'Настройки', 'prio' => 2,'icon' => 'oi oi-wrench'), 'islink' => false, 'submenu' => array(), 'objects' => null);
     $tmpmenu['cdr']=(object) array('data'=> (object) array('name' => 'Журналы', 'prio' => 3, 'icon' => 'oi oi-calendar'), 'islink' => false, 'submenu' => array(), 'objects' => null);
     $tmpmenu['statistics']=(object) array('data'=> (object) array('name' => 'Аналитика', 'prio' => 4, 'icon' => 'oi oi-bar-chart'), 'islink' => false, 'submenu' => array(), 'objects' => null);
//   $tmpmenu['help']=(object) array('data'=> (object) array('name' => 'Справка', 'prio' => 5, 'icon' => 'oi oi-book'), 'islink' => false, 'submenu' => array(), 'objects' => null);
     $maxpath=0;
     foreach($webmodules as $module) {
       $path = $module->location;
       $moduleclass = $module->class;
       $item = $moduleclass::getMenu();
       $mp = substr_count($path, '/');
       if($mp>$maxpath) $maxpath=$mp;
       if($item) {
         $tmpmenu[$path]=(object) array('data' => $item, 'islink' => is_subclass_of($moduleclass, 'core\ViewModule'), 'submenu' => array(), 'objects' => null);
         $tmpmenu[$path]->zoneclass = null;
         if(method_exists($moduleclass, 'getZoneInfo')) {
           $zoneInfo = $moduleclass::getZoneInfo();
           $tmpmenu[$path]->objects=$zoneInfo->getObjects();
           $tmpmenu[$path]->zoneclass=$zoneInfo->zoneClass;
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
               $entry=&self::addMenu($submenu, $location, isset($item->data->icon)?$item->data->icon:sprintf($base, mb_strtoupper(mb_substr($item->data->name,0,3)),'100%'), $item->data->name, $tmpmenu[$location]->submenu);
               if($item->objects&&$item->zoneclass) {
                 $entry->zoneclass = $item->zoneclass;
                 foreach($item->objects as $object) {
                   $objentry=&self::addMenu($entry->value, $location.'#'.$object->id, sprintf($base,mb_strtoupper(mb_substr($object->text,0,3)),'100%'), $object->text);
                 }
               }
             }
           }
         }
       }
     }
     return $menu;
  }

  public function json(string $request, \stdClass $request_data) {
    $zonesmodule=getModuleByClass('core\SecZones');
    if($zonesmodule) $zonesmodule->getCurrentSeczones();
    $result = new \stdClass();
    switch($request) {
      case "scopes": {
        $scopes = self::getScopes();
        $result = self::returnResult($scopes);
      } break;
      case "role-get": {
        $role = self::getRole($request_data->role);
        if(!isset($role->id)) {
          $role->id=$request_data->role;
          $role->title = '';
        }
        if($zonesmodule&&!self::checkZones()) {
          $role->zones = array();
          $classes = $zonesmodule->getSeczoneClasses();
          $zones=$zonesmodule->getObjectSeczones('security_role', $role->id);
          foreach($zones as $zone) {
            $zoneinfo=&$role->zones[];
            $zoneinfo=$zonesmodule->getSeczone($zone);
            $zoneinfo->classes = array();
            foreach($classes as $zoneclass) {
              $zoneinfo->classes[$zoneclass->class] = array();
              $objects = $zonesmodule->getSeczoneObjects($zone, $zoneclass->class);
              foreach($objects as $object_id => $object_name) {
                $zoneinfo->classes[$zoneclass->class][] = (object) array('id' => $object_id, 'text' => $object_name);
              }
            }
          }
        }
        $role->readonly = !self::checkEffectivePriv('security_role', $role->id, 'security_writer');
        if(self::checkEffectivePriv('security_role', $role->id, 'security_reader')) $result = self::returnResult($role);
      } break;
      case "role-set": {
        if(isset($request_data->id)&&self::checkEffectivePriv('security_role', $request_data->id, 'security_writer')) {
          $ini = new \INIProcessor('/etc/asterisk/manager.conf');
          $roles=self::getRoles();
          $perm=self::getPermissions($_SESSION['login']);
          if($request_data->privs === 'false') $request_data->privs = array();
          $rights=self::expandPrivs($request_data->privs);
          if(isset($request_data->orig_id)&&($request_data->orig_id!='')&&($request_data->orig_id!=$request_data->id)) { //rename
            if($perm->role == $request_data->orig_id) {
              $result = self::returnError('danger', 'Невозможно переименовать свою роль безопасности');
              break;
            }
            if(isset($roles[$request_data->id])) {
              $result = self::returnError('danger', 'Роль с таким идентификатором уже существует');
              break;
            }
            if($zonesmodule&&!self::checkZones()) {
              foreach($zonesmodule->getObjectSeczones('security_role', $request_data->orig_id) as $zone) {
                if(($zone==$request_data->orig_id.'_write')||($zone==$request_data->orig_id.'_read')) {
                  $zonesmodule->removeSeczone($zone);
                } else {
                  $zonesmodule->removeSeczoneObject($zone, 'security_role', $request_data->orig_id);
                }
              }
            }
            $role_cnt=self::getDB('role_persist', 'count');
            for($i=1; $i<=$role_cnt; $i++) {
              $role_id=self::getDB('role_persist', 'role_'.$i.'/id');
              if($role_id==$_POST['orig_id']) {
                self::setDB('role_persist', 'role_'.$i.'/id', $_POST['id']);
                break;
              }
            }
            foreach($ini as $key => $entry) {
              if(isset($entry->secret)&&isset($entry->role)) {
                if($entry->role==$request_data->orig_id) {
                  $entry->role = $request_data->id;
                  if($rights) {
                    $entry->read = implode(',',$rights->read);
                    $entry->write = implode(',',$rights->write);
                  }
                }
              }
            }
          }
          if($perm->role == $request_data->id) {
            $result = self::returnError('danger', 'Невозможно изменить свой профиль безопасности');
            break;
          }
          if((!isset($request_data->orig_id))||$request_data->orig_id=='') { //new
            if(isset($roles[$request_data->id])) {
              $result = self::returnError('danger', 'Профиль безопасности с таким тидентификатором уже существует');
              break;
            } else {
              $role_cnt=self::getDB('role_persist', 'count');
              $role_cnt++;
              self::setDB('role_persist', 'role_'.$role_cnt.'/id',$request_data->id);
              self::setDB('role_persist', 'count', $role_cnt);
              if($zonesmodule&&self::checkZones()) {
                $eprivs = $zonesmodule->getCurrentPrivs('security_role', $request_data->id);
                $zone = isset($eprivs['security_writer'])?$eprivs['security_writer']:false;
                if(!$zone) $zone = isset($eprivs['security_reader'])?$eprivs['security_reader']:false;
                if($zone) {
                  $zonesmodule->addSeczoneObject($zone, 'security_role', $request_data->id);
                } else {
                  $result = self::returnError('danger', 'Отказано в доступе');
                  break;
                }
              }
            }
          } else {
            foreach($ini as $key => $entry) {
              if(isset($entry->secret)&&isset($entry->role)) {
                if($entry->role==$request_data->id) {
                  if($rights) {
                    $entry->read = implode(',',$rights->read);
                    $entry->write = implode(',',$rights->write);
                  }
                }
              }
            }
          }
          if($zonesmodule&&!self::checkZones()) {
            $zones = $zonesmodule->getObjectSeczones('security_role', $request_data->id);
            foreach($zones as $zone) {
              $zonesmodule->removeSeczone($zone);
            }
            if($request_data->advancedrights=='true') {
              $zones = array();
              if(is_array($request_data->zones)) $zones = $request_data->zones;
              $has_ro=false;
              $has_rw=false;
              foreach($zones as $key => $zone) {
                if($zone->id==';rw') {
                  $zones[$key]->id=$request_data->id.'_write';
                  $zones[$key]->title=$zones[$key]->title.' '.$request_data->title;
                  $zones[$key]->privs=array("system_info", "system_control", "settings_reader", "settings_writer", "dialplan_reader", "dialplan_writer", "invoke_commands", "realtime", "security_reader", "security_writer", "dialing", "message", "cdr", "debug", "agent");
                  $has_rw=true;
                } elseif($zone->id==';ro') {
                  $zones[$key]->id=$request_data->id.'_read';
                  $zones[$key]->title=$zones[$key]->title.' '.$request_data->title;
                  $zones[$key]->privs=array("system_info", "settings_reader", "dialplan_reader", "realtime", "security_reader", "cdr", "agent");
                  $has_ro=true;
                } else {
                  $writeexists = false;
                  $readexists = false;
                  foreach($zone->privs as $priv) {
                    if(strpos($priv,'_reader')!==false) {
                      $readexists = true;
                    }
                    if(strpos($priv,'_writer')!==false) {
                      $writeexists = true;
                    }
                  }
                  if($writeexists) $has_rw=true;
                  elseif($readexists) $has_ro=true;
                }
              }
              if(!$has_rw) {
                $zones[]=(object) array('id' => $request_data->id.'_write',
                               'title' => 'Чтение и запись для '.$request_data->title,
                               'privs' => array("system_info", "system_control", "settings_reader", "settings_writer", "dialplan_reader", "dialplan_writer", "invoke_commands", "realtime", "security_reader", "security_writer", "dialing", "message", "cdr", "debug", "agent"),
                               'classes' => array()
                              );
                $has_rw=true;
              }
              if(!$has_ro) {
                $zones[]=(object) array('id' => $request_data->id.'_read',
                               'title' => 'Только чтение для '.$request_data->title,
                               'privs' => array("system_info", "settings_reader", "dialplan_reader", "invoke_commands", "realtime", "security_reader", "cdr", "agent"),
                               'classes' => array()
                              );
                $has_ro=true;
              }
              //create zones
              $classes = $zonesmodule->getSeczoneClasses();
              foreach($zones as $key => $zone) {
                $newzone=$zonesmodule->setSeczone(null, $zone->id, $zone->title, $zone->privs);
                $zonesmodule->addSeczoneObject($zone->id, 'security_role', $request_data->id);
                foreach($classes as $zoneclass) {
                  $classname = $zoneclass->class;
                  if(isset($zone->classes->$classname)&&is_array($zone->classes->$classname)&&($zoneclass->class!='security_role')) {
                    foreach($zone->classes->$classname as $object) {
                      $zonesmodule->addSeczoneObject($zone->id, $zoneclass->class, $object->id);
                    }
                  }
                }
              }
            }
          }
          $role_cnt=self::getDB('role_persist', 'count');
          for($i=1; $i<=$role_cnt; $i++) {
            $role_id=self::getDB('role_persist', 'role_'.$i.'/id');
            if($role_id==$request_data->id) {
              self::setDB('role_persist', 'role_'.$i.'/title', $request_data->title);
              self::deltreeDB('role_persist/role_'.$i.'/scope');
              self::deltreeDB('role_persist/role_'.$i.'/privs');
              if($request_data->privs === 'false') $request_data->privs = array();
              self::setDB('role_persist', 'role_'.$i.'/privs/count', count($request_data->privs));
              foreach($request_data->privs as $k => $priv) {
                self::setDB('role_persist', 'role_'.$i.'/privs/priv_'.($k+1), $priv);
              }
              if($request_data->scope === 'false') $request_data->scope = array();
              self::setDB('role_persist', 'role_'.$i.'/scope/count', count($request_data->scope));
              foreach($request_data->scope as $k => $scope) {
                self::setDB('role_persist', 'role_'.$i.'/scope/scope_'.($k+1), $scope);
              }
              break;
            }
          }
          $ini->save();
          $result = self::returnSuccess();
          self::reloadConfig();
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "role-remove": {
        if(isset($request_data->id)&&self::checkEffectivePriv('security_role', $request_data->id, 'security_writer')) {
          $ini = new \INIProcessor('/etc/asterisk/manager.conf');
          $zonesmodule=getModuleByClass('core\SecZones');
          if(self::getRole($_SESSION['login'])==$request_data->id) {
            $result = self::returnError('danger', 'Невозможно удалить свой профиль безопансости');
          } else {
            $canremove=true;
            foreach($ini as $key => $entry) {
              if(isset($entry->secret)&&isset($entry->role)) {
                if($entry->role==$request_data->id) {
                  $canremove=false;
                }
              }
            }
            if($canremove) {
              if($zonesmodule) {
                foreach($zonesmodule->getObjectSeczones('security_role', $request_data->id) as $zone) {
                  if(($zone==$request_data->id.'_write')||($zone==$request_data->id.'_read')) {
                    $zonesmodule->removeSeczone($zone);
                  } else {
                    $zonesmodule->removeSeczoneObject($zone, 'security_role', $request_data->id);
                  }
                }
              }
              $role_cnt=self::getDB('role_persist', 'count');
              for($i=1; $i<=$role_cnt; $i++) {
                $role_id=self::getDB('role_persist', 'role_'.$i.'/id');
                if($role_id==$request_data->id) {
                  self::deltreeDB('role_persist/role_'.$i);
                  for($j=$i+1; $j<=$role_cnt; $j++) {
                    self::setDB('role_persist', 'role_'.($j-1).'/id', self::getDB('role_persist', 'role_'.$j.'/id'));
                    self::setDB('role_persist', 'role_'.($j-1).'/title', self::getDB('role_persist', 'role_'.$j.'/title'));
                    $privs_count=self::getDB('role_persist', 'role_'.$j.'/privs/count');
                    self::setDB('role_persist', 'role_'.($j-1).'/privs/count', $privs_count);
                    for($k=1; $k<=$privs_count; $k++) {
                      self::setDB('role_persist', 'role_'.($j-1).'/privs/priv_'.$k, self::getDB('role_persist', 'role_'.$j.'/privs/priv_'.$k));
                    }
                    $scope_count=self::getDB('role_persist', 'role_'.$j.'/scope/count');
                    self::setDB('role_persist', 'role_'.($j-1).'/scope/count', $scope_count);
                    for($k=1; $k<=$scope_count; $k++) {
                      self::setDB('role_persist', 'role_'.($j-1).'/scope/scope_'.$k, self::getDB('role_persist', 'role_'.$j.'/scope/scope_'.$k));
                    }
                  }
                  self::deltreeDB('role_persist/role_'.$role_cnt);
                  self::setDB('role_persist', 'count', $role_cnt-1);
                  break;
                }
              }
              $result = self::returnSuccess();
            } else {
              $result = self::returnError('danger', 'Невозможно удалить назначенную роль безопасности');
            }
          }
        } else {
          $result = self::returnError('danger', 'Отзазано в доступе');
        }
      } break;
    }
    return $result;
  }

  public function scripts() {
    global $location;
    ?>
    <script>
      var role_id='<?php echo @$_GET['id']; ?>';
      var roles=[];
      var zones=[];
      var security_cont = null;
      var seczone_cont = null;
      var scope = null;
      var card = null;
      var adv_change = null;
      var rw_change = null;
      function updateRoles() {
        sendRequest('roles').success(function(data) {
          var hasactive=false;
          var items=[];
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              if(data[i].id==role_id) hasactive=true;
              if(data[i].title=='') {
                switch(data[i].id) {
                  case 'full_control': data[i].title='Полный доступ'; break;
                  case 'admin': data[i].title='Администратор'; break;
                  case 'technician': data[i].title='Проектировщик'; break;
                  case 'operator': data[i].title='Оператор'; break;
                  case 'agent': data[i].title='Агент'; break;
                  case 'manager': data[i].title='Руководитель'; break;
                }
              }
              items.push({id: data[i].id, title: data[i].title, active: data[i].id==role_id});
            }
          };
          rightsidebar_set('#sidebarRightCollapse', items);
          if(!hasactive) {
            card.hide();
            window.history.pushState(role_id, $('title').html(), '/'+urilocation);
            role_id='';
            rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
            sidebar_apply(null);
            if(data.length>0) loadRole(data[0].id);
          } else {
            loadRole(role_id);
          }
        });
      }

      function updateScopes() {
        sendRequest('scopes').success(function(data) {
          var hasactive=false;
          card.setValue({scope: {value: data, clean: true}});
        });
      }

      function loadRole(role) {
        sendRequest('role-get', {role: role}).success(function(data) {
          rightsidebar_activate('#sidebarRightCollapse', role);
          rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
          role_id=data.id;
          switch(data.id) {
            case 'full_control': data.title='Полный доступ'; break;
            case 'admin': data.title='Администратор'; break;
            case 'technician': data.title='Проектировщик'; break;
            case 'operator': data.title='Оператор'; break;
            case 'agent': data.title='Агент'; break;
            case 'manager': data.title='Руководитель'; break;
          }
          data.name=data.id;
          data.desc=data.title;
          data.privs={uncheck: true, value: data.privs};
          data.scope={uncheck: true, value: data.scope};
          zones=data.zones;

          var scopecount=0;
          if(data.scope.value) scopecount=data.scope.value.length;
          if(scopecount>0) {
            var items = scope.tree.getChildren(scope.tree);
            for(var i=0; i<items.length; i++) {
              var entry = scope.tree.getDataById(items[i]);
              if(typeof entry.zoneclass != 'undefined') {
                for(var j=0; j<data.zones.length; j++) {
                  if(typeof data.zones[j].classes[entry.zoneclass] != 'undefined') {
                    for(var k=0; k<data.zones[j].classes[entry.zoneclass].length; k++) {
                      if(typeof scope.tree.getDataById(entry.id+'#'+data.zones[j].classes[entry.zoneclass][k].id) != 'undefined') {
                        data.scope.value.push(entry.id+'#'+data.zones[j].classes[entry.zoneclass][k].id);
                      }
                    }
                  }
                }
              }
            }
          }
          data.advancedrights=(data.zones.length>0);
          scope.onChange = null;
          card.setValue(data);
          scope.onChange = scopeChange;
          modeChange(adv_change);
          window.history.pushState(role_id, $('title').html(), '/<?=$location?>?id='+role_id);
          card.show();
          if(data.readonly) card.disable(); else checkRole();
        });
      }

      function checkRole() {
        var candel=true;
        switch(role_id) {
          case '':
          case 'full_control':
          case 'admin':
          case 'technician':
          case 'operator':
          case 'agent':
          case 'manager': {
            candel=false;
          } break;
        }
        switch(card.getValue().name) {
          case 'full_control':
          case 'admin':
          case 'technician':
          case 'operator':
          case 'agent':
          case 'manager': {
            card.disable();
            card.node.querySelector('#name').widget.enable();
            sidebar_apply(null);
            rightsidebar_init('#sidebarRightCollapse', null, (role_id=='')?null:sbadd, sbselect);
          } break;
          default: {
            card.enable();
            sidebar_apply(sbapply);
            rightsidebar_init('#sidebarRightCollapse', (candel)?sbdel:null, (role_id=='')?null:sbadd, sbselect);
          }
        }
      }

      function addRole() {
        role_id='';
        rightsidebar_activate('#sidebarRightCollapse', null);
        var data = {};
        data.name='new_role';
        data.desc='Новый профиль безопасности';
        data.privs={uncheck: true, value: []};
        data.scope={uncheck: true, value: []};
        card.setValue(data);
        card.show();
        checkRole();
      }

      function removeRole() {
        showdialog('Удаление профиля безопасности','Вы уверены что действительно хотите удалить профиль безопасности системы?',"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            var data = {};
            data.id = role_id;
            sendRequest('role-remove', data).success(function() {
              showalert('success','Профиль безопасности системы успешно удален');
              role_id='';
              updateRoles();
              return false;
            });
          }
        });
      }

      function sendRoleData() {
        var data = card.getValue();
        data.orig_id = role_id;
        data.id = data.name;
        data.title = data.desc;
        delete data.rwaccess;
        if(data.advancedrights) {
          data.zones = zones;
        } else {
          delete data.zones;
        }
        var scope_real=[];
        for(var i=0; i<data.scope.length; i++) {
          if(data.scope[i].indexOf('#')==-1) scope_real.push(data.scope[i]);
        }
        data.scope=scope_real;
        sendRequest('role-set', data).success(function() {
          showalert('success','Профиль безопасности успешно изменен');
          role_id=data.id;
          updateRoles();
          return false;
        });
      }

      function sendRole() {
        var proceed = false;
        var data = card.getValue();
        var canrename=true;
        switch(role_id) {
          case '':
          case 'full_control':
          case 'admin':
          case 'technician':
          case 'operator':
          case 'agent':
          case 'manager': {
            canrename=false;
          } break;
        }
        data.orig_id = role_id;
        data.id = data.name;
        if(data.id=='') {
          showalert('warning','Не задан идентификатор профиля');
          return false;
        }
        if((data.orig_id!='')&&(data.id!=data.orig_id)) {
          showdialog('Идентификатор профиля безопасности изменен','Выберите действие с профилем безопасности:',"warning",canrename?['Rename','Copy', 'Cancel']:['Copy', 'Cancel'],function(e) {
            if(e=='Rename') {
              proceed=true;
            }
            if(e=='Copy') {
              proceed=true;
              role_id='';
            }
            if(proceed) {
              sendRoleData();
            }
          });
        } else {
          proceed=true;
        }
        if(proceed) {
          sendRoleData();
        }
      }

      function scopeUnselect(sender, item) {
        scopeSelectionChange(sender, item, 'unselect');
      }

      function scopeSelect(sender, item) {
        scopeSelectionChange(sender, item, 'select');
      }

      function scopeSelectionChange(sender, item, mode) {
        var allisobject = true;
        var items=sender.tree.getSelections();
        var checked=sender.tree.getCheckedNodes();
        if(mode=='select') {
          items.push(item);
        } else {
          var j=items.indexOf(item);
          if(j!=-1) items.splice(j,1);
        }
        var rw=0;
        var total=items.length;
        for(var r=0; r<items.length; r++) {
          item = items[r];
          if(item.indexOf('#')==-1) allisobject=false;
          if(checked.indexOf(item)==-1) {
            total--;
            continue;
          }
          var seczoneclass=null;
          var parentid=sender.tree.getNodeById(item).offsetParent().data('id');
          if(typeof parentid != 'undefined') {
            var parentdata=sender.tree.getDataById(parentid);
            if(typeof parentdata.zoneclass != 'undefined') {
              seczoneclass=parentdata.zoneclass;
            }
          }
          if(!seczoneclass) {
            allisobject = false;
          } else {
            for(var i=0; i<zones.length; i++) {
              if(typeof zones[i].classes[seczoneclass]!='undefined') {
                for(var j=0; j<zones[i].classes[seczoneclass].length; j++) {
                  if((parentid+'#'+zones[i].classes[seczoneclass][j].id)==item) {
                    for(var k=0; k<zones[i].privs.length; k++) {
                      if(zones[i].privs[k].indexOf('_writer')!=-1) {
                        rw++;
                        break;
                      }
                    }
                    break;
                  }
                }
              }
            }
          }
        }
        if(allisobject&&(total>0)) {
          security_cont.hide();
          seczone_cont.show();
          seczone_cont.setValue({rwaccess: {value: (rw==0)?false:((rw==total)?true:null), three: false}});
        } else {
          seczone_cont.hide();
          security_cont.show();
        }
      }

      function scopeChange(sender, item, state) {
        if(item.id.indexOf('#')!=-1) {
          if(state=='checked') {
            scope.tree.unselectAll();
            scope.tree.select(scope.tree.getNodeById(item.id));
            rw_change.setValue(false);
            rwChange(rw_change);
          } else {
            var seczoneclass=null;
            var parentid=scope.tree.getNodeById(item.id).offsetParent().data('id');
            if(typeof parentid != 'undefined') {
              var parentdata=scope.tree.getDataById(parentid);
              if(typeof parentdata.zoneclass != 'undefined') {
                seczoneclass=parentdata.zoneclass;
              }
            }
            if(seczoneclass) {
              for(var i=0; i<zones.length; i++) {
                if(typeof zones[i].classes[seczoneclass]!='undefined') {
                  for(var j=0; j<zones[i].classes[seczoneclass].length; j++) {
                    if((parentid+'#'+zones[i].classes[seczoneclass][j].id)==item.id) {
                      zones[i].classes[seczoneclass].splice(j,1);
                      break;
                    }
                  }
                }
              }
            }
          }
        }
      }

      function modeChange(sender) {
        var adv=sender.getValue();
        var items=scope.tree.getChildren(scope.tree);
        for(var i=0; i<items.length; i++) {
          if(items[i].indexOf('#')!=-1) {
            var node = scope.tree.getNodeById(items[i]);
            if(adv) {
              node.show();
              node.parent().parent().find('span[data-role=expander] > i').show();
            } else {
              node.hide();
              node.parent().parent().find('span[data-role=expander] > i').hide();
            }
          }
        }
        if(!adv) {
          var sel = scope.tree.getSelections();
          for(var i=0; i<sel.length; i++) {
            if(sel[i].indexOf('#')!=-1) {
              scope.tree.unselectAll();
              scope.tree.select(scope.tree.getNodeById(sel[i].substr(0,sel[i].indexOf('#'))));
              break;
            }
          }
        }
      }

      function rwChange(sender) {
        var item = null;
        var sel = scope.tree.getSelections();
        var checkeditems=scope.tree.getCheckedNodes();
        for(var r=0; r<sel.length; r++) {
          if((sel[r].indexOf('#')!=-1)&&(checkeditems.indexOf(sel[r])!=-1)) {
            item=sel[r];
            var checked=sender.getValue();
            var seczoneclass=null;
            var parentid=scope.tree.getNodeById(item).offsetParent().data('id');
            if(typeof parentid != 'undefined') {
              var parentdata=scope.tree.getDataById(parentid);
              if(typeof parentdata.zoneclass != 'undefined') {
                seczoneclass=parentdata.zoneclass;
              }
            }
            if(seczoneclass) {
              //remove object from zones
              for(var i=0; i<zones.length; i++) {
                if(typeof zones[i].classes[seczoneclass]!='undefined') {
                  for(var j=0; j<zones[i].classes[seczoneclass].length; j++) {
                    if((parentid+'#'+zones[i].classes[seczoneclass][j].id)==item) {
                      zones[i].classes[seczoneclass].splice(j,1);
                      break;
                    }
                  }
                }
              }
              var zoneset=false;
              //search for zone with right privelege
              for(var i=0; i<zones.length; i++) {
                var readonly=true;
                for(var k=0; k<zones[i].privs.length; k++) {
                  if(zones[i].privs[k].indexOf('_writer')!=-1) {
                    readonly=false;
                    break;
                  }
                }
                if(typeof zones[i].classes[seczoneclass] == 'undefined') zones[i].classes[seczoneclass] = [];
                if(readonly!=checked) {
                  zones[i].classes[seczoneclass].push({id: item.substr(item.indexOf('#')+1), text: scope.tree.getDataById(item).text});
                  zoneset=true;
                  break;
                }
              }
              if(!zoneset) {
                var zone=null;
                if(checked) {
                  zone={"id": ";rw", "title": "Чтение и запись", "privs": ["settings_writer"], "classes": {}};
                } else {
                  zone={"id": ";ro", "title": "Только чтение", "privs": [], "classes": {}};
                }
                zone.classes[seczoneclass]=[];
                zone.classes[seczoneclass].push({id: item.substr(item.indexOf('#')+1), text: scope.tree.getDataById(item).text});
                zones.push(zone);
              }
            }
          }
        }
      }

      function sbselect(e, item) {
        loadRole(item);
      }

<?php
  if(self::checkPriv('security_writer')) {
?>

      function sbadd(e) {
        addRole();
      }

      function sbapply(e) {
        sendRole();
      }

      function sbdel(e) {
        removeRole();
      }

<?php
  } else {
?>

    var sbadd=null;
    var sbapply=null;
    var sbdel=null;

<?php
  }
?>

      $(function () {
        var items=[];
        rightsidebar_set('#sidebarRightCollapse', items);
        rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
        sidebar_apply(null);

        card = new widgets.section(rootcontent,null);
        obj = new widgets.input(card, {id: 'name', pattern: '[a-zA-Z0-9_-]+', placeholder: 'unique_id'}, "Наименование профиля безопасности", "Уникальный идентификатор профиля безопасности в системе");
        obj.onInput=checkRole;
        obj = new widgets.input(card, {id: 'desc'}, "Отображаемое имя");
        var subcard = new widgets.section(card,null);
        subcard.node.classList.add('form-group');
        adv_change = new widgets.checkbox(subcard, {id: 'advancedrights', value: false}, "Ограничить доступ на уровне объектов", "Позволяет задать видимость и режим доступа отдельных объектов");
        adv_change.onChange=modeChange;
        subcard = new widgets.section(card,null);
        subcard.node.classList.add('row');
        var scope_cont = new widgets.columns(subcard,2);
        scope = new widgets.tree(scope_cont, {id: 'scope', checkbox: true}, "Область видимости профиля безопасности", "Область видимости ограничивает набор привилегий указанными частями графического интерфейса. Например - изменение настроек только учетных записей SIP.<br>Если область видимости не задана, доступ предоставляется ко всем загруженным модулям в зависимости от набора привилегий.");
        scope.onSelect = scopeSelect;
        scope.onUnselect = scopeUnselect;
        seczone_cont = new widgets.columns(subcard,2);
        seczone_cont.hide();
        obj = new widgets.section(seczone_cont,null);
        obj.node.classList.add('form-group');
        obj.node.style.position='sticky';
        obj.node.style.top='75px';
        rw_change = new widgets.checkbox(obj, {id: 'rwaccess', value: false}, "Разрешить взаимодействие с объектом", "Задает разрешения на изменение настроек объекта и интерактивное управление");
        rw_change.onChange=rwChange;
        security_cont = new widgets.columns(subcard,2);
        obj = new widgets.list(security_cont, {id: 'privs', checkbox: true}, "Набор привилегий безопасности", "Задаёт набор привилегий доступных пользователю");
<?php
            $privs = array_keys(self::$internal_priveleges);
            foreach($privs as $info) {
              switch($info) {
                case "system_info": {
                  $name='Состояние системы';
                } break;
                case "system_control": {
                  $name='Управление системой';
                } break;
                case "settings_reader": {
                  $name='Чтение настроек';
                } break;
                case "settings_writer": {
                  $name='Изменение настроек';
                } break;
                case "dialplan_reader": {
                  $name='Чтение диалплана';
                } break;
                case "dialplan_writer": {
                  $name='Изменение диалплана';
                } break;
                case "invoke_commands": {
                  $name='Вызов команд из сценария (AGI)';
                } break;
                case "realtime": {
                  $name='Получение событий в реальном времени';
                } break;
                case "security_reader": {
                  $name='Чтение настроек безопасности';
                } break;
                case "security_writer": {
                  $name='Изменение настроек безопасности';
                } break;
                case "dialing": {
                  $name='Осуществление вызовов';
                } break;
                case "message": {
                  $name='Отправка сообщений';
                } break;
                case "cdr": {
                  $name='Чтение журналов';
                } break;
                case "debug": {
                  $name='Диагностика работы системы';
                } break;
                case "agent": {
                  $name='Управление очередью вызовов';
                } break;
                default: {
                  $name=$info;
                }
              }
              printf('obj.setValue([{id: "%s", text: "%s"}]);', $info, $name);
            }
  if(!self::checkPriv('security_writer')) {
?>
       card.disable();
<?php
  }
?>
        card.hide();
        updateScopes();
        updateRoles();
      });
    </script>
    <?php
  }

  public function render() {
    ?>
    <?php
  }

}

?>