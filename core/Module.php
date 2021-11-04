<?php
/**
 * @ingroup coreapi
 * Базовый абстрактный класс модуля OAS´terisk, содержит ряд методов,
 * таких как проверка лицензий, прав доступа, привилегий, создание запросов на лицензию и
 * основные механизмы кэширования, AMI и AGI сценариев.
 */
abstract class Module {

  /**
   * Кэш горячих межсессионных данных в оперативной памяти
   *
   * @var \Memcached $cache
   */
  protected $cache;

  /**
   * Интерфейс AMI, при вызове со стороны веб-интерфейса или планировщика
   *
   * @var \AMI $ami
   */
  protected $ami;
  
  /**
   * Интерфейс AGI, при вызове со стороны технологической платформы
   *
   * @var \AGI $agi
   */
  protected $agi;

  /**
   * Константы вида уведомления о событии
   */
  const ADD = 0;
  const RENAME = 1;
  const CHANGE = 2;
  const REMOVE = 3;

  /**
   * Обработчики событий от разных модулей
   *
   * @var array $HANDLERS
   */
  protected static $HANDLERS = null;

  /**
   * Перечень открытых INI файлов
   *
   * @var array $INIFILES
   */
  protected static $INIFILES = null;


  protected static $LOCKS = array();

  /**
   * Текущие привилегии доступа пользователя после аутентификации
   *
   * @var \security\User $permissions
   */
  public static $user = null;

  /**
   * Сертификат УЦ "Лицензирования"
   *
   * @var string $subcadata
   */
  private static $subcadata='-----BEGIN CERTIFICATE-----
    MIIIzjCCBragAwIBAgIQNNWO1936B2mYOMNOEYPa0jANBgkqhkiG9w0BAQUFADCC
    AQYxCzAJBgNVBAYTAlJVMSowKAYDVQQIDCHQotGO0LzQtdC90YHQutCw0Y8g0L7Q
    sdC70LDRgdGC0YwxFTATBgNVBAcMDNCi0Y7QvNC10L3RjDFWMFQGA1UECgxN0J7Q
    ntCeICLQntGC0LrRgNGL0YLRi9C1INCQ0LLRgtC+0LzQsNGC0LjQt9C40YDQvtCy
    0LDQvdC90YvQtSDQodC40YHRgtC10LzRiyIxLjAsBgNVBAsMJdCT0L7Qu9C+0LLQ
    vdC+0Lkg0KPQpiDQntCe0J4gItCe0JDQoSIxEjAQBgNVBAMTCWNhLm9hcy5zdTEY
    MBYGCSqGSIb3DQEJARYJY2FAb2FzLnN1MB4XDTE3MDgwNTAwMDAwMFoXDTI3MDgw
    NDIzNTk1OVowggEGMQswCQYDVQQGEwJSVTEqMCgGA1UECAwh0KLRjtC80LXQvdGB
    0LrQsNGPINC+0LHQu9Cw0YHRgtGMMRUwEwYDVQQHDAzQotGO0LzQtdC90YwxVjBU
    BgNVBAoMTdCe0J7QniAi0J7RgtC60YDRi9GC0YvQtSDQkNCy0YLQvtC80LDRgtC4
    0LfQuNGA0L7QstCw0L3QvdGL0LUg0KHQuNGB0YLQtdC80YsiMSwwKgYDVQQLDCPQ
    o9CmICLQm9C40YbQtdC90LfQuNGA0L7QstCw0L3QuNGPIjEUMBIGA1UEAxMLY2Vy
    dC5vYXMuc3UxGDAWBgkqhkiG9w0BCQEWCWNhQG9hcy5zdTCCAiIwDQYJKoZIhvcN
    AQEBBQADggIPADCCAgoCggIBAMq7RT7+X97ENYItZ0r+R4GHji6NUJ5dTDQklHbw
    2S35Pp8St5OJbZ/PSa11+qxBHkwGnO5YuUn0sqGJZCmd9+oBv//sArkDyZVWdAjy
    a8oZicOK1FuTEY09boIX3oRjH8lIegVAAVCmOknuhdDgHUTXm8q2MGeWF3cZqal1
    Bl6tyIrYOkZWU4EzfVtjTVb+DLNtxbpNeDtFgPfIVED/G1sr4wEx0P9peX9JVdSQ
    soo+2hu+bmXycRS/nzSSpH5Wrz7OiZTOh/TFwe2ivpORuzOsdOw3lPeSWN9iU3n5
    FpjH50EmBpApXfKzYOEvR3tM1wn/qWVE0t+KP0zozSPdKLM2e8lc4E4EZzNOXzXG
    65tTRudIusGjmVhYfnBsfOW3076KirvUxkDe9NdBEQ66FarC/L/5tvILxseIk/H9
    GlzWW0NCGtwzwlbFCqrn31JLdosW6F6A85HTJ1AvgVpqtoYPzCrk/Ch/BN//QIPE
    HYos7cq6cOm9qLahoXSoJHRVvuxU3LN4l7rz/Ol9o9l4LB71pXBGQeL/PnbR64BL
    +veJhIsocHU3CRV+bqZU74oFzifntOYUR6pYNtJTc+ZoWckpF4zziXZ5LS88+pH1
    eEle99hDmlIPIEMPfm6o6jfJxaJObnhMTx+w3cGxkAxxsfGcQaTvbXtxhrOsarHM
    rCGFAgMBAAGjggIyMIICLjAPBgNVHRMBAf8EBTADAQH/MB0GA1UdDgQWBBSesSG4
    CwDj9jSPqDYrP6090lcNVDCCAUcGA1UdIwSCAT4wggE6gBR8corPejFDpqUBfBdS
    JV+iRhmFeKGCAQ6kggEKMIIBBjELMAkGA1UEBhMCUlUxKjAoBgNVBAgMIdCi0Y7Q
    vNC10L3RgdC60LDRjyDQvtCx0LvQsNGB0YLRjDEVMBMGA1UEBwwM0KLRjtC80LXQ
    vdGMMVYwVAYDVQQKDE3QntCe0J4gItCe0YLQutGA0YvRgtGL0LUg0JDQstGC0L7Q
    vNCw0YLQuNC30LjRgNC+0LLQsNC90L3Ri9C1INCh0LjRgdGC0LXQvNGLIjEuMCwG
    A1UECwwl0JPQvtC70L7QstC90L7QuSDQo9CmINCe0J7QniAi0J7QkNChIjESMBAG
    A1UEAxMJY2Eub2FzLnN1MRgwFgYJKoZIhvcNAQkBFgljYUBvYXMuc3WCEDTVjtfd
    +gdpmDjDThGD2sgwCwYDVR0PBAQDAgEGMDIGA1UdHwQrMCkwJ6AloCOGIWh0dHBz
    Oi8vY2VydC5vYXMuc3UvcHVibGljL2NhLmNybDA9BggrBgEFBQcBAQQxMC8wLQYI
    KwYBBQUHMAKGIWh0dHBzOi8vY2VydC5vYXMuc3UvcHVibGljL2NhLmNydDARBglg
    hkgBhvhCAQEEBAMCAAcwHgYJYIZIAYb4QgENBBEWD3hjYSBjZXJ0aWZpY2F0ZTAN
    BgkqhkiG9w0BAQUFAAOCAgEAtYbJCU8zUgMJJzJhsmjKx46Pvo0EJ96jzXTqW7tL
    ZG4DwfJJso6fMGBd8ZFcGNl2IsJmbmuQsCCE/NA3PyZohmmxWLObj4Te0fXZySSi
    pLRNfctqIyWPdRlKhKCQNluznSPFFbxfZ0qvAnVg35xYfgwM7I4LSuZOBFqhW47E
    cAsPHL1xg9c9WUKE7GTF3JwlIX4t/gCUuWjbRfKFF87FKEnHDTmb1h7tIfFe5dje
    Q9NFvrcAzuNx2/VxJU2MGZ1kKgVIsnyV6Nqy2cOVcWhlyJkkSl9Sf2wI25KU/Ovt
    LFn5ujakdCEybZK60LXGkAKeJxcwEpQik+WnaF1c4NbnhkX5o3TyXeeJVJOpC27i
    72Jlt+P4H5fRxRzvkMT9tjfZhR4paRlackE+QcKtyr3/5UPBPaPfO11iFq6Gm0xB
    Per7ojH0d8cOnAJCcjWuesNKaByPEcjAPiyvg1n57zkzE9ZfnF7ht2rD1+DxApyh
    gl2vSrRf/XxbU56oEX0DhThtlCz2Qv8A7dpdQ0Ov5cp0G/qhVmRKZXDjGA35Lfqe
    xxi0sPPT9YUJSncEjWd4HfErlaXT+ZUJd7DElYnOhv/9si4KfT/c6XuFUKSrkAP1
    4A+Oss+Dbn2NuNLxOh7EGBootoBNcz/ghzlOghnTqAoNALg8MF5oXH21pQD11RVg
    /dU=
-----END CERTIFICATE-----';

