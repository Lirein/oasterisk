<?php

namespace module;

abstract class License extends \module\Subject {

  static $collection = '\module\Licenses';

  public function __construct(string $id = null) {
    parent::__construct($id);
    $this->loadInfo();
  }

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
      $info->hash = $data['serialNumberHex'];
      $info->from=new \DateTime('@'.$data['validFrom_time_t']);
      $info->to=new \DateTime('@'.$data['validTo_time_t']);
    }
    return $info;
  }

  private function loadInfo() {
    $info = $this->info();
    $this->data->name = $info->name;
    $this->id = $info->codename;
    $this->old_id = $this->id;
    $this->data->valid = $info->valid;
    $this->data->license = $info->license;
    $this->data->agreement = $info->agreement;           
  }

  abstract public function info();

  public function __set($property, $value) {
    return false;
  }

  /**
   * Создает новый запрос на сертификат
   *
   * @return string Возвращает CSR запрос на сертификат в кодировке base64
   */
  public function createRequest() {
    $dn = array(
    "countryName" => "RU",
    "name" => $this->id,
    "serialNumber" => implode(',', $this->getRootSerial())
    );

    $privkey = openssl_pkey_new();
    $csr = openssl_csr_new($dn, $privkey);

    openssl_csr_export($csr, $csrout);
    return $csrout;
  }

  /**
   * Активирует лицензию путем записи файла лицензии и перечитывает её состояние
   *
   * @param string $data Данные файла лицензии
   * @return boolean Возвращает истину в случае успешной записи файла лицензии
   */
  public function activate(string $data) {
    $result =  file_put_contents(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/licensing/'.$this->id.'.crt',$data);
    if($result) {
      $this->loadInfo();
    }
    return $result;
  }

  public function save() {
    return false;
  }

  public function delete() {
    $result =  false;
    if(file_exists(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/licensing/'.$this->id.'.crt')) {
      $result = unlink(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/licensing/'.$this->id.'.crt');
      $this->cache->delete('license_'.$this->id);
    }
    return $result;
  }

  public function reload() {
    return false;
  }

  public function cast() {
    $keys = array();
    $keys['name'] = $this->data->name;
    $keys['codename'] = $this->id;
    $keys['valid'] = $this->data->valid;
    $keys['license'] = $this->data->license;
    if($this->data->license!==null) {
      $keys['license']->from = $keys['license']->from->format(\DateTime::ISO8601);
      $keys['license']->to = $keys['license']->to->format(\DateTime::ISO8601);
    }
    $keys['agreement'] = $this->data->agreement;
    return (object) $keys;
  }
}

?>