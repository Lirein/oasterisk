<?php

namespace core;

class FeaturesSettings extends ViewModule {
  public static function getLocation() {
    return 'settings/general/features';
  }

  public static function getMenu() {
    return (object) array('name' => 'Функции каналов', 'prio' => 2, 'icon' => 'oi oi-command');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  /**
   * Перегружает конфигурацию на стороне технологической платформы
   *
   * @return void
   */
  public function reloadConfig() {
    $this->ami->send_request('Command', array('Command' => 'features reload'));
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    $generalparams = '{"general": {
      "blindxfer": "#1",
      "disconnect": "*0",
      "automon": "*1",
      "atxfer": "*2",
      "parkcall": "#72",
      "automixmon": "*3"
    }, "applicationmap": {
      "transferdigittimeout": "3",
      "xfersound": "beep",
      "xferfailsound": "beeperr",
      "pickupexten": "*8",
      "pickupsound": "beep",
      "pickupfailsound": "beeperr",
      "featuredigittimeout": "1000",
      "recordingfailsound": "beeperr",
      "atxfernoanswertimeout": "15",
      "atxferdropcall": "!no",
      "atxferloopdelay": "10",
      "atxfercallbackretries": "2",
      "transferdialattempts": "3",
      "transferretrysound": "beep",
      "transferinvalidsound": "beeperr",
      "atxferabort": "*1",
      "atxfercomplete": "*2",
      "atxferthreeway": "*3",
      "atxferswap": "*4"
    }}';
    switch($request) {
      case "features-get":{
        $ini = new \INIProcessor('/etc/asterisk/features.conf');
        $returnData = $ini->getDefaults($generalparams);
      
        if(isset($ini->featuremap)){
           $returnData->featuremap = array();
           foreach ($ini->featuremap as $k => $v)
           $returnData->featuremap[] = $k.'='.(string) $v;
        }
        $result = self::returnResult($returnData);
      } break;
      case "features-set":{
        if($this->checkPriv('settings_writer')) {
          $ini = new \INIProcessor('/etc/asterisk/features.conf');
          $ini->setDefaults($generalparams, $request_data);
          if(is_array($request_data->featuremap)) {
            if(isset($ini->featuremap)) {
              unset($ini->featuremap);
            }
            foreach($request_data->featuremap as $feature) {
              $label = $feature->label;
              $value = $feature->dtmf.','.$feature->activateon.'/'.$feature->activateby.','.$feature->application.(empty($feature->application_data)?'':(',('.$feature->application_data.')'));
              $ini->featuremap->$label = $value;
              $ini->featuremap->$label->setExtended(true);
            }
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
      case "get-sounds":{
        $sounds = new \core\Sounds();
        $soundList = array();
        foreach($sounds->get() as $v => $dummy) {
          $soundList[] = (object) array('id' => $v, 'text' => $v);
        }
        $result = self::returnResult($soundList);
      } break;
      case "get-contexts":{
        $dialplan = new \core\Dialplan();
        $contextList = array();
        foreach($dialplan->getContexts() as $v) {
          $contextList[] = (object) array('id' => $v->id, 'text' => $v->title);
        }
        $result = self::returnResult($contextList);
      } break;
    }
    return $result;
  }

  public function scripts() {
    ?>
      <script>
      widgets.useraction=class useractionWidget extends baseWidget {
        constructor(parent, data, label, hint) {
          super(parent,data,label,hint);
          this.node = document.createElement('div');
          this.node.widget = this;
          this.node.className='form-group row';
          if(this.label) {
            this.label.className = 'col-12 form-label';
            this.node.appendChild(this.label);
          }
          this.context_data=[];
          this.sound_data=[];
          this.sound_select = {theme: "bootstrap", minimumInputLength: 1, ajax: {
              transport: function(params, success, failure) {
                // fitering if params.data.q available
                var items = [];
                if (params.data && params.data.q) {
                  items = this.sender.sound_data.filter(function(item) {
                      return new RegExp(params.data.q, 'i').test(item.text);
                  });
                }
                var promise = new Promise(function(resolve, reject) {
                  resolve({results: items});
                });
                promise.then(success);
                promise.catch(failure);
              },
              sender: this
            },
            dropdownAutoWidth: true,
          };
          this.context_select = {theme: "bootstrap", minimumInputLength: 1, ajax: {
              transport: function(params, success, failure) {
                // fitering if params.data.q available
                var items = [];
                if (params.data && params.data.q) {
                  items = this.sender.context_data.filter(function(item) {
                      var res1 = new RegExp(params.data.q, 'i').test(item.id);
                      var res2 = new RegExp(params.data.q, 'i').test(item.text);
                      return res1 || res2;
                  });
                }
                var promise = new Promise(function(resolve, reject) {
                  resolve({results: items});
                });
                promise.then(success);
                promise.catch(failure);
              },
              sender: this
            },
            dropdownAutoWidth: true,
          };
          this.content = document.createElement('div');
          this.content.className='form-group col-12';
          this.node.appendChild(this.content);
          this.inputlabel = document.createElement('label');
          this.inputlabel.className='col-12 form-label';
          this.inputlabel.textContent=_('newactionentry',"Новое действие");
          this.node.appendChild(this.inputlabel);
          this.inputdiv = this.createAction('custom', '*', 'caller', 'self', 'NoOp', '');
          this.inputdiv.classList.add('col-12');
          this.inputdiv.widget=this;
          this.inputdiv.btn.textContent='+';
          this.inputdiv.btn.onclick=this.newAction;
          this.node.appendChild(this.inputdiv);
          this.setParent(parent);
          if((typeof data != 'undefined') && data ) this.setValue(data);
        }
        inputKeypress(sender) {
          var BACKSPACE = 8;
          var DELETE = 46;
          var TAB = 9;
          var LEFT = 37 ;
          var UP = 38 ;
          var RIGHT = 39 ;
          var DOWN = 40 ;
          var END = 35 ;
          var HOME = 35 ;
          var result = false;
          // Checking backspace and delete
          if(sender.keyCode == BACKSPACE || sender.keyCode == DELETE || sender.keyCode == TAB
              || sender.keyCode == LEFT || sender.keyCode == UP || sender.keyCode == RIGHT || sender.keyCode == DOWN)  {
              result = true;
          }
          if(sender.target.pattern) {
            var expr=RegExp('^'+sender.target.pattern+'$','g');
            result = expr.test(sender.target.value.substr(0,sender.target.selectionStart)+sender.key+sender.target.value.substr(sender.target.selectionEnd));
          } else result = true;
          return result;
        }
        createAction(label, dtmf, activateby, activateon, application, application_data) {
          var inputdiv = document.createElement('div');
          inputdiv.className = 'input-group';
          inputdiv.label = document.createElement('input');
          inputdiv.label.type='text';
          inputdiv.label.className='form-control col-2';
          inputdiv.label.pattern='[a-zA-Z0-9_-]+';
          inputdiv.label.value=label;
          inputdiv.label.widget=this;
          inputdiv.label.entry=inputdiv;
          inputdiv.label.onkeypress=this.inputKeypress;
          inputdiv.appendChild(inputdiv.label);
          inputdiv.input = document.createElement('input');
          inputdiv.input.type='text';
          inputdiv.input.className='form-control col-2';
          inputdiv.input.pattern='[#*0-9A-D]+';
          inputdiv.input.value=dtmf;
          inputdiv.input.widget=this;
          inputdiv.input.entry=inputdiv;
          inputdiv.input.onkeypress=this.inputKeypress;
          inputdiv.appendChild(inputdiv.input);
          inputdiv.activateby = document.createElement('select');
          inputdiv.activateby.className='custom-select col';
          inputdiv.activateby.widget=this;
          inputdiv.activateby.entry=inputdiv;
          var options=[
                  {id: 'caller', text: 'Сам абонент'},
                  {id: 'callee', text: 'Другой абонент'},
                  {id: 'both', text: 'Оба абонента'}
          ];
          for(var i=0; i<options.length; i++) {
            var item = document.createElement('option');
            item.value=options[i].id;
            item.textContent=options[i].text;
            inputdiv.activateby.appendChild(item);
          }
          inputdiv.appendChild(inputdiv.activateby);
          inputdiv.activateon = document.createElement('select');
          inputdiv.activateon.className='custom-select col';
          inputdiv.activateon.widget=this;
          inputdiv.activateon.entry=inputdiv;
          var options=[
                  {id: 'self', text: 'У себя'},
                  {id: 'peer', text: 'У другого абонента'}
          ];
          for(var i=0; i<options.length; i++) {
            var item = document.createElement('option');
            item.value=options[i].id;
            item.textContent=options[i].text;
            inputdiv.activateon.appendChild(item);
          }
          inputdiv.appendChild(inputdiv.activateon);
          inputdiv.app = document.createElement('input');
          inputdiv.app.className='form-control';
          inputdiv.app.type='text';
          inputdiv.app.value=application;
          inputdiv.app.pattern='.+';
          inputdiv.app.onkeypress=this.inputKeypress;
          inputdiv.appendChild(inputdiv.app);
          inputdiv.appdata = document.createElement('input');
          inputdiv.appdata.className='form-control';
          inputdiv.appdata.type='text';
          inputdiv.appdata.value=application_data;
          inputdiv.appdata.pattern='.+';
          inputdiv.appdata.onkeypress=this.inputKeypress;
          inputdiv.appendChild(inputdiv.appdata);
          inputdiv.subbtn = document.createElement('span');
          inputdiv.subbtn.className='input-group-append';
          inputdiv.appendChild(inputdiv.subbtn);
          inputdiv.btn = document.createElement('button');
          inputdiv.btn.className='btn btn-secondary';
          inputdiv.btn.textContent='-';
          inputdiv.btn.onclick=this.removeAction;
          inputdiv.btn.widget=this;
          inputdiv.btn.entry=inputdiv;
          inputdiv.subbtn.appendChild(inputdiv.btn);
          inputdiv.activateby.value=activateby;
          inputdiv.activateon.value=activateon;
          return inputdiv;
        }
        getAction(sender) {
          var actiondata = {label: '', dtmf: '', activateby: '', activateon: '', application: '', application_data: ''};
          actiondata.label = sender.label.value;
          actiondata.dtmf = sender.input.value;
          actiondata.activateby = sender.activateby.value;
          actiondata.activateon = sender.activateon.value;
          actiondata.application = sender.app.value;
          actiondata.application_data = sender.appdata.value;
          return actiondata;
        }
        removeAction(sender) {
          var result = true;
          sender.target.entry.parentNode.removeChild(sender.target.entry);
          return false;
        }
        newAction(sender) {
          var result = true;
          var data = sender.target.widget.getAction(sender.target.entry);
          for(var i=0; i<sender.target.widget.content.childNodes.length; i++) {
            if(sender.target.widget.content.childNodes[i].label.value==data.label) return false;
          }
          var entry=sender.target.widget.createAction(data.label, data.dtmf, data.activateby, data.activateon, data.application, data.application_data);
          entry.widget=sender.target.widget;
          sender.target.widget.content.appendChild(entry);
          return result;
        }
        setValue(avalue) {
          if(typeof avalue == 'string') {
            avalue = {value: [avalue]};
          } else if((typeof avalue == 'object') && (avalue instanceof Array)) {
            avalue = {value: avalue};
          }
          if((typeof avalue.id == 'undefined')&&(this.content.id == '')) avalue.id='useraction-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
          if(typeof avalue.id != 'undefined') {
            this.content.id=avalue.id;
            if(this.label) this.label.htmlFor=this.content.id;
          }
          if(typeof avalue.value != 'undefined') {
            this.content.textContent='';
            for(var i=0; i<avalue.value.length; i++) {
              if(typeof avalue.value[i] == 'string') {
                var list=avalue.value[i].split('=');
                var data=[''];
                if(typeof list[1] != 'undefined') {
                  data=list[1].split('(');
                  if(typeof data[1] != 'undefined') data[1]=data[1].split(')')[0]; else data[1]='';
                  var dtmfdata=data[0].split(',');
                  var actdata=dtmfdata[1].split('/');
                  if(typeof actdata[1] == 'undefined') {
                    actdata[1] = 'both';
                  }
                  avalue.value[i]={label: list[0], dtmf: dtmfdata[0], activateby: actdata[1], activateon: actdata[0], application: dtmfdata[2], application_data: data[1]};
                }
              }
              var entry=this.createAction(avalue.value[i].label, avalue.value[i].dtmf, avalue.value[i].activateby, avalue.value[i].activateon, avalue.value[i].application, avalue.value[i].application_data);
              entry.widget=this;
              this.content.appendChild(entry);
            }
          }
          return true;
        }
        disable() {
          var nodes = this.node.querySelectorAll('input');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=true;
          }
          nodes = this.node.querySelectorAll('select');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=true;
          }
          nodes = this.node.querySelectorAll('button');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=true;
          }
          return true;
        }
        disabled() {
          return this.inputdiv.btn.disabled;
        }
        enable() {
          var nodes = this.node.querySelectorAll('input');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=false;
          }
          nodes = this.node.querySelectorAll('select');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=false;
          }
          nodes = this.node.querySelectorAll('button');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=false;
          }
          return true;
        }
        getID() {
          return this.content.id;
        }
        getValue() {
          var result=[];
          for(var i=0; i<this.content.childNodes.length; i++) {
            result.push(this.getAction(this.content.childNodes[i]));
          }
          return result;
        }
      }

      var context_data = [];
      var sound_data = [];

      function loadFeatures() {
        sendRequest('features-get').success(function(data) {
          card.setValue(data);
        });
      }

      function sendFeatures() {
        sendRequest('features-set', card.getValue()).success(function() {
          loadFeatures()
          return true;
        });
      }

      $(function () {
        sendRequest('get-sounds').success(function(sounds) {
          sound_data.splice(0);
          sound_data.push.apply(sound_data, {id: '', text: 'Не указано'});
          sound_data.push.apply(sound_data, sounds);
        });
        sendRequest('get-contexts').success(function(contexts) {
          context_data.splice(0);
          context_data.push.apply(context_data, contexts);
        });
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
      });



