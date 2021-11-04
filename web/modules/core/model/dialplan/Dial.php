<?php

namespace core;

class DialOptions {

  //****Features****//

  /**
   * Разрешить вызываемой стороне мягко завершить диалог (выйти из диалога) нажатием соответствующей DTMF клавиши
   * h
   *
   * @var bool $called_feature_hangup
   */
  public $called_feature_hangup;

  /**
   * Разрешить вызывающей стороне мягко завершить диалог (выйти из диалога) нажатием соответствующей DTMF клавиши
   * H
   *
   * @var bool $caller_feature_hangup
   */
  public $caller_feature_hangup;

  /**
   * Разрешить вызываемой стороне осуществить парковку вызова нажатием соответствующей DTMF клавиши
   * k
   *
   * @var bool $called_feature_park
   */
  public $called_feature_park;

  /**
   * Разрешить вызывающей стороне осуществить парковку вызова нажатием соответствующей DTMF клавиши
   * K
   *
   * @var bool $caller_feature_park
   */
  public $caller_feature_park;

  /**
   * Разрешить трансфер (перевод) звонка вызываемому абоненту
   * t
   *
   * @var bool $called_feature_transfer
   */
  public $called_feature_transfer;

  /**
   * Разрешить трансфер (перевод) звонка вызывающему абоненту
   * T
   *
   * @var bool $caller_feature_transfer
   */
  public $caller_feature_transfer;

  /**
   * Разрешить запись звонка вызываемому абоненту
   * w
   *
   * @var bool $called_feature_record
   */
  public $caller_feature_record;

  /**
   * Разрешить запись звонка вызывающему абоненту
   * W
   *
   * @var bool $caller_feature_record
   */
  public $called_feature_record;

  /**
   * Включить запись разговора и разрешить её отключение вызывающему абоненту
   * x
   *
   * @var bool $called_feature_autorecord
   */
  public $caller_feature_autorecord;

  /**
   * Включить запись разговора и разрешить её отключение вызываемому абоненту
   * x
   *
   * @var bool $caller_feature_autorecord
   */
  public $called_feature_autorecord;

  //****Triggers****//

  /**
   * Запускает триггер перед вызовом каждого канала назначения
   * b(ivr^s^1)
   *
   * @var \dialplan\Context $called_oncall_trigger
   */
  public $called_oncall_trigger;

  /**
   * Запускает триггер перед вызовом в канале источника
   * B(ivr^s^1)
   *
   * @var \dialplan\Context $caller_oncall_trigger
   */
  public $caller_oncall_trigger;

  /**
   * Продолжить выполнение диалплана с указанной точки на стороне вызываемого абонента или на следующую инструкцию
   * F(ivr^s^1)
   * 
   * @var \dialplan\Context|bool $called_onend_continue;
   */
  public $called_onend_continue;

  /**
   * Продолжить выполнение диалплана со следующей инструкции на стороне вызывающего абонента 
   * g
   * 
   * @var bool $caller_onend_continue;
   */
  public $caller_onend_continue;

  /**
   * Выполнить указанный сценарий в случае ответа вызываемым абонентом
   * U(ivr^s^1)
   * 
   * @var \dialplan\Context $called_onanswer_trigger
   */
  public $called_onanswer_trigger;

  /**
   * Отправить DTMF код вызываемому абоненту после поднятия трубки
   * D(dtmf::)
   * 
   * @var string $called_onanswer_dtmf
   */
  public $called_onanswer_dtmf;

  /**
   * Отправить DTMF код вызывающему абоненту после поднятия трубки
   * D(:dtmf:)
   * 
   * @var string $caller_onanswer_dtmf
   */
  public $caller_onanswer_dtmf;

  /**
   * Отправить DTMF код вызываемому абоненту после начада вызова до поднятия трубки
   * D(::dtmf)
   * 
   * @var string $called_onprogress_dtmf
   */
  public $called_onprogress_dtmf;

  /**
   * Перевести вызывающего и вызываемого абонента после ответа на 1 и 2 инструкцию точки входа соответственно
   * G(ivr^s^1)
   * 
   * @var \dialplan\Context $onanswer_transfer;
   */
  public $onanswer_transfer;

