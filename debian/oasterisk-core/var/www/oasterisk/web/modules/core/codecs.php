<?php

namespace core;

class Codecs extends Module {

  /**
   * Возвращает массив доступных в системе экземпляров кодеков в виде структуры codec:title
   * 
   * @param string $codec Наименование кодека или null если получаем полный перечень кодеков
   * @return array Массив структур экземпляров классов кодеков
   */
  public function get($codec=null) {
    if(isset($codec))
      $result = null;
    else
      $result = array();
    $codecs = getModulesByClass('core\Codec');
    foreach($codecs as $module) {
      $info = $module->info();
      $info->codec = $module;
      if(!isset($info->title)) $info->title=$info->name;
      if(isset($codec)) {
         if($info->name==$codec) $result=$info;
      } else $result[] = $info;
    }
    return $result;
  }

  /**
   * Проверяет наличие в списке кодека с заданным именем класса
   *
   * @param array $codecs Перечень загруженных кодеков
   * @param string $class Наименование класса кодека
   * @return boolean Истина если кодек найден
   */
  private function hasClass(&$codecs, $class) {
    foreach($codecs as $codec) 
      if($codec->name==$class) return true;
    return false;
  }

  /**
   * Возвращает массив доступных в системе кодеков в виде структуры class:name
   *
   * @return array Массив структур с информацией о кодеках
   */
  public function getByClass($codec=null) {
    if(isset($codec))
      $result = null;
    else
      $result = array();
    $codecs = getModulesByClass('core\Codec');
    foreach($codecs as $module) {
      $info = $module->info();
      $info->codec = $module;
      $info->title = trim(preg_replace('/\(.*\)/','',$info->title));
      if(isset($codec)) {
         if($info->title==$codec) $result=$info;
      } else {
         if(!self::hasClass($result, $info->name)) $result[] = $info;
      }
    }
    return $result;
  }

  /**
   * Загружает список разрешённых кодеков в профиль абонента или транкового подключения из их спецификации
   *
   * @param stdClass $profile Профиль абонента или транкового подключения
   * @param string $allow Идентификатор массива разрешенных кодеков (по умолчанию allow)
   * @param string $disallow Идентификатор массива запрещенных кодеков (по умолчанию disallow)
   * @return bool Истина в случае успешной расшифровки кодеков
   */
  public function extractCodecs(\stdClass &$profile, $allow = 'allow', $disallow = 'disallow') {
    $result = false;
    $profile->codecs = array();
    $allcodecs = array();
    $codecs = $this->getByClass();
    foreach($codecs as $info) {
      $allcodecs[] = $info->name;
    }
    if(is_array($profile->$disallow)) foreach($profile->$disallow as $codec) {
      if($codec=='all') {
        $result = true;
        $profile->codecs = array();
      }
    }
    if(is_array($profile->$allow)) foreach($profile->$allow as $codec) {
      if($codec=='all') {
        $profile->codecs = $allcodecs;
      } else {
        if(in_array((string)$codec, $allcodecs))
          $profile->codecs[] = (string)$codec;
      }
    }
    unset($profile->$disallow);
    unset($profile->$allow);
    return $result;
  }

}

?>