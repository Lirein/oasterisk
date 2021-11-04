<?php

// namespace core;

// class MysqlCdrEngineSettings extends CdrEngineSettings {

//   private static $defaultsettings = '{
//     "hostname": "localhost",
//     "table": "cdr",
//     "dbname": "cdrdb",
//     "password": "asterisk",
//     "user": "asterisk",
//     "port": 3306,
//     "sock": ""
//   }';

//   private static $mysql = '{
//     "hostname": "",
//     "dbname": "",
//     "table": "",
//     "password": "",
//     "user": "",
//     "port": "3306",
//     "sock": "",
//     "cdrzone": "UTC",
//     "usegmtime": "!no",
//     "charset": "",
//     "compat": "!no",
//     "ssl_ca": "",
//     "ssl_cert": "",
//     "ssl_key": ""
//   }';

//   private static $connection = null;

//   public function info() {
//     return (object) array("id" => 'mysql', "title" => 'База данных MySQL');
//   }

  
//   // public function getParams() {
//   //   $result = new \stdClass();
//   //   // $ini = self::getINI('/etc/asterisk/cdr_mysql.conf');
//   //   // $result = $ini->global->getDefaults(self::$mysql);
//   //   // $result->connected = self::checkSettings();
//   //   return $result;
//   // }

//   // public function setParams($data) {
//   //   $ini = self::getINI('/etc/asterisk/cdr_mysql.conf');
//   //   //$ini->global->setDefaults(self::$mysql, $data);
//   //   return $ini->save();
//   // }


//   // private static function TableExists($sql, $table) {
//   //   $res = $sql->query("SHOW TABLES LIKE \"".$sql->real_escape_string($table)."\"");
//   //   return $res&&($res->num_rows > 0);
//   // }

//   // private static function checkSettings() {
//   //   $ini = self::getINI('/etc/asterisk/cdr_mysql.conf');
//     // $settings = $ini->global->getDefaults(self::$defaultsettings);
//     // if(self::$connection == null) {
//     //   try {
//     //     self::$connection = @new \mysqli($settings->hostname, $settings->user, $settings->password, $settings->dbname, $settings->port, $settings->sock);
//     //   } catch(\Exception $e) {
//     //     self::$connection = null;
//     //   }
//     // }
//     // if(!self::$connection) {
//     //   self::$connection = null;
//     //   return 1;
//     // } else {
//     //   if(self::$connection->connect_error!='') {
//     //     self::$connection = null;
//     //     return 1;
//     //   }
//     //   if(!@self::$connection->select_db($settings->dbname)) return 2;
//     //   if(!self::TableExists(self::$connection, $settings->table)) return 3;
//     // }
//   //   return 0;
//   // }

//   // public static function selectable() {
//   //   return self::checkSettings()==0;
//   // }

//   // public function enable() {
//   //   $ini = self::getINI('/etc/asterisk/cdr_mysql.conf');
//   //   $ini->global->table = 'cdr';
//   //   $ini->save();
//   //   unset($ini);
//   // }

//   // public function disable() {
//   //   $ini = self::getINI('/etc/asterisk/cdr_mysql.conf');
//   //   if (isset($ini->global)&&isset($ini->global->table)){
//   //     unset($ini->global->table);
//   //   }
//   //   $ini->save();
//   //   unset($ini);
//   // }

//   // public function createDb($db) {
//   //   self::checkSettings();
//   //   if(self::$connection) {
//   //     self::$connection->query("CREATE DATABASE ".self::$connection->real_escape_string($db));
//   //   }
//   // }

//   // public function createTable($table) {
//   //   self::checkSettings();
//   //   if(self::$connection) {
//   //     $res = self::$connection->query("CREATE TABLE ".self::$connection->real_escape_string($table)." (
//   //     id int(11) NOT NULL AUTO_INCREMENT,
//   //     start datetime DEFAULT NULL,
//   //     answer datetime DEFAULT NULL,
//   //     end datetime DEFAULT NULL,
//   //     clid varchar(80) DEFAULT NULL,
//   //     src varchar(80) DEFAULT NULL,
//   //     dst varchar(80) DEFAULT NULL,
//   //     dcontext varchar(80) DEFAULT NULL,
//   //     channel varchar(80) DEFAULT NULL,
//   //     dstchannel varchar(80) DEFAULT NULL,
//   //     lastapp varchar(80) DEFAULT NULL,
//   //     lastdata varchar(80) DEFAULT NULL,
//   //     duration int(11) DEFAULT NULL,
//   //     billsec int(11) DEFAULT NULL,
//   //     disposition varchar(45) DEFAULT NULL,
//   //     amaflags varchar(45) DEFAULT NULL,
//   //     accountcode varchar(20) DEFAULT NULL,
//   //     uniqueid varchar(150) NOT NULL,
//   //     linkedid varchar(150) DEFAULT NULL,
//   //     peeraccount varchar(20) DEFAULT NULL,
//   //     recordingfile varchar(255) DEFAULT NULL,
//   //     sequence int(11) NOT NULL,
//   //     action varchar(35) DEFAULT NULL,
//   //     PRIMARY KEY (id)
//   //     ) ENGINE=InnoDB AUTO_INCREMENT=21666 DEFAULT CHARSET=utf8mb4");
//   //   }
//   // }

// }

?>
