<?php

namespace toitut;

/**
 * Модуль нормализации номера телефона абонента и приведению его к стандарту ITU-T.
 * Дополнительно пытается найти номер телефона в справочнике после нормализации и выставляет CALLERID(name).
 *
 * Нормализация набираемого номера к DESTINTATION:
 * AGI(agi=toitut,exten)
 * AGI(agi=toitut,exten=${EXTEN})
 * Нормализация CALLERID(num), заменяет текущий CALLERID(num)
 * AGI(agi=toitut,dnid)
 * AGI(agi=toitut,dnid=${CALLERID(num)})
 */
class ITUTModule extends \Module implements \module\IAGI {

  public function agi(\stdClass $request_data) {
    if(isset($request_data->exten)) {
      if($request_data->exten==true) {
        $request_data->exten = $this->agi->extension;
      }
      $destination = $request_data->exten;
      $this->agi->set_variable('DESTINATION', $destination);
      $this->agi->set_variable('CALLTYPE', 'INTERNAL');
    } elseif(isset($request_data->dnid)) {
      $this->agi->set_variable('CALLTYPE', 'INCOMING');
    }
  }

}

?>