<?php

namespace dial;

/**
 * Модуль вызова внутренних контактов или абоеннтов за внешней линией.
 * Вызов абонента:
 * AGI(agi=dial,contact=123@staff-book)
 * Вызов номера за транком
 * AGI(agi=dial,trunk=phone@trunkid)
 * Вызов SIP абонента
 * AGI(agi=dial,p2p=user@domain)
 */
class DialModule extends \Module implements \module\IAGI {

  private function localDial(\staff\Contact $contact) {
    if($this->agi->get_variable('DIALPLAN_EXISTS(ab-'.$contact->book.',contact_'.$contact->id.',1)', TRUE)) {
      if(!$this->agi->get_variable('CALLSTARTED', TRUE)) {
        //Получаем набор триггеров до начала звонка
        $triggers = getModulesByClass('core\BeginCallTrigger');
        foreach($triggers as $trigger) {
          $trigger->exec();
          unset($trigger);
        }
        unset($triggers);
        $calltype = $this->agi->get_variable('CALLTYPE', TRUE);
        if(empty($calltype)) $calltype = 'INTERNAL';
        $this->agi->set_variable('__CALLSTARTED', 1);
        $this->agi->set_variable('__CALLTYPE', $calltype);
        $this->agi->set_variable('CHANNEL(hangup_handler_push)', 'triggers,callend,1');
      }
      $opts = $this->agi->get_variable('DIAL_OPTS', TRUE);
      if(strpos($opts, 'b(triggers')===false) { //Проверяем не выставлены ли ещё параметры звонков
        $opts = $opts.'b(triggers^callsetup^1)';
        $this->agi->set_variable('__DIAL_OPTS', $opts);
      }
      //Вызов настоящей цепочки вызовов контакта
      $dials = array();
      $dails[] = 'Local/contact_'.$contact->id.'@staff-'.$contact->book.'/n';
      $triggers = getModulesByClass('core\CreateCallTrigger');
      foreach($triggers as $trigger) {
        $dial = $trigger->exec();
        if($dial) $dials[] = $dial;
        unset($trigger);
      }
      unset($triggers);
      $extraopts = '';
      if(count($dials)>1) $extraopts = 'b(triggers,callmultiplestart)';
      $this->agi->exec('Dial', implode('&', $dials).',,B(triggers^callbefore^1)U(triggers)'.$extraopts.'t');
    } else {
      $this->agi->verbose('Can not found dialplan entry point at "contact_'.$contact->id.'@staff-'.$contact->book.'"', 2);
      return;
    } 
  }

  private function trunkDial(\channel\Trunk $trunk, string $phone) {
    if(!$this->agi->get_variable('CALLSTARTED', TRUE)) {
      //Получаем набор триггеров до начала звонка
      $triggers = getModulesByClass('core\BeginCallTrigger');
      foreach($triggers as $trigger) {
        $trigger->exec();
        unset($trigger);
      }
      unset($triggers);
      if($trunk instanceof \channel\Provider) { //Если провайдер - исходящий, иначе маршрутизация
        $this->agi->set_variable('__CALLSTARTED', 1);
        $this->agi->set_variable('__CALLTYPE', 'OUTGOING');
      } else {
        $this->agi->set_variable('__CALLSTARTED', 1);
        $this->agi->set_variable('__CALLTYPE', 'FORWARD');
      }
      $this->agi->set_variable('CHANNEL(hangup_handler_push)', 'triggers,callend,1');
    }
    $opts = $this->agi->get_variable('DIAL_OPTS', TRUE);
    if(strpos($opts, 'b(triggers')===false) { //Проверяем не выставлены ли ещё параметры звонков
      $opts = $opts.'b(triggers^callsetup^1)';
      $this->agi->set_variable('__DIAL_OPTS', $opts);
    }
    //Вызов настоящей цепочки вызовов контакта
    if(strpos($this->agi->channel, 'Local')!==0) { //Если вызов не из виртуального канала - ставим обработчик поднятия трубки
      $opts = 'U(triggers)'.$opts;
    }
    $this->agi->exec('Dial', $trunk->getDial($phone).',,B(triggers^callbefore^1)'.$opts);
  }