  //****Call Features****/

  /**
   * Уведомляет вызываемого абонента указанным аудиофайлом сразу после соединения
   * A(x)
   *
   * @var \sound\Sound $called_announce
   */
  public $called_announce;

  /**
   * Позволить вызывающему абоненту набирать односимвольные точки входа в текущем контексте
   * d
   *
   * @var bool $caller_waitexten
   */
  public $caller_waitexten;

  /**
   * Автоматически завершить вызов на стороне вызывающего абонента если вызываемый положил трубку
   * e
   * 
   * @var bool $caller_onend_hangup
   */
  public $caller_onend_hangup;

  /**
   * Задает код завершения вызова для всех параллельных вызываемых назначений в случае ответа одним из них на вызов.
   * Q(cause)
   *
   * @var integer $called_onotherparty_cause
   */
  public $called_onotherparty_cause;

  /**
   * Установить статус "Отвечен другим абонентом" при отмене звонка вызываемой стороной
   * c
   *
   * @var bool $answered_elsewhere
   */
  public $answered_elsewhere;

  /**
   * Отправить CALLERID при трансфере вызова
   * f(name <num>)
   * 
   * @var string $forward_callerid
   */
  public $forward_callerid;

  /**
   * Установить CALLERID для вызываемого абонента
   * s(name <num>)
   * 
   * @var string $called_callerid
   */
  public $called_callerid;

  /**
   * Сбрасывать данные накопленные в журнале детализации до момента вызова
   * C
   *
   * @var bool $cdr_reset
   */
  public $cdr_reset;

  //****Restrictions****//

  /**
   * Запрещает осуществлять трансфер вызова
   * i
   *
   * @var bool $deny_forward
   */
  public $deny_forward;

  /**
   * Запрещает осуществлять перехват вызова
   * I
   *
   * @var bool $deny_redirect
   */
  public $deny_redirect;

  /**
   * Ограничить максимальное время разговора, после чего мягко завершить диалог. Задает время в секундах.
   * L(x::)
   *
   * @var integer $time_limit_max
   */
  public $time_limit_max;

  /**
   * Задать таймаут, в секундах, до момента выдачи предупреждающшего сигнала об истечении времени разговора.
   * L(:y:)
   *
   * @var integer $time_limit_warn
   */
  public $time_limit_warn;

  /**
   * Задать период повтора предупреждающего сигнала о прекращении разговора
   * L(::z)
   *
   * @var integer $time_limit_repeat
   */
  public $time_limit_repeat;

  //****Progress Indication****//

  /**
   * Оправляет сигнализацию об отвеченном звонке до начала обработки сценариев на стороне вызываемого абонента
   * a
   *
   * @var bool $early_answer
   */
  public $early_answer;

  /**
   * Задать класс музыки на удержании вместо гудков
   * m(moh_class)
   *
   * @var \sound\MOH $musiconhold
   */
  public $musiconhold;

  /**
   * Задать тип гудков воспроизводимых в линию в время вызова
   * r(tone_class)
   *
   * @var \sound\Tone $ringing_tone
   */
  public $ringing_tone;

  /**
   * Передать сигнализацию о осуществлении вызова до его фактического начала (применимо при долгом времени согласования звонка)
   * R
   *
   * @var bool $predial_ring
   */
  public $predial_ring;

  /**
   * Задает жесткий таймаут вызова, в секундах, после которого происходит завершение обоих каналов
   * S(x)
   *
   * @var integer $time_limit_hard
   */
  public $time_limit_hard;

  /**
   * Сбросить все таймауты при переадресации/переводе вызова
   * z
   *
   * @var bool $timeout_onforward_reset
   */
  public $timeout_onforward_reset;

  //****Other****//

  /**
   * Указывает имя наследуемой переменной
   *
   * @var \dialplan\Variable
   */
  public $inherits;

  /**
   * Конструктор свойств звонка
   *
   */
  public function __construct() {
    $this->reset();
  }

