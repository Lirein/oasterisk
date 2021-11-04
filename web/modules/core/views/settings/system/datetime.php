<?php

namespace core;

class DateTimeSettings extends \view\View {

  public static function getLocation() {
    return 'settings/system/datetime';
  }

  public static function getAPI() {
    return 'system/datetime';
  }

  public static function getViewLocation() {
    return 'system/datetime';
  }

  public static function getMenu() {
    return (object) array('name' => 'Дата и время', 'prio' => 2, 'icon' => 'ScheduleSharpIcon');
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
        this.timezones = await this.asyncRequest('timezones');

        this.timezone = new widgets.select(parent, {id: 'timezone', options: this.timezones, default: _('UTC')}, _("Часовой пояс"));

        this.subcard1 = new widgets.section(parent, null);
        this.modelabel = new widgets.label(this.subcard1, null, _("Настройка времени"));
        this.modelabel.selfalign = {xs: 12, lg: 4, style: {alignSelf: 'center'}};
        this.mode = new widgets.toggle(this.subcard1, {id: 'mode'}, null, _('Автоматически'));
        this.mode.selfalign = {xs:12, sm: 6, lg: 4};
        this.mode.onChange = (sender) => {
          if(sender.value) {
            sender.setHint(_('Автоматически'));
            this.time.hide();
          } else {
            sender.setHint(_('Вручную'));
            this.time.show();
          }
        }

        this.timesection = new widgets.section(parent, null);
        this.timesection.selfalign = {xs:12};
        this.timesection.itemsalign = {xs: 12, lg:6};

        this.time = new widgets.datetime(parent, {id: 'unixtimestamp', storeas: 'string', value: moment()}, _("Время"));
         
        this.onReset = super.reset;

        this.hasSave = true;
      }

      function setValue(data){
        console.log(data);
        if (isSet(data.timezone)) {
        let timezone = data.timezone.split('/');
        super.setValue(data);
        //this.timezone.setValue({value: timezone[1]});
        this.mode.onChange(this.mode);
        this.data = data;
        }
      }

      </script>;
    <?php
  }
}

?>