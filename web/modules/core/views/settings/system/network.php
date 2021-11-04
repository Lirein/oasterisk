<?php

namespace core;

class NetworkSettings extends \view\View {

  public static function getAPI() {
    return 'system/network/addresses';
  }

  public static function getViewLocation() {
    return 'system/addresses';
  }
  public static function getLocation() {
    return 'settings/system/networks';
  }

  public static function getMenu() {
    return (object) array('name' => 'IP Адреса', 'prio' => 8, 'icon' => 'SettingsEthernetSharpIcon');
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
        if(!isSet(this.__proto__.addressdialog)) {
          this.__proto__.addressdialog = new widgets.dialog(dialogcontent, {id: 'AddressDialog'}, _('Настройки IP адреса'));
        }
        this.addressdialog = this.__proto__.addressdialog;

        this.addresstable = new widgets.table(parent, {id: 'addresses', head: {
          family: _('Тип адреса'),
          title: _('Сеть/Шлюз'),
          adapter: _('Сетевой интерфейс'),
          controls: '',
        }, value: []});
        this.addresstable.selfalign = {xs: 12};
        this.addresstable.disableCellSort('controls');
        this.addresstable.setHeadWidth('controls', '120px');
        this.addresstable.setCellFilter('family', this.filterFamily);
        this.addresstable.setCellFilter('title', this.filterTitle);
        this.addresstable.setCellFilter('controls', this.filterControls);
        this.addresstable.removebtn = new widgets.iconbutton(null, {id: 'remove', color: 'default', onClick: this.removeAddress, icon: 'DeleteSharpIcon'}, null, _('Удалить адрес'));
        this.addresstable.editbtn = new widgets.iconbutton(null, {id: 'edit', color: 'primary', onClick: this.editAddress, icon: 'EditSharpIcon'}, null, _('Редактировать адрес'));
        this.addresstable.setCellControl('controls', [
          this.addresstable.removebtn,
          this.addresstable.editbtn,
        ]);

        this.hasAdd = true;
      }

      async function preload() {
        await super.preload();
        if(!isSet(this.__proto__.addressdialog.view)) await require('system/network/address', this.__proto__.addressdialog);
        this.parent.endload();
      }

      async function load() {
        this.parent.preload();
        this.parent.renderLock();
        let data = null;
        let items = null;
        [data, items] = await Promise.all([this.asyncRequest('get', data), this.getItems()]);
        if((isSet(data.readonly))&&data.readonly) {
          this.showAdd = false;
          this.showRemove = false;
        } else {
          this.showAdd = true;
          this.showRemove = true;
        }
        this.setValue({addresses: items});
        if(this.showAdd && this.hasAdd) {
          this.parent.setAppend(this.add);
        } else {
          this.parent.setAppend(null);
        }
        this.parent.renderUnlock();
        this.parent.endload();
        this.parent.redraw();
      }

      function filterFamily(sender, value, rowdata) {
        if(value == 'ipv4') return _('IP протокол версии 4');
        return _('IP протокол версии 6');
      }

      function filterTitle(sender, value, rowdata) {
        if(rowdata.address == '0.0.0.0') return _('Автоматически');
        if(rowdata.gateway == '0.0.0.0') return _("{0}/{1}").format(rowdata.address, rowdata.prefix);
        return _("{0}/{1} шлюз {2}").format(rowdata.address, rowdata.prefix, rowdata.gateway);
      }

      function filterControls(sender, value, rowdata) {
        if(this.showRemove || (isSet(rowdata.readonly)&&!rowdata.readonly)) {
          this.addresstable.removebtn.hidden = false;
        } else {
          this.addresstable.removebtn.hidden = true;
        }
        return true;
      }

      async function add() {
        this.addressdialog.view.setValue({id: false, adapter: [], family: 'ipv4', address: "0.0.0.0", prefix: "0", gateway: "0.0.0.0", readonly: false});
        this.addressdialog.applyfunc = this.saveAddress;
        this.addressdialog.show();
        super.add();
        return true;
      }

      function editAddress(sender, value) {
        console.log(value);
        this.addressdialog.view.setValue(value.item);
        if (value.item.readonly) {
          this.addressdialog.applyfunc = null;
        } else {
          this.addressdialog.applyfunc = this.saveAddress;
        }
        this.addressdialog.show();
      }

      function removeAddress(e) {
        console.log('remove', this, e);
      }

      function saveAddress(dialog) {
        console.log('save');
        //сохранять изменения
       // this.addressdialog.hide();
        this.load();
        return true;
      }
      
      function setValue(data) {
        this.parent.renderLock();
        super.setValue(data);

        this.parent.renderUnlock();
        this.parent.redraw();
      }

      </script>;
    <?php
  }

}

?>
