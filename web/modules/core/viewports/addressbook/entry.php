<?php

namespace core;

class AddressBookEntry extends \view\ViewPort {

  public static function getViewLocation() {
    return 'addressbook/entry';
  }

  public function implementation() {
    ?>
      <script>
      
      async function init(parent, data) {
        this.id = null;
        this.name = new widgets.input(parent, {id: 'name', pattern: /[^:;*$%^#@]+/, placeholder: _('ООО «Рога и копыта»')}, _('Наименование контакта'));
        this.title = new widgets.input(parent, {id: 'title', pattern: /[^:;*$%^#@]+/, placeholder: _('Контрагент')}, _('Должность контакта'));
      
        this.phones = new widgets.inputlist(parent, {id: 'phones', value: [], expand: true}, _('Телефонные номера'));
      }

      function setValue(data) {
        if((!isSet(data))||(data==null)) data = {};
        if(!isSet(data.id)) data.id = null;
        this.id = data.id;
        if(this.id == null) {
          this.parent.setValue({name: '', title: '', phones: []});
        } else {
          this.sendRequest('get', {id: this.id}, 'rest/addressbook/entry').success(function(data) {
            this.parent.setValue(data);
          }.bind(this));
        }
        if((isSet(data.readonly))&&data.readonly) {
          this.parent.disable();
        } else {
          this.parent.enable();
        }
      }

      function getValue() {
        let data = this.parent.getValue();
        data.id = this.id;
        return data;
      }

    <?php
  }
}

?>