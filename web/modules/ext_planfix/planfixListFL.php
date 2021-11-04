<?php

namespace planfix;

class planfixContactList extends \scheduler\Contact {
    
  /**
   * Адресная книга
   *
   * @var \planfix\PlanfixModule
   */
  private static $planfix = null;

  /**
   * Инициализация статической переменной со ссылкой на экземпляр адресной книги
   *
   * @return void
   */
  private static function init() {
    if(!self::$planfix) {
      self::$planfix = new \planfix\PlanfixModule();
    }
  }

  /**
   * Возвращает перечень доступных адресных книг в виде id = наименование
   *
   * @return array Резулььтат в виде массива ключ=значение
   */
  public static function getGroups() {
    global $_CACHE;
    self::init();
    $groupList = $_CACHE->get('planfixcontactgrouplist');
    if(!is_array($groupList)||(count($groupList)==0)) {
      $request =  \planfix\PlanfixModule::requestContactGroupList();
      $groupList = \planfix\PlanfixModule::extractContactGroupList(self::$planfix->sendAPIRequest($request));
      $_CACHE->set('planfixcontactgrouplist', $groupList, 86400);
    }
    return $groupList;
  }

  /**
   * Запрашивает срез из списка контактов адресной книги
   *
   * @param integer $first Номер записи указывающий на текущую обрабатываемую строку
   * @return \scheduler\ContactItem[] Массив контактов
   */
  protected function fetch(int $first) {
    self::init();
    $groupList = self::getGroups();
    if(!isset($groupList[$this->groupname])) {
      $this->cache->set('planfixcontactgrouplist', array(), 1);
      $groupList = self::getGroups();
    }
    if(!isset($groupList[$this->groupname])) return null;

    $contacts = array();
    $request = \planfix\PlanfixModule::requestContactList(null, null, static::$maxbuffer, intdiv($first, static::$maxbuffer)+1);
    \planfix\PlanfixModule::addContactFilter($request, 'ingroup', 'equal', $groupList[$this->groupname]);
    $result = self::$planfix->sendAPIRequest($request);

    if($result['contacts']['@attributes']['count']>0) {
      if(isset($result['contacts']['@value']['contact'])) {
        if(!isset($result['contacts']['@value']['contact'][0])) $result['contacts']['@value']['contact']=array($result['contacts']['@value']['contact']);
        foreach($result['contacts']['@value']['contact'] as $contact) {
          if(isset($contact['phones'])&&isset($contact['phones']['phone'])) {
            $phones = array();
            if(!isset($contact['phones']['phone'][0])) $contact['phones']['phone']=array($contact['phones']['phone']);
            foreach($contact['phones']['phone'] as $phone) {
              if(trim($phone['number'])!='') $phones[]=trim($phone['number']);
            }
            $contactitem = new \scheduler\ContactItem();
            $contactitem->id = $contact['id'];
            $contactitem->title = $contact['name'];
            $contactitem->phones = $phones;
            $contactitem->group = $this->groupname;
            $contacts[] = $contactitem;
          }
        }
      }
    }
    if(count($contacts)) return $contacts;
    return null;
  }

  /**
   * Перемещает контакт из одной адресной книги в другую.
   *
   * @param string $contactId Идентифкатор контакта адресной книги
   * @param string $group Новая адресная книга
   * @return bool Возвращает ложь, если адресной книги не существует или контакт с таким номером уже существует.
   */
  protected function moveContact(string $contactId, string $group) {
    self::init();
    $groupList = self::getGroups();
    if(!isset($groupList[$group])) return false;
    $request = \planfix\PlanfixModule::requestContactUpdate($contactId, array('group' => array('id' => $groupList[$group])), false);
    return self::$planfix->sendAPIRequest($request)!=false;
  }

  protected function getFields(string $contactId) {
    global $_CACHE;
    self::init();
    $fields = $_CACHE->get('planfixcontactfieldlist');
    if(!is_array($fields)||(count($fields)==0)) {
      $fields = array('name' => 'name',
      'midName' => 'midName',
      'lastName' => 'lastName',
      'post' => 'post',
      'email' => 'email',
      'address' => 'address',
      'description' => 'description',
      'site' => 'site',
      'skype' => 'skype',
      'icq' => 'icq',
      'userPic' => 'userPic');
      $request = \planfix\PlanfixModule::requestContact($contactId);
      $result = self::$planfix->sendAPIRequest($request);
      if(isset($result['contact'])&&isset($result['contact']['customData'])&&isset($result['contact']['customData']['customValue'])) {
        foreach($result['contact']['customData']['customValue'] as $field) {
          $fields[$field['field']['name']] = $field['field']['id'];
        }
      }
      unset($result);
      $_CACHE->set('planfixcontactfieldlist', $fields, 86400);
    }
    return $fields;
  }

  public function update(string $contactId, string $field, string $value) {
    self::init();
    $fieldList = $this->getFields($contactId);
    if(!isset($fieldList[$field])) return false;
    if($fieldList[$field] == $field) {
      $req = array($field => $value);
    } else {
      $req = array('customData' => array('customValue' => array('id' => $fieldList[$field], 'value' => $value)));
    }
    $request = \planfix\PlanfixModule::requestContactUpdate($contactId, $req, false);
    return self::$planfix->sendAPIRequest($request)!=false;
  }

}

?>