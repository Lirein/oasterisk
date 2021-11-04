<?php

namespace core;

class LoginsDW extends DashboardWidget {

  public static function info() {
    $result = (object) array("title" => 'Пользователи', "name" => 'logins');
    return $result;
  }

  public static function check($write = false) {
    $result = true;
    $result &= self::checkPriv('system_info');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    if(self::checkPriv('security_reader')) {
      $return['logins'] = array();
      $logins = $this->cache->get('logins');
      foreach($logins as $login) {
        if($login->profilename=='') {
          switch($login->profile) {
            case 'full_control': $login->profilename='Полный доступ'; break;
            case 'admin': $login->profilename='Администратор'; break;
            case 'technician': $login->profilename='Проектировщик'; break;
            case 'operator': $login->profilename='Оператор'; break;
            case 'agent': $login->profilename='Агент'; break;
            case 'manager': $login->profilename='Руководитель'; break;
          }
        }
        if(self::checkEffectivePriv('security_role', $login->profile, 'security_reader')) $return['logins'][]=$login;
      }
      return $return;
    }
    return array();
  }

  public function scripts() {
    printf("
    if(typeof data.logins != 'undefined') {
      if(data.logins.length) {
        var logins=$('<div class=\"list-group\"></div>');
        for(var i = 0; i < data.logins.length; i++) {
          $('<a href=\"/settings/security/users?id='+data.logins[i].login+'\" class=\"small list-group-item list-group-item-action justify-content-between\">'+data.logins[i].name+' ('+data.logins[i].profilename+')<span class=\"badge badge-primary badge-pill right\">'+data.logins[i].ip+'</span></a>').appendTo(logins);
        }
        $('#logins .card-block').html(logins);
      } else $('#logins .card-block').html('');
    } else $('#logins').hide();
    ");
  }

  public function render() {
    printf("
<div class='card' id='logins'>
 <div class='card-header'>
  Пользователи
 </div>
 <div class='card-block' style='height: 300px; overflow-y: auto;'>
 </div>
</div>
    ");
  }

}