  /**
   * Метод производит сброс всех свойств звонка
   *
   * @return void
   */
  public function reset() {
    $this->called_feature_hangup = null;
    $this->caller_feature_hangup = null;
    $this->called_feature_park = null;
    $this->caller_feature_park = null;
    $this->called_feature_transfer = null;
    $this->caller_feature_transfer = null;
    $this->caller_feature_record = null;
    $this->called_feature_record = null;
    $this->caller_feature_autorecord = null;
    $this->called_feature_autorecord = null;
    $this->called_oncall_trigger = null;
    $this->caller_oncall_trigger = null;
    $this->called_onend_continue = null;
    $this->caller_onend_continue = null;
    $this->called_onanswer_trigger = null;
    $this->called_onanswer_dtmf = null;
    $this->caller_onanswer_dtmf = null;
    $this->called_onprogress_dtmf = null;
    $this->onanswer_transfer = null;
    $this->called_announce = null;
    $this->caller_waitexten = null;
    $this->caller_onend_hangup = null;
    $this->called_onotherparty_cause = null;
    $this->answered_elsewhere = null;
    $this->forward_callerid = null;
    $this->called_callerid = null;
    $this->cdr_reset = null;
    $this->deny_forward = null;
    $this->deny_redirect = null;
    $this->time_limit_max = null;
    $this->time_limit_warn = null;
    $this->time_limit_repeat = null;
    $this->early_answer = null;
    $this->musiconhold = null;
    $this->ringing_tone = null;
    $this->predial_ring = null;
    $this->time_limit_hard = null;
    $this->timeout_onforward_reset = null;
    $this->inherits = null;
  }

   /**          
   * @param int &$position Текущая позиция указателя на опцию
   * @return \stdClass
   */
  private function parseOption(string $options, int &$position) {
    $result = (object)array('option' => $options[$position], 'value' => true);
    if($position < strlen($options)-1) {
      if($options[$position+1]=='(') {
        $position+=2;
        $startfrom = $position;
        while(($position < strlen($options)) && ($options[$position]!=')')) {
          $position++;
        }
        $result->value = substr($options, $startfrom, $position-$startfrom-1);
      } elseif(($options[$position]=='$')&&($options[$position+1]=='(')) {
        $position+=2;
        $startfrom = $position;
        while(($position < strlen($options)) && ($options[$position]!='}')) {
          $position++;
        }
        $result->value = substr($options, $startfrom, $position-$startfrom-1);
        $result->option = 'variable';
      }
    }
    return $result;
  }

