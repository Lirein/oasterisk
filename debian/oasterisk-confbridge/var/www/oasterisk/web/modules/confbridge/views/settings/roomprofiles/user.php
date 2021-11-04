<?php

namespace confbridge;

class UserSettings extends \core\ViewModule {

  public static function getLocation() {
    return 'settings/roomprofiles/user';
  }

  public static function getMenu() {
    return (object) array('name' => 'Профили участников', 'prio' => 2, 'icon' => 'oi oi-person');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-confbridge');
    return $result;
  }

  public static function getZoneInfo() {
    $result = new \SecZoneInfo();
    $result->zoneClass = 'confbridge_profile';
    $result->getObjects = function () {
                              $ini = new \INIProcessor('/etc/asterisk/confbridge.conf');
                              $profiles=array();
                              foreach($ini as $k => $v) {
                                if(isset($v->type)&&($v->type=='user')) {
                                  $profile=new \stdClass();
                                  $profile->id = $k;
                                  $profile->text = empty($v->getComment())?$k:$v->getComment();
                                  $profile->admin = isset($v->admin)?$v->admin->getValue():false;
                                  $profile->marked = isset($v->marked)?$v->marked->getValue():false;
                                  $profiles[]=$profile;
                                }
                              }
                              return $profiles;
                            };
    return $result;
  }

  public static function getObjects() {
    $getObjects = self::getZoneInfo()->getObjects;
    return $getObjects();
  }

