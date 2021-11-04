<?php

namespace core;

class ScheduleREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'schedule';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        $schedules = new \scheduler\Schedules();
        $objects = array();
        foreach($schedules as $id => $data) {
          $objects[]=(object)array('id' => $id, 'title' => $data->title);
        }
        $result = self::returnResult($objects);
        unset($schedules);
      } break;
      case "get": {
        if(isset($request_data->id)&&self::checkEffectivePriv('schedule', $request_data->id, 'settings_reader')) {
          $schedule = new \scheduler\Schedule($request_data->id);
          $result = self::returnResult($schedule->cast());
          unset($schedule);
        }
      } break;
      case "set": {
        if(isset($request_data->id)&&self::checkEffectivePriv('schedule', $request_data->id, 'settings_writer')) {
          $schedule = new \scheduler\Schedule($request_data->id);
          if($schedule->assign($request_data)) {
            if($schedule->save()){
              $schedule->reload();
              $result = self::returnResult((object)array('id' => $schedule->id));
            } else {
              $result = self::returnError('danger', 'Не удалось сохранить профиль');
            }
          } else {
            $result = self::returnError('danger', 'Не удалось установить данные');
          }
          unset($schedule);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "add": {
        if((empty($request_data->id)||($request_data->id == 'false'))&&self::checkPriv('settings_writer')) {
          $schedule = new \scheduler\Schedule();
          if($schedule->assign($request_data)) {
            if($schedule->save()){
              $schedule->reload();
              $result = self::returnResult((object)array('id' => $schedule->id));
            } else {
              $result = self::returnError('danger', 'Не удалось сохранить профиль');
            }
          } else {
            $result = self::returnError('danger', 'Не удалось установить данные');
          }
          unset($schedule);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "remove": {
        if(isset($request_data->id)&&self::checkPriv('settings_writer')) {
          $schedule = new \scheduler\Schedule($request_data->id);
          if($schedule->delete()) {
            $schedule->reload();
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Не удалось удалить профиль');
          }
          unset($schedule);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      // case "get-contexts":{
      //   $dialplan = new \core\Dialplan();
      //   $contextList = array();
      //   foreach($dialplan->getContexts() as $v) {
      //     $contextList[] = (object) array('id' => $v->id, 'text' => $v->title);
      //   }
      //   $result = self::returnResult($contextList);
      // } break;
      // case "get-triggers":{
      //   $triggers = findModulesByClass('core\ScheduleTrigger');
      //   $triggerlist = array();
      //   foreach($triggers as $trigger) {
      //     $classname = $trigger->class;
      //     $triggerlist[] = (object)array('id' => $classname, 'text' => $classname::getName());
      //   }
      //   $result = self::returnResult($triggerlist);
      // } break;
      // case "users": {
      //   $result =self::returnResult(self::getAsteriskPeers());
      // } break;
    }
    return $result;
  }
    
}

?>