  /**
   * Метод производит разбор строки звонка из его атрибутов
   *
   * @param string $options Строка атрибутов звонка
   * @return void
   */
  public function parse(string $options) {
    $this->reset();
    for($i = 0; $i < strlen($options); $i++) {
      $option = $this->parseOption($options, $i);
      switch($option->option) {
        case 'h': {
          $this->called_feature_hangup =  true;
        } break;
        case 'H': {
          $this->caller_feature_hangup =  true;
        } break;
        case 'k': {
          $this->called_feature_park =  true;
        } break;
        case 'K': {
          $this->caller_feature_park =  true;
        } break;
        case 't': {
          $this->called_feature_transfer = true;
        } break;
        case 'T': {
          $this->caller_feature_transfer = true;
        } break;
        case 'w': {
          $this->caller_feature_record = true;
        } break;
        case 'W': {
          $this->called_feature_record = true;
        } break;
        case 'x': {
          $this->caller_feature_autorecord = true;          
        } break;
        case 'X': {
          $this->called_feature_autorecord = true;          
        } break;
        case 'b': {
          $location = explode('^', $option->value);
          $context = \dialplan\Dialplan::find($location[0]);
          if($context->old_id) {
            $this->called_oncall_trigger = $context;
          }
        } break;
        case 'B': {
          $location = explode('^', $option->value);
          $context = \dialplan\Dialplan::find($location[0]);
          if($context->old_id) {
            $this->caller_oncall_trigger = $context;
          }
        } break;
        case 'F': {
          if($option->value) {
            $location = explode('^', $option->value);
            $context = \dialplan\Dialplan::find($location[0]);
            if($context->old_id) {
              $this->caller_onend_continue = $context;
            }
          } else {
            $this->called_onend_continue = true;
          }
        } break;
        case 'g': {
          $this->caller_onend_continue = true;
        } break;
        case 'U': {
          $location = explode('^', $option->value);
          $context = \dialplan\Dialplan::find($location[0]);
          if($context->old_id) {
            $this->called_onanswer_trigger = $context;
          }
        } break;
        case 'D': {
          $dtmf = explode(':', $option->value);
          if(!empty($dtmf[0])) $this->called_onanswer_dtmf = $dtmf[0];
          if(!empty($dtmf[1])) $this->caller_onanswer_dtmf = $dtmf[1];
          if(!empty($dtmf[2])) $this->called_onprogress_dtmf = $dtmf[2];
        } break;
        case 'G': {
          $location = explode('^', $option->value);
        } break;
        case 'A': {
          $sound = new \sound\Sound($option->value);
          if($sound->old_id) {
            $this->called_announce = $sound;
          }
        } break;
        case 'd': {
          $this->caller_waitexten = true;
        } break;
        case 'e': {
          $this->caller_onend_hangup = true;
        } break;
        case 'Q': {
          $this->called_onotherparty_cause = \channel\Channel::getCauseNumber($option->value);
        } break;
        case 'c': {          
          $this->forward_callerid = $option->value;
        } break;
        case 's': {
          $this->called_callerid = $option->value;
        } break;
        case 'C': {
          $this->cdr_reset = true;
        } break;
        case 'i': {
          $this->deny_forward = true;
        } break;
        case 'I': {
          $this->deny_redirect = true;
        } break;
        case 'L': {
          $seconds = explode(':', $option->value);
          if(!empty($seconds[0])) $this->time_limit_max = $seconds[0];
          if(!empty($seconds[1])) $this->time_limit_warn = $seconds[1];
          if(!empty($seconds[2])) $this->time_limit_repeat = $seconds[2];
        } break;
        case 'a': {
          $this->early_answer = true;
        } break;
        case 'm': {
          $moh = new \sound\MOH($option->value);
          if($moh->old_id) {
            $this->musiconhold = $moh;
          }
        } break;
        case 'r': {
          $tone = new \sound\Tone($option->value);
          if($tone->old_id) {
            $this->ringing_tone = $tone;
          }
        } break;
        case 'R': {
          $this->predial_ring = true;
        } break;
        case 'S': {
          $this->time_limit_hard = $option->value;
        } break;
        case 'z': {
          $this->timeout_onforward_reset = true;
        } break;
        case 'variable': {
          $this->inherits = new \dialplan\Variable($option->value);
        } break;
      }
    }
  }

