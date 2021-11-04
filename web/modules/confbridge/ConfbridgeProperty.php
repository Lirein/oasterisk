<?php

namespace confbridge;

class ConfbridgeProperty extends \staff\Property {

  protected static $title = "Конференцсвязь";

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-confbridge');
    return $result;
  }

  public static function getPropertyList() {
    return json_decode('{
      "pincode": {
        "default": "",
        "title": "Пинкод",
        "checked": true
      },
      "retries": {
        "default": "",
        "title": "Количество повторов",
        "description": "Устанавливает количество повторных вызовов контакта"
      },
      "dialtimeout": {
        "default": "",
        "title": "Длительность вызова",
        "description": "Устанавливает длительность каждой попытки вызова контакта в секундах"
      },
      "retrydelay": {
        "default": "",
        "title": "Задержка между повторами",
        "description": "Устанавливает задержку между повторными вызовами контакта в секундах"
      },
      "calldelay": {
        "default": "",
        "title": "Задержка вызова",
        "description": "Устанавливает задержку/опережение вызова участника селектора по умолчанию до момента времени начала селектора по расписанию"
      }
    }');
  }

  private static function checkpin($pin) {
    global $_CACHE;
    $classname = explode('\\', get_called_class());
    $classname = strtolower(array_pop($classname));
    $addressbook = getModuleByClass('addressbook\AddressBook');
    if($addressbook) {
      $books = $addressbook->getBooks();
      $pincodes = $_CACHE->get($classname.'_pincodes');
      if(!$pincodes) {
        $pincodes = array();
        foreach(array_keys($books) as $book) {
          $contacts = $addressbook->getContacts($book);
          foreach($contacts as $contact) {
            $contactpin = $contact->$classname->pincode;
            if($contactpin!=''&&!in_array($contactpin, $pincodes)) $pincodes[] = $contactpin;
          }
        }
        $_CACHE->set($classname.'_pincodes', $pincodes, 10);
      }
      return !in_array($pin, $pincodes);
    }
    return true;
  }

  public static function json(string $request, \stdClass $request_data) {
    switch($request) {
      case "uniquepin": {
        if(isset($request_data->pin)) {
          $result = self::returnResult(self::checkpin($request_data->pin));
        } else {
          do {
            $pin = sprintf('%04d', rand(0,9999));
          } while(!self::checkpin($pin));
          $result = self::returnResult($pin);
        }
      } break;
      default: {
        $result = new \stdClass();
      }
    }
    return $result;
  }
  
  public static function implementation() {
    $classname = explode('\\', get_called_class());
    $classname = strtolower(array_pop($classname));
    ?>
    this.newcontacthandlers.push(function() {
      card.setValue({pincode: '', retries: 2, dialtimeout: 10, retrydelay: 5, calldelay: 0});
      this.sendRequest('contactaction', {propertyclass: '<?= $classname ?>', action: 'uniquepin'}).success(function(data) {
        card.setValue({pincode: data});
        card.node.querySelector('#pincode').widget.input.classList.add('is-valid');
        contactpincode = '';
      });
    });
    this.savecontacthandlers.push(async function() {
      if(card.node.querySelector('#pincode').widget.input.classList.contains('is-invalid')) {
        let modalresult = await showdialog(_('Внимание'), _('Контакт с таким пинкодом уже существует. Вы уверены что хотите сохранить контакт?'), 'question', ['Yes', 'No']);
        return modalresult=='Yes';
      } else {
        return true;
      }
    });
    let obj = new widgets.input(card, {id: 'pincode', pattern: /[0-9#*A-D]+/}, _("Пинкод")); 
    let contactpincode = '';
    obj.onInput = function(sender) {
      let pincode = sender.getValue();
      if((pincode!=contactpincode)&&(pincode!='')) {
        this.sendRequest('contactaction', {propertyclass: '<?= $classname ?>', action: 'uniquepin', pin: pincode}).success(function(data) {
          if(data) {
            sender.input.classList.remove('is-invalid');
            sender.input.classList.add('is-valid');
          } else {
            sender.input.classList.remove('is-valid');
            sender.input.classList.add('is-invalid');
          }
        });
      } else {
        sender.input.classList.remove('is-valid');
        sender.input.classList.remove('is-invalid');
      }
    };
    obj.onChange = function(sender) {
      contactpincode = sender.getValue();
    };
    obj = new widgets.input(card, {id: 'retries', pattern: /[0-9]+/}, _("Повторных звонков")); 
    obj = new widgets.input(card, {id: 'dialtimeout', pattern: /[0-9]+/}, _("Длительность вызова")); 
    obj = new widgets.input(card, {id: 'retrydelay', pattern: /[0-9]+/}, _("Задержка между повторами")); 
    obj = new widgets.input(card, {id: 'calldelay', pattern: /[0-9]+/}, _("Отложить вызов на")); 
    <?php
  }
  
}

?>