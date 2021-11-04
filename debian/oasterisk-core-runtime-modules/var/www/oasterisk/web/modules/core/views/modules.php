<?php

namespace core;

class ModulesManage extends ViewModule {

  public static function getLocation() {
    return 'manage/modules';
  }

  public static function getMenu() {
    return (object) array('name' => 'Управление модулями', 'prio' => 10, 'icon' => 'oi oi-puzzle-piece');
  }

  public static function check($write = false) {
    $result = true;
    $result &= self::checkPriv('system_info');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public static function normalizeName($modulename) {
    $parts=explode('-',$modulename);
    $prefix='';
    switch($parts[0]) {
      case 'resource': $prefix='res_'; break;
      case 'application': $prefix='app_'; break;
      case 'function': $prefix='func_'; break;
      default: $prefix=$parts[0].'_';
    }
    return $prefix.$parts[1].'.so';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    $return = new \stdClass();
    switch($request) {
      case "desc": {
        $result = self::returnResult(\core\ModulesSettings::_getModuleDesc());
      } break;
      case "list": {
        $modules = self::getAsteriskModules();
        $return->pbx=$modules->pbx;
        $return->channel=$modules->channel;
        $return->codec=$modules->codec;
        $return->format=$modules->format;
        $return->cdr=$modules->cdr;
        $return->cel=$modules->cel;
        $return->bridge=$modules->bridge;
        $return->resource=$modules->resource;
        $return->application=$modules->application;
        $return->function=$modules->function;
        $result = self::returnResult($return);
      } break;
      case "load": {
        if(isset($request_data->module)&&self::checkPriv('system_control')) {
          $modulename=self::normalizeName($request_data->module);
          $loadstate=$this->ami->send_request('ModuleLoad',array('Module' => $modulename, 'LoadType' => 'load'));
          if($loadstate['Response']=='Success') {
            $result = self::returnSuccess('Модуль успешно загружен');
            $this->cache->delete('modules');
          } else {
            $result = self::returnError('danger', 'Не удалось загрузить модуль: '.$loadstate['Message']);
          }
        }
      } break;
      case "unload": {
        if(isset($request_data->module)&&self::checkPriv('system_control')) {
          $modulename=self::normalizeName($request_data->module);
          $loadstate=$this->ami->send_request('ModuleLoad',array('Module' => $modulename, 'LoadType' => 'unload'));
          if($loadstate['Response']=='Success') {
            $result = self::returnSuccess('Модуль успешно выгружен');
            $this->cache->delete('modules');
          } else {
            $result = self::returnError('danger', 'Не удалось выгрузить модуль: '.$loadstate['Message']);
          }
        }
      } break;
      case "reload": {
        if(isset($request_data->module)&&self::checkPriv('system_control')) {
          $modulename=self::normalizeName($request_data->module);
          $loadstate=$this->ami->send_request('ModuleLoad',array('Module' => $modulename, 'LoadType' => 'reload'));
          if($loadstate['Response']=='Success') {
            $result = self::returnSuccess('Модуль успешно перезагружен');
            $this->cache->delete('modules');
          } else {
            $result = self::returnError('danger', 'Не удалось перезагрузить модуль: '.$loadstate['Message']);
          }
        }
      } break;
    }
    return $result;
  }

  public function scripts() {
    ?>
    <script>
      function mapSection(section) {
        switch(section) {
          case 'pbx': return 'Конфигурация платформы'; break;
          case 'channel': return 'Канальные драйверы'; break;
          case 'codec': return 'Аудио/Видео кодеки'; break;
          case 'format': return 'Форматы передачи данных RTP'; break;
          case 'cdr': return 'Детализация вызовов'; break;
          case 'cel': return 'Детализация событий звонка'; break;
          case 'bridge': return 'Объединение голосовых каналов'; break;
          case 'resource': return 'Разделяемые ресурсы'; break;
          case 'application': return 'Приложения логики управления'; break;
          case 'function': return 'Функции переменных логики управления'; break;
          default: return section;
        }
      }

      var desc = null;

      function mapModule(section, name) {
        var result=name;
        if(typeof desc[section] != 'undefined') {
          if(typeof desc[section][name] != 'undefined') {
            result = desc[section][name];
          }
        }
        return result;
      }

      function updateDescriptions() {
        sendRequest('desc').success(function(data) {
          desc = data;
        });
      }

      function updateModules() {
        sendRequest('list').success(function(data) {
          $('#module-list').html('');
          var modules=$('<ul class="list-group d-inline-block cel-12 c-1 c-md-2 c-lg-3 c-xl-4 pr-3" id="confbridge-users-list"></ul>').appendTo($('#module-list'));
          for(section in data) {
            sectiontitle=mapSection(section);
            var section_list = $('<li class="list-group-item virtual d-inline-block w-100 pl-0 pr-0"><div class="group-header">'+sectiontitle+'</div><ul class="list-group col-12 pr-0"></ul></li>').appendTo(modules).find('ul');
            for(module in data[section]) {
              var item=$('<li class="small list-group-item pt-1 pb-1 ml-3 mr-3" style="transition: background-color 0.5s ease;" id="module-'+section+'-'+module+'">'+mapModule(section, module)+'</li>').appendTo(section_list);
              if(data[section][module].loaded) {
                item.addClass('list-group-item-success');
              } else {
                item.addClass('list-group-item-danger');
              }
              var check=$('<div class="custom-control custom-checkbox float-right toggle small"><input type="checkbox" class="custom-control-input" id="'+section+'-'+module+'" onInput="event.preventDefault(); event.stopPropagation(); changeState(event.originalTarget); return false;"><label class="custom-control-label" for="'+section+'-'+module+'">&nbsp;</label><span class="bg"></span></div>').appendTo(item).find('input');
              check.prop('checked',data[section][module].loaded);
<?php if(self::checkPriv('system_control')) { ?>
              var reload=$('<button class="btn btn-light btn-xs float-right" onClick="reloadModule(\''+section+'-'+module+'\')"><span class="oi oi-reload"></span></button>').appendTo(item);
              if(!data[section][module].loaded) reload.hide();
<?php } else { ?>
              check.prop('disabled',true);
<?php } ?>
            }
          }
        });
      }

      function loadModule(module) {
        sendRequest('load', {module: module}).success(function(data) {
          var amodule=$('#module-'+module);
          amodule.find('input').prop('checked',true);
          amodule.find('button').show();
          amodule.removeClass('list-group-item-danger').addClass('list-group-item-success');
          return true;
        }).error(function(e) {
          $('#'+module).prop('checked',false);
          return true;
        });
      }

      function unloadModule(module) {
        sendRequest('unload', {module: module}).success(function(data) {
          var amodule=$('#module-'+module);
          amodule.find('input').prop('checked',false);
          amodule.find('button').hide();
          amodule.removeClass('list-group-item-success').addClass('list-group-item-danger');
          return true;
        }).error(function(e) {
          $('#'+module).prop('checked',true);
          return true;
        });
      }
      function reloadModule(module) {
        sendRequest('reload', {module: module}).success(function(data) {
          showalert('success','Конфигурация модуля успешно обновлена');
          updateModules();
          return true;
        });
      }

      function changeState(obj) {
        if(obj.checked) {
          loadModule(obj.id);
        } else {
          unloadModule(obj.id);
        }
      }

      $(function () {
        updateDescriptions();
        updateModules();
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
      });
    </script>
    <?php
  }

  public function render() {
    ?>
       <div class="form-group" style="margin-right: -1.5rem;">
        <div class="d-flex flex-wrap col-12 pl-1 pr-0" id="module-list">
        </div>
       </div>
    <?php
  }

}

?>