  /**
   * Метод возвращает строковое представление параметров звонка
   *
   * @return string Строка атрибутов звонка
   */
  public function __toString() {
    $result = '';
    if($this->inherits) $result .= $this->inherits;
    if($this->called_feature_hangup) $result .= 'h';
    if($this->caller_feature_hangup) $result .= 'H';
    if($this->called_feature_park) $result .= 'k';
    if($this->caller_feature_park) $result .= 'K';
    if($this->called_feature_transfer) $result .= 't';
    if($this->caller_feature_transfer) $result .= 'T';
    if($this->caller_feature_record) $result .= 'w';
    if($this->called_feature_record) $result .= 'W';
    if($this->caller_feature_autorecord) $result .= 'x';
    if($this->called_feature_autorecord) $result .= 'X';
    if($this->called_oncall_trigger) $result .= 'b('.$this->called_oncall_trigger->old_id.'^s^1)';
    if($this->caller_oncall_trigger) $result .= 'B('.$this->caller_oncall_trigger->old_id.'^s^1)';
    if($this->called_onend_continue) $result .= 'F('.$this->called_onend_continue->old_id.'^s^1)';
    if($this->caller_onend_continue) $result .=  'g';
    if($this->called_onanswer_trigger) $result .= 'U('.$this->called_onanswer_trigger->old_id.'^s^1)';
    if($this->called_onanswer_dtmf||$this->caller_onanswer_dtmf||$this->called_onprogress_dtmf) {
      $data = array();
      $data[0] = ($this->called_onanswer_dtmf)?$this->called_onanswer_dtmf:'';
      $data[1] = ($this->caller_onanswer_dtmf)?$this->caller_onanswer_dtmf:'';
      $data[2] = ($this->called_onprogress_dtmf)?$this->called_onprogress_dtmf:'';
      $result .= 'D('.implode(':', $data).')';
    }
    if($this->onanswer_transfer) $result .= 'G('.$this->onanswer_transfer->old_id.'^s^1)';
    if($this->called_announce) $result .= 'A('.$this->called_announce.')';
    if($this->caller_waitexten) $result .= 'd';
    if($this->caller_onend_hangup) $result .= 'e';
    if($this->called_onotherparty_cause) $result .= 'Q('.$this->called_onotherparty_cause.')';
    if($this->answered_elsewhere) $result .= 'c';
    if($this->forward_callerid) $result .= 'f('.$this->forward_callerid.')';
    if($this->called_callerid) $result .= 's('.$this->called_callerid.')';
    if($this->cdr_reset) $result .= 'C';
    if($this->deny_forward) $result .= 'i';
    if($this->deny_redirect) $result .= 'I';
    if($this->time_limit_max) {
      $result .= 'L('.$this->time_limit_max;
      if($this->time_limit_warn) {
        $result .= ':'.$this->time_limit_warn;
        if($this->time_limit_repeat) $result .= ':'.$this->time_limit_repeat;
      }
      $result .= ')';
    }
    if($this->early_answer) $result .= 'a';
    if($this->musiconhold) $result .= 'm('.$this->musiconhold->old_id.')';
    if($this->ringing_tone) $result .= 'r('.$this->ringing_tone->old_id.')';
    if($this->predial_ring) $result .= 'R';
    if($this->time_limit_hard) $result .= 'S('.$this->time_limit_hard.')';
    if($this->timeout_onforward_reset) $result .= 'z';
    return $result;
  }

public function cast() {
  $result = new \stdClass;
  $result->called_feature_hangup = ($this->called_feature_hangup)?(string)$this->called_feature_hangup:null;
  $result->caller_feature_hangup = ($this->caller_feature_hangup)?(string)$this->caller_feature_hangup:null;
  $result->called_feature_park = ($this->called_feature_park)?(string)$this->called_feature_park:null;
  $result->caller_feature_park = ($this->caller_feature_park)?(string)$this->caller_feature_park:null;
  $result->called_feature_transfer = ($this->called_feature_transfer)?(string)$this->called_feature_transfer:null;
  $result->caller_feature_transfer = ($this->caller_feature_transfer)?(string)$this->caller_feature_transfer:null;
  $result->caller_feature_record = ($this->caller_feature_record)?(string)$this->caller_feature_record:null;
  $result->called_feature_record = ($this->called_feature_record)?(string)$this->called_feature_record:null;
  $result->caller_feature_autorecord = ($this->caller_feature_autorecord)?(string)$this->caller_feature_autorecord:null;
  $result->called_feature_autorecord = ($this->called_feature_autorecord)?(string)$this->called_feature_autorecord:null;
  $result->called_oncall_trigger = ($this->called_oncall_trigger)?(string)$this->called_oncall_trigger:null;
  $result->caller_oncall_trigger = ($this->caller_oncall_trigger)?(string)$this->caller_oncall_trigger:null;
  $result->called_onend_continue = ($this->called_onend_continue)?(string)$this->called_onend_continue:null;
  $result->caller_onend_continue = ($this->caller_onend_continue)?(string)$this->caller_onend_continue:null;
  $result->called_onanswer_trigger = ($this->called_onanswer_trigger)?(string)$this->called_onanswer_trigger:null;
  $result->called_onanswer_dtmf = ($this->called_onanswer_dtmf)?(string)$this->called_onanswer_dtmf:null;
  $result->caller_onanswer_dtmf = ($this->caller_onanswer_dtmf)?(string)$this->caller_onanswer_dtmf:null;
  $result->called_onprogress_dtmf = ($this->called_onprogress_dtmf)?(string)$this->called_onprogress_dtmf:null;
  $result->onanswer_transfer = ($this->onanswer_transfer)?(string)$this->onanswer_transfer:null;
  $result->called_announce = ($this->called_announce)?(string)$this->called_announce:null;
  $result->caller_waitexten = ($this->caller_waitexten)?(string)$this->caller_waitexten:null;
  $result->caller_onend_hangup = ($this->caller_onend_hangup)?(string)$this->caller_onend_hangup:null;
  $result->called_onotherparty_cause = ($this->called_onotherparty_cause)?(string)$this->called_onotherparty_cause:null;
  $result->answered_elsewhere = ($this->answered_elsewhere)?(string)$this->answered_elsewhere:null;
  $result->forward_callerid = ($this->forward_callerid)?(string)$this->forward_callerid:null;
  $result->called_callerid = ($this->called_callerid)?(string)$this->called_callerid:null;
  $result->cdr_reset = ($this->cdr_reset)?(string)$this->cdr_reset:null;
  $result->deny_forward = ($this->deny_forward)?(string)$this->deny_forward:null;
  $result->deny_redirect = ($this->deny_redirect)?(string)$this->deny_redirect:null;
  $result->time_limit_max = ($this->time_limit_max)?(string)$this->time_limit_max:null;
  $result->time_limit_warn = ($this->time_limit_warn)?(string)$this->time_limit_warn:null;
  $result->time_limit_repeat = ($this->time_limit_repeat)?(string)$this->time_limit_repeat:null;
  $result->early_answer = ($this->early_answer)?(string)$this->early_answer:null;
  $result->musiconhold = ($this->musiconhold)?(string)$this->musiconhold:null;
  $result->ringing_tone = ($this->ringing_tone)?(string)$this->ringing_tone:null;
  $result->predial_ring = ($this->predial_ring)?(string)$this->predial_ring:null;
  $result->time_limit_hard = ($this->time_limit_hard)?(string)$this->time_limit_hard:null;
  $result->timeout_onforward_reset = ($this->timeout_onforward_reset)?(string)$this->timeout_onforward_reset:null;
  $result->inherits = ($this->inherits)?(string)$this->inherits:null;
  return $result;
}

public function assign(\stdClass $data) {
  $this->reset();
  if(!empty($data->called_feature_hangup)) $this->called_feature_hangup = $data->called_feature_hangup;
  if(!empty($data->caller_feature_hangup)) $this->caller_feature_hangup = $data->caller_feature_hangup;
  if(!empty($data->called_feature_park)) $this->called_feature_park = $data->called_feature_park;
  if(!empty($data->caller_feature_park)) $this->caller_feature_park = $data->caller_feature_park;
  if(!empty($data->called_feature_transfer)) $this->called_feature_transfer = $data->called_feature_transfer;
  if(!empty($data->caller_feature_transfer)) $this->caller_feature_transfer = $data->caller_feature_transfer;
  if(!empty($data->caller_feature_record)) $this->caller_feature_record = $data->caller_feature_record;
  if(!empty($data->called_feature_record)) $this->called_feature_record = $data->called_feature_record;
  if(!empty($data->caller_feature_autorecord)) $this->caller_feature_autorecord = $data->caller_feature_autorecord;
  if(!empty($data->called_feature_autorecord)) $this->called_feature_autorecord = $data->called_feature_autorecord;
  if(!empty($data->called_oncall_trigger)) {
    $trigger = \dialplan\Dialplan::find($data->called_oncall_trigger);
    if($trigger->old_id) {
      $this->called_oncall_trigger = $trigger;
    }
  }
  if(!empty($data->caller_oncall_trigger)) {
    $trigger = \dialplan\Dialplan::find($data->caller_oncall_trigger);
    if($trigger->old_id) {
      $this->caller_oncall_trigger = $trigger;
    }
  }
  if(!empty($data->called_onend_continue)) {
    if($data->called_onend_continue===true||$data->called_onend_continue===false||$data->called_onend_continue==1||$data->called_onend_continue==0||$data->called_onend_continue=='true'||$data->called_onend_continue=='false') {
      $this->called_onend_continue = $data->called_onend_continue===true||$data->called_onend_continue==1||$data->called_onend_continue=='true';
    } else {
      $trigger = \dialplan\Dialplan::find($data->called_onend_continue);
      if($trigger->old_id) {
        $this->called_onend_continue = $trigger;
      }
    }
  }
  if(!empty($data->caller_onend_continue)) $this->caller_onend_continue = $data->caller_onend_continue;
  if(!empty($data->called_onanswer_trigger)) {
    $trigger = \dialplan\Dialplan::find($data->called_onanswer_trigger);
    if($trigger->old_id) {
      $this->called_onanswer_trigger = $trigger;
    }
  }
  if(!empty($data->called_onanswer_dtmf)) $this->called_onanswer_dtmf = $data->called_onanswer_dtmf;
  if(!empty($data->caller_onanswer_dtmf)) $this->caller_onanswer_dtmf = $data->caller_onanswer_dtmf;
  if(!empty($data->called_onprogress_dtmf)) $this->called_onprogress_dtmf = $data->called_onprogress_dtmf;
  if(!empty($data->onanswer_transfer)) {
    $trigger = \dialplan\Dialplan::find($data->onanswer_transfer);
    if($trigger->old_id) {
      $this->onanswer_transfer = $data->onanswer_transfer;
    }
  }
  if(!empty($data->called_announce)) {
    $announce = new \sound\Sound($data->called_announce);
    if($announce->old_id) {
      $this->called_announce = $announce;
    }
  }
  if(!empty($data->caller_waitexten)) $this->caller_waitexten = $data->caller_waitexten;
  if(!empty($data->caller_onend_hangup)) $this->caller_onend_hangup = $data->caller_onend_hangup;
  if(!empty($data->called_onotherparty_cause)) $this->called_onotherparty_cause = $data->called_onotherparty_cause;
  if(!empty($data->answered_elsewhere)) $this->answered_elsewhere = $data->answered_elsewhere;
  if(!empty($data->forward_callerid)) $this->forward_callerid = $data->forward_callerid;
  if(!empty($data->called_callerid)) $this->called_callerid = $data->called_callerid;
  if(!empty($data->cdr_reset)) $this->cdr_reset = $data->cdr_reset;
  if(!empty($data->deny_forward)) $this->deny_forward = $data->deny_forward;
  if(!empty($data->deny_redirect)) $this->deny_redirect = $data->deny_redirect;
  if(!empty($data->time_limit_max)) $this->time_limit_max = $data->time_limit_max;
  if(!empty($data->time_limit_warn)) $this->time_limit_warn = $data->time_limit_warn;
  if(!empty($data->time_limit_repeat)) $this->time_limit_repeat = $data->time_limit_repeat;
  if(!empty($data->early_answer)) $this->early_answer = $data->early_answer;
  if(!empty($data->musiconhold)) {
    $moh = new \sound\MOH($data->musiconhold);
    if($moh->old_id) {
      $this->musiconhold = $moh;
    }
  }
  if(!empty($data->ringing_tone)) {
    $tone = new \sound\Tone($data->ringing_tone);
    if($tone->old_id) {
      $this->ringing_tone = $tone;
    }
  }
  if(!empty($data->predial_ring)) $this->predial_ring = $data->predial_ring;
  if(!empty($data->time_limit_hard)) $this->time_limit_hard = $data->time_limit_hard;
  if(!empty($data->timeout_onforward_reset)) $this->timeout_onforward_reset = $data->timeout_onforward_reset;
  if(!empty($data->inherits)) $this->inherits = new \dialplan\Variable($data->inherits);
}

}

