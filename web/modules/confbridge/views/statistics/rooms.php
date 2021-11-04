<?php

namespace confbridge;

class RoomStatistics extends \view\View {

  private static $cdrmodules = null;
  private static $cdrprocessor = null;

  protected static function init() {
    if(self::$cdrmodules==null)
       self::$cdrmodules=getModulesByClass('core\CdrEngine');
    if(self::$cdrprocessor==null)
       self::$cdrprocessor=getModuleByClass('core\CdrProcessor');
  }

  public static function getLocation() {
    return 'statistics/confbridgecalls';
  }

  public static function getMenu() {
    return (object) array('name' => 'КС: Табличная', 'prio' => 2, 'icon' => 'oi oi-grid-four-up');
  }

  public static function check($write = false) {
    $result = true;
    $result &= self::checkPriv('cdr');
    $result &= self::checkLicense('oasterisk-confbridge');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    self::init();
    $result = array();
    switch($request) {
      case "cdr": {
        $confbridge = new \confbridge\ConfbridgeModule();
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
        $cdr = self::$cdrprocessor->process($cdr);
        $roominfo = array();
        $rooms = array();
        foreach($cdr as $entry) {
          if(($entry->entry->context=='confbridge')&&($entry->entry->app=='dummy')&&($entry->state=='initialized')) {
            if(!in_array($entry->dst->name, $roominfo)) {
              $roominfo[$entry->dst->name] = $confbridge->getPersistentRoom($entry->dst->name);
            }
            if(($roominfo[$entry->dst->name]==null)||($roominfo[$entry->dst->name]&&$roominfo[$entry->dst->name]->useinstatistics)) {
//              if($roominfo[$entry->dst->name]==null) $entry->dst->name='[others]';
              $count = 0;
              foreach($entry->value as $event) {
                if(!empty($event->dst->num)&&empty($event->action)) {
                  $count++;
                }
              }
              $rooms[] = (object) array('name' => $entry->dst->name, 'start' => $entry->from, 'end' => $entry->to, 'duration' => $entry->duration, 'count' => $count);
            }
          }
        }
        $result = self::returnResult($rooms);
      } break;
    }
    return $result;
  }

  public function implementation() {
    self::init();
    ?>
    <script>
      var card = null;
      var engines = [];
      var roomdata = null;
      engines.push({id: 'all', text: "Все доступные"});

<?php
      foreach(self::$cdrmodules as $module) {
        $info=$module->info();
        printf("engines.push({id: '%s', text: '%s'});\n", $info->name, $info->title);
      }
?>

      function timetoalpha(seconds) {
        let period = seconds;
        let strperiod = "";
        let hours = Math.trunc(period/3600);
        period = period - hours*3600;
        let minutes = Math.trunc(period/60);
        period = period - minutes*60;
        if(hours) strperiod += " "+hours+" "+decOfNum(hours,[_("час"), _("часа"), _("часов")]);
        if(minutes) strperiod += " "+minutes+" "+decOfNum(minutes,[_("минута"), _("минуты"), _("минут")]);
        if(period) strperiod += " "+period+" "+decOfNum(period,[_("секунда"), _("секунды"), _("секунд")]);
        return strperiod;
      }

      function timetostr(seconds) {
        let period = seconds;
        let strperiod = "";
        let hours = Math.trunc(period/3600);
        period = period - hours*3600;
        let minutes = Math.trunc(period/60);
        period = period - minutes*60;
        strperiod = hours.zeroPad(10)+":"+minutes.zeroPad(10)+':'+period.zeroPad(10);
        return strperiod;
      }

      function loadCDR() {
        var data=card.getValue();
        this.sendRequest('cdr', {engine: data.engine, from: data.period.from, to: data.period.for}).success(function(data) {
          roomdata = data;
          for(let i in data) {
            data[i].date = new Date(data[i].start);
            data[i].start = new Date(data[i].start);
            data[i].end = new Date(data[i].end);
          }
          card.setValue({cdr: {value: data, clean: true}});
          return false;
        });
        return true;
      }

      $(function () {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();

        card = new widgets.section(rootcontent,null);
        let section = new widgets.section(card, null);
        section.node.className = 'form-group row';
        obj = new widgets.select(section, {id: 'engine', inline: true, value: engines, clean: true, search: false}, "Система хранения журналов");
        obj.node.classList.add('col-md-6');
        obj.node.classList.add('col-12');
        let fromdate = new moment();
        fromdate.set('date', 1);
        obj = new widgets.datetimefromto(section, {id: 'period', inline: true, format: 'DD.MM.YYYY', value: {from: fromdate, for: new moment()}});
        obj.node.classList.add('col');
        obj.onChange=loadCDR;

        obj = new widgets.table(card, {id: 'cdr', sorted: true, head: {date: _('Дата'), name: _('Комната'), start: _('Начало'), end: _('Окончание'), duration: _('Длительность'), count: _('Всего участников')}, value: [], clean: true});
        obj.setCellFilter('date', getDate);
        obj.setCellFilter('start', getTime);
        obj.setCellFilter('end', getTime);
        obj.setCellFilter('duration', timetostr);
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