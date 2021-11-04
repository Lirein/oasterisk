<?php

namespace core;

class SQLiteCdrEngine extends CdrEngine {

  public function info() {
    return (object) array("name" => 'sqlite', "title" => 'База данных SQLite');
  }

  public static function check() {
    $result = true;
    $result &= self::checkModule('cdr', 'sqlite3_custom', true);
    $result &= self::checkPriv('cdr');
    $result &= self::checkLicense('oasterisk-core');
    $ini = self::getINI('/etc/asterisk/cdr_sqlite3_custom.conf');
    $result &= isset($ini->master)&&isset($ini->master->table);
    unset($ini);
    return $result;
  }

  /***
  Return rowset with structure:
   {id - row id
    uid, lid - unique and linked id
    from - call date
    answer - answer date
    to - call end date
    duration - total call time
    seconds - answered time
    src - {
           channel - source channel in <TECH>/<peer>
           user - source peer
           num - source phone number
           name - source callerid name
          }
    dst - {
           channel - destination channel in <TECH>/<peer>
           user - empty value
           num - destination phone number
           name - destination callerid name
          }
     entry - {
              exten - Extension
              context - Context
              app - Application name
              data - Application data
             }
     state - Call status
     record - Record file
     action - Extra realtime operator action
    }
  */
  public function cdr(\DateTime $from, \DateTime $to) {
    $result = array();
    $sql = new \SQLite3('/var/log/asterisk/master.db', SQLITE3_OPEN_READONLY);
    if($sql) {
      $stmt = @$sql->prepare('select * from cdr where datetime(calldate) between datetime(:from) and datetime(:to) order by uniqueid asc, datetime(calldate) asc');
      $res = false;
      if($stmt) {
        $stmt->bindValue(':from', $from->format('Y-m-d\TH:i:s'), SQLITE3_TEXT);
        $stmt->bindValue(':to', $to->format('Y-m-d\TH:i:s'), SQLITE3_TEXT);
        $res = $stmt->execute();
      }
      if($res)
      while($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $rowdata = new \stdClass();
        $rowdata->id=$row['AcctId'];
        $rowdata->uid=$row['uniqueid'];
        $rowdata->lid=isset($row['linkedid'])?$row['linkedid']:null;
        $rowdata->from=new \DateTime($row['calldate']);
        $rowdata->answer=isset($row['callanswer'])?(new \DateTime($row['callanswer'])):null;
        $rowdata->to=isset($row['callend'])?(new \DateTime($row['callend'])):null;
        $rowdata->seconds=isset($row['billsec'])?$row['billsec']:null;
        $rowdata->duration=isset($row['duration'])?$row['duration']:null;
        if(($rowdata->to===null)&&($rowdata->duration!==null)) $rowdata->to=(clone $rowdata->from)->add(new \DateInterval('PT'.abs($rowdata->duration).'S'));;
        if(($rowdata->answer===null)&&($rowdata->to!==null)&&($rowdata->seconds!==null)) $rowdata->answer=(clone $rowdata->to)->sub(new \DateInterval('PT'.abs($rowdata->seconds).'S'));
        if(($rowdata->duration===null)&&($rowdata->to!==null)) $rowdata->duration=getDateDiff($rowdata->from, $rowdata->to);
        if(($rowdata->seconds===null)&&($rowdata->answer!==null)&&($rowdata->to!==null)) $rowdata->seconds=getDateDiff($rowdata->answer, $rowdata->to);
        $rowdata->src = new \stdClass();
        $rowdata->src->channel=$row['channel'];
        $rowdata->src->user=$row['src'];
        $rowdata->src->num='';
        $rowdata->src->name='';
        if(isset($row['clid']))
          if(preg_match('/("(.+)"|([^ <>]+)|)\s*(<([a-z0-9.@_-]{0,})(>|)|)/',$row['clid'],$match)) {
            if(empty($match[2])) $rowdata->src->name=mb_check_encoding($match[1])?$match[1]:'';
              else $rowdata->src->name=mb_check_encoding($match[2])?$match[2]:'';
            $rowdata->src->num=$match[5];
            if(($rowdata->src->name=='""')||($rowdata->src->num==$rowdata->src->name)) $rowdata->src->name='';
          };
        $rowdata->dst = new \stdClass();
        $rowdata->dst->channel=$row['dstchannel'];
        $rowdata->dst->user='';
        $rowdata->dst->num='';
        $rowdata->dst->name='';
        if(isset($row['dstclid']))
          if(preg_match('/("(.+)"|([^ <>]+)|)\s*(<([a-z0-9.@_-]{0,})(>|)|)/',$row['dstclid'],$match)) {
            if(empty($match[2])) $rowdata->dst->name=mb_check_encoding($match[1])?$match[1]:'';
              else $rowdata->dst->name=mb_check_encoding($match[2])?$match[2]:'';
            if(isset($match[5]))$rowdata->dst->num=$match[5];
            if(($rowdata->dst->name=='""')||($rowdata->dst->num==$rowdata->dst->name)) $rowdata->dst->name='';
          };
        $rowdata->entry = new \stdClass();
        $rowdata->entry->exten=isset($row['dst'])?$row['dst']:null;
        $rowdata->entry->context=isset($row['dcontext'])?$row['dcontext']:null;
        $rowdata->entry->app=$row['lastapp'];
        $rowdata->entry->data=$row['lastdata'];
        $rowdata->state=strtolower($row['disposition']);
        $rowdata->record=isset($row['recordingfile'])?(empty($row['recordingfile'])?'':'/recording/'.rawurlencode(str_replace('/var/spool/asterisk/monitor/','',$row['recordingfile']))):'';
        $rowdata->action=isset($row['action'])?trim($row['action']," \t\n\r\0\x0B\"\'"):'';
        if(empty($rowdata->dst->num)) $rowdata->dst->num=$rowdata->entry->exten;
        $result[] = $rowdata;
      }
    }
    return $result;
  }

}

?>
