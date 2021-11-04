<?php

namespace core;

class CdrProcessor extends Module {

  public static function check($write = false) {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function extrastates() {
    $result=array();
    foreach(getModulesByClass('core\CdrFilter') as $filter) {
      if(method_exists($filter, 'states')) {
        $result=array_merge($result, $filter->states());
      }
    }
    return $result;
  }

  public function process($cdrdata) {
    $haszones=false;
    $zonesmodule=getModuleByClass('core\SecZones');
    if($zonesmodule) $zones=$zonesmodule->getCurrentSeczones();
    if($zonesmodule&&!empty($zones)) {
      $haszones=true;
    }
    $dialplan = getModuleByClass('core\Dialplan');
    $contextlist = $dialplan->getContexts();
    $contexts = array();
    foreach($contextlist as $ctx) {
      $contexts[$ctx->id] = $ctx->title;
    }

    $result=array();
    $filters=array();

    function subProcess(&$filters, $key, &$master, $haszones, $contexts) {
      $filterres=false;
      foreach($filters as $filter) {
        if(isset($master[$key])&&in_array($master[$key]->entry->app, $filter->apps)) {
          $filterres|=$filter->module->filter((object) array('record' => &$key, 'records' => &$master));
        }
      }
      if(!$filterres&&$haszones) unset($master[$key]);
      if(isset($master[$key])&&($master[$key]->entry->exten=='s')) {
        $master[$key]->dst->name=$contexts[$master[$key]->entry->context];
      }
      if(!$filterres&&isset($master[$key])&&isset($master[$key]->value)) {
        $records=&$master[$key]->value;
        foreach($records as $rkey => $record) {
          subProcess($filters, $rkey, $records, $haszones, $contexts);
        }
      }
    }

    function postProcessDate($key, &$master) {
      $master[$key]->from=$master[$key]->from->format(\DateTime::ISO8601);
      $master[$key]->answer=$master[$key]->answer->format(\DateTime::ISO8601);
      $master[$key]->to=$master[$key]->to->format(\DateTime::ISO8601);
      if(isset($master[$key]->value)) {
        $records=&$master[$key]->value;
        foreach($records as $rkey => $record) {
          postProcessDate($rkey, $records);
        }
      }
    }

    foreach(getModulesByClass('core\CdrFilter') as $filter) {
      if(method_exists($filter, 'apps')) {
        $filters[]=(object) array('apps'=>$filter->apps(), 'module'=>$filter);
      }
    }
    foreach($cdrdata as $record) {
      if(isset($result[$record->uid])) {
        if($result[$record->uid]->from>$record->from) {
          $result[$record->uid]->from=clone $record->from;
          $result[$record->uid]->seconds=getDateDiff($result[$record->uid]->from, $result[$record->uid]->to);
        }
        if($result[$record->uid]->to<$record->to) {
          $result[$record->uid]->to=clone $record->to;
          $result[$record->uid]->duration=getDateDiff($result[$record->uid]->from, $result[$record->uid]->to);
          $result[$record->uid]->seconds=getDateDiff($result[$record->uid]->answer, $result[$record->uid]->to);
        }
        $result[$record->uid]->value[$record->id]=$record;
      } else {
        $result[$record->uid]=$record;
      }
    }
    foreach(array_reverse($result) as $uid => $record) {
      if(isset($record->lid)&&($record->lid!=$uid)) {
        if(isset($result[$record->lid])) {
          if($result[$record->lid]->from>$record->from) {
            $result[$record->lid]->from=clone $record->from;
            $result[$record->lid]->seconds=getDateDiff($result[$record->lid]->from, $result[$record->lid]->to);
          }
          if($result[$record->lid]->to<$record->to) {
            $result[$record->lid]->to=clone $record->to;
            $result[$record->lid]->duration=getDateDiff($result[$record->lid]->from, $result[$record->lid]->to);
            $result[$record->lid]->seconds=getDateDiff($result[$record->lid]->answer, $result[$record->lid]->to);
          }
          $result[$record->lid]->value[$uid]=&$result[$uid];
          unset($result[$uid]);
        }
      }
    }
    foreach($result as $uid => $record) {
      subProcess($filters, $uid, $result, $haszones, $contexts);
    }
    foreach($result as $uid => $record) {
      postProcessDate($uid, $result);
    }
    return $result;
  }

}

abstract class CdrFilter extends Module {

  abstract public function apps();
  abstract public function filter($data);

  protected static function clone($object) {
    $newobj = clone $object;
    foreach($newobj as $key => $value) {
      if(is_object($value)) $newobj->$key = self::clone($value);
    }
    return $newobj;
  }

}

abstract class CdrEngine extends Module {

  abstract public function info();
  abstract public function cdr(\DateTime $from, \DateTime $to);

}

abstract class CdrEngineSettings extends Module {

  public static function selectable() {
    return true;
  }
  
  //  abstract public function getParams();
//  abstract public function setParams($data);
  abstract public function enable();
  abstract public function disable();

}

?>
