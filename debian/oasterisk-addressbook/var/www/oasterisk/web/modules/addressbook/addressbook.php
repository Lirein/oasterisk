<?php

namespace addressbook;

class AddressBook extends \core\Module {

  private $dialplan = null;

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-addressbook');
    return $result;
  }
    
  public function __construct() {
    parent::__construct();
    $this->dialplan = new \core\Dialplan();
  }

  public function reloadConfig() {
    $this->dialplan->reloadConfig();
  }

  public function getApplications() {
    $result = array();
    foreach($this->dialplan->getApplications() as $app => $appdata) {
      $result[] = (object) array('id' => $app, 'text' => $app);
    }
    return $result;
  }

  public function getBooks() {
    $contexts = $this->dialplan->getContexts();
    $books = array();
    foreach($contexts as $context) {
      if(strpos($context->id, 'ab-')===0) {
        $books[substr($context->id, 3)] = $context->title;
      }
    }
    return $books;
  }

  public function setBook($book, $title) {
    $context = $this->dialplan->getContext('ab-'.$book);
    if(!$context) {
      $context = new \stdClass();
      $context->extents = array();
    }
    return $this->dialplan->saveContext('ab-'.$book, 'ab-'.$book, $title, $context->extents);
  }

  public function renameBook($book, $newbook, $title=null) {
    $context = $this->dialplan->getContext('ab-'.$book);
    if(!$title) $title = $context->title;
    $result = $this->dialplan->saveContext('ab-'.$book, 'ab-'.$newbook, $title, $context->extents);
    $modules = findModulesByClass('core\ContactPropertyModule', true);
    if($modules&&count($modules)) {
      foreach($modules as $module) {
        $classname = $module->class;
        $info = $classname::info();
        foreach($context->extents as $exten => $extendata) {
          $contactprops = new $classname($exten.'@ab-'.$book);
          $oldprop = $contactprops->getProperties();
          $contactprops->removeProperties();
          unset($contactprops);
          $contactprops = new $classname($exten.'@ab-'.$newbook);
          $contactprops->setProperties($oldprop);
        }
      }
    }    
    return $result;
  }

  public function copyBook($book, $newbook, $title=null) {
    $context = $this->dialplan->getContext('ab-'.$book);
    if(!$title) $title = $context->title;
    $result = $this->dialplan->saveContext('', 'ab-'.$newbook, $title, $context->extents);
    $modules = findModulesByClass('core\ContactPropertyModule', true);
    if($modules&&count($modules)) {
      foreach($modules as $module) {
        $classname = $module->class;
        $info = $classname::info();
        foreach($context->extents as $exten => $extendata) {
          $contactprops = new $classname($exten.'@ab-'.$book);
          $oldprop = $contactprops->getProperties();
          unset($contactprops);
          $contactprops = new $classname($exten.'@ab-'.$newbook);
          $contactprops->setProperties($oldprop);
        }
      }
    }    
    return $result;
  }

  public function removeBook($book) {
    return $this->dialplan->removeContext('ab-'.$book);
  }

  public function getContacts($book) {
    $result = array();
    $contextData = $this->dialplan->getContext('ab-'.$book);
    if($contextData) {
      foreach($contextData->extents as $exten => $extendata) {
        $entry = new \stdClass();
        $entry->id = $exten;
        $entry->name = $exten;
        $entry->numbers = array();
        $entry->channels = array();
        foreach($extendata as $action) {
          if($action->title!=$exten) $entry->name = $action->title;
          if(preg_match('/([A-Za-z_]+)\((.*)\)/', $action->value, $match)) {
            if(strtolower($match[1])=='dial') {
              $appdata = explode(',', $match[2]);
              $dials = explode('&', $appdata[0]);
              foreach($dials as $channel) {
                $dial = explode('/', $channel);
                $number = array_pop($dial);
                if(count($dial)&&($dial[0]=='Local')&&(strpos($number, '@ab-')!==false)) {
                  $number = explode('@', $number);
                  $contact = $this->getContact(substr($number[1], 3), $number[0]);
                  foreach($contact->numbers as $number) {
                    if(!in_array($number, $entry->numbers)) $entry->numbers[] = $number;
                  }
                  foreach($contact->channels as $channel) {
                    if(!in_array($channel, $entry->channels)) $entry->channels[] = $channel;
                  }
                } else {
                  if(!in_array($number, $entry->numbers)) $entry->numbers[] = $number;
                  if(!in_array($channel, $entry->channels)) $entry->channels[] = $channel;
                }
              }
            }
          }
        }
        $titledata = explode(':', $entry->name);
        $entry->title = isset($titledata[1])?trim($titledata[1]):'';
        $entry->name = $titledata[0];
        $modules = findModulesByClass('core\ContactPropertyModule', true);
        if($modules&&count($modules)) {
          foreach($modules as $module) {
            $classname = $module->class;
            $info = $classname::info();
            $propertyclass = $info->class;
            $contactprops = new $classname($exten.'@ab-'.$book);
            $entry->$propertyclass = $contactprops->getProperties();
            unset($contactprops);
          }
        }    
        $result[] = $entry;
      }
    }
    return $result;
  }

  public function getContact($book, $exten) {
    $result = null;
    $contextData = $this->dialplan->getContext('ab-'.$book);
    if($contextData&&isset($contextData->extents->$exten)) {
      $extendata = $contextData->extents->$exten;
      $result = new \stdClass();
      $result->id = $exten;
      $result->actions = array();
      $result->name = $exten;
      $result->numbers = array();
      $result->channels = array();
      foreach($extendata as $action) {
        $entry = new \stdClass();
        $entry->synonym = $action->synonym;
        if($action->title!=$exten) $result->name = $action->title;
        if(preg_match('/([A-Za-z_]+)\((.*)\)/', $action->value, $match)) {
          switch(strtolower($match[1])) {
            case 'dial': {
              $entry->type = 'dial';
              $appdata = explode(',', $match[2]);
              $entry->dials = explode('&', $appdata[0]);
              foreach($entry->dials as $channel) {
                $dial = explode('/', $channel);
                $number = array_pop($dial);
                if(count($dial)&&($dial[0]=='Local')&&(strpos($number, '@ab-')!==false)) {
                  $number = explode('@', $number);
                  $contact = $this->getContact(substr($number[1], 3), $number[0]);
                  foreach($contact->numbers as $number) {
                    if(!in_array($number, $result->numbers)) $result->numbers[] = $number;
                  }
                  foreach($contact->channels as $channel) {
                    if(!in_array($channel, $result->channels)) $result->channels[] = $channel;
                  }
                } else {
                  if(!in_array($number, $result->numbers)) $result->numbers[] = $number;
                  if(!in_array($channel, $result->channels)) $result->channels[] = $channel;
                }
              }
              if(isset($appdata[1])) {
                $entry->timeout = $appdata[1];
                if(strtoupper($entry->timeout)=='${DIAL_TIMEOUT}') {
                  $entry->timeout = '';
                }
              } else {
                $entry->timeout = '';
              }
              if(isset($appdata[2])) {
                $entry->options = $appdata[2];
                if(strtoupper($entry->options)=='${DIAL_OPTIONS}') {
                  $entry->options = '';
                }
              } else {
                $entry->options = '';
              }
            } break;
            case 'wait': {
              $entry->type = 'wait';
              $entry->timeout = $match[2];
            } break;
            default: {
              $entry->type = 'custom';
              $entry->app = $match[1];
              $entry->data = $match[2];
            }
          }
        } else {
          $entry->type = 'other';
          $entry->value = $action->value;
        }
        $result->actions[] = $entry;
      }
      $titledata = explode(':', $result->name);
      $result->title = isset($titledata[1])?trim($titledata[1]):'';
      $result->name = $titledata[0];
      $modules = findModulesByClass('core\ContactPropertyModule', true);
      if($modules&&count($modules)) {
        foreach($modules as $module) {
          $classname = $module->class;
          $info = $classname::info();
          $propertyclass = $info->class;
          $contactprops = new $classname($exten.'@ab-'.$book);
          $result->$propertyclass = $contactprops->getProperties();
          unset($contactprops);
        }
      }    
    }
    return $result;
  }

  public function setContact($book, $data) {
    $result = false;
    $contextData = $this->dialplan->getContext('ab-'.$book);
    if($contextData) {
      if($data->orig_id!='') {
        $origid = $data->orig_id;
        if(isset($contextData->extents->$origid)) {
          unset($contextData->extents->$origid);
        }
      }
      $newid = $data->id;
      if(($data->orig_id!='')&&($data->orig_id!=$data->id)&&isset($contextData->extents->$newid)) return false;
      if(($data->orig_id=='')&&(isset($contextData->extents->$newid))) return false;
      $actions = array();
      $i = 1;
      foreach($data->actions as $action) {
        $entry = new \stdClass();
        $entry->synonym = '';
        if(isset($action->synonym)) $entry->synonym = $action->synonym;
        switch($action->type) {
          case 'dial': {
            if($action->timeout=='') $action->timeout = '${DIAL_TIMEOUT}';
            if($action->options=='') $action->options = '${DIAL_OPTIONS}';
            $entry->value = 'Dial('.(isset($action->dials)?implode('&', $action->dials):$action->appdata).','.$action->timeout.','.$action->options.')';
          } break;
          case 'wait': {
            $entry->value = 'Wait('.(isset($action->timeout)?$action->timeout:$action->appdata).')';
          } break;
          case 'custom': {
            $entry->value = $action->app.'('.(isset($action->data)?$action->data:$action->appdata).')';
          } break;
          case 'other': {
            $entry->value = $action->app;
          } break;
        }
        $actions[$i++] = $entry;
      }
      if(count($actions)) {
        $actions[1]->title = $data->name.($data->title?(': '.$data->title):'');
        $contextData->extents->$newid = $actions;
        $modules = findModulesByClass('core\ContactPropertyModule', true);
        if($modules&&count($modules)) {
          foreach($modules as $module) {
            $classname = $module->class;
            $info = $classname::info();
            $propertyclass = $info->class;
            if(isset($data->$propertyclass)) {
              $contactprops = new $classname($newid.'@ab-'.$book);
              $contactprops->setProperties($data->$propertyclass);
              unset($contactprops);
            }
          }
        }    
      }
      $result = $this->dialplan->saveContext('ab-'.$book, 'ab-'.$book, $contextData->title, $contextData->extents);
    }
    return $result;
  }

  public function removeContact($book, $id) {
    $result = false;
    $contextData = $this->dialplan->getContext('ab-'.$book);
    if($contextData) {
      if($id!='') {
        if(isset($contextData->extents->$id)) unset($contextData->extents->$id);
      }
      $modules = findModulesByClass('core\ContactPropertyModule', true);
      if($modules&&count($modules)) {
        foreach($modules as $module) {
          $classname = $module->class;
          $info = $classname::info();
          $contactprops = new $classname($id.'@ab-'.$book);
          $contactprops->removeProperties();
          unset($contactprops);
        }
      }    
      $result = $this->dialplan->saveContext('ab-'.$book, 'ab-'.$book, $contextData->title, $contextData->extents);
    }
    return $result;
  }

}

?>