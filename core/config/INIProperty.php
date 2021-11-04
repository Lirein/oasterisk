<?php

namespace config;

abstract class INIProperty {
  /**
   * Внутреннее хранилище ключа поля
   *
   * @var string
   */
  protected $_name;
  /**
   * Внутреннее хранилище значения поля
   *
   * @var mixed
   */
  protected $_value;

  /**
   * Комментарий поля
   *
   * @var string
   */
  protected $_comment;

  /**
   * Описание поля
   *
   * @var string
   */
  protected $_description;

  /**
   * Является ли поле расширенным
   *
   * @var boolean
   */
  protected $_isExt = false;

  /**
   * Конструктор
   * 
   * @param array $fields Массив полей секции
   * @param string $key Ключ поля
   * @param mixed $value Значение поля
   */
  public abstract function __construct(array &$fields, string $key, $value);

  /**
   * Присваивает значение полю
   *
   * @param mixed $value Значение поля
   * @return bool
   */
  public abstract function setValue($value);

  /**
   * Возвращает значение поля
   *
   * @return mixed Возвращает значение поля
   */
  public function getValue() {
    return $this->_value;
  }

  /**
   * Преобразует экземпляр класса в строку
   *
   * @return string Возвращает поле в формате "Ключ = Значение"
   */
  public abstract function castString();

  /**
   * Преобразует значение поля в строку
   *
   * @return string
   */
  public function __toString() {
    return $this->_value;
  }

  // Общие методы

  /**
   * Задаёт комментарий поля
   *
   * @param string $comment Комментарий
   */
  public function setComment(string $comment) {
    $this->_comment = $comment;
  }

  /**
   * Возвращает комментарий поля
   *
   * @return string Возвращает комментарий поля
   */
  public function getComment() {
    return $this->_comment;
  }

  /**
   * Задаёт описание поля
   *
   * @param string $value Новое описание
   * @return void
   */
  public function setDescription(string $value) {
    $this->_description = $value;
  }

  /**
   * Возвращает описание поля
   *
   * @return string Возвращает описание поля
   */
  public function getDescription() {
    return $this->_description;
  }

  /**
   * Устанавливает ключ поля
   *
   * @param string $name Ключ поля
   * @return void
   */
  public function setName(string $name) {
    $this->_name = $name;
  }
  
  /**
   * Возвращает ключ поля
   *
   * @return string Возвращает ключ поля
   */
  public function getName() {
    return $this->_name;
  }
  
  /**
   * Преобразует поле в расширенное или обычное.
   *
   * @param boolean $extended Преобразовать поле в расширенное - true, обычное - false. Если параметр не указан, то преобразует в расширенное.
   * @return void
   */
  public function setExtended(bool $extended = true) {
    $this->_isExt = $extended;
  }

  /**
   * Проверяет является ли поле расширенным.
   *
   * @return bool Возвращает true, если поле расширенное. Иначе - false
   */
  public function getExtended() {
    return $this->_isExt;
  }
}

?>
