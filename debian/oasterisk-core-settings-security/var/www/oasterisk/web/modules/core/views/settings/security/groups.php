<?php

namespace core;

class GroupsSecuritySettings extends ViewModule {

  public static function getLocation() {
    return 'settings/security/groups';
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

  public static function addMenu(&$menu, $link, $icon, $title, &$submenu=null, $active=false) {
     $menu[]=(object) array('id' => $link, 'icon' => $icon, 'text' => $title, 'value' => &$submenu, 'active' => $active);
  }

  public function getScopes() {
     $webmodules = findModulesByClass('core\MenuModule');
     $tmpmenu = array();
     $tmpmenu['manage']=(object) array('data'=> (object) array('name' => 'Управление', 'prio' => 1, 'icon' => 'oi oi-dashboard'), 'islink' => false, 'submenu' => array());
     $tmpmenu['settings']=(object) array('data'=> (object) array('name' => 'Настройки', 'prio' => 2, 'icon' => 'oi oi-wrench'), 'islink' => false, 'submenu' => array());
     $tmpmenu['cdr']=(object) array('data'=> (object) array('name' => 'Журналы', 'prio' => 3, 'icon' => 'oi oi-calendar'), 'islink' => false, 'submenu' => array());
     $tmpmenu['statistics']=(object) array('data'=> (object) array('name' => 'Аналитика', 'prio' => 4, 'icon' => 'oi oi-bar-chart'), 'islink' => false, 'submenu' => array(), 'objects' => null);
//   $tmpmenu['help']=(object) array('data'=> (object) array('name' => 'Справка', 'prio' => 5, 'icon' => 'oi oi-book'), 'islink' => false, 'submenu' => array());
     $maxpath=0;
     foreach($webmodules as $module) {
       $path = $module->location;
       $moduleclass = $module->class;
       $item = $moduleclass::getMenu();
       $mp = substr_count($path, '/');
       if($mp>$maxpath) $maxpath=$mp;
       if($item) $tmpmenu[$path]=(object) array('data' => $item, 'islink' => is_subclass_of($moduleclass, 'core\ViewModule'), 'submenu' => array());
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
             if((!$item->islink)||self::checkScope($location)) self::addMenu($submenu, $location, isset($item->data->icon)?$item->data->icon:sprintf($base,mb_strtoupper(mb_substr($item->data->name,0,3)),'100%'), $item->data->name, $tmpmenu[$location]->submenu);
           }
         }
       }
     }
     return $menu;
  }

  public function json(string $request, \stdClass $request_data) {
    global $permissions;
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
          $role->title='';
        }
        if($zonesmodule&&!self::checkZones()) {
          $role->zones=$zonesmodule->getObjectSeczones('security_role', $role->id);
        }
        $role->readonly=!self::checkEffectivePriv('security_role', $role->id, 'security_writer');
        if(self::checkEffectivePriv('security_role', $role->id, 'security_reader')) $result = self::returnResult($role);
      } break;
      case "role-set": {
        if(isset($request_data->id)&&self::checkEffectivePriv('security_role', $request_data->id, 'security_writer')) {
          $ini = new \INIProcessor('/etc/asterisk/manager.conf');
          $roles = self::getRoles();
          $perm = self::getPermissions($_SESSION['login']);
          if(!is_array($request_data->privs)) $request_data->privs = array();
          if(!is_array($request_data->scope)) $request_data->scope = array();
          $rights = self::expandPrivs($request_data->privs);
          if(isset($request_data->orig_id)&&($request_data->orig_id!='')&&($request_data->orig_id!=$request_data->id)) { //rename
            if($perm->role==$request_data->orig_id) {
              $result = self::returnError('danger', 'Невозможно переименовать свою группу безопасности');
              break;
            }
            if(isset($roles[$request_data->id])) {
              $result = self::returnError('danger', 'Роль с таким идентификатором уже существует');
              break;
            }
            if($zonesmodule&&empty($permissions['zones'])) {
              foreach($zonesmodule->getObjectSeczones('security_role', $request_data->orig_id) as $zone) {
                 $zonesmodule->removeSeczoneObject($zone, 'security_role', $request_data->orig_id);
              }
            }
            $role_cnt=self::getDB('role_persist', 'count');
            for($i=1; $i<=$role_cnt; $i++) {
              $role_id=self::getDB('role_persist', 'role_'.$i.'/id');
              if($role_id==$request_data->orig_id) {
                self::setDB('role_persist', 'role_'.$i.'/id', $request_data->id);
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
          if($perm->role==$request_data->id) {
            $result = self::returnError('danger', 'Запрещено изменять привилегии своей роли безопасности');
            break;
          }
          if((!isset($request_data->orig_id))||$request_data->orig_id=='') { //new
            if(isset($roles[$request_data->id])) {
              $result = self::returnError('danger', 'Роль с таким идентификатором уже существует');
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
              $zonesmodule->removeSeczoneObject($zone, 'security_role', $request_data->id);
            }
            if(is_array($request_data->zones)) foreach($request_data->zones as $zone) {
              $zonesmodule->addSeczoneObject($zone, 'security_role', $request_data->id);
            }
          }
          $role_cnt=self::getDB('role_persist', 'count');
          for($i=1; $i<=$role_cnt; $i++) {
            $role_id=self::getDB('role_persist', 'role_'.$i.'/id');
            if($role_id==$request_data->id) {
              self::setDB('role_persist', 'role_'.$i.'/title', $request_data->title);
              self::deltreeDB('role_persist/role_'.$i.'/scope');
              self::deltreeDB('role_persist/role_'.$i.'/privs');
              self::setDB('role_persist', 'role_'.$i.'/privs/count', count($request_data->privs));
              foreach($request_data->privs as $k => $priv) {
                self::setDB('role_persist', 'role_'.$i.'/privs/priv_'.($k+1), $priv);
              }
              self::setDB('role_persist', 'role_'.$i.'/scope/count', count($request_data->scope));
              foreach($request_data->scope as $k => $scope) {
                self::setDB('role_persist', 'role_'.$i.'/scope/scope_'.($k+1), $scope);
              }
              break;
            }
          }
          $ini->save();
          $result = self::returnSuccess();
          $this->reloadConfig();
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "role-remove": {
        if(isset($request_data->id)&&self::checkEffectivePriv('security_role', $request_data->id, 'security_writer')) {
          $result = self::returnSuccess();
          $ini = new \INIProcessor('/etc/asterisk/manager.conf');
          $zonesmodule=getModuleByClass('core\SecZones');
          if(self::getRole($_SESSION['login'])==$request_data->id) {
            $result = self::returnError('danger', 'Невозможно удалить свою роль безопасности');
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
                  $zonesmodule->removeSeczoneObject($zone, 'security_role', $request_data->id);
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
            } else {
              $result = self::returnError('danger', 'Невозможно удалить назначенную роль безопасности');
            }
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
    }
    return $result;
  }

  public function scripts() {
    ?>
    <script>
      var role_id='<?php echo @$_GET['id']; ?>';
      var roles=[];
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
          var props = $('#role-data');
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
          card.setValue(data);
          window.history.pushState(role_id, $('title').html(), '/'+urilocation+'?id='+role_id);
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
            sendRequest('role-remove', data).success(function(data) {
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
<?php
        if(findModuleByPath('settings/security/seczones')) {
          $zonesmodule=getModuleByClass('core\SecZones');
          if($zonesmodule) $zonesmodule->getCurrentSeczones();
          if($zonesmodule&&!self::checkZones()) {
            printf('var values = [];');
            foreach($zonesmodule->getSeczones() as $zone => $name) {
              printf('values.push({id: "%s", text: "%s"});', $zone, $name);
            }
            printf('obj = new widgets.collection(card, {id: "zones", value: values}, "Зоны безопасности");');
          }
        }
?>
        var subcard = new widgets.section(card,null);
        subcard.node.classList.add('row');
        var scope_cont = new widgets.columns(subcard,2);
        obj = new widgets.tree(scope_cont, {id: 'scope', checkbox: true}, "Область видимости профиля безопасности", "Область видимости ограничивает набор привилегий указанными частями графического интерфейса. Например - изменение настроек только учетных записей SIP.<br>Если область видимости не задана, доступ предоставляется ко всем загруженным модулям в зависимости от набора привилегий.");
        var security_cont = new widgets.columns(subcard,2);
        var cont = null;
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