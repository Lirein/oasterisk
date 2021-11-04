#!/usr/bin/php
<?php

  function makeCall($roomid, $userid, $groupid, $data, $schedule = null) {
    $filedata = "";
    if($groupid!='') {
      $groupid='_group_'.$groupid;
    }
    $parameters = $data;
    foreach($parameters as $key => $param) {
      if(!is_array($param)) $param=array($param);
      foreach($param as $value) {
        $filedata .= sprintf("%s: %s\n", $key, $value);
      }
    }
    $filename = '/var/spool/asterisk/tmp/room_'.$roomid.$groupid.'_user_'.$userid.'.call';
//    if(!file_exists('/var/spool/asterisk/outgoing/room_'.$roomid.$groupid.'_user_'.$userid.'.call')||(filemtime('/var/spool/asterisk/outgoing/room_'.$roomid.$groupid.'_user_'.$userid.'.call')>time())) {
    if(!file_exists('/var/spool/asterisk/outgoing/room_'.$roomid.$groupid.'_user_'.$userid.'.call')) {
      $result=file_put_contents($filename, $filedata);
      if($result && $schedule) {
        touch($filename, $schedule);
      }
      chmod($filename, 0660);
      rename($filename,'/var/spool/asterisk/outgoing/room_'.$roomid.$groupid.'_user_'.$userid.'.call');
    } else {
      $result=false;
    }
    return $result;
  }

  $info=posix_getpwnam("asterisk");
  $uid=$info["uid"];
  $gid=$info["gid"];

  posix_setuid($uid);
  posix_setgid($gid);

  if(isset($_SERVER['data'])) {
    $roomid = $_SERVER['roomid'];
    $user = $_SERVER['user'];
    $groupid = $_SERVER['groupid'];
    $data = json_decode($_SERVER['data']);
    $schedule = $_SERVER['schedule'];
    if(makeCall($roomid, $user, $groupid, $data, $schedule)) exit(0);
  }
  exit(1);
?>
