<?php

namespace confbridge;

use stdClass;

class ConfbridgeModule extends \core\Module implements \AGIInterface {

  public function agi(\stdClass $request_data) {
    $require_room_pin=0;
    $require_user_pin=0;
    $has_room_user=false;
    $roomenabled=false;
    $confroom=$this->agi->get_variable('CONFROOM',true);;
    $confgroup=$this->agi->get_variable('CONFGROUP',true);;
    $cidnum=(string)$this->agi->get_variable('CIDNUM',true);
    $roomprofile='';
    $userprofile='';
    $menuprofile='';
    $bridge=false;
    $nofail = 0;
    $connline=$this->agi->get_variable('CONNECTEDLINE(all)',true);
    $this->agi->set_variable('CDR(dclid)',$connline);
    if(isset($request_data->failed)) {
      $username=$this->agi->get_variable('CIDNAME',true);
      if($username) $this->agi->set_variable('CDR(action)',$username);
      if($confroom) $this->agi->set_variable('CDR(recordingfile)',$confroom);
      if($confgroup) {
        $nofail=self::getDB('conf_'.$confroom.'/group_'.$confgroup, $cidnum);
        if($nofail) self::deltreeDB('conf_'.$confroom.'/group_'.$confgroup.'/'.$cidnum);
      } else {
        $nofail=self::getDB('conf_'.$confroom,$cidnum);
        if($nofail) self::deltreeDB('conf_'.$confroom.'/'.$cidnum);
      }
      if($nofail) return 0;
    }
    $usernumber=$this->agi->get_variable('CALLERID(num)',true);
    $channelnumber=explode('/',$this->agi->get_variable('CHANNEL(name)',true));
    $channelnumber=array_pop($channelnumber);
    if(strrpos($channelnumber,'-')!==false) $channelnumber=substr($channelnumber,0,strrpos($channelnumber,'-'));
    $rooms=$this->cache->get('confroomlist');
    if(!$rooms) {
      $rooms=array();
      $roomlist=self::getRooms();
      foreach($roomlist as $roomname) {
        $rooms[$roomname]=$this->getPersistentRoom($roomname);
      }
      $this->cache->set('confroomlist', $rooms, 30);
    }
    $groups=$this->cache->get('confgrouplist');
    if(!$groups) {
      $groups=new \stdClass();
      $grouplist=self::getGroups();
      foreach($grouplist as $groupname) {
        $groups->$groupname=$this->getPersistentGroup($groupname);
      }
      $this->cache->set('confgrouplist', $groups, 30);
    }
    if(isset($request_data->incoming)) { //incoming calls to confbridge processing (dialog)
      $abmodule = null;
      //search active rooms and check dialed number, room and user pincode, select apropriate room
      $confroom=$this->agi->get_variable('CONFROOM',true);
      if($confroom == '') $confroom=$this->agi->get_variable('EXTEN',true);
      $roompin=$this->agi->get_variable('ROOM_PIN',true);
      $userpin=$this->agi->get_variable('USER_PIN',true);
      $addressbookuser = false;
      foreach($rooms as $roomname => $room) { //Search valid room
        if($room->active) {
          if(($confroom == $roomname)||((strlen($roompin)>0)&&($room==$room->pin))) {
            $confroom == $roomname;
            $roompin = $room->pin;
            $require_room_pin = 0;
            break;
          } else {
            if($require_room_pin < strlen($room->pin)) $require_room_pin = strlen($room->pin);
          }
        }
      }
      $this->agi->verbose($require_room_pin, 4);
      if($require_room_pin&&(strlen($roompin)==0)) {
        $readstate = 'TIMEOUT';
        while($readstate== 'TIMEOUT') {
          $this->agi->exec('Read','ROOM_PIN'.$this->agi->option_delim.'conf-getconfno'.$this->agi->option_delim.$require_room_pin.$this->agi->option_delim.$this->agi->option_delim.'1'.$this->agi->option_delim.'10');
          $roompin=$this->agi->get_variable('ROOM_PIN',true);
          $readstate=$this->agi->get_variable('READSTATUS', true);
          if(($roompin!='')&&($readstate=='TIMEOUT')) $readstate = 'OK';
        }
        if(in_array($readstate, array('ERROR', 'INTERRUPTED'))) return 1;
      }
      foreach($rooms as $roomname => $room) { //Search valid room
        if(($room->active)&&(($room->pin==$roompin)||($confroom==$roomname))) {
          $this->agi->verbose("Room found ".$roomname, 4);
          $has_room_user = false;
          foreach($room->users as $user) {
            $roomusernumber=explode('/',$user->chan);
            $chantype=$roomusernumber[0];
            $roomusernumber=array_pop($roomusernumber);
            if(($chantype=='DAHDI')&&in_array($roomusernumber[0],array('U','I','N','L','S','V','R'))) {
              $roomusernumber=substr($roomusernumber,1);
            }
            if(($chantype == 'Local')&&(strpos($roomusernumber, '@ab-')!==false)) {
              if(!$abmodule) $abmodule = getModuleByClass('addressbook\AddressBook');
              if($abmodule) {
                $contactinfo = explode('@', $roomusernumber);
                $exten = $contactinfo[0];
                $book = substr($contactinfo[1], 3);
                $contact = $abmodule->getContact($book, $exten);
                foreach($contact->numbers as $number) {
                  if(($number==$usernumber)||($number==$channelnumber)) {
                    $roomusernumber=$number;
                    $addressbookuser = true;
                    break;
                  }
                }
              }
            }
            if(($roomusernumber==$usernumber)||($roomusernumber==$channelnumber)) {
              $has_room_user = true;
              $require_user_pin = strlen($user->pin);
              break;
            }
            if($require_user_pin < strlen($user->pin)) $require_user_pin = strlen($user->pin);
          }
          if(!$has_room_user) foreach($room->groups as $groupname) {
            $groupname=$groupname->id;
            foreach($groups->$groupname->users as $user) {
              $groupusernumber=explode('/',$user->chan);
              $chantype=$groupusernumber[0];
              $groupusernumber=array_pop($groupusernumber);
              if(($chantype=='DAHDI')&&in_array($groupusernumber[0],array('U','I','N','L','S','V','R'))) {
                $groupusernumber=substr($groupusernumber,1);
              }
              if(($chantype == 'Local')&&(strpos($groupusernumber, '@ab-')!==false)) {
                if(!$abmodule) $abmodule = getModuleByClass('addressbook\AddressBook');
                if($abmodule) {
                  $contactinfo = explode('@', $groupusernumber);
                  $exten = $contactinfo[0];
                  $book = substr($contactinfo[1], 3);
                  $contact = $abmodule->getContact($book, $exten);
                  foreach($contact->numbers as $number) {
                    if(($number==$usernumber)||($number==$channelnumber)) {
                      $groupusernumber = $number;
                      $addressbookuser = true;
                      break;
                    }
                  }
                }
              }
              if(($roomusernumber==$usernumber)||($roomusernumber==$channelnumber)) {
                $has_room_user = true;
                $require_user_pin = strlen($user->pin);
              }
              if($require_user_pin < strlen($user->pin)) $require_user_pin = strlen($user->pin);
              if($has_room_user) break;
            }
          }
          if($require_user_pin&&(strlen($userpin)==0)) {
            $readstate = 'TIMEOUT';
            while($readstate== 'TIMEOUT') {
              $this->agi->exec('Read','USER_PIN'.$this->agi->option_delim.'confbridge-pin'.$this->agi->option_delim.$require_user_pin.$this->agi->option_delim.$this->agi->option_delim.'1'.$this->agi->option_delim.'10');
              $userpin=$this->agi->get_variable('USER_PIN',true);
              $readstate=$this->agi->get_variable('READSTATUS', true);
              if(($userpin!='')&&($readstate=='TIMEOUT')) $readstate = 'OK';
            }
            if(in_array($readstate, array('ERROR', 'INTERRUPTED'))) return 1;
          }
          $addressbookuser = false;
          foreach($room->users as $user) {
            $roomusernumber=explode('/',$user->chan);
            $chantype=$roomusernumber[0];
            $roomusernumber=array_pop($roomusernumber);
            if(($chantype=='DAHDI')&&in_array($roomusernumber[0],array('U','I','N','L','S','V','R'))) {
              $roomusernumber=substr($roomusernumber,1);
            }
            if(($chantype == 'Local')&&(strpos($roomusernumber, '@ab-')!==false)) {
              if(!$abmodule) $abmodule = getModuleByClass('addressbook\AddressBook');
              if($abmodule) {
                $contactinfo = explode('@', $roomusernumber);
                $exten = $contactinfo[0];
                $book = substr($contactinfo[1], 3);
                $contact = $abmodule->getContact($book, $exten);
                foreach($contact->numbers as $number) {
                  if(($number==$usernumber)||($number==$channelnumber)) {
                    $roomusernumber=$number;
                    $addressbookuser = true;
                    break;
                  }
                }
              }
            }
            if(($has_room_user&&(($roomusernumber==$usernumber)||($roomusernumber==$channelnumber))&&($user->pin==$userpin))||((!$has_room_user)&&($user->pin==$userpin)&&($user->pin!=''))) {
              if(!((($roomusernumber==$usernumber)||($roomusernumber==$channelnumber))&&(!$addressbookuser))&&($room->regex!='')&&(preg_match('/'.$room->regex.'/m',$usernumber))) continue;
              $this->agi->verbose("User found ".$usernumber,4);
              // $this->agi->verbose('roomuser: '.$roomusernumber.', cidnum: '.$usernumber.', channum: '.$channelnumber.', regex: '.$room->regex,3);
              $roomenabled=true;
              $confroom=$roomname;
              $roomprofile=$room->profile;
              $userprofile=$user->profile;
              $menuprofile=$user->menu;
              $maxcount=$room->maxcount;
              $this->agi->set_variable('EXTENDNUM',$user->extnum);
              if($maxcount) $this->agi->set_variable('CONFBRIDGE(bridge,max_members)',$maxcount);
              $this->agi->set_variable('CALLERID(name)',$user->callerid);
              if(!(($roomusernumber==$usernumber)||($roomusernumber==$channelnumber))) {
                $this->agi->set_variable('CALLERID(name)',$user->callerid.' ('.$usernumber.')');
                $this->agi->set_variable('CALLERID(num)',$roomusernumber);
              } else {
                $roomusernumber=explode('/',$user->chan);
                $roomusernumber=array_pop($roomusernumber);
                if($addressbookuser) $this->agi->set_variable('CALLERID(name)',$user->callerid.' ('.$usernumber.')');
                $this->agi->set_variable('CALLERID(num)',$roomusernumber);
              }
              break;
            }
          }
          if($roomenabled) break;
          foreach($room->groups as $groupname) {
            $groupname=$groupname->id;
            foreach($groups->$groupname->users as $user) {
              $groupusernumber=explode('/',$user->chan);
              $chantype=$groupusernumber[0];
              $groupusernumber=array_pop($groupusernumber);
              if(($chantype=='DAHDI')&&in_array($groupusernumber[0],array('U','I','N','L','S','V','R'))) {
                $groupusernumber=substr($groupusernumber,1);
              }
              if(($chantype == 'Local')&&(strpos($groupusernumber, '@ab-')!==false)) {
                if(!$abmodule) $abmodule = getModuleByClass('addressbook\AddressBook');
                if($abmodule) {
                  $contactinfo = explode('@', $groupusernumber);
                  $exten = $contactinfo[0];
                  $book = substr($contactinfo[1], 3);
                  $contact = $abmodule->getContact($book, $exten);
                  foreach($contact->numbers as $number) {
                    if(($number==$usernumber)||($number==$channelnumber)) {
                      $groupusernumber = $number;
                      $addressbookuser = true;
                      break;
                    }
                  }
                }
              }
              if(($has_room_user&&(($groupusernumber==$usernumber)||($groupusernumber==$channelnumber))&&($user->pin==$userpin))||((!$has_room_user)&&($user->pin==$userpin)&&($user->pin!=''))) {
                if(!((($groupusernumber==$usernumber)||($groupusernumber==$channelnumber))&&(!$addressbookuser))&&($room->regex!='')&&(preg_match('/'.$room->regex.'/m',$usernumber))) continue;
                $this->agi->verbose("User found ".$usernumber,4);
                // $this->agi->verbose('grpuser: '.$groupusernumber.', cidnum: '.$usernumber.', channum: '.$channelnumber.', regex: '.$room->regex,3);
                $roomenabled=true;
                $confroom=$roomname;
                $roomprofile=$room->profile;
                $userprofile=$user->profile;
                $menuprofile=$user->menu;
                $maxcount=$room->maxcount;
                $this->agi->set_variable('EXTENDNUM',$user->extnum);
                if($maxcount) $this->agi->set_variable('CONFBRIDGE(bridge,max_members)',$maxcount);
                $this->agi->set_variable('CALLERID(name)',$user->callerid);
                if(!(($groupusernumber==$usernumber)||($groupusernumber==$channelnumber))) {
                  $this->agi->set_variable('CALLERID(name)',$user->callerid.' ('.$usernumber.')');
                  $this->agi->set_variable('CALLERID(num)',$groupusernumber);
                } else {
                  $groupusernumber=explode('/',$user->chan);
                  $groupusernumber=array_pop($groupusernumber);
                  if($addressbookuser) $this->agi->set_variable('CALLERID(name)',$user->callerid.' ('.$usernumber.')');
                  $this->agi->set_variable('CALLERID(num)',$groupusernumber);
                }
                break;
              }
            }
          }
          if($roomenabled) break;
        }
      }
    } elseif(isset($request_data->direct)) { //direct call to named conf (no dialog, pin only)
      $confroom=$this->agi->get_variable('CONFROOM',true);
      if($confroom=='') $confroom=$this->agi->get_variable('EXTEN',true);
      $roomprofile=$this->agi->get_variable('ROOMPROFILE',true);
      $userprofile=$this->agi->get_variable('USERPROFILE',true);
      $menuprofile=$this->agi->get_variable('MENUPROFILE',true);
      $maxcount=$this->agi->get_variable('MAXCOUNT',true);
      if($maxcount) $this->agi->set_variable('CONFBRIDGE(bridge,max_members)',$maxcount);
      $roomenabled=true;
    } elseif(isset($request_data->outgoing)) { //outgoing invite, call or schedule event
      $requirepin=false;
      $confroom=$this->agi->get_variable('CONFROOM',true);
      if($confroom=='') $confroom=$this->agi->get_variable('EXTEN',true);
      $roomprofile=$this->agi->get_variable('ROOMPROFILE',true);
      $userprofile=$this->agi->get_variable('USERPROFILE',true);
      $menuprofile=$this->agi->get_variable('MENUPROFILE',true);
      $extendnum=$this->agi->get_variable('EXTENDNUM',true);
      $extenddelay=$this->agi->get_variable('EXTENDDELAY',true);
      if($extendnum!='') {
       if($extenddelay) {
         sleep($extenddelay);
         $this->agi->exec('SendDTMF',$extendnum);
       }
      }
      $maxcount=$this->agi->get_variable('MAXCOUNT',true);
      $chancidname=$this->agi->get_variable('CALLERID(name)',true);
      $cidnum=$this->agi->get_variable('CIDNUM',true);
      $cidname=$this->agi->get_variable('CIDNAME',true);
      if($cidnum) $this->agi->set_variable('CALLERID(num)',$cidnum);
      if($chancidname=='') $this->agi->set_variable('CALLERID(name)',$usernumber);
      if($cidname) $this->agi->set_variable('CALLERID(name)',$cidname);
      if($maxcount) $this->agi->set_variable('CONFBRIDGE(bridge,max_members)',$maxcount);
      $channelnumber=explode('/',$this->agi->get_variable('CHANNEL(name)',true));
      if($channelnumber[0]=='Local') {
        $channelnumber=array_pop($channelnumber);
        $subchannels = explode(';', $channelnumber);
        if((count($subchannels)>1)&&($subchannels[1]==1)) {
          $channelnumber=explode('/',$this->agi->get_variable('IMPORT(Local/'.$subchannels[0].';2,DIALEDPEERNAME)', true));
          if($channelnumber[0]=='Local') {
            $channelnumber=array_pop($channelnumber);
            $subchannels = explode(';', $channelnumber);
            $channelnumber = explode('/',$this->agi->get_variable('IMPORT(Local/'.$subchannels[0].';2,DIALEDPEERNAME)', true));
          }
          $channelnumber=array_pop($channelnumber);
          if(strrpos($channelnumber,'-')!==false) $channelnumber=substr($channelnumber,0,strrpos($channelnumber,'-'));     
          $cidname=$this->agi->get_variable('CALLERID(name)', true);
          $this->agi->set_variable('CALLERID(name)',$cidname.' ('.$channelnumber.')');
        }
      }
      $roomenabled=true;
    } elseif(isset($request_data->bridge)) { //goto bridge
      $requirepin=false;
      $confroom=$this->agi->get_variable('CONFROOM',true);
      $roomprofile=$this->agi->get_variable('ROOMPROFILE',true);
      $userprofile=$this->agi->get_variable('USERPROFILE',true);
      $menuprofile=$this->agi->get_variable('MENUPROFILE',true);
      $maxcount=$this->agi->get_variable('MAXCOUNT',true);
      if($maxcount) $this->agi->set_variable('CONFBRIDGE(bridge,max_members)',$maxcount);
      $roomenabled=true;
      $bridge=true;
    } elseif(isset($request_data->start)) { //Call for all users of conference
      $confroom=$this->agi->get_variable('CONFROOM',true);
      if($confroom=='') $confroom=$this->agi->get_variable('EXTEN',true);
      $this->startPersistentRoom($confroom);
      return 0;
    } elseif(isset($request_data->failed)) { //failed spool
      $this->agi->set_variable('failed',true);
      $roomname=$this->agi->get_variable('CONFROOM',true);
      $username=$this->agi->get_variable('CIDNAME',true);
      if($username) $this->agi->set_variable('CDR(action)',$username);
      if($roomname) $this->agi->set_variable('CDR(recordingfile)',$roomname);
      if(($nofail!=1)&&isset($rooms[$roomname])) {
        $room = &$rooms[$roomname];
        foreach($room->users as $user) {
          $roomusernumber=explode('/',$user->chan);
          $chantype=$roomusernumber[0];
          $roomusernumber=array_pop($roomusernumber);
          if(($chantype=='DAHDI')&&in_array($roomusernumber[0],array('U','I','N','L','S','V','R'))) {
            $roomusernumber=substr($roomusernumber,1);
          }
          if($roomusernumber==$channelnumber) {
            $nofail=self::getDB('conf_'.$roomname.'/user_'.$user->intid,'failed');
            self::setDB('conf_'.$roomname.'/user_'.$user->intid,'failed',($nofail==2)?0:1);
          }
        }
        foreach($room->groups as $groupname) {
          $groupname=$groupname->id;
          foreach($groups->$groupname->users as $user) {
            $groupusernumber=explode('/',$user->chan);
            $chantype=$groupusernumber[0];
            $groupusernumber=array_pop($groupusernumber);
            if(($chantype=='DAHDI')&&in_array($groupusernumber[0],array('U','I','N','L','S','V','R'))) {
              $groupusernumber=substr($groupusernumber,1);
            }
            if($groupusernumber==$channelnumber) {
              $nofail=self::getDB('conf_'.$roomname.'/group_'.$groupname.'/user_'.$user->intid,'failed');
              self::setDB('conf_'.$roomname.'/group_'.$groupname.'/user_'.$user->intid,'failed',($nofail==2)?0:1);
            }
          }
        }
      }
      return 0;
    } elseif(isset($request_data->hangup)) { //hangup call
      $abmodule = null;
      $failed=$this->agi->get_variable('failed',true);
      $cancell=$this->agi->get_variable('CANCELLAST',true);
      $confroom=$this->agi->get_variable('CONFROOM',true);
      $monfile=$this->agi->get_variable('MIXMONITOR_FILENAME',true);
      if($monfile!='') $this->agi->set_variable('CDR(recordingfile)',$monfile);
      $parties=$this->agi->get_variable('CONFBRIDGE_INFO(parties,'.$confroom.')',true);
      if(!$failed&&($parties==0)) {
        $roomname=$confroom;
        if(isset($rooms[$roomname])) {
          $room = &$rooms[$roomname];
          foreach($room->users as $user) {
            $roomusernumber=explode('/',$user->chan);
            $chantype=$roomusernumber[0];
            $roomusernumber=array_pop($roomusernumber);
            if(($chantype=='DAHDI')&&in_array($roomusernumber[0],array('U','I','N','L','S','V','R'))) {
              $roomusernumber=substr($roomusernumber,1);
            }
            if(($chantype == 'Local')&&(strpos($roomusernumber, '@ab-')!==false)) {
              if(!$abmodule) $abmodule = getModuleByClass('addressbook\AddressBook');
              if($abmodule) {
                $contactinfo = explode('@', $roomusernumber);
                $exten = $contactinfo[0];
                $book = substr($contactinfo[1], 3);
                $contact = $abmodule->getContact($book, $exten);
                foreach($contact->numbers as $number) {
                  if(($usernumber==$usernumber)||($number==$channelnumber)) {
                    $roomusernumber = $number;
                    break;
                  }
                }
              }
            }
            if($roomusernumber==$channelnumber) {
              if(file_exists('/var/spool/asterisk/outgoing/room_'.$roomname.'_user_'.$user->intid.'.call')) unlink('/var/spool/asterisk/outgoing/room_'.$roomname.'_user_'.$user->intid.'.call');
            } else {
              if($cancell&&$room->active&&file_exists('/var/spool/asterisk/outgoing/room_'.$roomname.'_user_'.$user->intid.'.call')) unlink('/var/spool/asterisk/outgoing/room_'.$roomname.'_user_'.$user->intid.'.call');
            }
            self::setDB('conf_'.$roomname.'/user_'.$user->intid,'failed',0);
          }
          foreach($room->groups as $groupname) {
            $groupname=$groupname->id;
            foreach($groups->$groupname->users as $user) {
              $groupusernumber=explode('/',$user->chan);
              $chantype=$groupusernumber[0];
              $groupusernumber=array_pop($groupusernumber);
              if(($chantype=='DAHDI')&&in_array($groupusernumber[0],array('U','I','N','L','S','V','R'))) {
                $groupusernumber=substr($groupusernumber,1);
              }
              if(($chantype == 'Local')&&(strpos($groupusernumber, '@ab-')!==false)) {
                if(!$abmodule) $abmodule = getModuleByClass('addressbook\AddressBook');
                if($abmodule) {
                  $contactinfo = explode('@', $groupusernumber);
                  $exten = $contactinfo[0];
                  $book = substr($contactinfo[1], 3);
                  $contact = $abmodule->getContact($book, $exten);
                  foreach($contact->numbers as $number) {
                    if(($usernumber==$usernumber)||($number==$channelnumber)) {
                      $groupusernumber = $number;
                      break;
                    }
                  }
                }
              }
              self::setDB('conf_'.$roomname.'/group_'.$groupname.'/user_'.$user->intid,'failed',0);
              if($groupusernumber==$channelnumber) {
                if(file_exists('/var/spool/asterisk/outgoing/room_'.$roomname.'_group_'.$groupname.'_user_'.$user->intid.'.call')) unlink('/var/spool/asterisk/outgoing/room_'.$roomname.'_group_'.$groupname.'_user_'.$user->intid.'.call');
              } else {
                if($cancell&&$room->active&&file_exists('/var/spool/asterisk/outgoing/room_'.$roomname.'_group_'.$groupname.'_user_'.$user->intid.'.call')) unlink('/var/spool/asterisk/outgoing/room_'.$roomname.'_group_'.$groupname.'_user_'.$user->intid.'.call');
              }
            }
          }
        }
      }
      return 0;
    }
    if(!$roomenabled) {
      $this->agi->exec('Playback','conf-invalidpin');
      $this->agi->set_variable('CDR(action)','invalidpin');
      return 1; //unknown action
    }
    $this->agi->set_variable('CONFROOM',$confroom);
    $this->agi->set_variable('ROOMPROFILE',$roomprofile);
    $this->agi->set_variable('USERPROFILE',$userprofile);
    $this->agi->set_variable('MENUPROFILE',$menuprofile);

    //collect room and group data
    $this->agi->set_variable('CONFBRIDGE(bridge,template)',$roomprofile);
    $language=$this->agi->get_variable('CHANNEL(language)',true);
    $this->agi->set_variable('CONFBRIDGE(bridge,language)',$language);
    $this->agi->set_variable('CONFBRIDGE(user,template)',$userprofile);

    while(true) {
      if($bridge) {
        $confsubroom=$this->agi->get_variable('NEWBRIDGE',true);
        if($confsubroom=='') $confsubroom=$confroom.'&'.$this->agi->get_variable('CALLERID(num)',true);
        $this->agi->verbose('Confsubroom name is '.$confsubroom,4);
        $connline=$this->agi->get_variable('CONNECTEDLINE(all)',true);
        $this->agi->set_variable('CDR(dclid)',$connline);
        $this->agi->set_variable('CDR(action)','""');
        $this->agi->set_variable('ISOLATED', '0');
        $this->agi->set_variable('CONFBRIDGE(user,quiet)','yes');
        $this->agi->set_variable('CONFBRIDGE(user,announce_user_count)','no');
        $this->agi->set_variable('CONFBRIDGE(user,music_on_hold_when_empty)','yes');
        $this->agi->set_variable('CONFBRIDGE(user,wait_marked)','no');
        $this->agi->set_variable('CONFBRIDGE(user,end_marked)','no');
        $this->agi->exec('Confbridge', trim($confsubroom.$this->agi->option_delim.$this->agi->option_delim.$this->agi->option_delim.$menuprofile));
        $this->agi->set_variable('NEWBRIDGE','');
        $this->agi->set_variable('CONFBRIDGE(user,clear)','');
        $this->agi->set_variable('CONFBRIDGE(user,template)',$userprofile);
//        $this->agi->set_variable('CONFBRIDGE(user,wait_marked)','no');
        $this->agi->set_variable('CONFBRIDGE(user,announce_user_count)','no');
        $monfile=$this->agi->get_variable('MIXMONITOR_FILENAME',true);
        if($monfile!='') $this->agi->set_variable('CDR(recordingfile)',$monfile);
        $parties=$this->agi->get_variable('CONFBRIDGE_INFO(parties,'.$confsubroom.')',true);
        if($parties==1) { // if last user in room - return all
          $this->agi->verbose('Last user in subroom '.$confsubroom,4);
          system('/usr/sbin/asterisk -rx \'confbridge kick "'.$confsubroom.'" all\'');
        }
        $bridge=false;
      } else {
        $connline=$this->agi->get_variable('CONNECTEDLINE(all)',true);
        $this->agi->set_variable('CDR(dclid)',$connline);
        $this->agi->exec('Confbridge', trim($confroom.$this->agi->option_delim.$this->agi->option_delim.$this->agi->option_delim.$menuprofile));
        $monfile=$this->agi->get_variable('MIXMONITOR_FILENAME',true);
        if($monfile!='') $this->agi->set_variable('CDR(recordingfile)',$monfile);
        $status=$this->agi->get_variable('CONFBRIDGE_RESULT',true);
        $this->agi->verbose('Confroom '.$confroom.' leave state with '.$status,4);
        if($status=='LEAVE') $bridge=true; else break;
      }
    }

    $nopin=$this->agi->get_variable('NOPIN',true);
    $parties=$this->agi->get_variable('CONFBRIDGE_INFO(parties,'.$confroom.')',true);
    if(!$nopin) {
      if($parties==0) { //reset temporary pin if first user
        self::setDB('conf_'.$confroom,'pin','');
      }
      $roomtmppin=self::getDB('conf_'.$confroom,'pin');
      if($requirepin&&$roomtmppin) { //if temporary pin required
        $this->agi->set_variable('CONFBRIDGE(user,pin)',$roomtmppin);
      }
    }
    $this->agi->set_variable('NOPIN','1');

    //set success state
    $roomname=$confroom;
    if(($parties==0)&&isset($rooms[$roomname])) {
      $room = &$rooms[$roomname];
      foreach($room->users as $user) {
        $roomusernumber=explode('/',$user->chan);
        $chantype=$roomusernumber[0];
        $roomusernumber=array_pop($roomusernumber);
        if(($chantype=='DAHDI')&&in_array($roomusernumber[0],array('U','I','N','L','S','V','R'))) {
          $roomusernumber=substr($roomusernumber,1);
        }
        if(($roomusernumber==$usernumber)||($roomusernumber==$channelnumber)) {
          if(isset($request_data->incoming)&&file_exists('/var/spool/asterisk/outgoing/room_'.$roomname.'_user_'.$user->intid.'.call')) unlink('/var/spool/asterisk/outgoing/room_'.$roomname.'_user_'.$user->intid.'.call');
          self::setDB('conf_'.$roomname.'/user_'.$user->intid,'failed',0);
        }
      }
      foreach($room->groups as $groupname) {
        $groupname=$groupname->id;
        foreach($groups->$groupname->users as $user) {
          $groupusernumber=explode('/',$user->chan);
          $chantype=$groupusernumber[0];
          $groupusernumber=array_pop($groupusernumber);
          if(($chantype=='DAHDI')&&in_array($groupusernumber[0],array('U','I','N','L','S','V','R'))) {
            $groupusernumber=substr($groupusernumber,1);
          }
          if(($groupusernumber==$usernumber)||($groupusernumber==$channelnumber)) {
            if(isset($request_data->incoming)&&file_exists('/var/spool/asterisk/outgoing/room_'.$roomname.'_group_'.$groupname.'_user_'.$user->intid.'.call')) unlink('/var/spool/asterisk/outgoing/room_'.$roomname.'_group_'.$groupname.'_user_'.$user->intid.'.call');
            self::setDB('conf_'.$roomname.'/group_'.$groupname.'/user_'.$user->intid,'failed',0);
          }
        }
      }
    }

    return 0;
  }

