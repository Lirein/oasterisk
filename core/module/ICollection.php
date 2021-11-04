<?php

namespace module;

/**
 * Интерфейс реализующий коллекцию субьектов
 */
interface ICollection extends \Iterator {

  /**
   * Конструктор без аргументов - инициализирует коллекцию объектов
   */
  public function __construct();

  /**
   * Создает новый элемент коллекции
   *
   * @param \module\ISubject $subject Субъект который необходимо добавить в коллекцию. Контроль типов обязателен.
   * @return bool Возвращает истину если удалось добавить субьект в коллекцию.
   */
  public static function add(\module\ISubject &$subject);

  /**
   * Переименовывает субьект коллекции
   *
   * @param \module\ISubject $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function rename(\module\ISubject &$subject);

  /**
   * Изменяет субьект коллекции
   *
   * @param \module\ISubject $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function change(\module\ISubject &$subject);

  /**
   * Удаляет субьект из коллекции
   *
   * @param \module\ISubject $subject Субьект который необходимо удалить из коллекции.
   * @return bool Возвращает истину если субьект удалось удалить из коллекции.
   */
  public static function remove(\module\ISubject &$subject);

  /**
   * Метод возвращает количество элементов коллекции
   *
   * @return integer Возвращает количество элементов коллекции
   */
  public function count();

  /**
   * Метод возвращает массив доступных ключей
   *
   * @return string[]
   */
  public function keys();

  /**
   * Метод возвращает субъект с заданным ID или null если такой субъект не найден
   *
   * @param string $id Идентификатор субъекта
   * @return \module\Subject Субьект или null
   */
  public static function find(string $id);

  /**
  * Функция должна возвращать тип cубъектов коллекции
  *
  * @return string
  */
  static function getTypeName();

  /**
  * Функция должна возвращать новый идентификатор коллекции
  *
  * @return string
  */
  function newID();

}
?>