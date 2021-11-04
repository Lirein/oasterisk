<?php

namespace planfix;

class PlanfixModule extends \core\Module implements \AGIInterface, \JSONInterface {

  static $planfixLastError = NULL;

  public static function check() {
    global $_AMI;
    $result = true;
    if(isset($_AMI)) $result &= self::checkPriv('dialing');
    $result &= self::checkLicense('oasterisk-planfix');
    return $result;
  }

  public static function getLocation() {
    return 'integration';
  }

  public function agi(\stdClass $request_data) {
    $calltype='';
    if(isset($request_data->in)) {
      $calltype='in';
    }
    if(isset($request_data->out)) {
      $calltype='out';
    }
    if(isset($request_data->api)) {
      switch($request_data->api) {
        case 'updateContact': {
          $id=isset($request_data->id)?$request_data->id:'';
          $group=isset($request_data->group)?$request_data->group:'';
          $groupList = $this->cache->get('planfixcontactgrouplist');
          if(!is_array($groupList)||(is_array($groupList)&&!isset($groupList[$group]))) {
            $request = self::requestContactGroupList();
            $groupList = self::extractContactGroupList($this->sendAPIRequest($request));
            $this->cache->set('planfixcontactgrouplist', $groupList, 86400);
          }
          if(is_array($groupList)&&isset($groupList[$group])) {
            $request = self::requestContactUpdate($id,array('group' => array('id' => $groupList[$group])),false);
            $this->sendAPIRequest($request);
          }
        } break;
      }
    } elseif(isset($request_data->contact)) {
      $this->getContactInfo();
    } elseif(isset($request_data->record)) {
      $this->startRecord();
    } elseif(isset($request_data->start)) {
      $this->sendEvent('INCOMING',$calltype);
    } elseif(isset($request_data->answer)) {
      $this->sendEvent('ACCEPTED',$calltype);
    } elseif(isset($request_data->end)) {
      $this->sendEvent('COMPLETED',$calltype);
    }
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "planfix": {
        $apitoken=self::getDB('integration/planfix','localtoken');
        if(isset($request_data->token)&&($request_data->token == $apitoken)) {
          $completed=FALSE;
          if(isset($request_data->cmd)) {
            switch($request_data->cmd) {
              case 'makeCall': {
                $completed=$this->makeCall($request_data->from, $request_data->to);
              } break;
            }
          }
          if(!$completed) {
            http_response_code(400);
            $result=self::returnData(json_encode(array('error' => "Invalid parameters")), 'application/json');
          }
        } else if(isset($request_data->uuid)) {
          $this->sendRecord();
          $result = self::returnData('');
        } else {
          http_response_code(401);
          $result = self::returnData(json_encode(array('error' => "Invalid token"), 'application/json'));
        }
      } break;
    }
    return $result;
  }

  public function sendTelAPIRequest($command, $data) {
    $planfixapi=self::getDB('integration/planfix','apiurl');
    $planfixtoken=self::getDB('integration/planfix','token');
    $url = $planfixapi;
    $data['cmd']=$command;
    $data['planfix_token']=$planfixtoken;
    $req = http_build_query($data);
    $options = array(
            'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n"
                                ."Content-Length: " . strlen($req) . "\r\n",
                    'method'  => 'POST',
                    'content' => $req
            )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { return FALSE; }
    if(isset($this->agi)) {
      $this->agi->verbose('Request data '.print_r($req,true),4);
      $this->agi->verbose('Request result '.$result,4);
    }
    return json_decode($result);
  }

  public static function mapCallerID($callerid) {
    $extnumsimple=self::getDB('integration/planfix','simplemap');
    $extnum=array();
    $extcount=self::getDB('integration/planfix','mapcount');
    for($i=0; $i<$extcount; $i++) {
      $key=rawurldecode(self::getDB('integration/planfix','mapkey'.$i));
      $value=rawurldecode(self::getDB('integration/planfix','mapval'.$i));
      $extnum[$key]=$value;
    }
    if($extnumsimple) {
      if((strlen($callerid)>2)&&(substr($callerid,0,2)=='00')) {
        $callerid='+'.substr($callerid,2);
      } else if((strlen($callerid)>13)&&(substr($callerid,0,3)=='810')) {
        $callerid='+'.substr($callerid,3);
      } else if((strlen($callerid)>10)&&($callerid[0]==8)) {
        $callerid='+7'.substr($callerid,1);
      } else if((strlen($callerid)>10)&&($callerid[0]==7)) {
        $callerid='+'.$callerid;
      } else {
        if(isset($extnum[strlen($callerid)])) {
          $callerid=$extnum[strlen($callerid)].$callerid;
        }
      }
    } else {
      foreach($extnum as $regex => $replace) {
        if(preg_match('/'.$regex.'/', $callerid)) {
          $callerid = preg_replace('/'.$regex.'/', $replace,  $callerid);
        }
      }
    }
    return $callerid;
  }

  public function getContactInfo() {
    $this->agi->set_variable('CALL_TO','');
    $callerid=self::mapCallerID($this->agi->get_variable('CALLERID(num)', TRUE));
    $this->agi->verbose('Set request callerid to '.$callerid,4);
    $answer = $this->sendTelAPIRequest('contact', array('phone' => $callerid));

    if(isset($answer->contact_name)) {
      $this->agi->set_variable('CALLERID(name)',$answer->contact_name);
      $this->agi->verbose('Set caller id to '.$answer->contact_name,2);
    } else {
      $this->agi->verbose('No such contact as '.$callerid.' in PlanFix',2);
    }

    if(isset($answer->responsible)) {
//      if(isset($map[$answer->responsible])) $answer->responsible=$map[$answer->responsible];
      $this->agi->set_variable('CALL_TO',$answer->responsible);
      $this->agi->verbose('Set CALL_TO to '.$answer->responsible,3);
    } else {
      $this->agi->verbose('No responsible to '.$callerid.' in PlanFix',3);
    }
  }

  public function sendEvent($eventtype, $calltype) {
    $localurl=self::getDB('integration/planfix','localurl');
    $uniqueid = $this->agi->get_variable('UNIQ', TRUE);
    if(strlen($uniqueid)==0) {
      $uniqueid = $this->agi->get_variable('UNIQUEID', TRUE);
    }
    $extid = explode('&',$this->agi->get_variable('CALL_TO', TRUE));

    $this->agi->verbose('Send event '.$eventtype.' with mode '.$calltype.' uid '.$uniqueid,4);

//$map = array_flip($map);
//if(isset($map[$extid])) $extid=$map[$extid];


    $trunknum = trim($this->agi->get_variable('TRUNK_NUMBER', TRUE)); //outgoing trunk phone number
    if(strlen($trunknum)==0) {
      $trunknum = $this->agi->get_variable('DIVER', TRUE);
      if(strlen($trunknum)==0) {
        $trunknum = trim($this->agi->get_variable('CALLERID(DNID)', TRUE));
      }
    }
    $clientnum = ''; //client phone number
    $internalnum = ''; //interal phone number

    if(($eventtype=='INCOMING')&&($calltype=='out')) $eventtype='OUTGOING';

    if($eventtype == 'OUTGOING') {
      $callerid=$this->agi->get_variable('CALLERID(num)', TRUE);
      if(strpos($callerid,'*')!==false) {
        $callerid = str_replace('*','.',$callerid);
        $this->agi->set_variable('CALLERID(num)',$callerid);
      }
      $this->agi->set_variable('_AEXT',implode('&',$extid));
      $this->agi->set_variable('_UNIQ',$uniqueid);
      $this->agi->set_variable('_CLRID',$callerid);
      $this->agi->set_variable('_DIVER',$trunknum);
    }

    if($eventtype == 'INCOMING') {
      $callerid=self::mapCallerID($this->agi->get_variable('CALLERID(num)', TRUE));
      $this->agi->set_variable('_AEXT',implode('&',$extid));
      $this->agi->set_variable('_UNIQ',$uniqueid);
      $this->agi->set_variable('_CLRID',$callerid);
      $this->agi->set_variable('_DIVER',$trunknum);
    }

    $dialednum = '';
    if($eventtype == 'ACCEPTED') {
      $partyid=$this->agi->get_variable('CALLERID(num)', TRUE);
      $extid = explode('&',$this->agi->get_variable('AEXT', TRUE));
      $callerid = $this->agi->get_variable('CLRID', TRUE);
      $dialednum = $this->agi->get_variable('DIALEDPEERNUMBER', TRUE);
      $dialednuma = explode('/',$dialednum);
      $dialednum = array_pop($dialednuma);
    }

    if($eventtype != 'COMPLETED') {
      $this->agi->verbose('Set event request to '.$callerid,4);
    } else {
/*      $currentcaller=$this->agi->get_variable('CLRID', TRUE);
      $realcaller=$this->agi->get_variable('CALLERID(num)', TRUE);
      if(strpos($currentcaller,'*')!==false) {
        $currentcaller = str_replace('*','.',$callerid);
      }
      if(strpos($realcaller,'*')!==false) {
        $realcaller = str_replace('*','.',$callerid);
      }*/
    }

    $duration = 0;
    $dialstatus = '';

    $extdata = array();
    if($eventtype == 'COMPLETED') {
      $extid = explode('&',$this->agi->get_variable('AEXT', TRUE));
      $callerid = $this->agi->get_variable('CLRID', TRUE);
      $duration = $this->agi->get_variable('DIALEDTIME', TRUE);
      $dialstatus = $this->agi->get_variable('DIALSTATUS', TRUE);
      $dialednum = $this->agi->get_variable('DIALEDPEERNUMBER', TRUE);
      $dialednuma = explode('/',$dialednum);
      $dialednum = array_pop($dialednuma);
      $this->agi->verbose('Dialed num is '.$dialednum,3);

      //CHANUNAVAIL | CONGESTION | BUSY | NOANSWER | ANSWER | CANCEL | HANGUP
      if($calltype=='in') {
        if($dialstatus=='ANSWER') $dialstatus = 'Success';
        else $dialstatus='Missed';
      } else {
        if($dialstatus=='ANSWER') $dialstatus = 'Success';
        elseif($dialstatus=='BUSY') $dialstatus = 'Busy';
        elseif($dialstatus=='CHANUNAVAIL') $dialstatus = 'NotAvaliable';
        elseif($dialstatus=='CONGESTION') $dialstatus = 'NotAvaliable';
        elseif($dialstatus=='NOANSWER') $dialstatus = 'NotAvaliable';
        else $dialstatus='NotAllowed';
      }

      $is_recorded = TRUE; //strlen(trim($this->agi->get_variable('MIXMONITOR_FILENAME', TRUE)))>0;

      $this->agi->verbose('Set recorded to '.($is_recorded?'true':'false'),4);

      $extdata['duration'] = $duration;
      $extdata['status'] = $dialstatus;
      $extdata['is_recorded'] = $is_recorded;
//      if(file_exists('/var/spool/asterisk/monitor/'.$uniqueid.'.mp3')?1:0) {
//      if('is_recorded') {
//        $data['record_link']=$localurl.'/recording/'.$uniqueid.'.mp3';
//      }
    }

    foreach($extid as $ext) {
      //detect external and internal phone numbers
      if($calltype=='in') {
        $clientnum = $callerid;
        $internalnum = $ext;
      } else {
        $internalnum = $callerid;
        $clientnum = $ext;
      }

      if($eventtype=='ACCEPTED') {
        if($ext!=$dialednum) {
          $this->agi->verbose('Cancel for '.$ext.' dialed for '.$dialednum,3);
          $data = array('type' => $calltype, 'event'=>'COMPLETED', 'phone' => $clientnum, 'diversion' => $trunknum, 'ext' => $internalnum, 'callid' => $uniqueid, 'duration' => 0, 'status' => 'Cancelled', 'is_recorded' => 0);
        } else {
          $this->agi->verbose('Answer for '.$ext,3);
          $data = array('type' => $calltype, 'event'=>$eventtype, 'phone' => $clientnum, 'diversion' => $trunknum, 'ext' => $internalnum, 'callid' => $uniqueid);
        }
      } else {
        if($eventtype=='COMPLETED') {
          $this->agi->verbose('Ext is '.$ext,3);
          if(($dialednum!='')&&($ext!=$dialednum)) $ext='';
        }
        $data = array('type' => $calltype, 'event'=>$eventtype, 'phone' => $clientnum, 'diversion' => $trunknum, 'ext' => $internalnum, 'callid' => $uniqueid)+$extdata;
      }

      if($ext!='') {
        $this->agi->verbose('Send '.$eventtype.' event as '.$calltype.' call. External phone '.$clientnum.', Internal phone '.$internalnum,3);
        if($eventtype == 'COMPLETED') {
          $this->agi->verbose('Completion status '.$dialstatus.' with duration '.$duration.' access from '.$localurl.'/recording/'.$uniqueid.'.mp3',3);
          $this->agi->verbose('Completion data '.http_build_query($data),4);
        }
        $answer = $this->sendTelAPIRequest('event', $data);
      }
    }
  }

  public function sendRecord() {
    $pfurl=rawurldecode($request_data->pfurl);
    $pftoken=rawurldecode($request_data->token);
    $localurl=rawurldecode($request_data->localurl);
    $uniqueid = $request_data->uuid;
    $record = $localurl.'/recording/'.$uniqueid.'.mp3';
    $answer = $this->sendTelAPIRequest('record', array('callid' => $uniqueid, 'record_link' => $record, 'is_temp' => 0));
    return $answer;
  }

  public function startRecord() {
    $login=self::getDB('integration/planfix','user');
    $passwd=self::getDB('integration/planfix','userpwd');
    $localurl=self::getDB('integration/planfix','localurl');
    $pfurl=self::getDB('integration/planfix','apiurl');
    $pftoken=self::getDB('integration/planfix','token');
    $uniqueid = $this->agi->get_variable('UNIQ', TRUE);
    if($uniqueid == '') {
      $uniqueid = $this->agi->get_variable('UNIQUEID', TRUE);
      $this->agi->set_variable('__UNIQ', $uniqueid);
    }
    $this->agi->exec('MixMonitor',array($uniqueid.'.wav','','/usr/bin/ast2mp3 '.$uniqueid.' '.$login.' '.$passwd.' '.rawurlencode($localurl)));
    $this->agi->verbose('Start record for call '.$uniqueid,3);
  }

  public function makeCall($from, $to) {
    $context=self::getDB('integration/planfix','context');
    $sipdomain=self::getDB('integration/planfix','domain');
    if(strpos($from,'@')!==FALSE) {
      $pos = strpos($from,'@');
      $sipdomain = substr($from, $pos+1);
      $from = substr($from, 0, $pos);
    }
    $to=self::mapCallerID($to);
    $this->ami->Originate('SIP/'.$from, $to, $context, 1, NULL, NULL, 30000, '"'.$from.'" <'.str_replace('.','*',$from).'>', array('SIPDOMAIN='.$sipdomain,'__SIPADDHEADER51=Alert-Info: <http://localhost/>;info=alert-autoanswer','__SIPADDHEADER52=Alert-Info: <http://localhost/>;answer-after=0','__SIPADDHEADER53=Call-Info: <sip:127.0.0.1>;answer-after=0') ,NULL,TRUE);
    return TRUE;
  }

  public static function getErrorMessage() {
    static $errorMap = [
        '0001' => 'Неверный API Key',
        '0002' => 'Приложение заблокировано',
        '0003' => 'Ошибка XML разбора. Некорректный XML',
        '0004' => 'Неизвестный аккаунт',
        '0005' => 'Ключ сессии недействителен (время жизни сессии истекло)',
        '0006' => 'Неверная подпись',
        '0007' => 'Превышен лимит использования ресурсов (ограничения, связанные с лицензиями или с количеством запросов)',
        '0008' => 'Неизвестное имя функции',
        '0009' => 'Отсутствует один из обязательных параметров функции',
        '0010' => 'Аккаунт заморожен',
        '0011' => 'На площадке аккаунта производится обновление программного обеспечения',
        '0012' => 'Отсутствует сессия, не передан параметр сессии в запрос',
        '0013' => 'Неопределенный пользователь',
        '0014' => 'Пользователь неактивен',
        '0015' => 'Недопустимое значение параметра',
        '0016' => 'В данном контексте параметр не может принимать переданное значение',
        '0017' => 'Отсутствует значение для зависящего параметра',
        '0018' => 'Функции/функционал не реализована',
        '0019' => 'Заданы конфликтующие между собой параметры',
        '0020' => 'Вызов функции запрещен',
        '0021' => 'Запрошенное количество объектов больше максимально разрешенного для данной функции',
        '0022' => 'Использование API недоступно для бесплатного аккаунта',
        '1001' => 'Неверный логин или пароль',
        '1002' => 'На выполнение данного запроса отсутствуют права (привилегии)',
        '2001' => 'Запрошенный проект не существует',
        '2002' => 'На выполнение данного запроса отсутствуют права (привилегии)',
        '2003' => 'Ошибка добавления проекта',
        '3001' => 'Указанная задача не существует',
        '3002' => 'Нет доступа к над задаче',
        '3003' => 'Проект, в рамках которого создается задача, не существует',
        '3004' => 'Проект, в рамках которого создается задача, не доступен',
        '3005' => 'Ошибка добавления задачи',
        '3006' => 'Время "Приступить к работе" не может быть больше времени "Закончить работу до"',
        '3007' => 'Неопределенная периодичность, скорее всего задано несколько узлов, которые конфликтуют друг с другом или не указан ни один',
        '3008' => 'Нет доступа к задаче',
        '3009' => 'Нет доступа на изменение данных задачи',
        '3010' => 'Данную задачу отклонить нельзя (скорее всего, она уже принята этим пользователем)',
        '3011' => 'Данную задачу принять нельзя (скорее всего, она уже принята этим пользователем)',
        '3012' => 'Пользователь, выполняющий запрос, не является исполнителем задачи',
        '3013' => 'Задача не принята (для выполнения данной функции задача должна быть принята)',
        '4001' => 'На выполнение данного запроса отсутствуют права (привилегии)',
        '4002' => 'Действие не существует',
        '4003' => 'Ошибка добавления действия',
        '4004' => 'Ошибка обновления данных',
        '4005' => 'Ошибка обновления данных',
        '4006' => 'Попытка изменить статус на недозволенный',
        '4007' => 'В данном действии запрещено менять статус',
        '4008' => 'Доступ к комментария/действию отсутствует',
        '4009' => 'Доступ к задаче отсутствует',
        '4010' => 'Указанная аналитика не существует',
        '4011' => 'Для аналитики были переданы не все поля',
        '4012' => 'Указан несуществующий параметр для аналитики',
        '4013' => 'Переданные данные не соответствуют типу поля',
        '4014' => 'Указанный ключ справочника нельзя использовать',
        '4015' => 'Указанный ключ справочника не существует',
        '4016' => 'Указанный ключ данных поля не принадлежит указанной аналитике',
        '5001' => 'Указанная группа пользователей не существует',
        '5002' => 'На выполнение данного запроса отсутствуют права (привилегии)',
        '5003' => 'Ошибка добавления',
        '6001' => 'На выполнение данного запроса отсутствуют права (привилегии)',
        '6002' => 'Данный e-mail уже используется',
        '6003' => 'Ошибка добавления сотрудника',
        '6004' => 'Пользователь не существует',
        '6005' => 'Ошибка обновления данных',
        '6006' => 'Указан идентификатор несуществующей группы пользователей',
        '7001' => 'На выполнение данного запроса отсутствуют права (привилегии)',
        '7002' => 'Клиент не существует',
        '7003' => 'Ошибка добавления клиента',
        '7004' => 'Ошибка обновления данных',
        '8001' => 'На выполнение данного запроса отсутствуют права (привилегии)',
        '8002' => 'Контакт не существует',
        '8003' => 'Ошибка добавления контакта',
        '8004' => 'Ошибка обновления данных',
        '8005' => 'Контакт не активировал доступ в ПланФикс',
        '8006' => 'Контакту не предоставлен доступ в ПланФикс',
        '8007' => 'E-mail, указанный для логина, не уникален',
        '8008' => 'Попытка установки пароля для контакта, не активировавшего доступ в ПланФикс',
        '8009' => 'Ошибка обновления данных для входа в систему',
        '9001' => 'На выполнение данного запроса отсутствуют права (привилегии)',
        '9002' => 'Запрашиваемый файл не существует',
        '9003' => 'Ошибка загрузки файла',
        '9004' => 'Попытка загрузить пустой список файлов',
        '9005' => 'Недопустимый символ в имени файла',
        '9006' => 'Имя файла не уникально',
        '9007' => 'Ошибка файловой системы',
        '9008' => 'Ошибка возникает при попытке добавить файл из проекта для проекта',
        '9009' => 'Файл, который пытаются добавить к задаче, является файлом другого проекта',
        '10001' => 'На выполнение данного запроса отсутствуют права (привилегии)',
        '10002' => 'Аналитика не существует',
        '10003' => 'Переданный параметр группы аналитики не существует',
        '10004' => 'Переданный параметр справочника аналитики не существует',
        '11001' => 'Указанной подписки не существует',
    ];
    if(isset(self::$planfixLastError)) {
      return $errorMap[self::$planfixLastError];
    }
    return 'Успешно';
  }

  public function getLastError() {
    return self::$planfixLastError;
  }

  private static function addElement(&$xml, $key, $value) {
     if(!is_numeric($key)) $xml->startElement($key);
     if(is_array($value)) {
       if(isset($value['@value'])) {
         if(isset($value['@attributes'])) {
           foreach($value['@attributes'] as $ekey => $evalue) {
             if($evalue!==NULL) {
               $xml->writeAttribute($ekey, $evalue);
             }
           }
         }
         if(is_array($value['@value'])) {
           foreach($value['@value'] as $ekey => $evalue) {
             if($evalue!==NULL) {
               self::addElement($xml, $ekey, $evalue);
             }
           }
         } else {
           self::addElement($xml, $key, $value['@value']);
         }
       } else {
         foreach($value as $ekey => $evalue) {
           if($evalue!==NULL) {
             self::addElement($xml, $ekey, $evalue);
           }
         }
       }
     } else {
       $xml->text($value);
     }
     if(!is_numeric($key)) $xml->endElement();
  }

  private static function getXmlData($data) {
     $xml = new \XMLWriter('request');
     $xml->openMemory();
     $xml->startDocument('1.0', 'UTF-8');
     $xml->setIndent(true);
     foreach($data as $key => $value) {
       if($value!==NULL) {
         self::addElement($xml, $key, $value);
       }
     }
     return $xml->outputMemory(true);
  }

  private static function getXmlElement($xml) {
    $result = null;
    while($xml->read())
        switch ($xml->nodeType) {
            case \XMLReader::END_ELEMENT: return $result;
            case \XMLReader::ELEMENT:
                $node = $xml->isEmptyElement ? '' : self::getXmlElement($xml);
                $nodename = $xml->name;
                if($xml->hasAttributes) {
                  $node = array('@value' => $node, '@attributes' => array());
                  while($xml->moveToNextAttribute()) {
                    $node['@attributes'][$xml->name] = $xml->value;
                  }
                }
                if(isset($result[$nodename])) {
                  if(is_array($result[$nodename])&&isset($result[$nodename][0])) {
                    $result[$nodename][] = $node;
                  } else {
                    $result[$nodename] = array($result[$nodename]);
                    $result[$nodename][] = $node;
                  }
                } else {
                  $result[$nodename] = $node;
                }
            break;
            case \XMLReader::TEXT:
            case \XMLReader::CDATA:
                $result .= $xml->value;
        }
    return $result;
  }

  private static function getXmlResult($data) {
     $result = null;
     $xml = new \XMLReader();;
     $xml->XML($data);
     $result = self::getXmlElement($xml);
     return $result;
  }

  private static function getStringFromDate($dt) {
    $result = false;
    if(is_object($dt)&&is_subclass_of($dt, 'DateTime')) {
      $result = $dt->format('d-m-Y');
    } elseif(preg_match('/([0-9]{1,2})[-.]([0-9]{1,2})[-.]([0-9]{4})/', $dt, $match)) {
      $result = sprintf("%02d-%02d-%04d", $match[1], $match[2], $match[3]);
    } elseif(preg_match('/([0-9]{4})[-\/]([0-9]{1,2})[-\/]([0-9]{1,2})/', $dt, $match)) {
      $result = sprintf("%02d-%02d-%04d", $match[2], $match[3], $match[1]);
    } elseif(is_string($dt)&&(strpos('@', $dt)===0)) {
      $value = new \DateTime($dt);
      $result = $dt->format('d-m-Y');
    }
    return $result;
  }

  private static function getStringFromDateTime($dt) {
    $result = false;
    if(is_object($dt)&&is_subclass_of($dt, 'DateTime')) {
      $result = $dt->format('d-m-Y h:i');
    } elseif(preg_match('/([0-9]{1,2})[-.]([0-9]{1,2})[-.]([0-9]{4})\s+([0-9]{1,2}):([0-9]{1,2})/', $dt, $match)) {
      $result = sprintf("%02d-%02d-%04d %02d:%02d", $match[1], $match[2], $match[3], $match[4], $match[5]);
    } elseif(preg_match('/([0-9]{4})[-\/]([0-9]{1,2})[-\/]([0-9]{1,2})\s+([0-9]{1,2}):([0-9]{1,2})/', $dt, $match)) {
      $result = sprintf("%02d-%02d-%04d %02d:%02d", $match[2], $match[3], $match[1], $match[4], $match[5]);
    } elseif(preg_match('/([0-9]{1,2})[-.]([0-9]{1,2})[-.]([0-9]{4})/', $dt, $match)) {
      $result = sprintf("%02d-%02d-%04d 00:00", $match[1], $match[2], $match[3]);
    } elseif(preg_match('/([0-9]{4})[-\/]([0-9]{1,2})[-\/]([0-9]{1,2})/', $dt, $match)) {
      $result = sprintf("%02d-%02d-%04d 00:00", $match[2], $match[3], $match[1]);
    } elseif(is_string($dt)&&(strpos('@', $dt)===0)) {
      $value = new \DateTime($dt);
      $result = $dt->format('d-m-Y h:i');
    }
    return $result;
  }

  private static function getStringFromBoolean($value) {
    $result = false;
    if($value===true) {
      $result = 1;
    } elseif($value===false) {
      $result = 0;
    } elseif(is_string($value)&&(strtolower($value) === 'true')) {
      $result = 1;
    } elseif(is_string($value)&&(strtolower($value) === 'false')) {
      $result = 0;
    } elseif($value == 1) {
      $result = 1;
    } elseif($value == 0) {
      $result = 0;
    }
    return $result;
  }

  public static function addTaskFilter(&$request, $type, $mode, $value) {
    if(!isset($request['req']['filters'])) $request['req']['filters']=array();
    $data = array();
    $filter = array('filter' => &$data);
    $compmode='';
    switch($type) {
      case 'createdby': {
         $data['type']=12;
      } break;
      case 'begins': {
         $data['type']=13;
      } break;
      case 'finish':
      case 'ends': {
         $data['type']=14;
      } break;
      case 'lastactive': {
         $data['type']=21;
      } break;
      case 'finished':
      case 'ended':
      case 'lastend': {
         $data['type']=19;
      } break;
      case 'lastrun': {
         $data['type']=20;
      } break;
      case 'lastchange': {
         $data['type']=38;
      } break;
      case 'lastcomment': {
         $data['type']=79;
      } break;
      case 'userdate': {
         $data['type']=103;
      } break;

      case 'addedby': {
         $data['type']=1;
      } break;
      case 'responseby': {
         $data['type']=2;
      } break;
      case 'joined': {
         $data['type']=39;
      } break;
      case 'auditby': {
         $data['type']=3;
      } break;
      case 'auditprojectby': {
         $data['type']=59;
      } break;
      case 'audittaskby': {
         $data['type']=60;
      } break;
      case 'hascontact': {
         $data['type']=108;
      } break;
      case 'hasuser': {
         $data['type']=109;
      } break;
      case 'hasusercontact': {
         $data['type']=112;
      } break;
      case 'haslist': {
         $data['type']=113;
      } break;

      case 'isnostart': {
         $data['type']=22;
      } break;
      case 'isnoend': {
         $data['type']=23;
      } break;
      case 'hasstart': {
         $data['type']=25;
      } break;
      case 'hasend': {
         $data['type']=26;
      } break;
      case 'isrepeated': {
         $data['type']=16;
      } break;
      case 'isonce': {
         $data['type']=28;
      } break;
      case 'discontinued':
      case 'isdiscontinued': {
         $data['type']=17;
      } break;
      case 'continue':
      case 'continues':
      case 'continued': {
         $data['type']=28; //!! TODO: Contact with planfix - possible duplcate of "isonce"
      } break;
      case 'withoutresponse':
      case 'withoutresponseible':
      case 'woresponse':
      case 'woresponseible': {
         $data['type']=33;
      } break;
      case 'withoutjoined':
      case 'withoutjoined':
      case 'wojoined':
      case 'wojoined': {
         $data['type']=41;
      } break;
      case 'addedbyuser': {
         $data['type']=34;
      } break;
      case 'addedbycontact': {
         $data['type']=35;
      } break;
      case 'responsebyuser': {
         $data['type']=71;
      } break;
      case 'responsebycontact': {
         $data['type']=69;
      } break;
      case 'joinedbyuser': {
         $data['type']=72;
      } break;
      case 'joinedbycontact': {
         $data['type']=70;
      } break;
      case 'issetuserfield': {
         $data['type']=152;
      } break;
      case 'isemptyuserfield':
      case 'isfreeuserfield': {
         $data['type']=153;
      } break;
      case 'hasanalytics': {
         $data['type']=11;
      } break;
      case 'hasnoanalytics': {
         $data['type']=18;
      } break;
      case 'isparent': {
         $data['type']=73;
      } break;

      case 'hastemplate': {
         $data['type']=51;
      } break;
      case 'hasstate': {
         $data['type']=10;
      } break;
      case 'hascontact': {
         $data['type']=7;
      } break;
      case 'hasprocess': {
         $data['type']=24;
      } break;

      case 'name': {
         $data['type']=8;
      } break;
      case 'userstring': {
         $data['type']=101;
      } break;

      case 'usernum':
      case 'usernumber': {
         $data['type']=102;
      } break;

      case 'checked': {
         $data['type']=105;
      } break;

      case 'inlist': {
         $data['type']=106;
      } break;

      case 'indict':
      case 'indictionary': {
         if(is_array($value)) {
           $data['type']=114;
         } else {
           $data['type']=107;
         }
      } break;

      default: {
        $data['type']=$type;
      } break;
    }
    switch($compmode) {
      case 'dt': {
        switch($mode) {
          case 'equal':
          case 'eq': {
            $data['operator']='equal';
          } break;
          case 'notequal':
          case 'neq':
          case 'ne': {
            $data['operator']='notequal';
          } break;
          case 'greater':
          case 'gt': {
            $data['operator']='gt';
          } break;
          case 'lighter':
          case 'lt': {
            $data['operator']='lt';
          } break;
          default: {
            $data['operator']=$mode;
          }
        }
        if(is_string($value)&&in_array($value, array('today', 'yesterday', 'tomorrow', 'thisweek', 'lastweek', 'nextweek', 'thismonth', 'lastmonth', 'nextmonth'))) {
          $data['value']=array('datetype' => $value);
        } elseif(is_array($value)) {
          if(isset($value['mode'])&&is_string($value['mode'])&&in_array($value['mode'], array('today', 'yesterday', 'tomorrow', 'thisweek', 'lastweek', 'nextweek', 'thismonth', 'lastmonth', 'nextmonth'))) {
            $data['value']=array('datetype' => $value['mode']);
          } elseif(isset($value['value'])&&isset($value['mode'])&&is_string($value['mode'])&&in_array($value['mode'], array('last', 'next', 'in'))) {
            $data['value']=array('datetype' => $value['mode'], 'datevalue' => self::getStringFromDate($value['value']));
          } elseif(isset($value['value'])&&isset($value['mode'])&&is_string($value['mode'])&&in_array($value['mode'], array('anotherdate'))) {
            $data['value']=array('datetype' => $value['mode'], 'datefrom' => self::getStringFromDate($value['value']));
          } elseif(isset($value['value'])&&is_array($value['value'])&&isset($value['value']['from'])&&isset($value['value']['to'])&&isset($value['mode'])&&is_string($value['mode'])&&in_array($value['mode'], array('anotherperiod'))) {
            $data['value']=array('datetype' => $value['mode'], 'datefrom' => self::getStringFromDate($value['value']['from']), 'dateto' => self::getStringFromDate($value['value']['to']));
          } elseif(isset($value['from'])&&isset($value['to'])&&isset($value['mode'])&&is_string($value['mode'])&&in_array($value['mode'], array('anotherperiod'))) {
            $data['value']=array('datetype' => $value['mode'], 'datefrom' => self::getStringFromDate($value['from']), 'dateto' => self::getStringFromDate($value['to']));
          }
        } else {
          $data['value']=array('datetype' => 'anotherdate', 'datevalue' => self::getStringFromDate($value));
        }
      } break;
      case 'intid': {
        switch($mode) {
          case 'equal':
          case 'eq': {
            $data['operator']='equal';
          } break;
          case 'notequal':
          case 'neq':
          case 'ne': {
            $data['operator']='notequal';
          } break;
          default: {
            $data['operator']=$mode;
          }
        }
        $data['value']=(int)$value;
      } break;
      case 'int': {
        switch($mode) {
          case 'equal':
          case 'eq': {
            $data['operator']='equal';
          } break;
          case 'notequal':
          case 'neq':
          case 'ne': {
            $data['operator']='notequal';
          } break;
          case 'greater':
          case 'gt': {
            $data['operator']='gt';
          } break;
          case 'lighter':
          case 'lt': {
            $data['operator']='lt';
          } break;
          default: {
            $data['operator']=$mode;
          }
        }
        $data['value']=(int)$value;
      } break;
      case 'bool': {
        switch($mode) {
          case 'equal':
          case 'eq': {
            $data['operator']='equal';
          } break;
          default: {
            $data['operator']=$mode;
          }
        }
        $data['value']=self::getStringFromBoolean($value);
      } break;
      case 'checkbox': {
        switch($mode) {
          case 'equal':
          case 'eq': {
            $data['operator']='equal';
          } break;
          case 'notequal':
          case 'neq':
          case 'ne': {
            $data['operator']='notequal';
          } break;
          default: {
            $data['operator']=$mode;
          }
        }
        $data['value']=self::getStringFromBoolean($value);
      } break;
      case 'string': {
        switch($mode) {
          case 'equal':
          case 'eq': {
            $data['operator']='equal';
          } break;
          case 'notequal':
          case 'neq':
          case 'ne': {
            $data['operator']='notequal';
          } break;
          default: {
            $data['operator']=$mode;
          }
        }
        $data['value']=(string)$value;
      } break;
      default: {
        $data['operator']=$mode;
        $data['value']=$value;
      } break;
    }
    $request['req']['filters'][]=&$filter;
  }

  public static function addContactFilter(&$request, $type, $mode, $value) {
    if(!isset($request['req']['filters'])) $request['req']['filters']=array();
    $data = array();
    $filter = array('filter' => &$data);
    $compmode='';
    switch($type) {
      case 'dtcreate': {
         $data['type']=12;
         $compmode='dt';
      } break;
      case 'dtbirth': {
         $data['type']=4011;
         $compmode='dt';
      } break;
      case 'dtwlastactivity': {
         $data['type']=4213;
         $compmode='dt';
      } break;
      case 'dtwolastactivity': {
         $data['type']=4219;
         $compmode='dt';
      } break;
      case 'dtjwlastactivity': {
         $data['type']=4214;
         $compmode='dt';
      } break;
      case 'dtjwolastactivity': {
         $data['type']=4220;
         $compmode='dt';
      } break;
      case 'dtuser': {
         $data['type']=4103;
         $compmode='dt';
      } break;

      case 'addedby': {
         $data['type']=1;
         $compmode='intid';
      } break;
      case 'responseby': {
         $data['type']=2;
         $compmode='intid';
      } break;
      case 'hasread': {
         $data['type']=47;
         $compmode='intid';
      } break;
      case 'haswrite': {
         $data['type']=48;
         $compmode='intid';
      } break;
      case 'hascontact': {
         $data['type']=4108;
         $compmode='intid';
      } break;
      case 'hasuser': {
         $data['type']=4109;
         $compmode='intid';
      } break;
      case 'hasusercontact': {
         $data['type']=4112;
         $compmode='intid';
      } break;
      case 'haslist': {
         $data['type']=4113;
         $compmode='intid';
      } break;

      case 'iscompany': {
         $data['type']=4006;
         $compmode='bool';
      } break;
      case 'iscontact': {
         $data['type']=4007;
         $compmode='bool';
      } break;
      case 'hasaccess': {
         $data['type']=4010;
         $compmode='bool';
      } break;
      case 'allowtasks': {
         $data['type']=4012;
         $compmode='bool';
      } break;
      case 'denytasks': {
         $data['type']=4017;
         $compmode='bool';
      } break;
      case 'allowviewtasks': {
         $data['type']=4013;
         $compmode='bool';
      } break;
      case 'denyviewtasks': {
         $data['type']=4018;
         $compmode='bool';
      } break;

      case 'name': {
         $data['type']=4001;
         $compmode='string';
      } break;
      case 'title': {
         $data['type']=4002;
         $compmode='string';
      } break;
      case 'phone': {
         $data['type']=4003;
         $compmode='string';
      } break;
      case 'address': {
         $data['type']=4004;
         $compmode='string';
      } break;
      case 'email': {
         $data['type']=4005;
         $compmode='string';
      } break;
      case 'userstring': {
         $data['type']=4101;
         $compmode='string';
      } break;

      case 'usernum':
      case 'usernumber': {
         $data['type']=4102;
         $compmode='int';
      } break;

      case 'checked': {
         $data['type']=4105;
         $compmode='checkbox';
      } break;

      case 'inlist': {
         $data['type']=4106;
         $compmode='string';
      } break;

      case 'indict':
      case 'indictionary': {
         $data['type']=4107;
         $compmode='intid';
      } break;
      case 'ingroup': {
         $data['type']=4008;
         $compmode='intid';
      } break;

      default: {
        $data['type']=$type;
      } break;
    }
    switch($compmode) {
      case 'dt': {
        switch($mode) {
          case 'equal':
          case 'eq': {
            $data['operator']='equal';
          } break;
          case 'notequal':
          case 'neq':
          case 'ne': {
            $data['operator']='notequal';
          } break;
          case 'greater':
          case 'gt': {
            $data['operator']='gt';
          } break;
          case 'lighter':
          case 'lt': {
            $data['operator']='lt';
          } break;
          default: {
            $data['operator']=$mode;
          }
        }
        if(is_string($value)&&in_array($value, array('today', 'yesterday', 'tomorrow', 'thisweek', 'lastweek', 'nextweek', 'thismonth', 'lastmonth', 'nextmonth'))) {
          $data['value']=array('datetype' => $value);
        } elseif(is_array($value)) {
          if(isset($value['mode'])&&is_string($value['mode'])&&in_array($value['mode'], array('today', 'yesterday', 'tomorrow', 'thisweek', 'lastweek', 'nextweek', 'thismonth', 'lastmonth', 'nextmonth'))) {
            $data['value']=array('datetype' => $value['mode']);
          } elseif(isset($value['value'])&&isset($value['mode'])&&is_string($value['mode'])&&in_array($value['mode'], array('last', 'next', 'in'))) {
            $data['value']=array('datetype' => $value['mode'], 'datevalue' => self::getStringFromDate($value['value']));
          } elseif(isset($value['value'])&&isset($value['mode'])&&is_string($value['mode'])&&in_array($value['mode'], array('anotherdate'))) {
            $data['value']=array('datetype' => $value['mode'], 'datefrom' => self::getStringFromDate($value['value']));
          } elseif(isset($value['value'])&&is_array($value['value'])&&isset($value['value']['from'])&&isset($value['value']['to'])&&isset($value['mode'])&&is_string($value['mode'])&&in_array($value['mode'], array('anotherperiod'))) {
            $data['value']=array('datetype' => $value['mode'], 'datefrom' => self::getStringFromDate($value['value']['from']), 'dateto' => self::getStringFromDate($value['value']['to']));
          } elseif(isset($value['from'])&&isset($value['to'])&&isset($value['mode'])&&is_string($value['mode'])&&in_array($value['mode'], array('anotherperiod'))) {
            $data['value']=array('datetype' => $value['mode'], 'datefrom' => self::getStringFromDate($value['from']), 'dateto' => self::getStringFromDate($value['to']));
          }
        } else {
          $data['value']=array('datetype' => 'anotherdate', 'datevalue' => self::getStringFromDate($value));
        }
      } break;
      case 'intid': {
        switch($mode) {
          case 'equal':
          case 'eq': {
            $data['operator']='equal';
          } break;
          case 'notequal':
          case 'neq':
          case 'ne': {
            $data['operator']='notequal';
          } break;
          default: {
            $data['operator']=$mode;
          }
        }
        $data['value']=(int)$value;
      } break;
      case 'int': {
        switch($mode) {
          case 'equal':
          case 'eq': {
            $data['operator']='equal';
          } break;
          case 'notequal':
          case 'neq':
          case 'ne': {
            $data['operator']='notequal';
          } break;
          case 'greater':
          case 'gt': {
            $data['operator']='gt';
          } break;
          case 'lighter':
          case 'lt': {
            $data['operator']='lt';
          } break;
          default: {
            $data['operator']=$mode;
          }
        }
        $data['value']=(int)$value;
      } break;
      case 'bool': {
        switch($mode) {
          case 'equal':
          case 'eq': {
            $data['operator']='equal';
          } break;
          default: {
            $data['operator']=$mode;
          }
        }
        $data['value']=self::getStringFromBoolean($value);
      } break;
      case 'checkbox': {
        switch($mode) {
          case 'equal':
          case 'eq': {
            $data['operator']='equal';
          } break;
          case 'notequal':
          case 'neq':
          case 'ne': {
            $data['operator']='notequal';
          } break;
          default: {
            $data['operator']=$mode;
          }
        }
        $data['value']=self::getStringFromBoolean($value);
      } break;
      case 'string': {
        switch($mode) {
          case 'equal':
          case 'eq': {
            $data['operator']='equal';
          } break;
          case 'notequal':
          case 'neq':
          case 'ne': {
            $data['operator']='notequal';
          } break;
          default: {
            $data['operator']=$mode;
          }
        }
        $data['value']=(string)$value;
      } break;
      default: {
        $data['operator']=$mode;
        $data['value']=$value;
      } break;
    }
    $request['req']['filters'][]=&$filter;
  }

  public static function requestContactGroupList() {
    return array('command' => 'contact.getGroupList', 'req' => array());
  }

  public static function extractContactGroupList($response) {
    if(is_array($response)) {
      $result=array();
      if(isset($response['contactGroups']['@value'])) {
        if(isset($response['contactGroups']['@value']['group'][0])) {
          foreach($response['contactGroups']['@value']['group'] as $group) {
            $result[$group['name']]=$group['id'];
          }
        } else {
          $result[$response['contactGroups']['@value']['group']['name']]=$response['contactGroups']['@value']['group']['id'];
        }
      }
      return $result;
    }
    return false;
  }

  public static function requestContactFilterList() {
    return array('command' => 'contact.getFilterList', 'req' => array());
  }

  public static function extractContactFilterList($response) {
    if(is_array($response)) {
      $result=array();
      if(isset($response['contactFilterList']['@value'])) {
        if(isset($response['contactFilterList']['@value']['contactFilter'][0])) {
          foreach($response['contactFilterList']['@value']['contactFilter'] as $filter) {
            $result[$filter['Name']]=$filter['ID'];
          }
        } else {
          $result[$response['contactFilterList']['@value']['contactFilter']['Name']]=$response['contactFilterList']['@value']['contactFilter']['ID'];
        }
      }
      return $result;
    }
    return false;
  }

  public static function requestContactUpdate(int $id = 0, array $fields, bool $silent = true) {
    $fields=array('id' => $id)+$fields;
    $request=array('silent' => ($silent?1:0), 'contact' => $fields);
    return array('command' => 'contact.update', 'req' => $request);
  }

  public static function requestContactList(string $search = null, int $company=null, int $count = 100, int $page = 1) {
    if($count>100) $count=100;
    $request=array('pageCurrent' => $page, 'pageSize' => $count, 'target' => 'contact');
    if(isset($search)) $request['search'] = $search;
    if(isset($company)) $request['company'] = $search;
    return array('command' => 'contact.getList', 'req' => $request);
  }

  public static function requestContactCompanyList(string $search = null, int $count = 100, int $page = 1) {
    if($count>100) $count=100;
    $request=array('pageCurrent' => $page, 'pageSize' => $count, 'target' => 'company');
    if(isset($search)) $request['search'] = $search;
    return array('command' => 'contact.getList', 'req' => $request);
  }

  public static function requestContactTemplateList(string $search = null, int $count = 100, int $page = 1) {
    if($count>100) $count=100;
    $request=array('pageCurrent' => $page, 'pageSize' => $count, 'target' => 'template');
    if(isset($search)) $request['search'] = $search;
    return array('command' => 'contact.getList', 'req' => $request);
  }

  public static function requestContactFilter(int $filter = 0, string $search = null, int $count = 100, int $page = 1) {
    if($count>100) $count=100;
    $request=array('pageCurrent' => $page, 'pageSize' => $count, 'target' => $filter);
    if(isset($search)) $request['search'] = $search;
    return array('command' => 'contact.getList', 'req' => $request);
  }

  public static function requestNext(array &$req) {
    if(isset($req['req'])&&isset($req['req']['pageCurrent'])) {
      $req['req']['pageCurrent']+=1;
      return true;
    }
    return false;
  }

  public function sendAPIRequest(array $data) {
    self::$planfixLastError = NULL;
    $planfixapi='https://apiru.planfix.ru/xml';

    $planfixuri=self::getDB('integration/planfix','apiurl');
    $planfixkey=self::getDB('integration/planfix','xmlkey');
    $planfixtoken=self::getDB('integration/planfix','xmltoken');
    preg_match('/([a-z0-9_\-]+).planfix.ru/', $planfixuri, $match);
    $data['account']=$match[1];
    $auth = base64_encode("$planfixkey:$planfixtoken");
    $data['req']=array('account' => $data['account'])+$data['req'];
    $req = self::getXmlData(array('request' => array('@value' => $data['req'], '@attributes' => array('method' => $data['command']))));
    $options = array(
            'http' => array(
                    'header'  => "Content-Type: application/xml\r\n"
                                ."Accept: application/xml\r\n"
                                ."Content-Length: " . strlen($req) . "\r\n"
                                ."Authorization: Basic ".$auth."\r\n",
                    'method'  => 'POST',
                    'content' => $req
            )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($planfixapi, false, $context);
    if ($result === FALSE) { return FALSE; }
    $result=self::getXmlResult($result);
    if($result['response']['@attributes']['status']=='error') {
      self::$planfixLastError = $result['response']['@value']['code'];
      return false;
    }
    return $result['response']['@value'];
  }

}
?>
