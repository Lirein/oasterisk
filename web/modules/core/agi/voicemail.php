<?php

namespace voicemail;

/**
 * Модуль работы с голосовой почтой.
 * Прослушать сообщения:
 * AGI(agi=voicemail)
 * AGI(agi=voicemail,contact=contactid)
 */
class VoicemailModule extends \Module implements \module\IAGI {

  public function agi(\stdClass $request_data) {
    if(isset($request_data->contact)) {

    } else { //

    }
  }

}

?>