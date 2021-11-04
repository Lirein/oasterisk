<?php

namespace core;

class ChannelsDW extends DashboardWidget {

  public static function info() {
    $result = (object) array("title" => 'Вызовы', "name" => 'calls');
    return $result;
  }

  public static function check($write = false) {
    $result = true;
    $result &= self::checkPriv('system_info');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    $return = array();
    $return['calls'] = self::getAsteriskCalls();
    return $return;
  }

  public function implementation() {
    printf("
    if(data.calls.length) {
      var calls=$('<ul class=\"list-group\"></ul>');
      for(var i = 0; i < data.calls.length; i++) {
        $('<li class=\"small list-group-item justify-content-between\">'+data.calls[i].from+' -> '+data.calls[i].to+'<span class=\"badge badge-default badge-pill right\">'+getUTCTime(UTC((new Date())-data.calls[i].start*1000))+'</span></li>').appendTo(calls);
      }
      $('#calls .card-block').html(calls);
    } else $('#calls .card-block').html('');
    ");
  }

  public function render() {
    printf("
<div class='card' id='calls'>
 <div class='card-header'>
  Вызовы
 </div>
 <div class='card-block' style='height: 300px; overflow-y: auto;'>
 </div>
</div>
    ");
  }

}

?>