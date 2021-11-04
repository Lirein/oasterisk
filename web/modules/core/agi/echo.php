<?php

namespace echoservice;

/**
 * Модуль эхо теста.
 * AGI(agi=echoservice)
 * Обратный вызов абоеннта через планировщик вызовов
 * AGI(agi=echoservice,callback)
 */
class EchoModule extends \Module implements \module\IAGI {

  public function agi(\stdClass $request_data) {
    if(isset($request_data->contact)) {

    } else { //

    }
  }

}

?>