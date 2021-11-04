<?php

namespace core;

class KeyValuesEntry extends \view\ViewPort {

  public static function getViewLocation() {
    return 'keyvalue/multipleentry';
  }

  public function implementation() {
    ?>
      <script>
      
      async function init(parent, data) {
        this.section = new widgets.section(parent);
        this.section.itemsalign = {xs: 12};
        this.keytext = _('Ключ');
        this.valuetext = _('Значение');
        if(isSet(parent.parent.data)&&isSet(parent.parent.data.keytext)) {
          this.keytext = parent.parent.data.keytext;
        }
        if(isSet(parent.parent.data)&&isSet(parent.parent.data.valuetext)) {
          this.valuetext = parent.parent.data.valuetext;
        }
        this.key = new widgets.input(this.section, {id: 'key', pattern: /[a-z0-9_]+/}, this.keytext);
        this.values = new widgets.section(null);
        this.values.setParent(this.section);
        this.value = [];
      }

      function setValue(data) {
        if(data == null) return;
        this.parent.renderLock();
        if(data instanceof Array) {
          data = {value: data};
        }
        if(typeof data.key == 'string') {
          this.key.setValue(data.key);
        }
        if(isSet(data.value) && (!data.value instanceof Array)) {
          data.value = [data.value];
        }
        if(data.value instanceof Array) {
          this.value = data.value;
          this.values.clear();
          for(let i in this.value) {
            if(typeof this.value[i] == 'string') {
              let entry = new widgets.input(this.values, {value: this.value[i]}, this.valuetext);
              entry.onChange = this.entrychange;
            }
          }
          let entry = new widgets.input(this.values, {}, this.valuetext);
          entry.onChange = this.entrychange;
        }
        this.parent.renderUnlock();
      }

      function getValue() {
        let result = {key: this.key.getValue(), value: []};
        for(let i in this.values.children) {
          let val = this.values.children[i].getValue();
          if(val) result.value.push(val);
        }
        return result;
      }

      function entrychange(sender) {
        let entryid = this.values.children.indexOf(sender);
        if((entryid == (this.values.children.length-1))) {
          if(sender.getValue()!='') {
            let entry = new widgets.input(this.values, {}, _('Значение'));
            entry.onChange = this.entrychange;
          }
          this.parent.redraw();
        } else if((this.values.children.length>1)&&(sender.getValue()=='')) {
          this.values.children.splice(entryid, 1);
          this.parent.redraw();
        }
      }

      function clear() {
        this.setValue({key: '', value: []});
      }

    <?php
  }
}

?>