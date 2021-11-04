<?php

namespace core;

class AddressbookEntryREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'addressbook/entry';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        if(isset($request_data->id)) {
          $addressbook = new AddressBookGroup($request_data->id);
          $objects = array();
          foreach($addressbook as $id => $data) {
            $objects[]=(object)array('id' => $id, 'title' => $data->name);
          }
          $result = self::returnResult($objects);
          unset($addressbook);
        }
      } break;
      case "get": {
        if(isset($request_data->id)) {
          list($id, $book) = explode('@', $request_data->id);
          if(self::checkEffectivePriv(dirname(self::getServiceLocation()), $book, 'settings_reader')) {
            $addressbookcontact = new AddressBookContact($request_data->id);
            $result = self::returnResult($addressbookcontact->cast());
            unset($addressbookcontact);
          }
        }
      } break;
      case "add": {
        if(isset($request_data->book)&&self::checkEffectivePriv(dirname(self::getServiceLocation()), $request_data->book, 'settings_writer')) {
          $addressbookcontact = new AddressBookContact();
          if($addressbookcontact->assign($request_data)) {
            if($addressbookcontact->save()){
              $result = self::returnResult((object)array('id' => $addressbookcontact->id));
            } else {
              $result = self::returnError('danger', 'Не удалось сохранить контакт');
            }   
          } else {
            $result = self::returnError('danger', 'Не удалось установить данные контакта');
          }
          unset($addressbookcontact);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "set": {
        if(isset($request_data->id)) {
          list($id, $book) = explode('@', $request_data->id);
          if(self::checkEffectivePriv(dirname(self::getServiceLocation()), $book, 'settings_writer')&&(!isset($request_data->book)||self::checkEffectivePriv(dirname(self::getServiceLocation()), $request_data->book, 'settings_writer'))) {
            $addressbookcontact = new AddressBookContact($request_data->id);
            if($addressbookcontact->assign($request_data)) {
              if($addressbookcontact->save()){
                // $addressbookcontact->reload();
                $result = self::returnResult((object)array('id' => $addressbookcontact->id));
              } else {
                $result = self::returnError('danger', 'Не удалось сохранить контакт');
              }   
            } else {
              $result = self::returnError('danger', 'Не удалось установить данные контакта');
            }
            unset($addressbookcontact);
          } else {
            $result = self::returnError('danger', 'Отказано в доступе');
          }
        }
      } break;
      case "remove": {
        if(isset($request_data->id)) {
          list($id, $book) = explode('@', $request_data->id);
          if(self::checkEffectivePriv(dirname(self::getServiceLocation()), $book, 'settings_writer')) {
            $addressbookcontact = new AddressBookContact($request_data->id);
            if($addressbookcontact->delete()) {
              // $addressbookcontact->reload();
              $result = self::returnSuccess();
            } else {
              $result = self::returnError('danger', 'Не удалось удалить контакт');
            }
            unset($addressbookcontact);
          } else {
            $result = self::returnError('danger', 'Отказано в доступе');
          }
        }
      } break;
    }
    return $result;
  }
    
}

?>