<?php

namespace core;

class NetworkIPv4Settings extends \view\ViewPort {

  public static function getViewLocation() {
    return 'system/network/address/ipv4';
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
        this.address = new widgets.input(parent, {id: 'address', pattern: [/25[0-5]|(2[0-4]|1\d|[1-9]|)\d/, '.', /25[0-5]|(2[0-4]|1\d|[1-9]|)\d/, '.', /25[0-5]|(2[0-4]|1\d|[1-9]|)\d/, '.', /25[0-5]|(2[0-4]|1\d|[1-9]|)\d/, '/', /3[0-2]|[12][0-9]|[0-9]/], placeholder: 'Не задано'}, _("Адрес"));           
        this.gateway = new widgets.input(parent, {id: 'gateway', pattern: [/25[0-5]|(2[0-4]|1\d|[1-9]|)\d/, '.', /25[0-5]|(2[0-4]|1\d|[1-9]|)\d/, '.', /25[0-5]|(2[0-4]|1\d|[1-9]|)\d/, '.', /25[0-5]|(2[0-4]|1\d|[1-9]|)\d/], placeholder: 'Не задано'}, _("Шлюз по умолчанию"));                       
      }

      function setValue(data) {
        console.log('ipv4', data);
        if ((!isSet(data))||(data == null)) return;
        this.parent.renderLock();
        this.address.setValue(data.address+'/'+data.prefix);
        this.gateway.setValue(data.gateway);
        this.data = data;
        this.parent.renderUnlock();
        this.parent.redraw();
      }
    
      </script>
    <?php
  }
}

?>