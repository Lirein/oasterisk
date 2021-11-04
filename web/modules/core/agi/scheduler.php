<?php

namespace scheduler;

class SchedulerAGI extends \Module implements \module\IAGI {

  public function agi(\stdClass $request_data) {
    $exten = $this->agi->get_variable('EXTEN', true);
    $context = $this->agi->get_variable('CONTEXT', true);
    $operator = $this->agi->get_variable('OPERATOR', true);
    $schedule = '';
    if(isset($request_data->start)||isset($request_data->stop)) {
      if(isset($request_data->id)) $schedule = $request_data->id;
      if(!$operator) {
        $operator = $this->agi->get_variable('CHANNEL(name)', true);
        $operator = substr($operator, 0, strrpos($operator, '-'));
      }
    } else {
      $schedule = $this->agi->get_variable('SCHEDULE', true);
    }
    if($schedule=='') {
      $this->agi->log('warning', 'No schedule specified');
      return 1;
    }
    $schedule = new \core\ScheduleSubject($schedule);
    if(!$schedule->old_id) {
      $this->agi->log('warning', 'No schedule with id '.$schedule->id.' found');
      unset($schedule);
      return 2;
    }
    if($operator) {
      //TODO: Operator to the contactlist search
    }
    if(isset($request_data->start)) {
      $this->agi->verbose('Start '.$schedule->id.' for '.$operator, 3);
      $schedule->start($operator);
    } elseif(isset($request_data->stop)) {
      $this->agi->verbose('Stop '.$schedule->id.' for '.$operator, 3);
      $schedule->stop($operator, isset($request_data->cancel));
    } elseif(isset($request_data->trigger)) {
      $this->agi->verbose('Start trigger', 3);
      if($schedule->trigger($request_data)) {
        $this->agi->verbose('End trigger', 3);
        $this->agi->set_variable('TRIGGER_STATUS', 'SUCCESS');
      } else {
        $this->agi->verbose('Fail trigger', 3);
        $this->agi->set_variable('TRIGGER_STATUS', 'FAILED');
      }
    } else {
      if(($exten=='s')&&($context=='scheduler')) {
        $schedule->run();
        $this->agi->verbose('Scheduler action start', 3);
      } elseif(($exten=='call')&&($context=='scheduler')) {
        $schedule->test();
        $this->agi->verbose('Scheduler action call', 3);
      } elseif((($exten=='failed')||($exten=='reschedule'))&&($context=='scheduler')) {
        $schedule->next();
        $this->agi->verbose('Scheduler action reschedule', 3);
      } else {
        $this->agi->log('warning', 'No action found');
      }
    }
  }

}

?>