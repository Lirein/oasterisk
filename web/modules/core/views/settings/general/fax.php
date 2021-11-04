<?php

namespace core;

class FaxSettings extends \view\View {

  public static function getLocation() {
    return 'settings/general/fax';
  }

  public static function getAPI() {
    return 'general/fax';
  }

  public static function getViewLocation() {
    return 'general/fax';
  }

  public static function getMenu() {
    return (object) array('name' => 'Настройки факса', 'prio' => 5, 'icon' => 'PrintSharpIcon', 'mode' => 'expert');
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

        this.fax = new widgets.section(parent,'fax',_("Основные параметры"));
        this.raterangelabel = new widgets.label(this.fax, null, _("Скорость передачи"));
        this.raterangelabel.selfalign = {xs: 12, lg: 4, style: {alignSelf: 'end'}};
        this.minrate = new widgets.select(this.fax, {id: 'minrate', options: [{id: _("2400"), title: _("2400")}, {id: _("4800"), title: _("4800")}, {id: _("7200"), title: _("7200")}, {id: _("9600"), title: _("9600")}, {id: _("12000"), title: _("12000")}, {id: _("14400"), title: _("14400")}],search: false}, _("От"));
        this.minrate.selfalign = {xs:12, sm: 6, lg: 4};
        this.maxrate = new widgets.select(this.fax, {id: 'maxrate', options: [{id: _("2400"), title: _("2400")}, {id: _("4800"), title: _("4800")}, {id: _("7200"), title: _("7200")}, {id: _("9600"), title: _("9600")}, {id: _("12000"), title: _("12000")}, {id: _("14400"), title: _("14400")}],search: false}, _("До"));
        this.maxrate.selfalign = {xs:12, sm: 6, lg: 4};

        this.statusevents = new widgets.checkbox(this.fax, {single: true, id: 'statusevents', value: false}, _("Отправлять события в AMI-интерфейс о ходе передачи факса"));
        this.ecm = new widgets.checkbox(this.fax, {single: true, id: 'ecm', value: false}, _("Включить корректор ошибок"));
        this.t38timeout = new widgets.input(this.fax, {id: 't38timeout', prefix: _("миллисекунд"), pattern: /[0-9]+/}, _("Таймаут согласования передачи по протоколу t38"));
        this.modems = new widgets.list(this.fax, {id: 'modems', options: [{id: 'v17', title:  _("v17")}, {id: 'v27', title:  _("v27")}, {id: 'v29', title:  _("v29")}],  checkbox: true}, _("Тип используемого модема")); 
        this.modems.selfalign = {xs: 12};

        this.udptl = new widgets.section(parent,'udptl',_("Настройки UDPTL"));
        this.subcard1 = new widgets.section(this.udptl,null);
        this.udptlrangelabel = new widgets.label(this.subcard1, null, _("Диапазон UDPTL адресов"));
        this.udptlrangelabel.selfalign = {xs: 12, lg: 4, style: {alignSelf: 'end'}};
        this.udptlstart = new widgets.input(this.subcard1, {id: 'udptlstart', pattern: /[0-9]+/}, _("От"));
        this.udptlstart.selfalign = {xs:12, sm: 6, lg: 4};
        this.udptlend = new widgets.input(this.subcard1, {id: 'udptlend', pattern: /[0-9]+/}, _("До"));
        this.udptlend.selfalign = {xs:12, sm: 6, lg: 4};
        
        this.udptlchecksums = new widgets.checkbox(this.udptl, {single: true, id: 'udptlchecksums', value: false}, _("Включить проверку контрольной суммы UDP"));
        this.udptlfecentries = new widgets.input(this.udptl, {id: 'udptlfecentries', pattern: /[0-9]+/}, _("Количество записей коррекции ошибок в одном пакете"));
        this.udptlfecspan = new widgets.input(this.udptl, {id: 'udptlfecspan', pattern: /[0-9]+/}, _("Частота для передачи контрольной суммы пакета"));
        this.useevenports = new widgets.checkbox(this.udptl, {single: true, id: 'use_even_ports', value: false}, _("Использовать только четные номера потров udp для передачи факса"));

        this.onReset = this.reset;

        this.hasSave = true;
      }

      function reset() {
        this.setValue({"fax": {
                          "maxrate": "14400",
                          "minrate": "4800",
                          "statusevents": "false",
                          "modems": ["v17","v27","v29"],
                          "ecm": "true",
                          "t38timeout": "5000"
                        }, "udptl": {
                          "udptlstart": "4000",
                          "udptlend": "4999",
                          "udptlchecksums": "false",
                          "udptlfecentries": "3",
                          "udptlfecspan": "3",
                          "use_even_ports": "false"
                        }});
      };

      </script>
    <?php
  }
}

?>