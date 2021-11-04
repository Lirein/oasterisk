<?php

namespace core;

class RoutingSettings extends \view\View {

  public static function getAPI() {
    return 'system/network/routes';
  }

  public static function getViewLocation() {
    return 'system/routes';
  }
  public static function getLocation() {
    return 'settings/system/routes';
  }

  public static function getMenu() {
    return (object) array('name' => 'Статические маршруты', 'prio' => 8, 'icon' => 'SettingsEthernetSharpIcon');
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
        if(!isSet(this.__proto__.routedialog)) {
          this.__proto__.routedialog = new widgets.dialog(dialogcontent, {id: 'RouteDialog'}, _('Настройки маршрута'));
        }
        this.routedialog = this.__proto__.routedialog;

        this.routetable = new widgets.table(parent, {id: 'routes', head: {
          title: _('Сеть/Шлюз'),
          controls: '',
        }, value: []});
        this.routetable.selfalign = {xs: 12};
        this.routetable.disableCellSort('controls');
        this.routetable.setHeadWidth('controls', '120px');
        this.routetable.setCellFilter('title', this.filterTitle);
        this.routetable.setCellFilter('controls', this.filterControls);
        this.routetable.removebtn = new widgets.iconbutton(null, {id: 'remove', color: 'default', onClick: this.removeRoute, icon: 'DeleteSharpIcon'}, null, _('Удалить адрес'));
        this.routetable.editbtn = new widgets.iconbutton(null, {id: 'edit', color: 'primary', onClick: this.editRoute, icon: 'EditSharpIcon'}, null, _('Редактировать адрес'));
        this.routetable.setCellControl('controls', [
          this.routetable.removebtn,
          this.routetable.editbtn,
        ]);

        this.hasAdd = true;
      }

      async function add() {
        this.routedialog.view.setValue({id: false, adapter: [], family: 'ipv4', from: null, address: "0.0.0.0", prefix: "0", gateway: "0.0.0.0", onlink: false, metric: null, type: null, scope: null, table: null, mtu: null, readonly: false});
        this.routedialog.applyfunc = this.saveRoute;
        this.routedialog.show();
        super.add();
        return true;
      }

      async function preload() {
        await super.preload();
        if(!isSet(this.__proto__.routedialog.view)) await require('system/network/route', this.__proto__.routedialog);
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
        this.setValue({routes: items});
        if(this.showAdd && this.hasAdd) {
          this.parent.setAppend(this.add);
        } else {
          this.parent.setAppend(null);
        }
        this.parent.renderUnlock();
        this.parent.endload();
        this.parent.redraw();
      }

      function filterTitle(sender, value, rowdata) {
        if(rowdata.address == '0.0.0.0') return _('Автоматически');
        if(rowdata.gateway == '0.0.0.0') return _("{0}/{1}").format(rowdata.address, rowdata.prefix);
        return _("{0}/{1} шлюз {2}").format(rowdata.to, rowdata.prefix, rowdata.via);
      }

      function filterControls(sender, value, rowdata) {
        if(this.showRemove || (isSet(rowdata.readonly)&&!rowdata.readonly)) {
          this.routetable.removebtn.hidden = false;
        } else {
          this.routetable.removebtn.hidden = true;
        }
        return true;
      }

      function editRoute(sender, value) {
        this.routedialog.view.setValue(value.item);
        if (value.item.readonly) {
          this.routedialog.applyfunc = null;
        } else {
          this.routedialog.applyfunc = this.saveRoute;
        }
        this.routedialog.show();
      }

      function removeRoute(e) {
        console.log('remove', this, e);
      }

      function saveRoute(dialog) {
        console.log('save');
        //сохранять изменения
       // this.routedialog.hide();
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
