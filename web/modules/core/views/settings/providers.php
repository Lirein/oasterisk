<?php

namespace core;

class ProviderSettingsView extends \view\Collection {

  public static function getLocation() {
    return 'settings/provider';
  }

  public static function getAPI() {
    return 'provider';
  }

  public static function getViewLocation() {
    return 'provider';
  }

  public static function getMenu() {
    return (object) array('name' => 'Провайдеры', 'prio' => 1, 'icon' => 'PublicSharpIcon');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function implementation() {
    ?>
      <script>
      async function init(parent, data) {
        this.id = null;
        this.name = new widgets.input(parent, {id: 'name'}, _('Название провайдера'));
        this.phone = new widgets.input(parent, {id: 'phone'}, _('Номер телефона'), _('Номер телефона, используемый для входящих звонков на АТС, через данного провадйера'));
        this.type = new widgets.select(parent, {id: 'type', expand: true, search: false, options: await this.asyncRequest('types'), clean: true}, _('Тип провайдера'));
        this.type.onChange = this.onTypeSelect;
        this.settings = new widgets.section(parent, 'account');

        this.hasAdd = true;
      }

      function add() {
        this.id = null;
        rootcontent.show();
        this.setValue({
          name: _('Новый провайдер'),
          phone: '',
          type: 'sip',
          settings: {}
        });
        sidebar_apply(saveEntry);
        setCurrentID(null);
        return true;
      }

      function setValue(data) {
        this.settings.clear();
        this.id = data.id;
        super.setValue(data);
        if(!isSet(data.numberformat)) data.numberformat = {itut: true};
        this.onTypeSelect(this.type);
      }

      function getValue() {
        let result = {};
        result.id = this.id;
        result.name = this.name.getValue();
        result.phone = this.phone.getValue();
        result.type = this.type.getValue();
        result.account = this.settings.view.getValue();
        result.numberformat = result.account.numberformat;
        delete result.account.numberformat;
        return result;
      }

      async function onTypeSelect(sender) {
        let type = sender.getValue();
        let newdata = {};
        Object.assign(newdata, this.data.account, {numberformat: this.data.numberformat});
        await require('provider/'+type, this.settings, newdata);
      }

      function addProvider() {
        
      }

      </script>;
    <?php
  }

}

?>