<?php

namespace core;

class AddressbookREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'addressbook';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        $addressbooks = new AddressBookGroups();
        $objects = array();
        foreach($addressbooks as $id => $data) {
          if(self::checkEffectivePriv(self::getServiceLocation(), $id, 'settings_reader')) $objects[]=(object)array('id' => $id, 'title' => $data->title);
        }
        $result = self::returnResult($objects);
        unset($addressbooks);
      } break;
      case "get": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_reader')) {
          $addressbook = new AddressBookGroup($request_data->id);
          $result = self::returnResult($addressbook->cast());
          unset($addressbook);
        }
      } break;
      case "add": {
        if((empty($request_data->id)||($request_data->id == 'false'))&&self::checkPriv('settings_writer')) {
          $addressbook = new AddressBookGroup();        
          if($addressbook->assign($request_data)) {
            if($addressbook->save()){
              $result = self::returnResult((object)array('id' => $addressbook->id));
            } else {
              $result = self::returnError('danger', 'Не удалось добавить адресную книгу');
            }
          } else {
            $result = self::returnError('danger', 'Не удалось установить данные адресной книги');
          }   
          unset($addressbook);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "set": {
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_writer')) {
          $addressbook = new AddressBookGroup($request_data->id);        
          if($addressbook->assign($request_data)) {
            if($addressbook->save()){
              $addressbook->reload();
              $result = self::returnResult((object)array('id' => $addressbook->id));
            } else {
              $result = self::returnError('danger', 'Не удалось сохранить адресную книгу');
            }
          } else {
            $result = self::returnError('danger', 'Не удалось установить данные адресной книги');
          }   
          unset($addressbook);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "remove": {
        if(isset($request_data->id)&&self::checkPriv('settings_writer')) {
          $addressbook = new AddressBookGroup($request_data->id);
          if($addressbook->delete()) {
            $addressbook->reload();
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Не удалось удалить адресную книгу');
          }
          unset($addressbook);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "import": {
        if(isset($request_data->id)&&isset($request_data->file)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_writer')) {
          $file = file_get_contents($request_data->file->tmp_name);
          if(substr($file, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
            $file = substr($file, 3);
          }
          $lines = explode("\n", $file);
          $importdata = [];
          $name = 0;
          $title = 1;
          $phone = 2;
          $start = 1;
          if(count($lines)>0) {
            $linedata = explode(';', mb_strtolower(trim($lines[0])));
            $col = array_search('name', $linedata);
            if($col===false) $col = array_search('название', $linedata);
            if($col===false) $col = array_search('наименование', $linedata);
            if($col===false) $col = array_search('компания', $linedata);
            if($col===false) $col = array_search('имя', $linedata);
            if($col!==false) $name = $col;
            $col = array_search('title', $linedata);
            if($col===false) $col = array_search('должность', $linedata);
            if($col!==false) $title = $col;
            $col = array_search('phone', $linedata);
            if($col===false) $col = array_search('phones', $linedata);
            if($col===false) $col = array_search('number', $linedata);
            if($col===false) $col = array_search('numbers', $linedata);
            if($col===false) $col = array_search('телефон', $linedata);
            if($col===false) $col = array_search('телефоны', $linedata);
            if($col===false) $col = array_search('номер', $linedata);
            if($col===false) $col = array_search('номера', $linedata);
            if($col!==false) $phone = $col;
            if($col===false) $start = 0;
            while(isset($lines[$start])) {
              $linedata = explode(';', trim($lines[$start]));
              if(isset($linedata[$name])&&isset($linedata[$phone])) {
                $contactname = trim($linedata[$name], " \t\n\r\0\x0B\"'");
                $contacttitle = trim($linedata[$title], " \t\n\r\0\x0B\"'");
                $contactphones = explode(',', trim($linedata[$phone], " \t\n\r\0\x0B\"'"));
                for($i = 0; $i < count($contactphones); $i++) {
                  $contactphones[$i] = trim($contactphones[$i]);
                }
                if(!isset($importdata[$contactname])) $importdata[$contactname] = (object) array('phones' => array(), 'title' => $contacttitle);
                $importdata[$contactname]->phones = array_merge($importdata[$contactname]->phones, $contactphones);
              }
              $start++;
            }
            if(count($importdata)>0) {
              $addressbook = new \core\AddressBookGroup($request_data->id);
              foreach($addressbook as $contact) {
                $contact->delete();
              }
              foreach($importdata as $contactname => $contactphones) {
                $newcontact = new \core\AddressBookContact(null); 
                $newcontact->name = $contactname;
                $newcontact->title = $contactphones->title;
                $newcontact->phones = $contactphones->phones;
                $newcontact->book = $addressbook->id;
                $newcontact->save();
              }
              $result = self::returnSuccess('Импорт успешно завершен');
            } else {
              $result = self::returnError('warning', 'Записей не обнаружено');
            }
          } else {
            $result = self::returnError('danger', 'Неверный формат файла');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "export": {
        error_log(print_r($request_data, true));
        if(isset($request_data->id)&&self::checkEffectivePriv(self::getServiceLocation(), $request_data->id, 'settings_reader')) {
          $addressbook = new AddressBookGroup($request_data->id);
          $data = array('Наименование;Должность;Телефоны');
          foreach($addressbook as $contact) {
            $data[] = "\"".$contact->name."\";\"".$contact->title."\";\"".implode(', ', $contact->phones)."\"";
          }
          $filename = str_replace(' ', '_', $addressbook->name); 
          $result = self::returnData("\xEF\xBB\xBF".implode("\r\n", $data)."\r\n", 'text/csv', $filename.'.csv');
          unset($addressbook);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;  
    }
    return $result;
  }
    
}

?>