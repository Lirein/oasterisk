<?php

namespace core;

class SQLiteCdrEngineSettings extends CdrEngineSettings {

  private static $sqlite3 = '{
    "table": "cdr",
    "columns": "",
    "values": ""
  }';

  public function info() {
    return (object) array("id" => 'sqlite', "title" => 'База данных SQLite');
  }

  public static function check() {
    $result = true;
    $result &= self::checkModule('cdr', 'sqlite3_custom', true);
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function enable() {
    $ini = new \INIProcessor('/etc/asterisk/cdr_sqlite3_custom.conf');
    $ini->master->table = 'cdr';
    $ini->save();
    unset($ini);
  }

  public function disable() {
    $ini = new \INIProcessor('/etc/asterisk/cdr_sqlite3_custom.conf');
    if (isset($ini->master)){
      unset($ini->master);
    }
    $ini->save();
    unset($ini);
  }

}

?>
