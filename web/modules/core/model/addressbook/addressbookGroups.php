<?php

namespace core;

class AddressBookGroups extends \module\Collection {

  /**
   * Класс подключения к СУБД SqLite3
   *
   * @var \SQLite3 $database
   */
  static public $database;
    
  public static function init() {
    if(!self::$database) {
      if(file_exists("/var/lib/asterisk/addrbook.db")){
        self::$database = new \SQLite3('/var/lib/asterisk/addrbook.db');
      } else {
        self::$database = new \SQLite3('/var/lib/asterisk/addrbook.db');
        if(self::$database) {
          $sql="PRAGMA foreign_keys = ON;";
          self::$database->query($sql);
          $sql="CREATE TABLE 'book' (
                    'id' INTEGER PRIMARY KEY AUTOINCREMENT,
                    'name' TEXT NOT NULL
                );";
          self::$database->query($sql);
          $sql="CREATE TABLE 'contact' (
                    'id' INTEGER PRIMARY KEY AUTOINCREMENT,
                    'book_id' INTEGER NOT NULL,
                    'name' TEXT NOT NULL,
                    'title' TEXT NOT NULL,
                  FOREIGN KEY('book_id') REFERENCES 'book'('id') on delete cascade);";
          self::$database->query($sql);
          $sql="CREATE TABLE 'phones' (
                    'contact_id' INTEGER,
                    'phone' TEXT NOT NULL,
                  FOREIGN KEY('contact_id') REFERENCES 'contact'('id') on delete cascade,
                  primary key('contact_id', 'phone'));";
          if(self::$database->query($sql)) return true;
        }
      }
    }
    if (!self::$database) return false;
    return true;
  }

  public function __construct() {
    self::init();
    parent::__construct();
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $stmt = @self::$database->prepare('select `id` from `book`');
    $result = false;
    if($stmt) {
      $result = $stmt->execute();
    }
    $this->items = array();
    if($result) {
      while($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $this->items[] = $row['id'];
      }
    }
    reset($this->items);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $AddressBook = current($this->items);
    return new \core\AddressBookGroup($AddressBook);
  }

}
?>