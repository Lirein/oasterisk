<?php

namespace scheduler;

class Schedule extends \module\Subject {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \scheduler\Schedules $collection
   */
  static $collection = 'scheduler\\Schedules';

  /**
   * Параметры планировщика:
   * title - заголовок
   * enabled - признак активно/отключено
   * start - режим запуска: manual, once, daily, periodic, odd, even
   * startat - указывает время запуска
   * startby - указывает перечень дней недели или периоды когда возможен запуск цикла расписания
   * repeat - режим повтора: true, false
   * repeatby - указывает период/+задержку повтора в секундах
   *            (по окончанию = 0, с момента запуска = number, с задержкой = +number)
   * repeatfor - общая длительность с момента старта или количество запусков
   *            (без ограничений = 0, с момента старта = number, количество зпусков = +number)
   * stop - режим останова: manual, once
   * stopat - указывает дату/время прекращения планирования задания
   * action - действие которое нужно запустить - контекст диалплана
   * trigger - класс запускаемого триггера
   * destination - опционально - абонент назначения исходящего вызова
   * variables - набор переменных вида ключ=значение
   * 
   * @var string $defaultparams
   */
  private static $defaultparams = '{
    "title": "",
    "enabled": false,
    "start": "manual",
    "startat": "",
    "startby": "",
    "repeat": false,
    "repeatby": "",
    "repeatfor": "",
    "stop": "manual",
    "stopat": "",
    "action": "",
    "trigger": "core\\\schedulerInternalTrigger",
    "destination": "",
    "context": "",
    "variables": [{"value": "", "id": ""}],
    "cancels": [""],
    "finish": ""
  }';

  /**
   * Конструктор с идентификатором - инициализирует модель
   */
  public function __construct(string $id = null) {
    parent::__construct();
    $this->data = \config\DB::readDataItem('schedules', 'id', $id, self::$defaultparams);
    if($this->data != null) {
      $variables = array();
      foreach($this->data->variables as $variable) {
        $variables[$variable->id] = $variable->value;
      }
      $this->data->variables = $variables;
      $this->old_id = $id;
    } else {
      $this->data = json_decode(self::$defaultparams);
      $this->data->variables = array();
    }
    if(!$this->data->title) $this->data->title = $id;
    $this->id = $id;
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    unset($this->data);
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property) {
    if($property=='id') return $this->id;
    if($property=='old_id') return $this->old_id;
    if($property=='startby') {
      if($this->data->start == 'periodic') return $this->data->startby;
      return explode('&', $this->data->startby);
    }
    $defvals = json_decode(self::$defaultparams);
    if(isset($this->data->$property)) {
      if(is_bool($defvals->$property)) return ($this->data->$property==='1');
      else return $this->data->$property;
    }
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    if($property=='id') {
      if($this->id == $this->data->title) {
        $this->data->title = $value;
      }
      $this->id = $value;
      return true;
    } 
    if($property=='title') {
      $this->data->title = $value;
      return true;
    }
    if($property=='startby') {
      if(is_array($value)) {
        $this->data->startby = implode('&', $value);
      } else {
        $this->data->startby = $value;
      }
      return true;
    } 
    if($property=='variables') {
      $this->data->variables = array();
      if (is_array($value) && count($value)) {
        foreach($value as $variable) {
          if(!is_numeric($variable->key)) $this->data->variables[$variable->key] = $variable->value;
        }
      }
      return true;
    } 
    $defvals = json_decode(self::$defaultparams);
    if(isset($this->data->$property)) {
      if(is_bool($defvals->$property)) $this->data->$property = (($value===true)||($value==='true')||($value===1))?1:0; 
      else $this->data->$property = $value;
      return true;
    }
    return false;
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save() {
    $this->lock('schedules');
    if (!$this->id) $this->id = (new self::$collection())->newID();
    $sectionname = $this->id;

    if($this->old_id!==null) {
      if($this->id!=$this->old_id) {
        Schedules::rename($this);
        $oldname = $this->old_id;
        $olddata = \config\DB::readDataItem('schedules', 'id', $oldname, self::$defaultparams);
        \config\DB::deleteDataItem('schedules', 'id', $oldname, self::$defaultparams);
        \config\DB::writeDataItem('schedules', 'id', $sectionname, self::$defaultparams, $olddata);
      } else {
        Schedules::change($this);
      }
    } else { //Создаем расписание
      Schedules::add($this);
    }
    $needreschedule = false;
    $olddata = \config\DB::readDataItem('schedules', 'id', $sectionname, self::$defaultparams);
    if(($olddata)&&(($olddata->enabled!=$this->data->enabled)||($olddata->start!=$this->data->start))) $needreschedule = true;
    $olddata = clone $this->data;
    $variables = array();
    foreach($olddata->variables as $key => $variable) {
      $variables[] = (object)array('value' => $variable, 'id' => $key);
    }
    $olddata->variables = $variables;
    \config\DB::writeDataItem('schedules', 'id', $sectionname, self::$defaultparams, $olddata);
    $this->old_id = $this->id;
    if($needreschedule) {
      if($this->data->enabled&&($this->data->start!='manual')) {
        $this->start();
      } else {
        $this->stop();
      }
    }
    $this->unlock('schedules');
    return true;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    $subjectid = $this->old_id;
    $result = \config\DB::deleteDataItem('schedules', 'id', $subjectid, self::$defaultparams);
    Schedules::remove($this);
    return $result;
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    if(($this->data->start !== 'manual') && $this->data->enabled) {
      $this->start();
    }
    return false;
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast() {
    $keys = array();
    $keys['id'] = $this->id;
    $keys['old_id'] = $this->old_id;
    foreach($this->data as $key => $value) {
      $keys[$key] = $this->__get($key);
    }
    return (object)$keys;
  }

  /**
   * Возвращает время следующего запуска по расписанию
   *
   * @param \DateTime $fromtime Дата и время от которой осуществлять поиск следующей даты
   * @return \DateTime Дата/Время следующего запуска расписания
   */
  public function getStartTime(\DateTime $fromtime = null) {
    if($fromtime) {
      $now = clone $fromtime;
    } else {
      $now = new \DateTime();
    }
    $now->setTimezone(new \DateTimeZone('GMT'));
    $starttime = clone $now;
    if($this->data->start!='manual') { //Дата-время в формате ISO YYYY-MM-DDThh:mm:ss±hhmm
      if(preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})([+-][0-9]{4})/', $this->data->startat, $match)) {
        $starttime->setTimezone(new \DateTimeZone($match[7]));
        $now->setTimezone(new \DateTimeZone($match[7]));
        $starttime->setDate($match[1], $match[2], $match[3]);
        $starttime->setTime($match[4], $match[5], $match[6]);
      } else {
        return false;
      }
    }
    switch($this->data->start) {
      case 'manual': {
      } break;
      case 'once': {
        $diff = $starttime->getTimestamp() - $now->getTimestamp();
        if($diff<0) return false;
      } break;
      case 'daily': {
        $diff = $starttime->getTimestamp() - $now->getTimestamp();
        if($diff<0) {
          $hour = $starttime->format('H');
          $minute = $starttime->format('i');
          $second = $starttime->format('s');
          $starttime->setTimestamp($now->getTimestamp());
          $starttime->setTime($hour, $minute, $second);
          $diff = $starttime->getTimestamp() - $now->getTimestamp();
          if($diff<0) {
            $starttime->add(new \DateInterval('P1D'));
          }
        }
        if($this->data->startby!='') {
          $days = explode('&', $this->data->startby);
          $i = 0;
          while(!in_array(strtolower($starttime->format('D')), $days)) {
            if($i++>7) return false;
            $starttime->add(new \DateInterval('P1D'));
          }
        }
      } break;
      case 'periodic': { //К примеру X/Y сутки через трое - 1/3
        $period = explode('/', $this->data->startby);
        if(count($period)!=2) return false;
        while($starttime->getTimestamp() < $now->getTimestamp()) {
          $found = false;
          for($i = 0; $i < $period[0]; $i++) {//check X/ days
            if($starttime->getTimestamp() >= $now->getTimestamp()) {
              $found = true;
              break;
            }
            $starttime->add(new \DateInterval('P1D'));
          }
          if($found) break;
          for($i = 0; $i < $period[1]; $i++) { //skip /Y days
            $starttime->add(new \DateInterval('P1D'));
          }         
        }
      } break;
      case 'odd': {//Только нечетные дни
        $diff = $starttime->getTimestamp() - $now->getTimestamp();
        if($diff<0) {
          $hour = $starttime->format('H');
          $minute = $starttime->format('i');
          $second = $starttime->format('s');
          $starttime->setTimestamp($now->getTimestamp());
          $starttime->setTime($hour, $minute, $second);
          $diff = $starttime->getTimestamp() - $now->getTimestamp();
          if($diff<0) {
            $starttime->add(new \DateInterval('P1D'));
          }
        }
        //Если день четный - добавить 1 день
        if(($starttime->format('d') % 2)===0) $starttime->add(new \DateInterval('P1D'));
        if($this->data->startby!='') { //Если заданы дни недели
          $days = explode('&', $this->data->startby);
          $i = 0;
          while(!(in_array(strtolower($starttime->format('D')), $days)&&(($starttime->format('d') % 2)===1))) {
            if($i++>28) return false; //Максимум может быть четыре периода, если мы находимся на стыке двух месяцев
            $starttime->add(new \DateInterval('P1D'));
          }
        }
      } break;
      case 'even': {//Только четные дни
        $diff = $starttime->getTimestamp() - $now->getTimestamp();
        if($diff<0) {
          $hour = $starttime->format('H');
          $minute = $starttime->format('i');
          $second = $starttime->format('s');
          $starttime->setTimestamp($now->getTimestamp());
          $starttime->setTime($hour, $minute, $second);
          $diff = $starttime->getTimestamp() - $now->getTimestamp();
          if($diff<0) {
            $starttime->add(new \DateInterval('P1D'));
          }
        }
        //Если день нечетный - добавить 1 день
        if(($starttime->format('d') % 2)===1) $starttime->add(new \DateInterval('P1D'));
        if(($starttime->format('d') % 2)===1) $starttime->add(new \DateInterval('P1D'));
        if($this->data->startby!='') { //Если заданы дни недели
          $days = explode('&', $this->data->startby);
          $i = 0;
          while(!(in_array(strtolower($starttime->format('D')), $days)&&(($starttime->format('d') % 2)===0))) {
            if($i++>28) return false; //Максимум может быть четыре периода, если мы находимся на стыке двух месяцев
            $starttime->add(new \DateInterval('P1D'));
          }
        }
      } break;
      default: {
        return false;
      } break;
    }
    return $starttime;
  }

  private function mapvars($key, $value) {
    return $key.'='.$value;
  }

  /**
   * Возвращает список дополнительных переменных, которые модифицирует или добавляет триггер
   *
   * @return string[]
   */
  private function getTriggeredVariables() {
    $trigger = getModuleByClass($this->data->trigger); //find trigger class ScheduleTrigger
    if($trigger&&($trigger instanceof Trigger)) { //Запускаем триггер чтобы получить основные параметры планируемого вызова
      return $trigger->vars(); 
    } else {
      return array();
    }
  }

  private function getTriggeredData($operator = '') {
    $triggered_data = null;
    if($this->data->context=='') return false;
    $trigger = getModuleByClass($this->data->trigger); //find trigger class ScheduleTrigger
    if($trigger&&($trigger instanceof Trigger)) { //Запускаем триггер чтобы получить основные параметры планируемого вызова
      $triggered_data = @$trigger->start($this->data->variables); 
    } else {
      return false;
    }
    if(!$triggered_data) {
      if($this->data->destination) {
        $triggered_data = new \stdClass();
        $triggered_data->variables = $this->data->variables;
        $triggered_data->destination = '';
      }
      return false;
    }
    if(empty($triggered_data->destination)) {
      $triggered_data->destination = substr($this->data->destination, strrpos($this->data->destination, '/')+1);
      if(strpos($triggered_data->destination, '@')!==false) $triggered_data->destination = substr($triggered_data->destination, 0, strpos($triggered_data->destination, '@'));
    }
    if(empty($triggered_data->variables['CIDNUM'])||empty($triggered_data->variables['CIDNAME'])) {
      // $users = self::getAsteriskPeers(); //TODO: Коллекция абонентов и триггер на изменение
      // $usernum = $triggered_data->destination;
      // foreach($users as $user) {
      //   if($user->mode=='peer') {
      //     if($usernum==$user->login) {
      //       if(empty($triggered_data->variables['CIDNUM'])) $triggered_data->variables['CIDNUM'] = $user->number;
      //       if(empty($triggered_data->variables['CIDNAME'])) $triggered_data->variables['CIDNAME'] = $user->name;
      //       break;
      //     } else
      //     if($usernum==$user->number) {
      //       if(empty($triggered_data->variables['CIDNUM'])) $triggered_data->variables['CIDNUM'] = $user->number;
      //       if(empty($triggered_data->variables['CIDNAME'])) $triggered_data->variables['CIDNAME'] = $user->name;
      //       break;
      //     }
      //   }
      // } 
    }
    if(!isset($triggered_data->retries)) {
      $triggered_data->retries = 0; //По умолчанию без повторных попыток
    }
    if(!isset($triggered_data->retrydelay)) {
      $triggered_data->retrydelay = 10; //Задержка между попытками вызова 10 секунд
    }
    if(!isset($triggered_data->calltimeout)) {
      $triggered_data->calltimeout = 30; //Звоним 30 секунд
    }
    if(!isset($triggered_data->callerid)) {
      $triggered_data->callerid = 'OAS´terisk <0>'; //Звоним 30 секунд
    }
    if($operator) {
      $triggered_data->variables['OPERATOR'] = $operator;
    }
    $triggered_data->variables['SCHEDULE'] = $this->id;
    return $triggered_data;
  }

  private function makeCall(\DateTime $starttime, $triggered_data) {
    $return_value = 255;
    $triggered_data->variables['DESTINATION'] = $triggered_data->destination;
    $triggered_data->variables['RETRIES'] = $triggered_data->retries;
    $triggered_data->variables['RETRYDELAY'] = $triggered_data->retrydelay;
    $triggered_data->variables['CALLTIMEOUT'] = $triggered_data->calltimeout;
    if(strpos($triggered_data->destination, '/')===false) {
      $triggered_data->destination = 'Local/call@scheduler/n';
    }
    if(isset($triggered_data->application)) {
      if(!isset($triggered_data->applicationdata)) $triggered_data->applicationdata = '';
      $parameters = array('Channel' => $triggered_data->destination, 'Application' => $triggered_data->application, 'Data' => $triggered_data->applicationdata, 'Set' => array_map(array($this, 'mapvars'), array_keys($triggered_data->variables), array_values($triggered_data->variables)), 'CallerID' => $triggered_data->callerid, 'MaxRetries' => $triggered_data->retries, 'RetryTime' => $triggered_data->retrydelay, 'WaitTime' => $triggered_data->calltimeout);
    } else {
      $parameters = array('Channel' => $triggered_data->destination, 'Context' => 'scheduler', 'Extension' => 's', 'Priority' => 1, 'Set' => array_map(array($this, 'mapvars'), array_keys($triggered_data->variables), array_values($triggered_data->variables)), 'CallerID' => $triggered_data->callerid, 'MaxRetries' => $triggered_data->retries, 'RetryTime' => $triggered_data->retrydelay, 'WaitTime' => $triggered_data->calltimeout);
    }

    $descriptorspec = array(
      0 => array("pipe", "r"),
      1 => array("pipe", "w"),
      2 => array("pipe", "w")
    );

    $cwd = dirname(dirname(dirname(__FILE__)));
    $env = array('data' => json_encode($parameters), 'schedule' => $starttime->getTimestamp());

    $process = proc_open($cwd.'/.call', $descriptorspec, $pipes, $cwd, $env);

    if(is_resource($process)) {
      fclose($pipes[0]);

      $log = stream_get_contents($pipes[1]);
      if($log!='') {
        if(isset($this->agi)) {
          $this->agi->log('WARNING', $log);
        } else {
          error_log('Error in call: '.$log);
        }
      }
      fclose($pipes[1]);
      fclose($pipes[2]);

      $return_value = proc_close($process);
    }
    return $return_value;
  }

  public function enable() {
    if(!$this->data->enabled) {
      $this->data->enabled = true;
      $this->save();
    }
  }

  public function disable() {
    if($this->data->enabled) {
      $this->data->enabled = false;
      $this->save();
    } else {
      $this->stop();
    }
  }

  /**
   * Устанавливает вызов события по расписанию на ближайшую дату по отношению к текущей,
   * отменяет предыдущее запланированное расписание.
   *
   * @param string $operator Опциональный параметр оператора, для каждого оператора может быть добавлен свой планировщик
   * @return bool Возвращает истину в случае успешной операции планирования вызова
   */
  public function start($operator = '', \DateTime $fromtime = null) {
    $this->stop($operator);
    $starttime = $this->getStartTime($fromtime);
    if(!$starttime) return false;
    if($this->data->stop=='once') {
      $stoptime = new \DateTime();
      $stoptime->setTimezone(new \DateTimeZone('GMT')); 
      if(preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})([+-][0-9]{4})/', $this->data->stopat, $match)) {
        $stoptime->setTimezone(new \DateTimeZone($match[7]));
        $stoptime->setDate($match[1], $match[2], $match[3]);
        $stoptime->setTime($match[4], $match[5], $match[6]);
      } else {
        return false;
      }
      if($starttime->getTimestamp()>$stoptime->getTimestamp()) {
        if($this->data->enabled) {
          $this->data->enabled = false;
          $this->save();
        }
        return false;
      }
    }

    $triggered_data = $this->getTriggeredData($operator);
    if(!$triggered_data) return false;

    $triggered_data->variables['STARTTIME'] = $starttime->getTimestamp();
    $triggered_data->variables['STARTCOUNT'] = 1;

    $cancelkey = array_search($operator, $this->data->cancels);
    if($cancelkey!==false) {     
      unset($this->data->cancels[$cancelkey]);
      $this->save();
    }
    $return_value = $this->makeCall($starttime, $triggered_data);

    if($return_value!==0) return false;

    return true;
  }

  public function getScheduleInstances() {
    $list = array();
    $rootdir = '/var/spool/asterisk/outgoing';
    if($dh = opendir($rootdir)) {
      while(($file = readdir($dh)) !== false) {
        if(is_file($rootdir . '/' . $file)) {
          if($file[0]!='.') {
            $isscheduler = false;
            $scheduleid = null;
            $scheduleoperator = null;
            $schedulechannel = null;
            $content = explode("\n", file_get_contents($rootdir.'/'.$file));
            foreach($content as $line) {
              if(strpos($line, 'Context: scheduler')===0) {
                $isscheduler = true;
              } elseif(strpos($line, 'Set: SCHEDULE=')===0) {
                $scheduleid = trim(substr($line, 14));
              } elseif(strpos($line, 'Set: OPERATOR=')===0) {
                $scheduleoperator = trim(substr($line, 14));
              } elseif(strpos($line, 'Channel: ')===0) {
                $schedulechannel = trim(substr($line, 9));
              }
            }
            if($isscheduler&&$schedulechannel&&($scheduleid==$this->id)) {
              $list[] = (object)array('file' => $rootdir.'/'.$file, 'channel' => $schedulechannel, 'operator' => $scheduleoperator);
            }
          }
        }
      }
      closedir($dh);
    }
    return $list;
  }

  /**
   * Отменяет предыдущее запланированное расписание, отменяет текущую операцию по расписанию
   * при наличии флага. Если оператор не задан - отменяет расписания для всех операторов.
   *
   * @param string $operator Опциональный параметр оператора, для каждого оператора может быть добавлен свой планировщик
   * @param bool $cancel При выставленном флаге отменяет текущее выполняющееся расписание
   * @return bool Возвращает истину в случае успешной операции отмены планирования вызова
   */
  public function stop($operator = '', $cancel = false) {
    $list = $this->getScheduleInstances();
    foreach($list as $schedule) {
      //Если оператор задан - ищем события только для данного оператора, иначе все события этого расписания
      if(($operator&&($schedule->operator==$operator))||!$operator) {
        unlink($schedule->file);
        if($cancel) {
          $chanparts = explode('/', $schedule->channel);
          $chantype = $chanparts[0];
          $chanpeer = array_pop($chanparts);
          if($this->agi) {
            $channels = explode(' ', $this->agi->get_variable('CHANNELS('.$chantype.'/.*'.$chanpeer.'-.*)', true));
            foreach($channels as $channel) {
              $scheduleid = $this->agi->get_variable('IMPORT('.$channel.',SCHEDULE)', true);
              if($scheduleid = $this->id) $this->agi->hangup($channel);
            }
          } else {
            $channels = $this->getAsteriskCalls();
            foreach($channels as $channel) {
              if(preg_match('/'.$chantype.'\/.*'.$chanpeer.'-.*/', $channel['id'])) {
                $scheduleid = $this->ami->GetVar($channel['id'], 'SCHEDULE');
                if(isset($scheduleid['Value'])&&($scheduleid['Value'] = $this->id)) $this->ami->Hangup($channel['id']);
              }
            }
          }
        }
      }
    }    
    if(!in_array($operator, $this->data->cancels)) {
      $this->data->cancels[] = $operator;
//      if(isset($this->agi)) $this->agi->verbose('Set cancel flag to '.$operator. ' total cancels '.print_r($this->data->cancels, true));
      $this->save();
    }
    return true;
  }

  public function trigger($request_data) {
    if(!$this->agi) return false;
    if(isset($request_data->notify)&&isset($request_data->audio)) {
      $operator =  $this->agi->get_variable('OPERATOR', true);
      if(empty($operator)) return false;
      $triggered_data = new \stdClass();
      $triggered_data->variables = array();
      $triggered_data->variables['__SIPADDHEADER51'] = 'Alert-Info: <http://localhost/>\;info=alert-autoanswer';
      $triggered_data->variables['__SIPADDHEADER52'] = 'Alert-Info: <http://localhost/>\;answer-after=0';
      $triggered_data->variables['__SIPADDHEADER53'] = 'Call-Info: <sip:127.0.0.1>\;answer-after=0';

      $triggered_data->variables['OPERATOR'] = $operator;
      $triggered_data->variables['SCHEDULE'] = $this->id;  

      $triggered_data->destination = $operator;
      $triggered_data->application = 'Playback';
      $triggered_data->applicationdata = $request_data->audio;
      $triggered_data->retries = 0;
      $triggered_data->retrydelay = 10;
      $triggered_data->calltimeout = 30;
      $triggered_data->callerid = 'OAS´terisk <0>';
      $this->makeCall(new \DateTime(), $triggered_data);
    } else {
      $trigger = getModuleByClass($this->data->trigger); //find trigger class ScheduleTrigger
      if($trigger&&($trigger instanceof Trigger)) { //Запускаем триггер чтобы получить основные параметры планируемого вызова
        return @$trigger->trigger($request_data); 
      } else {
        return false;
      }    
    }
  }

  /**
   * Запускает выполнение действия по событию расписания
   *
   * @return bool Возвращает истину в случае успешного выполнения сценария
   */
  public function run() {
    if(!$this->agi) return false;
    $channel = $this->agi->get_variable('CHANNEL(name)', true);
    $channel = substr($channel, 0, strrpos($channel, ';'));
    if($this->agi->get_variable('IMPORT('.$channel.';2,DESTINATION)', true) == '') {
      $this->agi->exec('NoCDR', '');
      return false;
    }
    $this->agi->set_variable('CHANNEL(hangup_handler_push)', 'scheduler,reschedule,1');
    $cidnum =  $this->agi->get_variable('CIDNUM', true);
    $cidname =  $this->agi->get_variable('CIDNAME', true);
    $this->agi->set_variable('CALLERID(num)', $cidnum);
    $this->agi->set_variable('CALLERID(name)', $cidname);
    if($this->data->action) {
      $this->agi->setContext($this->data->action);
      return true;
    }
    return false;
  }

  private function finish($operator = null) {
    if(!empty($this->data->finish)) {
      if($operator) {
        while($this->agi->get_variable('DEVICE_STATE('.$operator.')', true) != "NOT_INUSE") {
          sleep(10);
        }
      }
      $this->agi->exec('gosub', $this->data->finish.',s,1');
    }
  }

  public function test() {
    if(!$this->agi) return false;
    $operator =  $this->agi->get_variable('OPERATOR', true);
    if(!empty($operator)) {
      $devstate =  $this->agi->get_variable('DEVICE_STATE('.$operator.')', true);
      if($devstate!='NOT_INUSE') {
        $now = new \DateTime();
        $nextstart = clone $now;
        $nextstart->add(new \DateInterval('PT10S'));

        $triggered_data = new \stdClass();
        $triggered_data->variables = $this->data->variables;

        $vars = $this->getTriggeredVariables();
        foreach($vars as $var) {
          $triggered_data->variables[$var] = $this->agi->get_variable($var, true);
        }

        $triggered_data->variables['STARTTIME'] = $this->agi->get_variable('STARTTIME', true);
        $triggered_data->variables['STARTCOUNT'] = $this->agi->get_variable('STARTCOUNT', true);
        $triggered_data->variables['OPERATOR'] = $operator;
        $triggered_data->variables['SCHEDULE'] = $this->id;  
        $triggered_data->variables['CIDNAME'] = $this->agi->get_variable('CIDNAME', true);
        $triggered_data->variables['CIDNUM'] = $this->agi->get_variable('CIDNUM', true);

        $triggered_data->destination = $this->agi->get_variable('DESTINATION', true);
        $triggered_data->retries = $this->agi->get_variable('RETRIES', true);
        $triggered_data->retrydelay = $this->agi->get_variable('RETRYDELAY', true);
        $triggered_data->calltimeout = $this->agi->get_variable('CALLTIMEOUT', true);
        $triggered_data->callerid = $this->agi->get_variable('CALLERID(all)', true);
        $list = $this->getScheduleInstances();
        foreach($list as $schedule) {
          if($schedule->operator==$operator) {
            unlink($schedule->file);
          }
        }    
        $return_value = $this->makeCall($nextstart, $triggered_data);
        $this->agi->set_variable('DESTINATION', '');
        $this->agi->exec('NoCDR', '');
        $this->agi->answer();
        return $return_value;
      } else {
        $this->agi->setContext($this->data->context, $this->agi->get_variable('DESTINATION', true));
      }
    } else {
      $this->agi->setContext($this->data->context, $this->agi->get_variable('DESTINATION', true));
    }
  }

  /**
   * Переносит вызов события по расписанию на следующую дату по отношению к последней запланированной
   *
   * @param string $operator Опциональный параметр оператора, для каждого оператора может быть добавлен свой планировщик
   * @return bool Возвращает истину в случае успешной операции планирования вызова
   */
  public function next() {
    if(!$this->agi) return false;
    $operator =  $this->agi->get_variable('OPERATOR', true);
    if(in_array($operator, $this->data->cancels)) return false;
    $count = $this->agi->get_variable('STARTCOUNT', true)+1;
    $starttime = new \DateTime('@'.$this->agi->get_variable('STARTTIME', true));
    $laststart =  $this->agi->get_variable('CHANNEL(UNIQUEID)', true);
    $laststart = new \DateTime('@'.substr($laststart, 0, strpos($laststart, '.')));
    $now = new \DateTime();
    if($this->data->repeat) { //Повторяем ли задание?
      $nextstart = clone $now;
      if($this->data->repeatby) { //А повторяем задание с задержкой?
        if($this->data->repeatby[0]=='+') { //Сдвиг относительно окончания задания
          $nextstart->add(new \DateInterval('PT'.((int)$this->data->repeatby).'S'));
        } else {
          $nextstart->setTimestamp($laststart->getTimestamp()); //Сдвиг относительно старта задания
          $nextstart->add(new \DateInterval('PT'.((int)$this->data->repeatby).'S'));
        }
      }
      if($this->data->repeatfor) { //Если задано ограничение на число/длительность запусков
        if($this->data->repeatfor[0]=='+') { //По числу повторов
          if($count > (int)$this->data->repeatfor) {
            $this->finish($operator);
            if(($this->data->start != 'manual') && $this->data->enabled) return $this->start($operator);
            return false;
          }
        } else {
          if($nextstart->getTimestamp() - $starttime->getTimestamp() > (int)$this->data->repeatfor) {
            $this->finish($operator);
            if(($this->data->start != 'manual') && $this->data->enabled) return $this->start($operator);
            return false;
          }
        }
      }

      $triggered_data = $this->getTriggeredData($operator);
      if(!$triggered_data) {
        $this->finish($operator);
        return false;
      }
  
      $triggered_data->variables['STARTTIME'] = $starttime->getTimestamp();
      $triggered_data->variables['STARTCOUNT'] = $count;

      $return_value = $this->makeCall($nextstart, $triggered_data);
      if($return_value != 0) return false;
      return true;
    } else {
      if(($this->data->start != 'manual') && $this->data->enabled) return $this->start($operator);
      return true;
    }
    return false;
  }
}

?>