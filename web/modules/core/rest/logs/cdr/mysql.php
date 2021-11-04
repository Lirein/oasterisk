<?php

namespace core\cdr;

class Mysql extends \core\ICDRRestInterface {

  public static function getTitle() {
    return 'База данных MySQL';
  }
 
  public function get() {
    $cdr = new \core\MysqlCdrEngineModel(); 
    return $cdr->cast();
  }

  public function set($request_data) {
    $cdr = new \core\MysqlCdrEngineModel(); 
    $cdr->assign($request_data);
    return $cdr->save();
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "createdb": {
        if(isset($request_data->db)&&self::checkPriv('settings_writer')) {
          $cdr = new \core\MysqlCdrEngineModel();
          if ($cdr->createDb($request_data->db)){
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Не удалось создать базу');
          }
          
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "createtable": {
        if(isset($request_data->table)&&self::checkPriv('settings_writer')) {
          $cdr = new \core\MysqlCdrEngineModel();
          $cdr->createTable($request_data->table);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
    }
    return $result;
  }
    
}

?>