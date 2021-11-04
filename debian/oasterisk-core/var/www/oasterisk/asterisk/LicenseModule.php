<?php

namespace core;

/**
 * Базовый класс модуля лицензирования, отвечает за запрос и просмотр лицензий
 */
abstract class LicenseModule extends Module {

  /**
   * Возвращает информацию о лицензии и сроках её действия в виде структуры:<br>
   * <b>org</b> - Наименование компании<br>
   * <b>serial</b> - Серийный номер<br>
   * <b>from</b> - От какой даты<br>
   * <b>to</b> - До какой даты<br>
   *
   * @param string $module_name Идентификатор модуля лицензирования
   * @return object Структура данных с описанием модуля
   */
  public function getLicenseInfo(string $module_name) {
    $serial = $this->getRootSerial();
    $info = null;
    if(file_exists(dirname(__DIR__).'/licensing/'.$module_name.'.crt')) {
      $crt = openssl_x509_read(file_get_contents(dirname(__DIR__).'/licensing/'.$module_name.'.crt'));
      $data = openssl_x509_parse($crt);
      $info = new \stdClass();
      $info->country=$data['subject']['C'];
      $info->location=isset($data['subject']['L'])?$data['subject']['L']:'Город';
      $info->region=$data['subject']['ST'];
      $info->org=$data['subject']['O'];
      $info->serial=$data['subject']['serialNumber'];
      $info->from=new \DateTime('@'.$data['validFrom_time_t']);
      $info->to=new \DateTime('@'.$data['validTo_time_t']);
    }
    return $info;
  }

  abstract public function info();

}

?>