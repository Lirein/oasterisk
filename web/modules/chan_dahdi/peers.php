<?php

namespace dahdi;

class DahdiPeer extends \channel\Trunk {

  public function info() {
    return (object) array("title" => 'E1', "name" => 'dahdi');
  }

  public static function check() {
    return self::checkLicense('oasterisk-e1');
  }

  public function __construct(string $id = null) {
    parent::__construct($id);
  }

  public function __serialize() {
    
  }

  public function __unserialize(array $keys) {
    
  }

  public function __isset($property){
    # code...
  }

  public function __get($property){
    # code...
  }

  public function __set($property, $value){
    # code...
  }

  public function cast(){
    # code...
  }
  
  public function getDial(string $number) {
    return 'DAHDI/g'.$this->group.'/'.$number;
  }

  public function checkDial(string $dial, string &$number) {
    $dials = explode('&', $dial);
    $result = false;
    foreach($dials as $dialentry) {
      if(strpos($dialentry, 'DAHDI/g'.$this->group.'/'.$number)===0) {
        $result = true;
        break;
      }
    }
    if(!$result) {
      foreach($dials as $dialentry) {
        if(strpos($dialentry, 'DAHDI/g'.$this->group.'/')===0) {
          $result = true;
          $number = substr($dialentry, strlen('DAHDI/g'.$this->group.'/'));
          break;
        }
      }
    }
    return $result;
  }

  public function checkChannel(string $channel, string $phone) {
    $result = false;
    if(strpos($channel, 'DAHDI/i')===0 && strpos($channel, '/'.$phone)===0) $result=true;
    return $result;
  }

}

?>
