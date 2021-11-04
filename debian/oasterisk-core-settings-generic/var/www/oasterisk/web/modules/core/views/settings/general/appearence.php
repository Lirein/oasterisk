<?php

namespace core;

class AppearenceSettings extends ViewModule {
  
  public static function getLocation() {
    return 'settings/general/appearence';
  }

  public static function getMenu() {
    return (object) array('name' => 'Внешний вид', 'prio' => 3, 'icon' => 'oi oi-brush');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = array();
    switch($request) {
      case "settings-get":{
        $return = new \stdClass();
        $return->savesystem=(self::checkPriv('settings_writer')&&!self::checkZones());
        $perm = self::_getRole();
        $return->savegroup=self::checkEffectivePriv('security_group', $perm, 'security_writer');
        $return->defaults = self::getCurrentSettings(true);
        $result = self::returnResult($return);
      } break;
      case "settings-set":{
        $perm = self::_getRole();
        switch($request_data->savetype) {
          case 0: {
            $ini = new \INIProcessor('/etc/asterisk/manager.conf');
            $login = $_SESSION['login'];
            $defaults = self::getCurrentSettings(true);
            if(isset($ini->$login)) {
              foreach(array_keys($defaults) as $key) {
                if(isset($request_data->$key)) {
                  $ini->$login->$key = (string) $request_data->$key;
                }
              }
              if($ini->save()) {
                $result = self::returnSuccess();
              } else {
                $result = self::returnError('danger', 'Не удалось сохранить настройки стиля');
              }
            }
            $result = self::returnSuccess();
          } break;
          case 1: {
            if(self::checkEffectivePriv('security_group', $perm, 'security_writer')) {
              foreach($request_data as $key => $value) {
                self::setDB('appearence/'.$perm, $key, $value);
              }   
              $result = self::returnSuccess();
            } else {
              $result = self::returnError('danger', 'Отказано в доступе');
            }
            $result = self::returnSuccess();
          } break;
          case 2: {
            if(self::checkPriv('settings_writer')&&!self::checkZones()) {
              $ini = new \INIProcessor('/etc/asterisk/asterisk.conf');
              foreach($request_data as $param => $value) {
                if($param != 'savetype') {
                  $ini->appearence->$param = $value;
                }
              }          
              if($ini->save()) {
                $result = self::returnSuccess();
              } else {
                $result = self::returnError('danger', 'Не удалось сохранить настройки стиля');
              }
            } else {
              $result = self::returnError('danger', 'Отказано в доступе');
            }
          } break;
          default: {
            $result = self::returnError('danger', 'Неверно указан тип сохраняемых настроек');
          }
        }
      } break;
      case "settings-reset":{
        switch($request_data->resettype) {
          case 0: {
            $ini = new \INIProcessor('/etc/asterisk/manager.conf');
            $login = $_SESSION['login'];
            $defaults = self::getCurrentSettings(true);
            if(isset($ini->$login)) {
              foreach(array_keys($defaults) as $key) {
                if(isset($ini->$login->$key)) unset($ini->$login->$key);
              }
              if($ini->save()) {
                $result = self::returnSuccess();
              } else {
                $result = self::returnError('danger', 'Не удалось сохранить настройки стиля');
              }
            }
            $result = self::returnSuccess();
          } break;
          case 1: {
            $perm = self::_getRole();
            if(self::checkEffectivePriv('security_group', $perm, 'security_writer')) {
              self::deltreeDB('appearence/'.$perm);   
              $result = self::returnSuccess();
            } else {
              $result = self::returnError('danger', 'Отказано в доступе');
            }
          } break;
          case 2: {
            if(self::checkPriv('settings_writer')&&!self::checkZones()) {
              $ini = new \INIProcessor('/etc/asterisk/asterisk.conf');
              if(isset($ini->appearence)) unset($ini->appearence);
              if($ini->save()) {
                $result = self::returnSuccess();
              } else {
                $result = self::returnError('danger', 'Не удалось сохранить настройки стиля');
              }
            } else {
              $result = self::returnError('danger', 'Отказано в доступе');
            }
          } break;
          default: {
            $result = self::returnError('danger', 'Неверно указан тип сохраняемых настроек');
          }
        }
      } break;
      case "templates-get":{
        $templates = array();
        $modtemplates = getModuleByClass('core\AppearenceTemplate');
        if($modtemplates) {
            foreach($modtemplates as $template) {
                $templates[] = $template->info();
            }
        }
        $result = self::returnResult($templates);
      } break;
    }
    return $result;
  }

