<?php

namespace core;

class FaxSettings extends ViewModule {
  public static function getLocation() {
    return 'settings/general/fax';
  }

  public static function getMenu() {
    return (object) array('name' => 'Настройки факса', 'prio' => 5, 'icon' => 'oi oi-print');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  /**
   * Перегружает конфигурацию на стороне технологической платформы
   *
   * @return void
   */
  public function reloadConfig() {
    $this->ami->send_request('Command', array('Command' => 'fax reload'));
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    static $faxparams = '{
      "maxrate": "14400",
      "minrate": "4800",
      "statusevents": "!no",
      "modems": ["v17","v27","v29"],
      "ecm": "!yes",
      "t38timeout": "5000"
    }';
    static $udptlparams = '{
      "udptlstart": "4000",
      "udptlend": "4999",
      "udptlchecksums": "!no",
      "udptlfecentries": "3",
      "udptlfecspan": "3",
      "use_even_ports": "!no"
    }';
    switch($request) {
      case "fax-get":{
        $ini = new \INIProcessor('/etc/asterisk/res_fax.conf');
        $ini2 = new \INIProcessor('/etc/asterisk/udptl.conf');
        $returnData = new \stdClass();
        $returnData->fax = $ini->general->getDefaults($faxparams);
        $returnData->udptl = $ini2->general->getDefaults($udptlparams);
        $result = self::returnResult($returnData);
      } break;
      case "fax-set":{
        if($this->checkPriv('settings_writer')) {
          $ini = new \INIProcessor('/etc/asterisk/res_fax.conf');
          $ini2 = new \INIProcessor('/etc/asterisk/udptl.conf');
          if ($request_data->fax->maxrate < $request_data->fax->minrate){
            $tmp = $request_data->fax->maxrate;
            $request_data->fax->maxrate = $request_data->fax->minrate;
            $request_data->fax->minrate = $tmp;
          }
          if ($request_data->udptl->udptlend < $request_data->udptl->udptlstart){
            $tmp = $request_data->udptl->udptlend;
            $request_data->udptl->udptlend = $request_data->udptl->udptlstart;
            $request_data->udptl->udptlstart = $tmp;
          }
          $ini->general->setDefaults($faxparams, $request_data->fax);
          $ini2->general->setDefaults($udptlparams, $request_data->udptl);
          if(($ini->save()) && ($ini2->save())) {
            $result = self::returnSuccess();
            $this->reloadConfig();
          } else {
            $result = self::returnError('danger', 'Невозможно сохранить настройки');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
    }
    return $result;
  }

  public function scripts() {
    ?>
      <script>

      function loadFax() {
        sendRequest('fax-get').success(function(data) {
          card.setValue(data);
        });
      }

      function sendFax() {
        sendRequest('fax-set', card.getValue()).success(function() {
          loadFax();
          return true;
        });
      }

      
<?php
if(self::checkPriv('settings_writer')) {
      ?>

      function sbapply(e) {
        sendFax();
      }

<?php
} else {
      ?>

    var sbapply=null;

<?php
}
    ?>

      $(function () {
        sidebar_apply(sbapply);
        card = new widgets.section(rootcontent,null);
        var cols = new widgets.section(card,null);
        cols.node.classList.add('row');
        var col1 = new widgets.columns(cols,2);
        var col2 = new widgets.columns(cols,2);

        subcard1 = new widgets.section(col1,'fax',_("Основные параметры"));

        obj = new widgets.select(subcard1, {id: 'maxrate', value: ['2400', '4800', '7200', '9600', '12000', '14400'],search: false},
            "Максимальная скорость передачи");
        obj = new widgets.select(subcard1, {id: 'minrate', value: ['2400', '4800', '7200', '9600', '12000', '14400'],search: false},
            "Минимальная скорость передачи");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'statusevents', value: false},
            "Отправлять события в AMI-интерфейс о ходе передачи факса");
        obj = new widgets.list(subcard1, {id: 'modems', value: ['v17', 'v27', 'v29'], clean: true, checkbox: true, sorted: true},
            "Тип используемого модема"); 
        obj = new widgets.checkbox(subcard1, {single: true, id: 'statusevents', value: false},
            "Включить корректор ошибок");
        obj = new widgets.input(subcard1, {id: 't38timeout',pattern: '[0-9]+'},
            "Таймаут согласования передачи по протоколу t38 в миллисекундах");
        
        subcard2 = new widgets.section(col2,'udptl');

        subcard3 = new widgets.section(subcard2,null,_("Диапазон UDPTL адресов"));
        
        var subcard = new widgets.section(subcard3,null);
        subcard.node.classList.add('row');
        cont = new widgets.columns(subcard,2);
        obj = new widgets.input(cont, {id: 'udptlstart', pattern: '[0-9]+'}, 
            "От");
        cont = new widgets.columns(subcard,2);
        obj = new widgets.input(cont, {id: 'udptlend', pattern: '[0-9]+'}, 
            "До");

        subcard4 = new widgets.section(subcard2,null,_("Настройки UDPTL"));
            
        obj = new widgets.checkbox(subcard4, {single: true, id: 'udptlchecksums', value: false},
            "Включить проверку контрольной суммы UDP");
        obj = new widgets.input(subcard4, {id: 'udptlfecentries', pattern: '[0-9]+'}, 
            "Количество записей коррекции ошибок в одном пакете");
        obj = new widgets.input(subcard4, {id: 'udptlfecspan', pattern: '[0-9]+'}, 
            "Частота для передачи контрольной суммы пакета");
        obj = new widgets.checkbox(subcard4, {single: true, id: 'use_even_ports', value: false},
            "Использовать только четные номера потров udp для передачи факса");

<?php
if(!self::checkPriv('settings_writer')) {
      ?>
    card.disable();
<?php
}
    ?>
        loadFax();
      });
    </script>
    <?php
}

  public function render() {
    ?>
        <input type="password" hidden/>
    <?php
}
}

?>