<?php

namespace core;

class CDRSettings extends ViewModule {
 
  private static $cdrparams = '{
    "enable": "!yes",
    "unanswered": "!yes",
    "congestion": "!no",
    "endbeforehexten": "!no",
    "initiatedseconds": "!no",
    "batch": "!no",
    "size": "100",
    "time": "300",
    "scheduleronly": "!no",
    "safeshutdown": "!yes"
  }';

  public static function getLocation() {
    return 'settings/logs/cdr';
  }

  public static function getMenu() {
    return (object) array('name' => 'Детализация вызовов', 'prio' => 2, 'icon' => 'oi oi-spreadsheet');
  }
  
  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function reloadConfig() {
    $this->ami->send_request('Command', array('Command' => 'cdr reload'));
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "cdr": {
        $logs = array();
        $logs[] = (object) array('id' => 'settings', 'title' => 'Настройки журналов');
        $engines = getModulesByClass('core\CdrEngineSettings');
        foreach($engines as $cdr) {
          if(method_exists($cdr, 'scripts')) $logs[] = $cdr->info();
        }
        $result = self::returnResult($logs);
      } break;
      case "cdr-get": {
        $cdr = $request_data->id;
        $cdrdata = new \stdClass();
          $cdrdata->id = $cdr;
        if($cdr == 'settings') {
          ob_start();
          $this->subScripts();         
          $cdrdata->scripts = ob_get_clean();
          $cdrdata->params = $this->getParams();
          $cdrdata->readonly = !self::checkPriv('settings_writer');
          $result = self::returnResult($cdrdata);
        } else {
          $engine = null;
          $engines = getModulesByClass('core\CdrEngineSettings');
          foreach($engines as $cdrengine) {
            $cdrinfo = $cdrengine->info();
            if($cdrinfo->id == $cdr) {
              $engine = $cdrengine;
              break;
            }
          }
          if($engine) {
            ob_start();
            $engine->scripts();         
            $cdrdata->scripts = ob_get_clean();
            $cdrdata->params = $engine->getParams();
            $cdrdata->readonly = !self::checkPriv('settings_writer');
            $result = self::returnResult($cdrdata);
          } else {
            $result = self::returnError('danger', 'Указанный движок ведения журналов не обнаружен');
          }
        }
      } break;
      case "cdr-set": {
        $cdr = $request_data->id;
        if(self::checkPriv('settings_writer')) {
          if($cdr == 'settings') {
            if($this->setParams($request_data)) {
              $result = self::returnSuccess();
              $this->reloadConfig();
            } else {
              $result = self::returnError('danger', 'Невозможно сохранить настройки');
            }
          } else {
            $engine = null;
            $engines = getModulesByClass('core\CdrEngineSettings');
            foreach($engines as $cdrengine) {
              $cdrinfo = $cdrengine->info();
              if($cdrinfo->id == $cdr) {
                $engine = $cdrengine;
                break;
              }
            }
            if($engine) {
              if($engine->setParams($request_data)) {
                $result = self::returnSuccess();
                $this->reloadConfig();
              } else {
                $result = self::returnError('danger', 'Невозможно сохранить настройки');
              }
            } else {
              $result = self::returnError('danger', 'Указанный движок ведения журналов не обнаружен');
            }
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "cdr-action": {
        $cdr = $request_data->id;
        $engine = null;
        $engines = getModulesByClass('core\CdrEngineSettings');
        foreach($engines as $cdrengine) {
          $cdrinfo = $cdrengine->info();
          if(($cdrinfo->id == $cdr) && method_exists($cdrengine, 'json')) {
            $engine = $cdrengine;
            break;
          }
        }
        if($engine) {
          $action = $request_data->action;
          unset($request_data->id);
          unset($request_data->action);
          $result = $engine->json($action, $request_data);
        } else {
          $result = self::returnError('danger', 'Указанный движок ведения журналов не обнаружен');
        }
      } break;
      case "get-modules":{
        $moduleList = array();
        $modules = findModulesByClass('core\CdrEngineSettings', true);
        foreach($modules as $module) {
          $classname = $module->class;
          $classinfo = $classname::info();
          if($classname::selectable()) {
            $moduleList[]=(object) array('id' => $classinfo->id, 'text' => $classinfo->title);
          }
        }
        $result = self::returnResult($moduleList);
      } break;
    }
    return $result;
  }

  public function subScripts() {
//    <script>
    ?>

    obj = new widgets.checkbox(card, {single: true, id: 'enable', value: false},
        "Включить журналирование деталей вызовов");
    obj = new widgets.checkbox(card, {single: true, id: 'unanswered', value: false},
        "Журналировать неотвеченные вызовы");
    obj = new widgets.checkbox(card, {single: true, id: 'congestion', value: false},
        "Журналировать вызовы отвергнутые из-за перегрузки каналов",
        "Setting this to yes will report each call that fails to complete due to congestion conditions");
    obj = new widgets.checkbox(card, {single: true, id: 'endbeforehexten', value: false},
        "Закрывать вывод деталей вызовов до запуска расширения h в диалплане",
        "Обычно запись деталей вызовов не прекращается пока диалплан полностью не завершит работу");
    obj = new widgets.checkbox(card, {single: true, id: 'initiatedseconds', value: false},
        "При вычислении поля 'billsec' использовать точные значения (до микросекунд)",
        "По-умолчанию, округляется вверх. Это помогает гарантировать поведение CDR Asterisk аналогичному поведению телекоммуникационных компаний");
    obj = new widgets.checkbox(card, {single: true, id: 'batch', value: false},
        "Сохранять детали вызовов в очередь и журналировать пакетами",
        "Снижает нагрузку на ядро технологической платформы. Внимание: может привести к потере данных при небезопасном завершении работы");
    obj = new widgets.input(card, {id: 'size'},
        "Максимальное число записей в одном пакете"); 
    obj = new widgets.input(card, {id: 'time'},
        "Максимальное количество секунд между пакетами",
        "Пакет записей будет журналирован по завершению этого периода времени, даже если размер не был достигнут"); 
    obj = new widgets.checkbox(card, {single: true, id: 'scheduleronly', value: false},
        "Загружать пакеты в едином потоке планировщика");
    obj = new widgets.checkbox(card, {single: true, id: 'safeshutdown', value: false},
        "Блокировать выключение, пока данные не сохранены");
    obj = new widgets.list(card, {id: 'activeengines', value: module_data, checkbox: true, sorted: true}, "Движки");
    
    <?php
  }

  public function getParams() {
    $result = new \stdClass();
    $ini = new \INIProcessor('/etc/asterisk/cdr.conf');
    $returnData = $ini->general->getDefaults(self::$cdrparams);
    $returnData->activeengines = array();
    $modules = findModulesByClass('core\CdrEngine', true);
    foreach($modules as $module) {
      $classname = $module->class;
      $classinfo = $classname::info();
      $returnData->activeengines[]= $classinfo->name;
    }
    $result = $returnData;
    return $result;
  }

  public function setParams($data) {
    $ini = new \INIProcessor('/etc/asterisk/cdr.conf');
    $ini->general->setDefaults(self::$cdrparams, $data);
    $modules = getModulesByClass('core\CdrEngineSettings', true);
    $passiveengines = array();
    if(isset($data->activeengines)) {
      if (!is_array($data->activeengines)) {
        $data->activeengines = array();
      }
      if (!empty($data->activeengines)) {
        foreach($data->activeengines as $engine) {
          foreach($modules as $module) {
            $classinfo = $module::info();
            if($classinfo->id == $engine) {
              $module->enable();
            } else {
              if(!in_array($module, $passiveengines)) $passiveengines[] = $module;
            }
          }
        }
      } else {
        $passiveengines = $modules;
      }
    }
    foreach($passiveengines as $engine) $engine->disable();
    return $ini->save();
  }

  public function scripts() {
    ?>
    <script>
      var cdr_id='<?php echo (isset($_GET['id'])?$_GET['id']:''); ?>';
      var card = null;
      var module_data = [];
      var loadhandlers = [];

      function updateCDRs() {
        sendRequest('cdr').success(function(data) {
          var hasactive=false;
          var items=[];
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              if(data[i].id==cdr_id) hasactive=true;
              items.push({id: data[i].id, title: data[i].title, active: data[i].id==cdr_id});
            }
          };
          rightsidebar_set('#sidebarRightCollapse', items);
          if(!hasactive) {
            if(card) card.hide();
            window.history.pushState(cdr_id, $('title').html(), '/'+urilocation);
            cdr_id='';
            rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
            sidebar_apply(null);
            if(data.length>0) loadCDR(data[0].id);
          } else {
            loadCDR(cdr_id);
          }
          return false;
        });
      }
      
      function loadCDR(id) {
        if(id == 'settings') loadModules();
        sendRequest('cdr-get', {id: id}).success(function(data) {
          rightsidebar_activate('#sidebarRightCollapse', id);
          rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
          sidebar_apply(sbapply);
          cdr_id=data.id;
          rootcontent.textContent = '';
          card = new widgets.section(rootcontent,null);
          card.hide();
          loadhandlers = [];
          let script = document.createElement('script');
          script.text = data.scripts;
          rootcontent.append(script);
          if(id == 'settings') card.setValue({value: module_data, clean: true});
          card.setValue(data.params);
          for(let i in loadhandlers) {
            loadhandlers[i](data.params);
          }
          card.show();
          if(data.readonly) card.disable(); else card.enable();
          window.history.pushState(cdr_id, $('title').html(), '/'+urilocation+'?id='+cdr_id);
          rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
          return false;
        });
      }

      function sendCDRData() {
        var data = card.getValue();
        data.id = cdr_id;
        sendRequest('cdr-set', data).success(function() {
          updateCDRs();
          return true;
        });
      }

      function loadModules() {
        sendRequest('get-modules').success(function(modules) {
          module_data.splice(0);
          module_data.push.apply(module_data, modules);
        });
      }

      function updateSSL() {
        sendRequest('cdr-action', {id: cdr_id, action: 'ssl-get'}).success(function(data) {
          if(data.issuer=='') data.issuer=_('noissuer','Издатель не определен');
          if(data.subject=='') data.sublect=_('nosubject','Субъект не определен');
          if(data.validfrom!='') data.validfrom=new moment(data.validfrom*1000).format('DD.MM.YYYY');
          if(data.validto!='') data.validto=new moment(data.validto*1000).format('DD.MM.YYYY');
          data.alias={value: data.alias, clean: true};
          ssldialog.setValue({info: data});
        });
      }

      function sendSSLRequest(sender) {
        sendRequest('cdr-action', {id: cdr_id, action: 'ssl-request'}).success(function(data) {
          var blob = new Blob([data], {type: "application/x-pem-file"});
          saveAs(blob, "ssl.req");
        });
        return true;
      }
 
      function sbselect(e, item) {
        loadCDR(item);
      }

<?php
  if(self::checkPriv('security_writer')) {
?>

      function sbapply(e) {
        sendCDRData();
      }

<?php
  } else {
?>
    var sbapply=null;

<?php
  }
?>

      $(function () {
        var items=[];
        rightsidebar_set('#sidebarRightCollapse', items);
        rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
        sidebar_apply(null);

        updateCDRs();
      });
    </script>
    <?php
  }

  public function render() {
    ?>
        <input type="password" hidden/>
    <?php
  }

}

?>