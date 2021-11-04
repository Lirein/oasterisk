<?php

namespace confbridge;

class ConfbridgeManage extends \core\ViewModule {

  public static function getLocation() {
    return 'manage/rooms';
  }

  public static function getMenu() {
    return (object) array('name' => 'Конференц-комнаты', 'prio' => 5, 'icon' => 'oi oi-people');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('system_info');
    $result &= self::checkLicense('oasterisk-confbridge');
    return $result;
  }

  private static function orderroom($a, $b) {
    return strcmp($a->number, $b->number);
  }

  private static function orderuser($a, $b) {
    return strcmp($a->name, $b->name);
  }

  public function json(string $request, \stdClass $request_data) {
    $abmodule = null;
    $result = new \stdClass();
    switch($request) {
      case "rooms": {
        $rooms = new \stdClass();
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          $rooms=$confmodule->getRoomList();
          $nums = array();
          foreach($rooms as $roomid => $room) {
            $nums[]=$room->number;
            $room_info = $confmodule->getRoomInfo($room->number);
            if(isset($room_info->max_members)) {
              $rooms[$roomid]->avail=isset($room_info->max_members)?$room_info->max_members:0;
            } else {
              $rooms[$roomid]->avail=0;
            }
            $rooms[$roomid]->active=false;
            if(!self::checkEffectivePriv('confbridge_room', $room->number, 'system_info')) {
              unset($rooms[$roomid]);
            }
          }
          $prooms=$confmodule->getRooms();
          foreach($prooms as $roomid) {
            if(self::checkEffectivePriv('confbridge_room', $roomid, 'system_info')) {
              $room_info = $confmodule->getPersistentRoom($roomid);
              $room = new \stdClass();
              $room->active=$room_info->active;
              if(!in_array($roomid, $nums)) {
                $room->number=$roomid;
                $room->count=0;
                if(!empty($room_info->maxcount)) {
                  $room->avail=$room_info->maxcount;
                } else {
                  if(isset($room_info->profile)) {
                    $ini = new \INIProcessor('/etc/asterisk/confbridge.conf');
                    $roomprofile = $room_info->profile;
                    if(isset($ini->$roomprofile)) {
                      $result = $ini->$roomprofile;
                      unset($result->type);
                    }
                    $room->avail=isset($result->max_members)?$result->max_members:0;
                  } else {
                    $room->avail=0;
                  }
                }
                $rooms[]=$room;
              } else {
                foreach($rooms as $lroomid => $room) {
                 if($room->number==$roomid) {
                   $rooms[$lroomid]->active=$room_info->active;
                 }
                }
              }
            }
          }
        }
        usort($rooms, array(__CLASS__, "orderroom"));
        $result = self::returnResult($rooms);
      } break;
      case "room": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule&&self::checkEffectivePriv('confbridge_room', $request_data->id, 'system_info')) {
          $prooms=$confmodule->getRooms();
          $return = new \stdClass();
          $return->readonly=!self::checkEffectivePriv('confbridge_room', $request_data->id, 'system_control');
          $return->active=false;
          $return->disallowcallout=false;
          $return->number=$request_data->id;
          $room_info = $confmodule->getRoomInfo($request_data->id);
          $return->profile=isset($room_info->profile)?$room_info->profile:'';
          $running=false;
          $rooms=$confmodule->getRoomList();
          foreach($rooms as $room) {
            if($room->number==$request_data->id) {
              $return->locked=$room->locked;
              $return->muted=$room->muted;
              $return->recording=$room->recording;
              $running=true;
            }
          }
          $return->persistent=false;
          $return->running=$running;
          $return->groups=array();
          $return->groupids=array();
          if($running) {
            $return->active=true;
            $return->users=$confmodule->getRoomUsers($request_data->id);
            foreach($return->users as $k => $v) {
              $return->users[$k]->online=true;
              $return->users[$k]->dialing=false;
              $return->users[$k]->groupid=false;
            }
            $return->pin=$confmodule->getPin($request_data->id);
          } else {
            $return->users=array();
          }
          $subrooms=$confmodule->getSubRoomList($request_data->id);
          foreach($subrooms as $subroom) {
            $return->running=true;
            $room=$subroom;
            $room->users=$confmodule->getRoomUsers($room->number);
            foreach($room->users as $k => $v) {
              $room->users[$k]->online=true;
              $room->users[$k]->dialing=false;
              $room->users[$k]->groupid=false;
            }
            usort($room->users, array(__CLASS__, "orderuser"));
            $return->groups[]=$room;
          }
          if(in_array($request_data->id, $prooms)) { //is persistent
            $return->persistent=true;
            $room_info = $confmodule->getPersistentRoom($request_data->id);
            $return->active=$room_info->active;
            $return->disallowcallout=$room_info->disallowcallout;
            $return->groupids=$room_info->groups;
            $ini = new \INIProcessor('/etc/asterisk/confbridge.conf');
            $userslists=array();
            $userslists[]=(object) array('list' => $room_info->users, 'group' => '', 'groupid' => '');
            foreach($room_info->groups as $proomgroup) {
              $proomgroupinfo=$confmodule->getPersistentGroup($proomgroup->id, $request_data->id);
              $userslists[]=(object) array('list' => $proomgroupinfo->users, 'group' => '_group_'.$proomgroup->id, 'groupid' => $proomgroup->id);
            }
            foreach($userslists as $userslist) {
              foreach($userslist->list as $puserkey => $puser) {
                $userlink = array();
                $chandata=explode("/", $puser->chan);
                $number = $chandata[count($chandata)-1];
                switch($chandata[0]) {
                  case "DAHDI": {
                    if(in_array($number[0],array('U','I','N','L','S','V'.'R'))) { //if dialed with prefix - remove them
                      $number=substr($number,1);
                    }
                  } break;
                }
                foreach($return->users as $runuserkey => $runuser) {
                  if((strrpos($runuser->channel,'-')!==false)&&(strrpos($runuser->channel,'-')>strrpos($runuser->channel, '/'))) {
                    $channel=substr($runuser->channel,0,strrpos($runuser->channel,'-'));
                  } else {
                    $channel=$runuser->channel;
                  }
                  $chanrun=explode("/", $channel);
                  if($runuser->number==$number) {
                    if((count($chanrun)<=2)||((count($chanrun)>2)&&($runuser->extnum==$puser->extnum))) {
                      $userlink[]=&$return->users[$runuserkey];
                    }
                  }
                }
                if(count($userlink)==0) {
                  foreach($return->groups as $groupid => $group) {
                    foreach($group->users as $runuserkey => $runuser) {
                     if((strrpos($runuser->channel,'-')!==false)&&(strrpos($runuser->channel,'-')>strrpos($runuser->channel,'/'))) {
                        $channel=substr($runuser->channel,0,strrpos($runuser->channel,'-'));
                      } else {
                        $channel=$runuser->channel;
                      }
                      $chanrun=explode("/", $channel);
                      if($runuser->number==$number) {
                        if((count($chanrun)<=2)||((count($chanrun)>2)&&($runuser->extnum==$puser->extnum))) {
                          $userlink[]=&$return->groups[$groupid]->users[$runuserkey];
                        }
                      }
                    }
                  }
                }
                if(count($userlink)==0) {
                  $user = &$return->users[];
                  $user = new \stdClass();
                  $user->online=false;
                  $user->id=$puser->intid;
                  $user->number = $number;
                  $user->name = $puser->callerid;
                  $user->channel = $puser->chan;
                  $user->extnum = $puser->extnum;
                  $user->start = 0;
                  $user->failed = isset($puser->failed)&&($puser->failed==1);
                  $user->dialing = false;
                  $user->waiting = false;
                  $user->admin = false;
                  $user->marked = false;
                  $user->waitmarked = false;
                  $user->endmarked = false;
                  $user->muted = false;
                  $user->isolated = false;
                  $user->groupid = $userslist->groupid;
                  $user->group = $userslist->group;
                  $profilename = $puser->profile;
                  if(isset($ini->$profilename)) {
                    $profile = $ini->$profilename;
                    $user->admin = isset($profile->admin)?($profile->admin=='yes'):false;
                    $user->marked = isset($profile->marked)?($profile->marked=='yes'):false;
                    $user->waitmarked = isset($profile->wait_marked)?($profile->wait_marked=='yes'):false;
                    $user->endmarked = isset($profile->end_marked)?($profile->end_marked=='yes'):false;
                    $user->muted = isset($profile->startmuted)?($profile->startmuted=='yes'):false;
                  }
                  if(isset($request_data->id)&&file_exists('/var/spool/asterisk/outgoing/room_'.$request_data->id.$userslist->group.'_user_'.($puserkey+1).'.call')&&(filemtime('/var/spool/asterisk/outgoing/room_'.$request_data->id.$userslist->group.'_user_'.($puserkey+1).'.call')<=(time()+40))) {
                    $user->scheduled=true;
                  } else {
                    $user->scheduled=false;
                  }
                } else {
                  foreach($userlink as $key => $value) {
                    $user = &$userlink[$key];
                    if($user->groupid===false) $user->groupid = $userslist->groupid;
                    if(!isset($user->id)) $user->id = $puser->intid;
                    if(isset($request_data->id)&&isset($user->id)&&isset($user->group)&&file_exists('/var/spool/asterisk/outgoing/room_'.$request_data->id.$user->group.'_user_'.$user->id.'.call')&&(filemtime('/var/spool/asterisk/outgoing/room_'.$request_data->id.$user->group.'_user_'.$user->id.'.call')<=(time()+40))) {
                      $user->scheduled=true;
                    } else {
                      $user->scheduled=false;
                    }
                  }
                }
                unset($userlink);
              }
            }
          }
          $ringusers = $confmodule->getRingUsers($request_data->id);
          foreach($ringusers as $ruserkey => $ruser) {
            $userkey = null;
            if(strpos($ruser->channel,'-')!==false) {
              $channel=substr($ruser->channel,0,strrpos($ruser->channel,'-'));
            } else {
              $channel=$ruser->channel;
            }
            $chandata=explode("/", $channel);
            $number = $chandata[count($chandata)-1];
            foreach($return->users as $runuserkey => $runuser) {
              if(strpos($runuser->channel,'-')!==false) { 
                $channel=substr($runuser->channel,0,strrpos($runuser->channel,'-'));
              } else {
                $channel=$runuser->channel;
              }
              $chanrun=explode("/", $channel);
              if((($ruser->number==$chanrun[count($chanrun)-1])||($runuser->number==$number)||($chanrun[count($chanrun)-1]==$number))&&($ruser->extnum==$runuser->extnum)) {
                $userkey=$runuserkey;
              }
            }
            if($userkey===null) {
              $user = &$return->users[];
              $user = new \stdClass();
              $user->online=false;
              $user->scheduled=false;
              $user->number = $chandata[count($chandata)-1];
              $user->name = $ruser->callerid;
              $user->channel = $ruser->channel;
              $user->extnum = $ruser->extnum;
              $user->start = 0;
              $user->waiting = false;
              $user->failed = false;
              $user->admin = false;
              $user->marked = false;
              $user->waitmarked = false;
              $user->endmarked = false;
              $user->muted = false;
              $user->groupid = '';
              $profilename = $ruser->profile;
              if(isset($ini->$profilename)) {
                $profile = $ini->$profilename;
                $user->admin = isset($profile->admin)?($profile->admin=='yes'):false;
                $user->marked = isset($profile->marked)?($profile->marked=='yes'):false;
                $user->waitmarked = isset($profile->wait_marked)?($profile->wait_marked=='yes'):false;
                $user->endmarked = isset($profile->end_marked)?($profile->end_marked=='yes'):false;
                $user->muted = isset($profile->startmuted)?($profile->startmuted=='yes'):false;
              }
              $user->dialing=true;
            } else {
              $user = &$return->users[$userkey];
              $user->dialing=true;
              $user->channel=$ruser->channel;
            }
          }
          foreach($return->users as $k => $v) {
            if(strpos($return->users[$k]->channel,'Local/')===0) {
              $chaninfo = explode('@', substr($return->users[$k]->channel, 6));
              if($return->users[$k]->online||$return->users[$k]->dialing) $chaninfo[1] = substr($chaninfo[1], 0, strrpos($chaninfo[1], '-'));
              if(!$abmodule) $abmodule = getModuleByClass('addressbook\AddressBook');
              if($abmodule) {
                $contact = $abmodule->getContact(substr($chaninfo[1], 3), $chaninfo[0]);
                if($contact) {
                  $return->users[$k]->number = implode(', ', $contact->numbers);
                  $return->users[$k]->numbers = $contact->numbers;
                  $return->users[$k]->channels = $contact->channels;
                }
              }
            } elseif(strpos($return->users[$k]->number, '@ab-')!==false) {
              $chaninfo = explode('@', $return->users[$k]->number);
              if(!$abmodule) $abmodule = getModuleByClass('addressbook\AddressBook');
              if($abmodule) {
                $contact = $abmodule->getContact(substr($chaninfo[1], 3), $chaninfo[0]);
                if($contact) {
                  $return->users[$k]->number = implode(', ', $contact->numbers);             
                  $return->users[$k]->numbers = $contact->numbers;
                  $return->users[$k]->channels = $contact->channels;
                }
              }
            }
            if(preg_match('/(.*)\((.*)\).*/',$return->users[$k]->name,$match)) {
              $return->users[$k]->name=$match[1];
              if(strpos($return->users[$k]->number, $match[2])!==false) {
                $return->users[$k]->number = str_replace($match[2], '<b>'.$match[2].'</b>', $return->users[$k]->number);
              } else {
                $return->users[$k]->number=$return->users[$k]->number.' ('.$match[2].')';
              }
            }
          }
          foreach($return->groups as $groupid => $group) {
            foreach($group->users as $runuserkey => $runuser) {
              if(strpos($return->groups[$groupid]->users[$runuserkey]->channel,'Local/')===0) {
                $chaninfo = explode('@', substr($return->groups[$groupid]->users[$runuserkey]->channel, 6));
                if($return->groups[$groupid]->users[$runuserkey]->online||$return->groups[$groupid]->users[$runuserkey]->dialing) $chaninfo[1] = substr($chaninfo[1], 0, strrpos($chaninfo[1], '-'));
                if(!$abmodule) $abmodule = getModuleByClass('addressbook\AddressBook');
                if($abmodule) {
                  $contact = $abmodule->getContact(substr($chaninfo[1], 3), $chaninfo[0]);
                  if($contact) {
                    $return->groups[$groupid]->users[$runuserkey]->number = implode(', ', $contact->numbers);
                    $return->groups[$groupid]->users[$runuserkey]->numbers = $contact->numbers;
                    $return->groups[$groupid]->users[$runuserkey]->channels = $contact->channels;
                  }  
                }
              } elseif(strpos($return->groups[$groupid]->users[$runuserkey]->number,'@ab-')!==false) {
                $chaninfo = explode('@', $return->groups[$groupid]->users[$runuserkey]->number);
                if(!$abmodule) $abmodule = getModuleByClass('addressbook\AddressBook');
                if($abmodule) {
                  $contact = $abmodule->getContact(substr($chaninfo[1], 3), $chaninfo[0]);
                  if($contact) {
                    $return->groups[$groupid]->users[$runuserkey]->number = implode(', ', $contact->numbers);
                    $return->groups[$groupid]->users[$runuserkey]->numbers = $contact->numbers;
                    $return->groups[$groupid]->users[$runuserkey]->channels = $contact->channels;
                  }  
                }
              }
              if(preg_match('/(.*)\((.*)\).*/',$runuser->name,$match)) {
                $return->group[$groupid]->users[$runuserkey]->name=$match[1];
                if(strpos($return->groups[$groupid]->users[$runuserkey]->number, $match[2])!==false) {
                  $return->groups[$groupid]->users[$runuserkey]->number = str_replace($match[2], '<b>'.$match[2].'</b>', $return->groups[$groupid]->users[$runuserkey]->number);
                } else {
                  $return->groups[$groupid]->users[$runuserkey]->number=$runuser->number.' ('.$match[2].')';
                }
              }
            }
          }
          usort($return->users, array(__CLASS__, "orderuser"));
        }
        if(self::checkPriv('dialing')) $return->disallowcallout=false;
        $this->cache->set('room_'.str_replace(' ','_',$request_data->id), $return,30);
        $result = self::returnResult($return);
      } break;
      case "pin": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          $confmodule->setPin($request_data->room,$request_data->pin);
          $result=self::returnResult($request_data->pin);
        } else {
          $result = self::returnError('danger', 'Модуль конференц связи поврежден');
        }
      } break;
      case "lock": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          $confmodule->lockRoom($request_data->room);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Модуль конференц связи поврежден');
        }
      } break;
      case "unlock": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          $confmodule->unlockRoom($request_data->room);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Модуль конференц связи поврежден');
        }
      } break;
      case "mute": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          if(isset($request_data->users)) {
            foreach($request_data->users as $user) $confmodule->muteRoom($request_data->room, $user);
          } else
            $confmodule->muteRoom($request_data->room);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Модуль конференц связи поврежден');
        }
      } break;
      case "unmute": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          if(isset($request_data->users)) {
            foreach($request_data->users as $user) $confmodule->unmuteRoom($request_data->room, $user);
          } else 
            $confmodule->unmuteRoom($request_data->room);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Модуль конференц связи поврежден');
        }
      } break;
      case "record": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          $confmodule->startRecordRoom($request_data->room);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Модуль конференц связи поврежден');
        }
      } break;
      case "unrecord": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          $confmodule->stopRecordRoom($request_data->room);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Модуль конференц связи поврежден');
        }
      } break;
      case "kick": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          $users=$request_data->users;
          $confmodule->kickUsers($request_data->room, $users);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Модуль конференц связи поврежден');
        }
      } break;
      case "isolate": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          foreach($request_data->users as $user) $confmodule->isolateUser($request_data->room,$user);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Модуль конференц связи поврежден');
        }
      } break;
      case "merge": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          $confmodule->mergeUsers($request_data->room,$request_data->users);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Модуль конференц связи поврежден');
        }
      } break;
      case "invite": {
        if(self::checkPriv('dialing')) {
          $confmodule = new \confbridge\ConfbridgeModule();
          if($confmodule) {
            $confmodule->inviteUser($request_data->room, $request_data->room_profile, $request_data->profile, $request_data->menu, $request_data->channel, $request_data->callerid, $request_data->extendnum, $request_data->delay);
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Модуль конференц связи поврежден');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "setvolume": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          $user=$request_data->user;
          $confmodule->setUserVolume($request_data->room, $user, $request_data->volumein, $request_data->volumeout);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Модуль конференц связи поврежден');
        }
      } break;
      case "start": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          $result = self::returnSuccess();
          $confmodule->cancelPersistentRoom($request_data->room);
          if(is_array($request_data->users)&&(count($request_data->users)>0)) {
            foreach($request_data->users as $user) {
              $confmodule->callPersistentRoomUser($request_data->room, $user->id, $user->groupid);
            }
          } else {
            if(!$confmodule->startPersistentRoom($request_data->room)) $result = self::returnError('warning', 'Нет ни одного участника комнаты с автоматическим вызовом');
          }
        } else {
          $result = self::returnError('danger', 'Модуль конференц связи поврежден');
        }
      } break;
      case "call": {
        $confmodule = new \confbridge\ConfbridgeModule();
        if($confmodule) {
          $confmodule->cancelPersistentRoom($request_data->room);
          if(isset($request_data->channel)) {
            $confmodule->callPersistentRoomUser($request_data->room, $request_data->user_id, $request_data->group_id, $request_data->channel);
          } else {
            $confmodule->callPersistentRoomUser($request_data->room, $request_data->user_id, $request_data->group_id);
          }
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Модуль конференц связи поврежден');
        }
      } break;
      case "users": {
        $result = self::returnResult(self::getAsteriskPeers());
      } break;
      case "user-profiles": {
        $module = new \confbridge\UserSettings();
        $result=$module->json($request, $request_data);
      } break;
      case "menu-profiles": {
        $module = new \confbridge\MenuSettings();
        $result=$module->json($request, $request_data);
      } break;
      case "room-profiles": {
        $module = new \confbridge\RoomSettings();
        $result=$module->json($request, $request_data);
      } break;
      case "events": {
        $module = new \core\DashboardManage();
        $result=$module->json($request, $request_data);
      } break;
    }
    return $result;
  }

  public function scripts() {
    $time=time();
    ?>
    <script>
      var needupdateroom = false;
      var updateTimeout = 0;
      var running = false;
      var updateUsersTimeout = 0;
      var users=[];
      var roomusers={list: [], groups: []};
      var id='<?=isset($_GET['id'])?$_GET['id']:0; ?>';
      var room_profile='';
      var readonly = false;
      var allowcallout = true;
      var active = false;
      var servertime = new moment().unix()-<?=$time?>;

      function updateRooms() {
        sendRequest('rooms').success(function(data) {
          var hasactive=false;
          var items=[];
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              var badgeclass='success';
              var cardclass='';
              if(data[i].number==id) hasactive=true;
              if(data[i].active) {
                cardclass='success';
                badgeclass='secondary';
              }
              if(data[i].avail!=0) {
                if(data[i].count>(data[i].avail*0.65)) badgeclass='warning';
                if(data[i].count>(data[i].avail*0.9)) badgeclass='danger';
              }
              items.push({id: data[i].number, class: cardclass, count: data[i].count, max: data[i].avail, active: data[i].number==id, title: data[i].number, badgeclass: badgeclass});
            }
          };
          rightsidebar_set('#sidebarRightCollapse', items);
          if(!hasactive) {
            var room=$('#confbridge-data');
            room.addClass('invisible');
            window.history.pushState(id, $('title').html(), '/'+urilocation);
            id=0;
            for(var i = 0; i < data.length; i++) {
              if(data[i].active) {
                id=data[i].number;
                break;
              }
            }
            if(id==0) id=data[0].number;
          }
          if(id!=0) updateRoom();
        });
      }

      function updateRoom() {
        needupdateroom=false;
        clearTimeout(updateTimeout);
        if(id!=0) sendRequest('room', {id: id}).success(function(data) {
          if(data.number !== undefined) {
            rightsidebar_activate('#sidebarRightCollapse', id);
            readonly=data.readonly;
            allowcallout=!data.disallowcallout;
            active=data.active;
            room_profile=data.profile;
            window.history.pushState(id, $('title').html(), '/'+urilocation+'?id='+id);
            var rooms=$('#confbridge-list');
            rooms.children().removeClass('active');
            rooms.find('#room-'+id.split(' ').join('_')).addClass('active');
            var room=$('#confbridge-data');
            room.find('#conf-number').val(data.number);
            var current_pin = room.find('#conf-current-pin');
            if(current_pin.val()!=data.pin) {
              current_pin.val(data.pin);
              room.find('#conf-pin').val(data.pin);
            }
            var view=room.find('#conf-view input');
            if(localStorage.getItem("confbridge-view")==="true") {
              if(!$(view.get(1)).prop('checked')) $(view.get(1)).parent().button('toggle');
            } else {
              if(!$(view.get(0)).prop('checked')) $(view.get(0)).parent().button('toggle');
            }
            var online = 0;
            var muted = 0;
            var onlineselected = 0;
            var currentroomusers = data.users.length; //Всего пользователей сейчас
            var oldroomusers = 0; //Пользователей осталось из старых
            var oldroomuserstotal = roomusers.list.length; //Всего было пользователей
            var oldusers = 0; //Было пользователей в группах
            var currentusers = 0; //Осталось пользователей в группах
            var currentuserstotal = 0; //Всего пользователей в группах
            if(data.users.length) { //check room states
              for(var i = 0; i < data.users.length; i++) {
                if(data.users[i].muted) muted+=1;
                if(data.users[i].online) online+=1;
                data.users[i].hold=false;
                var processtalk=true; //update for asterisk 13.21 or above
                if(typeof data.users[i].talk != 'undefined') {
                   processtalk=false;
                }
                if(processtalk) {
                  data.users[i].talk=false;
                }
                data.users[i].selected=false;
                for(var j = 0; j < roomusers.list.length; j++) {
                  if((data.users[i].channel==roomusers.list[j].channel)&&(data.users[i].extnum==roomusers.list[j].extnum)) {
                    oldroomusers++;
                    data.users[i].hold=roomusers.list[j].hold;
                    if(processtalk) {
                      data.users[i].talk=roomusers.list[j].talk;
                    }
                    data.users[i].selected=roomusers.list[j].selected;
                    if(data.users[i].isolated||data.users[i].muted) data.users[i].talk=false;
                  }
                }
                if(data.users[i].selected&&data.users[i].online) onlineselected++;
              }
            }
            for(var m = 0; m < roomusers.groups.length; m++) {
              oldusers += roomusers.groups[m].users.length;
            }
            for(var j = 0; j < data.groups.length; j++) {
              for(var i = 0; i < data.groups[j].users.length; i++) {
                currentuserstotal++;
                data.groups[j].users[i].hold=false;
                var processtalk=true; //update for asterisk 13.21 or above
                if(typeof data.groups[j].users[i].talk != 'undefined') {
                   processtalk=false;
                }
                if(processtalk) {
                  data.groups[j].users[i].talk=false;
                }
                data.groups[j].users[i].selected=false;
                for(var m = 0; m < roomusers.groups.length; m++) {
                  for(var k = 0; k < roomusers.groups[m].users.length; k++) {
                    if(data.groups[j].users[i].channel==roomusers.groups[m].users[k].channel) {
                      currentusers++;
                      data.groups[j].users[i].hold=roomusers.groups[m].users[k].hold;
                      if(processtalk) {
                        data.groups[j].users[i].talk=roomusers.groups[m].users[k].talk;
                      }
                      data.groups[j].users[i].selected=roomusers.groups[m].users[k].selected;
                    }
                  }
                }
                if(data.groups[j].users[i].isolated||data.groups[j].users[i].muted) data.groups[j].users[i].talk=false;
              }
            }
            roomusers.groupids=data.groupids;
            roomusers.list=data.users;
            roomusers.groups=data.groups;
            room.removeClass('invisible');
            if(readonly) {
              room.find('#conf-lock-btn').addClass('invisible');
              room.find('#conf-mute-btn').addClass('invisible');
              room.find('#conf-record-btn').addClass('invisible');
              room.find('#conf-pin').addClass('invisible');
              room.find('#conf-merge-btn').addClass('invisible');
              room.find('#conf-start-btn').addClass('invisible');
              room.find('#conf-stop-btn').addClass('invisible');
            } else {
              room.find('#conf-lock-btn').removeClass('invisible');
              room.find('#conf-mute-btn').removeClass('invisible');
              room.find('#conf-record-btn').removeClass('invisible');
              room.find('#conf-pin').removeClass('invisible');
              room.find('#conf-merge-btn').removeClass('invisible');
              if(allowcallout||active) {
                room.find('#conf-start-btn').removeClass('invisible');
              } else {
                room.find('#conf-start-btn').addClass('invisible');
              }
              room.find('#conf-stop-btn').removeClass('invisible');
            }
            if(onlineselected>0) {
              room.find('#conf-lock-btn button').text('Изолировать')[0].dataset['originalTitle']='Изолировать выделенных абонентов';
            } else if(data.locked) {
              room.find('#conf-lock-btn button').addClass('active').html('Разблокировать')[0].dataset['originalTitle']='Разблокировать комнату';
            } else {
              room.find('#conf-lock-btn button').removeClass('active').html('Заблокировать')[0].dataset['originalTitle']='Заблокировать комнату';
            }
            if(data.running) {
              if(muted>=(online*3/5)) {
                room.find('#conf-mute-btn button').addClass('active').html('Дуплекс')[0].dataset['originalTitle']='Включить микрофоны абонентам';
              } else {
                room.find('#conf-mute-btn button').removeClass('active').html('Симплекс')[0].dataset['originalTitle']='Приглушить микрофоны у абонентов';
              }
              if(data.recording) {
                room.find('#conf-record-btn button').addClass('active').html('Отключить запись')[0].dataset['originalTitle']='Отключить запись конференции';
              } else {
                room.find('#conf-record-btn button').removeClass('active').html('Включить запись')[0].dataset['originalTitle']='Включить запись конференции';
              }
              room.find('#conf-lock-btn').show();
              room.find('#conf-mute-btn').show();
              room.find('#conf-record-btn').show();
              var cont = room.find('#conf-pin').parent().parent().parent();
              if(room.find('#conf-view').parent()[0]!=cont[0]) {
                cont.append(room.find('#conf-view')).show();
              }
            } else {
              room.find('#conf-lock-btn').hide();
              room.find('#conf-mute-btn').hide();
              room.find('#conf-record-btn').hide();
              var cont = room.find('#conf-pin').parent().parent().parent();
              if(room.find('#conf-view').parent()[0]==cont[0]) {
                room.find('#conf-merge-btn').hide().parent().append(room.find('#conf-view'));
                cont.hide();
              }
            }
            running = data.running;
            updateTimeout = setTimeout(updateRoom,3000);
            if((currentroomusers==oldroomusers)&&(oldroomusers==oldroomuserstotal)&&(oldusers==currentusers)&&(currentusers==currentuserstotal)) {
              updateRoomUsers();
            } else {
              renderRoomUsers();
            }
          }
        });
      }

      function updateRoomUsers() {
        clearTimeout(updateUsersTimeout);
        var room=document.querySelector('#confbridge-data');
        var listview=localStorage.getItem("confbridge-view")!=="true";
        var onlineselected = 0;
        var scheduled = 0;
        var stoplessness = 0;
        var offlineselected = 0;
        if(roomusers.list.length||roomusers.groups.length) {
          var users = room.querySelector('#confbridge-users-list');
          for(var j = 0; j < roomusers.groups.length; j++) {
            group=users.querySelector('#confbridge-subgroup-'+j+'-list');
            for(var i = 0; i < roomusers.groups[j].users.length; i++) {
              var userentry=null;
              try {
                var channel = roomusers.groups[j].users[i].channel;
                if(roomusers.groups[j].users[i].extnum!='') channel += 'P'+roomusers.groups[j].users[i].extnum;
                userentry=group.querySelector('[id="subuser-'+channel+'"]');
              } catch (err) {
                userentry = null;
              }
              if(userentry==null) {
                renderRoomUsers();
                return;
              }
              var usertime=userentry.querySelector('#usertime');
              usertime.textContent=getUTCTime(UTC((new Date()).getTime()-roomusers.groups[j].users[i].start*1000-servertime*1000));
              var status='secondary';
              if(roomusers.groups[j].users[i].marked&&roomusers.groups[j].users[i].admin) status='danger';
              else if(roomusers.groups[j].users[i].admin) status='warning';
              else if(roomusers.groups[j].users[i].marked) status='success';
              if(listview) {
                status='';
                if(roomusers.groups[j].users[i].hold) status='dark';
                else if(roomusers.groups[j].users[i].marked&&roomusers.groups[j].users[i].admin) status='danger';
                else if(roomusers.groups[j].users[i].admin) status='warning';
                else if(roomusers.groups[j].users[i].marked) status='success';
                userentry.classList.remove('list-group-item-secondary');
                userentry.classList.remove('list-group-item-danger');
                userentry.classList.remove('list-group-item-warning');
                userentry.classList.remove('list-group-item-success');
                userentry.classList.remove('list-group-item-dark');
                if(status!='') userentry.classList.add('list-group-item-'+status);
                var usertext = userentry.querySelector('span[class=text]');
                if(usertext.textContent!=roomusers.groups[j].users[i].name) usertext.textContent=roomusers.groups[j].users[i].name;
                usertext=usertext.nextSibling.nextSibling;
                var numbertext = roomusers.groups[j].users[i].number;
                if(roomusers.groups[j].users[i].extnum!='') numbertext += 'P'+roomusers.groups[j].users[i].extnum;
                if(usertext.innerHTML!=numbertext) usertext.innerHTML=numbertext;
                usertext=usertext.nextSibling;
                if(usertext.innerHTML!=('<'+roomusers.groups[j].users[i].number+'>')) usertext.innerHTML='<'+roomusers.groups[j].users[i].number+'>';
              } else {
                usercard=userentry.firstChild;
                usercard.classList.remove('bg-secondary');
                usercard.classList.remove('bg-danger');
                usercard.classList.remove('bg-warning');
                usercard.classList.remove('bg-succcess');
                if(status!='') usercard.classList.add('bg-'+status);

                textarea=userentry.querySelector('#name');
                if(textarea.textContent!=roomusers.groups[j].users[i].name) textarea.textContent=roomusers.groups[j].users[i].name;

                numberarea=userentry.querySelector('#number');
                var numbertext = roomusers.groups[j].users[i].number;
                if(roomusers.groups[j].users[i].extnum!='') numbertext += 'P'+roomusers.groups[j].users[i].extnum;
                if(numberarea.innerHTML!=numbertext) numberarea.innerHTML=numbertext;

                iconhold=usercard.querySelector('#icon-hold');
                iconduplex=usercard.querySelector('#icon-duplex');

                if(roomusers.groups[j].users[i].online) {
                  if(roomusers.groups[j].users[i].hold) {
                    iconhold.classList.remove('hidden');
                    iconduplex.classList.add('hidden');
                  } else {
                    iconduplex.classList.remove('hidden');
                    iconhold.classList.add('hidden');
                  }
                }

              }
              talkarea=userentry.querySelector('#icon-talk');
              if(roomusers.groups[j].users[i].talk) {
                talkarea.classList.remove('hidden');
              } else {
                talkarea.classList.add('hidden');
              }
            }
          }
          for(var i = 0; i < roomusers.list.length; i++) {
            var userentry=null;
            try {
              var channel=roomusers.list[i].channel.replace(/\//g,'\\/');
              if(roomusers.list[i].extnum!='') channel+='P'+roomusers.list[i].extnum;
              var userentry=users.querySelector('[id="user-'+channel+'"]');
            } catch (err) {
              userentry = null;
            }
            if(userentry==null) {
              renderRoomUsers();
              return;
            }
            if(roomusers.list[i].selected) {
              if(roomusers.list[i].online)
               onlineselected++;
              else
               offlineselected++;
              if(roomusers.list[i].online||roomusers.list[i].dialing||roomusers.list[i].scheduled)
                stoplessness++;
            }
            if(roomusers.list[i].dialing||roomusers.list[i].scheduled)
              scheduled++;
            var status='secondary';
            if(roomusers.list[i].marked&&roomusers.list[i].admin) status='danger';
            else if(roomusers.list[i].admin) status='warning';
            else if(roomusers.list[i].marked) status='success';

            usertime=userentry.querySelector('#usertime');
            if(usertime) usertime.textContent=getUTCTime(UTC((new Date())-roomusers.list[i].start*1000-servertime*1000));

            icontalk=userentry.querySelector('#icon-talk');
            if(roomusers.list[i].talk) {
              if(icontalk) icontalk.classList.remove('hidden');
            } else {
              if(icontalk) icontalk.classList.add('hidden');
            }
            if(roomusers.list[i].online) { //online user
              if(usertime) usertime.classList.remove('hidden');
            } else {
              if(usertime) usertime.classList.add('hidden');
            }
            if(listview) {
              status='';
              if(roomusers.list[i].hold) status='dark';
              else if(roomusers.list[i].marked&&roomusers.list[i].admin) status='danger';
              else if(roomusers.list[i].admin) status='warning';
              else if(roomusers.list[i].marked) status='success';
              if(!roomusers.list[i].online) {
                if(roomusers.list[i].dialing) status='info';
                else if(roomusers.list[i].scheduled) status='secondary';
              } else {
              }

              userentry.classList.remove('list-group-item-secondary');
              userentry.classList.remove('list-group-item-danger');
              userentry.classList.remove('list-group-item-warning');
              userentry.classList.remove('list-group-item-success');
              userentry.classList.remove('list-group-item-dark');
              userentry.classList.remove('list-group-item-info');
              if(status!='') userentry.classList.add('list-group-item-'+status);
              if(roomusers.list[i].selected) userentry.classList.add('active'); else userentry.classList.remove('active');
              var usertext = userentry.querySelector('span[class=text]');
              if(usertext.textContent!=roomusers.list[i].name) usertext.textContent=roomusers.list[i].name;
              usertext=usertext.nextSibling.nextSibling;
              var usernumber=roomusers.list[i].number;
              if(roomusers.list[i].extnum!='') usernumber+='P'+roomusers.list[i].extnum;
              if(usertext.innerHTML!=usernumber) usertext.innerHTML=usernumber;
              usertext=usertext.nextSibling;
              if(usertext.innerHTML!=('<'+usernumber+'>')) usertext.innerHTML='<'+usernumber+'>';

              if(roomusers.list[i].online) { //online user
                if(roomusers.list[i].selected) {
                  if(usertime) usertime.classList.remove('badge-secondary');
                  if(usertime) usertime.classList.add('badge-light');
                } else {
                  if(usertime) usertime.classList.remove('badge-light');
                  if(usertime) usertime.classList.add('badge-secondary');
                }
              }

              buttonmute=userentry.querySelector('#button-mute');
              buttonisolate=userentry.querySelector('#button-isolate');
              buttonkick=userentry.querySelector('#button-kick');
              buttoncall=userentry.querySelector('#button-call');
              if(roomusers.list[i].online) { //online user
                if(buttonmute) buttonmute.classList.remove('hidden');
                if(buttonisolate) buttonisolate.classList.remove('hidden');
                if(buttonkick) buttonkick.classList.remove('hidden');
                if(buttoncall) buttoncall.classList.add('hidden');
                if(roomusers.list[i].muted) {
                  if(buttonmute) buttonmute.classList.remove('icon-mute');
                  if(buttonmute) buttonmute.classList.add('icon-unmute');
                  if(buttonmute) buttonmute.classList.add('active');
                } else {
                  if(buttonmute) buttonmute.classList.remove('active');
                  if(buttonmute) buttonmute.classList.remove('icon-unmute');
                  if(buttonmute) buttonmute.classList.add('icon-mute');
                } 
                if(roomusers.list[i].isolated) {
                  if(buttonisolate) buttonisolate.classList.remove('icon-circle-slash');
                  if(buttonisolate) buttonisolate.classList.add('icon-return');
                  if(buttonisolate) buttonisolate.classList.remove('btn-warning');
                  if(buttonisolate) buttonisolate.classList.add('btn-success');
                } else {
                  if(buttonisolate) buttonisolate.classList.remove('icon-return');
                  if(buttonisolate) buttonisolate.classList.add('icon-circle-slash');
                  if(buttonisolate) buttonisolate.classList.remove('btn-success');
                  if(buttonisolate) buttonisolate.classList.add('btn-warning');
                }
              } else { //persistent offline user
                if(buttonmute) buttonmute.classList.add('hidden');
                if(buttonisolate) buttonisolate.classList.add('hidden');
                if(roomusers.list[i].dialing) {
                  if(buttonkick) buttonkick.classList.remove('hidden');
                  if(buttoncall) buttoncall.classList.add('hidden');
                } else {
                  if(roomusers.list[i].scheduled) {
                    if(buttoncall) buttoncall.classList.add('hidden');
                    if(buttonkick) buttonkick.classList.remove('hidden');
                  } else {
                    if(buttonkick) buttonkick.classList.add('hidden');
                    if(buttoncall) buttoncall.classList.remove('hidden');
                  }
                }
              }
            } else {
              usercard=userentry.firstChild;
              usercard.classList.remove('bg-secondary');
              usercard.classList.remove('bg-danger');
              usercard.classList.remove('bg-warning');
              usercard.classList.remove('bg-succcess');
              if(status!='') usercard.classList.add('bg-'+status);
              cardheader=usercard.firstChild;
              if(roomusers.list[i].selected) {
                cardheader.classList.remove('bg-light');
                cardheader.classList.add('bg-primary');
              } else {
                cardheader.classList.remove('bg-primary');
                cardheader.classList.add('bg-light');
              }

              textarea=userentry.querySelector('#name');
              if(textarea.textContent!=roomusers.list[i].name) textarea.textContent=roomusers.list[i].name;

              numberarea=userentry.querySelector('#number');
              if(roomusers.list[i].selected) {
                numberarea.classList.remove('badge-secondary');
                numberarea.classList.add('badge-light');
              } else {
                numberarea.classList.remove('badge-light');
                numberarea.classList.add('badge-secondary');
              }
              var usernumber = roomusers.list[i].number;
              if(roomusers.list[i].extnum!='') usernumber+='P'+roomusers.list[i].extnum;
              if(numberarea.innerHTML!=usernumber) numberarea.innerHTML=usernumber;

              iconpause=usercard.querySelector('#icon-pause');
              iconhold=usercard.querySelector('#icon-hold');
              iconmute=usercard.querySelector('#icon-mute');
              iconmoh=usercard.querySelector('#icon-moh');
              iconyellow=usercard.querySelector('#icon-yellow');
              icongreen=usercard.querySelector('#icon-green');
              iconfail=usercard.querySelector('#icon-fail');
              iconwait=usercard.querySelector('#icon-wait');
              iconring=usercard.querySelector('#icon-ring');
              if(!roomusers.list[i].online) {
                icontalk.classList.add('hidden');
                iconpause.classList.add('hidden');
                iconhold.classList.add('hidden');
                iconmute.classList.add('hidden');
                iconmoh.classList.add('hidden');
                iconyellow.classList.add('hidden');
                icongreen.classList.add('hidden');
                if(roomusers.list[i].dialing) {
                  iconfail.classList.add('hidden');
                  iconwait.classList.add('hidden');
                  iconring.classList.remove('hidden');
                } else if(roomusers.list[i].scheduled) {
                  iconring.classList.add('hidden');
                  iconfail.classList.add('hidden');
                  iconwait.classList.remove('hidden');
                } else if(roomusers.list[i].failed) {
                  iconring.classList.add('hidden');
                  iconwait.classList.add('hidden');
                  iconfail.classList.remove('hidden');
                } else {
                  iconfail.classList.add('hidden');
                  iconwait.classList.add('hidden');
                  iconring.classList.add('hidden');
                }
              } else {
                iconring.classList.add('hidden');
                iconfail.classList.add('hidden');
                iconwait.classList.add('hidden');
                if(roomusers.list[i].isolated) {
                  iconpause.classList.remove('hidden');
                } else {
                  iconpause.classList.add('hidden');
                }
                if(roomusers.list[i].hold) {
                  iconhold.classList.remove('hidden');
                } else {
                  iconhold.classList.add('hidden');
                }
                if(roomusers.list[i].muted) {
                  iconmute.classList.remove('hidden');
                } else {
                  iconmute.classList.add('hidden');
                }
                if(roomusers.list[i].moh) {
                  iconmoh.classList.remove('hidden');
                } else {
                  iconmoh.classList.add('hidden');
                }
                if(roomusers.list[i].isolated|roomusers.list[i].hold|roomusers.list[i].moh) {
                  iconyellow.classList.remove('hidden');
                  icongreen.classList.add('hidden');
                } else {
                  icongreen.classList.remove('hidden');
                  iconyellow.classList.add('hidden');
                }
              }
            }
          }
        } else $(room).find('#users').html('');
        room=$(room);
        if(onlineselected>0) {
          room.find('#conf-lock-btn button').text('Изолировать')[0].dataset['originalTitle']='Изолировать выделенных абонентов';;
        }
        if(stoplessness>0) {
          room.find('#conf-stop-btn button').text('Отбить')[0].dataset['originalTitle']='Отбить выделенных абонентов';
          room.find('#conf-stop-btn').show();
        } else {
          if((scheduled>0)||running) {
            room.find('#conf-stop-btn button').text('Завершить')[0].dataset['originalTitle']='Завершить конференцию';
            room.find('#conf-stop-btn').show();
          } else {
            room.find('#conf-stop-btn').hide();
          }
        }
        if(onlineselected>=2) {
          room.find('#conf-merge-btn').show();
        } else {
          room.find('#conf-merge-btn').hide();
        }
        if(offlineselected>0) {
          room.find('#conf-start-btn button').text('Вызвать')[0].dataset['originalTitle']='Вызвать выделенных абонентов';
          if(allowcallout||active) {
            room.find('#conf-start-btn').show();
          } else {
            room.find('#conf-start-btn').hide();
          }
        } else {
          room.find('#conf-start-btn button').text('Запустить')[0].dataset['originalTitle']='Начать вызов всем абонентам комнаты';
          if(running) {
            room.find('#conf-start-btn').hide();
          } else {
            if(allowcallout||active) {
              room.find('#conf-start-btn').show();
            } else {
              room.find('#conf-start-btn').hide();
            }
          }
        }
        updateUsersTimeout = setTimeout(updateRoomUsers,1000);
      }

      function renderRoomUsers() {
        clearTimeout(updateUsersTimeout);
        var room=$('#confbridge-data');
        var listview=localStorage.getItem("confbridge-view")!=="true";
        var onlineselected = 0;
        var stoplessness = 0;
        var scheduled = 0;
        var offlineselected = 0;
        if(roomusers.list.length||roomusers.groups.length) {
          var users = null;
          if(listview) {
            users=document.createRange().createContextualFragment('<ul class="list-group d-inline-block col-12 c-1 c-md-2 c-lg-2 c-xl-3 pr-3" id="confbridge-users-list"></ul>').firstElementChild;
          } else {
            users=document.createRange().createContextualFragment('<div class="form-group d-flex flex-wrap col-12" id="confbridge-users-list"></div>').firstElementChild;
          }
          for(var j = 0; j < roomusers.groups.length; j++) {
            if(listview) {
              group=document.createRange().createContextualFragment('<li class="list-group-item" style="padding-left: 0; padding-right: 0;"><ul class="list-group d-inline-block col-12 c-1 c-md-2 c-lg-2 c-xl-3 pr-3" id="confbridge-subgroup-'+j+'-list"></ul></li>').firstElementChild;
              groupdata=group.firstChild;
            } else {
              group=document.createRange().createContextualFragment('<div class="form-group col-12 rounded d-flex flex-wrap" id="confbridge-subgroup-'+j+'-list"></div>').firstElementChild;
              groupdata=group;
            }
            for(var i = 0; i < roomusers.groups[j].users.length; i++) {
              var userentry=null;
              var status='secondary';
              if(roomusers.groups[j].users[i].marked&&roomusers.groups[j].users[i].admin) status='danger';
              else if(roomusers.groups[j].users[i].admin) status='warning';
              else if(roomusers.groups[j].users[i].marked) status='success';
              usertime=document.createElement('span');
              usertime.classList.add('right');
              usertime.textContent=getUTCTime(UTC((new Date()).getTime()-roomusers.groups[j].users[i].start*1000-servertime*1000));
              usertime.id='usertime';
              buttonsarea=null;
              infoarea=null;
              if(listview) {
                status='';
                if(roomusers.groups[j].users[i].hold) status='dark';
                else if(roomusers.groups[j].users[i].marked&&roomusers.groups[j].users[i].admin) status='danger';
                else if(roomusers.groups[j].users[i].admin) status='warning';
                else if(roomusers.groups[j].users[i].marked) status='success';
                var channel=roomusers.groups[j].users[i].channel;
                if(roomusers.groups[j].users[i].extnum!='') channel+='P'+roomusers.groups[j].users[i].extnum;
                userentry=document.createRange().createContextualFragment('<li class="small list-group-item pt-1 pb-1" id="subuser-'+channel+'"></li>').firstElementChild;
                if(status!='') userentry.classList.add('list-group-item-'+status);
                var usernumber=roomusers.groups[j].users[i].number;
                if(roomusers.groups[j].users[i].extnum!='') usernumber+='P'+roomusers.groups[j].users[i].extnum;
                userentry.appendChild(document.createRange().createContextualFragment('<span class="text">'+roomusers.groups[j].users[i].name+'</span><br><small class="d-inline-block d-lg-none">'+usernumber+'</small><span class="d-none d-lg-inline-block">&lt;'+usernumber+'&gt;</span>'));
                talkarea=document.createElement('span');
                talkarea.classList.add('icon-megaphon');
                talkarea.textContent='';
                talkarea.id='icon-talk';
                if(!roomusers.groups[j].users[i].talk) {
                  talkarea.classList.add('hidden');
                }
                userentry.appendChild(talkarea);
                usertime.classList.add('badge');
                usertime.classList.add('badge-secondary');
                usertime.classList.add('badge-pill');
                buttonsarea=document.createElement('span');
                buttonsarea.classList.add('list-btn-group');
                buttonsarea.classList.add('right');
                if(readonly) buttonsarea.classList.add('invisible');
                userentry.appendChild(buttonsarea);
                buttonsarea.appendChild(usertime);
                buttonelement = document.createRange().createContextualFragment('<button class="btn btn-warning icon icon-return right" onClick="event.stopPropagation(); kickUsers([\''+roomusers.groups[j].users[i].channel+'\'], \''+roomusers.groups[j].number+'\')"></button>').firstElementChild;
                buttonsarea.appendChild(buttonelement);
              } else {
                var channel=roomusers.groups[j].users[i].channel;
                if(roomusers.groups[j].users[i].extnum!='') channel+='P'+roomusers.groups[j].users[i].extnum;
                userentry=document.createRange().createContextualFragment('<div class="form-group user-card"><div class="card small" id="subuser-'+channel+'"></div></div>').firstElementChild;
                usercard=userentry.firstChild;
                if(status!='') usercard.classList.add('bg-'+status);
                cardheader=document.createRange().createContextualFragment('<div class="card-header"></div>').firstElementChild;
                usercard.appendChild(cardheader);
                cardheader.classList.add('bg-light');

                textarea=document.createElement('span');
                textarea.classList.add('left');
                textarea.textContent=roomusers.groups[j].users[i].name;
                textarea.id='name';
                cardheader.appendChild(textarea);

                cardheader.appendChild(document.createElement('br'));
                numberarea=document.createElement('span');
                numberarea.classList.add('badge');
                numberarea.classList.add('left');
                numberarea.classList.add('badge-secondary');
                numberarea.classList.add('badge-pill');
                var usernumber=roomusers.groups[j].users[i].number;
                if(roomusers.groups[j].users[i].extnum!='') usernumber+='P'+roomusers.groups[j].users[i].extnum;
                numberarea.innerHTML=usernumber;
                numberarea.id='number';
                cardheader.appendChild(numberarea);

                iconarea=document.createElement('span');
                iconarea.classList.add('icon-phone-isolated');
                iconarea.textContent='';
                iconarea.id='icon-hold';
                iconhold=iconarea;
                cardheader.appendChild(iconarea);
                iconarea=document.createElement('span');
                iconarea.classList.add('icon-phone-duplex');
                iconarea.textContent='';
                iconarea.id='icon-duplex';
                iconduplex=iconarea;
                cardheader.appendChild(iconarea);
                if(roomusers.groups[j].users[i].online) {
                  if(roomusers.groups[j].users[i].hold) {
                    iconduplex.classList.add('hidden');
                  } else {
                    iconhold.classList.add('hidden');
                  }
                }
                talkarea=document.createElement('span');
                talkarea.classList.add('icon-megaphon');
                talkarea.textContent='';
                talkarea.id='icon-talk';
                if(!roomusers.groups[j].users[i].talk) {
                  talkarea.classList.add('hidden');
                }
                cardheader.appendChild(talkarea);

                cardheader.appendChild(usertime);
                if(!readonly) $(cardheader).contextMenu({menuSelector: "#cardContextMenu", group: roomusers.groups[j].number, user: roomusers.groups[j].users[i].channel, menuOpen: function(invokedOn) {
                  cleanMenu();
                  invokedOn.find('#menu-mute').hide();
                  invokedOn.find('#menu-unmute').hide();
                  invokedOn.find('#menu-hold').hide();
                  invokedOn.find('#menu-unhold').hide();
                  invokedOn.find('#menu-kick').hide();
                  invokedOn.find('#menu-return').show();
                  invokedOn.find('#menu-returnall').show();
                  invokedOn.find('#menu-volumein').hide();
                  invokedOn.find('#menu-volumeout').hide();
                  invokedOn.find('#menu-call').hide();
                  return true;
                }, menuSelected: function(invokedOn, selectedMenu) {
                  var group = invokedOn.data("group");
                  var user = invokedOn.data("user");
                  switch(selectedMenu.get(0).id) {
                    case 'menu-return': kickUsers([user], group); break;
                    case 'menu-returnall': kickAll(group); break;
                  }
                }});
              }
              groupdata.appendChild(userentry);
            }
            users.appendChild(group);
          }
          var groups = [];
          for(var i = 0; i < roomusers.groupids.length; i++) {
            if(listview) {
              group=document.createRange().createContextualFragment('<li class="list-group-item virtual"><div class="group-header" onClick="selectGroup(\''+roomusers.groupids[i].id+'\')" id="confbridge-group-'+roomusers.groupids[i].id.split(' ').join('_')+'-list">'+roomusers.groupids[i].id+'</div><ul class="list-group col-12"></ul></li>').firstElementChild;
              groupdata=group.firstChild.nextSibling;
            } else {
              group=document.createRange().createContextualFragment('<div class=\"form-group col-12 rounded d-flex flex-wrap virtual\" onClick="selectGroup(\''+roomusers.groupids[i].id+'\')" id="confbridge-group-'+roomusers.groupids[i].id.split(' ').join('_')+'-list"><div class="group-header">'+roomusers.groupids[i].id+'</div></div>').firstElementChild;
              groupdata=group;
            }
            groups.push({group: group, groupdata: groupdata, groupid: roomusers.groupids[i].id});
          }
          for(var i = 0; i < roomusers.list.length; i++) {
            var userentry=null;
            if(roomusers.list[i].selected) {
              if(roomusers.list[i].online)
                onlineselected++;
              else
                offlineselected++;
              if(roomusers.list[i].online||roomusers.list[i].dialing||roomusers.list[i].scheduled)
                stoplessness++;
            }
            if(roomusers.list[i].dialing||roomusers.list[i].scheduled)
              scheduled++;
            var status='secondary';
            if(roomusers.list[i].marked&&roomusers.list[i].admin) status='danger';
            else if(roomusers.list[i].admin) status='warning';
            else if(roomusers.list[i].marked) status='success';
            usertime=document.createElement('span');
            usertime.classList.add('right');
            usertime.textContent=getUTCTime(UTC((new Date())-roomusers.list[i].start*1000-servertime*1000));
            usertime.id='usertime';
            buttonsarea=null;
            infoarea=null;
            if(listview) {
              status='';
              if(roomusers.list[i].hold) status='dark';
              else if(roomusers.list[i].marked&&roomusers.list[i].admin) status='danger';
              else if(roomusers.list[i].admin) status='warning';
              else if(roomusers.list[i].marked) status='success';
              if(!roomusers.list[i].online) {
                if(roomusers.list[i].dialing) status='info';
                else if(roomusers.list[i].scheduled) status='secondary';
              } else {
              }
              var channel=roomusers.list[i].channel;
              if(roomusers.list[i].extnum!='') channel+='P'+roomusers.list[i].extnum;
              userentry=document.createRange().createContextualFragment('<li class="small list-group-item pt-1 pb-1" id="user-'+channel+'" onClick="event.stopPropagation(); roomusers.list['+i+'].selected = !roomusers.list['+i+'].selected; updateRoomUsers();"></li>').firstElementChild;
              if(status!='') userentry.classList.add('list-group-item-'+status);
              if(roomusers.list[i].selected) userentry.classList.add('active');
              var usernumber=roomusers.list[i].number;
              if(roomusers.list[i].extnum!='') usernumber+='P'+roomusers.list[i].extnum;
              userentry.appendChild(document.createRange().createContextualFragment('<span class="text">'+roomusers.list[i].name+'</span><br><small class="d-inline-block d-lg-none">'+usernumber+'</small><span class="d-none d-lg-inline-block">&lt;'+usernumber+'&gt;</span>'));
              talkarea=document.createElement('span');
              talkarea.classList.add('icon-megaphon');
              talkarea.textContent='';
              talkarea.id='icon-talk';
              if(!roomusers.list[i].talk) {
                talkarea.classList.add('hidden');
              }
              userentry.appendChild(talkarea);

              usertime.classList.add('badge');
              usertime.classList.add('badge-pill');
              if(roomusers.list[i].online) { //online user
                if(roomusers.list[i].selected) {
                  usertime.classList.add('badge-light');
                } else {
                  usertime.classList.add('badge-secondary');
                }
              } else {
                usertime.classList.add('hidden');
              }
              buttonsarea=document.createElement('span');
              buttonsarea.classList.add('list-btn-group');
              buttonsarea.classList.add('right');
              if(readonly) buttonsarea.classList.add('invisible');
              userentry.appendChild(buttonsarea);
              buttonsarea.appendChild(usertime);
              buttonmute = document.createRange().createContextualFragment('<button class="btn btn-info icon" id="button-mute" onClick="event.stopPropagation(); muteRoom(this, \''+roomusers.list[i].channel+'\')"></button>').firstElementChild;
              buttonsarea.appendChild(buttonmute);
              buttonkick = document.createRange().createContextualFragment('<button class="btn btn-danger icon icon-trash right" id="button-kick" onClick="event.stopPropagation(); kickUsers([\''+roomusers.list[i].channel+'\'], \''+id+'\')"></button>').firstElementChild;
              buttonsarea.appendChild(buttonkick);
              buttonisolate = document.createRange().createContextualFragment('<button class="btn btn-warning icon" id="button-isolate" onClick="event.stopPropagation(); isolateUser(\''+roomusers.list[i].channel+'\')"></button>').firstElementChild;
              buttonsarea.appendChild(buttonisolate);
              buttoncall = document.createRange().createContextualFragment('<button class="btn btn-success icon icon-call right" id="button-call" onClick="event.stopPropagation(); callRoomUser('+roomusers.list[i].id+', \''+roomusers.list[i].groupid+'\')"></button>').firstElementChild;
              buttonsarea.appendChild(buttoncall);
              if(roomusers.list[i].online) { //online user
                buttoncall.classList.add('hidden');
                if(roomusers.list[i].muted) {
                  buttonmute.classList.add('icon-unmute');
                  buttonmute.classList.add('active');
                } else {
                  buttonmute.classList.add('icon-mute');
                }
                if(roomusers.list[i].isolated) {
                  buttonisolate.classList.add('icon-return');
                  buttonisolate.classList.remove('btn-warning');
                  buttonisolate.classList.add('btn-success');
                } else {
                  buttonisolate.classList.add('icon-circle-slash');
                }
              } else { //persistent offline user
                buttonmute.classList.add('hidden');
                buttonisolate.classList.add('hidden');
                if(roomusers.list[i].dialing) {
                  buttoncall.classList.add('hidden');
                } else {
                  if(roomusers.list[i].scheduled) {
                    buttoncall.classList.add('hidden');
                  } else {
                    buttonkick.classList.add('hidden');
                  }
                }
              }
            } else {
              var channel=roomusers.list[i].channel;
              if(roomusers.list[i].extnum!='') channel+='P'+roomusers.list[i].extnum;
              userentry=document.createRange().createContextualFragment('<div class="form-group user-card" id="user-'+channel+'"><div class="card small" onClick="event.stopPropagation(); roomusers.list['+i+'].selected = !roomusers.list['+i+'].selected; updateRoomUsers();"></div></div>').firstElementChild;
              usercard=userentry.firstChild;
              if(status!='') usercard.classList.add('bg-'+status);
              cardheader=document.createRange().createContextualFragment('<div class="card-header"></div>').firstElementChild;
              if(roomusers.list[i].selected) {
                cardheader.classList.add('bg-primary');
              } else {
                cardheader.classList.add('bg-light');
              }
              usercard.appendChild(cardheader);

              textarea=document.createElement('span');
              textarea.classList.add('left');
              textarea.textContent=roomusers.list[i].name;
              textarea.id='name';
              cardheader.appendChild(textarea);

              cardheader.appendChild(document.createElement('br'));
              numberarea=document.createElement('span');
              numberarea.classList.add('badge');
              numberarea.classList.add('left');
              if(roomusers.list[i].selected) {
                numberarea.classList.add('badge-light');
              } else {
                numberarea.classList.add('badge-secondary');
              }
              numberarea.classList.add('badge-pill');
              var usernumber=roomusers.list[i].number;
              if(roomusers.list[i].extnum!='') usernumber+='P'+roomusers.list[i].extnum;
              numberarea.innerHTML=usernumber;
              numberarea.id='number';
              cardheader.appendChild(numberarea);

              iconarea=document.createElement('span');
              iconarea.classList.add('icon-ring-success');
              iconarea.textContent='';
              iconarea.id='icon-ring';
              iconarea.classList.add('hidden');
              iconring=iconarea;
              cardheader.appendChild(iconarea);

              iconarea=document.createElement('span');
              iconarea.classList.add('icon-ring-wait');
              iconarea.textContent='';
              iconarea.id='icon-wait';
              iconarea.classList.add('hidden');
              iconwait=iconarea;
              cardheader.appendChild(iconarea);

              iconarea=document.createElement('span');
              iconarea.classList.add('icon-ring-error');
              iconarea.textContent='';
              iconarea.id='icon-fail';
              iconarea.classList.add('hidden');
              iconfail=iconarea;
              cardheader.appendChild(iconarea);

              talkarea=document.createElement('span');
              talkarea.classList.add('icon-megaphone');
              talkarea.textContent='';
              talkarea.id='icon-talk';
              talkarea.classList.add('hidden');
              cardheader.appendChild(talkarea);

              iconarea=document.createElement('span');
              iconarea.classList.add('icon-pause');
              iconarea.textContent='';
              iconarea.id='icon-pause';
              iconarea.classList.add('hidden');
              iconpause=iconarea;
              cardheader.appendChild(iconarea);

              iconarea=document.createElement('span');
              iconarea.classList.add('icon-sign-in');
              iconarea.textContent='';
              iconarea.id='icon-hold';
              iconarea.classList.add('hidden');
              iconhold=iconarea;
              cardheader.appendChild(iconarea);

              iconarea=document.createElement('span');
              iconarea.classList.add('icon-micstrip');
              iconarea.textContent='';
              iconarea.id='icon-mute';
              iconarea.classList.add('hidden');
              iconmute=iconarea;
              cardheader.appendChild(iconarea);

              iconarea=document.createElement('span');
              iconarea.classList.add('icon-moh');
              iconarea.textContent='';
              iconarea.id='icon-moh';
              iconarea.classList.add('hidden');
              iconmoh=iconarea;
              cardheader.appendChild(iconarea);

              iconarea=document.createElement('span');
              iconarea.classList.add('icon-phone-yellow');
              iconarea.textContent='';
              iconarea.id='icon-yellow';
              iconarea.classList.add('hidden');
              iconyellow=iconarea;
              cardheader.appendChild(iconarea);

              iconarea=document.createElement('span');
              iconarea.classList.add('icon-phone-duplex');
              iconarea.textContent='';
              iconarea.id='icon-green';
              iconarea.classList.add('hidden');
              icongreen=iconarea;
              cardheader.appendChild(iconarea);

              if(!roomusers.list[i].online) {
                if(roomusers.list[i].dialing) {
                  iconring.classList.remove('hidden');
                } else if(roomusers.list[i].scheduled) {
                  iconwait.classList.remove('hidden');
                } else if(roomusers.list[i].failed) {
                  iconfail.classList.remove('hidden');
                }
              } else {
                if(roomusers.list[i].talk) {
                  talkarea.classList.remove('hidden');
                }
                if(roomusers.list[i].isolated) {
                  iconpause.classList.remove('hidden');
                }
                if(roomusers.list[i].hold) {
                  iconhold.classList.remove('hidden');
                }
                if(roomusers.list[i].muted) {
                  iconmute.classList.remove('hidden');
                }
                if(roomusers.list[i].moh) {
                  iconmoh.classList.remove('hidden');
                }
                if(roomusers.list[i].isolated) {
                  iconyellow.classList.remove('hidden');
                } else if(roomusers.list[i].hold) {
                  iconyellow.classList.remove('hidden');
                } else if(roomusers.list[i].muted) {
                  icongreen.classList.remove('hidden');
                } else if(roomusers.list[i].moh) {
                  iconyellow.classList.remove('hidden');
                } else {
                  icongreen.classList.remove('hidden');
                }
              }

              cardheader.appendChild(usertime);
              if(!roomusers.list[i].online) { //online user
                usertime.classList.add('hidden');
              }
              if(!readonly) $(cardheader).contextMenu({menuSelector: "#cardContextMenu", user: roomusers.list[i].channel, menuOpen: function(invokedOn) {
                cleanMenu();
                var channel = invokedOn.data("user");
                var i = 0;
                for(var j=0; j<roomusers.list.length; j++) {
                  if(roomusers.list[j].channel==channel) {
                    i=j;
                    break;
                  }
                }
                invokedOn.find('#menu-volumein > div > button.dropdown-item').removeClass('active');
                invokedOn.find('#menu-volumeout > div > button.dropdown-item').removeClass('active');
                if(roomusers.list[i].online) { //online user
                 invokedOn.find('#menu-mute').hide();
                 invokedOn.find('#menu-unmute').hide();
                 if(typeof roomusers.list[i].volumein != 'undefined') {
                  let volumein = roomusers.list[i].volumein;
                  let volumeout = roomusers.list[i].volumeout;
                  if(volumein>0) volumein = 'p'+volumein;
                  else if(volumein==0) volumein = '0';
                  else volumein = 'm'+(volumein*-1);
                  if(volumeout>0) volumeout = 'p'+volumeout;
                  else if(volumeout==0) volumeout = '0';
                  else volumeout = 'm'+(volumeout*-1);
                  invokedOn.find('#menu-volumein').show().find('div > button.dropdown-item[id=volin-'+volumein+']').addClass('active');
                  invokedOn.find('#menu-volumeout').show().find('div > button.dropdown-item[id=volout-'+volumeout+']').addClass('active');;
                 } else {
                  invokedOn.find('#menu-volumein').hide();
                  invokedOn.find('#menu-volumeout').hide();
                 }
                 if(roomusers.list[i].muted) {
                   invokedOn.find('#menu-unmute').show();
                 } else {
                   invokedOn.find('#menu-mute').show();
                 }
                 invokedOn.find('#menu-hold').hide();
                 invokedOn.find('#menu-unhold').hide();
                 if(roomusers.list[i].isolated) {
                   invokedOn.find('#menu-unhold').show();
                 } else {
                   invokedOn.find('#menu-hold').show();
                 }
                 invokedOn.find('#menu-kick').show();
                 invokedOn.find('#menu-return').hide();
                 invokedOn.find('#menu-returnall').hide();
                 invokedOn.find('#menu-call').hide();
                 if(allowcallout||active) {
                   addMenuCalls(roomusers.list[i]);
                 }
                } else { //persistent offline user
                 if(roomusers.list[i].dialing) {
                   invokedOn.find('#menu-volumein').hide();
                   invokedOn.find('#menu-volumeout').hide();
                   invokedOn.find('#menu-mute').hide();
                   invokedOn.find('#menu-unmute').hide();
                   invokedOn.find('#menu-hold').hide();
                   invokedOn.find('#menu-unhold').hide();
                   invokedOn.find('#menu-kick').show();
                   invokedOn.find('#menu-return').hide();
                   invokedOn.find('#menu-returnall').hide();
                   invokedOn.find('#menu-call').hide();
                   if(allowcallout||active) {
                     addMenuCalls(roomusers.list[i]);
                   }
                 } else {
                   if(roomusers.list[i].scheduled) {
                     invokedOn.find('#menu-volumein').hide();
                     invokedOn.find('#menu-volumeout').hide();
                     invokedOn.find('#menu-mute').hide();
                     invokedOn.find('#menu-unmute').hide();
                     invokedOn.find('#menu-hold').hide();
                     invokedOn.find('#menu-unhold').hide();
                     invokedOn.find('#menu-kick').show();
                     invokedOn.find('#menu-return').hide();
                     invokedOn.find('#menu-returnall').hide();
                     invokedOn.find('#menu-call').hide();
                     if(allowcallout||active) {
                       addMenuCalls(roomusers.list[i]);
                     }
                   } else {
                     invokedOn.find('#menu-volumein').hide();
                     invokedOn.find('#menu-volumeout').hide();
                     invokedOn.find('#menu-mute').hide();
                     invokedOn.find('#menu-unmute').hide();
                     invokedOn.find('#menu-hold').hide();
                     invokedOn.find('#menu-unhold').hide();
                     invokedOn.find('#menu-kick').hide();
                     invokedOn.find('#menu-return').hide();
                     invokedOn.find('#menu-returnall').hide();
                     if(allowcallout||active) {
                       invokedOn.find('#menu-call').show();
                       addMenuCalls(roomusers.list[i]);
                     } else {
                       invokedOn.find('#menu-call').hide();
                       return false;
                     }
                   }
                 }
                }
                return true;
              }, menuSelected: function(invokedOn, selectedMenu) {
                var channel = invokedOn.data("user");
                if(selectedMenu.get(0).id.indexOf("vol")===0) {
                  let mode = selectedMenu.get(0).id.split('-')[0].substr(3);
                  setUserVolume(channel, mode, selectedMenu.get(0).textContent);
                } else {
                  switch(selectedMenu.get(0).id) {
                    case 'menu-call': {
                      var i = 0;
                      for(var j=0; j<roomusers.list.length; j++) {
                        if(roomusers.list[j].channel==channel) {
                          i=j;
                          break;
                        }
                      }
                      callRoomUser(roomusers.list[i].id, roomusers.list[i].groupid);
                    } break;
                    case 'menu-mute': muteRoom('', channel); break;
                    case 'menu-unmute': muteRoom('un', channel); break;
                    case 'menu-hold': isolateUser(channel); break;
                    case 'menu-unhold': isolateUser(channel); break;
                    case 'menu-kick': kickUsers([channel], id); break;
                    default: {
                      if(selectedMenu.get(0).id.indexOf('menu-call-')===0) {
                        let r = selectedMenu.get(0).id.substr(10);
                        var i = 0;
                        for(var j=0; j<roomusers.list.length; j++) {
                          if(roomusers.list[j].channel==channel) {
                            i=j;
                            break;
                          }
                        }
                        callRoomUser(roomusers.list[i].id, roomusers.list[i].groupid, roomusers.list[i].channels[r]);
                      }
                    }
                  }
                }
              }});
            }

            if(roomusers.list[i].groupid!='') {
              for(var j = 0; j < groups.length; j++) {
                if(groups[j].groupid==roomusers.list[i].groupid)
                  groups[j].groupdata.appendChild(userentry);
              }
            } else {
              users.appendChild(userentry);
            }
          }
          for(var i = 0; i < groups.length; i++) {
            if(!readonly) $(groups[i].group).contextMenu({menuSelector: "#cardContextMenu", group: groups[i].groupid, user: 0, menuOpen: function(invokedOn) {
              cleanMenu();
              invokedOn.find('#menu-call').hide();
              invokedOn.find('#menu-mute').hide();
              invokedOn.find('#menu-unmute').hide();
              invokedOn.find('#menu-hold').hide();
              invokedOn.find('#menu-unhold').hide();
              invokedOn.find('#menu-kick').hide();
              var online=0;
              var offline=0;
              var atline=0;
              var muted=0;
              var unmuted=0;
              var isolated=0;
              var unisolated=0;
              var groupid = invokedOn.data("group");
              for(var j = 0; j < roomusers.list.length; j++) {
                if(roomusers.list[j].groupid==groupid) {
                  if(roomusers.list[j].online) {
                    online++;
                    if(roomusers.list[j].muted) muted++; else unmuted++;
                    if(roomusers.list[j].isolated) isolated++; else unisolated++;
                  } else if(roomusers.list[j].dialing||roomusers.list[j].scheduled) atline++;
                  else offline++;
                }
              }
              if(online>0) {
                if(unmuted>0) invokedOn.find('#menu-mute').show();
                if(muted>0) invokedOn.find('#menu-unmute').show();
                if(unisolated>0) invokedOn.find('#menu-hold').show();
                if(isolated>0) invokedOn.find('#menu-unhold').show();
              }
              if((online>0)||(atline>0)) {
                invokedOn.find('#menu-kick').show();
              }
              if(offline>0) {
                if(allowcallout||active) {
                  invokedOn.find('#menu-call').show();
                } else {
                  invokedOn.find('#menu-call').hide();
                  if(online==0) return false;
                }
              }
              invokedOn.find('#menu-return').hide();
              invokedOn.find('#menu-returnall').hide();
              invokedOn.find('#menu-volumein').hide();
              invokedOn.find('#menu-volumeout').hide();
              return true;
            }, menuSelected: function(invokedOn, selectedMenu) {
              var j = invokedOn.data("group");
              switch(selectedMenu.get(0).id) {
                case 'menu-mute': muteGroup(j); break;
                case 'menu-unmute': unmuteGroup(j); break;
                case 'menu-hold': holdGroup(j); break;
                case 'menu-unhold': unholdGroup(j); break;
                case 'menu-call': callGroup(j); break;
                case 'menu-kick': kickGroup(j); break;
              }
            }});
            users.appendChild(groups[i].group);
          }
          room.find('#users').empty().get(0).appendChild(users);
          room.find('#users ul li span.text').each(function() {
            var obj = $(this);
            obj.css({width: obj.parent().find('.list-btn-group').position().left-10});
          });
        } else room.find('#users').html('');
        if(onlineselected>0) {
          room.find('#conf-lock-btn button').text('Изолировать')[0].dataset['originalTitle']='Изолировать выделенных абонентов';;
        }
        if(stoplessness>0) {
          room.find('#conf-stop-btn button').text('Отбить')[0].dataset['originalTitle']='Отбить выделенных абонентов';
          room.find('#conf-stop-btn').show();
        } else {
          if(running||(scheduled>0)) {
            room.find('#conf-stop-btn button').text('Завершить')[0].dataset['originalTitle']='Завершить конференцию';;
            room.find('#conf-stop-btn').show();
          } else {
            room.find('#conf-stop-btn').hide();
          }
        }
        if(onlineselected>=2) {
          room.find('#conf-merge-btn').show();
        } else {
          room.find('#conf-merge-btn').hide();
        }
        if(offlineselected>0) {
          room.find('#conf-start-btn button').text('Вызвать')[0].dataset['originalTitle']='Вызвать выделенных абонентов';
          if(allowcallout||active) {
            room.find('#conf-start-btn').show();
          } else {
            room.find('#conf-start-btn').hide();
          }
        } else {
          room.find('#conf-start-btn button').text('Запустить')[0].dataset['originalTitle']='Начать вызов всем абонентам комнаты';
          if(running) {
            room.find('#conf-start-btn').hide();
          } else {
            if(allowcallout||active) {
              room.find('#conf-start-btn').show();
            } else {
              room.find('#conf-start-btn').hide();
            }
          }
        }
        updateUsersTimeout = setTimeout(updateRoomUsers,1000);
      }

      function cleanMenu() {
        let menu = document.querySelector('#cardContextMenu');
        let menucall = menu.querySelector('#menu-call');
        let next = menucall.nextSibling;
        while(next) {
          menucall = next;
          next = next.nextSibling;
          menu.removeChild(menucall);
        }
      }

      function addMenuCalls(user) {
        if(typeof user.channels !== 'undefined') {
          let menu = document.querySelector('#cardContextMenu');
          for(let j in user.channels) {
            let canadd = true;
            for(let i in roomusers.list) {
              if((roomusers.list[i].online||roomusers.list[i].dialing||roomusers.list[i].scheduled)&&(roomusers.list[i].channel.indexOf(user.channels[j])===0)) {
                canadd = false;
                break;
              }
            }
            if(canadd) {
              let btn = document.createElement('button');
              btn.className = 'dropdown-item text-success pl-3 pr-3';
              btn.style.filter = 'opacity(60%)';
              btn.id = 'menu-call-'+j;
              let span = document.createElement('span');
              span.className =  '';
              btn.append(span);
              btn.innerHTML = '<span class="inline-icon icon-ring-success"></span>' + user.numbers[j];
              menu.appendChild(btn);
            }
          }
        }
      }

      function loadRoom(aid) {
        id=aid;
        roomusers.list=[];
        updateRoom();
      }

      function unselectAll() {
        for(var i = 0; i < roomusers.list.length; i++) {
          roomusers.list[i].selected=false;
        }
      }

      function kickUsers(users, room) {
        var selectedusers = [];
        for(var i = 0; i < users.length; i++) {
          var roomuser = null;
          for(var j = 0; j < roomusers.list.length; j++) {
            if(roomusers.list[j].channel==users[i]) {
              roomuser=roomusers.list[j];
            }
          }
          if(roomuser==null) {
            for(var j = 0; j < roomusers.groups.length; j++) {
              for(var k = 0; k < roomusers.groups[j].users.length; k++) {
                if(roomusers.groups[j].users[k].channel==users[i]) {
                  selectedusers.push({user: users[i], groupid: roomusers.groups[j].users[k].groupid, kick: (roomusers.groups[j].name==room), reschedule: roomusers.groups[j].users[k].scheduled});
                }
              }
            }
          } else {
            selectedusers.push({user: users[i], groupid: roomuser.groupid, reschedule: roomuser.scheduled, kick: false});
          }
        }
        if(selectedusers.length>0) {
          sendRequest('kick', {room: room, users: selectedusers}).success(function() {
            return false;
          });
        }
      }

      function isolateUser(user) {
        if(!Array.isArray(user)) user=[user];
        sendRequest('isolate', {room: id, users: user}).success(function() {
          updateRoom();
          return false;
        });
      }

      function mergeRoomUsers() {
        var selected = false;
        var mergeusers = []
        for(var i = 0; i < roomusers.list.length; i++) {
          if(roomusers.list[i].selected&&roomusers.list[i].online) {
            selected = true;
            mergeusers.push(roomusers.list[i].channel);
          }
        }
        if(selected) {
          sendRequest('merge', {room: id, users: mergeusers}).success(function() {
            unselectAll();
            updateRoomUsers();
            return false;
          });
        }
      }

      function unholdGroup(groupid) {
        var selected = false;
        var isolateusers = []
        for(var i = 0; i < roomusers.list.length; i++) {
          if((roomusers.list[i].groupid==groupid)&&roomusers.list[i].online&&roomusers.list[i].isolated) {
            isolateusers.push(roomusers.list[i].channel);
            selected=true;
          }
        }
        if(selected) {
          isolateUser(isolateusers);
        }
      }

      function holdGroup(groupid) {
        var selected = false;
        var isolateusers = []
        for(var i = 0; i < roomusers.list.length; i++) {
          if((roomusers.list[i].groupid==groupid)&&roomusers.list[i].online&&!roomusers.list[i].isolated) {
            isolateusers.push(roomusers.list[i].channel);
            selected=true;
          }
        }
        if(selected) {
          isolateUser(isolateusers);
        }
      }

      function unmuteGroup(groupid) {
        var selected = false;
        var isolateusers = []
        for(var i = 0; i < roomusers.list.length; i++) {
          if((roomusers.list[i].groupid==groupid)&&roomusers.list[i].online&&roomusers.list[i].muted) {
            isolateusers.push(roomusers.list[i].channel);
            selected=true;
          }
        }
        if(selected) {
          sendRequest('unmute', {room: id, users: isolateusers}).success(function() {
            updateRoom();
            return false;
          });
        }
      }

      function muteGroup(groupid) {
        var selected = false;
        var isolateusers = []
        for(var i = 0; i < roomusers.list.length; i++) {
          if((roomusers.list[i].groupid==groupid)&&roomusers.list[i].online&&!roomusers.list[i].muted) {
            isolateusers.push(roomusers.list[i].channel);
            selected=true;
          }
        }
        if(selected) {
          sendRequest('mute', {room: id, users: isolateusers}).success(function() {
            updateRoom();
            return false;
          });
        }
      }

      function callGroup(groupid) {
        var callusers = []
        for(var i = 0; i < roomusers.list.length; i++) {
          if((roomusers.list[i].groupid==groupid)&&!roomusers.list[i].online&&!roomusers.list[i].scheduled&&!roomusers.list[i].dialing) {
            callusers.push({id: roomusers.list[i].id, groupid: roomusers.list[i].groupid});
          }
        }
        sendRequest('start', {room: id, users: callusers}).success(function() {
          updateRoom();
          return false;
        });
      }

      function kickGroup(groupid) {
        var kicking=[];
        for(var i = 0; i < roomusers.list.length; i++) {
          if((roomusers.list[i].groupid==groupid)&&(roomusers.list[i].online||roomusers.list[i].scheduled||roomusers.list[i].dialing)) {
            kicking.push(roomusers.list[i].channel);
          }
        }
        kickUsers(kicking, id);
      }

      function setUserVolume(user, mode, volume) {
        let volumein = 0;
        let volumeout = 0;
        for(var i = 0; i < roomusers.list.length; i++) {
          if(roomusers.list[i].online) {
            if(roomusers.list[i].channel == user) {
              volumein = roomusers.list[i].volumein;
              volumeout = roomusers.list[i].volumein;
            }
          }
        }
        if(mode == 'in') {
          volumein = volume;
        } else {
          volumeout = volume;
        }
        sendRequest('setvolume', {room: id, user: user, volumein: volumein, volumeout: volumeout}).success(function() {
          updateRoom();
          return false;
        });
      }

      function lockRoom(btn) {
        var selected = false;
        var isolateusers = []
        for(var i = 0; i < roomusers.list.length; i++) {
          if(roomusers.list[i].selected&&roomusers.list[i].online) {
            isolateusers.push(roomusers.list[i].channel);
            selected = true;
          }
        }
        if(!selected) {
          sendRequest(($(btn).hasClass('active')?'un':'')+'lock', {room: id}).success(function() {
            updateRoom();
            return false;
          });
        } else {
          isolateUser(isolateusers);
          unselectAll();
        }
      }

      function muteRoom(btn, user) {
        var selected = 0;
        var muted = 0;
        var isolateusers = []
        for(var i = 0; i < roomusers.list.length; i++) {
          if(roomusers.list[i].selected&&roomusers.list[i].online) {
            selected++;
            if(roomusers.list[i].muted) muted++;
          }
        }
        if(selected==0||(typeof user != 'undefined')) {
          sendRequest(((btn=='un'||$(btn).hasClass('active'))?'un':'')+'mute', {room: id, users: [user]}).success(function() {
            updateRoom();
            return false;
          });
        } else {
          for(var i = 0; i < roomusers.list.length; i++) {
            if(roomusers.list[i].selected&&roomusers.list[i].online) {
              isolateusers.push(roomusers.list[i].channel);
            }
          }
          sendRequest(((muted==selected)?'un':'')+'mute', {room: id, users: isolateusers}).success(function() {
            updateRoom();
            unselectAll();
            return false;
          });
        }
      }

      function recordRoom(btn) {
        sendRequest(($(btn).hasClass('active')?'un':'')+'record', {room: id}).success(function() {
          updateRoom();
          return false;
        });
      }

      function inviteRoom(room, chan, user, conf_profile, profile, menu, trunknum, extendnum, extenddelay, callerid) {
        dialog=$('#conf-invite-dialog');
        dialog.modal('hide');
        var channel=chan+'/'+user;
        if(trunknum!="") channel=channel+'/'+trunknum;
        sendRequest('invite', {room: room, room_profile: conf_profile, profile: profile, menu: menu, channel: channel, callerid: callerid, extendnum: extendnum, delay: extenddelay}).success(function() {
          return false;
        });
      }

      function startRoom(btn) {
        var callusers = []
        for(var i = 0; i < roomusers.list.length; i++) {
          if(roomusers.list[i].selected&&!roomusers.list[i].online) {
            callusers.push({id: roomusers.list[i].id, groupid: roomusers.list[i].groupid});
          }
        }
        sendRequest('start', {room: id, users: callusers}).success(function() {
          unselectAll();
          updateRoom();
          return false;
        }).error(function() {
          unselectAll();
          return true;
        });
      }

      function stopRoom(btn) {
        var kicking=[];
        for(var i = 0; i < roomusers.list.length; i++) {
          if(roomusers.list[i].selected&&(roomusers.list[i].dialing||roomusers.list[i].online||roomusers.list[i].scheduled)) {
            kicking.push(roomusers.list[i].channel);
          }
        }
        if(kicking.length==0) {
          for(var j = 0; j < roomusers.groups.length; j++) {
            for(var i = 0; i < roomusers.groups[j].users.length; i++) {
              kicking.push(roomusers.groups[j].users[i].channel);
            }
          }
          for(var i = 0; i < roomusers.list.length; i++) {
            kicking.push(roomusers.list[i].channel);
          }
        }
        kickUsers(kicking, id);
        unselectAll();
      }

      function kickAll(room) {
        var kicking=[];
        for(var j = 0; j < roomusers.groups.length; j++) {
          if(roomusers.groups[j].number==room) {
            for(var i = 0; i < roomusers.groups[j].users.length; i++) {
              kicking.push(roomusers.groups[j].users[i].channel);
            }
          }
        }
        kickUsers(kicking, room);
      }

      function callRoomUser(user_id, group_id, channel) {
        if(typeof channel == 'undefined') {
          sendRequest('call', {room: id, user_id: user_id, group_id: group_id}).success(function() {
            updateRoom();
            return false;
          });
        } else {
          sendRequest('call', {room: id, user_id: user_id, group_id: group_id, channel: channel}).success(function() {
            updateRoom();
            return false;
          });
        }
      }

      function setPin(btn) {
        sendRequest('pin', {room: id, pin: $(btn).parent().prev().val()}).success(function() {
          showalert('success', 'Пинкод успешно утсановлен');
          return false;
        });
      }

      function inviteUser() {
        var dialog=$('#conf-invite-dialog');
        inviteRoom(dialog.find('#conf-id').val(), dialog.find('#chan-list').val(), dialog.find('#user-list').val(), dialog.find('#room-profile').val(), dialog.find('#user-profile').val(), dialog.find('#menu-profile').val(), dialog.find('#trunk-number').val(), dialog.find('#extend-number').val(), dialog.find('#extend-number-delay').val(), dialog.find('#caller-id').val().trim());
      }

      if([].indexOf) {
        var find = function(array, value) {
          return array.indexOf(value);
        }
      } else {
        var find = function(array, value) {
          for (var i = 0; i < array.length; i++) {
            if (array[i] === value) return i;
          }
          return -1;
        }
      }

      function userListSelect(object) {
        var dialog = $(object).parent().parent().parent().parent();
        var userList = $(object);
        var trunkNumber = dialog.find("#trunk-number");
        var extendNumber = dialog.find("#extend-number");
        var extendNumberDelay = dialog.find("#extend-number-delay");
        var callerId = dialog.find("#caller-id");
        trunkNumber.val('');
        extendNumber.val('');
        extendNumberDelay.val('5');
        var user = [];
        for (var i = 0; i < users.length; i++) {
          if (users[i].login === userList.val()) user=users[i];
        }
        callerId.val(user.name.trim());
        if(user.mode==='peer') {
          extendNumber.parent().parent().hide();
          trunkNumber.parent().hide();
          userList.parent().parent().removeClass('col-7').addClass('col-10');
        } else {
          extendNumber.parent().parent().show();
          userList.parent().parent().removeClass('col-10').addClass('col-7');
          trunkNumber.parent().show();
        }
      }

      function userNumFindDesc(object) {
        var dialog = $(object).parent().parent().parent().parent();
        var trunkNumber = $(object);
        var chanList = dialog.find("#chan-list");
        var userList = dialog.find("#user-list");
        var callerId = dialog.find("#caller-id");
        var user = [];
        for (var i = 0; i < roomusers.list.length; i++) {
          if (roomusers.list[i].channel === chanList.val()+'/'+userList.val()+'/'+trunkNumber.val()) {
            user=roomusers.list[i];
            break;
          }
        }
        if(typeof user.name == 'undefined') {
          for (var j = 0; j < roomusers.groups.length; j++) {
            for (var i = 0; i < roomusers.groups[j].users.length; i++) {
              if (roomusers.groups[j].users[i].channel === chanList.val()+'/'+userList.val()+'/'+trunkNumber.val()) {
                user=roomusers.groups[j].users[i];
                break;
              }
            }
          }
        }
        if(typeof user.name == 'undefined') {
          for (var i = 0; i < users.length; i++) {
            if (users[i].login === userList.val()) user=users[i];
          }
        }
        if(typeof user.name != 'undefined') {
          callerId.val(user.name.trim());
        }
      }
      
      function loadUsers(dialog, type) {
        var chans = [];
        var chanList = dialog.find("#chan-list");
        var userList = dialog.find("#user-list");
        chanList.html('');
        userList.html('');
        for(var i=0; i<users.length; i++) {
          if(find(chans, users[i].type)==-1) chans.push(users[i].type);
          if(type==users[i].type)
            userList.append('<option value="'+users[i].login+'">'+((users[i].name=='')?users[i].number:users[i].name)+'</option>');
        }
        for(var i=0; i<chans.length; i++) {
          chanList.append('<option value="'+chans[i]+'" '+((chans[i]==type)?'selected':'')+'>'+chans[i]+'</option>');
        }
        localStorage.setItem("confbridge-usertype", type);
        userListSelect(userList[0]);
      }

      function updateUsers(dialog) {
        users = [];
        sendRequest('users').success(function(data) {
          users=data;
          if(users.length>0) {
            var type=localStorage.getItem("confbridge-usertype");
            var found=false;
            for(var i=0; i<users.length; i++) {
              if(users[i].type==type) {
                found=true;
                break;
              }
            }
            if(!found) type=users[0].type;
            loadUsers(dialog, type);
          }
        });
        sendRequest('room-profiles').success(function(data) {
          if(data.length) {
            var profile=dialog.find('#room-profile').val();
            var profiles=dialog.find('#room-profile').html('');
            for(var i = 0; i < data.length; i++) {
              $('<option value="'+data[i].id+'" '+(profile==data[i].id?' selected':'')+'>'+((data[i].title!='')?data[i].title:data[i].id)+'</option>').appendTo(profiles);
            }
          }
          return false;
        });
        sendRequest('menu-profiles').success(function(data) {
          if(data.length) {
            var profile=dialog.find('#menu-profile').val();
            var profiles=dialog.find('#menu-profile').html('');
            for(var i = 0; i < data.length; i++) {
              $('<option value="'+data[i].id+'" '+(profile==data[i].id?' selected':'')+'>'+((data[i].title!='')?data[i].title:data[i].id)+'</option>').appendTo(profiles);
            }
          }
          return false;
        });
        sendRequest('user-profiles').success(function(data) {
          if(data.length) {
            var profile=dialog.find('#user-profile').val();
            var profiles=dialog.find('#user-profile').html('');
            for(var i = 0; i < data.length; i++) {
              $('<option value="'+data[i].id+'" '+(profile==data[i].id?' selected':'')+'>'+((data[i].title!='')?data[i].title:data[i].id)+'</option>').appendTo(profiles);
            }
          }
          return false;
        });
      }

      function showInvite() {
        var dialog=$('#conf-invite-dialog');
        updateUsers(dialog);
        dialog.find('.modal-title').html('Приглашение в конференцию');
        dialog.find('#conf-id').val(id).parent().parent().hide();
        dialog.find('#room-profile').val(room_profile).parent().parent().parent().hide();
        dialog.modal('show');
      }

      function showConference() {
        var dialog=$('#conf-invite-dialog');
        updateUsers(dialog);
        dialog.find('.modal-title').html('Создание конференции');
        dialog.find('#conf-id').parent().parent().show();
        dialog.find('#room-profile').parent().parent().parent().show();
        dialog.modal('show');
      }

<?php
  if(self::checkPriv('realtime')) {
?>
      function subscribeEvents() {
        var source = new EventSource('/'+urilocation+'?json=events&events[]=Hangup&events[]=Newstate&events[]=ConfbridgeJoin&events[]=Hold&events[]=Unhold&events[]=ConfbridgeTalking');
        source.addEventListener('message', function(e) {
          var data=$.parseJSON(e.data);
          if(data['Event']) {
            switch(data['Event']) {
              case 'Hold':
              case 'Unhold':
              case 'ConfbridgeTalking': {
                for(var i=0; i<roomusers.list.length; i++) {
                  if(roomusers.list[i].channel==data['Channel']) {
                    switch(data['Event']) {
                      case 'Hold': {
                        roomusers.list[i].hold=true;
                      } break;
                      case 'Unhold': {
                        roomusers.list[i].hold=false;
                      } break;
                      case 'ConfbridgeTalking': {
                        roomusers.list[i].talk=(data['TalkingStatus']=='on');
                        if(roomusers.list[i].talk) {
                          roomusers.list[i].muted = false;
                          roomusers.list[i].isolated = false;
                        }
                      } break;
                    }
                  }
                }
                for(var i=0; i<roomusers.groups.length; i++) {
                  for(var j=0; j<roomusers.groups[i].users.length; j++) {
                    if(roomusers.groups[i].users[j].channel==data['Channel']) {
                      switch(data['Event']) {
                        case 'Hold': {
                          roomusers.groups[i].users[j].hold=true;
                        } break;
                        case 'Unhold': {
                          roomusers.groups[i].users[j].hold=false;
                        } break;
                        case 'ConfbridgeTalking': {
                          roomusers.groups[i].users[j].talk=(data['TalkingStatus']=='on');
                        } break;
                      }
                    }
                  }
                }
                if(!needupdateroom) {
                  clearTimeout(updateUsersTimeout);
                  updateUsersTimeout = setTimeout(updateRoomUsers, 250);
                }
              } break;
              case 'Hangup':
              case 'Newstate':
              case 'ConfbridgeJoin': {
                needupdateroom=true;
                clearTimeout(updateTimeout);
                clearTimeout(updateUsersTimeout);
                updateTimeout = setTimeout(updateRoom, 250);
              } break;
            }
          }
//          console.log(data);
        }, false);

        source.addEventListener('open', function(e) {
//          console.log('connected');
        }, false);

        source.addEventListener('error', function(e) {
          if (e.eventPhase == EventSource.CLOSED) {
//            console.log('disconnected');
            source.close();
            subscribeEvents();
          }
        }, false);
      }
<?php
  }
?>

      function updateUsersView() {
        var view=$('#conf-view input');
        localStorage.setItem("confbridge-view", $(view.get(1)).prop('checked'));
        renderRoomUsers();
      }

      function selectAll() {
        var selected = 0;
        for(var i = 0; i < roomusers.list.length; i++) {
          if(roomusers.list[i].selected) {
            selected+=1;
          }
        }
        if(selected>roomusers.list.length/2) {
          for(var i = 0; i < roomusers.list.length; i++) {
            roomusers.list[i].selected = false;
          }
        } else {
          for(var i = 0; i < roomusers.list.length; i++) {
            roomusers.list[i].selected = true;
          }
        }
        updateRoomUsers();
      }

      function selectTalking() {
        var selected = 0;
        for(var i = 0; i < roomusers.list.length; i++) {
          roomusers.list[i].selected = roomusers.list[i].talk;
        }
        updateRoomUsers();
      }


      function selectGroup(groupid) {
        var count = 0;
        var selected = 0;
        for(var i = 0; i < roomusers.list.length; i++) {
          if(roomusers.list[i].groupid==groupid) {
            count+=1;
            if(roomusers.list[i].selected) {
              selected+=1;
            }
          }
        }
        if(selected>count/2) {
          for(var i = 0; i < roomusers.list.length; i++) {
            if(roomusers.list[i].groupid==groupid) {
              roomusers.list[i].selected = false;
            }
          }
        } else {
          for(var i = 0; i < roomusers.list.length; i++) {
            if(roomusers.list[i].groupid==groupid) {
              roomusers.list[i].selected = true;
            }
          }
        }
        updateRoomUsers();
      }

<?php
  if(self::checkPriv('dialing')) {
?>

      function sbadd(e) {
        showConference();
      }

<?php
  } else {
?>

    var sbadd=null;

<?php
  }
?>

      function sbselect(e, sel) {
        loadRoom(sel);
      }

      $(document).ready(function() {
        updateRooms();
        setInterval('updateRooms()', 5000);
<?php
  if(self::checkPriv('realtime')) {
?>
        subscribeEvents();
<?php
  }
?>
        items=[];
        rightsidebar_set('#sidebarRightCollapse', items);
        rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
        $('#user-list').select2({theme: "bootstrap", minimumInputLength: 1, language: document.scrollingElement.lang});
        $('[data-toggle="tooltip"]').tooltip();
        $(window).on('resize',function(e) { 
          $('#users ul li span.text').each(function() {
            var obj = $(this);
            obj.css({width: obj.parent().find('.list-btn-group').position().left-10});
          });
        });
      });

      document.onkeydown = function(e) {
        if ((e.ctrlKey && e.keyCode == 'A'.charCodeAt(0))) {
          selectAll();
          return false;
        }
        if ((e.ctrlKey && e.keyCode == 'S'.charCodeAt(0))) {
          selectTalking();
          return false;
        }
      }

    </script>
    <?php
  }

  public function render() {
    ?>
       <div id="cardContextMenu" class="dropdown-menu p-0" role="menu" style="display:none; min-width: 9rem;" >
        <button class="dropdown-item text-dark pl-3 pr-3" id='menu-mute'><span class="inline-icon icon-phone-simplex"></span>Симплекс</button>
        <button class="dropdown-item text-success pl-3 pr-3" id='menu-unmute'><span class="inline-icon icon-phone-duplex"></span>Дуплекс</button>
        <button class="dropdown-item text-warning pl-3 pr-3" id='menu-hold'><span class="inline-icon icon-phone-isolated"></span>Изолировать</button>
        <button class="dropdown-item text-success pl-3 pr-3" id='menu-unhold'><span class="inline-icon icon-phone"></span>Вернуть</button>
        <button class="dropdown-item text-danger pl-3 pr-3" id='menu-kick'><span class="inline-icon icon-ring-error"></span>Отбить</button>
        <button class="dropdown-item text-warning pl-3 pr-3" id='menu-return'><span></span>Вернуть</button>
        <button class="dropdown-item text-danger pl-3 pr-3" id='menu-returnall'><span></span>Вернуть всех</button>
        <div class="dropdown-submenu" id='menu-volumeout'>
          <div class="dropdown-item text-default pl-3 pr-3"><span></span>Громкость динамика</div>
          <div class="dropdown-menu">
          <button class="dropdown-item text-danger pl-3 pr-3" id='volout-p5'><span></span>+5</button>
          <button class="dropdown-item text-warning pl-3 pr-3" id='volout-p4'><span></span>+4</button>
          <button class="dropdown-item text-warning pl-3 pr-3" id='volout-p3'><span></span>+3</button>
          <button class="dropdown-item text-success pl-3 pr-3" id='volout-p2'><span></span>+2</button>
          <button class="dropdown-item text-success pl-3 pr-3" id='volout-p1'><span></span>+1</button>
          <button class="dropdown-item text-info pl-3 pr-3" id='volout-0'><span></span>0</button>
          <button class="dropdown-item text-success pl-3 pr-3" id='volout-m1'><span></span>-1</button>
          <button class="dropdown-item text-success pl-3 pr-3" id='volout-m2'><span></span>-2</button>
          <button class="dropdown-item text-warning pl-3 pr-3" id='volout-m3'><span></span>-3</button>
          <button class="dropdown-item text-warning pl-3 pr-3" id='volout-m4'><span></span>-4</button>
          <button class="dropdown-item text-danger pl-3 pr-3" id='volout-m5'><span></span>-5</button>
          </div>
        </div>
        <div class="dropdown-submenu" id='menu-volumein'>
          <div class="dropdown-item text-default pl-3 pr-3" id='submenu-volumein'><span></span>Громкость микрофона</div>
          <div class="dropdown-menu">
          <button class="dropdown-item text-danger pl-3 pr-3" id='volin-p5'><span></span>+5</button>
          <button class="dropdown-item text-warning pl-3 pr-3" id='volin-p4'><span></span>+4</button>
          <button class="dropdown-item text-warning pl-3 pr-3" id='volin-p3'><span></span>+3</button>
          <button class="dropdown-item text-success pl-3 pr-3" id='volin-p2'><span></span>+2</button>
          <button class="dropdown-item text-success pl-3 pr-3" id='volin-p1'><span></span>+1</button>
          <button class="dropdown-item text-info pl-3 pr-3" id='volin-0'><span></span>0</button>
          <button class="dropdown-item text-success pl-3 pr-3" id='volin-m1'><span></span>-1</button>
          <button class="dropdown-item text-success pl-3 pr-3" id='volin-m2'><span></span>-2</button>
          <button class="dropdown-item text-warning pl-3 pr-3" id='volin-m3'><span></span>-3</button>
          <button class="dropdown-item text-warning pl-3 pr-3" id='volin-m4'><span></span>-4</button>
          <button class="dropdown-item text-danger pl-3 pr-3" id='volin-m5'><span></span>-5</button>
          </div>
        </div>
        <button class="dropdown-item text-success pl-3 pr-3" id='menu-call'><span class="inline-icon icon-ring-success"></span>Позвонить</button>
       </div>
       <div class="modal fade" id='conf-invite-dialog'>
        <div class="modal-dialog modal-lg" role="document">
         <div class="modal-content">
          <div class="modal-header">
           <h5 class="modal-title">Приглашение в конференцию</h5>
           <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
           </button>
          </div>
          <div class="modal-body">
           <div class="form-group row">
            <label for="conf-id" class="col-5 col-form-label">Номер конференции
            </label>
            <div class="col-7">
             <input class="form-control" type="text" value="100" id="conf-id">
            </div>
           </div>
           <div class="form-group row">
            <label for="room-profile" class="col-5 col-form-label">Профиль конференции</label>
            <div class="col-7">
             <div class="input-group">
              <select class="custom-select w-100" id="room-profile">
              </select>
             </div>
            </div>
           </div>
           <div class="form-group row">
            <label for="user-profile" class="col-5 col-form-label">Профиль пользователя</label>
            <div class="col-7">
             <div class="input-group">
              <select class="custom-select w-100" id="user-profile">
              </select>
             </div>
            </div>
           </div>
           <div class="form-group row">
            <label for="menu-profile" class="col-5 col-form-label">Профиль меню пользователя</label>
            <div class="col-7">
             <div class="input-group">
              <select class="custom-select w-100" id="menu-profile">
              </select>
             </div>
            </div>
           </div>
           <div class="form-group row">
            <div class="col-2">
             <label for="chan-list" class="form-label">Тип канала</label>
             <div class="input-group">
              <select class="custom-select w-100" id="chan-list" onChange="loadUsers($('#conf-invite-dialog'), this.value)">
              </select>
             </div>
            </div>
            <div class="col-7">
             <label for="user-list" class="form-label">Пользователь/Шлюз</label>
             <div class="input-group">
              <select class="custom-select w-100" id="user-list" onChange="userListSelect(this)">
              </select>
             </div>
            </div>
            <div class="col-3">
             <label for="trunk-number" class="form-label">Вызываемый номер
             </label>
             <input class="form-control" type="text" value="" id="trunk-number" onInput="userNumFindDesc(this)">
            </div>
           </div>
           <div class="form-group row">
            <label for="extend-number" class="col-4 col-form-label">Дополнительный номер
            </label>
            <div class="col-3">
             <input class="form-control" type="text" value="" id="extend-number">
            </div>
            <label for="extend-number-delay" class="col-2 col-form-label">Задержка
            </label>
            <div class="col-2">
             <input class="form-control" type="text" value="5" id="extend-number-delay">
            </div>
            <label for="extend-number-delay" class="col-1 col-form-label">сек
            </label>
           </div>
           <div class="form-group row">
            <label for="caller-id" class="col-4 col-form-label">Отображаемое имя
            </label>
            <div class="col-8">
             <input class="form-control" type="text" value="" id="caller-id">
            </div>
           </div>
          </div>
          <div class="modal-footer">
           <button type="button" class="btn btn-success" onClick="inviteUser()">Пригласить</button>
           <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
          </div>
         </div>
        </div>
       </div>

       <div class="invisible" id="confbridge-data">
         <div class="form-group row">
          <label for="conf-number" class="col form-label">Наименование конференции
          </label>
          <div class="col-12 col-lg-7">
           <input class="form-control" type="text" readonly value="" id="conf-number">
          </div>
         </div>
         <div class="form-group row">
          <label for="conf-number" class="col form-label">Действия с конференцией</label>
          <div class="col-12 col-xs-5 col-md-12 col-lg-8 col-xl-9 row pl-4 pl-lg-0 pr-0 pr-lg-4">
            <div class="col-6 col-md-5 col-lg-4 col-xl-3 pr-1 pl-1 pt-1" id='conf-lock-btn'>
              <button class="btn btn-warning form-control pr-md-1 pl-md-1" onClick='lockRoom(this)' data-toggle="tooltip" data-placement="top" title="Заблокировать конференц комнату">Заблокировать</button>
            </div>
            <div class="col-6 col-md-5 col-lg-4 col-xl-3 pr-1 pl-1 pt-1" id='conf-mute-btn' >
              <button class="btn btn-info form-control pr-md-1 pl-md-1" onClick='muteRoom(this)' data-toggle="tooltip" data-placement="top" title="Отключить микрофон у всех абонентов">Симплекс</button>
            </div>
            <div class="col-6 col-md-5 col-lg-4 col-xl-3 pr-1 pl-1 pt-1" id='conf-record-btn' >
              <button class="btn btn-info form-control pr-md-1 pl-md-1" onClick='recordRoom(this)' data-toggle="tooltip" data-placement="top" title="Запись конференц-комнаты">Включить запись</button>
            </div>
            <div class="col-6 col-md-5 col-lg-4 col-xl-3 pr-1 pl-1 pt-1" id='conf-start-btn'>
              <button class="btn btn-success form-control pr-md-1 pl-md-1" onClick='startRoom(this)' data-toggle="tooltip" data-placement="top" title="Начать вызов всем абонентам комнаты">Запустить</button>
            </div>
        <?php
         if(self::checkPriv('dialing')) {
        ?>
            <div class="col-6 col-md-5 col-lg-4 col-xl-3 pr-1 pl-1 pt-1">
              <button class="btn btn-success form-control pr-md-1 pl-md-1" onClick='showInvite()' data-toggle="tooltip" data-placement="top" title="Пригласить нового абонента в конференцию">Пригласить</button>
            </div>
        <?php
          }
        ?>
            <div class="col-6 col-md-5 col-lg-4 col-xl-3 pr-1 pl-1 pt-1" id='conf-merge-btn' >
              <button class="btn btn-primary form-control pr-md-1 pl-md-1" onClick='mergeRoomUsers()' data-toggle="tooltip" data-placement="top" title="Выделить выбранных абонентов в общую группу">Объединить</button>
            </div>
            <div class="col-6 col-md-5 col-lg-4 col-xl-3 pr-1 pl-1 pt-1" id='conf-stop-btn'>
              <button class="btn btn-danger form-control pr-md-1 pl-md-1" onClick='stopRoom(this)' data-toggle="tooltip" data-placement="top" title="Отбить выбранных абонентов">Отбить</button>
            </div>
          </div>
         </div>
         <div class="form-group row">
          <label for="conf-number" class="col form-label">Пинкод для доступа к конференции
          </label>
          <div class="col-12 col-lg-4">
           <div class="input-group">
            <input type="hidden" value="" id="conf-current-pin">
            <input class="form-control" type="text" value="" id="conf-pin">
            <span class="input-group-append">
             <button class="btn btn-secondary" onClick="setPin(this)">Задать</button>
            </span>
           </div>
          </div>
          <div class="btn-group btn-group-toggle col-12 col-md-8 col-lg-6 pt-1 pt-lg-0 mt-1" data-toggle="buttons" id="conf-view">
           <label class="btn btn-secondary active"><input type="radio" name="options" autocomplete="off" checked onChange="updateUsersView()">Список</label>
           <label class="btn btn-secondary"><input type="radio" name="options" autocomplete="off" onChange="updateUsersView()">Карточки</label>
          </div>
         </div>
         <div class="form-group row" id="users">
         </div>
       </div>
    <?php
  }

}

?>
