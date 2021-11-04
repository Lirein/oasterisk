<?php

namespace core;

class RTPSettings extends \view\View {
  
  public static function getLocation() {
    return 'settings/general/rtp';
  }

  public static function getAPI() {
    return 'general/rtp';
  }

  public static function getViewLocation() {
    return 'general/rtp';
  }

  public static function getMenu() {
    return (object) array('name' => 'Параметры RTP', 'prio' => 2, 'icon' => 'RssFeedSharpIcon', 'mode' => 'expert');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function implementation() {
    ?>
      <script>

      async function init(parent, data) {

        this.subcard2 = new widgets.section(parent,null,_("Настройки RTP"));
        this.subcard1 = new widgets.section(this.subcard2, null);
        this.rtprangelabel = new widgets.label(this.subcard1, null, _("Диапазон RTP портов"));
        this.rtprangelabel.selfalign = {xs: 12, lg: 4, style: {alignSelf: 'end'}};
        this.rtpstart = new widgets.input(this.subcard1, {id: 'rtpstart', pattern: /[0-9]+/}, _("От"));
        this.rtpstart.selfalign = {xs:12, sm: 6, lg: 4};
        this.rtpend = new widgets.input(this.subcard1, {id: 'rtpend', pattern: /[0-9]+/}, _("До"));
        this.rtpend.selfalign = {xs:12, sm: 6, lg: 4};

        this.rtpchecksums = new widgets.checkbox(this.subcard2, {single: true, id: 'rtpchecksums', value: false}, _("Подсчитывать контрольную сумму для UDP"));
        this.dtmftimeout = new widgets.input(this.subcard2, {id: 'dtmftimeout', pattern: /[0-9]+/}, _("Длительность DTMF сигнализации в звуковом тракте"), _("Длительность отправляемого DTMF сигнала в самплах, равных 1/8000 секунды"));
        this.rtcpinterval = new widgets.input(this.subcard2, {id: 'rtcpinterval', pattern: /[0-9]+/}, _("Таймаут между отчётами RTCP"));
        this.strictrtp = new widgets.checkbox(this.subcard2, {single: true, id: 'strictrtp', value: false}, _("Отбрасывать RTP пакеты, который приходят не от источника RTP"));
        this.strictrtp.onChange = (sender) => {
          if(sender.value) {
            this.probation.show();
          } else {
            this.probation.hide();
          }
        }
        this.probation = new widgets.input(this.subcard2, {id: 'probation', pattern: /[0-9]+/}, _("Количество RTP пакетов необходимое чтобы запомнить новый адрес вещания"), _("Количество RTP пакетов, которое должно быть получено с другого адреса/порта источника, чтобы запомнить новый адрес вещания. Опция активна, если активировано отбрасывание RTP пакетов"));
        this.dtls_mtu = new widgets.input(this.subcard2, {id: 'dtls_mtu', pattern: /[0-9]+/}, _("MTU для фрагментации пакетов DTLS"), _("Минимум 256"));

        this.subcard5 = new widgets.section(parent, null, _('Поддержка NAT'));

        this.subcard3 = new widgets.label(this.subcard5, {variant: 'subtitle2'}, _("Поддержка I.C.E."));
        this.icesupport = new widgets.checkbox(this.subcard5, {single: true, id: 'icesupport', value: false}, _("Включить поддержку ICE"));
        this.stunaddr = new widgets.input(this.subcard5, {id: 'stunaddr', pattern: /[0-9.:/_A-z-]+/, placeholder: 'Не задано'}, _("Адрес STUN сервера"));
        this.stunblacklist = new widgets.collection(this.subcard5, {id: 'stun_blacklist', select: 'iplist', entry: 'iplist'}, _("Запрещённые STUN"));
        this.iceblacklist = new widgets.collection(this.subcard5, {id: 'ice_blacklist', select: 'iplist', entry: 'iplist'}, _("Запрещённые ICE"));    

        this.divider = new widgets.divider(this.subcard5);
        this.subcard4 = new widgets.label(this.subcard5, {variant: 'subtitle2'}, _("Настройки серверов TURN"));
        this.turnaddr = new widgets.input(this.subcard5, {id: 'turnaddr', pattern: /[0-9.:/_A-z-]+/, placeholder: 'Не задано'}, _("Адрес TURN сервера ретрансляции"));
        this.turnusername = new widgets.input(this.subcard5, {id: 'turnusername', pattern: /[0-9_A-z]+/, placeholder: 'Не задано'}, _("Имя пользователя для авторизации на TURN сервере"));
        this.turnpassword = new widgets.input(this.subcard5, {password: true, id: 'turnpassword', placeholder: 'Не задано'}, _("Пароль для авторизации на TURN сервере"));
      
        this.onReset = this.reset;

        this.hasSave = true;
      }

      function setValue(data) {
        this.parent.setValue(data);
        this.strictrtp.onChange(this.strictrtp);
      }
  
      function reset() {
        this.setValue({ "rtpstart": "5000",
                        "rtpend": "31000",
                        "rtpchecksums": "false",
                        "dtmftimeout": "3000",
                        "rtcpinterval": "5000",
                        "strictrtp": "true",
                        "probation": "4",
                        "icesupport": "true",
                        "stunaddr": "",
                        "stun_blacklist": [],
                        "turnaddr": "",
                        "turnusername": "",
                        "turnpassword": "",
                        "ice_blacklist": [],
                        "dtls_mtu": "1200"
                        });
      };


      </script>
    <?php
  }
}

?>