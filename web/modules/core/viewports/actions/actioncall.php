<?php

namespace core;

class ActionCallViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'action/dial';
  }

  public static function info() {
    return 'Позвонить';
  }

  public static function getAPI() {
    return 'lines';
  }

  public function implementation() {
    ?>
      <script>
      
      async function init(parent, data) {

        this.dials = new widgets.select(parent, {id: 'dials', multiple: true, readonly: false, options: []}, _('Кому'));
        parent.parent.itemsalign = {xs: 6};
        this.dials.onSelect = (sender, item, action) => {
          if(action == 'add') {
            if(item.type == 'trunk') {
              return false;
            }
            return {id: item.id, type: item.type};
          }
          return true;
        }
        this.dials.onChipText = (sender, option, value) => {
          if(option.type == 'trunk') {
            return [value.title, option.number];
          }
          return [value.title, value.phone];
        }
        this.dials.onOptionText = (sender, option, value) => {
          if(option.type == 'trunk') {
            return [option.title, option.number];
          }
          return [option.title, option.jobtitle, value.phone];
        }
        this.dials.onEdit = (sender, option, value) => {
          console.log(sender, option, value);
        }
        this.dials.onOptionVisible = (sender, option) => {
          if((option.type == 'peer')&&(sender.value.indexOfId(option.id)!==-1)) return false;
          return true;
        }
        this.timeout = new widgets.select(parent, {id: 'timeout', search: false, options: [{id: 'default', title: _('По умолчанию')}, {id: 'limitless', title: _('Без ограничений')}, {id: 'x', title: _('x секунд')}], clean: true}, _('Длительность'));
        this.time = new widgets.input(parent, {id: 'time',pattern: /[0-9]+/, placeholder: '10'});
        this.time.hide();
        
        this.timeout.onChange = this.onTimeoutSelect;
        this.options = new widgets.select(parent, {id: 'options', readonly: false, multiple: true, options: [
          {id: 'called_feature_hangup', title: _('Разрешить вызываемой стороне мягко завершить диалог')},
          {id: 'caller_feature_hangup', title: _('Разрешить вызывающей стороне мягко завершить диалог')},
          {id: 'called_feature_park', title: _('Разрешить вызываемой стороне осуществить парковку вызова')},
          {id: 'caller_feature_park', title: _('Разрешить вызывающей стороне осуществить парковку вызова')},
          {id: 'called_feature_transfer', title: _('Разрешить трансфер (перевод) звонка вызываемому абоненту')},
          {id: 'caller_feature_transfer', title: _('Разрешить трансфер (перевод) звонка вызывающему абоненту')},
          {id: 'caller_feature_record', title: _('Разрешить запись звонка вызываемому абоненту')},
          {id: 'called_feature_record', title: _('Разрешить запись звонка вызывающему абоненту')},
          {id: 'caller_feature_autorecord', title: _('Включить запись разговора вызывающему абоненту')},
          {id: 'called_feature_autorecord', title: _('Включить запись разговора вызываемому абоненту')},
          {id: 'called_oncall_trigger', title: _('Запускает триггер перед вызовом каждого канала назначения')},
          {id: 'caller_oncall_trigger', title: _('Запускает триггер перед вызовом в канале источника')},
          {id: 'called_onend_continue', title: _('Продолжить выполнение диалплана с указанного сценария')},
          {id: 'caller_onend_continue', title: _('Продолжить выполнение диалплана со следующей инструкции')},
          {id: 'called_onanswer_trigger', title: _('Выполнить сценарий в случае ответа')},
          {id: 'called_onanswer_dtmf', title: _('Отправить DTMF код вызываемому абоненту')},
          {id: 'caller_onanswer_dtmf', title: _('Отправить DTMF код вызывающему абоненту')},
          {id: 'called_onprogress_dtmf', title: _('Сразу отправить DTMF код вызываемому абоненту')},
          {id: 'onanswer_transfer', title: _('Перевести вызывающего и вызываемого абонента после ответа на 1 и 2 инструкцию сценария')},
          {id: 'called_announce', title: _('Уведомлять вызываемого абонента указанным аудиофайлом')},
          {id: 'caller_waitexten', title: _('Позволить вызывающему абоненту набирать односимвольные DTMF')},
          {id: 'caller_onend_hangup', title: _('Автоматически завершить вызов по отбитию')},
          {id: 'called_onotherparty_cause', title: _('Код отмены вызовов при ответе')},
          {id: 'answered_elsewhere', title: _('Статус "Отвечен другим абонентом" при отбитии')},
          {id: 'forward_callerid', title: _('Отправить CALLERID при трансфере вызова')},
          {id: 'called_callerid', title: _('Передать CALLERID как')},
          {id: 'cdr_reset', title: _('Сбросить CDR')},
          {id: 'deny_forward', title: _('Зперетить трансфер')},
          {id: 'deny_redirect', title: _('Запретить перехват')},
          {id: 'time_limit_max', title: _('Ограничить максимальное время разговора')},
          {id: 'time_limit_warn', title: _('Таймаут до предупреждающего сигнала')},
          {id: 'time_limit_repeat', title: _('Период повтора предупреждающего сигнала')},
          {id: 'early_answer', title: _('Поднимать трубку до триггеров')},
          {id: 'musiconhold', title: _('Заменить гудок')},
          {id: 'ringing_tone', title: _('Класс гудков')},
          {id: 'predial_ring', title: _('Индикация вызова до согласовнаия')},
          {id: 'time_limit_hard', title: _('Установить лимит по времени')},
          {id: 'timeout_onforward_reset', title: _('Сбрасывать таймауты при переводе')},
          {id: 'inherits', title: _('Копировать переменную')}
        ]}, _('Опции'));
      
      }

      async function preload() {
        await super.preload();
        if(!isSet(this.__proto__.constructor.peers)) {
          this.__proto__.constructor.peers = await this.getItems();
        }
        this.dials.setValue({options: this.__proto__.constructor.peers});
        this.parent.endload();
      }

      function onTimeoutSelect(sender) {
        console.log(sender.getValue());
        if (sender.getValue() == 'x'){
          this.time.show();
        } else {
          this.time.hide();
        }
      }


    <?php
  }
}

?>