<?php

namespace core;

/**
 * Класс определяющий список контактов для планировщика расписания для последовательной обработки
 * Является оберткой над адресным списком
 */

class AddressBookList extends \scheduler\Contacts {
    
  /**
   * Адресная книга
   *
   * @var \core\AddressBookCollection
   */
  private static $AddressBook = null;

  /**
   * Инициализация статической переменной со ссылкой на экземпляр адресной книги
   *
   * @return void
   */
  private static function init() {
    if(!self::$AddressBook) {
      self::$AddressBook = new \core\AddressBookGroups();
    }
  }

  /**
   * Возвращает перечень доступных адресных книг в виде id = наименование
   *
   * @return array Резулььтат в виде массива ключ=значение
   */
  public static function getGroups() {
    self::init();
    $result = array();
    foreach (self::$AddressBook as $groupid => $group) $result[] = $groupid;
    return $result;
  }

  /**
   * Запрашивает срез из списка контактов адресной книги
   *
   * @param integer $first Номер записи указывающий на текущую обрабатываемую строку
   * @return \scheduler\ContactItem[] Массив контактов
   */
  protected function fetch(int $first) {
    self::init();
    //$contacts = self::$AddressBook->getContacts($this->groupname);
    
    if(self::$AddressBook[$this->groupname]) {
      $group = self::$AddressBook[$this->groupname];
      $result = array();
      $i = 0;
      $startat = intdiv($first, static::$maxbuffer)*static::$maxbuffer;
      $stopat = $startat+static::$maxbuffer-1;
      foreach($group as $contact) {
        if(($i>=$startat)&&($i<=$stopat)) {
          $contactitem = new \scheduler\Contact();
          $contactitem->id = $contact->id;
          $contactitem->title = $contact->name;
          $contactitem->phones = $contact->numbers;
          $contactitem->group = $this->groupname;
          $result[] = $contactitem; 
        }
        $i++;
      }
      if(count($result)) return $result;
    }
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
    $contact = new \core\AddressBookContact($contactId);
    if($contact) {
      //$contact->orig_id = '';
      $contact->group = $group;
      return $contact->save();
    }
    return false;
  }

  public function update(string $contactId, string $field, string $value) {
    return false;
  }

}

?>