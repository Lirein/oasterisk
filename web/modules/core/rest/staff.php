<?php

namespace core;

use stdClass;

class StaffREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'staff';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        $staffs = new \staff\Groups();
        $returnData = array();
        $returnData[] = (object) array('id' => '@allcontacts@', 'title' => 'Все контакты', 'icon' => 'GroupSharpIcon', 'readonly' => true);
        foreach($staffs as $id => $group){
          if(self::checkEffectivePriv(self::getServiceLocation(), $id, 'settings_reader')) $returnData[] = (object)array('id' => $id, 'title' => $group->title, 'icon' => 'LocalLibrarySharpIcon');
        }
        $result = self::returnResult($returnData);
      } break;
      case "get": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_reader')) {
          $groupid = $request_data->id;
          if($groupid == '@allcontacts@') {
            $staffs = new \staff\Groups();
            $profile = new \stdClass();
            $profile->id = $groupid;
            $profile->title = 'Все контакты';
            $profile->contacts = array();
            $profile->readonly = true;
            foreach($staffs as $group) {
              $groupdata = $group->cast();
              foreach(array_keys($groupdata->contacts) as $key) {
                $groupdata->contacts[$key]->group_title = $groupdata->title;
              }
              $profile->contacts = array_merge($profile->contacts, $groupdata->contacts);
            }
            $result = self::returnResult($profile);
          } else {
            $staff = \staff\Groups::find($groupid); 
            $profile = new \stdClass();
            $profile = ($staff)?$staff->cast():(new stdClass()); 
            $profile->readonly = !self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_writer');
            $result = self::returnResult($profile);            
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "set": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_writer')) {
          $staff = new \staff\Group(empty($request_data->old_id)?$request_data->id:$request_data->old_id); 
          if(empty($request_data->old_id)&&$staff->old_id) {
            $result=self::returnError('danger', "Класс с таким иденификатором уже существует");
            unset($staff);
            break;
          }
          if(!empty($request_data->old_id)&&($request_data->old_id!=$request_data->id)) {
            $test = new \staff\Group($request_data->id);
            if($test->old_id) {
              $result=self::returnError('danger', "Класс с таким иденификатором уже существует");
              unset($staff);
              unset($test);
              break;
            }
            unset($test);
          }

          if (!empty($request_data->copy)) {
            $old_staff = new \staff\Group($request_data->copy);
            $staff->assign($old_staff->cast());
            unset($old_staff);
          }
          if($staff->assign($request_data)) {
            self::deltreeDB('staff/'.$request_data->id);
            if ($staff->save()) {
              $staff->reload();
              $result = self::returnSuccess();
            } else {
              $result = self::returnError('danger', 'Не удалось сохранить пользователя');
            }
          } else {
            $result = self::returnError('danger', 'Не удалось установить данные пользователя');
          }
          unset($staff);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
                
      } break;
      case "remove": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_writer')) {
          $staff = new \staff\Group($request_data->id); 
          $result = $staff->delete();
          $staff->reload();
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;    
      case "import": {
        if(isset($request_data->file)&&self::checkPriv('settings_writer')) {
          $json = json_decode(file_get_contents($request_data->file->tmp_name));
          foreach($json as $profile) {
            $staff = new \staff\Group($profile->id); 
            $staff->title = $profile->title;
            $staff->save();
            foreach($profile->contacts as $contact) {
              $contact->orig_id = $contact->id;
              $newcontact = new \staff\Contact($contact->id.'@'.$profile->id); 
              $newcontact->assign($contact);
              $newcontact->save();
            }
          }
          $result = self::returnSuccess('Импорт успешно завершен');
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "export": {
        $agroups = array();
        $staffs = new \staff\Groups();
        foreach($staffs as $groupid => $group) {
          $profile = new \stdClass();
          $profile->id = $groupid;
          $profile->title = $group->title;
          $profile->contacts = array();
          foreach($group as $contact) {
            $profile->contacts[] = $contact->cast();
          }
          $agroups[] = $profile;
        }
        $result = self::returnData(json_encode($agroups), 'application/octet-stream', 'oasterisk-contacts.json');
      } break;
      case "domains": {
        $result = self::returnResult(array());
      } break;
      case "columns": {
        if(isset($request_data->columns)) {
          if(is_array($request_data->columns)&&count($request_data->columns)&&is_string($request_data->columns[0])) {
            if(self::$user->setUserProperty('staffcolumns', '[""]', $request_data->columns)) {
              $result = self::returnSuccess();
            } else {
              $result = self::returnError('warning', 'Не удалось сохранить перечень столбцов');
            }
          } else {
            $result = self::returnError('danger', 'Неверный формат данных');
          }
        } else {
          $columns = array();
          $columns[] = (object)array(
            'id' => 'id',
            'title' => 'Телефон',
            'disabled' => true,
            'checked' => true,
            'description' => 'Номер телефона сотрудника. Должен быть уникальным в пределах одного домена.'
          );
          $columns[] = (object)array(
            'id' => 'alias',
            'title' => 'Синоним',
            'disabled' => false,
            'checked' => true,
            'description' => 'Алфавитный синоним контакта для набора в пределах назначенного домена вида <alias@domain.tld>.'
          );
          $columns[] = (object)array(
            'id' => 'title',
            'title' => 'Должность',
            'disabled' => false,
            'checked' => false,
            'description' => 'Должность сотрудника'
          );
          $columns[] = (object)array(
            'id' => 'name',
            'title' => 'Ф.И.О.',
            'disabled' => false,
            'checked' => true,
            'description' => 'Наименование контакта, подстваляется в качестве CallerID'
          );
          $modules = findModulesByClass('staff\Property', true);
          if($modules&&count($modules)) {
            foreach($modules as $module) {
              $classname = $module->class;
              $info = $classname::info();
              $propertyclass = $info->class;
              $contactprops = $classname::getPropertyList();
              foreach($contactprops as $property => $propinfo) {
                $prop = new \stdClass();
                $prop->id = $propertyclass.'.'.$property;
                $prop->title = $propinfo->title;
                $prop->module = $info->title;
                $prop->disabled = false;
                if(isset($propinfo->checked)) {
                  $prop->checked = $propinfo->checked;
                } else {
                  $prop->checked = false;
                }
                if(isset($propinfo->description)) {
                  $prop->description = $propinfo->description;
                }
                $columns[] = $prop;
              }
            }
          }
          $usercolumns = self::$user->getUserProperty('staffcolumns', '[""]');
          if($usercolumns) {
            $newcolumns = array();
            foreach($usercolumns as $columnid) {
              foreach($columns as $column) {
                if($column->id == $columnid) {
                  $column->checked = true;
                  $newcolumns[] = $column;
                }
              }
            }
            foreach($columns as $column) {
              if(!in_array($column->id, $usercolumns)) {
                $column->checked = $column->disabled;
                $newcolumns[] = $column;
              }
            }
            $columns = $newcolumns;
          }
          $result = self::returnResult($columns);
        }
      } break;
    }
    return $result;
  }
    
}

?>