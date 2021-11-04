<?php

namespace core;

class LinesREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'lines';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        $entries = array();
        $lines = new \channel\Lines();
        foreach($lines as $line) {
          if((isset($line->istemplate)&&!$line->istemplate)||(!isset($line->istemplate))) {
            $entry = new \stdClass();
            if(isset($line->uniqueid)) {
              $entry->id = $line->getTypeName().'/'.$line->uniqueid;
            } else {
              $entry->id = $line->getTypeName().'/'.$line->id;
            }
            if(isset($line->name)) {
              $entry->title = $line->name;
              $entry->jobtitle = $line->title; 
            } else {
              $entry->title = $line->title;
              $entry->jobtitle = null;
            }
            $entry->icon = 'PersonSharpIcon';
            if($line instanceof \channel\Trunk) {
              $entry->phone = $line->phone;
              $entry->type = 'trunk';
            } else {
              $entry->phone = $line->id;
              $entry->type = 'peer';
            }
            // if(isset($line->group)) {
            //   $groupId = explode('@', $line->id);
            //   $groupId = array_pop($groupId);
            //   $groupTitle = $line->group;
            // } else {
              $groupId = $line::getTypeName();
              $groupTitle = $line::getTypeTitle();
            // }
            if(!isset($entries[$groupId])) {
              $entries[$groupId] = (object)array('id' => $groupId, 'title' => $groupTitle, 'value' => array());
            }
            switch(strtolower($line->getTypeName())) {
              case 'contact': {
                $entry->readonly = false;
              } break;
              default: {
                $entry->readonly = true;
              }
            }
            $entries[$groupId]->value[] = $entry;
          }
        }
        $entries = array_values($entries);
        $result = self::returnResult($entries);
      } break;
    }
    return $result;
  }
    
}

?>