  /**
   * Сертификат Головного УЦ "ООО «ОАС»"
   *
   * @var string $cadata
   */
  private static $cadata='-----BEGIN CERTIFICATE-----
    MIIInDCCBoSgAwIBAgIQNNWO1936B2mYOMNOEYPayDANBgkqhkiG9w0BAQUFADCC
    AQYxCzAJBgNVBAYTAlJVMSowKAYDVQQIDCHQotGO0LzQtdC90YHQutCw0Y8g0L7Q
    sdC70LDRgdGC0YwxFTATBgNVBAcMDNCi0Y7QvNC10L3RjDFWMFQGA1UECgxN0J7Q
    ntCeICLQntGC0LrRgNGL0YLRi9C1INCQ0LLRgtC+0LzQsNGC0LjQt9C40YDQvtCy
    0LDQvdC90YvQtSDQodC40YHRgtC10LzRiyIxLjAsBgNVBAsMJdCT0L7Qu9C+0LLQ
    vdC+0Lkg0KPQpiDQntCe0J4gItCe0JDQoSIxEjAQBgNVBAMTCWNhLm9hcy5zdTEY
    MBYGCSqGSIb3DQEJARYJY2FAb2FzLnN1MCAXDTE3MDgwMjE2MTUwMFoYDzcwODkw
    ODAyMTYxNTAwWjCCAQYxCzAJBgNVBAYTAlJVMSowKAYDVQQIDCHQotGO0LzQtdC9
    0YHQutCw0Y8g0L7QsdC70LDRgdGC0YwxFTATBgNVBAcMDNCi0Y7QvNC10L3RjDFW
    MFQGA1UECgxN0J7QntCeICLQntGC0LrRgNGL0YLRi9C1INCQ0LLRgtC+0LzQsNGC
    0LjQt9C40YDQvtCy0LDQvdC90YvQtSDQodC40YHRgtC10LzRiyIxLjAsBgNVBAsM
    JdCT0L7Qu9C+0LLQvdC+0Lkg0KPQpiDQntCe0J4gItCe0JDQoSIxEjAQBgNVBAMT
    CWNhLm9hcy5zdTEYMBYGCSqGSIb3DQEJARYJY2FAb2FzLnN1MIICIjANBgkqhkiG
    9w0BAQEFAAOCAg8AMIICCgKCAgEAxKSjahZ9cbONg5CZa4vKIvoaeVUhWv86+YX2
    2zPU+juIefASLKIyVMnRdZoX/A2Lgzsex/XGeoKHhoLQtJWRm75a71XeSj8n0H2N
    z/V2UsaBRmZL/nkYj5AHk2Q1bl3CzPsyDI4COAABDwF+PU8i6UF1gzQ0pVpVr3Fe
    dgq1EeCPBq7+p97ctDJUJ/10t5h+n+283yr3yeUrB/dzZjUjzwIGmBxhDP3Argxp
    istX0wFGhU/D4RXGdTz++T2yUx+s/Rl0cN0EJTit+IdupHoXnoSk5mTodRmS3B7z
    XCcthhyJ6oEMiwHZmTOJhBGDkj3JvRmvLd0likpjNEeEXMVOsvT1SWC8clQMoUxP
    VrP18Tmc0SJPHEN6pFSy9kI2FiYBezMkouYR6nImepn9nas9GhrMlLFAgwYr6UKi
    9QWgLbuBk7hAEhsSLuL6OM+XASdgdH6C9NgAizZrZJfBjJgZDaSNTdj9dzeKtnBl
    yum7E2c51UI1VHD5EF22sJ5zth/iKjk+gkW/OqOOg7lBKJxYzONf5uCTrlL9WxRv
    rR2n+fIvwpLrAvVno8aRS5OLF76ot475rwJlw9D1Ei4K6Hlch5lbiLB5fAVG/jry
    B7E9IIkkk4cLc4ngdlCgsKItXwvielORO0elQKs4pYFYo7pc4bTtCIk0xK+7+ds4
    F+nfAKECAwEAAaOCAf4wggH6MA8GA1UdEwEB/wQFMAMBAf8wHQYDVR0OBBYEFHxy
    is96MUOmpQF8F1IlX6JGGYV4MIIBRwYDVR0jBIIBPjCCATqAFHxyis96MUOmpQF8
    F1IlX6JGGYV4oYIBDqSCAQowggEGMQswCQYDVQQGEwJSVTEqMCgGA1UECAwh0KLR
    jtC80LXQvdGB0LrQsNGPINC+0LHQu9Cw0YHRgtGMMRUwEwYDVQQHDAzQotGO0LzQ
    tdC90YwxVjBUBgNVBAoMTdCe0J7QniAi0J7RgtC60YDRi9GC0YvQtSDQkNCy0YLQ
    vtC80LDRgtC40LfQuNGA0L7QstCw0L3QvdGL0LUg0KHQuNGB0YLQtdC80YsiMS4w
    LAYDVQQLDCXQk9C+0LvQvtCy0L3QvtC5INCj0KYg0J7QntCeICLQntCQ0KEiMRIw
    EAYDVQQDEwljYS5vYXMuc3UxGDAWBgkqhkiG9w0BCQEWCWNhQG9hcy5zdYIQNNWO
    1936B2mYOMNOEYPayDALBgNVHQ8EBAMCAQYwPQYIKwYBBQUHAQEEMTAvMC0GCCsG
    AQUFBzAChiFodHRwczovL2NlcnQub2FzLnN1L3B1YmxpYy9jYS5jcnQwEQYJYIZI
    AYb4QgEBBAQDAgAHMB4GCWCGSAGG+EIBDQQRFg94Y2EgY2VydGlmaWNhdGUwDQYJ
    KoZIhvcNAQEFBQADggIBAJIfl6nJAl19190KFTQHXsp9FQ2VOb9JNAqAc7dkga9Y
    Jq3Zpg2NAg1AbfscmEMo0J4EOwozC2SxIwmjUwLRM5K/ABFZw/KMg5HOff/9aWo1
    zyFH5qR+JbSZIcURRDQpee2PNISiCLLL0wcSLykmctT7QjEzoDFP2yd7jSXDEnXx
    JmOR7kKE3/4oDpAMoEponzzOr3b9naiDP8tY1vtkvPAigyHT0HQRW8Z14tMFuFVC
    0uqZraEA1LAE/fOA9UZFcsh9T010Nk5udJYzIf7zVT4wJZEl66YkMb2yT6Eeksb2
    0J7QpqTAXZS4dDUWA9TqU4WOQBbBuays+mJSGZMpAx7GHTP5aDdiTZV+pkArFEnb
    KlAvgoRLyOB65mwQFd1TW5ehF4WW40ETCIWGodq57iCIeZPgqdZyOX1jw69ZFpNy
    szM+RTbmNueQ/FkC0gBRkKjc/X6/iGnUFg75vBtyB8DAqKOkkvaHn4vEJJ5ZISku
    z0+AkaQ7mia4EiLcZSgjw2WyxbiYY3xVFhOMvMF0m/LhFv9nIjkO3uvgLtfIRv7x
    WXxKgjVVumCsneND3bkWry3OsCA6ShSCNxCZ0BHCBfNLi7Qr66R/6XSKnmQ+y4RH
    kwhFlNXN5e6AKngsjpmGkurOfvj97sgRVRo0TFdOteZ4Ki+4zl4GtjACgjPYbK6u
-----END CERTIFICATE-----';

