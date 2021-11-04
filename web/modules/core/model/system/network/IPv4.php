<?php

namespace core;

class IPv4 extends \module\Subject {

  /**
   * Конструктор без аргументов - инициализирует модель
   */
  public function __construct(string $id = null) {
    parent::__construct($id);
    $this->id = $id;
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    parent::__destruct();

  }

  public function __toString() {
    
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save() {
    return false;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    return false;
  }

}
?>