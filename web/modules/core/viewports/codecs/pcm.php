<?php

namespace core;

class CodecsPCMViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'codecs/pcm';
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    return $result;
  }

  public function implementation() {
    ?>
      <script>
      async function init(parent, data) {
        if(!isSet(data)) data = {};

        this.genericplc = new widgets.checkbox(parent, {id: 'genericplc', value: false}, _("Использовать программное кодирование"), _("Включает использование программного кодировния PLC данных аудио-кодека в случае отсутствия аппаратной поддержки, по умолчанию Включено."));   
      }

    </script>
    <?php
  }

}

?>
