<?php

namespace core;

class UsersSecuritySettings extends ViewModule {

  public static function getLocation() {
    return 'settings/security/users';
  }

  public static function getMenu() {
    return (object) array('name' => 'Пользователи', 'prio' => 1, 'icon' => 'oi oi-key');
  }

  public static function mask2cidr($mask){
    $long = ip2long($mask);
    $base = ip2long('255.255.255.255');
    return 32-log(($long ^ $base)+1,2);
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

  public function json(string $request, \stdClass $request_data) {
    $generalparams = '{
      "secret": "",
      "role": "",
      "displayconnects": "yes",
      "allowmultiplelogin": "yes",
      "read": ",",
      "write": ",",
      "permit": [],
      "deny": []
    }';
    $result = new \stdClass();
    switch($request) {
      case "users": {
        $ini = new \INIProcessor('/etc/asterisk/manager.conf');
        $profileList = array();
        foreach($ini as $sectionName=>$section) {
          if(isset($section->secret)) { 
            $profiledata = self::getPermissions($sectionName);
            if(self::checkEffectivePriv('security_role', $profiledata->role, 'security_reader')) {
              $profile = new \stdClass();
              $profile->id = $sectionName;
              $profile->title = empty($section->getComment())?$sectionName:$section->getComment();
              $profileList[]=$profile;
            }
          }
        }
        $result = self::returnResult($profileList);
      } break;
      case "user-get": {
        $profile = new \stdClass();
        $ini = new \INIProcessor('/etc/asterisk/manager.conf');
        $user = $request_data->user;
        if(isset($ini->$user)) {
          $k = $user;
          $v = $ini->$user;
          //$v = $request_data->$k;
          $profile = self::getPermissions($k);
          unset($profile->rolename);
          unset($profile->privs);
          unset($profile->scope);
          if(self::checkEffectivePriv('security_role', $profile->role, 'security_reader')) {
            $profile->id = $k;
            $params = $v->getDefaults($generalparams);
            unset($params->role);
            $profile = object_merge($profile, $params);
            if($profile->read == null) $profile->read = array();
            if($profile->write == null) $profile->write = array();
            if($profile->deny == null) $profile->deny = array();
            if($profile->permit == null) $profile->permit = array();
            foreach($profile->permit as $key => $val) {
              $parts = explode('/', $val);
              if(strpos($parts[1],'.')!==false) $parts[1]=self::mask2cidr($parts[1]);
              $profile->permit[$key] = implode('/',$parts);
            }
            $comment = $v->getComment();
            $profile->name = empty($comment)?$k:$comment;
            $result = self::returnResult(array('user' => $profile,
             'readonly' => !self::checkEffectivePriv('security_role', $profile->role, 'security_writer')));
          }
        }
      } break;
      case "user-set": {
        if(isset($request_data->id)) {
          $profiledata = self::getPermissions($request_data->id);
          $haspriv = false;
          if($profiledata !== null) {
            $haspriv = self::checkEffectivePriv('security_role', $profiledata->role, 'security_writer');
          } else {
            $haspriv = true;
          }
          if($haspriv&&
             (!isset($request_data->role)||
              (isset($request_data->role)&&self::checkEffectivePriv('security_role', $request_data->role, 'security_writer'))
             )
            ) {
            $id = $request_data->id;
            $ini = new \INIProcessor('/etc/asterisk/manager.conf');
            if(isset($request_data->orig_id)&&($request_data->orig_id!='')&&($request_data->orig_id!=$request_data->id)) {
              $orig_id = $request_data->orig_id;
              if(isset($ini->$id)) {
                $result = self::returnError('danger', 'Пользователь уже существует');
                break;
              }
              if(isset($ini->$orig_id))
                unset($ini->$orig_id);
            }
            if((!isset($request_data->orig_id))||$request_data->orig_id=='') {
              if(isset($ini->$id)) {
                $result = self::returnError('danger', 'Пользователь уже существует');
                break;
              }
            }
            $profile = new \stdClass();
            if($request_data->permit!=='false') {
              $profile->deny = array('0.0.0.0/0');
              $profile->permit = $request_data->permit; 
            } else {
              $profile->deny = array();
              $profile->permit = array();
            }
            if(isset($request_data->secret)&&strlen($request_data->secret)) {
              $profile->secret = $request_data->secret;
            } else {
              $result = self::returnError('danger', 'Пароль не указан');
              break;
            }
            if(isset($request_data->role)) {
              if(($_SESSION['login']==$request_data->id)&&($request_data->role!=self::getPermissions($request_data->id)->role)) {
                $result = self::returnError('danger', 'Нельзя изменить привилегии самому себе');
                break;
              } else {
                $profile->role = $request_data->role;
                $role=self::getRole($request_data->role);
                if($role) {
                  $rights=self::expandPrivs($role->privs);
                  if($rights) {
                    $profile->read = $rights->read;
                    $profile->write = $rights->write;
                  }
                }
              }
            }
            if(isset($request_data->name)) $ini->$id->setComment($request_data->name);
            $profile->displayconnects = $request_data->displayconnects;
            $profile->allowmultiplelogin = $request_data->allowmultiplelogin;
            $ini->$id->setDefaults($generalparams, $profile);
            $ini->save();
            $result = self::returnSuccess();
            $this->reloadConfig();
          } else {
            $result = self::returnError('danger', 'Доступ запрещён');
          }
        }
      } break;
      case "user-remove": {
        if(isset($request_data->id)) {
          $id = $request_data->id;
          $profiledata = self::getPermissions($id);
          if(self::checkEffectivePriv('security_role', $profiledata->role, 'security_writer')) {
            $ini = new \INIProcessor('/etc/asterisk/manager.conf');
            if($_SESSION['login']==$id) {
              $result = self::returnError('danger', 'Невозможно удалить самого себя');
            } else {
              if(isset($ini->$id)) {
                unset($ini->$id);
                $result = self::returnSuccess();
                $ini->save();
                $this->reloadConfig();
              } else {
                $result = self::returnError('danger', 'Пользователя с таким идентификатором не существует');
              }
            }
          } else {
            $result = self::returnError('danger', 'Доступ запрещён');
          }
        }
      } break;
    }
    return $result;
  }

