#!/usr/bin/php
<?php
  function makeCall($data, $schedule) {
    $filedata = "";
    $parameters = $data;
    foreach($parameters as $key => $param) {
      if(!is_array($param)) $param=array($param);
      foreach($param as $value) {
        $filedata .= sprintf("%s: %s\n", $key, $value);
      }
    }
    if(!$schedule) $schedule = time();
    $filelast = $schedule.'-'.random_int(1000,9999).'.call';
    $filename = '/var/spool/asterisk/tmp/'.$filelast;
    if(!file_exists('/var/spool/asterisk/outgoing/'.$filelast)) {
      $result=file_put_contents($filename, $filedata);
      if($result && $schedule) {
        touch($filename, $schedule, $schedule);
      }
      chmod($filename, 0660);
      rename($filename,'/var/spool/asterisk/outgoing/'.$filelast);
    } else {
      $result=false;
    }
    return $result;
  }

  posix_setuid(posix_geteuid());
  posix_setgid(posix_getegid());

  if(isset($_SERVER['data'])) {
    $data = json_decode($_SERVER['data']);
    $schedule = $_SERVER['schedule'];
    if(makeCall($data, $schedule)) exit(0);
  }
  exit(1);
?>