  public function json(string $request, \stdClass $request_data) {
    $defaultsettings = '{
      "admin": "!no",
      "marked": "!no",
      "startmuted": "!no",
      "quiet": "!no",
      "music_on_hold_when_empty": "!no",
      "music_on_hold_class": "default",
      "announce_user_count": "!no",
      "announce_user_count_all": "!no",
      "announce_only_user": "!no",
      "wait_marked": "!no",
      "end_marked": "!no",
      "dsp_drop_silence": "!no",
      "dsp_talking_threshold": "1",
      "dsp_silence_threshold": "2",
      "talk_detection_events": "!no",
      "denoise": "!no",
      "jitterbuffer": "!no",
      "pin": "",
      "announce_join_leave": "!no",
      "announce_join_leave_review": "!no",
      "dtmf_passthrough": "!no",
      "announcement": "",
      "timeout": "0"
    }';
    $zonesmodule=new \core\SecZones;
    if($zonesmodule) $zonesmodule->getCurrentSeczones();
    $result = new \stdClass();
    switch($request) {
      case "user-profiles": {
        $return = array();
        $profiles=self::getObjects();
        foreach($profiles as $profile) {
          if(self::checkEffectivePriv('confbridge_profile', $profile->id, 'settings_reader')) $return[]=(object) array('id' => $profile->id, 'title' => $profile->text, 'admin' => $profile->admin, 'marked' => $profile->marked);
        }
        $result = self::returnResult($return);
      } break;
      case "user-profile": {
        if(isset($request_data->user)&&self::checkEffectivePriv('confbridge_profile', $request_data->user, 'settings_reader')) { 
          $profile = new \stdClass();
          $ini = new \INIProcessor('/etc/asterisk/confbridge.conf');
          $username = $request_data->user;
          if(isset($ini->$username)) {
            $k = $username;
            $v = $ini->$username;
            if(isset($v->type)&&($v->type=='user')) {
              $profile = $v->getDefaults($defaultsettings);
              $profile->id = $k;
              $profile->title = empty($v->getComment())?$k:$v->getComment();
              if($zonesmodule&&!self::checkZones()) {
                $profile->zones=$zonesmodule->getObjectSeczones('confbridge_profile', $k);
              }
              $profile->readonly=!self::checkEffectivePriv('confbridge_profile', $k, 'settings_writer');
            }
          }
          $result = self::returnResult($profile);
        }
      } break;
      case "set-user-profile": {
        if(isset($request_data->orig_id)&&self::checkEffectivePriv('confbridge_profile', $request_data->orig_id, 'settings_writer')) {
          $ini = new \INIProcessor('/etc/asterisk/confbridge.conf');
          $username = $request_data->id;
          if(isset($request_data->orig_id)&&($request_data->orig_id!='')&&($request_data->orig_id!=$request_data->id)) {
            if(isset($ini->$username)) {
              $result = self::returnError('danger', 'Профиль с таким идентификатором уже существует');
              break;
            }
            if($zonesmodule&&self::checkZones()) {
              foreach($zonesmodule->getObjectSeczones('confbridge_profile', $request_data->orig_id) as $zone) {
                $zonesmodule->removeSeczoneObject($zone, 'confbridge_profile', $request_data->orig_id);
                $zonesmodule->addSeczoneObject($zone, 'confbridge_profile', $request_data->id);
              }
            }
            $oldusername = $request_data->orig_id;
            if(isset($ini->$oldusername))
              unset($ini->$oldusername);
          }
          if((!isset($request_data->orig_id))||$request_data->orig_id=='') {
            if(isset($ini->$username)) {
              $result = self::returnError('danger', 'Профиль с таким идентификатором уже существует');
              break;
            }
            if($zonesmodule&&self::checkZones()) {
              $eprivs = $zonesmodule->getCurrentPrivs('confbridge_profile', $request_data->id);
              $zone = isset($eprivs['settings_writer'])?$eprivs['settings_writer']:false;
              if(!$zone) $zone = isset($eprivs['settings_reader'])?$eprivs['settings_reader']:false;
              if($zone) {
                $zonesmodule->addSeczoneObject($zone, 'confbridge_profile', $request_data->id);
              } else {
                $result = self::returnError('danger', 'Отказано в доступе');
                break;
              }
            }
          }
          if(findModuleByPath('settings/security/seczones')&&($zonesmodule&&!self::checkZones())) {
            $zones = $zonesmodule->getObjectSeczones('confbridge_profile', $request_data->id);
            foreach($zones as $zone) {
              $zonesmodule->removeSeczoneObject($zone, 'confbridge_profile', $request_data->id);
            }
            if(is_array($request_data->zones)) foreach($request_data->zones as $zone) {
              $zonesmodule->addSeczoneObject($zone, 'confbridge_profile', $request_data->id);
            }
          }
          $ini->$username->type = 'user';
          $ini->$username->setDefaults($defaultsettings, $request_data);
          $ini->$username->setComment($request_data->title);
          $ini->save();
          $confmodule = new \confbridge\ConfbridgeModule();
          if($confmodule&&$confmodule->configReload()) {
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Невозможно сохранить профиль пользователя');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "remove-user-profile": {
        if(isset($request_data->id)&&self::checkPriv('settings_writer')) {
          $result = self::returnError('danger', 'Невозможно удалить профиль нользователя');
          $ini = new \INIProcessor('/etc/asterisk/confbridge.conf');
          $username = $request_data->id;
          if(isset($ini->$username)) {
            $v = $ini->$username;
            if(isset($v->type)&&($v->type=='user')) {
              if($zonesmodule) {
                foreach($zonesmodule->getObjectSeczones('confbridge_profile', $request_data->id) as $zone) {
                  $zonesmodule->removeSeczoneObject($zone, 'confbridge_profile', $request_data->id);
                }
              }
              unset($ini->$username);
              $ini->save();
              $confmodule = new \confbridge\ConfbridgeModule();
              if($confmodule&&$confmodule->configReload()) {
                $result = self::returnSuccess('Профиль успешно удален');
              }
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
      var user_profile_id='';
      function updateUserProfiles() {
        sendRequest('user-profiles').success(function(data) {
          var hasactive=false;
          var items=[];
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              if(data[i].id==user_profile_id) hasactive=true;
              if(data[i].marked&&data[i].admin) status = 'danger';
              else if(data[i].admin) status = 'warning';
              else if(data[i].marked) status = 'success';
              else status='';
              items.push({id: data[i].id, title: (data[i].title!='')?data[i].title:data[i].id, active: data[i].id==user_profile_id, class: status});
            }
          }
          rightsidebar_set('#sidebarRightCollapse', items);
          if(!hasactive) {
            card.hide();
            rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
            sidebar_apply(null);
            window.history.pushState(user_profile_id, $('title').html(), '/'+urilocation);
            user_profile_id='';
            if(data.length>0) loadUserProfile(data[0].id);
          } else {
            rightsidebar_init('#sidebarRightCollapse', sbdel, sbadd, sbselect);
            sidebar_apply(sbapply);
            loadUserProfile(user_profile_id);
          }
        });
      }

      function loadUserProfile(profile_id) {
        sendRequest('user-profile', {user: profile_id}).success(function(data) {
          if(data.id==profile_id) {
            user_profile_id=data.id;
            rightsidebar_activate('#sidebarRightCollapse', user_profile_id);
            rightsidebar_init('#sidebarRightCollapse', sbdel, sbadd, sbselect);
            card.setValue(data);
            card.show();
            sidebar_apply(data.readonly?null:sbapply);
            if(data.readonly) card.disable(); else card.enable();
          }
        });
      }

      function sendUserProfileData() {
        var data = card.getValue();
        data.orig_id=user_profile_id;
        sendRequest('set-user-profile', data).success(function() {
          user_profile_id=data.id;
          updateUserProfiles();
          return true;
        });
      }

      function sendUserProfile() {
        var proceed = false;
        var data = card.getValue();
        data.orig_id = user_profile_id;
        if(data.id=='') {
          showalert('warning','Не задан идентификатор профиля');
          return false;
        }
        if((data.orig_id!='')&&(data.id!=data.orig_id)) {
          showdialog('Идентификатор профиля изменен','Выберите действие с профилем:',"warning",['Rename','Copy', 'Cancel'],function(e) {
            if(e=='Rename') {
              proceed=true;
            }
            if(e=='Copy') {
              proceed=true;
              user_profile_id='';
            }
            if(proceed) {
              sendUserProfileData();
            }
          });
        } else {
          proceed=true;
        }
        if(proceed) {
          sendUserProfileData();
        }
      }

      function addUserProfile() {
        user_profile_id='';
        rightsidebar_activate('#sidebarRightCollapse', null);
        var data = {
          id: 'new_user',
          title: 'Профиль участника',
          admin: '',
          marked: '',
          startmuted: '',
          quiet: '',
          music_on_hold_when_empty: '',
          music_on_hold_class: '',
          announce_user_count: '',
          announce_user_count_all: '',
          announce_only_user: '',
          wait_marked: '',
          end_marked: '',
          dsp_drop_silence: '',
          dsp_talking_threshold: '',
          dsp_silence_threshold: '',
          talk_detection_events: '',
          denoise: '',
          jitterbuffer: '',
          announce_join_leave: '',
          announce_join_leave_review: '',
          dtmf_passthrough: '',
          timeout: '',
          pin: '',
          announcement: ''
        }
        card.setValue(data);
        card.show();
        card.enable();
        sidebar_apply(sbapply);
        rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
      }

      function removeUserProfile() {
        showdialog('Удаление профиля','Вы уверены что действительно хотите удалить профиль участника?',"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            var data = {};
            data.id = user_profile_id;
            sendRequest('remove-user-profile', data).success(function() {
              user_profile_id='';
              updateUserProfiles();
              return false;
            });
          }
        });
      }

      function sbselect(e, item) {
        loadUserProfile(item);
      }

      function sbapply(e) {
        sendUserProfile();
      }

<?php
  if(self::checkPriv('settings_writer')) {
?>

      function sbadd(e) {
        addUserProfile();
      }

      function sbdel(e) {
        removeUserProfile();
      }

<?php
  } else {
?>

    var sbadd=null;
    var sbdel=null;

<?php
  }
?>

      $(function () {
        var items=[];
        rightsidebar_set('#sidebarRightCollapse', items);
        rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
        sidebar_apply(null);
        var cont = null;
        card = new widgets.section(rootcontent,null);
        obj = new widgets.input(card, {id: 'id', pattern: '[a-zA-Z0-9_-]+'}, "Идентификатор профиля");
        obj = new widgets.input(card, {id: 'title'}, "Наименование профиля");
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
        cont = new widgets.columns(subcard,2);
        cont.node.classList.add('form-group');
        obj = new widgets.checkbox(cont, {id: 'admin', value: false}, "Администратор", "Администратор конференц-комнаты имеет право управления параметрами конференц комнаты, в частности, установкой паролей, пинкодов и отключением участников конференции.");
        cont = new widgets.columns(subcard,2);
        cont.node.classList.add('form-group');
        obj = new widgets.checkbox(cont, {id: 'marked', value: false}, "Ведущий участник", "Имеет право создания закрытых конференц-команат и может подключаться к конференц-комнатам за пределами допустимых окон доступа.");
        obj = new widgets.checkbox(card, {single: true, id: 'startmuted', value: false}, "Отключить микрофон при подключении к конференц-комнате");
        obj = new widgets.checkbox(card, {single: true, id: 'quiet', value: false}, "Не анонсировать подключение и отключение других участников", "Если опция включена, участнику не анонсируются подключения и отключения других абонентов, включая служебное уведомление о факте подключения и отключения абонентов.");
        obj = new widgets.checkbox(card, {single: true, id: 'music_on_hold_when_empty', value: false}, "Музыка на удержании при пустой комнате", "Проигрывает музыку на удержании, если участник является единственным в конференц-комнате");
        obj = new widgets.select(card, {id: 'music_on_hold_class', value: []}, "Класс музыки на удержании");
        obj = new widgets.checkbox(card, {single: true, id: 'announce_user_count', value: false}, "Озвучивать текущее количество участников","Озвучивает абоненту текущее количество участников конференц- комнаты при подключении к конференции, без учета самого абонента.");
        obj = new widgets.checkbox(card, {single: true, id: 'announce_user_count_all', value: false}, "Анонсировать новое количество участников","Анонсирует всем участникам конференц-комнаты общее число участников сразу после подключения абонента, за исключением самого участника.");
        obj = new widgets.checkbox(card, {single: true, id: 'announce_only_user', value: false}, "Озвучивать приветствие при пустой комнате","Озвучивает уведомление абоненту о том, что он является единственным участником конференц-комнаты");
        obj = new widgets.checkbox(card, {single: true, id: 'wait_marked', value: false}, "Ожидать ведущего", "Ожидать пока <i>ведущий</i> участник не подключится к конференц-комнате, проигрывать при этом музыку на ожидании.");
        obj = new widgets.checkbox(card, {single: true, id: 'end_marked', value: false}, "Выход без ведущего участника", "Все участники, имеющие данный параметр профиля, будут выкинуты из конференции, когда её покинет последний <i>ведущий</i> участник.");
        obj = new widgets.checkbox(card, {single: true, id: 'dsp_drop_silence', value: false}, "Подавлять тишину", "Автоматически определяет наличие тишины в линии и подавляет аудио поток от участника конференции.<br>Положительно сказывается на производительности сервера и на качестве связи при большом количестве участников.");
        obj = new widgets.input(card, {id: 'dsp_talking_threshold', pattern: '[0-9]*'}, "Порог определения разговора", {caption: "Порог определения разговора (в миллисекундах)", text: "Определяет задержку «антидребезга» для создания события начала разговора. Если в течение указанного времени регистрируется громкость аудиопотока <i>более</i> половины допустимой амплитуды, считается, что такой участник разговаривает."});
        obj = new widgets.input(card, {id: 'dsp_silence_threshold', pattern: '[0-9]*'}, "Порог определения тишины", {caption: "Порог определения тишины (в миллисекундах)", text: "Определяет задержку «антидребезга» для создания события окончания разговора. Если в течение указанного времени регистрируется громкость аудиопотока <i>менее</i> половины допустимой амплитуды, считается, что такой участник прекратил говорить."});
        obj = new widgets.checkbox(card, {single: true, id: 'talk_detection_events', value: false}, "Уведомлять о факте разговора абонентом", "Регистрирует события начала и окончания разговора от участника конференц-комнаты.<br>Необходимо для индикации разговора в управлении проводимой конференцией");
        obj = new widgets.checkbox(card, {single: true, id: 'denoise', value: false}, "Включить шумодав");
        obj = new widgets.checkbox(card, {single: true, id: 'jitterbuffer', value: false}, "Использовать буферизацию аудио-потока", "Буферизация аудио-потока подходит для абонентов с плохим качеством связи, однако порождает задержку или эхо, слышимое другими участникам конференц-комнаты");
        obj = new widgets.checkbox(card, {single: true, id: 'announce_join_leave', value: false}, "Анонсировать имя участникам конференции");
        obj = new widgets.checkbox(card, {single: true, id: 'announce_join_leave_review', value: false}, "Запрашивать имя участника у абонента", "Позволяет абоненту указать своё имя для последующего озвучивания другим участникам конференции");
        obj = new widgets.checkbox(card, {single: true, id: 'dtmf_passthrough', value: false}, "Передавать нажатия клавиш", "Позволяет передавать сигнализацию DTMF напрямую в конференц-комнату, минуя обработку меню пользователя.<br>Используется при объединении нескольких систем селекторной/конференц связи.");
        obj = new widgets.input(card, {id: 'timeout', pattern: '[0-9]*'}, "Таймаут участия в конференции", "Позволяет задать максимальное время присутствия в конференции");
        obj = new widgets.input(card, {id: 'pin', pattern: '[0-9]*'}, "Пин-код для доступа к конференциям", "Позволяет задать общий пин-код, ограничивающий пользователям с данным профилем возможность подключаться к конференц-комнатам. Обычно используется для профиля «По умолчанию» для повышения безопасности конференц-комнат.");
        obj = new widgets.select(card, {id: 'announcement', value: sound_data, clean: true, search: true}, "Произвольное звуковое приветствие");
        card.hide();
        updateUserProfiles();
      })
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