<?php

namespace core;

class NetworkIPv6Settings extends \view\ViewPort {

  public static function getViewLocation() {
    return 'system/network/address/ipv6';
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
        this.address = new widgets.input(parent, {id: 'address', pattern: [/^(?:[[:xdigit:]]{2}([-:]))(?:[[:xdigit:]]{2}\1){4}[[:xdigit:]]{2}$/], placeholder: 'Не задано'}, _("Адрес"));           
        this.gateway = new widgets.input(parent, {id: 'gateway', pattern: [/^(?:[[:xdigit:]]{2}([-:]))(?:[[:xdigit:]]{2}\1){4}[[:xdigit:]]{2}$/], placeholder: 'Не задано'}, _("Шлюз по умолчанию"));                       
      }

      function setValue(data) {
        console.log('ipv6',data);
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