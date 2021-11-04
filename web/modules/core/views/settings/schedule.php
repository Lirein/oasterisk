<?php

namespace core;

class ScheduleSettings extends \view\Collection {
  
  public static function getLocation() {
    return 'settings/schedule';
  }

  public static function getAPI() {
    return 'schedule';
  }

  public static function getViewLocation() {
    return 'schedule';
  }

  public static function getMenu() {
    return (object) array('name' => 'Расписание', 'prio' => 4, 'icon' => 'AlarmSharpIcon', 'mode' => 'expert');
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

        this.title = new widgets.input(parent, {id: 'title', expand: true}, _("Имя"));
        this.title.selfalign = {xs: 12};
        //this.scheduleid = new widgets.input(parent, {id: 'id'}, _("Внутренний идентификатор класса"));
        //this.enabled = new widgets.buttons(parent, {id: 'enabled', trigger: true, buttons: [{id: false, title: _('off',_("Выключено")), checked: true},{id: true, title: _('on',_("Включено"))}]}, _("Режим активности расписания"));
        this.enabled = new widgets.toggle(this.title, {id: 'enabled'}, null, _('Включено'));
        this.enabled.onChange = (sender) => {
          if(sender.value) {
            sender.setHint(_('Включено'));
          } else {
            sender.setHint(_('Выключено'));
          }
        }
        
        this.start = new widgets.select(parent, {id: 'start', expand: true, options: [{id: 'manual', title: _("Вручную")}, {id: 'once', title: _("Однократно")}, {id: 'daily', title: _("Ежедневно")},  {id: 'periodic', title: _("Периодически")}, {id: 'odd', title: _("По нечетным дням")}, {id: 'even', title: _("По чётным дням")}]}, _("Режим запуска"));
        this.start.onChange = (e) => { 
          switch (e.getValue()){
            case 'manual': {
              this.startat.hide();
              this.days.hide();
              this.startbyweek.hide();
              this.stop.hide();
              this.stopat.hide();
            } break;
            case 'once': {
              this.startat.show();
              this.days.hide();
              this.startbyweek.hide();
              this.stop.hide();
             // this.stopat.setValue({from: false});
              this.startat.setValue({for: false});
              this.stopat.hide();
            } break
            case 'periodic': {
              this.startat.show();
              this.days.show();
              this.startbyweek.hide();
              this.stop.show();
              this.stop.onChange(this.stop);
            } break
            default: {
              this.startat.show();
              this.days.hide();
              this.startbyweek.show();
              this.stop.show();
              this.stop.onChange(this.stop);
            } break;
          }
        };

        this.stop = new widgets.select(parent, {id: 'stop', expand: true, options: [{id: 'manual', title: _("Вручную")}, {id: 'once', title: _("По достижении даты")}]}, _("Режим останова"));
        this.stop.onChange = (e) => { 
          if(e.getValue() == 'once') {
            this.stopat.show();
            this.startat.setValue({for: this.stopat});
            this.stopat.setValue({from: this.startat});
          } else {
            this.stopat.hide();
            this.startat.setValue({for: false});
          }
        };

        this.startat = new widgets.datetime(parent, {id: 'startat', format: 'DD.MM.YYYY HH:mm:ss', storeas: 'string', value: moment()}, _("Время запуска"));
        this.stopat = new widgets.datetime(parent, {id: 'stopat', format: 'DD.MM.YYYY HH:mm:ss', value: moment().endOf('day').toDate()}, _("Время окончания"));
               
        this.days = new widgets.section(parent,null);
        this.days.itemsalign = {xs: 12, lg:6};
        this.workday = new widgets.input(this.days, {id: 'workday', prefix: _('день'), pattern: /[0-9]+/}, _("Рабочие дни"));
        this.weekend = new widgets.input(this.days, {id: 'weekend', prefix: _('день'), pattern: /[0-9]+/}, _("Нерабочие дни"));
        
        this.startbyweek = new widgets.buttons(parent, {id: 'startbyweek', trigger: true, multiple: true, buttons: [
        {id: 'mon', title: _("Понедельник"), shorttitle: _("Пн"), checked: true},
        {id: 'tue', title: _("Вторник"), shorttitle: _("Вт"), checked: true},
        {id: 'wed', title: _("Среда"), shorttitle: _("Ср"), checked: true},
        {id: 'thu', title: _("Четверг"), shorttitle: _("Чт"), checked: true},
        {id: 'fri', title: _("Пятница"), shorttitle: _("Пт"), checked: true},
        {id: 'sat', title: _("Суббота"), shorttitle: _("Сб"), checked: false},
        {id: 'sun', title: _("Воскресенье"), shorttitle: _("Вс"), checked: false}
        ]});

        this.repeat = new widgets.toggle(parent, {single: true, id: 'repeat', value: false, expand: true}, _("Режим повтора"));
        this.repeat.onChange = (e) => { 
          if(e.getValue() == true) {
            this.repeatsection.show();
          } else {
            this.repeatsection.hide();
          }
        };
        //this.repeat.selfalign = {xs:12};
        this.repeatsection = new widgets.section(parent, null);
        this.repeatsection.selfalign = {xs:12};
        this.repeatsection.itemsalign = {xs: 12, lg:6};
        this.repeatsection.repeatby = new widgets.section(this.repeatsection, null);
        this.repeatsection.repeatby.itemsalign = {xs: 12, lg:6};
        this.repeatsection.repeatfor = new widgets.section(this.repeatsection, null);
        this.repeatsection.repeatfor.itemsalign = {xs: 12, lg:6};
        this.repeatbyselect = new widgets.select(this.repeatsection.repeatby, {id: 'repeatbyselect', small: true, options: [{id: 'period', title: _("Каждые")}, {id: 'delay', title: _("Через")}]}, _("Повторять"));
        this.repeatby = new widgets.input(this.repeatsection.repeatby, {id: 'repeatby', pattern: /[0-9]+/, small: true, prefix: _('секунд'), placeholder: _("Немедленно")}, null); 
        this.repeatforselect = new widgets.select(this.repeatsection.repeatfor, {id: 'repeatforselect', small: true, options: [{id: 'duration', title: _("Общей длительностью")}, {id: 'count', title: _("Количество запусков")}]}, _("Ограничить по времени"));
        this.repeatforselect.onChange = (sender) => {
          if(sender.getValue()=='duration') {
            this.repeatfor.setValue({prefix: _('секунд')});
          } else {
            this.repeatfor.setValue({prefix: _('раз')});
          }
        };
        this.repeatfor = new widgets.input(this.repeatsection.repeatfor, {id: 'repeatfor', inline: true, small: true, prefix: _('секунд'), pattern: /[0-9]+/, placeholder: _("Без ограничений")}, null);  
        this.finish = new widgets.select(this.repeatsection, {id: 'finish', options: this.context_data}, _("Контекст завершения расписания"));
        this.finish.selfalign = {xs:12};

        this.destination = new widgets.select(parent, {id: 'destination', readonly: false, options: await this.asyncRequest('menuItems', null, 'rest/lines')}, _('Контакт назначения'));
        //this.destination = new widgets.peer(parent, {id: 'destination'}, _("Контакт назначения"));
        this.context = new widgets.select(parent, {id: 'context', options: this.context_data}, _("Контекст вызова"));
        this.action = new widgets.select(parent, {id: 'action', options: this.context_data}, _("Контекст действия"));
        this.trigger = new widgets.select(parent, {id: 'trigger', options: this.trigger_data}, _("Класс запускаемого триггера"));  
        //this.variables = new widgets.keyvaluelist(parent, {id: 'variables', valuefilter: this.valuefilter, multiple: false, value: [], expand: true}, _("Переменные"));  
        this.variables = new widgets.collection(parent, {id: 'variables', data: {keytext: _('Переменная'), valuetext: _('Значение')}, options: [true], value: [], select: 'keyvalue/entry', entry: 'keyvalue/entry'}, _("Переменные"));
        this.variables.selfalign = {xs: 12};
        
        this.hasAdd = true;
        this.hasSave = true;
      }

