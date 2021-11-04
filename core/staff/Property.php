<?php

namespace staff;

/**
 * @ingroup coreapi
 * Класс расширяющий набор свойств контакта. Должен использоваться модулями расширения если они могут публиковать настройки получаемые из контакта адресной книги.
 */
abstract class Property extends \Module {

  protected $_objectid;

  protected static $title = '';

  public function __construct(string $objectid) {
    parent::__construct();
    $this->_objectid = $objectid;
  }

  abstract public static function getPropertyList();

  protected static function metatojson($metainfo) {
    $json = new \stdClass();
    foreach($metainfo as $prop => $propinfo) {
      if(isset($propinfo->default)) {
        $json->$prop = $propinfo->default;
      } else {
        $json->$prop = static::metatojson($propinfo);
      }
    }
    return $json;
  }

  public final function removeProperties() {
    $classname = explode('\\', get_class($this));
    $classname = strtolower(array_pop($classname));
    $json = static::metatojson(static::getPropertyList());
    return \config\DB::deleteDataItem('contact_data/'.$classname, 'id', $this->_objectid, $json);
  }

  public final static function info() {
    $classname = explode('\\', get_called_class());
    $classname = strtolower(array_pop($classname));
    return (object) array("class" => $classname, "title" => static::$title);
  }

  public final function getProperties() {
    $classname = explode('\\', get_class($this));
    $classname = strtolower(array_pop($classname));
    $json = static::metatojson(static::getPropertyList());
    $result = \config\DB::readDataItem('contact_data/'.$classname, 'id', $this->_objectid, $json);
    if(!$result) return($json);
    return $result;
  }

  public final function setProperties($data) {
    $classname = explode('\\', get_class($this));
    $classname = strtolower(array_pop($classname));
    $json = static::metatojson(static::getPropertyList());
    return \config\DB::writeDataItem('contact_data/'.$classname, 'id', $this->_objectid, $json, $data);    
  }

  private static function StaffContactRename(int $event, \module\Subject &$contact) {
    $id = explode('@', $contact->old_id);
    $old_exten = $id[0];
    $old_book = $id[1];   
    $id = explode('@', $contact->id);
    $exten = $id[0];
    $book = $id[1];   
    $modules = findModulesByClass('staff\Property', true);
    if($modules&&count($modules)) {
      foreach($modules as $module) {
        $classname = $module->class;
        $contactprops = new $classname($old_exten.'@local-'.$old_book);
        $oldprop = $contactprops->getProperties();
        $contactprops->removeProperties();
        unset($contactprops);
        $contactprops = new $classname($exten.'@local-'.$book);
        $contactprops->setProperties($oldprop);
      }
    }
  }

  private static function StaffContactRemove(int $event, \module\Subject &$contact) {
    $id = explode('@', $contact->old_id);
    $old_exten = $id[0];
    $old_book = $id[1];   
    $modules = findModulesByClass('staff\Property', true);
    if($modules&&count($modules)) {
      foreach($modules as $module) {
        $classname = $module->class;
        $contactprops = new $classname($old_exten.'@local-'.$old_book);
        $contactprops->removeProperties();
        unset($contactprops);
      }
    }    
  }

  private static function StaffGroupRename(int $event, \module\Subject &$book) {
    $modules = findModulesByClass('staff\Property', true);
    if($modules&&count($modules)) {
      foreach($modules as $module) {
        $classname = $module->class;
        foreach($book->context->extents as $exten => $extendata) {
          $contactprops = new $classname($exten.'@local-'.$book->old_id);
          $oldprop = $contactprops->getProperties();
          $contactprops->removeProperties();
          unset($contactprops);
          $contactprops = new $classname($exten.'@local-'.$book->id);
          $contactprops->setProperties($oldprop);
        }
      }
    }
  }

  private static function StaffGroupRemove(int $event, \module\Subject &$book) {
    $modules = findModulesByClass('staff\Property', true);
    if($modules&&count($modules)) {
      foreach($modules as $module) {
        $classname = $module->class;
        foreach($book->context->extents as $exten => $extendata) {
          $contactprops = new $classname($exten.'@local-'.$book->old_id);
          $contactprops->removeProperties();
          unset($contactprops);
        }
      }
    }
  }

  public static function register() {
    self::setHandler(self::RENAME, 'staff\Contact', array(__CLASS__, 'StaffContactRename'));
    self::setHandler(self::REMOVE, 'staff\Contact', array(__CLASS__, 'StaffContactRemove'));
    self::setHandler(self::RENAME, 'staff\Group', array(__CLASS__, 'StaffGroupRename'));
    self::setHandler(self::REMOVE, 'staff\Group', array(__CLASS__, 'StaffGroupRemove'));
  }

}

?>