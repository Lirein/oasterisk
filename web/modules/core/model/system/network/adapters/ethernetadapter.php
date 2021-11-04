<?php

namespace core;

/**
 * Интерфейс реализующий субьект коллекции
 * Должен содержать набор приватных свойств и геттеры/сеттеры для их обработки
 * Метод save - сохраняет субьект
 * Метод delete вызывает метод delete класса коллекции
 */
class EthernetAdapter extends \core\NetworkAdapter {

  public function __construct(string $id = null) {
    parent::__construct($id);
    $config = new \config\NetPlan('/etc/netplan/test.yaml');
    if (isset($config->ethernets[$id])) {
      $adapter = $config->ethernets[$id];
      if (isset ($adapter->addresses)) $this->ipv4 = $adapter->addresses;
     // if (isset ($adapter['macaddress'])) $this->ipv6 = $adapter['macaddress'];
    }
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
    return parent::__get($property);
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    return parent::__set($property, $value);
  }

}
?>