      function valuefilter(sender, value) {
        let arr = [...value.matchAll('(\\[[^\\(\\]]*\\]|\\([^\\(\\]]*\\)|[^\\[\\(]+)+')];
        if(arr.length==0) return true;
        return arr[0][0]==value;
      }

      function setValue(data) {
        if ((!isSet(data))||(data == null)) return;
        this.parent.renderLock();
        if(data.start=='periodic') {
          let period = data.startby.split('/');
          data.workday = period[0];
          data.weekend = period[1];
          data.startbyweek = ["mon","tue","wed","thu","fri"];
        } else {
          data.startbyweek = data.startby;
          data.workday = 2;
          data.weekend = 2;
        }
        if (data.startat == '') data.startat = new Date();
        if (data.stopat == '') data.stopat = moment().endOf('day').toDate();
        super.setValue(data);
       // this.startat.setValue({from: moment().subtract(10, 'y'), for: moment().add(10,'y')._d});

        if (isSet(data.repeatfor)) {
          this.repeatforselect.setValue((data.repeatfor.indexOf('+')==0)?'count':'duration');
          this.repeatforselect.onChange(this.repeatforselect);
          this.repeatfor.setValue(Number.parseInt(data.repeatfor));
        } 
        if (isSet(data.repeatby)) {
          this.repeatbyselect.setValue((data.repeatby.indexOf('+')==0)?'delay':'period');
          this.repeatby.setValue(Number.parseInt(data.repeatby));
        }

        //ToDO ограничение, чтобы нельзя было выставить дату в прошлом
        if(this.startat.getMoment().diff(moment())<0) {
          this.startat.setValue({from: this.startat.getDate()});
        } else {
          this.startat.setValue({from: new Date()});
        }
        this.enabled.onChange(this.enabled);
        this.stop.onChange(this.stop);
        this.start.onChange(this.start);
        this.repeat.onChange(this.repeat);
        if(data.readonly) this.parent.disable(); else this.parent.enable();
        this.data = data;
        this.parent.renderUnlock();
        this.parent.redraw();
      }