class DialApplication extends \dialplan\Application {

  static $name = 'Dial';

  /**
   * Список назначений
   *
   * @var \channel\Line $destinations
   */
  private $destinations;

  private $timeout;

  private $options;

  /**
   * Конструктор приложения диалплана, принимает на вход параметры разделенные запятыми
   *
   * @param string $data Параметры диалплана
   */
  public function __construct(string $data) {
    $this->destinations = array();
    $this->timeout = null;
    $this->options = new DialOptions();
    parent::__construct($data);
  }

  /**
   * Получает значение параметра приложения
   *
   * @param string $property Наименование свойства
   * @return mixed Значение свойства
   */
  public function __get(string $property) {
    switch($property) {
      case 'comment': return $this->comment;
      case 'destinations': return $this->destinations;
      case 'timeout': return $this->timeout;
      case 'options': return $this->options;
    }
    return null;
  }

  /**
   * Устанавливает новое значение свойства приложения диалплана
   *
   * @param string $property Наименование свойства
   * @param mixed $value Новое значение свойства
   */
  public function __set(string $property, $value) {
    switch($property) {
      case 'comment': {
        $this->comment = $value;
      } break;
    }
  }

  /**
   * Производит разбор данных и сохраняет их в свойствах приложения, вызывается в том числе из конструктора
   *
   * @param string $data Набор параметров диалплана, разделенных запятыми
   * @return void
   */
  protected function parse(string $data) {
    $this->destinations = array();
    $this->timeout = null;
    $this->options->reset();
    $arguments = explode(',', $data, 3);
    $destinations = explode('&', $arguments[0]);
    $lines = new \channel\Lines();
    foreach($lines as $line) {
      foreach($destinations as $destination) {
        if($line instanceof \channel\Trunk) {
          $number = '';
          if($line->checkDial($destination, $number)) {;
            $line->dialnumber = $number;
            $this->destinations[] = $line;
            break;
          }
        } elseif($line instanceof \channel\Peer) {
          if($line->checkDial($destination)) {
            $this->destinations[] = $line;
            break;
          }
        }
      }     
    }
    if(!empty($arguments[1])) {
      if(is_numeric($arguments[1])) {
        $this->timeout = $arguments[1];
      } else {
        $this->timeout = new \dialplan\Variable($arguments[1]);
      }
    } 
    if(!empty($arguments[2])) $this->options->parse($arguments[2]);
  }

