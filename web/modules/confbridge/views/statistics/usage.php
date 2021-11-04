<?php

namespace confbridge;

class UsageStatistics extends \view\View {

  private static $cdrmodules = null;
  private static $cdrprocessor = null;

  protected static function init() {
    if(self::$cdrmodules==null)
       self::$cdrmodules=getModulesByClass('core\CdrEngine');
    if(self::$cdrprocessor==null)
       self::$cdrprocessor=getModuleByClass('core\CdrProcessor');
  }

  public static function getLocation() {
    return 'statistics/confbridgeusage';
  }

  public static function getMenu() {
    return (object) array('name' => 'КС: Общая', 'prio' => 1, 'icon' => 'oi oi-compass');
  }

  public static function check($write = false) {
    $result = true;
    $result &= self::checkPriv('cdr');
    $result &= self::checkLicense('oasterisk-confbridge');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    self::init();
    static $groupjson = '[[""]]';
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
        $periods = array();
        $rooms = array();
        $roominfo = array();
        foreach($cdr as $entry) {
          if(($entry->entry->context=='confbridge')&&($entry->entry->app=='dummy')&&($entry->state=='initialized')) {
            $period = 1;
            if(!in_array($entry->dst->name, $roominfo)) {
              $roominfo[$entry->dst->name] = $confbridge->getPersistentRoom($entry->dst->name);
            }
            if(($roominfo[$entry->dst->name]==null)||($roominfo[$entry->dst->name]&&$roominfo[$entry->dst->name]->useinstatistics)) {
              if($roominfo[$entry->dst->name]==null) $entry->dst->name='[others]';
              if(!in_array($entry->dst->name, $rooms)) $rooms[] = $entry->dst->name;
              $count = 0;
              foreach($entry->value as $event) {
                if(!empty($event->dst->num)&&empty($event->action)) {
                  $count++;
                }
              }
              $periods[$period][$entry->dst->name][] = (object) array('duration' => $entry->duration, 'count' => $count);
            }
          }
        }
        $result = self::returnResult((object) array('rooms' => $rooms, 'periods' => $periods));
      } break;
      case "get-groups": {
        $groups = \core\ASTDBStore::readDataItem('confgroup', 'id', $_SESSION['login'], $groupjson);
        $result = self::returnResult($groups);
      } break;
      case "add-group": {
        if(isset($request_data->group)&&isset($request_data->rooms)) {
          $groups = \core\ASTDBStore::readDataItem('confgroup', 'id', $_SESSION['login'], $groupjson);
          if($groups == null) $groups = new \stdClass();
          $group = $request_data->group;
          if(isset($groups->$group)) {
            $result = self::returnError('danger', 'Группа комнат с таким именем уже определена');
          } else {
            $groups->$group = $request_data->rooms;
            if(\core\ASTDBStore::writeDataItem('confgroup', 'id', $_SESSION['login'], $groupjson, $groups)) {
              $result = self::returnSuccess('Комнаты успешно объединены в группу');
            } else {
              $result = self::returnError('danger', 'Не удается объединить комнаты в группу');
            }
          }
        } else {
          $result = self::returnError('danger', 'Некорректный запрос');
        }
      } break;
      case "remove-group": {
        if(isset($request_data->group)) {
          $groups = \core\ASTDBStore::readDataItem('confgroup', 'id', $_SESSION['login'], $groupjson);
          if($groups == null) $groups = new \stdClass();
          $group = $request_data->group;
          if(!isset($groups->$group)) {
            $result = self::returnError('danger', 'Группа комнат с таким именем не определена');
          } else {
            unset($groups->$group);
            if(\core\ASTDBStore::writeDataItem('confgroup', 'id', $_SESSION['login'], $groupjson, $groups)) {
              $result = self::returnSuccess('Комнаты успешно разгруппированы');
            } else {
              $result = self::returnError('danger', 'Не удается разгруппировать комнаты');
            }
          }
        } else {
          $result = self::returnError('danger', 'Некорректный запрос');
        }
      } break;
    }
    return $result;
  }

  public function implementation() {
    self::init();
    ?>
    <script>
      var groupdialog = null;
      var card = null;
      var chart =null;
      var groupbtn = null;
      var ungroupbtn = null;
      var engines = [];
      var groups = [];
      var isgroup = [];
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

      function loadGroups() {
        this.sendRequest('get-groups').success(function(data) {
          groups = data;
          if(roomdata) {
            drawCDR();
          }
        });
      }

      function drawCDR() {
        isgroup = [];
        let labels = [];
        let datasets = {};
        let roomtogroup = {};
        let groupindex = {};
        let total = 0;
        let index = 0;
        let count = 0;
        for(let i in roomdata.rooms) {
          isgroup[index] = false;
          for(let group in groups) {
            let realname = roomdata.rooms[i];
            if((realname=='[others]')&&!isgroup[i]) {
              realname = _('[Прочие]');
            }
            if(groups[group].indexOf(realname)!=-1) {
              if(!isSet(groupindex[group])) {
                groupindex[group] = index;
                isgroup[index] = true;
                labels.push('['+group+']');
                index++;
              }
              roomtogroup[i] = groupindex[group];
              break;
            }
          }
          if(!isSet(roomtogroup[i])) {
            roomtogroup[i] = index;
            labels.push(roomdata.rooms[i]);
            index++;
          }
          count = 0;
          for(let j in roomdata.periods) {
            count++;
            if(!isSet(datasets[count])) datasets[count] = [];
            datasets[count][roomtogroup[i]] = 0;
          }
        }
        count = 0;
        for(let i in roomdata.periods) {
          count++;
          for(let j in roomdata.rooms) {
            if(typeof roomdata.periods[i][roomdata.rooms[j]]!='undefined') {
              let duration = 0;
              for(let k in roomdata.periods[i][roomdata.rooms[j]]) {
                if(typeof roomdata.periods[i][roomdata.rooms[j]][k].duration == 'string') {
                  duration += parseInt(roomdata.periods[i][roomdata.rooms[j]][k].duration);
                } else if(typeof roomdata.periods[i][roomdata.rooms[j]][k].duration == 'number') {
                  duration += roomdata.periods[i][roomdata.rooms[j]][k].duration;
                }
              }
              total += duration;
              datasets[count][roomtogroup[j]] += duration;
            }
          }
        }
        for(let i in labels) {
          if((labels[i]=='[others]')&&!isgroup[i]) {
            labels[i] = _('[Прочие]');
            // isgroup[i] = false;
          }
        }
        chart.setValue({legend: labels, value: datasets, clean: true});
        let borders = [];
        for(let i in isgroup) {
          if(isgroup[i]||(isgroup[i]===null)) borders.push('#888'); else borders.push('white');
          for(let j in chart.chart.data.datasets) {
            chart.chart.data.datasets[j].borderColor = borders;
          }
        }
        chart.chart.update();
        chart.setTitle(_("Всего")+" "+timetoalpha(total));
      }

      function loadCDR() {
        var data=card.getValue();
        this.sendRequest('cdr', {engine: data.engine, from: data.period.from, to: data.period.for}).success(function(data) {
          roomdata = data;
          drawCDR();
          return false;
        });
        return true;
      }

      function selectbtns() {
        let selected = chart.getSelected();
        let rooms = [];
        let groups = [];
        for(let i in selected) {
          if(isgroup[selected[i].index]===true) groups.push(chart.chart.data.labels[selected[i].index]);
          else if(isgroup[selected[i].index]===false) rooms.push(chart.chart.data.labels[selected[i].index]);
        }
        if(rooms.length>1) groupbtn.show(); else groupbtn.hide();
        if(groups.length>0) ungroupbtn.show(); else ungroupbtn.hide();
      }

      function newGroup() {
        let data = groupdialog.getValue();
        if(data.name!='') {
          let selected = chart.getSelected();
          let rooms = [];
          for(let i in selected) {
            if(!isgroup[selected[i].index]) rooms.push(chart.chart.data.labels[selected[i].index]);
          }
          this.sendRequest('add-group', {group: data.name, rooms: rooms}).success(function() {
            loadGroups();
          });
          chart.unselectAll();
          selectbtns();
          return true;
        } else {
          return false;
        }
      }

      $(function () {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
        groupdialog = new widgets.dialog(rootcontent, null, _("Задайте название группы конференц-комнат"));
        groupdialog.closebtn.setLabel(_('Отмена'));
        groupdialog.savebtn.setLabel(_('Принять'));
        obj = new widgets.input(groupdialog, {id: 'name'}, _("Наименование группы"));
        groupdialog.simplify();
        groupdialog.onSave = newGroup;

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
//        obj.onClick=requestFile;
        chart = new widgets.chart(card, {id: 'chart', type: 'doughnut', legend: [], value: [], clean: true});
        chart.onHintLabel = function(sender, dataset, index, data) {    
          return ' '+data.labels[index]+":"+timetoalpha(data.datasets[dataset].data[index]);
        }
        chart.onClick = function(sender, dataset, index) {
          if(sender.isSelected(dataset, index)) {
            sender.unselectData(dataset, index);
          } else {
            sender.selectData(dataset, index);
          }
          selectbtns();
        }
        chart.onLegendClick = function(sender, index) {
          for(let dataset in chart.chart.data.datasets) {
            if(sender.isSelected(dataset, index)) {
              sender.unselectData(dataset, index);
            } else {
              sender.selectData(dataset, index);
            }
          }
          selectbtns();
        }
        chart.onLegendText = function(sender, label, index) {
          let sum = 0;
          for(let i in sender.chart.data.datasets) {
            sum += sender.chart.data.datasets[i].data[index];
          }
          return label+' ('+timetostr(sum)+')';
        }
        card.node.style.position = 'relative';
        groupbtn = new widgets.button(card, {class: 'success'}, _("Сгруппировать"));
        groupbtn.node.style.position = 'absolute';
        groupbtn.node.style.top = (50+chart.node.offsetTop)+"px";
        groupbtn.node.style.left = '0px';
        groupbtn.onClick = function() {
          groupdialog.setValue({name: ''});
          groupdialog.show();
        }
        groupbtn.hide();

        ungroupbtn = new widgets.button(card, {class: 'danger'}, _("Разгруппировать"));
        ungroupbtn.node.style.position = 'absolute';
        ungroupbtn.node.style.top = (50+chart.node.offsetTop)+"px";
        ungroupbtn.node.style.right = '0px';
        ungroupbtn.hide();
        ungroupbtn.onClick = async function() {
          let result = await showdialog(_('Разгруппировка'), _('Вы уверены что хотите разгруппировать комнаты?'), 'warning', ['Yes', 'No']); 
          if(result=='Yes') {
            let selected = chart.getSelected();
            for(let i in selected) {
              if(isgroup[selected[i].index]) this.sendRequest('remove-group', {group: chart.chart.data.labels[selected[i].index].substr(1).split(']')[0]}).success(function() {
                loadGroups();
              });
            }
          }
          chart.unselectAll();
          selectbtns();
        }

        loadGroups();
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