  public function scripts() {
    ?>
    <script>
      var user_id='<?php echo (isset($_GET['id'])?$_GET['id']:''); ?>';
      var roles=[];
      function updateUsers() {
        sendRequest('users').success(function(data) {
          var hasactive=false;
          var items=[];
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              if(data[i].id==user_id) hasactive=true;
              items.push({id: data[i].id, title: data[i].title, active: data[i].id==user_id});
            }
          };
          rightsidebar_set('#sidebarRightCollapse', items);
          if(!hasactive) {
            card.hide();
            window.history.pushState(user_id, $('title').html(), '/'+urilocation);
            user_id='';
            rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
            sidebar_apply(null);
            if(data.length>0) loadUser(data[0].id);
          } else {
            loadUser(user_id);
          }
          return false;
        });
      }

      function getRoles() {
        sendRequest('roles').success(function(data) {
          for(var i=0; i<data.length; i++) {
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
            data[i]={id: data[i].id, text: data[i].title};
          }
          card.setValue({role: data});
          return false;
        });
      }

      function loadUser(user) {
        sendRequest('user-get', {user: user}).success(function(data) {
          rightsidebar_activate('#sidebarRightCollapse', user);
          rightsidebar_init('#sidebarRightCollapse', sbdel, sbadd, sbselect);
          sidebar_apply(sbapply);
          user_id=data.user.id;
          data.user.permit={value: data.user.permit, clean: true};
          card.setValue(data.user);
          card.show();
          if(data.readonly) card.disable(); else card.enable();
          window.history.pushState(user_id, $('title').html(), '/'+urilocation+'?id='+user_id);
          rightsidebar_init('#sidebarRightCollapse', sbdel, sbadd, sbselect);
          return false;
        });
      }

      function addUser() {
        user_id='';
        rightsidebar_activate('#sidebarRightCollapse', null);
        var data={id: 'new_user', name: 'Новый пользователь', secret: '', role: 'full_control', displayconnects: false, allowmultiplelogin: true, permit: {value: ['127.0.0.1/32'], clean: true}};
        card.setValue(data);
        card.enable();
        card.show();
        sidebar_apply(sbapply);
        rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
      }

      function removeUser() {
        showdialog('Удаление пользователя','Вы уверены что действительно хотите удалить пользователя системы?',"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            sendRequest('user-remove', {id: user_id}).success(function(data) {
              user_id=''
              updateUsers();
              user_id='';
              return true;
            });
          }
        });
      }

      function sendUserData() {
        var data = card.getValue();
        data.orig_id = user_id;
        sendRequest('user-set', data).success(function() {
          user_id=data.id;
          updateUsers();
          return true;
        });
      }
 
      function sendUser() {
        var proceed = false;
        var data = card.getValue();
        data.orig_id = user_id;
        if(data.id=='') {
          showalert('warning', 'Не задан логин пользователя');
          return false;
        }
        if((data.orig_id!='')&&(data.id!=data.orig_id)) {
          showdialog('Идентификатор пользователя системы изменен','Выберите действие с пользователем системы:',"warning",['Rename','Copy', 'Cancel'],function(e) {
            if(e=='Rename') {
              proceed=true;
            }
            if(e=='Copy') {
              proceed=true;
              user_id='';
            }
            if(proceed) {
              sendUserData();
            }
          });
        } else {
          proceed=true;
        }
        if(proceed) {
          sendUserData();
        }
      }

      function sbselect(e, item) {
        loadUser(item);
      }

<?php
  if(self::checkPriv('security_writer')) {
?>

      function sbadd(e) {
        addUser();
      }

      function sbapply(e) {
        sendUser();
      }

      function sbdel(e) {
        removeUser();
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
        obj = new widgets.input(card, {id: 'id', pattern: '[a-zA-Z0-9_-]+', placeholder: 'unique_id'}, "Логин", "Уникальный идентификатор пользователя");
        obj = new widgets.input(card, {id: 'name'}, "Отображаемое имя");
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
        obj = new widgets.input(card, {id: 'secret', password: true}, "Пароль пользователя");
        obj = new widgets.select(card, {id: 'role', search: false}, "Профиль безопасности");
        obj = new widgets.toggle(card, {single: true, id: 'displayconnects', value: false}, "Уведомления о действиях пользователя");
        obj = new widgets.toggle(card, {single: true, id: 'allowmultiplelogin', value: false}, "Разрешить несколько сессий");
        obj = new widgets.iplist(card, {id: 'permit'}, "Разрешенные адреса", "Укажите адреса, с которых разрешен вход данному пользователю.");

<?php
  if(!self::checkPriv('security_writer')) {
?>
    card.disable();
<?php
  }
?>
        card.hide();
        getRoles();
        updateUsers();
      });
    </script>
    <?php
  }

  public function render() {
    ?>
        <input type="password" hidden/>
    <?php
  }

}

?>