  /**
   * Возвращает свойства приложения диалплана
   *
   * @return \stdClass()
   */
  public function cast() {
    $result = new \stdClass();
    $dials = array();
    foreach($this->destinations as $destination) {
      if(isset($destination->uniqueoldid)) {
        $id = $destination->uniqueoldid;
      } else {
        $id = $destination->old_id;
      }
      if($destination instanceof \channel\Peer) $dials[] = (object)array('type' => 'peer', 'id' => $destination->getTypeName().'/'.$id);
      elseif($destination instanceof \channel\Trunk) $dials[] = (object)array('type' => 'trunk', 'id' => $destination->getTypeName().'/'.$id, 'phone' => $destination->dialnumber);
    }
    $result->name = self::$name;
    $result->dials = $dials;
    $result->timeout = $this->timeout?$this->timeout:null;
    $result->options = $this->options->cast();
    return $result;
  }

  /**
   * Преобразует параметры приложения в строку вида App(params)
   *
   * @return string
   */
  public function __toString() {
    $dials = array();
    foreach($this->destinations as $destination) {
      if($destination instanceof \channel\Peer) $dials[] = $destination->getDial();
      elseif($destination instanceof \channel\Trunk) $dials[] = $destination->getDial($destination->dialnumber);
    }
    return self::$name.'('.implode('&', $dials).','.($this->timeout?$this->timeout:'').','.$this->options.')';
  }

