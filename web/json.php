<?php
 $result = new \stdClass();

 switch($_GET['json']) {

    case 'logout': {
      unset($_SESSION['login']);
      unset($_SESSION['passwd']);
      unset($_SESSION['user']);
    } break;

      // if(preg_match('/(http|https):\/\/[a-z0-9.]+\/settings/', $_SERVER['HTTP_REFERER'])) $colors = \core\AppearenceModule::getDefaultSettings(); 
      // else $colors = \core\AppearenceModule::getCurrentSettings(); 

 }

 if($result) echo json_encode($result);
?>