<?php
if(self::checkPriv('settings_writer')) {
      ?>

      function sbapply(e) {
        sendFeatures();
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
        subcard1 = new widgets.section(card,'applicationmap',_("Основные параметры функциональных кодов DTMF АТС"));
        subcard2 = new widgets.section(card,'general',_("DTMF коды для активации функций во время вызова"));
        subcard3 = new widgets.section(card,null,_("Назначение пользовательских функций "), _("Укажите последовательность DTMF кодов для вызова действия"));

        obj = new widgets.input(subcard1, {id: 'transferdigittimeout', pattern: '[0-9]+'},
            _("Таймаут нажатия клавиш при наборе номера"),
            _("Указывает максимальный таймаут между получением DTMF кодов от абонента во время набора номера на который требуется перевести звонок"));
        obj = new widgets.select(subcard1, {id: 'xfersound', value: sound_data, clean: true, search: true},
            "Звук успешного перевода вызова");
        obj = new widgets.select(subcard1, {id: 'xferfailsound', value: sound_data, clean: true, search: true},
            "Звук неудачного перевода вызова");
        obj = new widgets.input(subcard1, {id: 'pickupexten',pattern: '[0-9*#A-D]+', placeholder: '*номер'},
            "Код функции перехвата входящих вызовов");
        obj = new widgets.select(subcard1, {id: 'pickupsound', value: sound_data, clean: true, search: true},
           "Звук успешного перехвата");
        obj = new widgets.select(subcard1, {id: 'pickupfailsound', value: sound_data, clean: true, search: true},
            "Звук неудачного перехвата");
        obj = new widgets.input(subcard1, {id: 'featuredigittimeout',pattern: '[0-9]+'},
            "Таймаут нажатия клавиш при активации функций",
            "Указывает максимальный таймаут (в миллисекундах) между набираемыми цифрами, набираемых для активации описываемых тут функций. По умолчанию - 1000.");
        obj = new widgets.select(subcard1, {id: 'recordingfailsound', value: sound_data, clean: true, search: true},
            "Звук неудачной попытки активировать запись вызова.");
        obj = new widgets.input(subcard1, {id: 'atxfernoanswertimeout',pattern: '[0-9]+'},
            "Таймаут ответа при сопровождаемом переводе вызова",
            "Время ожидания ответа при сопровождаемом переводе вызова, прежде чем удерживаемый вызов вернется к инициатору перевода или будет отключен. По умолчанию -  15 ");
        obj = new widgets.toggle(subcard1, {single: true, id: 'atxferdropcall', value: false},
            "Прекратить вызов при неудачном переводе вызова",
            "Определяет действия с входящим вызовом, в случае неудачного перевода вызова. Если данный параметр = 'no', тогда в случае неудачи, пытается повторить перевод, через заданный период, предпринимая  заданное количествово попыток. Если же установлено 'yes', то все каналы участвующие в переводе отключаются");
        obj = new widgets.input(subcard1, {id: 'atxferloopdelay', pattern: '[0-9]+'},
            "Период между попытками повторного перевода вызова",
            "Если \"Прекратить вызов при неудачном перевода вызова\"=no");
        obj = new widgets.input(subcard1, {id: 'atxfercallbackretries', pattern: '[0-9]+'},
            "Количество попыток перевода",
            "Если \"Прекратить вызов при неудачном перевода вызова\"=no");
        obj = new widgets.input(subcard1, {id: 'transferdialattempts', pattern: '[0-9]+'},
            "Количество попыток набора для перевода вызова до возвращения к изначальному вызову");
        obj = new widgets.select(subcard1, {id: 'transferretrysound', value: sound_data, clean: true, search: true},
            "Звук оповещения об исчерпании лимита попыток перевода");
        obj = new widgets.select(subcard1, {id: 'transferinvalidsound', value: sound_data, clean: true, search: true},
            "Звук оповещения о некорректном вводе екстеншена");
        obj = new widgets.input(subcard1, {id: 'atxferabort',pattern: '[0-9*#A-D]+', placeholder: '*номер'},
            "Код завершения перевода вызова",
            "Завершает перевод вызова. При сопровождаемом переводе, набор данного кода возвращает удерживаемый вызов инициатору перевода.");
        obj = new widgets.input(subcard1, {id: 'atxfercomplete',pattern: '[0-9*#A-D]+', placeholder: '*номер'},
            "Код завершения сопровождаемого перевода","Завершает сопровождаемый перевод и прекращает вызов");
        obj = new widgets.input(subcard1, {id: 'atxferthreeway',pattern: '[0-9*#A-D]+', placeholder: '*номер'},
            "Код создание трёхсторонней конференции","Завершает перевод вызова, но оставляет на связи. Создаёт трёхсторонную конференцию между участниками вызова");
        obj = new widgets.input(subcard1, {id: 'atxferswap',pattern: '[0-9*#A-D]+', placeholder: '*номер'},
            "Код переключения между двумя соединяющими сторонами",
            "Может использоваться неоднократно");

        obj = new widgets.input(subcard2, {id: 'blindxfer', pattern: '[0-9*#A-D]+', placeholder: '#номер'},
            "Слепой перевод");
        obj = new widgets.input(subcard2, {id: 'disconnect',pattern: '[0-9*#A-D]+', placeholder: '*номер'},
            "Прекращение вызова");
        obj = new widgets.input(subcard2, {id: 'automon',pattern: '[0-9*#A-D]+', placeholder: '*номер'},
            "Включение записи разговора",
            "Раздельная запись входящего и исходящего каналов");
        obj = new widgets.input(subcard2, {id: 'atxfer',pattern: '[0-9*#A-D]+', placeholder: '*номер'},
            "Сопровождаемый (контролируемый) перевод");
        obj = new widgets.input(subcard2, {id: 'parkcall',pattern: '[0-9*#A-D]+', placeholder: '#номер'},
            "Парковка вызова");
        obj = new widgets.input(subcard2, {id: 'automixmon',pattern: '[0-9*#A-D]+', placeholder: '*номер'},
            "Включение микшированной записи разговора",
            "Запись входящего и исходящего каналов в один файл");
        obj = new widgets.useraction(subcard3, {id: 'featuremap'}, null);


<?php
if(!self::checkPriv('settings_writer')) {
      ?>
    card.disable();
<?php
}
    ?>
        loadFeatures();
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