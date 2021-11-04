<?php

namespace core;

class StaffContactViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'staff/contact';
  }

  public static function getAPI() {
    return 'staff/contact';
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    return $result;
  }

  public function implementation() {
    ?>
      <script>
      async function init(parent, data) {
        if(!isSet(data)) data = {};
        this.group = null;
        this.defaulttype = '';

        this.card = new widgets.section(parent, {value: {}}, _('Параметры контакта'));
        this.card.selfalign = {sm: 12, md: 7, lg: 8};
        this.card.itemsalign = parent.itemsalign;

        this.phone = new widgets.input(this.card, {id: 'phone', pattern: /[+]{0,1}[0-9]*/, placeholder: '100'}, _('Внутренний номер'), _('Внутренний номер контакта адресной книги, используется как идентификатор записи или набираемый номер абонента при включении адресной книги в направление вызовов'));

        this.alias = new widgets.input(this.card, {id: 'alias', pattern: /[a-z._-]+/, placeholder: _('pupkin.vi')}, _('Синоним контакта'));

        this.name = new widgets.input(this.card, {id: 'name', pattern: /[^:;*$%^#@]+/, placeholder: _('Пупкин Василий Иванович')}, _('Наименование контакта'));

        this.title = new widgets.input(this.card, {id: 'title', pattern: /[^:;*$%^#@]+/, placeholder: _('Рядовой сотрудник')}, _('Должность'));

        this.peersettings = new widgets.section(this.card, {id: 'peer'});
        this.peersettings.selfalign = {sm: 12};
        this.peersettings.itemsalign = parent.itemsalign;

        this.actions = new widgets.section(parent, {value: {grayscaled: true}}, _('Порядок вызова контакта'));
        this.actions.selfalign = {sm: 12, md: 5, lg: 4};

        await require('ivr/actions', this.actions, {
          implict: [
            'dial',
            'goto',
            'voicemail',
          ],
        });
      }

      function setValue(data) {
        if((!isSet(data)) || (!isSet(data.id))) return;
        let contactid = data.id.split('@');
        this.group = contactid[1];
        this.parent.setValue({
          phone: contactid[0],
          alias: data.alias,
          name: data.name,
          title: data.title   
        });
        this.actions.view.setValue(data.actions);
        if(data.peer) {
          require('peer/'+data.peer.type.toLowerCase(), this.peersettings, data.peer);
        } else {
          this.peersettings.clear();
        }
      }

    </script>
    <?php
  }

}

?>
