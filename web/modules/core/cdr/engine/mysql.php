<?php

namespace core;

class MysqlCdrEngine extends CdrEngine {

  private static $connection = null;
  private static $table = 'cdr';

  private static $defaultsettings = '{
    "hostname": "127.0.0.1",
    "table": "cdr",
    "dbname": "cdrdb",
    "password": "asterisk",
    "user": "asterisk",
    "port": 3306,
    "sock": ""
  }';

  public function info() {
    return (object) array("name" => 'mysql', "title" => 'База данных MySQL');
  }

  private static function TableExists($sql, $table) {
    $res = $sql->query("SHOW TABLES LIKE \"".$sql->real_escape_string($table)."\"");
    return $res&&($res->num_rows > 0);
  }

  private static function checkSettings() {
    $ini = self::getINI('/etc/asterisk/cdr_mysql.conf');
    $ini->global->normalize(self::$defaultsettings);
    $settings = $ini->global;
    if(self::$connection == null) {
      try {
        self::$connection = @new \mysqli($settings->hostname, $settings->user, $settings->password, $settings->dbname, $settings->port, $settings->sock);
      } catch(\Exception $e) {
        self::$connection = null;
      }
    }
    if(!self::$connection) {
      self::$connection = null;
      return 1;
    } else {
      if(self::$connection->connect_error!='') {
        self::$connection = null;
        return 1;
      }
      if(!@self::$connection->select_db($settings->dbname)) return 2;
      if(!self::TableExists(self::$connection, $settings->table)) return 3;
      self::$table = $settings->table;
    }
    return 0;
  }

  public static function check() {
    $result = true;
    $result &= self::checkModule('cdr', 'mysql', true);
    $result &= self::checkPriv('cdr');
    $result &= self::checkLicense('oasterisk-core');
    $result &= (self::checkSettings()==0);
    return $result;
  }

  public static function getValue(&$array, $key, $default) {
    if(isset($array[$key])) return $array[$key];
    return $default;
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
    self::checkSettings();
    if(self::$connection) {
      self::$connection->query('SET names \'utf8\'');
      $stmt = self::$connection->prepare('select * from '.self::$connection->real_escape_string(self::$table).' where start between ? and ? order by uniqueid asc, start asc');
      if($stmt)
      $fromstr=$from->format('Y-m-d\TH:i:s');
      $tostr=$to->format('Y-m-d\TH:i:s');
      $stmt->bind_param('ss', $fromstr, $tostr);
      $stmt->execute();
      $stmt->store_result();
      $row=array();
      $bindVarsArray=array();
      $meta = $stmt->result_metadata();
      while ($column = $meta->fetch_field()) {
        $bindVarsArray[] = &$row[$column->name];
      }
      call_user_func_array(array($stmt, 'bind_result'), $bindVarsArray);

      while($stmt->fetch()) {
        $rowdata = new \stdClass();
        $rowdata->id=$row['sequence'];
        $rowdata->uid=$row['uniqueid'];
        $rowdata->lid=isset($row['linkedid'])?$row['linkedid']:null;
        $rowdata->from=new \DateTime($row['start'],new \DateTimeZone('UTC'));
        $rowdata->answer=null;
        $rowdata->to=null;
        $rowdata->seconds=isset($row['billsec'])?$row['billsec']:null;
        $rowdata->duration=isset($row['duration'])?$row['duration']:null;
        if(($rowdata->to===null)&&($rowdata->duration!==null)) $rowdata->to=(clone $rowdata->from)->add(new \DateInterval('PT'.$rowdata->duration.'S'));
        if(($rowdata->answer===null)&&($rowdata->to!==null)&&($rowdata->seconds!==null)) $rowdata->answer=(clone $rowdata->to)->sub(new \DateInterval('PT'.$rowdata->seconds.'S'));
        if(($rowdata->duration===null)&&($rowdata->to!==null)) $rowdata->duration=getDateDiff($rowdata->from, $rowdata->to);
        if(($rowdata->seconds===null)&&($rowdata->answer!==null)&&($rowdata->to!==null)) $rowdata->seconds=getDateDiff($rowdata->answer, $rowdata->to);
        $rowdata->src = new \stdClass();
        $rowdata->src->channel=$row['channel'];
        $rowdata->src->user=$row['src']?$row['src']:'';
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
        $rowdata->dst->channel=$row['dstchannel']?$row['dstchannel']:'';
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
      $stmt->close();
    }
    return $result;
  }

}

?>
