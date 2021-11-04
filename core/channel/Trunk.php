<?php

namespace channel;

/**
 * Класс многоканального (транкового) подключения
 */
abstract class Trunk extends Line {
  
  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \channel\Trunks $collection
   */
  static $collection = 'channel\\Trunks';

  /**
   * Опциональный параметр указывающйи на какой номер осуществляется вызов с использованием многоканальной линии связи
   *
   * @var string $dialnumber;
   */
  public $dialnumber = null;

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
   * Метод возвращает корректную строчку для вызова номера за многоканальной линией
   *
   * @param string $number Набираемый номер назначения
   * @return string Строка вида Technology/trunk/number
   */
  abstract public function getDial(String $number);

  /**
   * Метод проверяет принадлежность многоканальной линии строке вызова Dial
   *
   * @param string $dial Строка вызова команды Dial
   * @param string $number Ссылка на переменную для получения набираемого номера
   * @return bool Истина в случае наличия контакта в строке вызова
   */
  abstract public function checkDial(string $dial, string &$number);

  /**
   * Метод проверяет соотносится ли текущая многоканальная линия с указанным каналом
   *
   * @param string $channel Имя канала технологической платформы
   * @param string $phone Номер телефона контакта
   * @return bool Истина в случае если контакт относится к указанному каналу
   */
  abstract public function checkChannel(string $channel, string $phone);

}

?>