  public static function getCurrentSettings($asdefaults = false) {
    $result = array();
    $result['link'] = 'rgb(0, 140, 186)';
    $result['primary'] = 'rgb(0, 140, 186)';
    $result['secondary'] = 'rgb(238, 238, 238)';
    $result['success'] = 'rgb(67, 172, 106)';
    $result['warning'] = 'rgb(233, 144, 2)';
    $result['danger'] = 'rgb(240, 65, 36)';
    $result['info'] = 'rgb(91, 192, 222)';
    $result['light'] = 'rgb(229, 229, 229)';
    $result['dark'] = 'rgb(34, 34, 34)';
    $result['link-hover'] = 'rgb(0, 117, 155)';
    $result['primary-hover'] = 'rgb(0, 117, 155)';
    $result['secondary-hover'] = 'rgb(207, 207, 207)';
    $result['success-hover'] = 'rgb(55, 141, 87)';
    $result['warning-hover'] = 'rgb(202, 125, 2)';
    $result['danger-hover'] = 'rgb(209, 57, 31)';
    $result['info-hover'] = 'rgb(78, 166, 191)';
    $result['light-hover'] = 'rgb(198, 198, 198)';
    $result['dark-hover'] = 'rgb(3, 3, 3)';
    $result['link-active'] = 'rgb(0, 107, 143)';
    $result['primary-active'] = 'rgb(0, 107, 143)';
    $result['secondary-active'] = 'rgb(195, 195, 195)';
    $result['success-active'] = 'rgb(50, 129, 79)';
    $result['warning-active'] = 'rgb(190, 117, 2)';
    $result['danger-active'] = 'rgb(197, 53, 29)';
    $result['info-active'] = 'rgb(73, 155, 179)';
    $result['light-active'] = 'rgb(186, 186, 186)';
    $result['dark-active'] = 'rgb(0, 0, 0)';
    $result['primary-border'] = 'rgb(0, 96, 127)';
    $result['secondary-border'] = 'rgb(179, 179, 179)';
    $result['success-border'] = 'rgb(44, 113, 70)';
    $result['warning-border'] = 'rgb(174, 108, 1)';
    $result['danger-border'] = 'rgb(181, 49, 27)';
    $result['info-border'] = 'rgb(67, 141, 163)';
    $result['light-border'] = 'rgb(170, 170, 170)';
    $result['dark-border'] = 'rgb(0, 0, 0)';
    $result['primary-btn'] = 'rgb(0, 125, 166)';
    $result['secondary-btn'] = 'rgb(218, 218, 218)';
    $result['success-btn'] = 'rgb(59, 152, 93)';
    $result['warning-btn'] = 'rgb(213, 131, 2)';
    $result['danger-btn'] = 'rgb(220, 59, 33)';
    $result['info-btn'] = 'rgb(83, 174, 202)';
    $result['light-btn'] = 'rgb(209, 209, 209)';
    $result['dark-btn'] = 'rgb(14, 14, 14)';
    $result['primary-btn-hover'] = 'rgb(0, 115, 153)';
    $result['secondary-btn-hover'] = 'rgb(205, 205, 205)';
    $result['success-btn-hover'] = 'rgb(54, 139, 86)';
    $result['warning-btn-hover'] = 'rgb(200, 124, 2)';
    $result['danger-btn-hover'] = 'rgb(207, 56, 31)';
    $result['info-btn-hover'] = 'rgb(77, 163, 189)';
    $result['light-btn-hover'] = 'rgb(196, 196, 196)';
    $result['dark-btn-hover'] = 'rgb(1, 1, 1)';
    $result['primary-focus'] = 'rgb(0, 182, 241)';
    $result['secondary-focus'] = 'rgb(252, 252, 252)';
    $result['success-focus'] = 'rgb(130, 238, 170)';
    $result['warning-focus'] = 'rgb(251, 155, 3)';
    $result['danger-focus'] = 'rgb(252, 81, 53)';
    $result['info-focus'] = 'rgb(143, 224, 248)';
    $result['light-focus'] = 'rgb(250, 250, 250)';
    $result['dark-focus'] = 'rgb(211, 211, 211)';
    $result['shadow-primary'] = 'rgba(0, 140, 186, 0.25)';
    $result['shadow-secondary'] = 'rgba(238, 238, 238, 0.25)';
    $result['shadow-success'] = 'rgba(67, 172, 106, 0.25)';
    $result['shadow-warning'] = 'rgba(233, 144, 2, 0.25)';
    $result['shadow-danger'] = 'rgba(240, 65, 36, 0.25)';
    $result['shadow-info'] = 'rgba(91, 192, 222, 0.25)';
    $result['shadow-light'] = 'rgba(229, 229, 229, 0.25)';
    $result['shadow-dark'] = 'rgba(34, 34, 34, 0.25)';
    $result['shadow-primary-focus'] = 'rgba(0, 140, 186, 0.5)';
    $result['shadow-secondary-focus'] = 'rgba(238, 238, 238, 0.5)';
    $result['shadow-success-focus'] = 'rgba(67, 172, 106, 0.5)';
    $result['shadow-warning-focus'] = 'rgba(233, 144, 2, 0.5)';
    $result['shadow-danger-focus'] = 'rgba(240, 65, 36, 0.5)';
    $result['shadow-info-focus'] = 'rgba(91, 192, 222, 0.5)';
    $result['shadow-light-focus'] = 'rgba(229, 229, 229, 0.5)';
    $result['shadow-dark-focus'] = 'rgba(34, 34, 34, 0.5)';
    $result['shadow-primary-btn'] = 'rgba(29, 155, 196, 0.5)';
    $result['shadow-secondary-btn'] = 'rgba(248, 248, 248, 0.5)';
    $result['shadow-success-btn'] = 'rgba(98, 182, 129, 0.5)';
    $result['shadow-warning-btn'] = 'rgba(243, 164, 39, 0.5)';
    $result['shadow-danger-btn'] = 'rgba(250, 100, 75, 0.5)';
    $result['shadow-info-btn'] = 'rgba(130, 209, 232, 0.5)';
    $result['shadow-light-btn'] = 'rgba(239, 239, 239, 0.5)';
    $result['shadow-dark-btn'] = 'rgba(44, 44, 44, 0.5)';
    $result['alert-primary'] = 'rgb(204, 235, 245)';
    $result['alert-secondary'] = 'rgb(253, 253, 253)';
    $result['alert-success'] = 'rgb(218, 243, 228)';
    $result['alert-warning'] = 'rgb(252, 236, 210)';
    $result['alert-danger'] = 'rgb(253, 222, 217)';
    $result['alert-info'] = 'rgb(226, 245, 250)';
    $result['alert-light'] = 'rgb(251, 251, 251)';
    $result['alert-dark'] = 'rgb(223, 223, 223)';
    $result['primary-text'] = 'rgb(0, 70, 93)';
    $result['secondary-text'] = 'rgb(119, 119, 119)';
    $result['success-text'] = 'rgb(34, 86, 53)';
    $result['warning-text'] = 'rgb(117, 72, 1)';
    $result['danger-text'] = 'rgb(120, 33, 18)';
    $result['info-text'] = 'rgb(46, 96, 111)';
    $result['light-text'] = 'rgb(114, 114, 114)';
    $result['dark-text'] = 'rgb(17, 17, 17)';
    $result['primary-link-text'] = 'rgb(0, 47, 62)';
    $result['secondary-link-text'] = 'rgb(79, 79, 79)';
    $result['success-link-text'] = 'rgb(22, 57, 35)';
    $result['warning-link-text'] = 'rgb(78, 48, 1)';
    $result['danger-link-text'] = 'rgb(80, 22, 12)';
    $result['info-link-text'] = 'rgb(30, 64, 74)';
    $result['light-link-text'] = 'rgb(76, 76, 76)';
    $result['dark-link-text'] = 'rgb(11, 11, 11)';
    $result['primary-hover-text'] = 'rgb(0, 112, 149)';
    $result['secondary-hover-text'] = 'rgb(190, 190, 190)';
    $result['success-hover-text'] = 'rgb(54, 138, 85)';
    $result['warning-hover-text'] = 'rgb(186, 115, 2)';
    $result['danger-hover-text'] = 'rgb(192, 52, 29)';
    $result['info-hover-text'] = 'rgb(73, 154, 178)';
    $result['light-hover-text'] = 'rgb(183, 183, 183)';
    $result['dark-hover-text'] = 'rgb(27, 27, 27)';
    $result['tooltip-primary'] = 'rgba(0, 140, 186, 0.9)';
    $result['tooltip-secondary'] = 'rgba(238, 238, 238, 0.9)';
    $result['tooltip-success'] = 'rgba(67, 172, 106, 0.9)';
    $result['tooltip-warning'] = 'rgba(233, 144, 2, 0.9)';
    $result['tooltip-danger'] = 'rgba(240, 65, 36, 0.9)';
    $result['tooltip-info'] = 'rgba(91, 192, 222, 0.9)';
    $result['tooltip-light'] = 'rgba(229, 229, 229, 0.9)';
    $result['tooltip-dark'] = 'rgba(34, 34, 34, 0.9)';
    $result['table-primary-cell'] = 'rgb(175, 228, 245)';
    $result['table-secondary-cell'] = 'rgb(253, 253, 253)';
    $result['table-success-cell'] = 'rgb(201, 243, 216)';
    $result['table-warning-cell'] = 'rgb(252, 224, 181)';
    $result['table-danger-cell'] = 'rgb(253, 200, 191)';
    $result['table-info-cell'] = 'rgb(208, 241, 250)';
    $result['table-light-cell'] = 'rgb(251, 251, 251)';
    $result['table-dark-cell'] = 'rgb(223, 223, 223)';
    $result['table-primary-head'] = 'rgb(123, 196, 221)';
    $result['table-secondary-head'] = 'rgb(246, 246, 246)';
    $result['table-success-head'] = 'rgb(156, 213, 177)';
    $result['table-warning-head'] = 'rgb(244, 203, 136)';
    $result['table-danger-head'] = 'rgb(248, 167, 154)';
    $result['table-info-head'] = 'rgb(176, 224, 239)';
    $result['table-light-head'] = 'rgb(242, 242, 242)';
    $result['table-dark-head'] = 'rgb(145, 145, 145)';
    $result['table-primary-hover'] = 'rgb(149, 216, 238)';
    $result['table-secondary-hover'] = 'rgb(251, 251, 251)';
    $result['table-success-hover'] = 'rgb(181, 234, 201)';
    $result['table-warning-hover'] = 'rgb(250, 214, 157)';
    $result['table-danger-hover'] = 'rgb(251, 183, 171)';
    $result['table-info-hover'] = 'rgb(192, 234, 247)';
    $result['table-light-hover'] = 'rgb(248, 248, 248)';
    $result['table-dark-hover'] = 'rgb(200, 200, 200)';
    if(!$asdefaults) {
      $ini = new \INIProcessor('/etc/asterisk/manager.conf');
      $login = $_SESSION['login'];
      if(isset($ini->$login)&&isset($ini->$login->link)) {
        foreach($ini->$login as $key => $value) {
          $result[$key] = (string) $value;
        }
      } else {
        unset($ini);
        $role = self::_getRole();
        $link = self::getDB('appearence/'.$role, 'link');
        if(!empty($link)) {
          foreach(array_keys($result) as $key) {
            $result[$key] = self::getDB('appearence/'.$role, $key);
          }
        } else {
          $ini = new \INIProcessor('/etc/asterisk/asterisk.conf');
          if(isset($ini->appearence)&&isset($ini->appearence->link)) {
            foreach($ini->appearence as $key => $value) {
              $result[$key] = (string) $value;
            }
          }
          unset($ini);
        }
      }
    }
    return $result;
  }