  public function getConfBridgeChannels() {
    $tmpchannels = $this->ami->send_request('Command', array('Command' => 'core show channels concise'));
    if(is_array($tmpchannels)) {
      if(isset($tmpchannels['data'])) {
        $tmpchannels = explode("\n",$tmpchannels['data']);
      } else {
        $tmpchannels = array();
      }
    } else {
      $tmpchannels = explode("\n",$tmpchannels);
    }
    $channels=array();
    foreach($tmpchannels as $channel) {
      $channelname = trim(explode('!',$channel)[0]);
      if(strpos($channelname, 'CBRec/conf-')===0) $channels[]=$channelname;
    }
    return $channels;
  }

  public function getRoomList() {
    $parameters = array();
    $result = $this->ami->send_request('ConfbridgeListRooms', $parameters);
    $channels = array();
    $tmpchannels = $this->getConfBridgeChannels();
    foreach($tmpchannels as $channel) {
      $channels[] = preg_replace('/-uid-.*$/','\1', $channel);
    }
    $rooms = array();
    if(isset($result['Event'])&&($result['Event'] == 'ConfbridgeListRoomsComplete')) {
      foreach($result['events'] as $event) {
        if(strpos($event['Conference'],'&')===false) {
          $room = new \stdClass();
          $room->number=$event['Conference'];
          $room->count=$event['Parties'];
          $room->marked=$event['Marked'];
          $room->locked=$event['Locked']=='Yes';
          $room->muted=$event['Muted']=='Yes';
          $room->active=false;
          $room->recording=in_array('CBRec/conf-'.$room->number, $channels);
          $rooms[]=$room;
        }
      }
      foreach($result['events'] as $event) {
        if(strpos($event['Conference'],'&')!==false) {
          $found = false;
          foreach($rooms as $primaryroom) {
            if(strpos($event['Conference'],$primaryroom->number.'&')!==false) {
              $found=true;
              break;
            }
          }
          if(!$found) {
            $room = new \stdClass();
            $room->number=substr($event['Conference'],0,strpos($event['Conference'],'&'));
            $room->count=0;
            $room->marked=0;
            $room->locked=false;
            $room->muted=false;
            $room->recording=false;
            $rooms[]=$room;
          }
        }
      }
    }
    return $rooms;
  }

