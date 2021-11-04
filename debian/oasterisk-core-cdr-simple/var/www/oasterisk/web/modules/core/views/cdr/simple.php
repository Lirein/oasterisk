<?php

namespace core;

class SimpleCDR extends ViewModule {

  private static $cdrmodules = null;
  private static $cdrprocessor = null;

  protected static function init() {
    if(self::$cdrmodules==null)
       self::$cdrmodules=getModulesByClass('core\CdrEngine');
    if(self::$cdrprocessor==null)
       self::$cdrprocessor=getModuleByClass('core\CdrProcessor');
  }

  public static function getLocation() {
    return 'cdr/simple';
  }

  public static function getMenu() {
    return (object) array('name' => 'Простой журнал', 'prio' => 0, 'icon' => 'oi oi-spreadsheet');
  }

  public static function check($write = false) {
    $result = true;
    $result &= self::checkPriv('cdr');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    self::init();
    $result = array();
    switch($request) {
      case "cdr": {
         $cdr = array();
         if(isset($request_data->engine)&&isset($request_data->from)&&isset($request_data->to)) {
           $from = new \DateTime($request_data->from);
           $to = new \DateTime($request_data->to);
           $from->setTime(0,0,0);
           $to->setTime(23,59,59);
           foreach(self::$cdrmodules as $module) {
             $info=$module->info();
             if(($info->name == $request_data->engine)||($request_data->engine == 'all')) {
               $cdr = array_merge($cdr, $module->cdr($from, $to));
             }
           }
         }
         $result = self::returnResult(self::$cdrprocessor->process($cdr));
      } break;
    }
    return $result;
  }

  public function scripts() {
    self::init();
    global $location;
    ?>
    <script>
      var card = null;
      var cdrstates = {};
      cdrstates['hangup']='Отбой';
      cdrstates['busy']='Занят';
      cdrstates['answered']='Отвечен';
      cdrstates['failed']='Не отвечен';
      cdrstates['no answer']='Не отвечен';
      var engines = [];
      engines.push({id: 'all', text: "Все доступные"});

<?php
      foreach(self::$cdrmodules as $module) {
        $info=$module->info();
        printf("engines.push({id: '%s', text: '%s'});\n", $info->name, $info->title);
      }
      $extrastates=self::$cdrprocessor->extrastates();
      foreach($extrastates as $status => $desc) {
        printf("cdrstates['%s']='%s';\n", $status, $desc);
      }
?>

      function cdr_state(state) {
        if(typeof cdrstates[state] != 'undefined') return cdrstates[state];
        return state;
      }

      function cdr_time(adate) {
        return getDateTime(adate);
      }

      function processLevel(cdr) {
        var result=[];
        for(id in cdr) {
          var row = {src: '', dst: '', channel: '', state: '', calltime: null, callend: null, recording: ''};
          if(cdr[id].src.name) row.src=cdr[id].src.name;
          if(cdr[id].src.num) {
            if(row.src) {
              row.src+=' <'+cdr[id].src.num+'>';
            } else {
              row.src=cdr[id].src.num;
            }
          } else if(cdr[id].src.user) {
            if(row.src) {
              row.src+=' <'+cdr[id].src.user+'>';
            } else {
              row.src=cdr[id].src.user;
            }
          }
          if(cdr[id].dst.name) row.dst=cdr[id].dst.name;
          if(cdr[id].dst.num) {
            if(row.dst) {
              row.dst+=' <'+cdr[id].dst.num+'>';
            } else {
              row.dst=cdr[id].dst.num;
            }
          } else if(cdr[id].dst.user) {
            if(row.dst) {
              row.dst+=' <'+cdr[id].dst.user+'>';
            } else {
              row.dst=cdr[id].dst.user;
            }
          }
          row.channel=cdr[id].dst.channel;
          row.state=cdr[id].state;
          row.calltime=new Date(cdr[id].from);
          row.callend=new Date(cdr[id].to);
          row.recording=cdr[id].record;
          if(typeof cdr[id].value != 'undefined') {
            row.value=processLevel(cdr[id].value);
          }
          result.push(row);
        }
        return result;
      }

      function loadCDR() {
        var data=card.getValue();
        sendRequest('cdr', {engine: data.engine, from: data.period.from, to: data.period.for}).success(function(data) {
          var new_cdr=processLevel(data);
          card.setValue({cdr: {value: new_cdr, clean: true}});
          return false;
        });
        return true;
      }

      function requestFile() {
        var data=card.getValue();
        sendRequest('file', {engine: data.engine, from: data.period.from, to: data.period.for}).done(function(data) {
          if(typeof data.cdr != 'undefined') {
            var blob = new Blob([data.cdr], {type: "application/vnd.oasis.opendocument.spreadsheet"});
            saveAs(blob, "report-"+data.period.from+".ods");
          } else {
            showalert('danger','Не удалось загрузить отчет');
          }
          return false;
        });
      }

      function saveCSV() {
        var csv = $('#cdr').table2CSV({
                delivery: 'value'
            });
        var blob = new Blob([csv], {type: "text/plain;charset=utf-8"});
        saveAs(blob, "report.csv");
      }

      $(function () {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();

        card = new widgets.section(rootcontent,null);
        obj = new widgets.select(card, {id: 'engine', value: engines, clean: true, search: false}, "Система хранения журналов");
        obj = new widgets.datetimefromto(card, {id: 'period', format: 'DD.MM.YYYY', value: {from: new moment(), for: new moment()}});
        obj.onChange=loadCDR;
        obj = new widgets.button(obj.inputdiv, {id: 'save', class: 'light'}, "Сохранить");
        obj.onClick=saveCSV;
//        obj.onClick=requestFile;
        obj = new widgets.table(card, {id: 'cdr', head: {src: _('src','Источник'), dst: _('dst','Назначение'), channel: _('channel','Канал назначения'), state: _('state','Состояние'), calltime: _('calltime','Время звока'), callend: _('callend','Окончание звонка'), recording: _('recording','Запись')}, value: [], clean: true});
        obj.setHeadHint('timetotal', _('durationhint','Продолжительность связи общая и после поднятия трубки'));
        obj.setCellControl('recording', {class: 'audio', initval: {}});
        obj.setCellFilter('state', cdr_state);
        obj.setCellFilter('calltime', cdr_time);
        obj.setCellFilter('callend', cdr_time);

        loadCDR();
      })
    </script>
    <?php
  }

  public function render() {
   ?>
   <?php
  }

}

?>