  public function scripts() {
    ?>
      <script>
      var templates = null;
      var templatedata = null;
      var savesystem = false;
      var savegroup = false;
      var savegroupbtn = null;
      var savesystembtn = null;
      var resetgroupbtn = null;
      var resetsystembtn = null;
      var defaultsettings = [];
      
      function getColor(code) {
        return getComputedStyle(document.documentElement).getPropertyValue('--'+code).trim();
      }

      function setColor(code, color) {
        document.documentElement.style.setProperty('--'+code, color);
      }

      function loadData() {
        sendRequest('settings-get').success(function(data) {
          savesystem = data.savesystem;
          savegroup = data.savegroup;
          defaultsettings = data.defaults;
        });
      }

      function sendData(savetype) {
        var data = {};
        data.savetype = savetype;
        data['link'] = getColor('link');
        data['primary'] = getColor('primary');
        data['secondary'] = getColor('secondary');
        data['success'] = getColor('success');
        data['warning'] = getColor('warning');
        data['danger'] = getColor('danger');
        data['info'] = getColor('info');
        data['light'] = getColor('light');
        data['dark'] = getColor('dark');
        data['link-hover'] = getColor('link-hover');
        data['primary-hover'] = getColor('primary-hover');
        data['secondary-hover'] = getColor('secondary-hover');
        data['success-hover'] = getColor('success-hover');
        data['warning-hover'] = getColor('warning-hover');
        data['danger-hover'] = getColor('danger-hover');
        data['info-hover'] = getColor('info-hover');
        data['light-hover'] = getColor('light-hover');
        data['dark-hover'] = getColor('dark-hover');
        data['link-active'] = getColor('link-active');
        data['primary-active'] = getColor('primary-active');
        data['secondary-active'] = getColor('secondary-active');
        data['success-active'] = getColor('success-active');
        data['warning-active'] = getColor('warning-active');
        data['danger-active'] = getColor('danger-active');
        data['info-active'] = getColor('info-active');
        data['light-active'] = getColor('light-active');
        data['dark-active'] = getColor('dark-active');
        data['primary-border'] = getColor('primary-border');
        data['secondary-border'] = getColor('secondary-border');
        data['success-border'] = getColor('success-border');
        data['warning-border'] = getColor('warning-border');
        data['danger-border'] = getColor('danger-border');
        data['info-border'] = getColor('info-border');
        data['light-border'] = getColor('light-border');
        data['dark-border'] = getColor('dark-border');
        data['primary-btn'] = getColor('primary-btn');
        data['secondary-btn'] = getColor('secondary-btn');
        data['success-btn'] = getColor('success-btn');
        data['warning-btn'] = getColor('warning-btn');
        data['danger-btn'] = getColor('danger-btn');
        data['info-btn'] = getColor('info-btn');
        data['light-btn'] = getColor('light-btn');
        data['dark-btn'] = getColor('dark-btn');
        data['primary-btn-hover'] = getColor('primary-btn-hover');
        data['secondary-btn-hover'] = getColor('secondary-btn-hover');
        data['success-btn-hover'] = getColor('success-btn-hover');
        data['warning-btn-hover'] = getColor('warning-btn-hover');
        data['danger-btn-hover'] = getColor('danger-btn-hover');
        data['info-btn-hover'] = getColor('info-btn-hover');
        data['light-btn-hover'] = getColor('light-btn-hover');
        data['dark-btn-hover'] = getColor('dark-btn-hover');
        data['primary-focus'] = getColor('primary-focus');
        data['secondary-focus'] = getColor('secondary-focus');
        data['success-focus'] = getColor('success-focus');
        data['warning-focus'] = getColor('warning-focus');
        data['danger-focus'] = getColor('danger-focus');
        data['info-focus'] = getColor('info-focus');
        data['light-focus'] = getColor('light-focus');
        data['dark-focus'] = getColor('dark-focus');
        data['shadow-primary'] = getColor('shadow-primary');
        data['shadow-secondary'] = getColor('shadow-secondary');
        data['shadow-success'] = getColor('shadow-success');
        data['shadow-warning'] = getColor('shadow-warning');
        data['shadow-danger'] = getColor('shadow-danger');
        data['shadow-info'] = getColor('shadow-info');
        data['shadow-light'] = getColor('shadow-light');
        data['shadow-dark'] = getColor('shadow-dark');
        data['shadow-primary-focus'] = getColor('shadow-primary-focus');
        data['shadow-secondary-focus'] = getColor('shadow-secondary-focus');
        data['shadow-success-focus'] = getColor('shadow-success-focus');
        data['shadow-warning-focus'] = getColor('shadow-warning-focus');
        data['shadow-danger-focus'] = getColor('shadow-danger-focus');
        data['shadow-info-focus'] = getColor('shadow-info-focus');
        data['shadow-light-focus'] = getColor('shadow-light-focus');
        data['shadow-dark-focus'] = getColor('shadow-dark-focus');
        data['shadow-primary-btn'] = getColor('shadow-primary-btn');
        data['shadow-secondary-btn'] = getColor('shadow-secondary-btn');
        data['shadow-success-btn'] = getColor('shadow-success-btn');
        data['shadow-warning-btn'] = getColor('shadow-warning-btn');
        data['shadow-danger-btn'] = getColor('shadow-danger-btn');
        data['shadow-info-btn'] = getColor('shadow-info-btn');
        data['shadow-light-btn'] = getColor('shadow-light-btn');
        data['shadow-dark-btn'] = getColor('shadow-dark-btn');
        data['alert-primary'] = getColor('alert-primary');
        data['alert-secondary'] = getColor('alert-secondary');
        data['alert-success'] = getColor('alert-success');
        data['alert-warning'] = getColor('alert-warning');
        data['alert-danger'] = getColor('alert-danger');
        data['alert-info'] = getColor('alert-info');
        data['alert-light'] = getColor('alert-light');
        data['alert-dark'] = getColor('alert-dark');
        data['primary-text'] = getColor('primary-text');
        data['secondary-text'] = getColor('secondary-text');
        data['success-text'] = getColor('success-text');
        data['warning-text'] = getColor('warning-text');
        data['danger-text'] = getColor('danger-text');
        data['info-text'] = getColor('info-text');
        data['light-text'] = getColor('light-text');
        data['dark-text'] = getColor('dark-text');
        data['primary-link-text'] = getColor('primary-link-text');
        data['secondary-link-text'] = getColor('secondary-link-text');
        data['success-link-text'] = getColor('success-link-text');
        data['warning-link-text'] = getColor('warning-link-text');
        data['danger-link-text'] = getColor('danger-link-text');
        data['info-link-text'] = getColor('info-link-text');
        data['light-link-text'] = getColor('light-link-text');
        data['dark-link-text'] = getColor('dark-link-text');
        data['primary-hover-text'] = getColor('primary-hover-text');
        data['secondary-hover-text'] = getColor('secondary-hover-text');
        data['success-hover-text'] = getColor('success-hover-text');
        data['warning-hover-text'] = getColor('warning-hover-text');
        data['danger-hover-text'] = getColor('danger-hover-text');
        data['info-hover-text'] = getColor('info-hover-text');
        data['light-hover-text'] = getColor('light-hover-text');
        data['dark-hover-text'] = getColor('dark-hover-text');
        data['tooltip-primary'] = getColor('tooltip-primary');
        data['tooltip-secondary'] = getColor('tooltip-secondary');
        data['tooltip-success'] = getColor('tooltip-success');
        data['tooltip-warning'] = getColor('tooltip-warning');
        data['tooltip-danger'] = getColor('tooltip-danger');
        data['tooltip-info'] = getColor('tooltip-info');
        data['tooltip-light'] = getColor('tooltip-light');
        data['tooltip-dark'] = getColor('tooltip-dark');
        data['table-primary-cell'] = getColor('table-primary-cell');
        data['table-secondary-cell'] = getColor('table-secondary-cell');
        data['table-success-cell'] = getColor('table-success-cell');
        data['table-warning-cell'] = getColor('table-warning-cell');
        data['table-danger-cell'] = getColor('table-danger-cell');
        data['table-info-cell'] = getColor('table-info-cell');
        data['table-light-cell'] = getColor('table-light-cell');
        data['table-dark-cell'] = getColor('table-dark-cell');
        data['table-primary-head'] = getColor('table-primary-head');
        data['table-secondary-head'] = getColor('table-secondary-head');
        data['table-success-head'] = getColor('table-success-head');
        data['table-warning-head'] = getColor('table-warning-head');
        data['table-danger-head'] = getColor('table-danger-head');
        data['table-info-head'] = getColor('table-info-head');
        data['table-light-head'] = getColor('table-light-head');
        data['table-dark-head'] = getColor('table-dark-head');
        data['table-primary-hover'] = getColor('table-primary-hover');
        data['table-secondary-hover'] = getColor('table-secondary-hover');
        data['table-success-hover'] = getColor('table-success-hover');
        data['table-warning-hover'] = getColor('table-warning-hover');
        data['table-danger-hover'] = getColor('table-danger-hover');
        data['table-info-hover'] = getColor('table-info-hover');
        data['table-light-hover'] = getColor('table-light-hover');
        data['table-dark-hover'] = getColor('table-dark-hover');

        sendRequest('settings-set', data).success(function() {
          return true;
        });
      }

      function resetData(resettype) {
        var data = {};
        data.resettype = resettype;
        sendRequest('settings-reset', data).success(function() {
          for(param in defaultsettings) {
            setColor(param, defaultsettings[param]);
            cPrimary.setValue(getColor('primary'));
            cSecondary.setValue(getColor('secondary'));
            cLight.setValue(getColor('light'));
            cDark.setValue(getColor('dark'));
            cSuccess.setValue(getColor('success'));
            cInfo.setValue(getColor('info'));
            cWarning.setValue(getColor('warning'));
            cDanger.setValue(getColor('danger'));
          }
          return true;
        });
      }

      function loadTemplates() {
        sendRequest('templates-get').success(function(data) {
          if(data.length>0) { 
            templates.setValue({value: data, clean: true});
            templates.show();
          } else {
            templates.hide();
          }
        });
      }

      function sbapply(e) {
        if(savegroup||savesystem) {
          if(savegroup) savegroupbtn.show(); else savegroupbtn.hide();
          if(savesystem) savesystembtn.show(); else savesystembtn.hide();
          savedialog.show();
        } else {
          sendData(0);
        }
      }

      function sbreset(e) {
        if(savegroup||savesystem) {
          if(savegroup) resetgroupbtn.show(); else resetgroupbtn.hide();
          if(savesystem) resetsystembtn.show(); else resetsystembtn.hide();
          resetdialog.show();
        } else {
          resetData(0);
        }
      }

      var cPrimary = null;
      var cSecondary = null;
      var cSuccess = null;
      var cWarning = null;
      var cDanger = null;
      var cInfo = null;
      var cLight = null;
      var cDark = null;

      function changecolormap(evt) {
        clPrimary = cPrimary.getValue();
        clSecondary = cSecondary.getValue();
        clSuccess = cSuccess.getValue();
        clWarning = cWarning.getValue();
        clDanger = cDanger.getValue();
        clInfo = cInfo.getValue();
        clLight = cLight.getValue();
        clDark = cDark.getValue();

        setColor('link', clPrimary.toString());
        setColor('primary', clPrimary.toString());
        setColor('secondary', clSecondary.toString());
        setColor('success', clSuccess.toString());
        setColor('warning', clWarning.toString());
        setColor('danger', clDanger.toString());
        setColor('info', clInfo.toString());
        setColor('light', clLight.toString());
        setColor('dark', clDark.toString());

        var clPrimaryHover=clPrimary.getClone();
        clPrimaryHover.saturation+=0;
        clPrimaryHover.value-=12;
        var clSecondaryHover=clSecondary.getClone();
        clSecondaryHover.saturation+=0;
        clSecondaryHover.value-=12;
        var clSuccessHover=clSuccess.getClone();
        clSuccessHover.saturation+=0;
        clSuccessHover.value-=12;
        var clWarningHover=clWarning.getClone();
        clWarningHover.saturation+=0;
        clWarningHover.value-=12;
        var clDangerHover=clDanger.getClone();
        clDangerHover.saturation+=0;
        clDangerHover.value-=12;
        var clInfoHover=clInfo.getClone();
        clInfoHover.saturation+=0;
        clInfoHover.value-=12;
        var clLightHover=clLight.getClone();
        clLightHover.saturation+=0;
        clLightHover.value-=12;
        var clDarkHover=clDark.getClone();
        clDarkHover.saturation+=0;
        clDarkHover.value-=12;

        setColor('link-hover', clPrimaryHover.toString());
        setColor('primary-hover', clPrimaryHover.toString());
        setColor('secondary-hover', clSecondaryHover.toString());
        setColor('success-hover', clSuccessHover.toString());
        setColor('warning-hover', clWarningHover.toString());
        setColor('danger-hover', clDangerHover.toString());
        setColor('info-hover', clInfoHover.toString());
        setColor('light-hover', clLightHover.toString());
        setColor('dark-hover', clDarkHover.toString());

        var clPrimaryActive=clPrimary.getClone();
        clPrimaryActive.saturation+=0;
        clPrimaryActive.value-=17;
        var clSecondaryActive=clSecondary.getClone();
        clSecondaryActive.saturation+=0;
        clSecondaryActive.value-=17;
        var clSuccessActive=clSuccess.getClone();
        clSuccessActive.saturation+=0;
        clSuccessActive.value-=17;
        var clWarningActive=clWarning.getClone();
        clWarningActive.saturation+=0;
        clWarningActive.value-=17;
        var clDangerActive=clDanger.getClone();
        clDangerActive.saturation+=0;
        clDangerActive.value-=17;
        var clInfoActive=clInfo.getClone();
        clInfoActive.saturation+=0;
        clInfoActive.value-=17;
        var clLightActive=clLight.getClone();
        clLightActive.saturation+=0;
        clLightActive.value-=17;
        var clDarkActive=clDark.getClone();
        clDarkActive.saturation+=0;
        clDarkActive.value-=17;

        setColor('link-active', clPrimaryActive.toString());
        setColor('primary-active', clPrimaryActive.toString());
        setColor('secondary-active', clSecondaryActive.toString());
        setColor('success-active', clSuccessActive.toString());
        setColor('warning-active', clWarningActive.toString());
        setColor('danger-active', clDangerActive.toString());
        setColor('info-active', clInfoActive.toString());
        setColor('light-active', clLightActive.toString());
        setColor('dark-active', clDarkActive.toString());

        var clPrimaryBorder=clPrimary.getClone();
        clPrimaryBorder.saturation+=0;
        clPrimaryBorder.value-=23;
        var clSecondaryBorder=clSecondary.getClone();
        clSecondaryBorder.saturation+=0;
        clSecondaryBorder.value-=23;
        var clSuccessBorder=clSuccess.getClone();
        clSuccessBorder.saturation+=0;
        clSuccessBorder.value-=23;
        var clWarningBorder=clWarning.getClone();
        clWarningBorder.saturation+=0;
        clWarningBorder.value-=23;
        var clDangerBorder=clDanger.getClone();
        clDangerBorder.saturation+=0;
        clDangerBorder.value-=23;
        var clInfoBorder=clInfo.getClone();
        clInfoBorder.saturation+=0;
        clInfoBorder.value-=23;
        var clLightBorder=clLight.getClone();
        clLightBorder.saturation+=0;
        clLightBorder.value-=23;
        var clDarkBorder=clDark.getClone();
        clDarkBorder.saturation+=0;
        clDarkBorder.value-=23;

        setColor('primary-border', clPrimaryBorder.toString());
        setColor('secondary-border', clSecondaryBorder.toString());
        setColor('success-border', clSuccessBorder.toString());
        setColor('warning-border', clWarningBorder.toString());
        setColor('danger-border', clDangerBorder.toString());
        setColor('info-border', clInfoBorder.toString());
        setColor('light-border', clLightBorder.toString());
        setColor('dark-border', clDarkBorder.toString());

        var clPrimaryBtn=clPrimary.getClone();
        clPrimaryBtn.saturation+=0;
        clPrimaryBtn.value-=8;
        var clSecondaryBtn=clSecondary.getClone();
        clSecondaryBtn.saturation+=0;
        clSecondaryBtn.value-=8;
        var clSuccessBtn=clSuccess.getClone();
        clSuccessBtn.saturation+=0;
        clSuccessBtn.value-=8;
        var clWarningBtn=clWarning.getClone();
        clWarningBtn.saturation+=0;
        clWarningBtn.value-=8;
        var clDangerBtn=clDanger.getClone();
        clDangerBtn.saturation+=0;
        clDangerBtn.value-=8;
        var clInfoBtn=clInfo.getClone();
        clInfoBtn.saturation+=0;
        clInfoBtn.value-=8;
        var clLightBtn=clLight.getClone();
        clLightBtn.saturation+=0;
        clLightBtn.value-=8;
        var clDarkBtn=clDark.getClone();
        clDarkBtn.saturation+=0;
        clDarkBtn.value-=8;

        setColor('primary-btn', clPrimaryBtn.toString());
        setColor('secondary-btn', clSecondaryBtn.toString());
        setColor('success-btn', clSuccessBtn.toString());
        setColor('warning-btn', clWarningBtn.toString());
        setColor('danger-btn', clDangerBtn.toString());
        setColor('info-btn', clInfoBtn.toString());
        setColor('light-btn', clLightBtn.toString());
        setColor('dark-btn', clDarkBtn.toString());

        var clPrimaryBtnHover=clPrimary.getClone();
        clPrimaryBtnHover.saturation+=0;
        clPrimaryBtnHover.value-=13;
        var clSecondaryBtnHover=clSecondary.getClone();
        clSecondaryBtnHover.saturation+=0;
        clSecondaryBtnHover.value-=13;
        var clSuccessBtnHover=clSuccess.getClone();
        clSuccessBtnHover.saturation+=0;
        clSuccessBtnHover.value-=13;
        var clWarningBtnHover=clWarning.getClone();
        clWarningBtnHover.saturation+=0;
        clWarningBtnHover.value-=13;
        var clDangerBtnHover=clDanger.getClone();
        clDangerBtnHover.saturation+=0;
        clDangerBtnHover.value-=13;
        var clInfoBtnHover=clInfo.getClone();
        clInfoBtnHover.saturation+=0;
        clInfoBtnHover.value-=13;
        var clLightBtnHover=clLight.getClone();
        clLightBtnHover.saturation+=0;
        clLightBtnHover.value-=13;
        var clDarkBtnHover=clDark.getClone();
        clDarkBtnHover.saturation+=0;
        clDarkBtnHover.value-=13;

        setColor('primary-btn-hover', clPrimaryBtnHover.toString());
        setColor('secondary-btn-hover', clSecondaryBtnHover.toString());
        setColor('success-btn-hover', clSuccessBtnHover.toString());
        setColor('warning-btn-hover', clWarningBtnHover.toString());
        setColor('danger-btn-hover', clDangerBtnHover.toString());
        setColor('info-btn-hover', clInfoBtnHover.toString());
        setColor('light-btn-hover', clLightBtnHover.toString());
        setColor('dark-btn-hover', clDarkBtnHover.toString());

        var clPrimaryFocus=clPrimary.getClone();
        clPrimaryFocus.saturation-=(100-clPrimaryFocus.saturation)*2/5;
        clPrimaryFocus.value+=(100-clPrimaryFocus.value)*4/5;
        var clSecondaryFocus=clSecondary.getClone();
        clSecondaryFocus.saturation-=(100-clSecondaryFocus.saturation)*2/5;
        clSecondaryFocus.value+=(100-clSecondaryFocus.value)*4/5;
        var clSuccessFocus=clSuccess.getClone();
        clSuccessFocus.saturation-=(100-clSuccessFocus.saturation)*2/5;
        clSuccessFocus.value+=(100-clSuccessFocus.value)*4/5;
        var clWarningFocus=clWarning.getClone();
        clWarningFocus.saturation-=(100-clWarningFocus.saturation)*2/5;
        clWarningFocus.value+=(100-clWarningFocus.value)*4/5;
        var clDangerFocus=clDanger.getClone();
        clDangerFocus.saturation-=(100-clDangerFocus.saturation)*2/5;
        clDangerFocus.value+=(100-clDangerFocus.value)*4/5;
        var clInfoFocus=clInfo.getClone();
        clInfoFocus.saturation-=(100-clInfoFocus.saturation)*2/5;
        clInfoFocus.value+=(100-clInfoFocus.value)*4/5;
        var clLightFocus=clLight.getClone();
        clLightFocus.saturation-=(100-clLightFocus.saturation)*2/5;
        clLightFocus.value+=(100-clLightFocus.value)*4/5;
        var clDarkFocus=clDark.getClone();
        clDarkFocus.saturation-=(100-clDarkFocus.saturation)*2/5;
        clDarkFocus.value+=(100-clDarkFocus.value)*4/5;

        setColor('primary-focus', clPrimaryFocus.toString());
        setColor('secondary-focus', clSecondaryFocus.toString());
        setColor('success-focus', clSuccessFocus.toString());
        setColor('warning-focus', clWarningFocus.toString());
        setColor('danger-focus', clDangerFocus.toString());
        setColor('info-focus', clInfoFocus.toString());
        setColor('light-focus', clLightFocus.toString());
        setColor('dark-focus', clDarkFocus.toString());

        var clPrimaryShadow=clPrimary.getClone();
        clPrimaryShadow.alpha=0.25;
        var clSecondaryShadow=clSecondary.getClone();
        clSecondaryShadow.alpha=0.25;
        var clSuccessShadow=clSuccess.getClone();
        clSuccessShadow.alpha=0.25;
        var clWarningShadow=clWarning.getClone();
        clWarningShadow.alpha=0.25;
        var clDangerShadow=clDanger.getClone();
        clDangerShadow.alpha=0.25;
        var clInfoShadow=clInfo.getClone();
        clInfoShadow.alpha=0.25;
        var clLightShadow=clLight.getClone();
        clLightShadow.alpha=0.25;
        var clDarkShadow=clDark.getClone();
        clDarkShadow.alpha=0.25;

        setColor('shadow-primary', clPrimaryShadow.toString());
        setColor('shadow-secondary', clSecondaryShadow.toString());
        setColor('shadow-success', clSuccessShadow.toString());
        setColor('shadow-warning', clWarningShadow.toString());
        setColor('shadow-danger', clDangerShadow.toString());
        setColor('shadow-info', clInfoShadow.toString());
        setColor('shadow-light', clLightShadow.toString());
        setColor('shadow-dark', clDarkShadow.toString());

        var clPrimaryShadowFocus=clPrimary.getClone();
        clPrimaryShadowFocus.alpha=0.5;
        var clSecondaryShadowFocus=clSecondary.getClone();
        clSecondaryShadowFocus.alpha=0.5;
        var clSuccessShadowFocus=clSuccess.getClone();
        clSuccessShadowFocus.alpha=0.5;
        var clWarningShadowFocus=clWarning.getClone();
        clWarningShadowFocus.alpha=0.5;
        var clDangerShadowFocus=clDanger.getClone();
        clDangerShadowFocus.alpha=0.5;
        var clInfoShadowFocus=clInfo.getClone();
        clInfoShadowFocus.alpha=0.5;
        var clLightShadowFocus=clLight.getClone();
        clLightShadowFocus.alpha=0.5;
        var clDarkShadowFocus=clDark.getClone();
        clDarkShadowFocus.alpha=0.5;

        setColor('shadow-primary-focus', clPrimaryShadowFocus.toString());
        setColor('shadow-secondary-focus', clSecondaryShadowFocus.toString());
        setColor('shadow-success-focus', clSuccessShadowFocus.toString());
        setColor('shadow-warning-focus', clWarningShadowFocus.toString());
        setColor('shadow-danger-focus', clDangerShadowFocus.toString());
        setColor('shadow-info-focus', clInfoShadowFocus.toString());
        setColor('shadow-light-focus', clLightShadowFocus.toString());
        setColor('shadow-dark-focus', clDarkShadowFocus.toString());

        var clPrimaryShadowBtn=clPrimary.getClone();
        clPrimaryShadowBtn.saturation-=15;
        clPrimaryShadowBtn.value+=4;
        clPrimaryShadowBtn.alpha=0.5;
        var clSecondaryShadowBtn=clSecondary.getClone();
        clSecondaryShadowBtn.saturation-=15;
        clSecondaryShadowBtn.value+=4;
        clSecondaryShadowBtn.alpha=0.5;
        var clSuccessShadowBtn=clSuccess.getClone();
        clSuccessShadowBtn.saturation-=15;
        clSuccessShadowBtn.value+=4;
        clSuccessShadowBtn.alpha=0.5;
        var clWarningShadowBtn=clWarning.getClone();
        clWarningShadowBtn.saturation-=15;
        clWarningShadowBtn.value+=4;
        clWarningShadowBtn.alpha=0.5;
        var clDangerShadowBtn=clDanger.getClone();
        clDangerShadowBtn.saturation-=15;
        clDangerShadowBtn.value+=4;
        clDangerShadowBtn.alpha=0.5;
        var clInfoShadowBtn=clInfo.getClone();
        clInfoShadowBtn.saturation-=15;
        clInfoShadowBtn.value+=4;
        clInfoShadowBtn.alpha=0.5;
        var clLightShadowBtn=clLight.getClone();
        clLightShadowBtn.saturation-=15;
        clLightShadowBtn.value+=4;
        clLightShadowBtn.alpha=0.5;
        var clDarkShadowBtn=clDark.getClone();
        clDarkShadowBtn.saturation-=15;
        clDarkShadowBtn.value+=4;
        clDarkShadowBtn.alpha=0.5;

        setColor('shadow-primary-btn', clPrimaryShadowBtn.toString());
        setColor('shadow-secondary-btn', clSecondaryShadowBtn.toString());
        setColor('shadow-success-btn', clSuccessShadowBtn.toString());
        setColor('shadow-warning-btn', clWarningShadowBtn.toString());
        setColor('shadow-danger-btn', clDangerShadowBtn.toString());
        setColor('shadow-info-btn', clInfoShadowBtn.toString());
        setColor('shadow-light-btn', clLightShadowBtn.toString());
        setColor('shadow-dark-btn', clDarkShadowBtn.toString());

        var clPrimaryAlert=clPrimary.getClone();
        clPrimaryAlert.saturation-=(clPrimaryAlert.saturation)*5/6;
        clPrimaryAlert.value+=(100-clPrimaryAlert.value)*6/7;
        var clSecondaryAlert=clSecondary.getClone();
        clSecondaryAlert.saturation-=(clSecondaryAlert.saturation)*5/6;
        clSecondaryAlert.value+=(100-clSecondaryAlert.value)*6/7;
        var clSuccessAlert=clSuccess.getClone();
        clSuccessAlert.saturation-=(clSuccessAlert.saturation)*5/6;
        clSuccessAlert.value+=(100-clSuccessAlert.value)*6/7;
        var clWarningAlert=clWarning.getClone();
        clWarningAlert.saturation-=(clWarningAlert.saturation)*5/6;
        clWarningAlert.value+=(100-clWarningAlert.value)*6/7;
        var clDangerAlert=clDanger.getClone();
        clDangerAlert.saturation-=(clDangerAlert.saturation)*5/6;
        clDangerAlert.value+=(100-clDangerAlert.value)*6/7;
        var clInfoAlert=clInfo.getClone();
        clInfoAlert.saturation-=(clInfoAlert.saturation)*5/6;
        clInfoAlert.value+=(100-clInfoAlert.value)*6/7;
        var clLightAlert=clLight.getClone();
        clLightAlert.saturation-=(clLightAlert.saturation)*5/6;
        clLightAlert.value+=(100-clLightAlert.value)*6/7;
        var clDarkAlert=clDark.getClone();
        clDarkAlert.saturation-=(clDarkAlert.saturation)*5/6;
        clDarkAlert.value+=(100-clDarkAlert.value)*6/7;

        setColor('alert-primary', clPrimaryAlert.toString());
        setColor('alert-secondary', clSecondaryAlert.toString());
        setColor('alert-success', clSuccessAlert.toString());
        setColor('alert-warning', clWarningAlert.toString());
        setColor('alert-danger', clDangerAlert.toString());
        setColor('alert-info', clInfoAlert.toString());
        setColor('alert-light', clLightAlert.toString());
        setColor('alert-dark', clDarkAlert.toString());

        var clPrimaryText=clPrimary.getClone();
        clPrimaryText.value-=(clPrimaryText.value)/2;
        var clSecondaryText=clSecondary.getClone();
        clSecondaryText.value-=(clSecondaryText.value)/2;
        var clSuccessText=clSuccess.getClone();
        clSuccessText.value-=(clSuccessText.value)/2;
        var clWarningText=clWarning.getClone();
        clWarningText.value-=(clWarningText.value)/2;
        var clDangerText=clDanger.getClone();
        clDangerText.value-=(clDangerText.value)/2;
        var clInfoText=clInfo.getClone();
        clInfoText.value-=(clInfoText.value)/2;
        var clLightText=clLight.getClone();
        clLightText.value-=(clLightText.value)/2;
        var clDarkText=clDark.getClone();
        clDarkText.value-=(clDarkText.value)/2;

        setColor('primary-text', clPrimaryText.toString());
        setColor('secondary-text', clSecondaryText.toString());
        setColor('success-text', clSuccessText.toString());
        setColor('warning-text', clWarningText.toString());
        setColor('danger-text', clDangerText.toString());
        setColor('info-text', clInfoText.toString());
        setColor('light-text', clLightText.toString());
        setColor('dark-text', clDarkText.toString());

        var clPrimaryLinkText=clPrimary.getClone();
        clPrimaryLinkText.value-=(clPrimaryLinkText.value)*4/6;
        var clSecondaryLinkText=clSecondary.getClone();
        clSecondaryLinkText.value-=(clSecondaryLinkText.value)*4/6;
        var clSuccessLinkText=clSuccess.getClone();
        clSuccessLinkText.value-=(clSuccessLinkText.value)*4/6;
        var clWarningLinkText=clWarning.getClone();
        clWarningLinkText.value-=(clWarningLinkText.value)*4/6;
        var clDangerLinkText=clDanger.getClone();
        clDangerLinkText.value-=(clDangerLinkText.value)*4/6;
        var clInfoLinkText=clInfo.getClone();
        clInfoLinkText.value-=(clInfoLinkText.value)*4/6;
        var clLightLinkText=clLight.getClone();
        clLightLinkText.value-=(clLightLinkText.value)*4/6;
        var clDarkLinkText=clDark.getClone();
        clDarkLinkText.value-=(clDarkLinkText.value)*4/6;

        setColor('primary-link-text', clPrimaryLinkText.toString());
        setColor('secondary-link-text', clSecondaryLinkText.toString());
        setColor('success-link-text', clSuccessLinkText.toString());
        setColor('warning-link-text', clWarningLinkText.toString());
        setColor('danger-link-text', clDangerLinkText.toString());
        setColor('info-link-text', clInfoLinkText.toString());
        setColor('light-link-text', clLightLinkText.toString());
        setColor('dark-link-text', clDarkLinkText.toString());

        var clPrimaryHoverText=clPrimary.getClone();
        clPrimaryHoverText.value-=(clPrimaryHoverText.value)/5;
        var clSecondaryHoverText=clSecondary.getClone();
        clSecondaryHoverText.value-=(clSecondaryHoverText.value)/5;
        var clSuccessHoverText=clSuccess.getClone();
        clSuccessHoverText.value-=(clSuccessHoverText.value)/5;
        var clWarningHoverText=clWarning.getClone();
        clWarningHoverText.value-=(clWarningHoverText.value)/5;
        var clDangerHoverText=clDanger.getClone();
        clDangerHoverText.value-=(clDangerHoverText.value)/5;
        var clInfoHoverText=clInfo.getClone();
        clInfoHoverText.value-=(clInfoHoverText.value)/5;
        var clLightHoverText=clLight.getClone();
        clLightHoverText.value-=(clLightHoverText.value)/5;
        var clDarkHoverText=clDark.getClone();
        clDarkHoverText.value-=(clDarkHoverText.value)/5;

        setColor('primary-hover-text', clPrimaryHoverText.toString());
        setColor('secondary-hover-text', clSecondaryHoverText.toString());
        setColor('success-hover-text', clSuccessHoverText.toString());
        setColor('warning-hover-text', clWarningHoverText.toString());
        setColor('danger-hover-text', clDangerHoverText.toString());
        setColor('info-hover-text', clInfoHoverText.toString());
        setColor('light-hover-text', clLightHoverText.toString());
        setColor('dark-hover-text', clDarkHoverText.toString());

        var clPrimaryTooltip=clPrimary.getClone();
        clPrimaryTooltip.alpha=0.9;
        var clSecondaryTooltip=clSecondary.getClone();
        clSecondaryTooltip.alpha=0.9;
        var clSuccessTooltip=clSuccess.getClone();
        clSuccessTooltip.alpha=0.9;
        var clWarningTooltip=clWarning.getClone();
        clWarningTooltip.alpha=0.9;
        var clDangerTooltip=clDanger.getClone();
        clDangerTooltip.alpha=0.9;
        var clInfoTooltip=clInfo.getClone();
        clInfoTooltip.alpha=0.9;
        var clLightTooltip=clLight.getClone();
        clLightTooltip.alpha=0.9;
        var clDarkTooltip=clDark.getClone();
        clDarkTooltip.alpha=0.9;

        setColor('tooltip-primary', clPrimaryTooltip.toString());
        setColor('tooltip-secondary', clSecondaryTooltip.toString());
        setColor('tooltip-success', clSuccessTooltip.toString());
        setColor('tooltip-warning', clWarningTooltip.toString());
        setColor('tooltip-danger', clDangerTooltip.toString());
        setColor('tooltip-info', clInfoTooltip.toString());
        setColor('tooltip-light', clLightTooltip.toString());
        setColor('tooltip-dark', clDarkTooltip.toString());

        var clPrimaryTableCell=clPrimary.getClone();
        clPrimaryTableCell.saturation-=(clPrimaryTableCell.saturation)*5/7;
        clPrimaryTableCell.value+=(100-clPrimaryTableCell.value)*6/7;
        var clSecondaryTableCell=clSecondary.getClone();
        clSecondaryTableCell.saturation-=(clSecondaryTableCell.saturation)*5/7;
        clSecondaryTableCell.value+=(100-clSecondaryTableCell.value)*6/7;
        var clSuccessTableCell=clSuccess.getClone();
        clSuccessTableCell.saturation-=(clSuccessTableCell.saturation)*5/7;
        clSuccessTableCell.value+=(100-clSuccessTableCell.value)*6/7;
        var clWarningTableCell=clWarning.getClone();
        clWarningTableCell.saturation-=(clWarningTableCell.saturation)*5/7;
        clWarningTableCell.value+=(100-clWarningTableCell.value)*6/7;
        var clDangerTableCell=clDanger.getClone();
        clDangerTableCell.saturation-=(clDangerTableCell.saturation)*5/7;
        clDangerTableCell.value+=(100-clDangerTableCell.value)*6/7;
        var clInfoTableCell=clInfo.getClone();
        clInfoTableCell.saturation-=(clInfoTableCell.saturation)*5/7;
        clInfoTableCell.value+=(100-clInfoTableCell.value)*6/7;
        var clLightTableCell=clLight.getClone();
        clLightTableCell.saturation-=(clLightTableCell.saturation)*5/7;
        clLightTableCell.value+=(100-clLightTableCell.value)*6/7;
        var clDarkTableCell=clDark.getClone();
        clDarkTableCell.saturation-=(clDarkTableCell.saturation)*5/7;
        clDarkTableCell.value+=(100-clDarkTableCell.value)*6/7;

        setColor('table-primary-cell', clPrimaryTableCell.toString());
        setColor('table-secondary-cell', clSecondaryTableCell.toString());
        setColor('table-success-cell', clSuccessTableCell.toString());
        setColor('table-warning-cell', clWarningTableCell.toString());
        setColor('table-danger-cell', clDangerTableCell.toString());
        setColor('table-info-cell', clInfoTableCell.toString());
        setColor('table-light-cell', clLightTableCell.toString());
        setColor('table-dark-cell', clDarkTableCell.toString());

        var clPrimaryTableHead=clPrimary.getClone();
        clPrimaryTableHead.saturation-=(clPrimaryTableHead.saturation)*5/9;
        clPrimaryTableHead.value+=(100-clPrimaryTableHead.value)/2;
        var clSecondaryTableHead=clSecondary.getClone();
        clSecondaryTableHead.saturation-=(clSecondaryTableHead.saturation)*5/9;
        clSecondaryTableHead.value+=(100-clSecondaryTableHead.value)/2;
        var clSuccessTableHead=clSuccess.getClone();
        clSuccessTableHead.saturation-=(clSuccessTableHead.saturation)*5/9;
        clSuccessTableHead.value+=(100-clSuccessTableHead.value)/2;
        var clWarningTableHead=clWarning.getClone();
        clWarningTableHead.saturation-=(clWarningTableHead.saturation)*5/9;
        clWarningTableHead.value+=(100-clWarningTableHead.value)/2;
        var clDangerTableHead=clDanger.getClone();
        clDangerTableHead.saturation-=(clDangerTableHead.saturation)*5/9;
        clDangerTableHead.value+=(100-clDangerTableHead.value)/2;
        var clInfoTableHead=clInfo.getClone();
        clInfoTableHead.saturation-=(clInfoTableHead.saturation)*5/9;
        clInfoTableHead.value+=(100-clInfoTableHead.value)/2;
        var clLightTableHead=clLight.getClone();
        clLightTableHead.saturation-=(clLightTableHead.saturation)*5/9;
        clLightTableHead.value+=(100-clLightTableHead.value)/2;
        var clDarkTableHead=clDark.getClone();
        clDarkTableHead.saturation-=(clDarkTableHead.saturation)*5/9;
        clDarkTableHead.value+=(100-clDarkTableHead.value)/2;

        setColor('table-primary-head', clPrimaryTableHead.toString());
        setColor('table-secondary-head', clSecondaryTableHead.toString());
        setColor('table-success-head', clSuccessTableHead.toString());
        setColor('table-warning-head', clWarningTableHead.toString());
        setColor('table-danger-head', clDangerTableHead.toString());
        setColor('table-info-head', clInfoTableHead.toString());
        setColor('table-light-head', clLightTableHead.toString());
        setColor('table-dark-head', clDarkTableHead.toString());

        var clPrimaryTableHover=clPrimary.getClone();
        clPrimaryTableHover.saturation-=(clPrimaryTableHover.saturation)*5/8;
        clPrimaryTableHover.value+=(100-clPrimaryTableHover.value)*6/8;
        var clSecondaryTableHover=clSecondary.getClone();
        clSecondaryTableHover.saturation-=(clSecondaryTableHover.saturation)*5/8;
        clSecondaryTableHover.value+=(100-clSecondaryTableHover.value)*6/8;
        var clSuccessTableHover=clSuccess.getClone();
        clSuccessTableHover.saturation-=(clSuccessTableHover.saturation)*5/8;
        clSuccessTableHover.value+=(100-clSuccessTableHover.value)*6/8;
        var clWarningTableHover=clWarning.getClone();
        clWarningTableHover.saturation-=(clWarningTableHover.saturation)*5/8;
        clWarningTableHover.value+=(100-clWarningTableHover.value)*6/8;
        var clDangerTableHover=clDanger.getClone();
        clDangerTableHover.saturation-=(clDangerTableHover.saturation)*5/8;
        clDangerTableHover.value+=(100-clDangerTableHover.value)*6/8;
        var clInfoTableHover=clInfo.getClone();
        clInfoTableHover.saturation-=(clInfoTableHover.saturation)*5/8;
        clInfoTableHover.value+=(100-clInfoTableHover.value)*6/8;
        var clLightTableHover=clLight.getClone();
        clLightTableHover.saturation-=(clLightTableHover.saturation)*5/8;
        clLightTableHover.value+=(100-clLightTableHover.value)*6/8;
        var clDarkTableHover=clDark.getClone();
        clDarkTableHover.saturation-=(clDarkTableHover.saturation)*5/8;
        clDarkTableHover.value+=(100-clDarkTableHover.value)*6/8;

        setColor('table-primary-hover', clPrimaryTableHover.toString());
        setColor('table-secondary-hover', clSecondaryTableHover.toString());
        setColor('table-success-hover', clSuccessTableHover.toString());
        setColor('table-warning-hover', clWarningTableHover.toString());
        setColor('table-danger-hover', clDangerTableHover.toString());
        setColor('table-info-hover', clInfoTableHover.toString());
        setColor('table-light-hover', clLightTableHover.toString());
        setColor('table-dark-hover', clDarkTableHover.toString());
      }

      $(function () {
        savedialog = new widgets.dialog(rootcontent, null, _("Сохранение настроек"));
        savedialog.dialog.classList.remove('modal-lg');
        savedialog.dialog.classList.add('modal-md');
        savedialog.onSave=function() { sendData(0); return true; };
        savedialog.closebtn.setLabel(_('Отмена'));
        savegroupbtn = new widgets.button(savedialog.footer, {class: 'warning'}, _('Для группы'));
        savegroupbtn.onClick=function() { sendData(1); savedialog.hide(); return true; };
        savesystembtn = new widgets.button(savedialog.footer, {class: 'danger'}, _('Для системы'));
        savesystembtn.onClick=function() { sendData(2); savedialog.hide(); return true; };
        savedialog.savebtn.setLabel(_('Для себя'));
        obj = new widgets.label(savedialog, {id: 'text'}, _("Вы хотите сохранить настройки на уровне:"));
        savedialog.simplify();

        resetdialog = new widgets.dialog(rootcontent, null, _("Сброс настроек"));
        resetdialog.dialog.classList.remove('modal-lg');
        resetdialog.dialog.classList.add('modal-md');
        resetdialog.onSave=function() { resetData(0); return true; };
        resetdialog.closebtn.setLabel(_('Отмена'));
        resetgroupbtn = new widgets.button(resetdialog.footer, {class: 'warning'}, _('Для группы'));
        resetgroupbtn.onClick=function() { resetData(1); resetdialog.hide(); return true; };
        resetsystembtn = new widgets.button(resetdialog.footer, {class: 'danger'}, _('Для системы'));
        resetsystembtn.onClick=function() { resetData(2); resetdialog.hide(); return true; };
        resetdialog.savebtn.setLabel(_('Для себя'));
        obj = new widgets.label(resetdialog, {id: 'text'}, _("Выберите какой вид настроек интерфейса вы хотите сбросить"));
        resetdialog.simplify();

        card = new widgets.section(rootcontent,null);
        templates = new widgets.select(card, {id: 'templates', value: [], search: false}, 
            "Тема оформления");
        templates.hide();
        subcard1 = new widgets.section(card, null);
        subcard1.node.classList.add('form-group');
        subcard1.node.classList.add('row');
        col = new widgets.columns(subcard1, 2);

        subcard = new widgets.section(col, null);
        subcard.node.classList.add('row');
        col = new widgets.columns(subcard, 4);
        cPrimary = new widgets.colorpicker(col, {id: 'primary', value: getColor('primary')}, 'Основной');
        col = new widgets.columns(subcard, 4);
        cSecondary = new widgets.colorpicker(col, {id: 'secondary', value: getColor('secondary')}, 'Дополнительный');
        col = new widgets.columns(subcard, 4);
        cLight = new widgets.colorpicker(col, {id: 'light', value: getColor('light')}, 'Светлый');
        col = new widgets.columns(subcard, 4);
        cDark = new widgets.colorpicker(col, {id: 'dark', value: getColor('dark')}, 'Темный');

        col = new widgets.columns(subcard1, 2);

        subcard = new widgets.section(col, null);
        subcard.node.classList.add('row');
        col = new widgets.columns(subcard, 4);
        cSuccess = new widgets.colorpicker(col, {id: 'success', value: getColor('success')}, 'Успешно');
        col = new widgets.columns(subcard, 4);
        cInfo = new widgets.colorpicker(col, {id: 'info', value: getColor('info')}, 'Информация');
        col = new widgets.columns(subcard, 4);
        cWarning = new widgets.colorpicker(col, {id: 'warning', value: getColor('warning')}, 'Внимание');
        col = new widgets.columns(subcard, 4);
        cDanger = new widgets.colorpicker(col, {id: 'danger', value: getColor('danger')}, 'Ошибка');

        cPrimary.onChange=changecolormap;
        cSecondary.onChange=changecolormap;
        cLight.onChange=changecolormap;
        cDark.onChange=changecolormap;
        cSuccess.onChange=changecolormap;
        cInfo.onChange=changecolormap;
        cWarning.onChange=changecolormap;
        cDanger.onChange=changecolormap;

        subcard1 = new widgets.section(card, null);
        subcard1.node.classList.add('row');
        col = new widgets.columns(subcard1, 2);

        subcard = new widgets.section(col, null);
        obj = new widgets.list(subcard, {value: [
          {text: "Тестовый элемент списка"},
          {class: 'primary', text: "Тестовый элемент списка"},
          {class: 'secondary', text: "Тестовый элемент списка"},
          {class: 'light', text: "Тестовый элемент списка"},
          {class: 'dark', text: "Тестовый элемент списка"},
          {class: 'success', text: "Тестовый элемент списка"},
          {class: 'info', text: "Тестовый элемент списка"},
          {class: 'warning', text: "Тестовый элемент списка"},
          {class: 'danger', text: "Тестовый элемент списка"},
        ]});        

        col1 = new widgets.columns(subcard1, 2);

        subcard = new widgets.section(col1, null);
        subcard.node.classList.add('form-group');
        subcard.node.classList.add('row');
        col = new widgets.columns(subcard, 4);
        obj = new widgets.button(col, {class: 'primary'}, 'Кнопка');
        col = new widgets.columns(subcard, 4);
        obj = new widgets.button(col, {class: 'secondary'}, 'Кнопка');
        col = new widgets.columns(subcard, 4);
        obj = new widgets.button(col, {class: 'light'}, 'Кнопка');
        col = new widgets.columns(subcard, 4);
        obj = new widgets.button(col, {class: 'dark'}, 'Кнопка');

        subcard = new widgets.section(col1, null);
        subcard.node.classList.add('form-group');
        subcard.node.classList.add('row');
        col = new widgets.columns(subcard, 4);
        obj = new widgets.button(col, {class: 'success'}, 'Кнопка');
        col = new widgets.columns(subcard, 4);
        obj = new widgets.button(col, {class: 'info'}, 'Кнопка');
        col = new widgets.columns(subcard, 4);
        obj = new widgets.button(col, {class: 'warning'}, 'Кнопка');
        col = new widgets.columns(subcard, 4);
        obj = new widgets.button(col, {class: 'danger'}, 'Кнопка');

        loadTemplates();
        loadData();
        sidebar_apply(sbapply);
        sidebar_reset(sbreset);
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