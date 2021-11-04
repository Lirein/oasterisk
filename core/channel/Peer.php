<?php

namespace channel;

/**
 * Класс одноканального (абонентского) подключения
 */
abstract class Peer extends Line {
  
  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \channel\Peers $collection
   */
  static $collection = 'channel\\Peers';

  // public function __construct(string $id = null) {
  //   parent::__construct($id);
  // }

  // public function __get($property){
  //   # code...
  // }

  // public function __set($property, $value){
  //   # code...
  // }

  // public function cast(){
  //   # code...
  // }

  /**
   * Метод возвращает корректную строчку для вызова контакта
   *
   * @return string Строка вида Technology/peer[&Technology/peer[&...]]
   */
  abstract public function getDial();

  /**
   * Метод проверяет принадлежность контакта строке вызова Dial
   *
   * @param string $dial Строка вызова команды Dial
   * @return bool Истина в случае наличия контакта в строке вызова
   */
  abstract public function checkDial(string $dial);

  /**
   * Метод проверяет соотносится ли текущий контакт с указанным каналом
   *
   * @param string $channel Имя канала технологической платформы
   * @param string $phone Номер телефона контакта
   * @return bool Истина в случае если контакт относится к указанному каналу
   */
  abstract public function checkChannel(string $channel, string $phone);

}

?>