<?php

namespace module;

/**
 * Интерфейс реализующий субьект коллекции
 * Должен содержать набор приватных свойств и геттеры/сеттеры для их обработки
 * Метод save - сохраняет субьект
 * Метод delete вызывает метод delete класса коллекции
 */
abstract class MorphingSubject extends \Module implements \module\ISubject {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var MorphingCollection $collection
   */
  static $collection = 'module\\MorphingCollection';
    
  /**
   * Идентификатор субьекта - всегда должен быть задан
   *
   * @var string
   */
  protected $id;

  /**
   * Прежний идентификатор субъекта коллекции - задается равный null если это новый субьект, иначе принимает значение ID существующего субьекта
   *
   * @var string
   */
  protected $old_id;

  /**
   * Конструктор с идентификатором - инициализирует субьект коллекции
   * 
   * @param string $id Идентификатор элемента коллекции. Если идентификатор не задан, генерирует новый идентификатор, прежний идентификатор равен null. Если идентификатор задан - ищет субьект с указанным идентификатором или возвращает исключение в случае его отсутствия.
   */
  public function __construct(string $id = null) {
    parent::__construct();
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает истину если такое свойство существует
   *
   * @param mixed $property Имя свойства
   * @return bool Факт наличия свойства
   */

  abstract public function __isset($property);

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */

  abstract public function __get($property);

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  abstract public function __set($property, $value);

  /**
   * Сериализация объекта. В дочернем классе вначала вызывается родительский метод, потом сериализуются дополнительные атрибуты
   *
   * @return string[]
   */
  abstract public function __serialize();

  /**
   * Десериализация объекта. В дочернем классе вначале инициализируется объект, потом вызывается родительский метод и потом при необходимости восстанавливаются ресурсы.
   *
   * @param array $keys сериализованные ключи
   * @return void
   */
  abstract public function __unserialize(array $keys);

  /**
   * Сохраняет субьект в коллекции
   *
   * @return bool Возвращает истину в случае успешного сохранения субъекта
   */
  public function save() {
    if(empty($this->id)) return false;
    if(empty($this->old_id)) static::$collection::add($this); else
    if($this->old_id!=$this->id) static::$collection::rename($this); else
    static::$collection::change($this);
    return true;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    static::$collection::remove($this);
    return true;
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  abstract public function cast();

  /**
   * Клонирует объект с полным раскрытием свойств
   *
   * @return MorphingSubject
   */
  public function clone() {
    $classname = get_class($this);
    return new $classname($this->id);
  }

}
?>