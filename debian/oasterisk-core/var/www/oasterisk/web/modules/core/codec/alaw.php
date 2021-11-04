<?php

namespace core;

class aLawCodec extends Codec {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkModule('format', 'pcm', true);
    return $result;
  }

  public function info() {
    $result = (object) array("title" => 'G.711 A-law', "name" => 'alaw');
    return $result;
  }
  
  public function render() {
    return sprintf('
         <div class="form-group row">
          <div class="col">
           <label class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="genericplc" checked>
            <span class="custom-control-label">Использовать программное кодирование
             <span class="badge badge-pill badge-info" data-toggle="popover" data-placement="top" title="Программное кодирование" data-content="Включает использование программного кодировния PLC данных аудио-кодека в случае отсутствия аппаратной поддержки, по умолчанию Включено." data-trigger="hover" data-html=true>?</span>
            </span>
           </label>
          </div>
         </div>
    ');
  }

}

?>