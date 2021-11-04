<?php
 $result = new \stdClass();

 switch($_GET['json']) {

    case 'logout': {
      session_unset('login');
      session_unset('passwd');
    } break;

    case 'colorcss': {
      $result = null;
      header('Content-Type: text/css');
      $colors = \core\AppearenceSettings::getCurrentSettings(); 
      require_once($_SERVER['DOCUMENT_ROOT'].'/css/bootstrap-varcolors.css');
    } break;

 }

 if($result) echo json_encode($result);
?>
