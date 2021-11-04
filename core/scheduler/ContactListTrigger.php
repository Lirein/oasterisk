<?php

namespace scheduler;

/**
 * Триггер расписания вызываемый при каждой итерации запуска планировщика
 */

class ContactListTrigger extends Trigger {

  public static function getName() {
    return "Список контактов";
  }

  public function start($variables) {
    if(empty($variables['CONTACTLIST'])||empty($variables['CONTACTGROUP'])) return null;
    $contactlist = findModuleByClass($variables['CONTACTLIST']);
    if(!$contactlist) return null;
    if(!(in_array('\schedule\Contact', $contactlist->parentclass))) return null;
    $classname = $contactlist->class;
    $contactlist = new $classname($variables['CONTACTGROUP']);
    while(true) {
      $contact = $contactlist->get();
      if(!$contact) return null;
      if(count($contact->phones)>0) break;
    }
    $variables['CONTACT'] = $contact->id;
    $variables['CIDNAME'] = $contact->title;
    $phoneid = random_int(0, count($contact->phones)-1);
    $phone = $contact->phones[$phoneid];
    $variables['CIDNUM'] = $phone;
    $object = (object)array('variables' => $variables, 'destination' => $phone);
    return $object;
  }

  public function trigger($request_data) {
    if(!$this->agi) return false;
    if(isset($request_data->move)&&isset($request_data->group)) {
      $contactlist = $this->agi->get_variable('CONTACTLIST', true);
      $contactgroup = $this->agi->get_variable('CONTACTGROUP', true);
      $contact = $this->agi->get_variable('CONTACT', true);
      $contactlist = findModuleByClass($contactlist);
      if(!$contactlist) return false;
      $classname = $contactlist->class;
      $contactlist = new $classname($contactgroup);
      return $contactlist->move($contact, $request_data->group);
    }
    if(isset($request_data->update)&&isset($request_data->field)&&isset($request_data->value)) {
      $contactlist = $this->agi->get_variable('CONTACTLIST', true);
      $contactgroup = $this->agi->get_variable('CONTACTGROUP', true);
      $contact = $this->agi->get_variable('CONTACT', true);
      $contactlist = findModuleByClass($contactlist);
      if(!$contactlist) return false;
      $classname = $contactlist->class;
      $contactlist = new $classname($contactgroup);
      return $contactlist->update($contact, $request_data->field, $request_data->value);
    }
    return false;
  }

  public function vars() {
    return array('CONTACT', 'CONTACTLIST', 'CONTACTGROUP');
  }

}

?>