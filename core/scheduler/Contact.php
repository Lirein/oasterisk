<?php

namespace scheduler;

/**
 * Контакт списка обзвона состоящий из CallerID и набираемого номера
 */
class Contact {
   
  /**
   * Идентификатор контакта
   *
   * @var string $id
   */
  public $id = null;

  /**
   * Наименование контакта
   *
   * @var string $title
   */
  public $title = null;

  /**
   * Номера телефонов контакта
   *
   * @var string $phones[]
   */
  public $phones = null;

  /**
   * Наименование группы к которой принадлежит контакт
   *
   * @var string $group
   */
  public $group = null;
}
?>