<?php

namespace core;

class NetworkRouteSettings extends \view\ViewPort {
  
  public static function getAPI() {
    return 'system/network/route';
  }

  public static function getViewLocation() {
    return 'system/network/route';
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
        this.adapter = new widgets.select(parent, {id: 'adapter', readonly: false, required: true, placeholder: 'Не задано', view: 'system/network/adapters', default: ""}, _("Адаптер"));
        this.adapter.onChange = async (e) => { 
          data = await this.asyncRequest('addresses', {id: e.getValue()}, 'rest/system/network/routes');
          this.from.setValue({options: data, value: data[0]});
        };

        this.family = new widgets.select(parent, {id: 'family', expand: true, default: 'ipv4', options: [{id: 'ipv4', title: _("IP протокол версии 4")}, {id: 'ipv6', title: _("IP протокол версии 6")}]}, _("Тип адреса"));
        this.family.onChange = async (e) => { 
          await require('system/network/address/'+e.getValue(), this.address, this.data);
        };

        this.from = new widgets.select(parent, {id: 'from', readonly: false, placeholder: 'Не задано', options: [], default: ""}, _("from"));
        
        this.address = new widgets.section(parent, null);
        this.address.itemsalign = {xs: 12, md: 6};
        this.address.selfalign = {xs: 12};

        this.onlink = new widgets.checkbox(parent, {id: 'on-link', single: true, value: false}, _("Только при наличии подключения"));
        this.metric = new widgets.input(parent, {id: 'metric', pattern: /[0-9]+/, placeholder: 'Не задано'}, _("Метрика маршрута"));
        this.type = new widgets.select(parent, {id: 'type', expand: true, options: [{id: 'unicast', title: _("unicast")}, {id: 'unreachable', title: _("unreachable")}, {id: 'blackhole', title: _("blackhole")}, {id: 'prohibit', title: _("prohibit")}]}, _("Тип маршрута"));
        this.scope = new widgets.select(parent, {id: 'scope', expand: true, options: [{id: 'global', title: _("global")}, {id: 'link', title: _("link")}, {id: 'host', title: _("host")}]}, _("The route scope"));
        this.mtu = new widgets.input(parent, {id: 'mtu', pattern: /[0-9]+/, placeholder: 'Не задано'}, _("Path MTU"));

        
      }

      function setValue(data) {
        console.log('route',data);
        if ((!isSet(data))||(data == null)||(Object.keys(data).length === 0)) return;
        this.parent.renderLock();
        if(isSet(data.to)) data.address = data.to;
        if(isSet(data.via)) data.gateway = data.via;
       
        if(isSet(data.gateway)) {
          if (data.gateway='0.0.0.0') {
            data.scope = 'link';
          } else if (data.gateway='127.0.0.1') {
            data.scope = 'host';
          } else {
            data.scope = 'global';
          }
        }
        super.setValue(data);
        this.scope.disable();
        this.family.onChange(this.family);
        this.data = data;
        this.parent.renderUnlock();
        this.parent.redraw();
      }

      function setMode(mode) {
        this.parent.renderLock();
        switch(mode) {
          case 'basic':
          case 'advanced': {
            this.onlink.hide();
            this.mtu.hide();
            this.metric.hide();
          } break;
          case 'expert': {
            this.onlink.show();
            this.mtu.show();
            this.metric.show();
          } break;
        }
        this.parent.renderUnlock();
        this.parent.redraw();
      }
    
      </script>
    <?php
  }
}

?>