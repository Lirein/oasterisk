<?php

namespace core;

class ACLsSecurityViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'acl';
  }

  public static function getAPI() {
    return 'security/acl';
  }

  // public static function getMenu() {
  //   return (object) array('name' => 'Фильтры ip-адресов', 'prio' => 5, 'icon' => 'AccessibilitySharpIcon', 'mode' => 'expert');
  // }
  
  public static function check() {
    $result = true;
    $result &= self::checkPriv('security_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function implementation() {
    ?>
    <script>

    async function init(parent, data) {

      this.title = new widgets.input(parent, {id: 'title', default: _('Новый фильтр')}, _("Отображаемое имя"), _("Наименование фильтра, отображаемое в графическом интерфейсе"));
      this.title.selfalign = {xs: 12};
      //this.id = new widgets.input(parent, {id: 'id', pattern: /[a-zA-Z0-9_-]+/}, _("Наименование фильтра"), _("Внутренний идентификатор класса"));
      this.permit = new widgets.collection(parent, {id: 'permit', default: [], value: [], select: 'iplist', entry: 'iplist'}, _("Разрешённые IP"));    
      this.deny = new widgets.collection(parent, {id: 'deny', default: [], value: [], select: 'iplist', entry: 'iplist'}, _("Запрещённые IP"));    
      
      this.hasAdd = true;
      this.hasSave = true
    }

    function setValue(data) {
      if(!isSet(data.id)) data.id = false;
      if(!isSet(this.data)) this.data = {}
      this.parent.setValue(data);
      this.data = data;
    }

    function getValue() {
      let result = this.parent.getValue();
      result.id = this.data.id;
      return result;
    }
  
    async function add() {
      super.add();
      this.setValue({id: false});
      return true;
    }

    async function remove(id, title) {
      let modalresult = await showdialog(_('Удаление фильтра'),_('Вы уверены что действительно хотите удалить фильтр "{0}"?').format(title),"error",['Yes','No']);
      if(modalresult=='Yes') {
        return await super.remove(id, title);
      }
      return false;
    }

    </script>
    <?php
  }

}

?>