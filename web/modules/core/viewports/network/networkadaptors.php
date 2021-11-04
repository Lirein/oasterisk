<?php

namespace core;

class NetworkAdaptorsSettingsView extends \view\ViewPort {

  public static function getAPI() {
    return 'system/network/adapters';
  }

  public static function getViewLocation() {
    return 'system/network/adapters';
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

        this.typelist = [
          {id: "ethernet", title: _("Проводной")},
          {id: "wireless", title: _("Беспроводной")},
          {id: "vlan", title: _("VLAN")},
          {id: "lacp", title: _("Агрегация")},
          {id: "bridge", title: _("Сетевой мост")},
        ];

        this.title = new widgets.input(parent, {id: 'title'}, _("Название адаптера"));
        this.link = new widgets.toggle(this.title, {id: 'link'}, null, _("Подключён"));
        this.link.onChange = (sender) => {
          if(sender.value) {
            sender.setHint(_("Подключён"));
          } else {
            sender.setHint(_("Нет соединения"));
          }
        }
        this.type = new widgets.select(parent, {id: 'type', options: this.typelist, search:false}, _("Тип адаптера"));
        this.type.onChange = (e) => { 
          switch (e.getValue()){
            case 'ethernet': {
              this.model.show();
              this.x802.show();
              this.x802.onChange(this.x802);
              this.wirelessnetwork.hide();
              this.key.hide();
              this.adaptervlan.hide();
              this.number.hide();
              this.adapter.hide();
              this.mode.hide();
              this.spanningtree.hide();
            } break;
            case 'wireless': {
              this.model.show();
              this.x802.hide();
              this.authsection.hide();
              this.wirelessnetwork.show();
              this.wirelessnetwork.onChange(this.wirelessnetwork);
              this.adaptervlan.hide();
              this.number.hide();
              this.adapter.hide();
              this.mode.hide();
              this.spanningtree.hide();
            } break;
            case 'vlan': {
              this.model.hide();
              this.x802.hide();
              this.authsection.hide();
              this.wirelessnetwork.hide();
              this.key.hide();
              this.adapter.hide();
              this.mode.hide();
              this.spanningtree.hide();

              this.adaptervlan.show();
              this.number.show();
            } break;
            case 'lacp': {
              this.model.hide();
              this.x802.hide();
              this.authsection.hide();
              this.wirelessnetwork.hide();
              this.key.hide();
              this.adaptervlan.hide();
              this.number.hide();
              this.adapter.show();
              this.mode.show();
              this.spanningtree.hide();
            } break;
            case 'bridge': {
              this.model.hide();
              this.x802.hide();
              this.authsection.hide();
              this.wirelessnetwork.hide();
              this.key.hide();
              this.adaptervlan.hide();
              this.number.hide();
              this.adapter.show();
              this.mode.hide();
              this.spanningtree.show();
            } break;
            default: {
            
            } break;
          }
        };
        this.model = new widgets.input(parent, {id: 'model'}, _("Модель"));
        this.x802 = new widgets.checkbox(parent, {single: true, id: 'x802', value: false}, _("802.1X"));
        this.x802.onChange = (sender) => {
          if(sender.value) {
            this.authsection.show();
            this.list.onChange(this.list);
          } else {
            this.authsection.hide();
          }
        }

        this.wirelessnetwork = new widgets.select(parent, {id: 'wirelessnetwork', options: [{id: "OpenNetwork", title: _("OpenNetwork")}, {id: "SDES", title: _("SDES")}, {id: "WEP", title: _("WEP")}, {id: "WPA", title: _("WPA/WPA2 PSK")}, {id: "WPA2", title: _("WPA2 Enterprise")}], default: "OpenNetwork", search:false});
        this.wirelessnetwork.onChange = (e) => { 
          switch (e.getValue()){
            case 'OpenNetwork': {
              this.key.hide();
              this.authsection.hide();
            } break;
            case 'SDES': {
              this.key.show();
              this.authsection.hide();
            } break;
            case 'WEP': {
              this.key.show();
              this.authsection.hide();
            } break;
            case 'WPA': {
              this.key.show();
              this.authsection.hide();
            } break;
            case 'WPA2': {
              this.key.hide();
              this.authsection.show();
              this.list.onChange(this.list);
            } break;
            default: {
              this.key.hide();
              this.authsection.hide();
            } break;
          }
        };
        this.key = new widgets.input(parent, {id: 'key', default: ""}, _("Ключ сети"));

        this.authsection = new widgets.section(parent, null);
        this.list = new widgets.select(this.authsection, {id: 'list', options: [{id: "key", title: _("Ключевая пара")}, {id: "cert", title: _("Сертификат")}], default: "key", search:false});
        this.list.onChange = (e) => { 
          switch (e.getValue()){
            case 'key': {
              this.login.show();
              this.password.show();
              this.domen.show();
              this.cert.hide();
            } break;
            case 'cert': {
              this.login.hide();
              this.password.hide();
              this.domen.hide();
              this.cert.show();
            } break;
            default: {
              this.login.hide();
              this.password.hide();
              this.domen.hide();
              this.cert.hide();
            } break;
          }
        };
        this.login = new widgets.input(this.authsection, {id: 'login', required:true, default: ""}, _("Логин"));
        this.password = new widgets.input(this.authsection, {id: 'password', password: true, required:true, default: ""}, _("Пароль"));
        this.domen = new widgets.input(this.authsection, {id: 'domen', default: ""}, _("Домен"));
        this.cert = new widgets.input(this.authsection, {id: 'cert', default: ""}, _("Сертификат ЭП"));

        //ToDo adaptervlan - undefined
        this.adaptervlan = new widgets.select(parent, {id: 'adapter', readonly: false, multiple: false, minlines: 0, view: 'system/network/adapters', default: []}, _("Сетевой Адаптер"));
        this.number = new widgets.input(parent, {id: 'number', default: ""}, _("Номер"));

        this.adapter = new widgets.select(parent, {id: 'adapter', readonly: false, multiple: true, minlines: 0, view: 'system/network/adapters', default: []}, _("Сетевой Адаптер"));
        this.mode = new widgets.select(parent, {id: 'mode', options: [{id: "placeholder1", title: _("Резерв")}, {id: "placeholder2", title: _("Балансировка")}, {id: "placeholder3", title: _("Агрегация")}], search:false});
        
        this.spanningtree = new widgets.checkbox(parent, {single: true, id: 'spanningtree', value: false}, _("Spanning Tree"));

        //this.dns = new widgets.collection(parent, {id: 'dns', select: 'iplist', entry: 'iplist'}, _("DNS сервера"));    
        //this.domains = new widgets.input(parent, {id: 'domains'}, _("domains"));    

        this.speed = new widgets.input(parent, {id: 'speed', prefix: _("мегабит в секунду")}, _("Скорость"));
        this.mac = new widgets.input(parent, {id: 'mac'}, _("MAC адрес"));
        this.mtu = new widgets.input(parent, {id: 'mtu', prefix: _("байт")}, _("MTU"));
      }

      function setValue(data) {
        this.parent.renderLock();
        super.setValue(data);
        this.link.onChange(this.link);
        this.type.onChange(this.type);     
        this.type.disable();
        this.link.disable();
        this.model.disable();
        this.speed.disable();
        this.mac.disable();
        this.mtu.disable();
        this.data = data;
        this.parent.renderUnlock();
        this.parent.redraw();
      }

      </script>;
    <?php
  }

}

?>