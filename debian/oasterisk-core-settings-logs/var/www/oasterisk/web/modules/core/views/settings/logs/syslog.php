<?php

namespace core;

class SyslogSettings extends ViewModule {

  public static function getLocation() {
    return 'settings/logs/syslog';
  }

  public static function getMenu() {
    return (object) array('name' => 'Системный журнал', 'prio' => 1, 'icon' => 'oi oi-bookmark');
  }
  
  public static function check() {
    $result = true;
    $result &= self::checkPriv('security_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function reloadConfig() {
    $this->ami->send_request('Command', array('Command' => 'logger reload'));
  }

  private function getSysLogDirectory() {
    $ini = new \INIProcessor('/etc/asterisk/asterisk.conf');
    $return = (string) $ini->directories->astlogdir;
    unset($ini);
    return $return;
  }

  public function json(string $request, \stdClass $request_data) {
    static $generalparams = '{"general": {
      "dateformat": "%F %T",
      "use_callids": "!yes",
      "appendhostname": "!yes",
      "queue_log": "!yes",
      "queue_log_to_file": "!no",
      "queue_log_name": "queue_log",
      "queue_log_realtime_use_gmt": "!no",
      "rotatestrategy": "sequential",
      "exec_after_rotate": "",
      "logger_queue_limit": "1000"
    }}';
    $result = new \stdClass();
    switch($request) {
      case "syslog-get":{
        $ini = new \INIProcessor('/etc/asterisk/logger.conf');
        $returnData = $ini->getDefaults($generalparams);
        $logfiles = new \stdClass();
        foreach($ini->logfiles as $file => $value){
          $logfiles->$file = ',';
        }
        if(!isset($logfiles->console)) $logfiles->console = ',*';
        $returnData->logfiles = new \stdClass();
        $returnData->logfiles->extra = $ini->logfiles->getDefaults($logfiles);
        foreach($returnData->logfiles->extra as $file => $value){
          if(($pos = array_search('*', $value)) !== false) {
            unset($value[$pos]);
            if(count($value)==0) $value = array("verbose");
            $value = array_merge($value, array("notice","warning","error","debug","security","dtmf","fax"));
          }
          $returnData->logfiles->extra->$file = $value;
        }
        $returnData->logfiles->console = $returnData->logfiles->extra->console;
        unset($returnData->logfiles->extra->console);
        $result = self::returnResult($returnData);
      } break;
      case "syslog-set":{
        if($this->checkPriv('settings_writer')) {
          $ini = new \INIProcessor('/etc/asterisk/logger.conf');
          $ini->setDefaults($generalparams, $request_data);
          if(isset($ini->logfiles)) unset($ini->logfiles);
          if(!isset($request_data->logfiles->extra)) $request_data->logfiles->extra = new \stdClass();
          $request_data->logfiles->extra->console = $request_data->logfiles->console;
          foreach($request_data->logfiles->extra as $key => $value){
            $ini->logfiles->setDefaults((object)(array($key => ',')), (object)array($key => $value));
          }
          if($ini->save()) {
            $result = self::returnSuccess();
            $this->reloadConfig();
          } else {
            $result = self::returnError('danger', 'Невозможно сохранить настройки');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
    }
    return $result;
  }

  public function scripts() {
    ?>
      <script>
      var card = null;
      var rotate = null;
      var exec = null;
      var logf = null;
      var datelist = [
        {id: "%Y.%m.%d %H:%M:%S", text: "1993.01.26 18:47:00"},
        {id: "%Y.%m.%d %H.%M.%S", text: "1993.01.26 18.47.00"},
        {id: "%Y-%m-%d %H:%M:%S", text: "1993-01-26 18:47:00"},
        {id: "%Y-%m-%d %H.%M.%S", text: "1993-01-26 18.47.00"},
        {id: "%Y/%m/%d %H:%M:%S", text: "1993/01/26 18:47:00"},
        {id: "%Y-%m-%dT%H:%M:%S", text: "1993-01-26T18:47:00"},
        // {id: "%Y.%m.%d %H:%M:%S:%3q", text: "1993.01.26 18:47:00:567"},
        {id: "%Y%m%dT%H%M%S", text: "19930126T182400"},
        {id: "%d.%m.%Y %H:%M:%S", text: "26.01.1993 18:47:00"},
        // {id: "%d.%m.%y %H:%M:%S", text: "26.01.93 18:47:00"},
        {id: "%d.%m.%Y %H.%M.%S", text: "26.01.1993 18.47.00"},
        {id: "%d.%m.%Y %H,%M,%S", text: "26.01.1993 18,47,00"},
        {id: "%d-%m-%Y %H:%M:%S", text: "26-01-1993 18:47:00"},
        {id: "%d-%m-%Y %H.%M.%S", text: "26-01-1993 18.47.00"},
        {id: "%d/%m/%Y %H:%M:%S", text: "26/01/1993 18:47:00"},
        {id: "%d%m%YT%H%M%S", text: "26011993T182400"},
        {id: "%m.%d.%Y %H:%M:%S", text: "01.26.1993 18:47:00"},
        {id: "%m-%d-%Y %H:%M:%S", text: "01-26-1993 18:47:00"},
        {id: "%m/%d/%Y %H:%M:%S", text: "01/26/1993 18:47:00"},
        {id: "%m%d%YT%H%M%S", text: "01261993T182400"},
        {id: "%a, %d %b %Y %H:%M:%S", text: "Tue, 26 Jan 1993 18:47:00"},
        {id: "%a, %d.%b.%Y %H:%M:%S", text: "Tue, 26.Jan.1993 18:47:00"}
      ];
      var logcommon = [
        {id: "notice", text: "Уведомления"},
        {id: "warning", text: "Предупреждения"},
        {id: "error", text: "Ошибки"},
        {id: "debug", text: "Отладка"},
        {id: "security", text: "Безопасность"},
        {id: "dtmf", text: "Нажатие клавиш"},
        {id: "fax", text: "Передача факса"}
      ];
      var logverbose = {id: "verbose", text: "Подробности"};
      var logextra = [
        {id: "verbose(1)", text: "Подробности (уровень 1)"},
        {id: "verbose(2)", text: "Подробности (уровень 2)"},
        {id: "verbose(3)", text: "Подробности (уровень 3)"},
        {id: "verbose(4)", text: "Подробности (уровень 4)"},
        {id: "verbose(5)", text: "Подробности (уровень 5)"},
        {id: "verbose(6)", text: "Подробности (уровень 6)"},
        {id: "verbose(7)", text: "Подробности (уровень 7)"},
        {id: "verbose(8)", text: "Подробности (уровень 8)"},
        {id: "verbose(9)", text: "Подробности (уровень 9)"},
      ];
      var logall = {id: "*", text: "Все"};
      var logdata = [];
      logdata.push(logall);
      logdata.push.apply(logdata, logcommon);
      logdata.push(logverbose);
      logdata.push.apply(logdata, logextra);
      logdata = cloneArray(logdata);

      function loadSyslog() {
        sendRequest('syslog-get').success(function(data) {
          data.logfiles.extra = {value: data.logfiles.extra};
          card.setValue(data);
          rotate.onChange(rotate);
        });
      }

      function sendSyslog() {
        sendRequest('syslog-set', card.getValue()).success(function() {
          loadSyslog()
          return true;
        });
      }

      function addDebuggingLevel(sender, item) {
        let items = sender.getValue();
        if(item.id == '*') {
          items = [];
          items.push.apply(items, logcommon);
          items.push(logverbose);
          items = cloneArray(items);
          for(item in items) {
            items[item].checked = true;
          }
          items.push.apply(items, cloneArray([logall]));
        } else if(item.id.search('verbose')==0) {
          let newitems = [];
          newitems.push(logall);
          newitems.push.apply(newitems, logcommon);
          item.checked = true;
          newitems.push(item);
          newitems = cloneArray(newitems);
          for(item in items) {
            let i = findById(newitems, items[item]);
            if(i!=-1) {
              newitems[i].checked = true;
            }
          }
          items = newitems;
        } else {
          return true;
        }
        sender.setValue({value: items, clean: true});
        return false;
      }

      function removeDebuggingLevel(sender, item) {
        if(item.id.search('verbose')==0) {
          let items = sender.getValue();
          let newitems = cloneArray(logdata);
          for(itemid in items) {
            let i = findById(newitems, items[itemid]);
            if((items[itemid]!=item.id)&&(i!=-1)) {
              newitems[i].checked = true;
            }
          }
          items = newitems;
          sender.setValue({value: items, clean: true});
          return false;
        }
        return true;
      }

<?php
if(self::checkPriv('settings_writer')) {
      ?>

      function sbapply(e) {
        sendSyslog();
      }

<?php
} else {
      ?>

    var sbapply=null;

<?php
}
    ?>

      $(function () {
        sidebar_apply(sbapply);
        card = new widgets.section(rootcontent,null);
        var cols = new widgets.section(card,null);
        cols.node.classList.add('row');
        var col1 = new widgets.columns(cols,2);
        var col2 = new widgets.columns(cols,2);

        subcard1 = new widgets.section(col1,'general',_("Основные параметры"));

        obj = new widgets.select(subcard1, {id: 'dateformat', value: datelist, search:false}, 
            "Формат даты");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'use_callids', value: false},
            "Сохранять Asterisk Unique Call-Id");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'appendhostname', value: false},
            "Добавлять имя хоста к имени файлов журнала");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'queue_log', value: false},
            "Включить журнал очереди");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'queue_log_to_file', value: false},
            "Всегда записывать журнал очереди в файл",
            "Определяет, должен ли журнал очереди записываться в файл, даже если присутствует серверная  часть Realtime.");
        obj = new widgets.input(subcard1, {id: 'queue_log_name', pattern: '[0-9_A-z]+', placeholder: 'Не задано'}, 
            "Имя журнала очереди");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'queue_log_realtime_use_gmt', value: false},
            "При использовании Realtime журнала очереди сохранять GMT дату, а не локальное время");
        rotate = new widgets.select(subcard1, {id: 'rotatestrategy', value: [{id: 'none', text: 'Не применять'}, {id: 'sequential', text: 'Нумеровать по возрастающей'}, {id: 'rotate', text: 'Нумеровать по убывающей'}, {id: 'timestamp', text: 'Использовать дату вместо номера'}],search: false},
            "Ротация логов"); 
        rotate.onChange = function(e) { 
          if(e.getValue() != 'none') {
            exec.show();
          } else {
            exec.hide();
          }
        };
        exec = new widgets.input(subcard1, {id: 'exec_after_rotate', pattern: '[a-zA-Z0-9_/-${}]+'}, 
            "Выполнить команду после ротации");
        obj = new widgets.input(subcard1, {id: 'logger_queue_limit',pattern: '[0-9]+'},
            "Лимит на длину очереди на запись в файлы журнала");

        subcard2 = new widgets.section(col2,'logfiles',_("Файлы журналов"));
        
        logf = new widgets.collection(subcard2, {id: 'console', value: cloneArray(logdata)}, "Консоль");
        logf.addbtn.className = 'btn btn-secondary';
        logf.onAdd = addDebuggingLevel;
        logf.onRemove = removeDebuggingLevel;
        obj = new widgets.multiplecollection(subcard2, {id: 'extra', placeholder: _('Файл журнала'), options: cloneArray(logdata), value: {}}, "Пользовательские журналы");
        obj.onAdd = addDebuggingLevel;
        obj.onRemove = removeDebuggingLevel;


<?php
if(!self::checkPriv('settings_writer')) {
      ?>
    card.disable();
<?php
}
    ?>
        loadSyslog();
      });
    </script>
    <?php
}

  public function render() {
    ?>
        <input type="password" hidden/>
    <?php
}
}

?>
