<?php

namespace core;

class DialCdrFilter extends CdrFilter {

  public static function check() {
    return self::checkLicense('oasterisk-core');
  }

  public function apps() {
    return array('Dial');
  }

  public function filter($data) {
    $records=&$data->records;
    $record=&$records[$data->record];
    switch($record->entry->app) {
      case 'Dial': {
        if(strpos($record->src->channel, 'Local')===0) {
          unset($records[$data->record]);
        }
      } break;
    }
    return false;
  }

}

?>
