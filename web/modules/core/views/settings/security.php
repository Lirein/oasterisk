<?php

namespace core;

class SequritySettings extends \view\Menu implements \module\IJSON {

  public static function getLocation() {
    return 'settings/security';
  }

  public static function getMenu() {
    return (object) array('name' => 'Безопасность', 'prio' => 11, 'icon' => 'SecuritySharpIcon');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('security_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "profiles": {
        self::returnResult(array_keys(self::$internal_priveleges));
      } break;
      case "roles": {
        $roles=array();
        foreach(self::getRoles() as $role => $title) {
          if(self::checkEffectivePriv('security_role', $role, 'security_reader')) $roles[]=array('id'=>$role, 'title'=>$title);
        }
        $result = self::returnResult($roles);
      } break;
    }
    return $result;
  }

  public function implementation() {
    ?>
    <script>
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
    </script>
    <?php
  }

}

?>