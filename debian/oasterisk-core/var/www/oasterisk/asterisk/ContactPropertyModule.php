<?php

namespace core;

/**
 * @ingroup coreapi
 * Класс расширяющий набор свойств контакта. Должен использоваться модулями расширения если они могут публиковать настройки получаемые из контакта адресной книги.
 */
abstract class ContactPropertyModule extends Module {

  protected $_objectid;

  protected static $title = '';

  public function __construct(string $objectid) {
    parent::__construct();
    $this->_objectid = $objectid;
  }

  abstract public static function getPropertyList();

  public final function removeProperties() {
    $classname = explode('\\', get_class($this));
    $classname = strtolower(array_pop($classname));
    $json = static::getPropertyList();
    return ASTDBStore::deleteDataItem('contact_data/'.$classname, 'id', $this->_objectid, $json);
  }

  abstract public static function scripts();

  /**
   * Метод реализующий обработку JSON запроса со стороны фронтенда
   *
   * @param string $request Идентификатор запрашиваемых данных
   * @param object $request_data Объект запроса данных
   * @return object Объект результата
   */
  public static function json(string $request, \stdClass $request_data) {
    return new \stdClass();
  }

  public final static function info() {
    $classname = explode('\\', get_called_class());
    $classname = strtolower(array_pop($classname));
    return (object) array("class" => $classname, "name" => static::$title);
  }

  public final function getProperties() {
    $classname = explode('\\', get_class($this));
    $classname = strtolower(array_pop($classname));
    $json = static::getPropertyList();
    return ASTDBStore::readDataItem('contact_data/'.$classname, 'id', $this->_objectid, $json);
  }

  public final function setProperties($data) {
    $classname = explode('\\', get_class($this));
    $classname = strtolower(array_pop($classname));
    $json = static::getPropertyList();
    return ASTDBStore::writeDataItem('contact_data/'.$classname, 'id', $this->_objectid, $json, $data);    
  }

}

?>