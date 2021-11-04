<?php

namespace core;

class StaffContactREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'staff/contact';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        if(isset($request_data->id)) {
          if(self::checkEffectivePriv('staff', $request_data->id, 'settings_reader')) {
            $staff = \staff\Groups::find($request_data->id);
            if($staff) {
              $returnData = $staff->cast();
              $result = self::returnResult($returnData);
            } else {
              $result = self::returnError('warning', 'Запрошенная группа контактов не найдена');
            }
          } else {
            $result = self::returnError('danger', 'Отказано в доступе');
          }
        } else {
          $result = self::returnError('danger', 'Идентификатор группы контактов не передан');
        }
      } break;
      case "get": {
        if(isset($request_data->id)) {
          list($contactid, $group) = explode('@', $request_data->id, 2);
          if(!empty($group)&&(self::checkEffectivePriv('staff', $group, 'settings_reader'))) {
            $contact = \staff\Group::find($request_data->id);
            if($contact) {
              $result = self::returnResult($contact->cast());
            } else {
              $result = self::returnError('danger', 'Контакт не найден');
            }
          } else {
            $result = self::returnError('danger', 'Отказано в доступе');
          }
        } else {
          $result = self::returnError('danger', 'Неверно передан идентификатор контакта');
        }
    } break;
      case "set": {
        if(isset($request_data->orig_id)&&isset($request_data->group)&&self::checkEffectivePriv('staff', $request_data->group, 'settings_writer')) {
          $canprocess = true;
          if($request_data->orig_id!=$request_data->id) {
            $groups = new \staff\Groups();
            foreach($groups as $groupid => $group) {
              foreach($group as $contact) {
                if($contact->id == $request_data->id) {
                  $canprocess = false;
                  break;
                }
              }                   
              if(!$canprocess) break;
            }
          }
          if($canprocess) {
            $contact = new \staff\Contact((empty($request_data->orig_id)?$request_data->id:$request_data->orig_id).'@'.$request_data->group);
            $contact->assign($request_data);
            if($contact->save()) {
              $contact->reload();
              $result = self::returnSuccess('Контакт успешно сохранен');
            } else {
              $result = self::returnError('danger', 'Не удалось сохранить контакт');
            }
          } else {
            $result = self::returnError('danger', 'Контакт с таким внутренним номером уже существует');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "remove": {
        if(isset($request_data->id)&&isset($request_data->group)&&self::checkEffectivePriv('staff', $request_data->group, 'settings_writer')) {
          $contact = new \staff\Contact($request_data->id.'@'.$request_data->group); 
          if($contact->delete()) {
            $contact->reload();
            $result = self::returnSuccess('Контакт успешно удален');
          } else {
            $result = self::returnError('danger', 'Не удается удалить контакт');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "newid": {
        if(isset($request_data->group)&&self::checkEffectivePriv('staff', $request_data->group, 'settings_writer')) {
          $newid = -1;
          $straitnumbering=self::getDB('staff/'.$request_data->group, 'straitnumbering');
          $contacts = array();
          if($straitnumbering == 'true') {
            $staffs = new \staff\Groups();
            foreach($staffs as $k => $group) {
              $straitnumbering=self::getDB('staff/'.$k, 'straitnumbering');
              if($straitnumbering == 'true') {
                foreach($group as $contact) {
                  $contacts[] = $contact->id;
                }
              }
            }
          } else {
            $group = new \staff\Group($request_data->group); 
            foreach($group as $contact) {
              $contacts[] = $contact->id;
            }
          }
          asort($contacts);
          //uasort($contacts, array(__CLASS__,'contactcmp'));
          foreach($contacts as $contact) {
            if($newid==-1) $newid=$contact;
            if($contact == $newid) $newid++;
              else break;
          }
          if($newid==-1) $newid=1;
          $result = self::returnResult($newid);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;   
      case "contactaction": {
        if(isset($request_data->action)&&isset($request_data->propertyclass)) {
          $found = false;
          $modules = findModulesByClass('staff\Property', true);
          if($modules&&count($modules)) {
            foreach($modules as $module) {
              $classname = $module->class;
              $info = $classname::info();
              if($info->class == $request_data->propertyclass) {
                $found = true;
                $action = $request_data->action;
                unset($request_data->action);
                unset($request_data->propertyclass);
                $result = $classname::json($action, $request_data);
                break;
              }
            }
          }
          if(!$found) {
            $result = self::returnError('danger', 'Не найден класс расширения карточки контакта');
          }
        } else {
          $result = self::returnError('danger', 'Не переданы все требуемые параметры');
        }
      } break;
    }
    return $result;
  }
    
}

?>