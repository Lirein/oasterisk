<?php

namespace core;

class SecZonesSecuritySettings extends ViewModule {

  public static function getLocation() {
    return 'settings/security/seczones';
  }

  public static function getMenu() {
    return (object) array('name' => 'Зоны безопасности', 'prio' => 3, 'icon' => 'oi oi-magnifying-glass');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('security_reader');
    if(self::checkZones()) $result = false;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    $zonesmodule=new \core\SecZones();
    switch($request) {
      case "seczones": {
        $zones = array();
        foreach($zonesmodule->getSeczones() as $zone => $name) {
          $zones[]=(object) array('id' => $zone, 'title' => $name);
        }
        $result = self::returnResult($zones);
      } break;
      case "seczone-get": {
        $zoneinfo = new \stdClass();
        $zoneinfo->zone=$zonesmodule->getSeczone($request_data->seczone);
        $zoneinfo->classes = new \stdClass();
        $classes = $zonesmodule->getSeczoneClasses();
        foreach($classes as $zoneclass) {
          $zonename = 'zone_'.$zoneclass->class;
          $zoneinfo->classes->$zonename=array();
          $objects = $zonesmodule->getSeczoneObjects($request_data->seczone, $zoneclass->class);
          foreach($objects as $object_id => $object_name) {
            $zoneinfo->classes->$zonename[] = (object) array('id' => $object_id, 'text' => $object_name);
          }
        }
        $result = self::returnResult($zoneinfo);
      } break;
      case "seczone-set": {
        if(isset($request_data->id)&&self::checkPriv('security_writer')) {
          if($request_data->privs === 'false') $request_data->privs = array();
          $createresult = $zonesmodule->setSeczone(isset($request_data->orig_id)?$request_data->orig_id:null, $request_data->id, $request_data->title, $request_data->privs);
          switch($createresult) {
            case 0: $result = self::returnSuccess(); break;
            case 2: $result = self::returnError('danger', 'Зона безопасности с таким идентификатором уже существует'); break;
            default: $result = self::returnError('danger', 'Невозможно сохранить зону безопасности');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "seczone-remove": {
        if(isset($request_data->id)&&self::checkPriv('security_writer')) {
          $removeresult = $zonesmodule->removeSeczone($request_data->id);
          switch($removeresult) {
            case 0: $result = self::returnSuccess(); break;
            case 2: $result = self::returnError('danger', 'Зоны безопасности с таким идентификатором не существует'); break;
            default: $result = self::returnError('danger', 'Невозможно удалить зону безопасности');
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
      var seczone_id='<?php echo (isset($_GET['id'])?$_GET['id']:"null"); ?>';
      var card = null;
      var dummyclasses = {};

      function updateseczones() {
        sendRequest('seczones').success(function(data) {
          var hasactive=false;
          var items=[];
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              if(data[i].id==seczone_id) hasactive=true;
              items.push({id: data[i].id, title: data[i].title, active: data[i].id==seczone_id});
            }
          };
          rightsidebar_set('#sidebarRightCollapse', items);
          if(!hasactive) {
            card.hide();
            window.history.pushState(seczone_id, $('title').html(), '/'+urilocation);
            seczone_id='';
            rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
            sidebar_apply(null);
            if(data.length>0) loadseczone(data[0].id);
          } else {
            loadseczone(seczone_id);
          }
          return false;
        });
      }

      function loadseczone(seczone) {
        sendRequest('seczone-get', {seczone: seczone}).success(function(data) {
          rightsidebar_activate('#sidebarRightCollapse', seczone);
          rightsidebar_init('#sidebarRightCollapse', sbdel, sbadd, sbselect);
          sidebar_apply(sbapply);
          seczone_id=data.zone.id;
          data.zone.name=data.zone.id;
          data.zone.desc=data.zone.title;
          delete data.zone.id;
          data.zone.privs={uncheck: true, value: data.zone.privs};
          card.setValue(data.zone);
          for(zoneclass in data.classes) {
            if(data.classes[zoneclass].length==0) {
              card.node.querySelector('#'+zoneclass).parentNode.parentNode.parentNode.parentNode.widget.hide();
            } else {
              data.classes[zoneclass] = {clean: true, value: data.classes[zoneclass]};
              card.node.querySelector('#'+zoneclass).parentNode.parentNode.parentNode.parentNode.widget.show();
            }
          }
          card.setValue(data.classes);
<?php
  if(!self::checkPriv('security_writer')) {
?>
          card.disable();
<?php
  }
?>
          window.history.pushState(seczone_id, $('title').html(), '/'+urilocation+'?id='+seczone_id);
          card.show();
          return false;
        });
      }

      function addseczone() {
        seczone_id='';
        rightsidebar_activate('#sidebarRightCollapse', null);
        card.setValue({name: 'new_seczone', desc: 'Новая зона безопасности'});
        card.setValue(dummyclasses);
        for(zoneclass in dummyclasses) {
          card.node.querySelector('#'+zoneclass).parentNode.parentNode.parentNode.parentNode.widget.hide();
        }
        card.show();
        sidebar_apply(sbapply);
        rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
      }

      function removeseczone() {
        showdialog('Удаление зоны безопасности','Вы уверены что действительно хотите удалить зону безопасности?',"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            var data = {};
            data.id = seczone_id;
            sendRequest('seczone-remove', data).success(function(data) {
              showalert('success','Зона безопасности успешно удалена');
              seczone_id='';
              updateseczones();
              return false;
            });
          }
        });
      }

      function sendseczoneData() {
        var data = {};
        data=card.getValue();
        data.orig_id = seczone_id;
        data.id = data.name;
        data.title = data.desc;
        sendRequest('seczone-set', data).success(function() {
          showalert('success','Зона безопасности успешно изменена');
          seczone_id=data.id;
          updateseczones();
          return false;
        });
      }
 
      function sendseczone() {
        var proceed = false;
        var data = {};
        data=card.getValue();
        data.orig_id = seczone_id;
        data.id = data.name;
        if(data.id=='') {
          showalert('warning','Не задан идентификатор зоны');
          return false;
        }
        if((data.orig_id!='')&&(data.id!=data.orig_id)) {
          showdialog('Идентификатор зоны безопасности изменен','Выберите действие с зоной безопасности:',"warning",['Rename','Copy', 'Cancel'],function(e) {
            if(e=='Rename') {
              proceed=true;
            }
            if(e=='Copy') {
              proceed=true;
              seczone_id='';
            }
            if(proceed) {
              sendseczoneData();
            }
          });
        } else {
          proceed=true;
        }
        if(proceed) {
          sendseczoneData();
        }
      }

      function sbselect(e, item) {
        loadseczone(item);
      }

<?php
  if(self::checkPriv('security_writer')) {
?>

      function sbadd(e) {
        addseczone();
      }

      function sbapply(e) {
        sendseczone();
      }

      function sbdel(e) {
        removeseczone();
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
        obj = new widgets.input(card, {id: 'desc'}, "Отображаемое имя", "Наименование зоны безопасности");
        obj = new widgets.input(card, {id: 'name', pattern: '[a-zA-Z0-9_-]+', placeholder: 'unique_id'}, "Идентификатор", "Уникальный идентификатор профиля безопасности в системе");
        var subcard = new widgets.section(card,null);
        subcard.node.classList.add('row');
        var objects_cont = new widgets.columns(subcard,2);
        var security_cont = new widgets.columns(subcard,2);
        var cont = null;
        obj = new widgets.list(security_cont, {id: 'privs', checkbox: true}, "Набор привилегий безопасности", "Задаёт набор привилегий для объектов, включенных в зону безопасности.<br>Если привилегии не назначены, набор привилегий наследуется исходя из текущих привилегий профиля безопасности.");
<?php
            $zonesmodule = new \core\SecZones();
            $classes = $zonesmodule->getSeczoneClasses();
            foreach($classes as $zoneclass) {
              printf('dummyclasses.zone_%s = [];', $zoneclass->class);
              printf('cont = new widgets.section(objects_cont, null, "%s");', $zoneclass->name);
              printf('new widgets.list(cont, {id: "zone_%s"});', $zoneclass->class);
            }
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
                  $name='Чтение логики вызовов';
                } break;
                case "dialplan_writer": {
                  $name='Изменение логики вызовов';
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
        updateseczones();
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