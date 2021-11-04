<?php

namespace core;

class NetworkAddressSettings extends \view\ViewPort {
  
  public static function getAPI() {
    return 'system/network/address';
  }

  public static function getViewLocation() {
    return 'system/network/address';
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
        this.adapter = new widgets.select(parent, {id: 'adapter', readonly: false, multiple: true, minlines: 0, view: 'system/network/adapters', default: []}, _("Адаптер"));

        this.family = new widgets.select(parent, {id: 'family', expand: true, options: [{id: 'ipv4', title: _("IP протокол версии 4")}, {id: 'ipv6', title: _("IP протокол версии 6")}]}, _("Тип адреса"));
        this.family.onChange = async (e) => { 
          await require('system/network/address/'+e.getValue(), this.address, this.data);
        };

        // this.type = new widgets.select(parent, {id: 'type', expand: true, options: [{id: 'auto', title: _("Автоматически")}, {id: 'manual', title: _("Вручную")}]}, _("Получать адрес"));
        // this.type.onChange = (e) => { 
        //   if(e.getValue() == 'auto') {
        //     this.address.hide();
        //     this.gateway.hide();
        //   } else {
        //     this.address.show();
        //     this.gateway.show();
        //     this.family.onChange(this.family);
        //   }
        // };

        this.type = new widgets.checkbox(parent, {id: 'type', single: true, value: false}, _("Получать адрес автоматически"));
        this.type.selfalign = {xs: 12};
        this.type.onChange = (e) => { 
          if(e.getValue()) {
            this.address.hide();
          } else {
            this.address.show();
            this.family.onChange(this.family);
          }
        };

        this.address = new widgets.section(parent, null);
        this.address.itemsalign = {xs: 12, md: 6};
        this.address.selfalign = {xs: 12};
      }

      function setValue(data) {
        console.log(data);
        if ((!isSet(data))||(data == null)||(Object.keys(data).length === 0)) return;
        this.parent.renderLock();
        super.setValue(data);
        if(isSet(data.address)&&(data.address!='0.0.0.0')) {
          //this.type.setValue('manual');
          this.type.setValue(false);
        // } else this.type.setValue('auto');
        } else this.type.setValue(true);
        this.type.onChange(this.type);
        this.data = data;
        this.parent.renderUnlock();
        this.parent.redraw();
      }
    
      </script>
    <?php
  }
}

?>