  public function getSubRoomList($roomid) {
    $parameters = array();
    $result = $this->ami->send_request('ConfbridgeListRooms', $parameters);
    $channels = array();
    $tmpchannels = $this->getConfBridgeChannels();
    foreach($tmpchannels as $channel) {
      $channels[] = preg_replace('/-uid-.*$/','\1', $channel);
    }
    $rooms = array();
    if(isset($result['Event'])&&($result['Event']=='ConfbridgeListRoomsComplete')) {
      foreach($result['events'] as $event) {
        if(strpos($event['Conference'],$roomid.'&')===0) {
          $room = new \stdClass();
          $room->number=$event['Conference'];
          $room->count=$event['Parties'];
          $room->marked=$event['Marked'];
          $room->locked=$event['Locked']=='Yes';
          $room->muted=$event['Muted']=='Yes';
          $room->recording=in_array('CBRec/conf-'.$room->number, $channels);
          $rooms[]=$room;
        }
      }
    }
    return $rooms;
  }

  public function getRoomInfo($room) {
    $result = new \stdClass();
    $profile = $this->ami->Command("confbridge list \"$room\"");
    if(is_array($profile)) {
      if(isset($profile['data'])) $profile=$profile['data'];
      else return $result;
    }
    $profile = explode("\n", $profile);
    array_shift($profile);
    array_shift($profile);
    if(isset($profile[0])) {
      $profile = trim(substr($profile[0],54,18));
      $roomdefaults = '{
        "max_members": "0",
        "record_conference": "!yes",
        "record_file": "",
        "internal_sample_rate": "auto",
        "mixing_interval": "20",
        "video_mode": "none",
        "language": "en"
      }';
      $ini = new \INIProcessor('/etc/asterisk/confbridge.conf');
      if(isset($ini->$profile)) {
        $result = $ini->$profile->getDefaults($roomdefaults);
      }
      $result->profile=$profile;
    }
    return $result;
  }

  public function getChannels() {
    $parameters = array();
    $result = $this->ami->send_request('CoreShowChannels', $parameters);
    $users = array();
    if(isset($result['Event'])&&($result['Event']=='CoreShowChannelsComplete')) {
      foreach($result['events'] as $event) {
        $users[] = $event['Channel'];
      }
    }
    return $users;
  }

  public function getRingUsers($room) {
    $parameters = array();
    $result = $this->ami->send_request('CoreShowChannels', $parameters);
    $users = array();
    if(isset($result['Event'])&&($result['Event']=='CoreShowChannelsComplete')) {
      foreach($result['events'] as $event) {
        if($event['ChannelState']==5) {
          $confnum=$this->ami->GetVar($event['Channel'],'CONFROOM');
          if(isset($confnum['Value'])&&($confnum['Value']==$room)) {
            $user = new \stdClass();
            $user->confnum=$confnum['Value'];
            $confnum=$this->ami->GetVar($event['Channel'],'USERPROFILE');
            if(isset($confnum['Value'])) {
              $user->profile = $confnum['Value'];
            } else {
              $user->profile = 'default_user';
            }
            $extnum=$this->ami->GetVar($event['Channel'],'EXTENDNUM');
            if(isset($extnum['Value'])) {
              $user->extnum = $extnum['Value'];
            } else {
              $user->extnum = '';
            }
            $cidnum=$this->ami->GetVar($event['Channel'],'CIDNUM');
            if(isset($cidnum['Value'])) {
              $user->number = $cidnum['Value'];
            } else {
              $user->number = $event['CallerIDNum'];
            }
            $cidname=$this->ami->GetVar($event['Channel'],'CIDNAME');
            if(isset($cidnum['Value'])) {
              $user->callerid = $cidname['Value'];
            } else {
              $user->callerid = $event['CallerIDName'];
            }
            $user->channel = $event['Channel'];
            $users[]=$user;
          }
        }
      }
    }
    return $users;
  }

  public function getRoomUsers($room) {
    $parameters = array('Conference' => $room);
    $result = $this->ami->send_request('ConfbridgeList', $parameters);
    $users = array();
    if(isset($result['Event'])&&($result['Event']=='ConfbridgeListComplete')) {
      foreach($result['events'] as $event) {
        $user = new \stdClass();
        $user->number = $event['CallerIDNum'];
        $user->name = $event['CallerIDName'];
        $user->channel = $event['Channel'];
        $user->admin = $event['Admin']=='Yes';
        $user->marked = $event['MarkedUser']=='Yes';
        $user->waitmarked = $event['WaitMarked']=='Yes';
        $user->endmarked = $event['EndMarked']=='Yes';
        $user->waiting = $event['Waiting']=='Yes';
        $user->muted = $event['Muted']=='Yes';
        if(isset($event['Talking'])) $user->talk = $event['Talking']=='Yes'; //Support for asterisk 13.21 and above
        if(isset($event['VolumeIn'])) $user->volumein = $event['VolumeIn']; //Support for asterisk 13.21 and above
        if(isset($event['VolumeOut'])) $user->volumeout = $event['VolumeOut']; //Support for asterisk 13.21 and above
        $user->isolated = $this->ami->GetVar($event['Channel'],'ISOLATED')['Value']==1;
        $user->extnum = $this->ami->GetVar($event['Channel'],'EXTENDNUM')['Value'];
        $user->start = time()-$event['AnsweredTime'];
        $users[]=$user;
      }
    }
    return $users;
  }

  public function getPin($room) {
    return self::getDB('conf_'.$room, 'pin');
  }

  public function setPin($room, $pin) {
    return self::setDB('conf_'.$room, 'pin', $pin);
  }

  public function configReload() {
    $this->ami->send_request('Command', array('Command' => 'module reload app_confbridge'));
    return true;
  }

  public function kickUsers($room, $users) {
    if(strpos($room,'&')!==false) {
      $roomname=substr($room, 0, strpos($room, '&'));
    } else {
      $roomname=$room;
    }
    $roominfo=$this->cache->get('room_'.str_replace(' ','_',$roomname));
    if(!is_object($roominfo)) return array();
    $requests=array();
    foreach($users as $user) {
      if($user->groupid=='false') $user->groupid='';
      $request=new \stdClass();
      $user->id=null;
      $request->dialing=true;
      if(strpos($room,'&')!==false) {
        $user->online=true;
      } else {
        foreach($roominfo->users as $userkey => $userentry) {     
          if(($userentry->channel==$user->user)&&($userentry->groupid==$user->groupid)) {
            if(isset($userentry->id)) $user->id=$userentry->id;
            $user->online=$userentry->online||(isset($userentry->dialing)&&$userentry->dialing);
            $request->dialing=isset($userentry->dialing)&&$userentry->dialing;
          }
        }
      }
      $request->userid=$user->id;
      $request->groupid=$user->groupid;
      if($user->online) {
        $parameters = array('Channel' => $user->user);
        $parameters2 = array('Flags' => 'r');
        $this->ami->send_request('CDRFork', array_merge($parameters, $parameters2), true);
        $parameters2 = array('Variable' => 'action', 'Value' => 'kick');
        $this->ami->send_request('CDRSet', array_merge($parameters, $parameters2), true);
        if(strpos($room,'&')===false) {
          $user->kick=false;
        }
        if($user->kick) {
          $parameters2 = array('Conference' => $room);
          $request->params=array_merge($parameters, $parameters2);
          $request->action='ConfbridgeKick';
        }
        if(!$user->kick) {
          if($parameters) {
            $request->params=$parameters;
            $request->action='Hangup';
          }
        }
      }
      $request->reset=strpos($room,'&')===false;
      $requests[]=$request;
    }
    foreach($requests as $request) {
      if(isset($request->action)) {
        $res = $this->ami->send_request($request->action, $request->params, true);
      }
      if($request->reset) {
        if($request->groupid&&$request->userid) {
          if(file_exists('/var/spool/asterisk/outgoing/room_'.$roomname.'_group_'.$request->groupid.'_user_'.$request->userid.'.call')) unlink('/var/spool/asterisk/outgoing/room_'.$roomname.'_group_'.$request->groupid.'_user_'.$request->userid.'.call');
        } elseif($request->userid) {
          if(file_exists('/var/spool/asterisk/outgoing/room_'.$roomname.'_user_'.$request->userid.'.call')) unlink('/var/spool/asterisk/outgoing/room_'.$roomname.'_user_'.$request->userid.'.call');
        }
      }
    }
    foreach($requests as $request) {
      if($request->reset) {
        if($request->groupid&&$request->userid) {
          self::setDB('conf_'.$roomname.'/group_'.$request->groupid.'/user_'.$request->userid, 'failed',($request->dialing)?2:0);
        } elseif($request->userid) {
          self::setDB('conf_'.$roomname.'/user_'.$request->userid,'failed',($request->dialing)?2:0);
        }
      }
    }
    return true;
  }

  public function kickUser($room, $user) {
    $parameters = array('Conference' => $room, 'Channel' => $user);
    $res = $this->ami->send_request('ConfbridgeKick', $parameters);
    if(($res['Response']=='Success')&&(strpos($room,'&')===false)) {
      $parameters = array('Channel' => $user, 'Flags' => 'r');
      $this->ami->send_request('CDRFork', $parameters);
      $parameters = array('Channel' => $user, 'Variable' => 'action', 'Value' => 'kick');
      $this->ami->send_request('CDRSet', $parameters);
    } else {
      error_log('failed '.$user.' not in bridge yet, hangup him');
      $parameters = array('Channel' => $user, 'Context' => 'confbridge', 'Exten' => 'h', 'Priority' => 1);
      $res=$this->ami->send_request('Redirect', $parameters);
    }
    return $res['Response']=='Success';
  }

  public function setUserVolume($room, $user, $volumein, $volumeout) {
    $parameters = array('Conference' => $room, 'Channel' => $user, 'VolumeIn' => $volumein, 'VolumeOut' => $volumeout);
    $res = $this->ami->send_request('ConfbridgeSetVolume', $parameters);
    return $res['Response']=='Success';
  }

  public function hangupUser($user) {
    $parameters = array('Channel' => $user, 'Context' => 'confbridge', 'Exten' => 'h', 'Priority' => 1);
    $this->ami->send_request('Redirect', $parameters);
    return true;
  }

  public function isolateUser($room, $user) {
    $isolated = $this->ami->GetVar($user,'ISOLATED')['Value'];
    if($isolated==1) {
      $this->ami->SetVar($user,'ISOLATED', 0);
      $parameters = array('Direction' => 'all', 'State' => 'off', 'Channel' => $user);
      $this->ami->send_request('MuteAudio', $parameters);
      $parameters = array('Channel' => $user, 'Flags' => 'r');
      $this->ami->send_request('CDRFork', $parameters);
      $parameters = array('Channel' => $user, 'Variable' => 'action', 'Value' => 'unhold');
      $this->ami->send_request('CDRSet', $parameters);
    } else {
      $this->ami->SetVar($user,'ISOLATED', 1);
      $parameters = array('Direction' => 'all', 'State' => 'on', 'Channel' => $user);
      $this->ami->send_request('MuteAudio', $parameters);
      $parameters = array('Channel' => $user, 'Flags' => 'r');
      $this->ami->send_request('CDRFork', $parameters);
      $parameters = array('Channel' => $user, 'Variable' => 'action', 'Value' => 'hold');
      $this->ami->send_request('CDRSet', $parameters);
    }
    return true;
  }

  public function inviteUser($room, $room_profile, $profile, $menu, $user, $callerid, $extendnum, $extenddelay) {
    $chanarr=explode('/',$user);
    $number = array_pop($chanarr);
    $alias=self::getDB('conf_'.$room, 'alias');
    if(trim($alias)=='') {
      $users=self::getAsteriskPeers();
      if(count($chanarr)>1) {
        $login=array_pop($chanarr);
      } else {
        $login=$number;
      }
      foreach($users as $v) {
        if($v->login==$login) {
          $alias = $v->name.' <'.$v->number.'>';
          break;
        }
      }
    }
    $parameters = array('Channel' => $user, 'Context' => 'confbridge', 'MaxRetries' => 0, 'Extension' => $room, 'Priority' => 1, 'Set' => array('CONFGROUP=', 'CONFROOM='.$room, 'CIDNUM='.$number, 'CIDNAME='.$callerid, 'NOPIN=1', 'EXTENDDELAY='.$extenddelay, 'EXTENDNUM='.addslashes($extendnum), 'ROOMPROFILE='.$room_profile, 'USERPROFILE='.$profile, 'MENUPROFILE='.$menu), 'CallerID' => $alias, 'RetryTime' => 10, 'WaitTime' => 30);
    //    $this->ami->send_request('Originate', $parameters);
    $descriptorspec = array(
      0 => array("pipe", "r"),
      1 => array("pipe", "w"),
      2 => array("pipe", "w")
    );

    $cwd = dirname(__FILE__);
    $env = array('roomid' => $room, 'user' => 'u'.$number, 'groupid' => '', 'data' => json_encode($parameters), 'schedule' => null);

    $process = proc_open($cwd.'/.call', $descriptorspec, $pipes, $cwd, $env);

    if(is_resource($process)) {
      fclose($pipes[0]);

      $log = stream_get_contents($pipes[1]);
      if($log!='') error_log($log);
      fclose($pipes[1]);
      fclose($pipes[2]);

      $return_value = proc_close($process);
    }
    $result=$return_value===0;

    return $result;
  }

  public function mergeUsers($room, $users) {
    if(is_array($users)&&(count($users)>1)) {
      $brid=substr($users[0],strrpos($users[0],'-')+1);
      while($peer = array_pop($users)) {
        $this->ami->SetVar($peer,'CONFROOM',$room);
        $parameters = array('Conference' => $room, 'Channel' => $peer);
        $this->ami->send_request('ConfbridgeUnmute', $parameters);
        $parameters = array('Direction' => 'all', 'State' => 'off', 'Channel' => $peer);
        $this->ami->send_request('MuteAudio', $parameters);
        $this->ami->SetVar($peer,'NEWBRIDGE',$room.'&'.$brid);
        $parameters = array('Conference' => $room, 'Channel' => $peer);
        $this->ami->send_request('ConfbridgeSafeLeave', $parameters);
      }
      return true;
    }
    return false;
  }

  public function muteRoom($room, $user=null) {
    $parameters = array('Conference' => $room, 'Channel' => ($user==null)?'participants':$user);
    $this->ami->send_request('ConfbridgeMute', $parameters);
    if($user!=null) {
      $parameters = array('Channel' => $user, 'Flags' => 'r');
      $this->ami->send_request('CDRFork', $parameters);
      $parameters = array('Channel' => $user, 'Variable' => 'action', 'Value' => 'mute');
      $this->ami->send_request('CDRSet', $parameters);
    } else {
      $result = $this->ami->send_request('ConfbridgeList', $parameters);
      if(isset($result['Event'])&&($result['Event']=='ConfbridgeListComplete')) {
        foreach($result['events'] as $event) {
          if($event['MarkedUser']!='Yes') {
            $parameters = array('Channel' => $event['Channel'], 'Flags' => 'r');
            $this->ami->send_request('CDRFork', $parameters);
            $parameters = array('Channel' => $event['Channel'], 'Variable' => 'action', 'Value' => 'mute');
            $this->ami->send_request('CDRSet', $parameters);
          }
        }
      }
    }
    return true;
  }

  public function unmuteRoom($room, $user=null) {
    $parameters = array('Conference' => $room, 'Channel' => ($user==null)?'participants':$user);
    $this->ami->send_request('ConfbridgeUnmute', $parameters);
    if($user!=null) {
      $parameters = array('Channel' => $user, 'Flags' => 'r');
      $this->ami->send_request('CDRFork', $parameters);
      $parameters = array('Channel' => $user, 'Variable' => 'action', 'Value' => 'unmute');
      $this->ami->send_request('CDRSet', $parameters);
    } else {
      $result = $this->ami->send_request('ConfbridgeList', $parameters);
      if(isset($result['Event'])&&($result['Event']=='ConfbridgeListComplete')) {
        foreach($result['events'] as $event) {
          $parameters = array('Channel' => $event['Channel'], 'Flags' => 'r');
          $this->ami->send_request('CDRFork', $parameters);
          $parameters = array('Channel' => $event['Channel'], 'Variable' => 'action', 'Value' => 'mute');
          $this->ami->send_request('CDRSet', $parameters);
        }
      }
    }
    return true;
  }

  public function lockRoom($room) {
    $parameters = array('Conference' => $room);
    $this->ami->send_request('ConfbridgeLock', $parameters);
    return true;
  }

  public function unlockRoom($room) {
    $parameters = array('Conference' => $room);
    $this->ami->send_request('ConfbridgeUnlock', $parameters);
    return true;
  }

  public function startRecordRoom($room) {
    $parameters = array('Conference' => $room);
    $this->ami->send_request('ConfbridgeStartRecord', $parameters);
    $channels = self::getConfBridgeChannels();
    $roomchannel=null;
    foreach($channels as $channel) {
      if(strpos($channel, 'CBRec/conf-'.$room)===0) $roomchannel=$channel;
    }
    if($roomchannel) {
      $monfile = $this->ami->GetVar($roomchannel,'MIXMONITOR_FILENAME')['Value'];
      $result = $this->ami->send_request('ConfbridgeList', $parameters);
      if(isset($result['Event'])&&($result['Event']=='ConfbridgeListComplete')) {
        foreach($result['events'] as $event) {
          $this->ami->SetVar($event['Channel'], 'CDR(recordingfile)', $monfile);
        }
      }
    }
    return true;
  }

  public function stopRecordRoom($room) {
    $parameters = array('Conference' => $room);
    $this->ami->send_request('ConfbridgeStopRecord', $parameters);
    return true;
  }

  public static function getRooms() {
    $room_cnt=self::getDB('conf_persist', 'count');
    $list = array();
    for($i=1; $i<=$room_cnt; $i++) {
      $list[]=self::getDB('conf_persist', 'room_'.$i);
    }
    return $list;
  }

  public static function getGroups() {
    $group_cnt=self::getDB('group_persist', 'count');
    $list = array();
    for($i=1; $i<=$group_cnt; $i++) {
      $list[]=self::getDB('group_persist', 'group_'.$i);
    }
    return $list;
  }

  public static function setRooms($rooms) {
    $result=true;
    $i=1;
    foreach($rooms as $room) {
      $result&=self::setDB('conf_persist', 'room_'.$i, $room);
      $i++;
    }
    $result&=self::setDB('conf_persist', 'count', count($rooms));
    return $result;
  }

  public static function setGroups($groups) {
    $result=true;
    $i=1;
    foreach($groups as $group) {
      $result&=self::setDB('group_persist', 'group_'.$i, $group);
      $i++;
    }
    $result&=self::setDB('group_persist', 'count', count($groups));
    return $result;
  }

  public function getPersistentRoom($aroom) {
    if(self::getDB('conf_'.$aroom, 'profile')=='') return null;
    $room = new \stdClass();
    $room->pin=self::getDB('conf_'.$aroom, 'persist_pin');
    $room->maxcount=self::getDB('conf_'.$aroom, 'maxcount');
    $room->regex=self::getDB('conf_'.$aroom, 'regex');
    $room->alias=self::getDB('conf_'.$aroom, 'alias');
    $room->profile=self::getDB('conf_'.$aroom, 'profile');
    $room->activatebefore=self::getDB('conf_'.$aroom, 'activatebefore');
    if($room->activatebefore=='') $room->activatebefore = '0';
    $room->days=self::getDB('conf_'.$aroom, 'days');
    $room->enabled=self::getDB('conf_'.$aroom, 'enabled')=='true';
    $room->activeoncall=self::getDB('conf_'.$aroom, 'activeoncall')=='true';
    $room->useinstatistics=self::getDB('conf_'.$aroom, 'useinstatistics')!='false';
    $room->disallowcallout=self::getDB('conf_'.$aroom, 'disallowcallout')=='true';
    $room->from=self::getDB('conf_'.$aroom, 'from');
    $room->to=self::getDB('conf_'.$aroom, 'to');
    $uoffset=self::getDB('conf_'.$aroom, 'offset');
    $now = new \DateTime();
    $ftime=explode(':', $room->from);
    $ttime=explode(':', $room->to);
    $fschedule = new \DateTime();
    $fschedule->setTimezone(new \DateTimeZone('GMT'));
    $fschedule->setTime($ftime[0],$ftime[1]);
    $tschedule = new \DateTime();
    $tschedule->setTimezone(new \DateTimeZone('GMT'));
    $tschedule->setTime($ttime[0],$ttime[1]);
    $midnight = new \DateTime();
    $midnight->setTimezone(new \DateTimeZone('GMT'));
    $midnight->setTime(23,59);
    $checkdiff=false;
    if(($now->getTimestamp()>$fschedule->getTimestamp())&&($now->getTimestamp()<$midnight->getTimestamp())&&($fschedule->getTimestamp()>$tschedule->getTimestamp())) {
      $checkdiff=true;
      $tschedule->add(new \DateInterval('P1D'));
    } elseif($fschedule->getTimestamp()>$tschedule->getTimestamp()) $fschedule->sub(new \DateInterval('P1D'));
    $flschedule = clone $fschedule;
    if(isset($uoffset)&&$uoffset) {
      if($uoffset>0) {
        $flschedule->add(new \DateInterval('PT'.$uoffset.'M'));
      } else {
        $flschedule->sub(new \DateInterval('PT'.(-1*$uoffset).'M'));
      }
    }
    if($checkdiff) {
      if($flschedule->format('d')>$fschedule->format('d')) {
        $flschedule->sub(new \DateInterval('P1D'));
        $fschedule->sub(new \DateInterval('P1D'));
        $tschedule->sub(new \DateInterval('P1D'));
      } elseif($flschedule->format('d')<$fschedule->format('d')) {
        $flschedule->add(new \DateInterval('P1D'));
        $fschedule->add(new \DateInterval('P1D'));
        $tschedule->add(new \DateInterval('P1D'));
      }
    }
    $room->active=($room->enabled&&($now->getTimestamp()>=$fschedule->getTimestamp()-$room->activatebefore)&&($now->getTimestamp()<=$tschedule->getTimestamp())&&(($room->days!='')&&in_array(strtolower($flschedule->format('D')),explode('&',$room->days))));
    if($this->agi&&$room->activeoncall) {
      if($this->agi->get_variable('CONFBRIDGE_INFO(parties,'.$aroom.')', true)>0) {
        $room->active = true;
        $this->cache->delete('confroomlist');
      }
    } else
    if($this->ami&&$room->activeoncall) {
      $roominfo=$this->getRoomInfo($aroom);
      if(isset($roominfo->profile)) {
        $room->active = true;
        $this->cache->delete('confroomlist');
      }
    }
    $user_cnt=self::getDB('conf_'.$aroom, 'users');
    $room->users = array();
    for($i=1; $i<=$user_cnt; $i++) {
      $user = new \stdClass();
      $user->intid=$i;
      $user->pin=self::getDB('conf_'.$aroom, 'user_'.$i.'/pin');
      $user->callerid=self::getDB('conf_'.$aroom, 'user_'.$i.'/callerid');
      $user->profile=self::getDB('conf_'.$aroom, 'user_'.$i.'/profile');
      $user->menu=self::getDB('conf_'.$aroom, 'user_'.$i.'/menu');
      $user->chan=self::getDB('conf_'.$aroom, 'user_'.$i.'/chan');
      $user->delay=self::getDB('conf_'.$aroom, 'user_'.$i.'/delay');
      $user->failed=self::getDB('conf_'.$aroom, 'user_'.$i.'/failed');
      $user->auto=self::getDB('conf_'.$aroom, 'user_'.$i.'/auto')=='true';
      $user->extnum=self::getDB('conf_'.$aroom, 'user_'.$i.'/extnum');
      $user->extdelay=self::getDB('conf_'.$aroom, 'user_'.$i.'/extdelay');
      $user->retry=self::getDB('conf_'.$aroom, 'user_'.$i.'/retry');
      $user->retries=self::getDB('conf_'.$aroom, 'user_'.$i.'/retries');
      $user->timeout=self::getDB('conf_'.$aroom, 'user_'.$i.'/timeout');
      if($user->timeout=='') $user->timeout=10;
      if($user->retries=='') $user->retries=2;
      if($user->retry=='') $user->retry=5;
      $room->users[]=$user;
    }
    $group_cnt=self::getDB('conf_'.$aroom, 'groups');
    $room->groups = array();
    for($i=1; $i<=$group_cnt; $i++) {
      $group = new \stdClass();
      $group->id=self::getDB('conf_'.$aroom, 'group_'.$i.'/id');
      $room->groups[]=$group;
    }
    return $room;
  }

  public function getPersistentGroup($agroup, $aroom=null) {
    $group = new \stdClass();
    $group->users = array();
    $user_cnt=self::getDB('group_'.$agroup, 'users');
    for($i=1; $i<=$user_cnt; $i++) {
      $user = new \stdClass();
      $user->intid=$i;
      $user->pin=self::getDB('group_'.$agroup, 'user_'.$i.'/pin');
      $user->callerid=self::getDB('group_'.$agroup, 'user_'.$i.'/callerid');
      $user->profile=self::getDB('group_'.$agroup, 'user_'.$i.'/profile');
      $user->menu=self::getDB('group_'.$agroup, 'user_'.$i.'/menu');
      $user->chan=self::getDB('group_'.$agroup, 'user_'.$i.'/chan');
      $user->delay=self::getDB('group_'.$agroup, 'user_'.$i.'/delay');
      if($aroom) $user->failed=self::getDB('conf_'.$aroom, 'group_'.$agroup.'/user_'.$i.'/failed');
      $user->auto=self::getDB('group_'.$agroup, 'user_'.$i.'/auto')=='true';
      $user->extnum=self::getDB('group_'.$agroup, 'user_'.$i.'/extnum');
      $user->extdelay=self::getDB('group_'.$agroup, 'user_'.$i.'/extdelay');
      $user->retry=self::getDB('group_'.$agroup, 'user_'.$i.'/retry');
      $user->retries=self::getDB('group_'.$agroup, 'user_'.$i.'/retries');
      $user->timeout=self::getDB('group_'.$agroup, 'user_'.$i.'/timeout');
      if($user->timeout=='') $user->timeout=10;
      if($user->retries=='') $user->retries=2;
      if($user->retry=='') $user->retry=5;
      $group->users[]=$user;
    }
    return $group;
  }

  public function removePersistentRoom($aroom) {
    $rooms=self::getRooms();
    if(($key = array_search($aroom, $rooms)) !== false) {
      $pin=self::getDB('conf_'.$aroom, 'pin');
      $result=self::deltreeDB('conf_'.$aroom);
      self::setDB('conf_'.$aroom, 'pin', $pin);
      unset($rooms[$key]);
      self::setRooms($rooms);
      $user_cnt=self::getDB('conf_'.$aroom, 'users');
      for($i=1; $i<=$user_cnt; $i++) {
        $result&=self::deltreeDB('conf_'.$aroom.'/user_'.$i);
      }
      $result&=self::deltreeDB('conf_'.$aroom, 'users');
      return $result;
    }
    return false;
  }

  public function removePersistentGroup($agroup) {
    $groups=self::getGroups();
    if(($key = array_search($agroup, $groups)) !== false) {
      unset($groups[$key]);
      $result=self::setGroups($groups);
      $user_cnt=self::getDB('group_'.$agroup, 'users');
      for($i=1; $i<=$user_cnt; $i++) {
        $result&=self::deltreeDB('group_'.$agroup.'/user_'.$i);
      }
      self::deltreeDB('group_'.$agroup, 'users');
      $roomlist = self::getRooms();
      foreach($roomlist as $room) { 
        $grp_cnt=self::getDB('conf_'.$room, 'groups');
        $found=false;
        $groupsdata=array();
        for($i=1; $i<=$grp_cnt; $i++) {
          $groupdata=new stdClass();
          $groupdata->id=self::getDB('conf_'.$room.'/group_'.$i, 'id');
          if($groupdata->id==$agroup) $found=true; else $groupsdata[]=$groupdata;
        }
        if($found) {
          for($i=1; $i<=$grp_cnt; $i++) {
            $result&=self::deltreeDB('conf_'.$room.'/group_'.$i);
          }
          self::setDB('conf_'.$room, 'groups', count($groupsdata));
          for($i=1; $i<=count($groupsdata); $i++) {
            self::setDB('conf_'.$room.'/group_'.$i, 'id', $groupsdata[$i-1]->id);
          }
        }
      }
      return $result;
    }
    return false;
  }

  public function savePersistentRoom($old_id, $new_id, $palias, $ppin, $pcount, $regex, $profile, $users, $from, $to, $offset, $days, $enabled, $groups, $activeoncall, $disallowcallout, $useinstatistics, $activatebefore) {
    $rooms = self::getRooms();
    $result=true;
    if((!$old_id)&&(in_array($new_id, $rooms))) return false;
    if(($key = array_search($old_id, $rooms)) !== false) {
      if($old_id!=$new_id) {
        if(in_array($new_id, $rooms)) return false;
        $pin=self::getDB('conf_'.$old_id, 'pin');
        $result&=self::deltreeDB('conf_'.$old_id);
        self::setDB('conf_'.$new_id, 'pin', $pin);
        unset($rooms[$key]);
        $rooms[]=$new_id;
        self::setRooms($rooms);
      } else {
        $user_cnt=self::getDB('conf_'.$old_id, 'users');
        for($i=1; $i<=$user_cnt; $i++) {
          $result&=self::deltreeDB('conf_'.$old_id.'/user_'.$i);
        }
        self::deltreeDB('conf_'.$old_id, 'users');
        $grp_cnt=self::getDB('conf_'.$old_id, 'groups');
        for($i=1; $i<=$grp_cnt; $i++) {
          $result&=self::deltreeDB('conf_'.$old_id.'/group_'.$i);
        }
        self::deltreeDB('conf_'.$old_id, 'groups');
      }
    } else {
      $rooms[]=$new_id;
      self::setRooms($rooms);
    }
    self::setDB('conf_'.$new_id, 'persist_pin', $ppin);
    self::setDB('conf_'.$new_id, 'maxcount', $pcount);
    self::setDB('conf_'.$new_id, 'regex', $regex);
    self::setDB('conf_'.$new_id, 'alias', $palias);
    self::setDB('conf_'.$new_id, 'activatebefore', $activatebefore);
    self::setDB('conf_'.$new_id, 'profile', $profile);
    self::setDB('conf_'.$new_id, 'days', $days);
    self::setDB('conf_'.$new_id, 'enabled', $enabled);
    self::setDB('conf_'.$new_id, 'activeoncall', $activeoncall);
    self::setDB('conf_'.$new_id, 'disallowcallout', $disallowcallout);
    self::setDB('conf_'.$new_id, 'useinstatistics', $useinstatistics);
    self::setDB('conf_'.$new_id, 'from', $from);
    self::setDB('conf_'.$new_id, 'to', $to);
    self::setDB('conf_'.$new_id, 'offset', $offset);
    self::setDB('conf_'.$new_id, 'users', count($users));
    for($i=1; $i<=count($users); $i++) {
      self::setDB('conf_'.$new_id.'/user_'.$i, 'pin', $users[$i-1]->pin);
      self::setDB('conf_'.$new_id.'/user_'.$i, 'callerid', $users[$i-1]->callerid);
      self::setDB('conf_'.$new_id.'/user_'.$i, 'profile', $users[$i-1]->profile);
      self::setDB('conf_'.$new_id.'/user_'.$i, 'menu', $users[$i-1]->menu);
      self::setDB('conf_'.$new_id.'/user_'.$i, 'chan', $users[$i-1]->chan);
      self::setDB('conf_'.$new_id.'/user_'.$i, 'auto', $users[$i-1]->auto);
      self::setDB('conf_'.$new_id.'/user_'.$i, 'delay', ($users[$i-1]->delay?$users[$i-1]->delay:0));
      self::setDB('conf_'.$new_id.'/user_'.$i, 'extnum', $users[$i-1]->extnum);
      self::setDB('conf_'.$new_id.'/user_'.$i, 'extdelay', $users[$i-1]->extdelay);
      self::setDB('conf_'.$new_id.'/user_'.$i, 'retry', $users[$i-1]->retry);
      self::setDB('conf_'.$new_id.'/user_'.$i, 'retries', $users[$i-1]->retries);
      self::setDB('conf_'.$new_id.'/user_'.$i, 'timeout', $users[$i-1]->timeout);
    }
    self::setDB('conf_'.$new_id, 'groups', count($groups));
    for($i=1; $i<=count($groups); $i++) {
      self::setDB('conf_'.$new_id.'/group_'.$i, 'id', $groups[$i-1]);
    }
    return $result;
  }

  public function savePersistentGroup($old_id, $new_id, $users) {
    $groups = self::getGroups();
    $result=true;
    if((!$old_id)&&(in_array($new_id, $groups))) return false;
    if(($key = array_search($old_id, $groups)) !== false) {
      if($old_id!=$new_id) {
        if(in_array($new_id, $groups)) return false;
        unset($groups[$key]);
        $groups[]=$new_id;
        self::setGroups($groups);
        $roomlist = self::getRooms();
        foreach($roomlist as $room) { 
          $grp_cnt=self::getDB('conf_'.$room, 'groups');
          for($i=1; $i<=$grp_cnt; $i++) {
            $grpid=self::getDB('conf_'.$room.'/group_'.$i, 'id');
            if($grpid==$old_id) self::setDB('conf_'.$room.'/group_'.$i, 'id', $new_id);
          }
        }
      }
      $user_cnt=self::getDB('group_'.$old_id, 'users');
      for($i=1; $i<=$user_cnt; $i++) {
        $result&=self::deltreeDB('group_'.$old_id.'/user_'.$i);
      }
      $result&=self::deltreeDB('group_'.$old_id, 'users');
    } else {
      $groups[]=$new_id;
      self::setGroups($groups);
    }
    self::setDB('group_'.$new_id, 'users', count($users));
    for($i=1; $i<=count($users); $i++) {
      self::setDB('group_'.$new_id.'/user_'.$i, 'pin', $users[$i-1]->pin);
      self::setDB('group_'.$new_id.'/user_'.$i, 'callerid', $users[$i-1]->callerid);
      self::setDB('group_'.$new_id.'/user_'.$i, 'profile', $users[$i-1]->profile);
      self::setDB('group_'.$new_id.'/user_'.$i, 'menu', $users[$i-1]->menu);
      self::setDB('group_'.$new_id.'/user_'.$i, 'chan', $users[$i-1]->chan);
      self::setDB('group_'.$new_id.'/user_'.$i, 'auto', $users[$i-1]->auto);
      self::setDB('group_'.$new_id.'/user_'.$i, 'delay', ($users[$i-1]->delay?$users[$i-1]->delay:0));
      self::setDB('group_'.$new_id.'/user_'.$i, 'extnum', $users[$i-1]->extnum);
      self::setDB('group_'.$new_id.'/user_'.$i, 'extdelay', $users[$i-1]->extdelay);
      self::setDB('group_'.$new_id.'/user_'.$i, 'retry', $users[$i-1]->retry);
      self::setDB('group_'.$new_id.'/user_'.$i, 'retries', $users[$i-1]->retries);
      self::setDB('group_'.$new_id.'/user_'.$i, 'timeout', $users[$i-1]->timeout);
    }
    return $result;
  }

  public static function makeCall($roomid, $userid, $groupid, $schedule = null, $channel = null) {
    $user = new \stdClass();
    $maxcount=self::getDB('conf_'.$roomid, 'maxcount');
    $alias=self::getDB('conf_'.$roomid, 'alias');
    $profile=self::getDB('conf_'.$roomid, 'profile');
    if($groupid=='') {
      $retries=self::getDB('conf_'.$roomid, 'user_'.$userid.'/retries');
      if($retries=='') $retries=2;
      $retry=self::getDB('conf_'.$roomid, 'user_'.$userid.'/retry');
      if($retry=='') $retry=5;
      $timeout=self::getDB('conf_'.$roomid, 'user_'.$userid.'/timeout');
      if($timeout=='') $timeout=10;
      $user->callerid=self::getDB('conf_'.$roomid, 'user_'.$userid.'/callerid');
      $user->profile=self::getDB('conf_'.$roomid, 'user_'.$userid.'/profile');
      $user->menu=self::getDB('conf_'.$roomid, 'user_'.$userid.'/menu');
      $user->chan=self::getDB('conf_'.$roomid, 'user_'.$userid.'/chan');
      $user->extnum=self::getDB('conf_'.$roomid, 'user_'.$userid.'/extnum');
      $user->extdelay=self::getDB('conf_'.$roomid, 'user_'.$userid.'/extdelay');
    } else {
      $retries=self::getDB('group_'.$groupid, 'user_'.$userid.'/retries');
      if($retries=='') $retries=2;
      $retry=self::getDB('group_'.$groupid, 'user_'.$userid.'/retry');
      if($retry=='') $retry=5;
      $timeout=self::getDB('group_'.$groupid, 'user_'.$userid.'/timeout');
      if($timeout=='') $timeout=10;
      $user->callerid=self::getDB('group_'.$groupid, 'user_'.$userid.'/callerid');
      $user->profile=self::getDB('group_'.$groupid, 'user_'.$userid.'/profile');
      $user->menu=self::getDB('group_'.$groupid, 'user_'.$userid.'/menu');
      $user->chan=self::getDB('group_'.$groupid, 'user_'.$userid.'/chan');
      $user->extnum=self::getDB('group_'.$groupid, 'user_'.$userid.'/extnum');
      $user->extdelay=self::getDB('group_'.$groupid, 'user_'.$userid.'/extdelay');
    }
    $chandata=explode("/",$user->chan);
    if(($chandata[0] == 'Local') && ($channel)) {
      $abmodule = getModuleByClass('addressbook\AddressBook');
      if($abmodule) {
        $contactinfo = explode('@', $chandata[1]);
        $exten = $contactinfo[0];
        $book = substr($contactinfo[1], 3);
        $contact = $abmodule->getContact($book, $exten);
        foreach($contact->channels as $key => $concactchannel) {
          if($concactchannel == $channel) {
            $user->chan = $channel;
            $userid = $userid.'c'.$key;
            $chandata=explode("/",$user->chan);
            $timeout = 30;
            $retries = 0;
            break;
          }
        }
      }
    }
    $user->number=array_pop($chandata);
    $prefix='';
    if(($chandata[0]=='DAHDI')&&in_array($user->number[0],array('U','I','N','L','S','V','R'))) {
      $prefix=$user->number[0];
      $user->number=substr($user->number,1);
    }
    if(trim($alias)=='') {
      $users=self::getAsteriskPeers();
      if(count($chandata)>1) {
        $login=array_pop($chandata);
      } else {
        $login=$user->number;
      }
      foreach($users as $v) {
        if($v->login==$login) {
          $alias = $v->name.' <'.$v->number.'>';
          break;
        }
      }
    }
    if($prefix!='') {
      if(strpos($alias,'<')!==false) {
        preg_match('/^(.*)\<(.*)\>$/',$alias,$match);
        $alias = $match[1].'<'.$prefix.$match[2].'>';
      } else {
        $alias=$prefix.$alias;
      }
    }

    $parameters = array('Channel' => $user->chan, 'Context' => 'confbridge', 'MaxRetries' => $retries, 'Extension' => $roomid, 'Priority' => 1, 'Set' => array('CONFGROUP='.$groupid, 'CONFROOM='.$roomid, 'CIDNUM='.$user->number, 'CIDNAME='.$user->callerid, 'NOPIN=1', 'EXTENDDELAY='.$user->extdelay, 'EXTENDNUM='.addslashes($user->extnum), 'ROOMPROFILE='.$profile, 'USERPROFILE='.$user->profile, 'MENUPROFILE='.$user->menu, 'MAXCOUNT='.$maxcount), 'CallerID' => $alias, 'RetryTime' => $retry, 'WaitTime' => $timeout);

    $descriptorspec = array(
      0 => array("pipe", "r"),
      1 => array("pipe", "w"),
      2 => array("pipe", "w")
    );

    $cwd = dirname(__FILE__);
    $env = array('roomid' => $roomid, 'user' => $userid, 'groupid' => $groupid, 'data' => json_encode($parameters), 'schedule' => $schedule);

    $process = proc_open($cwd.'/.call', $descriptorspec, $pipes, $cwd, $env);

    if(is_resource($process)) {
      fclose($pipes[0]);

      $log = stream_get_contents($pipes[1]);
      if($log!='') error_log($log);
      fclose($pipes[1]);
      fclose($pipes[2]);

      $return_value = proc_close($process);
    }
    $result=$return_value===0;

    return $result;
  }

  public function cancelPersistentRoom($roomid) {
    $spooldir = '/var/spool/asterisk/outgoing';
    if($dh = opendir($spooldir)) {
      while(($file = readdir($dh)) !== false) {
        if(!is_dir($spooldir . '/' . $file)) {
          if($file[0]!='.') {
            if((strpos($file, 'room_'.$roomid.'_')===0)&&(filemtime($spooldir. '/' .$file)>time())) unlink($spooldir. '/' .$file);
          }
        }
      }
      closedir($dh);
    }
  }

  public function callPersistentRoomUser($roomid, $userid, $groupid, $channel=null) {
    return self::makeCall($roomid, $userid, $groupid, null, $channel);
  }

  public function startPersistentRoom($roomid, $schedule=null) {
    $result = true;
    $callcount = 0;
    $user_cnt=self::getDB('conf_'.$roomid, 'users');
    for($i=1; $i<=$user_cnt; $i++) {
     $auto=self::getDB('conf_'.$roomid, 'user_'.$i.'/auto')=='true';
     $delay=self::getDB('conf_'.$roomid, 'user_'.$i.'/delay');
     if((($schedule===null)&&$auto)||(($schedule)&&$auto)) {
       $result&=self::makeCall($roomid, $i, '', $schedule-$delay);
       $callcount++;
     }
    }
    $group_cnt=self::getDB('conf_'.$roomid, 'groups');
    $room['groups'] = array();
    for($i=1; $i<=$group_cnt; $i++) {
      $groupid=self::getDB('conf_'.$roomid, 'group_'.$i.'/id');
      $grp_cnt=self::getDB('group_'.$groupid, 'users');
      for($j=1; $j<=$grp_cnt; $j++) {
        $found = false;
        $gchannel=self::getDB('group_'.$groupid, 'user_'.$j.'/chan');
        for($k=1; $k<=$user_cnt; $k++) {
          $uchannel=self::getDB('conf_'.$roomid, 'user_'.$k.'/chan');
          if($uchannel==$gchannel) {
            $found=true;
            break;
          }
        }
        if(!$found) {
          $auto=self::getDB('group_'.$groupid, 'user_'.$j.'/auto')=='true';
          $delay=self::getDB('group_'.$groupid, 'user_'.$j.'/delay');
          if((($schedule===null)&&$auto)||(($schedule)&&$auto)) {
            $result&=self::makeCall($roomid, $j, $groupid, $schedule-$delay);
            $callcount++;
          }
        }
      }
    }
    return $result&&($callcount>0);
  }

  public function rescheduleRoomUser($room, $user, $groupid, $next = null) {
    if($next) $now = new \DateTime('@'.$next);
    else $now = new \DateTime();
    $uenabled=self::getDB('conf_'.$room, 'enabled')=='true';
    if($uenabled) {
      $udays=self::getDB('conf_'.$room, 'days');
      $ufrom=self::getDB('conf_'.$room, 'from');
      $uto=self::getDB('conf_'.$room, 'to');
      $uoffset=self::getDB('conf_'.$room, 'offset');
      $days=explode('&', $udays);
      $ftime=explode(':', $ufrom);
      $ttime=explode(':', $uto);
      $fschedule = clone $now;
      $fschedule->setTimezone(new \DateTimeZone('GMT'));
      $fschedule->setTime($ftime[0],$ftime[1]);
      $tschedule = clone $now;
      $tschedule->setTimezone(new \DateTimeZone('GMT'));
      $tschedule->setTime($ttime[0],$ttime[1]);
      if($fschedule->getTimestamp()>$tschedule->getTimestamp()) $tschedule->add(new \DateInterval('P1D'));
      $flschedule = clone $fschedule;
      if(isset($uoffset)&&$uoffset) {
        if($uoffset>0) {
          $flschedule->add(new \DateInterval('PT'.$uoffset.'M'));
        } else {
          $flschedule->sub(new \DateInterval('PT'.(-1*$uoffset).'M'));
        }
      }
      if($flschedule->format('d')>$fschedule->format('d')) {
        $flschedule->sub(new \DateInterval('P1D'));
        $fschedule->sub(new \DateInterval('P1D'));
        $tschedule->sub(new \DateInterval('P1D'));
      } elseif($flschedule->format('d')<$fschedule->format('d')) {
        $flschedule->add(new \DateInterval('P1D'));
        $fschedule->add(new \DateInterval('P1D'));
        $tschedule->add(new \DateInterval('P1D'));
      }
      if($now->getTimestamp()>$fschedule->getTimestamp()||(($udays!='')&&!in_array(strtolower($flschedule->format('D')),$days))) {
        $flschedule->add(new \DateInterval('P1D'));
        $fschedule->add(new \DateInterval('P1D'));
        if($udays!='') {
          while(!in_array(strtolower($flschedule->format('D')),$days)) {
            $flschedule->add(new \DateInterval('P1D'));
            $fschedule->add(new \DateInterval('P1D'));
          }
        }
      }
      $schedule=$fschedule->getTimestamp();
    }
    $userid='';
    if($groupid=='') {
      $user_cnt=self::getDB('conf_'.$room, 'users');
      for($i=1; $i<=$user_cnt; $i++) {
        $chan=self::getDB('conf_'.$room, 'user_'.$i.'/chan');
        if($chan==$user) {
          $userid=$i;
          break; 
        }
      }
      $auto=self::getDB('conf_'.$room, 'user_'.$userid.'/auto')=='true';
      $delay=self::getDB('conf_'.$room, 'user_'.$userid.'/delay');
      self::setDB('conf_'.$room, 'user_'.$userid.'/failed',0);
      $fgroupid='';
    } else {
      $grp_cnt=self::getDB('group_'.$groupid, 'users');
      for($i=1; $i<=$grp_cnt; $i++) {
        $chan=self::getDB('group_'.$groupid, 'user_'.$i.'/chan');
        if($chan==$user) {
          $userid=$i;
          break; 
        }
      }
      $auto=self::getDB('group_'.$groupid, 'user_'.$userid.'/auto')=='true';
      $delay=self::getDB('group_'.$groupid, 'user_'.$userid.'/delay');
      self::setDB('conf_'.$room, 'group_'.$groupid.'/user_'.$userid.'/failed',0);
      $fgroupid='_group_'.$groupid;
    }
    if(file_exists('/var/spool/asterisk/outgoing/room_'.$room.$fgroupid.'_user_'.$userid.'.call')) {
      unlink('/var/spool/asterisk/outgoing/room_'.$room.$fgroupid.'_user_'.$userid.'.call');
    }
    if($uenabled&&$auto&&($udays!='')) {
      self::makeCall($room, $userid, $groupid, $schedule-$delay);
    }
  }

  public function getSchedulePersistentRooms($now = null) {
    $rooms = self::getRooms();
    $result=new \stdClass();
    if($now==null) $now = new \DateTime();
    foreach($rooms as $roomid) {
      $uenabled=self::getDB('conf_'.$roomid, 'enabled')=='true';
      if($uenabled) {
        $user_cnt=self::getDB('conf_'.$roomid, 'users');
        $udays=self::getDB('conf_'.$roomid, 'days');
        $ufrom=self::getDB('conf_'.$roomid, 'from');
        $uto=self::getDB('conf_'.$roomid, 'to');
        $uoffset=self::getDB('conf_'.$roomid, 'offset');
        $days=explode('&', $udays);
        $ftime=explode(':', $ufrom);
        $ttime=explode(':', $uto);
        $fschedule = clone $now;
        $fschedule->setTimezone(new \DateTimeZone('GMT'));
        $fschedule->setTime($ftime[0],$ftime[1]);
        $tschedule = clone $now;
        $tschedule->setTimezone(new \DateTimeZone('GMT'));
        $tschedule->setTime($ttime[0],$ttime[1]);
        if($fschedule->getTimestamp()>$tschedule->getTimestamp()) $tschedule->add(new \DateInterval('P1D'));
        $flschedule = clone $fschedule;
        if(isset($uoffset)&&$uoffset) {
          if($uoffset>0) {
            $flschedule->add(new \DateInterval('PT'.$uoffset.'M'));
          } else {
            $flschedule->sub(new \DateInterval('PT'.(-1*$uoffset).'M'));
          }
        }
        if($flschedule->format('d')>$fschedule->format('d')) {
          $flschedule->sub(new \DateInterval('P1D'));
          $fschedule->sub(new \DateInterval('P1D'));
          $tschedule->sub(new \DateInterval('P1D'));
        } elseif($flschedule->format('d')<$fschedule->format('d')) {
          $flschedule->add(new \DateInterval('P1D'));
          $fschedule->add(new \DateInterval('P1D'));
          $tschedule->add(new \DateInterval('P1D'));
        }
        if($now->getTimestamp()>$fschedule->getTimestamp()||(($udays!='')&&!in_array(strtolower($flschedule->format('D')),$days))) {
          $flschedule->add(new \DateInterval('P1D'));
          $fschedule->add(new \DateInterval('P1D'));
          if($udays!='') {
            while(!in_array(strtolower($flschedule->format('D')),$days)) {
              $flschedule->add(new \DateInterval('P1D'));
              $fschedule->add(new \DateInterval('P1D'));
            }
          }
        }
        $schedule=$fschedule->getTimestamp();
        for($i=1; $i<=$user_cnt; $i++) {
          $auto=self::getDB('conf_'.$roomid, 'user_'.$i.'/auto')=='true';
          $delay=self::getDB('conf_'.$roomid, 'user_'.$i.'/delay');
          if($auto&&($udays!='')) {
            $result->$roomid[]=(object) array('user' => $i, 'group' => '', 'time' => $schedule-$delay);
          }
        }
        $group_cnt=self::getDB('conf_'.$roomid, 'groups');
        for($i=1; $i<=$group_cnt; $i++) {
          $groupid=self::getDB('conf_'.$roomid, 'group_'.$i.'/id');
          $grp_cnt=self::getDB('group_'.$groupid, 'users');
          for($j=1; $j<=$grp_cnt; $j++) {
            $found = false;
            $gchannel=self::getDB('group_'.$groupid, 'user_'.$j.'/chan');
            for($k=1; $k<=$user_cnt; $k++) {
              $uchannel=self::getDB('conf_'.$roomid, 'user_'.$k.'/chan');
              if($uchannel==$gchannel) {
                $found=true;
                break;
              }
            }
            if(!$found) {
              $auto=self::getDB('group_'.$groupid, 'user_'.$j.'/auto')=='true';
              $delay=self::getDB('group_'.$groupid, 'user_'.$j.'/delay');
              if($auto&&($udays!='')) {
                $result->$roomid[]=(object) array('user' => $j, 'group' => $groupid, 'time' => $schedule-$delay);
              }
            }
          }
        }
      }
    }
    return $result;
  }

  public function schedulePersistentRooms($now = null) {
    $schedule = $this->getSchedulePersistentRooms($now);
    $result=true;
    foreach($schedule as $roomid => $room) {
      foreach($room as $entry) {
        $result&=self::makeCall($roomid, $entry->user, $entry->group, $entry->time);
      }
    }
    return $result;
  }

  public function schedulePersistentRoom($roomid, $now = null) {
    $schedule = $this->getSchedulePersistentRooms($now);
    $result=false;
    if(isset($schedule->$roomid)) {
      $result=true;
      $room=$schedule->$roomid;
      foreach($room as $entry) {
        $result&=self::makeCall($roomid, $entry->user, $entry->group, $entry->time);
      }
    }
    return $result;
  }

}

?>