  /**
   * Конструктор класса, сохраняет текущие параметры подключения к Memcached, интерфейсы AMI и AGI
   */
  public function __construct() {
    if(!isset(self::$user)) self::initPermissions();
    global $_CACHE;
    global $_AMI;
    global $_AGI;
    $this->cache = $_CACHE;
    $this->ami = $_AMI;
    $this->agi = $_AGI;
  }

  /**
   * Функция должна возвращать URI для веб-сервера, если путь не задан возвращает null
   *
   * @return string URI веб страницы
   */
  public static function getLocation() {
    return null;
  }

  /**
   * Функция осуществляет проверку возможности использования модуля.
   * Если возвращает истину, модуль может использоваться, иначе модуль не загружается или возвращает ошибку 403.
   * Функция должна осуществить проверку наличия зависимых модулей, лицензий и прав доступа.
   *
   * @return boolean Результат проверки доступности модуля
   */
  public static function check() {
    return true;
  }

  /**
   * Возвращает путь к родительскому файлу устройства. Требуется для обхода цепочки накопителей, используемых при создании LVM тома и/или RAID массива для корневого раздела.
   *
   * @param string $path Путь к файлу устройства
   * @return string[] Путь к родительскому файлу устройства
   */
  private static function getSlaveDevice(string $path) {
    $slave = array();
    $d = dir($path.'/slaves');
    while(false !== ($e = $d->read())) {
      if(($e != '.')&&($e != '..')) {
        $slave[] = $e;
      }
    }
    $d->close();
    return $slave;
  }

