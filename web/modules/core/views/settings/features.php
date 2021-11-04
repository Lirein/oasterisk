<?php

namespace core;

class FeaturesSettings extends \view\View {

  public static function getLocation() {
    return 'settings/features';
  }

  public static function getAPI() {
    return 'features';
  }

  public static function getViewLocation() {
    return 'features';
  }

  public static function getMenu() {
    return (object) array('name' => 'Активируемые функции', 'prio' => 2, 'icon' => 'SettingsSharpIcon');
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

        this.sounds = await this.asyncRequest('get-sounds', null, 'rest/general/musiconhold'); //  возможно нужно отображение пути (язык)

        this.general = new widgets.section(parent, 'general',_("Настройки активируемых функций"));
        this.general.transferdigittimeout = new widgets.input(this.general, {id: 'transferdigittimeout', prefix: _("секунд"), pattern: /[0-9]+/}, _("Таймаут нажатия клавиш при переводе звонка"), _("Указывает максимальный таймаут между получением DTMF кодов от абонента во время набора номера, на который требуется перевести звонок"));
        this.general.xfersound = new widgets.select(this.general, {id: 'xfersound', options: this.sounds, clean: true, search: true}, _("Звук успешного перевода вызова"));
        this.general.xfersound.selfalign = {xs:12, sm: 6, lg: 6};
        this.general.xferfailsound = new widgets.select(this.general, {id: 'xferfailsound', options: this.sounds, clean: true, search: true}, _("Звук неудачного перевода вызова"));
        this.general.xferfailsound.selfalign = {xs:12, sm: 6, lg: 6};
        this.general.pickupsound = new widgets.select(this.general, {id: 'pickupsound', options: this.sounds, clean: true, search: true}, _("Звук успешного перехвата"));
        this.general.pickupsound.selfalign = {xs:12, sm: 6, lg: 6};
        this.general.pickupfailsound = new widgets.select(this.general, {id: 'pickupfailsound', options: this.sounds, clean: true, search: true}, _("Звук неудачного перехвата"));
        this.general.pickupfailsound.selfalign = {xs:12, sm: 6, lg: 6};

        this.general.featuredigittimeout = new widgets.input(this.general, {id: 'featuredigittimeout', prefix: _("миллисекунд"), pattern: /[0-9]+/}, _("Таймаут нажатия клавиш при активации функций"), _("Указывает максимальный таймаут между набираемыми цифрами, набираемых для активации функций"));
        this.general.recordingfailsound = new widgets.select(this.general, {id: 'recordingfailsound', options: this.sounds, clean: true, search: true}, _("Звук неудачной попытки активировать запись вызова"));
        this.general.atxfernoanswertimeout = new widgets.input(this.general, {id: 'atxfernoanswertimeout', prefix: _("секунд"), pattern: /[0-9]+/}, _("Таймаут ответа при сопровождаемом переводе вызова"), _("Время ожидания ответа при сопровождаемом переводе вызова, прежде чем удерживаемый вызов вернется к инициатору перевода или будет отключен"));
        this.general.atxferdropcall = new widgets.toggle(this.general, {single: true, id: 'atxferdropcall', value: false}, _("Прекратить вызов при неудачном переводе вызова"), _("Определяет действия с входящим вызовом, в случае неудачного перевода вызова. Если данный параметр = 'no', тогда в случае неудачи, пытается повторить перевод, через заданный период, предпринимая  заданное количествово попыток. Если же установлено 'yes', то все каналы участвующие в переводе отключаются"));
        this.general.atxferdropcall.onChange = (e) => { 
          if(e.getValue()) {
            this.general.atxferloopdelay.hide();
            this.general.atxfercallbackretries.hide();
          } else {
            this.general.atxferloopdelay.show();
            this.general.atxfercallbackretries.show();
          }
        };
        this.general.atxferloopdelay = new widgets.input(this.general, {id: 'atxferloopdelay', prefix: _("секунд"), pattern: /[0-9]+/}, _("Период между попытками повторного перевода вызова"));
        this.general.atxfercallbackretries = new widgets.input(this.general, {id: 'atxfercallbackretries', pattern: /[0-9]+/}, _("Количество попыток перевода"));
        
        this.general.transferdialattempts = new widgets.input(this.general, {id: 'transferdialattempts', pattern: /[0-9]+/}, _("Количество попыток набора для перевода вызова"), _("Количество попыток набора для перевода вызова до возвращения к изначальному вызову"));
        this.general.transferretrysound = new widgets.select(this.general, {id: 'transferretrysound', options: this.sounds, clean: true, search: true}, _("Звук оповещения об исчерпании лимита попыток перевода"));
        this.general.transferinvalidsound = new widgets.select(this.general, {id: 'transferinvalidsound', options: this.sounds, clean: true, search: true}, _("Звук оповещения о некорректном вводе екстеншена"));

        this.featuremap = new widgets.section(parent, 'featuremap',_("Активируемые функции"), _('Определяет комбинации DTMF кодов для активации функций во время звонка'));
        this.featuremap.blindxfer = new widgets.input(this.featuremap, {id: 'blindxfer', pattern: /[0-9*#A-D]+/, placeholder: '#'}, _("Слепой перевод"));
        this.featuremap.atxfer = new widgets.input(this.featuremap, {id: 'atxfer', pattern: /[0-9*#A-D]+/, placeholder: _('Укажите номер')}, _("Сопровождаемый (контролируемый) перевод"));
        this.featuremap.automon = new widgets.input(this.featuremap, {id: 'automon',pattern: /[0-9*#A-D]+/, placeholder: _('Укажите номер')}, _("Включение записи разговора"), _("Раздельная запись входящего и исходящего каналов"));
        this.featuremap.automixmon = new widgets.input(this.featuremap, {id: 'automixmon',pattern: /[0-9*#A-D]+/, placeholder: _('Укажите номер')}, _("Включение микшированной записи разговора"), _("Запись входящего и исходящего каналов в один файл"));
        this.featuremap.pickupexten = new widgets.input(this.featuremap, {id: 'pickupexten',pattern: /[0-9*#A-D]+/, placeholder: '*8'}, _("Код функции перехвата входящих вызовов"));
        this.featuremap.parkcall = new widgets.input(this.featuremap, {id: 'parkcall',pattern: /[0-9*#A-D]+/, placeholder: _('Укажите номер')}, _("Парковка вызова"));
        this.featuremap.disconnect = new widgets.input(this.featuremap, {id: 'disconnect',pattern: /[0-9*#A-D]+/, placeholder: '*'}, _("Прекращение вызова"));
        
        this.featuremap.atxfer.additional = new widgets.section(this.featuremap, null);
        this.featuremap.atxferabort = new widgets.input(this.featuremap.atxfer.additional, {id: 'atxferabort',pattern: /[0-9*#A-D]+/, placeholder: '*номер'}, _("Код завершения перевода вызова"), _("Завершает перевод вызова. При сопровождаемом переводе, набор данного кода возвращает удерживаемый вызов инициатору перевода."));
        this.featuremap.atxfercomplete = new widgets.input(this.featuremap.atxfer.additional, {id: 'atxfercomplete',pattern: /[0-9*#A-D]+/, placeholder: '*номер'}, _("Код завершения сопровождаемого перевода"), _("Завершает сопровождаемый перевод и прекращает вызов"));
        this.featuremap.atxferthreeway = new widgets.input(this.featuremap.atxfer.additional, {id: 'atxferthreeway',pattern: /[0-9*#A-D]+/, placeholder: '*номер'}, _("Код создание трёхсторонней конференции"), _("Завершает перевод вызова, но оставляет на связи. Создаёт трёхсторонную конференцию между участниками вызова"));
        this.featuremap.atxferswap = new widgets.input(this.featuremap.atxfer.additional, {id: 'atxferswap',pattern: /[0-9*#A-D]+/, placeholder: '*номер'}, _("Код переключения между двумя соединяющими сторонами"), _("Может использоваться неоднократно"));

        this.applicationmap = new widgets.section(parent, null, _("Назначение пользовательских функций "), _("Укажите последовательность DTMF кодов для вызова действия"));
        this.applicationmap.collection = new widgets.collection(this.applicationmap, {id: 'applicationmap', options: [true], value: [], entry: 'features/custom/entry', select: 'features/custom/select'}, null);

        this.onReset = this.reset; //Reset example

        this.hasSave = true;
      }

      function setValue(data) {
        this.parent.setValue(data);
        this.general.atxferdropcall.onChange(this.general.atxferdropcall);        
      }

      function setMode(mode) {
        this.parent.renderLock();
        switch(mode) {
          case 'basic': {
            this.general.hide();
            this.featuremap.atxfer.additional.hide();
            this.applicationmap.hide();
          } break;
          case 'advanced': {
            this.general.hide();
            this.featuremap.atxfer.additional.show();
            this.applicationmap.show();
          } break;
          case 'expert': {
            this.general.show();
            this.featuremap.atxfer.additional.show();
            this.applicationmap.show();
          } break;
        }
        this.parent.renderUnlock();
        this.parent.redraw();
      }

      function reset() {
        this.setValue({"featuremap": {
                          "blindxfer": "#1",
                          "disconnect": "*0",
                          "automon": "*1",
                          "atxfer": "*2",
                          "parkcall": "#72",
                          "automixmon": "*3",
                          "pickupexten": "*8",
                          "atxferabort": "*1",
                          "atxfercomplete": "*2",
                          "atxferthreeway": "*3",
                          "atxferswap": "*4"
                        }, "general": {
                          "transferdigittimeout": "3",
                          "xfersound": "beep",
                          "xferfailsound": "beeperr",
                          "pickupsound": "beep",
                          "pickupfailsound": "beeperr",
                          "featuredigittimeout": "1000",
                          "recordingfailsound": "beeperr",
                          "atxfernoanswertimeout": "15",
                          "atxferdropcall": "false",
                          "atxferloopdelay": "10",
                          "atxfercallbackretries": "2",
                          "transferdialattempts": "3",
                          "transferretrysound": "beep",
                          "transferinvalidsound": "beeperr"
                        }, "applicationmap": []
                        });
      };

      </script>;
    <?php
  }
}

?>