<?php

namespace core;

class AddressBookContact extends \module\Subject {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \core\AddressBookGroup $collection
   */
  static $collection = 'core\\AddressBookGroup';

  /**
   * Конструктор с идентификатором - инициализирует субьект коллекции
   * 
   * @param string $id Идентификатор элемента коллекции. Если идентификатор не задан, генерирует новый идентификатор, прежний идентификатор равен null. Если идентификатор задан - ищет субьект с указанным идентификатором или возвращает исключение в случае его отсутствия.
   */
  public function __construct(string $id = null){
    AddressBookGroups::init();
    parent::__construct($id);
    $this->data->book = null;
    $this->data->phones = array();
    $this->data->title = '';
    $this->data->name = '';

    if($id != null) {
      list($id, $book) = explode('@', $id, 2);
      $this->id = $id;
      $this->data->book = $book;
    }

    if($this->id && $this->book) {
      $stmt = @AddressBookGroups::$database->prepare("SELECT * FROM contact WHERE id = :id and book_id = :book");
      $result = false;
      if($stmt) {
        $stmt->bindValue(':id', $this->id, SQLITE3_INTEGER);
        $stmt->bindValue(':book', $this->book, SQLITE3_INTEGER);
        $result = $stmt->execute();
      }
      if($result) {
        $data = $result->fetchArray(SQLITE3_ASSOC);
        if($data) {
          $this->id = $data['id'];
          $this->old_id = $data['id'];
          $this->data->name = $data['name'];
          $this->data->title = $data['title'];
          $this->data->book = $data['book_id'];
          $result->finalize();
          $stmt = @AddressBookGroups::$database->prepare("SELECT * FROM phones WHERE contact_id = :id");
          $result = false;
          if($stmt) {
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $result = $stmt->execute();
          }
          if($result) {
            while($phone = $result->fetchArray(SQLITE3_ASSOC)) {
              $this->data->phones[] = $phone['phone'];
            }
            $result->finalize();
          }
        }
      }
      if(!$this->data->name) $this->data->name = $this->id;
    }
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    if($property=='book') {
      if(($value != $this->data->book)&&!$this->data->old_book) $this->data->old_book = $this->data->book;
      $this->data->book = $value;
      return true;
    }
    if($property=='old_book') {
      return false;
    }
    return parent::__set($property, $value);
  }

  /**
   * Сохраняет субьект в коллекции
   *
   * @return bool Возвращает истину в случае успешного сохранения субъекта
   */
  public function save(){
    if(!$this->data->book) return false;
    if($this->old_id!==null) {
      $stmt = @AddressBookGroups::$database->prepare("UPDATE `contact` SET `book_id` = :book, `name` = :name, `title` = :title WHERE `id` = :id");
      if ($stmt){
        $stmt->bindValue(':id', $this->old_id, SQLITE3_INTEGER);
      }
    } else { 
      $stmt = @AddressBookGroups::$database->prepare("INSERT INTO `contact` (`book_id`, `name`, `title`) VALUES (:book, :name, :title)");
    }
    if ($stmt){
      $stmt->bindValue(':book', $this->data->book, SQLITE3_INTEGER);
      $stmt->bindValue(':name', $this->data->name, SQLITE3_TEXT);
      $stmt->bindValue(':title', $this->data->title, SQLITE3_TEXT);
      $result = $stmt->execute();
      if($result) {
        if($this->old_id==null) {
          $this->id = AddressBookGroups::$database->lastInsertRowID();
        }
        $stmt = @AddressBookGroups::$database->prepare("DELETE FROM `phones` WHERE `contact_id` = :contact");
        $result = null;
        if ($stmt){
          $stmt->bindValue(':contact', $this->id, SQLITE3_INTEGER);
          $result = $stmt->execute();         
        }
        if($result) {
          $stmt = @AddressBookGroups::$database->prepare("INSERT INTO `phones`(`contact_id`, `phone`) VALUES(:contact_id, :phone)");
          if ($stmt){
            $stmt->bindValue(':contact_id', $this->id, SQLITE3_INTEGER);
            foreach ($this->data->phones as $phone) {
              $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
              $result = $stmt->execute();         
            }
          }
          if($result) {
            if($this->old_id===null) {
              \core\AddressBookGroup::add($this);
            } else {
              \core\AddressBookGroup::change($this);
            }
            $this->old_id = $this->id;
            return true;
          }
        }
      }
    }
    return false;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete(){
    if(!$this->old_id) return false;
    $stmt = @AddressBookGroups::$database->prepare("DELETE FROM `contact` WHERE `id` = :id");
    $result = false;
    if($stmt) {
      $stmt->bindValue(':id', $this->old_id, SQLITE3_INTEGER);
      $result = $stmt->execute();
    }
    if($result) {
      \core\AddressBookGroup::remove($this);
      return true;
    }
    return false;
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast(){
    $keys = array();
    $keys['id'] = $this->id.'@'.$this->book;
    $keys['name'] = $this->data->name;
    $keys['title'] = $this->data->title;
    $keys['phones'] = $this->data->phones;
    return (object)$keys;
  }

}
?>
