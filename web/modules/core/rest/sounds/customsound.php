<?php

namespace core;

class CustomSoundREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'sound/custom';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        $sounds = new \core\CustomSounds();
        $soundList = array();
        foreach($sounds as $k => $v) {
          $soundList[$k] = (object) array('id' => $k, 'title' => $v->title, 'system' => ($v instanceof \core\SystemSound));
          if(dirname($k)!='.') {
            if(!isset($soundList[dirname($k)])) {
              $soundList[dirname($k)] = (object) array('id' => dirname($k), 'title' => basename(dirname($k)));
            }
            if(!isset($soundList[dirname($k)]->value)) $soundList[dirname($k)]->value = array();
            $soundList[dirname($k)]->value[] = $soundList[$k];
            $soundList[$k]->remove = true;
          }
        }
        foreach($soundList as $k => $v) {
          if(isset($v->remove)) {
            unset($soundList[$k]->remove);
            unset($soundList[$k]);
          }
        }
        $soundList = array_values($soundList);
        $result = self::returnResult($soundList);
      } break;
      case "get": {
        if(self::checkPriv('settings_reader')) {
          if (isset($request_data->id)){
            $sound = new \core\CustomSound($request_data->id);
            if($sound->old_id) {
              $result = self::returnResult($sound->cast());
            } else {
              $result = self::returnError('warning', 'Звука с таким идентификатором не существует');
            }
          }
        } else {
          $result = self::returnError('danger', 'Доступ запрещен');
        }
      } break;
      case "record":{
        if(isset($request_data->file)) {
          $errors = array();
          $file = $request_data->file;
          $file->name = $request_data->name.'.wav';
          $file->language = $request_data->language;
          if (!file_exists(\sound\Sounds::getSoundsDir().'/'.(($file->language=='other')?'':($file->language.'/')).'custom/'.basename($file->name))) {
            if ($file->size < 268435456) {
              $sound = new \core\CustomSound('custom/'.$file->name);
              if (!$sound->upload($file)){
                $result = self::returnError('danger', 'Файл '.$file->name.' не удалось загрузить');
              } else {
                $result = self::returnSuccess();
              }
            } else {
              $result = self::returnError('danger', 'Файл '.$file->name.' слишком велик');
            }
          } else {
            $result = self::returnError('danger', 'Файл '.$file->name.' уже существует');
          }
        } else {
          $result = self::returnError('danger', 'Файлы не выбраны');
        }
      } break;
      case "upload": {
        if(isset($request_data->files)) {
          $errors = array();
          foreach($request_data->files as $file) {
            $file->language = $request_data->language;
            if (!file_exists(\sound\Sounds::getSoundsDir().'/'.(($file->language=='other')?'':($file->language.'/')).'custom/'.basename($file->name))) {
              if ($file->size < 268435456) {
                $info = pathinfo($file->name);
                if(in_array($info['extension'], array_keys(\sound\Sounds::getFormats()))) {
                  $sound = new \core\CustomSound('custom/'.pathinfo($file->name, PATHINFO_FILENAME));
                  if (!$sound->upload($file)){
                    $errors[] = 'Файл '.$file->name.' не удалось загрузить';
                  }
                } else {
                  $errors[] = 'Формат файла '.$file->name.' не поддерживается';
                }
              } else {
                $errors[] = 'Файл '.$file->name.' слишком велик';
              }
            } else {
              $errors[] = 'Файл '.$file->name.' уже существует';
            }
          }
          if(count($errors)) {
            $result = self::returnError((count($errors)==count($request_data->files))?'danger':'warning', implode('<br>', $errors));
          } else {
            $result = self::returnSuccess();
          }
        } else {
          $result = self::returnError('danger', 'Файлы не выбраны');
        }
      } break;    
      case "delete": {
        if (self::checkPriv('settings_reader')) {
          if (isset($request_data->id)) {
            $sound = new \core\CustomSound($request_data->id);
            if ($sound->delete($request_data->language, $request_data->format)){
              $result = self::returnSuccess();
            } else {
              $result = self::returnError('danger', 'Не удалось удалить файл');
            } 
            unset($moh);
          } else {
            $result = self::returnError('danger', 'Файлы не выбраны');
          }
        } else {
          $result = self::returnError('danger', 'Доступ запрещен');
        }
      } break; 
      

    }
    return $result;
  }
    
}

?>