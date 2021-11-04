<?php

namespace core;

class SyslogEntry extends \view\ViewPort {

  public static function getViewLocation() {
    return 'logs/entry';
  }

  public function implementation() {
    ?>
      <script>
      
      async function init(parent, data) {
        this.logtypes = [
          {id: 'console', title: _('Отладочная консоль')},
          {id: 'syslog', title: _('Системный журнал')},
          {id: 'messages', title: _('Информационные сообщения')},
          {id: 'security', title: _('Журнал безопасности')},
          {id: 'custom', title: _('Пользовательский журнал')},
        ];

        this.logdata = [
          {id: "*", title: "Все"},
          {id: "notice", title: "Уведомления"},
          {id: "warning", title: "Предупреждения"},
          {id: "error", title: "Ошибки"},
          {id: "debug", title: "Отладка"},
          {id: "security", title: "Безопасность"},
          {id: "dtmf", title: "Нажатие клавиш"},
          {id: "fax", title: "Передача факса"},
          {id: "verbose", title: "Подробности"},
          {id: "verbose(1)", title: "Подробности (уровень 1)"},
          {id: "verbose(2)", title: "Подробности (уровень 2)"},
          {id: "verbose(3)", title: "Подробности (уровень 3)"},
          {id: "verbose(4)", title: "Подробности (уровень 4)"},
          {id: "verbose(5)", title: "Подробности (уровень 5)"},
          {id: "verbose(6)", title: "Подробности (уровень 6)"},
          {id: "verbose(7)", title: "Подробности (уровень 7)"},
          {id: "verbose(8)", title: "Подробности (уровень 8)"},
          {id: "verbose(9)", title: "Подробности (уровень 9)"}
        ];

        this.key = new widgets.select(parent, {id: 'key', options: this.logtypes, value:'console'});
        this.key.onChange = (sender) => {
          if(sender.getValue()=='custom') {
            this.customname.show();
          } else {
            this.customname.hide();
          }
        }

        this.customname = new widgets.input(parent, {id: 'name'});
        this.customname.hide();

        this.options = parent.parent.options[0];
        this.value = new widgets.select(parent, {id: 'value', options: this.logdata, value: [], multiple: true});
        this.value.onChange = this.changeDebuggingLevel;
      }

      function setValue(data) {
        if(!isSet(data)) data = {};
        if((Object.keys(data).length)) {
          if (isSet(data.key)) {
            let id = this.logtypes.indexOfId(data.key);
            this.id = data.key;
            if ((id != -1)) {
              this.key.setValue(data.key);
            } else {
              this.customname.setValue(data.key);
              this.key.setValue('custom');
            }
            this.key.onChange(this.key);
          }
          if (isSet(data.value)) {
            this.value.setValue(data.value);
            this.value.onChange(this.value);
          }
        }
      }

      function getValue(){
        let result = {key: '', value: this.value.getValue()};
        if(this.key.getValue()=='custom') {
          result.key = this.customname.getValue();
        } else {
          result.key = this.key.getValue();
        }
        return result;
      }

      function changeDebuggingLevel(sender) {
        let items = sender.getValue();
        if (isSet(items)) {
          let lastitem = items[items.length - 1];
          let itemscommon = ["notice", "warning", "error", "debug", "security", "dtmf","fax"];
          if(items.indexOf('*') != -1) {
            items = itemscommon;
            items.push("verbose");
          } else if(lastitem.search('verbose')==0) {
            let newitems = [];
            for(let item of items) {
              let i = itemscommon.indexOf(item);
              if(i!=-1) {
                newitems.push(item);
              }
            }
            newitems.push(lastitem);
            items = newitems;
          } else {
            return true;
          }
          sender.setValue(items);
        }
        return false;
      }

    <?php
  }
}

?>