  /**
   * Функция возвращает серийный номер жесткого диска
   *
   * @param string $path Путь к файлу устройства жестного диска или твердотельного накопителя.
   * @return array Массив серийных номер жесткого диска
   */
  private static function getHDDSerial(string $path) {
    $output=array();
    $params=array();
    exec('udevadm info "'.$path.'"',$output);
    foreach($output as $k) {
      $v = explode('=',$k);
      if(count($v)==2) {
        $p = explode(':',$v[0]);
        $param = trim($p[1]);
        $params[$param]=$v[1];
      }
    }
    $serial = null;
    if(isset($params['ID_SERIAL_SHORT']))
      $serial = array($params['ID_SERIAL_SHORT']);
    elseif(isset($params['ID_SERIAL']))
      $serial = array($params['ID_SERIAL']);
    $isdm = isset($params['DM_NAME']);
    $ismd = isset($params['MD_NAME']);
    if(($serial==null)&&($isdm||$ismd)) {
      $path = "/sys".$params['DEVPATH'];
      $devs = self::getSlaveDevice($path);
      $serial = array();
      foreach($devs as $dev) {
        $serial = array_merge($serial, self::getHDDSerial('/dev/'.$dev));
      }
      $serial = array_unique($serial);
    }
    return $serial;
  }

  /**
   * Возвращает серийный номер диска корневого раздела ОС
   *
   * @return array Серийные номера дисков корневого раздела
   */
  protected static function getRootSerial() {
    $file = fopen('/proc/mounts', 'r');
    $root = "/dev/sda";
    if($file) {
      while(($line = fgets($file)) !==false) {
        $data = explode(' ',$line);
        if($data[1] == '/') $root = $data[0];
      }
      fclose($file);
    }
    return self::getHDDSerial($root);
  }

  /**
   * Проверяет наличие лицензии на соответствующий модуль расширения OAS´terisk.
   * Возвращает истину если лицензия найдена.
   *
   * @param string $module_name Кодовое имя модуля расширения
   * @return boolean Результат проверки лицензии
   */
  protected static function checkLicense(string $module_name) {
    global $_CACHE;
    $canprocess = $_CACHE->get('license_'.$module_name);
    if(!$canprocess) {
      $serial = self::getRootSerial();
      if(file_exists(dirname(__DIR__).'/licensing/'.$module_name.'.crt')) {
        $crt = openssl_x509_read(file_get_contents(dirname(__DIR__).'/licensing/'.$module_name.'.crt'));
        $data = openssl_x509_parse($crt);
        file_put_contents('/tmp/oas_licensing_ca.crt', self::$subcadata, LOCK_EX);
        file_put_contents('/tmp/oas_ca.crt', self::$cadata, LOCK_EX);
        if(is_array($data['subject']['serialNumber'])) {
          $canprocess=count(array_intersect($data['subject']['serialNumber'], $serial))>0;
        } else {
          $data['subject']['serialNumber']=explode(',',$data['subject']['serialNumber']);
          $canprocess=count(array_intersect($data['subject']['serialNumber'], $serial))>0;
        }
        $fp1 = fopen('/tmp/oas_ca.crt', "r+");
        $fp2 = fopen('/tmp/oas_licensing_ca.crt', "r+");
        if($canprocess) {
          if(($fp1!==false)&&($fp2!==false)) {
            flock($fp1, LOCK_SH);
            flock($fp2, LOCK_SH);
            $canprocess = ($data['subject']['name']==$module_name)&&(openssl_x509_checkpurpose($crt, X509_PURPOSE_SSL_CLIENT, array('/tmp/oas_ca.crt','/tmp/oas_licensing_ca.crt'))===true);
            flock($fp2, LOCK_UN);
            flock($fp1, LOCK_UN);
            fclose($fp1);
            fclose($fp2);
          } else {
            $canprocess = ($data['subject']['name']==$module_name);
          }
        }
        unlink('/tmp/oas_ca.crt');
        unlink('/tmp/oas_licensing_ca.crt');
      }
      $_CACHE->set('license_'.$module_name, $canprocess, 60);
    }
    return $canprocess;
  }

  /**
   * Возвращает все активные звонки в виде структуры:<br>
   * <b>id</b> - Идентификатор канала<br>
   * <b>from</b> - Имя абонента<br>
   * <b>from-id</b> - Номер телефона абонента<br>
   * <b>to</b> - Имя соединенного абонента<br>
   * <b>to-id</b> - Номер соединенного абонента<br>
   * @todo Консолидировать звонки по UniqueID и LinkedID или LinkedChannel
   * 
   * @return array Массив структур с информацией об активных звонках
   */
  protected static function getAsteriskCalls() {
    global $_AMI;
    $calls = array();
    $channels = $_AMI->send_request('CoreShowChannels', array());
    foreach($channels['events'] as $channel) {
      if($channel['CallerIDName']!="<unknown>") {
        $call = array();
        $call['id'] = $channel['Channel'];
        $call['from'] = $channel['CallerIDName'];
        $call['from-id'] = $channel['CallerIDNum'];
        $call['to'] = $channel['ConnectedLineName'];
        $call['to-id'] = $channel['ConnectedLineNum'];
        if($call['to-id']=='<unknown>') {
          $call['to'] = $channel['Exten'];
          $call['to-id'] = $channel['Exten'];
        }
        $duration = explode(':',$channel['Duration']);
        $call['start'] = time()-($duration[0]*60*60+$duration[1]*60+$duration[2]);
        $calls[]=$call;
      }
    }
    return $calls;
  }

