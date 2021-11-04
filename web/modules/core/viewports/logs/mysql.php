<?php

namespace core;

class MysqlCdrEngineSettingsViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'cdr/mysql';
  }

  public static function check() {
    $result = true;
    $result &= self::checkModule('cdr', 'mysql', true);
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function implementation() {
    ?>
      <script>
      async function init(parent, data) {

        this.warninglabel = new widgets.label(parent, null, _("Невозможно установить соединение с базой данных"));
        this.warninglabel.selfalign = {xs: 12, lg:12};
        //this.warninglabel.label.classList.add('text-danger');
        //this.warninglabel.hide();
        
        this.hostname = new widgets.input(parent, {id: 'hostname'}, _("Имя хоста"), _("Если сервер базы данных запущен на той же машине, что и сервер технологической платформы, для связи с локальным сокетом Unix можно задать имя хоста"));
        this.dbname = new widgets.input(parent, {id: 'dbname'}, _("Имя базы данных"));
        this.createdbbtn = new widgets.iconbutton(this.dbname, {id: 'createdbbtn', icon: 'AddBoxIcon', color: 'default'}, null, _("Создать базу данных"));
        this.createdbbtn.onClick = function() {
          this.sendRequest('createdb', {id: 'mysql', db: this.dbname.getValue()}, 'rest/logs/cdr').success(function(data) {
          }); 
        }.bind(this);
        this.table = new widgets.input(parent, {id: 'table'}, _("Имя таблицы"));
        this.createtblbtn = new widgets.iconbutton(this.table, {id: 'createtblbtn', icon: 'AddBoxIcon', color: 'default'}, null, _("Создать таблицу"));
        this.createtblbtn.onClick = function() {
          this.sendRequest('createtable',{id: 'mysql', table: this.table.getValue()}, 'rest/logs/cdr').success(function(data) {
          });     
        }.bind(this);
        this.password = new widgets.input(parent, {id: 'password', password: true}, _("Пароль пользователя"));
        this.user = new widgets.input(parent, {id: 'user'}, _("Имя пользователя"));
        this.port = new widgets.input(parent, {id: 'port'}, _("Порт"), _("Если имя хоста задано не как «localhost», тогда будет пытаться присоединиться к указанному порту или использовать порт по умолчанию."));
        this.sock = new widgets.input(parent, {id: 'sock'}, _("Сокет файл"), _("Если имя хоста не задано или задано как «localhost», тогда будет пытаться, подсоединиться к указанному сокет файлу или использовать сокет файл по умолчанию"));
        this.cdrzone = new widgets.select(parent, {id: 'cdrzone', options: [{id: 'UTC', title: _('UTC')}], search:false}, _("Часовой пояс"));
        this.usegmtime = new widgets.checkbox(parent, {single: true, id: 'usegmtime', value: false}, _("Использовать при журналировании среднее время по Гринчичу"));
        this.utf8 = new widgets.select(parent, {id: 'charset', options: [{id: 'utf8', title: _('utf8')}], search:false}, _("Кодировка запросов"));
        this.compat = new widgets.checkbox(parent, {single: true, id: 'compat', value: false}, _("Устанавливать дату публикации записи вместо даты начала вызова"));
        this.hasSave = true;
        this.onReset = this.reset;
      }

      function setValue(data) {
        super.setValue(data);
        this.data = data;
        switch(this.data.connected) {
          case 1: {
            this.createtblbtn.hide();
            this.createdbbtn.hide();
            this.warninglabel.show();
          } break;
          case 2: {
            this.createtblbtn.hide();
            this.createdbbtn.show();
            this.warninglabel.hide();
          } break
          case 3: {
            this.createtblbtn.show();
            this.createdbbtn.hide();
            this.warninglabel.hide();
          } break
          default: {
            this.createtblbtn.hide();
            this.createdbbtn.hide();
            this.warninglabel.hide();
          } break;
        }
      }

      function reset() {
        this.setValue({
          "id": "mysql",
          "hostname": "",
          "dbname": "",
          "table": "",
          "password": "",
          "user": "",
          "port": "3306",
          "sock": "",
          "cdrzone": "UTC",
          "usegmtime": false,
          "charset": "",
          "compat": false
        });
      }

    </script>
    <?php
  }

}

?>