  public function agi(\stdClass $request_data) {
    if(isset($request_data->contact)) {
      if($request_data->contact===true) {      
        $contactid = '';
        if(strpos($this->agi->context, 'staff-')===0) {
          $contactid = $this->agi->extension.'@'.substr($this->agi->context, 3);
        }
      } else { 
        $contactid = $request_data->contact;
      }
      $contact = new \staff\Contact($contactid);
      if(!$contact->old_id) {
        $this->agi->verbose('Can not found contact with id "'.$contactid.'"', 2);
        return;
      } 
      $this->localDial($contact);
    } elseif(isset($request_data->trunk)) { //
      if($request_data->trunk!==true) {
        $this->agi->verbose('Trunk id are not set', 2);
        return;
      }
      $trunkid = $request_data->trunk;
      $phone = $this->agi->extension;
      if(strpos($trunkid, '@')!==false) {
        $phoneattrunk = explode('@', $trunkid, 2);
        $phone = $phoneattrunk[0];
        $trunkid = $phoneattrunk[1];
        unset($phoneattrunk);
      }
      $trunk = \channel\Trunks::find($trunkid);
      if($trunk) {
        $this->agi->verbose('Can not found trunk with id "'.$trunkid.'"', 2);
        return;
      } 
      $this->trunkDial($trunk, $phone);
    } elseif(isset($request_data->p2p)) { //
      $domain = $this->agi->get_variable('SIPDOMAIN', TRUE);
      $user = $this->agi->extension;
      if($request_data->p2p!==true) {
        $domain = $request_data->p2p;
        if(strpos($domain, '@')!==false) {
          $useratdomain = explode('@', $domain, 2);
          $user = $useratdomain[0];
          $domain = $useratdomain[1];
          unset($useratdomain);
        }
      }
      $local = true; //Проверяем не локальный ли домен

      $opts = $this->agi->get_variable('DIAL_OPTS', TRUE);
      if(strpos($opts, 'b(triggers')===false) { //Проверяем не выставлены ли ещё параметры звонков
        $opts = $opts.'b(triggers^callsetup^1)';
        $this->agi->set_variable('__DIAL_OPTS', $opts);
      }
      if($local) {
        $contact = \staff\Group::find($user.'@'.$domain);
        if($contact) {
          $this->localDial($contact);
        } else {
          $this->agi->verbose('Can not found contact with name "'.$user.'"', 2);
          return;
        }
      } else {
        $calltype = $this->agi->get_variable('CALLTYPE', TRUE);
        if($calltype == 'INCOMING') {
          $this->agi->verbose('Forbidden to forward sip2sip calls directly', 2);
          return;
        }
        $this->agi->set_variable('__CALLSTARTED', 1);
        $this->agi->set_variable('__CALLTYPE', 'OUTGOING');
        if(strpos($this->agi->channel, 'Local')!==0) { //Если вызов не из виртуального канала - ставим обработчик поднятия трубки
          $opts = 'U(triggers)'.$opts;
        }
        $this->agi->exec('Dial', 'PJSIP/sip2sip/sip:'.$user.'@'.$domain.',,B(triggers^callbefore^1)'.$opts);
      }
    } elseif(isset($request_data->trigger)) { //
      switch($this->agi->extension) {
        case 's':
        case 'callanswer': { //Завершение звонка
            //Получаем набор триггеров завершения звонка
          $triggers = getModulesByClass('core\AnswerCallTrigger');
          foreach($triggers as $trigger) {
            $trigger->exec();
            unset($trigger);
          }
          unset($triggers);
        } break;
        case 'callend': { //Завершение звонка
          //Получаем набор триггеров завершения звонка
          $triggers = getModulesByClass('core\EndCallTrigger');
          foreach($triggers as $trigger) {
            $trigger->exec();
            unset($trigger);
          }
          unset($triggers);
          $this->agi->set_variable('__CALLSTARTED', '0');
        } break;
        case 'callmultiplestart': { //Перед началом вызова абонента (при наличии дополнительных вызовов)
          //Получаем набор триггеров
          $triggers = getModulesByClass('core\MultipleStartCallTrigger');
          foreach($triggers as $trigger) {
            $trigger->exec();
            unset($trigger);
          }
          unset($triggers);
          $this->agi->set_variable('CHANNEL(hangup_handler_push)', 'triggers,call,ultiplestop,1');
        } break;
        case 'callmultiplestop': { //После окончания вызова абонета (для каждого параллельного звонка)
          $triggers = getModulesByClass('core\MultipleStopCallTrigger');
          foreach($triggers as $trigger) {
            $trigger->exec();
            unset($trigger);
          }
          unset($triggers);
        } break;
        case 'callbefore': { //Перед началом вызова абонента
          //Получаем набор триггеров завершения звонка
          $triggers = getModulesByClass('core\BeforeCallTrigger');
          foreach($triggers as $trigger) {
            $trigger->exec();
            unset($trigger);
          }
          unset($triggers);
          $this->agi->set_variable('CHANNEL(hangup_handler_push)', 'triggers,callafter,1');
        } break;
        case 'callafter': { //После окончания вызова абонента
          $triggers = getModulesByClass('core\AfterCallTrigger');
          foreach($triggers as $trigger) {
            $trigger->exec();
            unset($trigger);
          }
          unset($triggers);
        } break;
        case 'callsetup': { //Инициализация канала назначения
          //Получаем набор триггеров завершения звонка
          $triggers = getModulesByClass('core\SetupCallTrigger');
          foreach($triggers as $trigger) {
            $trigger->exec();
            unset($trigger);
          }
          unset($triggers);
          $this->agi->set_variable('CHANNEL(hangup_handler_push)', 'triggers,calldestroy,1');
        } break;
        case 'calldestroy': { //Уничтожение канала назначения
          $triggers = getModulesByClass('core\DestroyCallTrigger');
          foreach($triggers as $trigger) {
            $trigger->exec();
            unset($trigger);
          }
          unset($triggers);
        } break;
        default: {
          $this->agi->verbose('Unknown call trigger type "'.$this->agi->extension.'"', 2);
          return;
        } break;
      }
    }
  }

}

?>