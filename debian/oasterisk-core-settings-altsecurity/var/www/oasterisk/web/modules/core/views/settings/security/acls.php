<?php

namespace core;

class ACLsSecuritySettings extends ViewModule {

  public static function getLocation() {
    return 'settings/security/acls';
  }

  public static function getMenu() {
    return (object) array('name' => 'Фильтры ip-адресов', 'prio' => 5, 'icon' => 'oi oi-account-login');
  }
  
  public static function check() {
    $result = true;
    $result &= self::checkPriv('security_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function reloadConfig() {
    $this->ami->send_request('Command', array('Command' => 'acl reload'));
  }

  public function json(string $request, \stdClass $request_data) {
    $generalparams = '{
        "permit": [],
        "deny": []
    }';
    $result = new \stdClass();
    switch($request) {
      case "acls": {
        $ini = new \INIProcessor('/etc/asterisk/acl.conf');
        $profileList = array();
        foreach($ini as $sectionName=>$section) {
          $profile = new \stdClass();
          $profile->id = $sectionName;
          $profile->title = empty($section->getComment())?$sectionName:$section->getComment();
          if(self::checkEffectivePriv('acl', $profile->id, 'settings_reader')) $profileList[]=$profile;
        }
        $result = self::returnResult($profileList);
      } break;
      case "acl-get": {
        $profile = new \stdClass();
        $ini = new \INIProcessor('/etc/asterisk/acl.conf');
        $acl = $request_data->acl;
        if(isset($ini->$acl)) {
          $k = $acl;
          $v = $ini->$acl;
          $profile->id = $k;
          $profile = object_merge($profile, $v->getDefaults($generalparams));
          // if($profile->deny == null) $profile->deny = array();
          // if($profile->permit == null) $profile->permit = array();
          // foreach($profile->permit as $key => $val) {
          //   $parts = explode('/', $val);
          //   if(strpos($parts[1],'.')!==false) $parts[1]=self::mask2cidr($parts[1]);
          //   $profile->permit[$key] = implode('/',$parts);
          // }
          $comment = $v->getComment();
          $profile->title = empty($comment)?$k:$comment;
          $profile->readonly = !self::checkEffectivePriv('moh', $profile->id, 'settings_writer');
          $result = self::returnResult($profile);
        }
      } break;
      case "acl-set": {
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
            $ini = new \INIProcessor('/etc/asterisk/acl.conf');
            if(isset($request_data->orig_id)&&($request_data->orig_id!='')&&($request_data->orig_id!=$request_data->id)) {
              $orig_id = $request_data->orig_id;
              if(isset($ini->$id)) {
                $result = self::returnError('danger', 'Фильтр уже существует');
                break;
              }
              if(isset($ini->$orig_id))
                unset($ini->$orig_id);
            }
            if((!isset($request_data->orig_id))||$request_data->orig_id=='') {
              if(isset($ini->$id)) {
                $result = self::returnError('danger', 'Фильтр уже существует');
                break;
              }
            }
            $profile = new \stdClass();
              $profile->permit = $request_data->permit; 
              $profile->deny = $request_data->deny;            
            
            if(isset($request_data->title)) $ini->$id->setComment($request_data->title);
            $ini->$id->setDefaults($generalparams, $profile);
            $ini->save();
            $result = self::returnSuccess();
            $this->reloadConfig();
          } else {
            $result = self::returnError('danger', 'Доступ запрещён');
          }
        }
      } break;
      case "acl-remove": {
        if(isset($request_data->id)&&self::checkPriv('settings_writer')) {
          $id = $request_data->id;
          $ini = new \INIProcessor('/etc/asterisk/acl.conf');
          if(isset($ini->$id)) {
            unset($ini->$id);
            $result = self::returnSuccess();
            $ini->save();
            $this->reloadConfig();
          } else {
            $result = self::returnError('danger', 'Фильтра с таким идентификатором не существует');
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
      var acl_id='<?php echo (isset($_GET['id'])?$_GET['id']:''); ?>';

      function updateACLs() {
        sendRequest('acls').success(function(data) {
          var hasactive=false;
          var items=[];
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              if(data[i].id==acl_id) hasactive=true;
              items.push({id: data[i].id, title: data[i].title, active: data[i].id==acl_id});
            }
          };
          rightsidebar_set('#sidebarRightCollapse', items);
          if(!hasactive) {
            card.hide();
            window.history.pushState(acl_id, $('title').html(), '/'+urilocation);
            acl_id='';
            rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
            sidebar_apply(null);
            if(data.length>0) loadACL(data[0].id);
          } else {
            loadACL(acl_id);
          }
          return false;
        });
      }

      
      function loadACL(acl) {
        sendRequest('acl-get', {acl: acl}).success(function(data) {
          rightsidebar_activate('#sidebarRightCollapse', acl);
          rightsidebar_init('#sidebarRightCollapse', sbdel, sbadd, sbselect);
          sidebar_apply(sbapply);
          acl_id=data.id;
          data.permit={value: data.permit, clean: true};
          data.deny={value: data.deny, clean: true};
          card.setValue(data);
          card.show();
          if(data.readonly) card.disable(); else card.enable();
          window.history.pushState(acl_id, $('title').html(), '/'+urilocation+'?id='+acl_id);
          rightsidebar_init('#sidebarRightCollapse', sbdel, sbadd, sbselect);
          return false;
        });
      }

      function addACL() {
        acl_id='';
        rightsidebar_activate('#sidebarRightCollapse', null);
        var data={id: 'new_acl', title: 'Новый фильтр', permit: {value: ['127.0.0.1/32'], clean: true}, deny: {value: ['0.0.0.0/0'], clean: true}};
        card.setValue(data);
        card.enable();
        card.show();
        sidebar_apply(sbapply);
        rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
      }

      function removeACL() {
        showdialog('Удаление фильтра','Вы уверены что действительно хотите удалить фильтр ip-адресов?',"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            sendRequest('acl-remove', {id: acl_id}).success(function(data) {
              acl_id=''
              updateACLs();
              return true;
            });
          }
        });
      }

      function sendACLData() {
        var data = card.getValue();
        data.orig_id = acl_id;
        sendRequest('acl-set', data).success(function() {
          acl_id=data.id;
          updateACLs();
          return true;
        });
      }
 
      function sendACL() {
        var proceed = false;
        var data = card.getValue();
        data.orig_id = acl_id;
        if(data.id=='') {
          showalert('warning', 'Не задан id фильтра');
          return false;
        }
        if((data.orig_id!='')&&(data.id!=data.orig_id)) {
          showdialog('Идентификатор фильтра изменен','Выберите действие с фильтром:',"warning",['Rename','Copy', 'Cancel'],function(e) {
            if(e=='Rename') {
              proceed=true;
            }
            if(e=='Copy') {
              proceed=true;
              acl_id='';
            }
            if(proceed) {
              sendACLData();
            }
          });
        } else {
          proceed=true;
        }
        if(proceed) {
          sendACLData();
        }
      }

      function sbselect(e, item) {
        loadACL(item);
      }

<?php
  if(self::checkPriv('security_writer')) {
?>

      function sbadd(e) {
        addACL();
      }

      function sbapply(e) {
        sendACL();
      }

      function sbdel(e) {
        removeACL();
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
        obj = new widgets.input(card, {id: 'title'},
            "Отображаемое имя", 
            "Наименование фильтра, отображаемое в графическом интерфейсе");
        obj = new widgets.input(card, {id: 'id', pattern: '[a-zA-Z0-9_-]+'}, 
            "Наименование фильтра", 
            "Внутренний идентификатор класса");
        obj = new widgets.iplist(card, {id: 'permit'}, 
            "Разрешённые IP");
        obj = new widgets.iplist(card, {id: 'deny'}, 
            "Запрещённые IP");
        
        
<?php
  if(!self::checkPriv('security_writer')) {
?>
    card.disable();
<?php
  }
?>
        card.hide();
        updateACLs();
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