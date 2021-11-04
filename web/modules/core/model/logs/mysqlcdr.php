<?php

namespace core;

class MysqlCdrEngineModel extends CdrEngineSettings implements \module\IModel {
  
  /**
   * Открытый INI файл с настройками MySQL
   *
   * @var \config\INI $ini
   */
  private static $ini = null;

  private static $connection = null;

  private static $defaultparams = '{
    "hostname": "",
    "dbname": "",
    "table": "",
    "password": "",
    "user": "",
    "port": "3306",
    "sock": "",
    "cdrzone": "UTC",
    "usegmtime": "!no",
    "charset": "",
    "compat": "!no",
    "ssl_ca": "",
    "ssl_cert": "",
    "ssl_key": ""
  }';

  public function info() {
    return (object) array("id" => 'mysql', "title" => 'База данных MySQL');
  }

  private static function init() {
    if(!self::$ini) self::$ini = self::getINI('/etc/asterisk/cdr_mysql.conf');
  }

  /**
   * Конструктор без аргументов - инициализирует модель
   */
  public function __construct(){
    self::init();
    self::$ini->global->normalize(self::$defaultparams);
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    //unset(self::$ini);
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
    $defaultparams = json_decode(self::$defaultparams);
    if ($property == 'connected') return self::checkSettings(); 
    if(isset($defaultparams->$property)) return self::$ini->global->$property->getValue();
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    $defaultparams = json_decode(self::$defaultparams);
    if (isset($defaultparams->$property)) {
      return self::$ini->global->$property = $value;
    }    
    return null; 
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save(){
    return self::$ini->save();
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    return false;
  }

  /**
    * Возвращает все свойства в виде объекта со свойствами
    *
    * @return \stdClass
    */
  public function cast() {
    $keys = array();
    foreach(json_decode(self::$defaultparams) as $key => $defvalue) {
      $keys[$key] = $this->__get($key);
    }
    $keys['connected']=$this->__get('connected');
    return (object) $keys;
  }
  
  /**
    * Устанавливает все свойства новыми значениями
    *
    * @param stdClass $assign_data Объект со свойствами - ключ→значение 
    */
  public function assign($assign_data){
    foreach(json_decode(self::$defaultparams) as $key => $defvalue) {
      if(isset($assign_data->$key)) $this->__set($key, $assign_data->$key);
    } 
  }

  public static function selectable() {
    return self::checkSettings()==0;
  }

  public function enable() {
    self::init();
    self::$ini->global->table = 'cdr';
    self::$ini->save();
  }

  public function disable() {
    self::init();
    if (isset(self::$ini->global)&&isset(self::$ini->global->table)){
      unset(self::$ini->global->table);
    }
    self::$ini->save();
  }

  private static function checkSettings() {
    self::init();
    $settings = self::$ini->global;
    if(self::$connection == null) {
      try {
        self::$connection = @new \mysqli($settings->hostname, $settings->user, $settings->password, $settings->dbname, $settings->port, $settings->sock);
      } catch(\Exception $e) {
        self::$connection = null;
      }
    }
    if(!self::$connection) {
      self::$connection = null;
      return 1;
    } else {
      if(self::$connection->connect_error!='') {
        self::$connection = null;
        return 1;
      }
      if(!@self::$connection->select_db($settings->dbname)) return 2;
      if(!self::TableExists(self::$connection, $settings->table)) return 3;
    }
    return 0;
  }

  private static function TableExists($sql, $table) {
    $res = $sql->query("SHOW TABLES LIKE \"".$sql->real_escape_string($table)."\""); //PHP Warning:  mysqli::real_escape_string(): Couldn't fetch mysqli
    return $res&&($res->num_rows > 0);
  }

  public function createDb($db) {
    error_log($db);
    self::checkSettings();
    if(self::$connection) {
      error_log(get_class(self::$connection));
      return self::$connection->query("CREATE DATABASE ".self::$connection->real_escape_string($db)); //PHP Warning:  mysqli::real_escape_string(): Couldn't fetch mysqli
    }
  }

  public function createTable($table) {
    self::checkSettings();
    if(self::$connection) {
      $res = self::$connection->query("CREATE TABLE ".self::$connection->real_escape_string($table)." (
      id int(11) NOT NULL AUTO_INCREMENT,
      start datetime DEFAULT NULL,
      answer datetime DEFAULT NULL,
      end datetime DEFAULT NULL,
      clid varchar(80) DEFAULT NULL,
      src varchar(80) DEFAULT NULL,
      dst varchar(80) DEFAULT NULL,
      dcontext varchar(80) DEFAULT NULL,
      channel varchar(80) DEFAULT NULL,
      dstchannel varchar(80) DEFAULT NULL,
      lastapp varchar(80) DEFAULT NULL,
      lastdata varchar(80) DEFAULT NULL,
      duration int(11) DEFAULT NULL,
      billsec int(11) DEFAULT NULL,
      disposition varchar(45) DEFAULT NULL,
      amaflags varchar(45) DEFAULT NULL,
      accountcode varchar(20) DEFAULT NULL,
      uniqueid varchar(150) NOT NULL,
      linkedid varchar(150) DEFAULT NULL,
      peeraccount varchar(20) DEFAULT NULL,
      recordingfile varchar(255) DEFAULT NULL,
      sequence int(11) NOT NULL,
      action varchar(35) DEFAULT NULL,
      PRIMARY KEY (id)
      ) ENGINE=InnoDB AUTO_INCREMENT=21666 DEFAULT CHARSET=utf8mb4");
    }
  }
}
?>