<?php

namespace core;

class IVRSettings extends \view\Collection {

  public static function getLocation() {
    return 'settings/ivr';
  }

  public static function getAPI() {
    return 'ivr';
  }

  public static function getViewLocation() {
    return 'ivr';
  }

  public static function getMenu() {
    return (object) array('name' => 'Сценарий', 'prio' => 7, 'icon' => 'AccountTreeSharpIcon');
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
        this.title = new widgets.input(parent, {id: 'title', expand: true}, _("Наименование сценария"));

        this.actiondialog = new widgets.dialog(dialogcontent, null, _('Редактирование действия'));
        this.actiondialog.onSave = this.saveAction;
        await require('action', this.actiondialog);

        this.addactionbtn = new widgets.iconbutton(parent, {id: 'addactionbtn', class: 'success', icon: 'AddSharpIcon', color: 'default'}, null, _("Добавить действие"));
        this.addactionbtn.onClick = this.addAction;

        this.actionstable = new widgets.table(parent, {id: 'actions', expand: true, sorted: true, head: {name: _('Наименование'), controls: ''}, expand: true, value: [], clean: true}, _("Панель набора действий"));
        this.actionstable.selfalign = {xs: 12};
        this.actionstable.setCellControl('controls', [
          new widgets.iconbutton(null, {id: 'remove', color: 'default', onClick: this.removeAction, icon: 'DeleteSharpIcon'}, null, _('Удалить действие')),
          new widgets.iconbutton(null, {id: 'edit', color: 'primary', onClick: this.editAction, icon: 'EditSharpIcon'}, null, _('Редактировать действие')),
        ]);
       // this.actionstable.setHeadWidth('remove', '1px');
        // this.actionstable.setHeadWidth('edit', '1px');
        // this.actionstable.setCellControl('remove', {class: 'button', initval: {class: 'danger', icon: 'oi oi-trash', onClick: this.removeAction}, title: '', novalue: true});
        // this.actionstable.setCellControl('edit', {class: 'button', initval: {icon: 'oi oi-pencil', onClick: this.editAction}, title: '', novalue: true});

        this.hasAdd = true;
      }

      function saveAction() {
        let data = this.actiondialog.view.getValue();
        data.id = this.id;
        sendSubject('rest/action', this.actiondialog.view.id, data).success((data) => {
          this.actiondialog.hide();
        });
      }

      function removeAction(e) {
        let data = this.actiondialog.view.getValue();
        data.id = this.id;
        removeSubject('rest/action', e.rowdata.id);
      }

      async function add() {
        this.setValue({id: false, name: _('Новый сценарий'), actions: []});
        setCurrentID(null);
      }

      function addAction(sender) {
        this.actiondialog.view.setValue({id: this.id, id: null});
        this.actiondialog.show();
      };

      function editAction(e) {
        this.actiondialog.view.setValue({id: this.id, id: e.rowdata.id});
        this.actiondialog.show();
      }

      

    </script>
    <?php
  }

}

?>