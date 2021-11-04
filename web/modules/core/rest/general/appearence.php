<?php

namespace core;

class AppearenceREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'general/appearence';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = array();
    switch($request) {
      case "get":{
        $return = new \stdClass();
        $return = \core\AppearenceModule::getCurrentSettings();
        $return->savesystem=(self::checkPriv('settings_writer')&&!self::checkObjects());
        $perm = self::$user->group->id;
        $return->savegroup=self::checkEffectivePriv('security_group', $perm, 'security_writer');
        $return->defaults = \core\AppearenceModule::getDefaultSettings();
        $result = self::returnResult($return);
      } break;
      case "set":{
        $perm = self::$user->group->id;
        $defaults = \core\AppearenceModule::getDefaultSettings();
        switch($request_data->savetype) {
          case 'user': {
            $ini = self::getINI('/etc/asterisk/manager.conf');
            $login = $_SESSION['login'];
            if(isset($ini->$login)) {
              foreach($defaults as $key => $value) {
                if($request_data->$key != $value) {
                  $ini->$login->$key = (string) $request_data->$key;
                } else {
                  unset($ini->$login->$key);
                }
              }
              if($ini->save()) {
                $result = self::returnSuccess();
              } else {
                $result = self::returnError('danger', 'Не удалось сохранить настройки стиля');
              }
            } else {
              $result = self::returnError('danger', 'Профиль пользователя не найден');
            }
          } break;
          case 'group': {
            if(self::checkEffectivePriv('security_group', $perm, 'security_writer')) {
              foreach($defaults as $key => $value) {
                if($request_data->$key != $value) {
                  self::setDB('appearence/'.$perm, $key, $request_data->$key);
                } else {
                  self::delDB('appearence/'.$perm, $key);
                }
                
              }   
              $result = self::returnSuccess();
            } else {
              $result = self::returnError('danger', 'Отказано в доступе');
            }
          } break;
          case 'system': {
            if(self::checkPriv('settings_writer')&&!self::checkObjects()) {
              $ini = self::getINI('/etc/asterisk/asterisk.conf');
              $count = count((array)$defaults);
              foreach($defaults as $key => $value) {
                if($request_data->$key != $value) {
                  $ini->appearence->$key = $request_data->$key;
                } else {
                  unset($ini->appearence->$key);
                  $count--;
                }
              }  
              if ($count <= 0) unset($ini->appearence);  
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
      case "templates":{
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

}