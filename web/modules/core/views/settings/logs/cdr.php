<?php

namespace core;

class CDRSettings extends \view\Collection {
 
  public static function getLocation() {
    return 'settings/logs/cdr';
  }


  public static function getAPI() {
    return 'logs/cdr';
  }

  public static function getViewLocation() {
    return 'logs/cdr';
  }

  public static function getMenu() {
    return (object) array('name' => 'Детализация вызовов', 'prio' => 2, 'icon' => 'ListAltSharpIcon', 'mode' => 'expert');
  }
  
  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function reloadConfig() {
    $this->ami->send_request('Command', array('Command' => 'cdr reload'));
  }

  public function implementation() {
    ?>
      <script>

      async function init(parent, data) {

        parent.itemsalign = {xs: 12};
        this.settings = new widgets.section(parent, null);
        this.settings.setApply = (func) => {
          this.parent.setApply(func?this.send:null);
        }
        this.settings.setReset = (func) => {
          this.parent.setReset(func?this.reset:null);
        }
      }

      async function setValue(data) {
        if(!isSet(data)) data = {};
        if(!isSet(this.data)) this.data = {};
        if(Object.keys(data).length) {
          if(this.data.id != data.id) {
            await require('cdr/'+data.id.toLowerCase(), this.settings, data);
            this.settings.view.onRefreshActions = () => {
              this.parent.renderLock();
              if(this.settings.view.hasSave && this.settings.view.showSave) {
                this.parent.setApply(this.send);
              } else {
                this.parent.setApply(null);
              }
              if(this.settings.view.showReset && this.settings.view.onReset) {
                this.parent.setReset(this.reset);
              } else {
                this.parent.setReset(null);
              }
              this.parent.renderUnlock();
              this.parent.redraw();
            }
          } else {
            super.setValue(data);
          }
          this.settings.view.onRefreshActions();
        }
        this.data = data;
      }

      function getValue() {
        let result = {};
        result = this.settings.getValue();
        result.id = this.data.id;
        return result;
      }

      function reset() {
        this.settings.view.onReset();
      }

      </script>
    <?php
  }

}

?>