<?php

namespace confbridge;

class ConfbridgeProperty extends \core\ContactPropertyModule {

  protected static $title = "Конференцсвязь";

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-confbridge');
    return $result;
  }

  public static function getPropertyList() {
    return json_decode('{
      "pincode": "",
      "retries": "",
      "dialtimeout": "",
      "retrydelay": "",
      "calldelay": ""
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
  
  public static function scripts() {
    $classname = explode('\\', get_called_class());
    $classname = strtolower(array_pop($classname));
    ?>
    newcontacthandlers.push(function() {
      card.setValue({pincode: '', retries: 2, dialtimeout: 10, retrydelay: 5, calldelay: 0});
      sendRequest('contactaction', {propertyclass: '<?= $classname ?>', action: 'uniquepin'}).success(function(data) {
        card.setValue({pincode: data});
        card.node.querySelector('#pincode').widget.input.classList.add('is-valid');
        contactpincode = '';
      });
    });
    savecontacthandlers.push(async function() {
      if(card.node.querySelector('#pincode').widget.input.classList.contains('is-invalid')) {
        let modalresult = await showdialog(_('Внимание'), _('Контакт с таким пинкодом уже существует. Вы уверены что хотите сохранить контакт?'), 'question', ['Yes', 'No']);
        return modalresult=='Yes';
      } else {
        return true;
      }
    });
    let obj = new widgets.input(card, {id: 'pincode', pattern: '[0-9#*A-D]+'}, _("Пинкод")); 
    var contactpincode = '';
    obj.onInput = function(sender) {
      let pincode = sender.getValue();
      if((pincode!=contactpincode)&&(pincode!='')) {
        sendRequest('contactaction', {propertyclass: '<?= $classname ?>', action: 'uniquepin', pin: pincode}).success(function(data) {
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
    obj = new widgets.input(card, {id: 'retries', pattern: '[0-9]+'}, _("Повторных звонков")); 
    obj = new widgets.input(card, {id: 'dialtimeout', pattern: '[0-9]+'}, _("Длительность вызова")); 
    obj = new widgets.input(card, {id: 'retrydelay', pattern: '[0-9]+'}, _("Задержка между повторами")); 
    obj = new widgets.input(card, {id: 'calldelay', pattern: '[0-9]+'}, _("Отложить вызов на")); 
    <?php
  }
  
}

?>