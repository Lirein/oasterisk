<?php

namespace core;

class CDRREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'logs/cdr';
  }
 
  public function reloadConfig() {
    $this->ami->send_request('Command', array('Command' => 'cdr reload'));
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems": {
        $logs = array();
        $logs[] = (object) array('id' => 'settings', 'title' => 'Настройки журналов');
        $engines = findModulesByClass('core\ICDRRestInterface');
        foreach($engines as $cdr) {
          $classname = $cdr->class;
          $baseclass = strtolower(basename(str_replace('\\', '/', $classname)));
          if(strtolower($baseclass) != 'settings') {
            $logs[] = (object)array(
              'id' => $baseclass,
              'title' => $classname::getTitle()
            );
          }
        }
        $result = self::returnResult($logs);
      } break;
      case "get": {
        if(self::checkPriv('settings_reader')) {
          if(isset($request_data->id)) {
            $result = null;
            $engines = findModulesByClass('core\ICDRRestInterface');
            foreach($engines as $cdr) {
              $classname = $cdr->class;
              $baseclass = strtolower(basename(str_replace('\\', '/', $classname)));
              if($baseclass == $request_data->id) {
                $cdrsettings = new $classname();
                $result = $cdrsettings->get();
                break;
              }
            }
            if($result) {
              $result->id = $request_data->id;
              $result = self::returnResult($result);
            } else {
              $result = self::returnError('danger', 'Набор настроек с таким идентификатором не найден');
            }
          } else {
            $result = self::returnError('danger', 'Не указан идентификатор набора настроек');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "set": {
        if(self::checkPriv('settings_writer')) {
          if(isset($request_data->id)) {
            $result = null;
            $engines = findModulesByClass('core\ICDRRestInterface');
            foreach($engines as $cdr) {
              $classname = $cdr->class;
              $baseclass = strtolower(basename(str_replace('\\', '/', $classname)));
              if($baseclass == $request_data->id) {
                $cdrsettings = new $classname();
                unset($request_data->id);
                $result = $cdrsettings->set($request_data);
                break;
              }
            }
            if($result) {
              $result = self::returnSuccess();
              $this->reloadConfig();
            } elseif($result===null) {
              $result = self::returnError('danger', 'Набор настроек с таким идентификатором не найден');
            } else {
              $result = self::returnError('danger', 'Невозможно установить набор настроек');
            }
          } else {
            $result = self::returnError('danger', 'Не указан идентификатор набора настроек');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      default: {
        if(self::checkPriv('settings_reader')) {
          if(isset($request_data->id)) {
            $result = null;
            $engines = findModulesByClass('core\ICDRRestInterface');
            foreach($engines as $cdr) {
              $classname = $cdr->class;
              $baseclass = strtolower(basename(str_replace('\\', '/', $classname)));
              if($baseclass == $request_data->id) {
                $cdrsettings = new $classname();
                unset($request_data->id);
                $result = $cdrsettings->json($request, $request_data);
                break;
              }
            }
            if($result===null) {
              $result = self::returnError('danger', 'Набор настроек с таким идентификатором не найден');
            }
          } else {
            $result = self::returnError('danger', 'Не указан идентификатор набора настроек');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      }
    }
    return $result;
  }

  // public function getParams() {
  //   $result = new \stdClass();
  //   $ini = self::getINI('/etc/asterisk/cdr.conf');
  //   $returnData = $ini->general->getDefaults(self::$cdrparams);
  //   $returnData->activeengines = array();
  //   $modules = findModulesByClass('core\CdrEngine', true);
  //   foreach($modules as $module) {
  //     $classname = $module->class;
  //     $classinfo = $classname::info();
  //     $returnData->activeengines[]= $classinfo->name;
  //   }
  //   $result = $returnData;
  //   return $result;
  // }

  // public function setParams($data) {
  //   $ini = self::getINI('/etc/asterisk/cdr.conf');
  //   // $ini->general->setDefaults(self::$cdrparams, $data);
  //   // $modules = getModulesByClass('core\CdrEngineSettings', true);
  //   // $passiveengines = array();
  //   // if(isset($data->activeengines)) {
  //   //   if (!is_array($data->activeengines)) {
  //   //     $data->activeengines = array();
  //   //   }
  //   //   if (!empty($data->activeengines)) {
  //   //     foreach($data->activeengines as $engine) {
  //   //       foreach($modules as $module) {
  //   //         $classinfo = $module::info();
  //   //         if($classinfo->id == $engine) {
  //   //           $module->enable();
  //   //         } else {
  //   //           if(!in_array($module, $passiveengines)) $passiveengines[] = $module;
  //   //         }
  //   //       }
  //   //     }
  //   //   } else {
  //   //     $passiveengines = $modules;
  //   //   }
  //   // }
  //   // foreach($passiveengines as $engine) $engine->disable();
  //   return $ini->save();
  //}
    
}

?>