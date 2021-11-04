<?php

namespace core;

class ActionViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'ivr/action';
  }

  public static function getAPI() {
    return 'view';
  }

  public function implementation() {
    ?>
      <script>
      
      async function init(parent, data) {
        this.implict = [];

        if(isSet(data)&&isSet(data.implict)) {
          this.implict = data.implict;
        } else
        if(isSet(parent)&&isSet(parent.data)&&isSet(parent.data.implict)) {
          this.implict = parent.data.implict;
        } else
        if(isSet(parent.parent)&&isSet(parent.parent.data)&&isSet(parent.parent.data.implict)) {
          this.implict = parent.parent.data.implict;
        }
        this.type = new widgets.select(parent, {id: 'type', search: false, options: []}, _('Шаг действия'));
        this.type.onChange = this.onTypeSelect;
        this.settings = new widgets.section(parent, 'action');
        this.settings.inlined = true;
        this.settings.hide();

      }

      async function preload() {
        await super.preload();
        if(!isSet(this.__proto__.constructor.stepactions)) {
          this.__proto__.constructor.stepactions = await this.getItems('action');
        }
        if(this.implict.length>0) {
          this.options = [];
          for(let i in this.__proto__.constructor.stepactions) {
            if(this.implict.indexOf(this.__proto__.constructor.stepactions[i].id)!=-1) this.options.push(this.__proto__.constructor.stepactions[i]);
          }
        } else {
          this.options = this.__proto__.constructor.stepactions;
        }
        this.type.setValue({options: this.options});
        this.parent.endload();
      }

      function setValue(data) {
        if(!isSet(data)||!isSet(data.name)) return;
        this.settings.clear();
        this.data = data;
        this.type.setValue(data.name.toLowerCase());
        this.onTypeSelect(this.type);
      }

      async function onTypeSelect(sender) {
        let type = sender.getValue();
        await require('action/'+type, this.settings, this.data);
        this.settings.show();
      }

    <?php
  }
}

?>