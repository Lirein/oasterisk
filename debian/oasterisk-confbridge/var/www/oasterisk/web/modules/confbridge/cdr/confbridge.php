<?php

namespace confbridge;

class ConfbridgeCdrFilter extends \core\CdrFilter {

  public static function check() {
    return self::checkLicense('oasterisk-confbridge');
  }

  public function apps() {
     return array('ConfBridge', 'AGI', 'Playback');
  }

  public function states() {
     return array('incoming' => 'Входящий', 'outgoing' => 'Исходящий', 'initialized' => 'Инициализация', 'kick' => 'Отбит', 'mute' => 'Симплекс', 'unmute' => 'Дуплекс', 'hold' => 'Изолирован', 'unhold' => 'Снят с изоляции', 'merge' => 'Объединение', 'merged' => 'Объединен', 'invalidpin' => 'Неверный пинкод');
  }

  public static function reassignUID(&$cdr, $uid) {
    foreach(array_keys($cdr) as $key) {
      $cdr[$key]->uid=$uid;
      if(!empty($cdr[$key]->action)) {
        $cdr[$key]->dst=$cdr[$key]->src;
        $cdr[$key]->src->name='Оператор';
        $cdr[$key]->src->num='';
        $cdr[$key]->src->user='';
        $cdr[$key]->state=$cdr[$key]->action;
      }
      if(isset($cdr[$key]->value)) self::reassignUID($cdr[$key]->value, $uid);
    }
  }

  private static function cdrcmp(&$a, &$b) {
    if($a->from>$b->from) return 1;
    if($a->from<$b->from) return -1;
    return 0;
  }

