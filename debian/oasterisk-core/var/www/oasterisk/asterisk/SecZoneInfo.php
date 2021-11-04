<?php
class SecZoneInfo {

  /**
   * Класс зоны безопасности
   *
   * @var string $zoneClass
   */
  public $zoneClass = '';
  
  /**
   * Функция для получения набора объектов
   *
   * @var callable $getObjects
   */
  public $getObjects = null;

  public function __call(string $method, array $arguments) {
    if(isset($this->$method)) {
      $func = $this->$method;
      return call_user_func_array($func, $arguments);
    }
    return null;
  }
}
?>