  /**
   * Возвращает список необходимых привилегий для вызова команды AMI
   *
   * @param string $command Наименование команды AMI
   * @return array Массив с перечнем привилегий
   */
  protected static function getCommandPriv(string $command) {
    if(@preg_match('/.*\(Priv:\s([\<\>a-z,]*)\)/',$command, $priv)) {
      return array_flip(explode(',',$priv[1]));
    }
    return array();
  }

  /**
   * Возвращает перечень доступных команд AMI текущему пользователю
   *
   * @return array Массив с перечнем команд AMI
   */
  protected static function getCommands() {
    global $_AMI;
    $commands = array_keys($_AMI->ListCommands());
    unset($commands['Response']);
    unset($commands['ActionID']);
    return $commands;
  }

  /**
   * Получает значение из AsteriskDB по пути и имени ключа
   *
   * @param string $family Путь в древовидной БД (ветвь)
   * @param string $key Наименование ключа БД
   * @return string Значение ключа
   */
  protected static function getDB(string $family, string $key) {
    global $_AMI;
    global $_AGI;
    global $_DBCache;
    if(!isset($_DBCache)) $_DBCache = array();
    if(isset($_DBCache[$family.'/'.$key])) return $_DBCache[$family.'/'.$key];
    if(isset($_AGI)) {
      $result = $_AGI->database_get($family, $key);
    } else {
      $result = $_AMI->DBGet($family, $key);
    }
    $_DBCache[$family.'/'.$key] = $result;
    return $result;
  }

  /**
   * Сохраняет значение в AsteriskDB, возвращает истину в случае успешного сохраненияю
   *
   * @param string $family Путь в древовидной БД (ветвь)
   * @param string $key Наименование ключа БД
   * @param string $value Значение ключа
   * @return boolean Результат сохранения значения в БД
   */
  protected static function setDB(string $family, string $key, string $value) {
    global $_AMI;
    global $_AGI;
    global $_DBCache;
    if(!isset($_DBCache)) $_DBCache = array();
    if(isset($_AGI)) {
      $result = $_AGI->database_put($family, $key, $value);
    } else {
      $parameters = array('Family' => $family, 'Key' => $key, 'Val' => $value);
      $result = $_AMI->send_request('DBPut', $parameters);
    }
    if($result['Response']=='Success') {
      $_DBCache[$family.'/'.$key] = $value;
    }
    return $result['Response']=='Success';
  }

  /**
   * Удаление ветви древовидной БД AsteriskDB
   *
   * @param string $family Путь в древовидной БД (ветвь)
   * @return boolean Результат удаления ветви из БД
   */
  protected static function deltreeDB(string $family) {
    global $_AMI;
    global $_AGI;
    global $_DBCache;
    if(!isset($_DBCache)) $_DBCache = array();
    if(isset($_AGI)) {
      $result = $_AGI->database_deltree($family);
    } else {
      $parameters = array('Family' => $family);
      $result = $_AMI->send_request('DBDelTree', $parameters);
    }
    if($result['Response']=='Success') {
      foreach(array_keys($_DBCache) as $key) {
        if(strpos($key, $family)===0) unset($_DBCache[$key]);
      }
    }
    return $result['Response']=='Success';
  }

  /**
   * Удаляет ключ в AsteriskDB, возвращает истину в случае успешного удаления
   *
   * @param string $family Путь в древовидной БД (ветвь)
   * @param string $key Наименование ключа БД
   * @return boolean Результат сохранения значения в БД
   */
  protected static function delDB(string $family, string $key) {
    global $_AMI;
    global $_AGI;
    global $_DBCache;
    if(isset($_AGI)) {
      $result = $_AGI->database_del($family, $key);
    } else {
      $parameters = array('Family' => $family, 'Key' => $key);
      $result = $_AMI->send_request('DBDel', $parameters);
    }
    if($result['Response']=='Success') {
      unset($_DBCache[$family.'/'.$key]);
    }
    return $result['Response']=='Success';
  }

  /**
   * Очищает локальный кэш БД техплатформы
   *
   * @return void
   */
  protected static function flushDBCache() {
    global $_DBCache;
    $_DBCache = array();
  }

