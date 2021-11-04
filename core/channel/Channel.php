<?php

namespace channel;

/**
 * @ingroup coreapi
 * Класс обертка над активным каналом ядра технологичейской платформы. Позволяет работать с переменными и параметрами канала.
 * Содержит ссылку на субьект описывающий многоканальное соединения или сущность сотрудника.
 */
class Channel extends \Module {

  /**
   * Уникальный идентификатор канала
   * 
   * @var string $id
   */
  private $id;

  /**
   * Уникальное наименование канала в технологический платформе
   * 
   * @var string $name
   */
  private $name;

  /**
   * Номер телефона канала
   * 
   * @var string $phone
   */
  private $phone;

  /**
   * Конструрктор канала, поолучает идентификатор канала
   *
   * @param string $channel Идентификатор канала (имя канала) технологической платформы
   */
  public function __construct(string $channel) {
    $this->name = $channel;
    if(!$chaneldata = $this->cache->get('channel-'.$channel)) {
      $channeldata = new \stdClass();
      if($this->agi) {
        $channeldata->id = $this->agi->get_variable('IMPORT('.$this->name.',UNIQUEID)', true);
        $channeldata->phone = $this->agi->get_variable('IMPORT('.$this->name.',CALLERID(num))', true);
      } else {
        $channeldata->id = $this->ami->get_variable($this->name, 'UNIQUEID');
        $channeldata->phone = $this->ami->get_variable($this->name, 'CALLERID(num)');
      }
      $this->cache->set('channel-'.$channel, $chaneldata, 300);
    }
    $this->id = $chaneldata->id;
    $this->phone = $chaneldata->phone;
    if(!$this->line) $this->line = new Peer($this->lineid);
  }

  /**
   * Возвращает массив допустимых сущностей многоканальных линий и сотрудников для данного канала
   *
   * @return Line[]
   */
  public function getCompartibleLines() {
    $lines = array();
    $peers = new Peers();
    foreach($peers as $peer) {
      if($peer->checkChannel($this->name, $this->phone)) {
        $lines[$peer->id] = $peer;
        break;
      }
    }
    $trunks = new Trunks();
    foreach($trunks as $trunk) {
      if($trunk->checkChannel($this->name, $this->phone)) {
        $lines[$trunk->id] = $trunk;
        $this->line = $trunk;
        break;
      }
    }
    return $lines;
  }

  public static function getCauseNumber($causecode) {
    $result = 0;
    switch($causecode) {
      case 'NOTDEFINED':
      case 'NONE': {
        $result = 0;
      } break;
      case 'ANSWERED_ELSEWHERE': {
        $result = 26;
      } break;
      case 'NOANSWER':
      case 'NO_ANSWER': {
        $result = 19;
      } break;
      case 'BUSY':
      case 'USER_BUSY': {
        $result = 17;
      } break;
      case 'CALL_REJECTED': {
        $result = 21;
      } break;
      case 'NO_ROUTE_TRANSIT_NET': {
        $result = 2;
      } break;
      case 'NO_ROUTE_DESTINATION': {
        $result = 3;
      } break;
      case 'MISDIALLED_TRUNK_PREFIX': {
        $result = 5;
      } break;
      case 'CHANNEL_UNACCEPTABLE': {
        $result = 6;
      } break;
      case 'CALL_AWARDED_DELIVERED': {
        $result = 7;
      } break;
      case 'PRE_EMPTED': {
        $result = 8;
      } break;
      case 'NUMBER_PORTED_NOT_HERE': {
        $result = 14;
      } break;
      case 'NORMAL':
      case 'NORMAL_CLEARING': {
        $result = 16;
      } break;
      case 'NO_USER_RESPONSE': {
        $result = 18;
      } break;
      case 'UNREGISTERED':
      case 'SUBSCRIBER_ABSENT': {
        $result = 20;
      } break;
      case 'NUMBER_CHANGED': {
        $result = 22;
      } break;
      case 'REDIRECTED_TO_NEW_DESTINATION': {
        $result = 23;
      } break;
      case 'DESTINATION_OUT_OF_ORDER': {
        $result = 27;
      } break;
      case 'INVALID_NUMBER_FORMAT': {
        $result = 28;
      } break;
      case 'FACILITY_REJECTED': {
        $result = 29;
      } break;
      case 'RESPONSE_TO_STATUS_ENQUIRY': {
        $result = 30;
      } break;
      case 'NORMAL_UNSPECIFIED': {
        $result = 31;
      } break;
      case 'CONGESTION':
      case 'NORMAL_CIRCUIT_CONGESTION': {
        $result = 34;
      } break;
      case 'FAILURE':
      case 'NETWORK_OUT_OF_ORDER': {
        $result = 38;
      } break;
      case 'NORMAL_TEMPORARY_FAILURE': {
        $result = 41;
      } break;
      case 'SWITCH_CONGESTION': {
        $result = 42;
      } break;
      case 'ACCESS_INFO_DISCARDED': {
        $result = 43;
      } break;
      case 'REQUESTED_CHAN_UNAVAIL': {
        $result = 44;
      } break;
      case 'FACILITY_NOT_SUBSCRIBED': {
        $result = 50;
      } break;
      case 'OUTGOING_CALL_BARRED': {
        $result = 52;
      } break;
      case 'INCOMING_CALL_BARRED': {
        $result = 54;
      } break;
      case 'BEARERCAPABILITY_NOTAUTH': {
        $result = 57;
      } break;
      case 'BEARERCAPABILITY_NOTAVAIL': {
        $result = 58;
      } break;
      case 'BEARERCAPABILITY_NOTIMPL': {
        $result = 65;
      } break;
      case 'NOSUCHDRIVER':
      case 'CHAN_NOT_IMPLEMENTED': {
        $result = 66;
      } break;
      case 'FACILITY_NOT_IMPLEMENTED': {
        $result = 69;
      } break;
      case 'INVALID_CALL_REFERENCE': {
        $result = 81;
      } break;
      case 'INCOMPATIBLE_DESTINATION': {
        $result = 88;
      } break;
      case 'INVALID_MSG_UNSPECIFIED': {
        $result = 95;
      } break;
      case 'MANDATORY_IE_MISSING': {
        $result = 96;
      } break;
      case 'MESSAGE_TYPE_NONEXIST': {
        $result = 97;
      } break;
      case 'WRONG_MESSAGE': {
        $result = 98;
      } break;
      case 'IE_NONEXIST': {
        $result = 99;
      } break;
      case 'INVALID_IE_CONTENTS': {
        $result = 100;
      } break;
      case 'WRONG_CALL_STATE': {
        $result = 101;
      } break;
      case 'RECOVERY_ON_TIMER_EXPIRE': {
        $result = 102;
      } break;
      case 'MANDATORY_IE_LENGTH_ERROR': {
        $result = 103;
      } break;
      case 'PROTOCOL_ERROR': {
        $result = 111;
      } break;
      case 'INTERWORKING': {
        $result = 127;
      } break;
      default: {
        if(is_numeric($causecode)) {
          $result = (int)$causecode;
        }
      }
    }
    return $result;
  }

}