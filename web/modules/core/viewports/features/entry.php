<?php

namespace core;

class FeaturesCustomEntry extends \view\ViewPort {

  public static function getViewLocation() {
    return 'features/custom/entry';
  }

  public static function getAPI() {
    return 'dialplan';
  }

  public function implementation() {
    ?>
      <script>
      
      async function init(parent, data) {

        let prototype = Object.getPrototypeOf(this);

        if((isSet(parent.applications)) && (!isSet(prototype.applications))) prototype.applications = parent.applications;
        if(!isSet(prototype.applications)) prototype.applications = await this.asyncRequest('actions', null);
        if(!isSet(parent.applications)) parent.applications = prototype.applications;
        
        this.label = new widgets.input(parent, {id: 'id', inline: true, placeholder: _('Укажите имя')});
        if (viewMode!='expert'){
          this.label.hide();
        } else {
          this.label.show();
        }
        this.dtmf = new widgets.input(parent, {id: 'dtmf', inline: true, pattern: /[0-9*#A-D]+/, placeholder: _('Укажите номер')});

        this.activateon = new widgets.select(parent, {id: 'activateon', inline: true, options: [{id: 'self', title: _('Себя')}, {id: 'peer', title: _('Другой стороны')}]});

        this.action = new widgets.select(parent, {id: 'action', inline: true, options: prototype.applications, value: null});

        this.actiondata = new widgets.section(null, {id: 'actiondata', inline: true});
        this.actiondata.setParent(parent);
        
        this.dtmf.onInput = this.dtmfInput;
      }

      function dtmfInput(sender, data) {
        let alreadyexists = false;
        let actions = this.parent.widget.getValue();
        actions.forEach(action => {
          if(action.dtmf == data.value) alreadyexists = true;
        });
        if(alreadyexists&&(viewMode!='expert')) sender.invalid();
        else sender.unvalid();
      }

    <?php
  }
}

?>