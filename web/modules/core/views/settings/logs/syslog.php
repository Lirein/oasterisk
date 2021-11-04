<?php

namespace core;

class SyslogSettings extends \view\View {

  public static function getLocation() {
    return 'settings/logs/syslog';
  }

  public static function getAPI() {
    return 'logs/syslog';
  }

  public static function getViewLocation() {
    return 'logs/syslog';
  }


  public static function getMenu() {
    return (object) array('name' => 'Системный журнал', 'prio' => 1, 'icon' => 'EventSharpIcon', 'mode' => 'expert');
  }
  
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

        this.datelist = [
          {id: "%Y.%m.%d %H:%M:%S"},
          {id: "%Y.%m.%d %H.%M.%S"},
          {id: "%Y-%m-%d %H:%M:%S"},
          {id: "%Y-%m-%d %H.%M.%S"},
          {id: "%Y/%m/%d %H:%M:%S"},
          {id: "%Y-%m-%dT%H:%M:%S"},
          // {id: "%Y.%m.%d %H:%M:%S:%3q"},
          {id: "%Y%m%dT%H%M%S"},
          {id: "%d.%m.%Y %H:%M:%S"},
          // {id: "%d.%m.%y %H:%M:%S"},
          {id: "%d.%m.%Y %H.%M.%S"},
          {id: "%d.%m.%Y %H,%M,%S"},
          {id: "%d-%m-%Y %H:%M:%S"},
          {id: "%d-%m-%Y %H.%M.%S"},
          {id: "%d/%m/%Y %H:%M:%S"},
          {id: "%d%m%YT%H%M%S"},
          {id: "%m.%d.%Y %H:%M:%S"},
          {id: "%m-%d-%Y %H:%M:%S"},
          {id: "%m/%d/%Y %H:%M:%S"},
          {id: "%m%d%YT%H%M%S"},
          {id: "%a, %d %b %Y %H:%M:%S"},
          {id: "%a, %d.%b.%Y %H:%M:%S"}
        ];

        let now = new Date();
        for(let i in this.datelist) {
          this.datelist[i].title = this.fromatLogDate(this.datelist[i].id, now);
        }

        this.subcard1 = new widgets.section(parent,'general',_("Основные параметры"));

        this.dateformat = new widgets.select(this.subcard1, {id: 'dateformat', options: this.datelist, search:false}, _("Формат даты"));
        this.use_callids = new widgets.checkbox(this.subcard1, {single: true, id: 'use_callids', value: false}, _("Сохранять Asterisk Unique Call-Id"));
        this.appendhostname = new widgets.checkbox(this.subcard1, {single: true, id: 'appendhostname', value: false}, _("Добавлять имя хоста к имени файлов журнала"));
        this.queue_log = new widgets.checkbox(this.subcard1, {single: true, id: 'queue_log', value: false}, _("Включить журнал очереди"));
        this.queue_log_to_file = new widgets.checkbox(this.subcard1, {single: true, id: 'queue_log_to_file', value: false}, _("Всегда записывать журнал очереди в файл"), _("Определяет, должен ли журнал очереди записываться в файл, даже если присутствует серверная  часть Realtime."));
        this.queue_log_name = new widgets.input(this.subcard1, {id: 'queue_log_name', pattern: /[0-9_A-z]+/, placeholder: 'Не задано'}, _("Имя журнала очереди"));
        this.queue_log_realtime_use_gmt = new widgets.checkbox(this.subcard1, {single: true, id: 'queue_log_realtime_use_gmt', value: false}, _("При использовании Realtime журнала очереди сохранять GMT дату, а не локальное время"));
        this.rotatestrategy = new widgets.select(this.subcard1, {id: 'rotatestrategy', options: [{id: 'none', title: 'Не применять'}, {id: 'sequential', title: 'Нумеровать по возрастающей'}, {id: 'rotate', title: 'Нумеровать по убывающей'}, {id: 'timestamp', title: 'Использовать дату вместо номера'}],search: false}, _("Ротация логов")); 
        this.rotatestrategy.onChange = (e) => { 
          if(e.getValue() != 'none') {
            this.exec_after_rotate.show();
          } else {
            this.exec_after_rotate.hide();
          }
        };
        this.exec_after_rotate = new widgets.input(this.subcard1, {id: 'exec_after_rotate', pattern: /[a-zA-Z0-9_/${}-]+/}, _("Выполнить команду после ротации"));
        this.logger_queue_limit = new widgets.input(this.subcard1, {id: 'logger_queue_limit',pattern: /[0-9]+/}, _("Лимит на длину очереди на запись в файлы журнала"));

        this.subcard2 = new widgets.section(parent,null,_("Файлы журналов"));

        this.extra = new widgets.collection(this.subcard2, {id: 'logfiles', entry: 'logs/entry'});
        
        this.onReset = this.reset;

        this.hasSave = true
      }

      function setValue(data) {
        if(!isSet(data)) data = {};
        if(!isSet(this.data)) this.data = {};
        if(Object.keys(data).length) {
          super.setValue(data);
          this.rotatestrategy.onChange(this.rotatestrategy);
        }
      }

      function getValue() {
        return {general: this.subcard1.getValue(), logfiles: this.extra.getValue()};
      }

      function getWeekday(day) {
        switch(day) {
          case 0: return _('Вс');
          case 1: return _('Пн');
          case 2: return _('Вт');
          case 3: return _('Ср');
          case 4: return _('Чт');
          case 5: return _('Пт');
          case 6: return _('Сб');
          default: return _('Вс');
        }
      }

      function getMonth(month) {
        switch(month) {
          case 1: return _('янв');
          case 2: return _('фев');
          case 3: return _('мар');
          case 4: return _('апр');
          case 5: return _('май');
          case 6: return _('июн');
          case 1: return _('июл');
          case 2: return _('авг');
          case 3: return _('сен');
          case 4: return _('окт');
          case 5: return _('ноя');
          case 6: return _('дек');
          default: return _('');
        }
      }

      function fromatLogDate(format, dt) {
        let result = format;
        result = result.replace('%H', dt.getHours().zeroPad(10));
        result = result.replace('%M', dt.getMinutes().zeroPad(10));
        result = result.replace('%S', dt.getSeconds().zeroPad(10));
        result = result.replace('%d', dt.getDate().zeroPad(10));
        result = result.replace('%m', dt.getMonth().zeroPad(10));
        result = result.replace('%Y', dt.getFullYear());
        result = result.replace('%a', this.getWeekday(dt.getDay()));
        result = result.replace('%b', this.getMonth(dt.getMonth()));
        return result;
      }

      function reset() {
        this.setValue({"general": {
                          "dateformat": "%Y-%m-%d %H:%M:%S",
                          "use_callids": true,
                          "appendhostname": true,
                          "queue_log": true,
                          "queue_log_to_file": false,
                          "queue_log_name": "queue_log",
                          "queue_log_realtime_use_gmt": false,
                          "rotatestrategy": "sequential",
                          "exec_after_rotate": "",
                          "logger_queue_limit": "1000"
                        }, "logfiles": {console: '*'}
                        });
      };
      

      </script>
    <?php
  }

}

?>
