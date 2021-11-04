<?php

namespace core;

class DashboardManage extends ViewModule {

  private static $dashboardmodules = null;

  protected static function init() {
    if(self::$dashboardmodules==null)
       self::$dashboardmodules=getModulesByClass('core\DashboardWidget');
  }

  public static function getLocation() {
    return 'manage/dashboard';
  }

  public static function getMenu() {
    return (object) array('name' => 'Состояние', 'prio' => 0, 'icon' => 'oi oi-pulse');
  }

  public static function check() {
    $result = self::checkPriv('system_info');
    return $result;
  }

  public static function getZoneInfo() {
    $result = new \SecZoneInfo();
    $result->zoneClass = 'dashboard_panel';
    $result->getObjects = function () {
                              self::init();
                              $result=array();
                              foreach(self::$dashboardmodules as $module) {
                                $moduleclass=get_class($module);
                                $info=$moduleclass::info();
                                $result[]=(object) array('id' => $info->name, 'text' => $info->title);
                              }
                              return $result;
                            };
    return $result;
  }

  public static function ami_event($ecode,$data) {
    $result = new \stdClass();
    $result->event = $ecode;
    $result->data = $data;
    echo 'data: ' . json_encode($data) . "\n\n";
    ob_flush();
    flush();
  }

  public function json(string $request, \stdClass $request_data) {
    self::init();
    $result = new \stdClass();
    switch($request) {
      case "manage": {
        $return = array();
        foreach(self::$dashboardmodules as $module) {
          $moduleclass = get_class($module);
          $info = $moduleclass::info();
          if(self::checkEffectivePriv('dashboard_panel', $info->name, 'system_info')) {
            $return=array_merge($return, $module->json($request, $request_data));
          }
        }
        $result = self::returnResult($return);
      } break;
      case "events": {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        @ini_set('output_buffering','Off');
        @ini_set('zlib.output_compression',0);
        ob_end_flush();
        ob_start();
        $lastevent=time();
        while(true) {
          try {
            $res=$this->ami->wait_response();
            if(isset($res['Event'])&&(in_array($res['Event'],$_GET['events']))) self::ami_event($res['Event'], $res);
            $currentevent=time();
            if(($currentevent-$lastevent)>30) {
              $lastevent=$currentevent;
              self::ami_event('KeepAlive', array('Event' => 'KeepAlive', 'Privilege' => 'system,all'));
            }
          } finally {
          };
        }
      } break;
    }
    return $result;
  }

  public function scripts() {
    self::init();
    printf("<script>
function updateStatus() {
  sendRequest('manage').success(function(data) {
");
    foreach(self::$dashboardmodules as $module) {
      $moduleclass = get_class($module);
      $info = $moduleclass::info();
      if(self::checkEffectivePriv('dashboard_panel', $info->name, 'system_info')) {
        $module->scripts();
      }
    }
    printf("
  });
}

$(document).ready(function() {
  updateStatus();
  setInterval('updateStatus()', 5000);
});

</script>");
  }

  public function render() {
    self::init();
    printf("<div class='card-deck'>\n");
    foreach(self::$dashboardmodules as $module) {
      $moduleclass = get_class($module);
      $info = $moduleclass::info();
      if(self::checkEffectivePriv('dashboard_panel', $info->name, 'system_info')) {
        $module->render();
      }
    }
    printf("</div>\n");
  }

}

?>