      function getValue() {
        let result = this.parent.getValue();
        result.id = this.data.id;
        
        let utcoffset = (moment()).utcOffset();
        let offset = Math.trunc(Math.abs(utcoffset)/60).zeroPad(10) + Math.trunc(Math.abs(utcoffset)%60).zeroPad(10);
        if(utcoffset>0) {
          offset = '+'+offset;
        } else {
          offset = '-'+offset;
        }
        result.startat += offset;
        result.stopat += offset;

        if (result.start == 'periodic'){
          result.startby = result.workday+'/'+result.weekend;
        } else {
          result.startby = result.startbyweek;
        }

        result.repeatby = ((result.repeatbyselect == 'delay')?'+':'')+this.repeatby.getValue();
        result.repeatfor = ((result.repeatforselect == 'count')?'+':'')+this.repeatfor.getValue();
        return result;
      }

      async function add() {
        this.setValue({id: false, title: 'Новое расписание', zones: [], enabled: false, start: "manual", startat: "", workday: 2, weekend: 2, startbyweek: ["mon","tue","wed","thu","fri"], repeat: false, repeatfor: "", repeatby: "", stop: "manual", stopat: "", context: "", action: "", trigger: "", destination: "", variables: []});
        setCurrentID(null);
      };

      async function remove(id, title) {
        try {
          if(!isSet(title)) {
            title = id;
            if(id === this.data.id) {
              title = this.data.title;
            }
          }
          let modalresult = await showdialog(_("Удаление расписания"),_("Вы уверены что действительно хотите удалить расписание <b>\"{0}\"</b>?").format(title),"error",['Yes','No']);
          if(modalresult=='Yes') {
            return await super.remove(id);
          }
        } finally {
          return false;
        }
      }
    
      </script>
    <?php
  }
}

?>