  public function filter($data) {
    static $abmodule = null;
    $records=&$data->records;
    $record=&$records[$data->record];
    switch($record->entry->app) {
      case 'Playback': {
        if($record->action=='invalidpin') {
          if(($record->src->name=='')||($record->src->name==$record->src->num)) {
            if(!$abmodule) $abmodule = getModuleByClass('addressbook\AddressBook');
            if($abmodule) {
              $books = $abmodule->getBooks();
              foreach($books as $book => $bookname) {
                $contacts = $abmodule->getContacts($book);
                foreach($contacts as $contact) {
                  if(in_array($record->src->num, $contact->numbers)) {
                    $record->src->name = $contact->name;
                    break;
                  }
                }
                if(!(($record->src->name=='')||($record->src->name==$record->src->num))) break;
              }
            }
          }
          $record->state='invalidpin';
        }
      } break;
      case 'AGI': {
        $params=explode(',',$record->entry->data);
        if(($params[0]=='oasterisk.php')&&($params[1]=='agi=confbridge')) {
          switch($params[2]) {
            case 'failed': {
              $room=rawurldecode(substr($record->record, 11));
              $record->src->name=$room;
              $record->dst->name=$record->action;
              if($record->dst->num=='failed') {
                $record->dst->channel=$record->src->channel;
                $record->dst->num='';
                $record->dst->user=substr($record->src->channel, strrpos($record->src->channel, '/')+1);
              }
              $record->record='';
              $currchan=substr($record->src->channel, strpos($record->src->channel, '/')+1);
              $currchan=substr($record->src->channel, 0, strpos($record->src->channel, '/')).'/'.((strpos($currchan, '/')!==false)?substr($currchan,0,strpos($currchan, '/')):$currchan);
              foreach(array_keys($records) as $key) {
                if($key==$data->record) break;
                if(($records[$key]->entry->app=='AppDial2')&&($records[$key]->src->num==$record->src->num)) {
                  $chkchan=(strpos($records[$key]->src->channel,'-')!==false)?substr($records[$key]->src->channel, 0, strpos($records[$key]->src->channel, '-')):$records[$key]->src->channel;
                  if($chkchan==$currchan) {
                    $diff=getDateDiff($records[$key]->to, $record->from);
                    if($diff<2) {
                      unset($records[$key]);
                      break;
                    }
                  }
                }
              }
              if(!self::checkEffectivePriv('confbridge_room', $room, 'cdr')) unset($records[$data->record]);
            } break;
          }
        }
      } break;
      case 'ConfBridge': {
        $params=explode(',', $record->entry->data);
        $room=$params[0];
        if(!isset($record->value)) $record->value=array();
        array_unshift($record->value, self::clone($record));
        if(isset($record->value[0])&&isset($record->value[0]->value)) unset($record->value[0]->value);
        $record->dst->name=$room;
        if($record->dst->num==$room) $record->dst->num='';
        $record->dst->channel='';
        $record->entry->app='dummy';
        $record->entry->context='confbridge';
        $record->state='initialized';
        $record->value[0]->entry->app='dummy';
        $record->value[0]->dst = clone $record->value[0]->src;
        $record->value[0]->src->name='Участник';
        $record->value[0]->src->user='';
        $record->value[0]->src->num='';
        $record->value[0]->state=($record->entry->context=='confbridge')?'outgoing':'incoming';
        $record->value[0]->action='';
        foreach(array_keys($records) as $key) {
          if(($key!=$data->record)&&($records[$key]->entry->app=='ConfBridge')&&($records[$key]->uid!=$record->uid)) {
            $params=explode(',', $records[$key]->entry->data);
            $recroom=$params[0];
            if(($recroom==$room)&&($records[$key]->from>=$record->from)&&($records[$key]->from<=(clone $record->to)->add(new \DateInterval('PT2S')))) {
              if($records[$key]->to>$record->to) {
                $record->to=clone $records[$key]->to;
                $record->duration=getDateDiff($record->from, $record->to);
                $record->seconds=getDateDiff($record->answer, $record->to);
              }
              $record->value[$key]=$records[$key];
              $record->value[$key]->entry->app='dummy';
              $record->value[$key]->dst = clone $record->value[$key]->src;
              $record->value[$key]->src->name='Участник';
              $record->value[$key]->src->num='';
              $record->value[$key]->src->user='';
              $record->value[$key]->state=($record->value[$key]->entry->context=='confbridge')?'outgoing':'incoming';
              $record->value[$key]->action='';
              if(isset($records[$key]->value)) {
                $record->value=array_merge($record->value, $records[$key]->value);
                unset($record->value[$key]->value);
              }
              unset($records[$key]);
            }
          }
        }
        self::reassignUID($record->value, $record->uid);
        usort($record->value, array(__CLASS__, 'cdrcmp'));
        foreach(array_keys($record->value) as $key) {
          if(isset($record->value[$key])) {
            if($record->value[$key]->entry->app=='ConfBridge') {
              $recroom=explode('&',explode(',',$record->value[$key]->entry->data)[0]);
              if(isset($recroom[1])) {
                $record->value[$key]->value=array(self::clone($record->value[$key]));
                $record->value[$key]->value[0]->record='';
                $record->value[$key]->value[0]->dst= clone $record->value[$key]->value[0]->src;
                $record->value[$key]->value[0]->src->name='Участник';
                $record->value[$key]->value[0]->src->num='';
                $record->value[$key]->value[0]->src->user='';
                $record->value[$key]->value[0]->state='merged';

                $record->value[$key]->entry->app='dummy';
                $srcs=array();
                if(!empty($record->value[$key]->src->name)) {
                  $srcs[]=$record->value[$key]->src->name;
                } elseif(!empty($record->value[$key]->src->num)) {
                  $srcs[]=$record->value[$key]->src->num;
                } else {
                  $srcs[]=$record->value[$key]->src->user;
                }
                $record->value[$key]->src->name='Оператор';
                $record->value[$key]->src->num='';
                $record->value[$key]->src->user='';
                $record->value[$key]->dst->num='';
                $record->value[$key]->dst->user='';
                $record->value[$key]->dst->channel='';
                $record->value[$key]->state='merge';
                if($record->value[$key]->record==$record->record) $record->value[$key]->record='';
                foreach(array_keys($record->value) as $subkey) {
                  if(($key!=$subkey)&&($record->value[$subkey]->entry->app=='ConfBridge')) {
                    $subrecroom=explode('&',explode(',',$record->value[$subkey]->entry->data)[0]);
                    if(isset($subrecroom[1])&&($recroom[1]==$subrecroom[1])&&($record->value[$subkey]->from>=$record->value[$key]->from)&&($record->value[$subkey]->from<=$record->value[$key]->to)) {
                      if(!empty($record->value[$subkey]->src->name)) {
                        $srcs[]=$record->value[$subkey]->src->name;
                      } elseif(!empty($record->value[$subkey]->src->num)) {
                        $srcs[]=$record->value[$subkey]->src->num;
                      } else {
                        $srcs[]=$record->value[$subkey]->src->user;
                      }
                      if($record->value[$subkey]->to>$record->value[$key]->to) {
                        $record->value[$key]->to=clone $record->value[$subkey]->to;
                        $record->value[$key]->duration=getDateDiff($record->value[$key]->from, $record->value[$key]->to);
                        $record->value[$key]->seconds=getDateDiff($record->value[$key]->answer, $record->value[$key]->to);
                      }
                      if(!empty($record->value[$subkey]->record)&&empty($record->value[$key]->record)) $record->value[$key]->record=$record->value[$subkey]->record;
                      $record->value[$subkey]->record='';
                      $record->value[$subkey]->dst=clone $record->value[$subkey]->src;
                      $record->value[$subkey]->src->name='Участник';
                      $record->value[$subkey]->src->num='';
                      $record->value[$subkey]->src->user='';
                      $record->value[$subkey]->state='merged';
                      $record->value[$key]->value[]=self::clone($record->value[$subkey]);
                      unset($record->value[$subkey]);
                    }
                  }
                }
                $record->value[$key]->dst->name=implode(', ',$srcs);
              } else {
                if(!empty($record->value[$key]->record)&&empty($record->record)) $record->record=$record->value[$key]->record;
                $record->value[$key]->record='';
                if($record->value[$key]->action=='') {
                  unset($record->value[$key]);
                }
              }
            } else {
              if(!empty($record->value[$key]->record)&&empty($record->record)) $record->record=$record->value[$key]->record;
              $record->value[$key]->record='';
            }
          }
        }
        if(!self::checkEffectivePriv('confbridge_room', $room, 'cdr')) unset($records[$data->record]);
        return true;
      } break;
    }
    return false;
  }

}

?>