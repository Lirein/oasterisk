<?php

namespace core;

class RTPSettings extends ViewModule {
  
  public static function getLocation() {
    return 'settings/general/rtp';
  }

  public static function getMenu() {
    return (object) array('name' => 'Параметры RTP', 'prio' => 2, 'icon' => 'oi oi-rss');
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
    $this->ami->send_request('Command', array('Command' => 'rtp reload'));
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    static $generalparams = '{
      "rtpstart": "5000",
      "rtpend": "31000",
      "rtpchecksums": "!no",
      "dtmftimeout": "3000",
      "rtcpinterval": "5000",
      "strictrtp": "!yes",
      "probation": "4",
      "icesupport": "!true",
      "stunaddr": "",
      "stun_blacklist": [],
      "turnaddr": "",
      "turnusername": "",
      "turnpassword": "",
      "ice_blacklist": [],
      "dtls_mtu": "1200"
    }';
    switch($request) {
      case "rtp-get":{
        $ini = new \INIProcessor('/etc/asterisk/rtp.conf');     
        $result = self::returnResult($ini->general->getDefaults($generalparams));
      } break;
      case "rtp-set":{
        if($this->checkPriv('settings_writer')) {
          $ini = new \INIProcessor('/etc/asterisk/rtp.conf');
          $ini->general->setDefaults($generalparams, $request_data);
          
          if($ini->save()) {
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

      var language_data = [];

      function loadRTP() {
        sendRequest('rtp-get').success(function(data) {
          card.setValue(data);
        });
      }

      function sendRTP() {
        sendRequest('rtp-set', card.getValue()).success(function() {
          loadRTP()
          return true;
        });
      }



<?php
if(self::checkPriv('settings_writer')) {
     ?>

      function sbapply(e) {
        sendRTP();
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

        subcard1 = new widgets.section(col1,null,_("Диапазон RTP портов"));

        var subcard = new widgets.section(subcard1,null);
        subcard.node.classList.add('row');
        cont = new widgets.columns(subcard,2);
        obj = new widgets.input(cont, {id: 'rtpstart', pattern: '[0-9]+'}, 
            "От");
        cont = new widgets.columns(subcard,2);
        obj = new widgets.input(cont, {id: 'rtpend', pattern: '[0-9]+'}, 
            "До");

        subcard2 = new widgets.section(col1,null,_("Настройки RTP"));

        obj = new widgets.checkbox(subcard2, {single: true, id: 'rtpchecksums', value: false},
            "Подсчитывать контрольную сумму для UDP");
        obj = new widgets.input(subcard2, {id: 'dtmftimeout', pattern: '[0-9]+'},
            "Длительность DTMF сигнализации в звуковом тракте",
            "Длительность отправляемого DTMF сигнала в самплах, равных 1/8000 секунды");
        obj = new widgets.input(subcard2, {id: 'rtcpinterval', pattern: '[0-9]+'},
            "Таймаут между отчётами RTCP");
        obj = new widgets.checkbox(subcard2, {single: true, id: 'strictrtp', value: false},
            "Отбрасывать RTP пакеты, который приходят не от источника RTP");
        obj = new widgets.input(subcard2, {id: 'probation', pattern: '[0-9]+'},
            "Количество RTP пакетов необходимое чтобы запомнить новый адрес вещания",
            "Количество RTP пакетов, которое должно быть получено с другого адреса/порта источника чтобы запомнить новый адрес вещания. Опция активна, если активировано отбрасывание RTP пакетов");
        obj = new widgets.input(subcard2, {id: 'dtls_mtu', pattern: '[0-9]+'},
            "MTU для фрагментации пакетов DTLS");

        subcard3 = new widgets.section(col2,null,_("Поддержка I.C.E."));

        obj = new widgets.checkbox(subcard3, {single: true, id: 'icesupport', value: false},
            "Включить поддержку ICE");
        obj = new widgets.input(subcard3, {id: 'stunaddr', pattern: '[0-9.:/_A-z-]+', placeholder: 'Не задано'}, 
             "Адрес STUN сервера");
        obj = new widgets.iplist(subcard3, {id: 'stun_blacklist'}, 
            "Запрещённые STUN");

        obj = new widgets.iplist(subcard3, {id: 'ice_blacklist'}, 
          "Запрещённые ICE");    

        subcard4 = new widgets.section(col2,null,_("Настройки серверов TURN"));

        obj = new widgets.input(subcard4, {id: 'turnaddr', pattern: '[0-9.:/_A-z-]+', placeholder: 'Не задано'}, 
            "Адрес TURN сервера ретрансляции");
        obj = new widgets.input(subcard4, {id: 'turnusername', pattern: '[0-9_A-z]+', placeholder: 'Не задано'}, 
            "Имя пользователя для авторизации на TURN сервере");
        obj = new widgets.input(subcard4, {password: true, id: 'turnpassword', placeholder: 'Не задано'}, 
            "Пароль для авторизации на TURN сервере");

<?php
if(!self::checkPriv('settings_writer')) {
      ?>
    card.disable();
<?php
}
    ?>
        loadRTP();
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