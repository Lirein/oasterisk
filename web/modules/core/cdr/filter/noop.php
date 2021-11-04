<?php

namespace core;

class NoOpCdrFilter extends CdrFilter {

  public static function check() {
    return self::checkLicense('oasterisk-core');
  }

  public function apps() {
    return array('NoOp','');
  }

  public function filter($data) {
    $records=&$data->records;
    unset($records[$data->record]);
    return false;
  }

}

?>