  /**
   * Получает права доступа текущего пользователя и запоминает их до окончания работы сессии.
   *
   * @return void
   */
  public static function initPermissions() {
    global $_CACHE;
    self::$user = true;
    if(!isset($_SESSION['user'])) {
      self::$user = new \security\User($_SESSION['login']);
      $_SESSION['user'] = serialize(self::$user); //Cache effective permissions to session
      $logins=$_CACHE->get('logins');
      if(!$logins) $logins=array();
      $loginkey=-1;
      $currtime=time();
      $sid=session_id();
      foreach($logins as $loginid => $logindata) {
        if($logindata->sessionid==$sid) $loginkey=$loginid;
        if($logindata->lastlogin+60<$currtime) unset($logins[$loginid]);
      }
      sort($logins);
      $ip=$_SERVER['REMOTE_ADDR'];
      if(isset($_SERVER['HTTP_X_REAL_IP'])) $ip=$_SERVER['HTTP_X_REAL_IP'];
      if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = $ips[0];
      }
      if($loginkey==-1) $loginkey=count($logins);
      if(isset(self::$user->name)) $logins[$loginkey] = (object) array('sessionid' => $sid, 'login' => $_SESSION['login'], 'ip' => $ip, 'profile' => self::$user->group->id, 'profilename' => self::$user->group->name, 'lastlogin' => $currtime, 'name' => self::$user->name);
      $_CACHE->set('logins',$logins,62);
    } else {
      self::$user=unserialize($_SESSION['user']);
    }
  }

  /**
   * Проверяет доступность URI согласно текущей области видимости пользователя.
   * Возвращает истину если указанная страница входит в область видимости пользователя.
   *
   * @param string $location URI страницы зарегистрированного модуля
   * @return boolean Результат проверки доступа.
   */
  public static function checkScope(string $location) {
    if(!isset(self::$user)) self::initPermissions();
    return self::$user->inScope($location);
  }

  /**
   * Проверяет наличие у пользователя базовой привилегии доступа согласно перечню $internal_priveleges.
   * Возвращает истину если указанная привилегия просутствует в списке привилегий пользователя
   *
   * @param string $priv Наименование привилегии
   * @return boolean Результат проверки доступа
   */
  protected static function checkPriv(string $priv) {
    if(!self::$user) self::initPermissions();
    $result=self::$user->checkPrivilege($priv);
    return $result;
  }
  
  /**
   * Проверяет наличие прав доступа на отдельный объект конфигурации.
   * Проверка прав производится с помощью механизма зон безопасности.
   * Если у пользователя не назначено зон безопасности или их поддержка
   * отключена, работает аналогично методу checkPriv().
   *
   * @param string $rest Путь к REST интерфейсу коллекции
   * @param string $object Идентификатор объекта
   * @param string $priv Наименование привилегии доступа
   * @return boolean Результат проверки доступа
   */
  protected static function checkEffectivePriv(string $rest, string $object, string $priv) {
    if(!self::$user) self::initPermissions();
    return true;
    return self::$user->checkEffectivePrivilege($rest, $object, $priv);
  }

  /**
   * Проверяет наличие доступа на запись хотя бы у одного объекта или интерфейса в целом
   *
   * @return boolean Результат проверки наличия доступа на запись
   */
  public static function hasWriteAccess() {
    if(!self::$user) self::initPermissions();
    if(self::$user === true) return false;
    return !self::$user->isReadonly();
  }

  /**
   * Проверяет назначены ли расширенные привилегии пользователю.
   * Возвращает истину, если у пользователя заданы расширенные привилегии.
   *
   * @return boolean Результат проверки наличия расширенных привилегий
   */
  protected static function checkObjects() {
    if(!self::$user) self::initPermissions();
    return !empty(self::$user->group->objects);
  }

  /**
   * Возвращает список доступных и загруженных модулей ядра технологической платформы.
   * Структура состоит из следующих полей:<br>
   * <b>autoload</b> - Признак автозагрузки модулей: истина/ложь<br>
   * <b>pbx</b> - Модули конфигурации ядра технологической платформы<br>
   * <b>resource</b> - Ресурсные и вспомогательные модули<br>
   * <b>application</b> - Приложения диалплана<br>
   * <b>function</b> - Функции диалплана<br>
   * <b>channel</b> - Канальные драйверы<br>
   * <b>codec</b> - Аудиокодеки для целей транскодирования<br>
   * <b>format</b> - Поддерживаемые форматы приема-передачи по RTP<br>
   * <b>bridge</b> - Мостовые соединения между каналами абонентов<br>
   * <b>cdr</b> - Модули записи детализации вызовов<br>
   * <b>cel</b> - Модули записи детализации событий<br>
   *
   * @return stdClass Структура с массивами модулей по классам
   */
  protected static function getAsteriskModules() {
    global $_AMI;
    global $_CACHE;
    global $_MODULES;
    $_MODULES = $_CACHE->get('modules');
    if(!$_MODULES) {
      $_MODULES = new \stdClass();
      $_MODULES->pbx = new \stdClass();
      $_MODULES->resource = new \stdClass();
      $_MODULES->application = new \stdClass();
      $_MODULES->function = new \stdClass();
      $_MODULES->channel = new \stdClass();
      $_MODULES->codec = new \stdClass();
      $_MODULES->format = new \stdClass();
      $_MODULES->bridge = new \stdClass();
      $_MODULES->cdr = new \stdClass();
      $_MODULES->cel = new \stdClass();
      $_MODULES->other = new \stdClass();
      if ($dh = opendir('/usr/lib/asterisk/modules')) {
        while (($file = readdir($dh)) !== false) {
          if(($file!='..')&&($file!='.')) {
            $pos=strpos($file,'_');
            $prefix=substr($file,0,$pos);
            $file=substr($file,$pos+1,-3);
            switch($prefix) {
              case "res": $_MODULES->resource->$file=(object) array('loaded'=>null,'mode'=>'unknown'); break;
              case "pbx": $_MODULES->pbx->$file=(object) array('loaded'=>null,'mode'=>'unknown'); break;
              case "app": $_MODULES->application->$file=(object) array('loaded'=>null,'mode'=>'unknown'); break;
              case "func": $_MODULES->function->$file=(object) array('loaded'=>null,'mode'=>'unknown'); break;
              case "chan": $_MODULES->channel->$file=(object) array('loaded'=>null,'mode'=>'unknown'); break;
              case "codec": $_MODULES->codec->$file=(object) array('loaded'=>null,'mode'=>'unknown'); break;
              case "format": $_MODULES->format->$file=(object) array('loaded'=>null,'mode'=>'unknown'); break;
              case "bridge": $_MODULES->bridge->$file=(object) array('loaded'=>null,'mode'=>'unknown'); break;
              case "cdr": $_MODULES->cdr->$file=(object) array('loaded'=>null,'mode'=>'unknown'); break;
              case "cel": $_MODULES->cel->$file=(object) array('loaded'=>null,'mode'=>'unknown'); break;
              default: $_MODULES->other->$file=(object) array('loaded'=>null,'mode'=>'unknown', 'prefix'=>$prefix);
            }
          }
        }
        closedir($dh);
      }
      $sorted=new \stdClass();
      $modulesdata = '{"modules": {"autoload": "!yes"}}';
      $ini = self::getINI('/etc/asterisk/modules.conf');
      //$modulesdata = $ini->getDefaults($modulesdata);
      $ini->normalize($modulesdata);

      $sorted->autoload=$ini->modules->autoload;
      $sorted->pbx = new \stdClass();
      $sorted->resource = new \stdClass();
      $sorted->application = new \stdClass();
      $sorted->function = new \stdClass();
      $sorted->channel = new \stdClass();
      $sorted->codec = new \stdClass();
      $sorted->format = new \stdClass();
      $sorted->bridge = new \stdClass();
      $sorted->cdr = new \stdClass();
      $sorted->cel = new \stdClass();
      $sorted->other = new \stdClass();
      foreach($ini->modules as $module => $state) {
        if($module!='autoload') {
          $pos=strpos($module,'_');
          $prefix=substr($module,0,$pos);
          $file=basename($module, '.so');
          switch($prefix) {
            case "res": if(isset($_MODULES->resource->$file)) $sorted->resource->$file = $_MODULES->resource->$file; break;
            case "pbx": if(isset($_MODULES->pbx->$file)) $sorted->pbx->$file = $_MODULES->pbx->$file; break;
            case "app": if(isset($_MODULES->application->$file)) $sorted->application->$file = $_MODULES->application->$file; break;
            case "func": if(isset($_MODULES->function->$file)) $sorted->function->$file = $_MODULES->function->$file; break;
            case "chan": if(isset($_MODULES->channel->$file)) $sorted->channel->$file = $_MODULES->channel->$file; break;
            case "codec": if(isset($_MODULES->codec->$file)) $sorted->codec->$file = $_MODULES->codec->$file; break;
            case "format": if(isset($_MODULES->format->$file)) $sorted->format->$file = $_MODULES->format->$file; break;
            case "bridge": if(isset($_MODULES->bridge->$file)) $sorted->bridge->$file = $_MODULES->bridge->$file; break;
            case "cdr": if(isset($_MODULES->cdr->$file)) $sorted->cdr->$file = $_MODULES->cdr->$file; break;
            case "cel": if(isset($_MODULES->cel->$file)) $sorted->cel->$file = $_MODULES->cel->$file; break;
            default: if(isset($_MODULES->other->$file)) $sorted->other->$file = $_MODULES->other->$file;
          }
        }
      }
      foreach($_MODULES as $class => $value) {
        foreach($_MODULES->$class as $module => $moduleinfo) {
          switch($class) {
            case "resource": $modulename = "res_".$module; break;
            case "pbx": $modulename = "pbx_".$module; break;
            case "application": $modulename = "app_".$module; break;
            case "function": $modulename = "func_".$module; break;
            case "channel": $modulename = "chan_".$module; break;
            case "codec": $modulename = "codec_".$module; break;
            case "format": $modulename = "format_".$module; break;
            case "bridge": $modulename = "bridge_".$module; break;
            case "cdr": $modulename = "cdr_".$module; break;
            case "cel": $modulename = "cel_".$module; break;
            default: $modulename = $moduleinfo->prefix.'_'.$module;
          }
          if(!isset($sorted->$class->$module)) $sorted->$class->$module = $_MODULES->$class->$module;
          $checkstate=$_AMI->ModuleCheck($modulename);
          $sorted->$class->$module->loaded=($checkstate['Response']=='Success');
          $sorted->$class->$module->version=($checkstate['Response']=='Success')?($checkstate['Version']):null;
          $somodulename = $modulename.'.so';
          if(isset($ini->modules->$somodulename)) $sorted->$class->$module->mode=$ini->modules->$somodulename;
        }
      }
      unset($ini);
      $_MODULES = $sorted;
      $_CACHE->set('modules', $_MODULES, 60);
    }
    return $_MODULES;
  }

  /**
   * Проверяет наличие модуля ядра технологической платформы
   *
   * @param string $class Класс модуля ядра (см. функцию getAsteriskModules())
   * @param string $module Наименование модуля
   * @param boolean $isloaded Признак загруженности/работы модуля в ядре технологической платформы
   * @return boolean Результат проверки наличия модуля
   */
  protected static function checkModule(string $class, string $module, bool $isloaded = false) {
    $result = false;
    $modules = self::getAsteriskModules();
    if(isset($modules->$class)&&isset($modules->$class->$module)&&(($isloaded==false)||($modules->$class->$module->loaded))) $result = true;
    return $result;
  }

  /**
   * Возвращает результат обработки функции json и устанавливает текст состояния
   *
   * @param mixed $data Ассоциативный массив с набором данных ответа
   * @param string $status Вид ответа: success, info, error, warning
   * @param string $message Опциональный текст сообщения пользователю
   * @return object Возвращает структуру с оформленными параметрами результата
   */
  public static function returnResult($data, $status = null, $message = null) {
    $result = (object) array('result' => $data); //self::_normalizeJSON($data));
    $result->status = 'success';
    if($status) $result->status = $status;
    if($message) $result->statustext = $message;
    return $result;
  }

  /**
   * Возвращает успешный результат обработки функции json и устанавливает текст состояния
   *
   * @param string $message Опциональный текст сообщения пользователю
   * @return object Возвращает структуру с оформленными параметрами результата
   */
  public static function returnSuccess($message = null) {
    $result = (object) array('status' => 'success');
    if($message) $result->statustext = $message;
    return $result;
  }

  /**
   * Возвращает ошибку выполнения функции json и устанавливает текст состояния
   *
   * @param string $status Вид ответа: success, info, danger, warning
   * @param string $message Опциональный текст сообщения пользователю
   * @return object Возвращает объект с оформленными параметрами результата
   */
  public static function returnError($status, $message = null) {
    $result = (object) array('status' => $status);
    if($message) $result->statustext = $message;
    return $result;
  }

  /**
   * Возвращает файл для отдачи его как содержимого из функции json
   *
   * @param string $content Строка с содержимым передвавемого файла
   * @param string $mime Тип данных, по умолчанию text/plain
   * @param string $name Имя файла для передачи клеинту
   * @return object Возвращает объект с оформленными параметрами результата
   */
  public static function returnData(string $content, string $mime = 'text/plain', string $name = '') {
    $result = (object) array('status' => 'success', 'content' => $content, 'content_name' => $name, 'content_type' => $mime);
    return $result;
  }

  /**
   * Возвращает файл для отдачи его как содержимого из функции json
   *
   * @param string $filename Путь к файлу в файловой системе
   * @param string $mime Тип данных, по умолчанию по содержимому файла
   * @param string $name Имя файла для передачи клеинту, по умолчанию из пути к файлу
   * @return object Возвращает объект с оформленными параметрами результата
   */
  public static function returnFile(string $filename, string $mime = '', string $name = '') {
    if($name == '') $name = basename($filename);
    if($mime == '') $mime = mime_content_type($filename);
    $result = (object) array('status' => 'success', 'content_name' => $name, 'content_type' => $mime, 'content' => file_get_contents($filename));
    return $result;
  }

  /**
   * Уведомляет другие модули о возникновении события
   * 
   * @param int $event Код типа события
   * @param \module\ISubject $subject Экземпляр класса по которому производится изменение
   */
  public static function notify(int $event, \module\ISubject &$subject) {
    if(self::$HANDLERS == null) self::initHandlers();
    foreach(self::$HANDLERS[$event] as $handler) {
      if(is_a($subject, $handler->class)) {
        call_user_func_array($handler->call, array($event, &$subject));
      }
    }
  }

  /**
   * Добавляет обработчик события для определенного класса, вызывается при осуществлении указанного действия над экземпляром класса
   *
   * @param int $event Событие
   * @param string $class Класс экземпляра объекта
   * @param callable $handler Коллбэк на функцию обработчик
   * @return void
   */
  public static function setHandler(int $event, string $class, $handler) {
    if(!self::$HANDLERS) self::initHandlers();
    foreach(self::$HANDLERS[$event] as $evthandler) {
      if(($evthandler->class == $class)&&($evthandler->call == $handler)) return;
    }
    self::$HANDLERS[$event][] = (object)array('class' => $class, 'call' => $handler);
  }

  /**
   * Глобальный перечень открытых INI файлов - получение ссылки на экземпляр или создание нового
   *
   * @param string $filename
   * @return \config\INI
   */
  public static function getINI($filename) {
    global $_CACHE;
    if(!self::$INIFILES) {
      self::$INIFILES = $_CACHE->get('INIFILES');
    }
    if(!self::$INIFILES) {
      self::$INIFILES = array();
    }
    if(!isset(self::$INIFILES[$filename])) {
      self::$INIFILES[$filename] = new \config\INI($filename);
    }
    $_CACHE->set('INIFILES', self::$INIFILES, 120);
    return self::$INIFILES[$filename];
  }

  /**
   * Инициализируем обработчики событий в ядре
   *
   * @return void
   */
  private static function initHandlers() {
    global $_CACHE;
    global $_WEBMODULES;
    global $_AGI;
    if(!self::$HANDLERS) self::$HANDLERS = $_CACHE->get('COREHANDLERS');
    if(!self::$HANDLERS) {
      self::$HANDLERS = array(self::ADD => array(), self::RENAME => array(), self::CHANGE => array(), self::REMOVE => array());
      if(!empty($_WEBMODULES)) {
        foreach($_WEBMODULES as $module) {
          $classname = $module->class;
          if(method_exists($classname, 'register')) {
            $classname::register();
          }
        }
      }
      $_CACHE->set('COREHANDLERS', self::$HANDLERS, 300);
    }
  }

  public function lock($lockname) {
    $tmpdir = sys_get_temp_dir();
    $fp = fopen($tmpdir.'/'.$lockname, "w+");
    if($fp) {
      flock($fp, LOCK_EX);
      fclose($fp);
    }
  }

  public function unlock($lockname) {
    $tmpdir = sys_get_temp_dir();
    $fp = fopen($tmpdir.'/'.$lockname, "w+");
    if($fp) {
      flock($fp, LOCK_UN);
      fclose($fp);
      unlink($tmpdir.'/'.$lockname);
    }
  }

  public function interLock($lockname) {
    if(isset(self::$LOCKS[$lockname])) {
      self::$LOCKS[$lockname]++;
    } else {
      self::$LOCKS[$lockname] = 1;
    }
  }

  public function interUnlock($lockname) {
    if(isset(self::$LOCKS[$lockname])) {
      self::$LOCKS[$lockname]--;
      if(self::$LOCKS[$lockname] == 0) {
        unset(self::$LOCKS[$lockname]);
      }
    }
  }

  public function interTestLock($lockname) {
    if(isset(self::$LOCKS[$lockname])&&(self::$LOCKS[$lockname]>0)) {
      return true;
    }
    return false;
  }

  public static function log($level, $message) {
    global $_AGI;
    switch(strtoupper($level)) {
      case 'ERROR': $reallevel = $level; break;
      case 'DANGER': $reallevel = 'ERROR'; break;
      case 'WARNING': $reallevel = $level; break;
      case 'NOTICE': $reallevel = $level; break;
      case 'DEBUG': $reallevel = $level; break;
      case 'VERBOSE': $reallevel = $level; break;
      default: $reallevel = 'VERBOSE';
    }
    if(isset($_AGI)) $_AGI->log($reallevel, $message);
    else error_log(strtoupper($reallevel).': '.$message);
  }

}

?>