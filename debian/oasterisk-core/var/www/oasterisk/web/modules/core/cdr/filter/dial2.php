<?php

namespace core;

class Dial2CdrFilter extends CdrFilter {

  public static function check() {
    return self::checkLicense('oasterisk-core');
  }

  public function apps() {
    return array('AppDial2');
  }

  public function filter($data) {
    $records=&$data->records;
    $record=&$records[$data->record];
    switch($record->entry->app) {
      case 'AppDial2': {
        $record->src->name='Исходящий звонок от АТС';
        $record->dst->user=substr($record->src->channel, strrpos($record->src->channel, '/')+1);
        $record->dst->user=(strrpos($record->dst->user, '-')!==false)?substr($record->dst->user, 0, strrpos($record->dst->user, '-')):$record->dst->user;
        if($record->dst->num=='s') unset($records[$data->record]);
        elseif((strpos($record->src->channel, 'Local')===0)&&(($record->state=='failed')||($record->state=='busy'))) {
          unset($records[$data->record]);
        }
      } break;
    }
    return false;
  }

}

?>