  /**
   * Принимает на вход структуру параметров приложения
   *
   * @param \stdClass $data
   * @return void
   */
  public function assign(\stdClass $data) {
    if(isset($data->type)&&isset($data->id)&&isset($data->data)) {
      if(($data->type == 'application')&&(strtolower($data->id) == strtolower(static::$name))) $this->assign($data->data);
    } else {
      foreach($data as $key => $value) {
        if(($key == 'dials')&&is_array($value)) {
          $this->destinations = array();
          foreach($value as $destination) {
            if(isset($destination->type)&&isset($destination->id)) {
              if($destination->type=='peer') {
                $peer = \channel\Peers::find($destination->id);
                if($peer) {
                  $this->destinations[] = $peer;
                }
              } elseif(($destination->type = 'trunk')&&(isset($destination->phone))) {
                $trunk = \channel\Trunks::find($destination->id);
                if($trunk) {
                  $trunk->dialnumber = $destination->phone;
                  $this->destinations[] = $trunk;
                }
              }
            }
          }
        } elseif($key == 'timeout') {
          if(is_numeric($value)) $this->timeout = $value;
          elseif(isset($value->type)&&($value->type == 'variable')) {
            $this->timeout = new \dialplan\Variable();
            $this->timeout->assign($value);
          }
        } elseif($key == 'options') {
          if(is_string($value)) {
            $this->options->parse($value);
          } else {
            $this->options->assign($value);
          }
        }
      }
    }    
  }

}

?>