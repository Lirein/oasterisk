<?php

namespace core;

class MusicOnHoldREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'general/musiconhold';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "audio": {
        if(isSet($request_data->file)) {
          $filename = \sound\MOHs::getMOHDirectory().'/'.$request_data->file;
          if(file_exists($filename)) {
            $info = pathinfo($request_data->file);
            if(in_array($info['extension'], array_keys(\sound\Sounds::getFormats()))) {
              $tmpfilename = tempnam('/tmp', 'oasterisk-music-');
              unlink($tmpfilename);
              $tmpfilename .= '.mp3';
              $sox = sprintf("sox \"%s\" \"%s\"", $filename, $tmpfilename);
              system($sox); 
              $result = self::returnFile($tmpfilename);
              unlink($tmpfilename);
            } else {
              $result = self::returnError('warning', 'Формат файла '.$request_data->file.' не поддерживается');
            }
          } else {
            $result = self::returnError('danger', 'Указанного файла не сущетсвует');
          }
        } else {
          $result = self::returnError('danger', 'Не указано имя файла');
        }
      } break;
      case "menuItems":{
        $moh = new \sound\MOHs();
        $objects = array();
        foreach($moh as $id => $data) {
          $objects[]=(object)array('id' => $id, 'title' => $data->title);
        }
        $result = self::returnResult($objects);
        unset($moh);
      } break;
      case "get": {
        if(isset($request_data->id)&&self::checkEffectivePriv('moh', $request_data->id, 'settings_reader')) {
          $moh = new \sound\MOH($request_data->id);
          $result = self::returnResult($moh->cast());
          unset($moh);
        }
      } break;
      case "set": {
        if(isset($request_data->id)&&self::checkEffectivePriv('moh', $request_data->id, 'settings_writer')) {
          $moh = new \sound\MOH($request_data->id);
          $moh->assign($request_data);
          if ($moh->save()) {
            $moh->reload();
            $result = self::returnResult((object)array('id' => $moh->id));
          } else {
            $result = self::returnError('danger', 'Не удалось сохранить профиль');
          }
          unset($moh);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "add": {
        if((empty($request_data->id)||($request_data->id == 'false'))&&self::checkPriv('settings_writer')) {
          $moh = new \sound\MOH();
          $moh->assign($request_data);
          if ($moh->save()) {
            $moh->reload();
            $result = self::returnResult((object)array('id' => $moh->id));
          } else {
            $result = self::returnError('danger', 'Не удалось сохранить профиль');
          }
          unset($moh);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "remove": {
        if(isset($request_data->id)&&self::checkPriv('settings_writer')) {
          $moh = new \sound\MOH($request_data->id);
          if($moh->delete()) {
            $moh->reload();
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Не удалось удалить профиль');
          }
          unset($moh);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "get-directories":{
        $moh = new \sound\MOHs();
        $result = self::returnResult($moh->getMOHDirectories());
        unset($moh);
      } break;   
      case "get-sounds":{
        $sounds = new \sound\Sounds();
        $soundList = array();
        foreach($sounds as $k => $v) {
          $soundList[] = (object) array('id' => $k, 'title' => $v->title);
        }
        $soundList = array_values($soundList);

        $result = self::returnResult($soundList);
      } break;  
      case "get-files":{
        if(isset($request_data->directory)&&($request_data->directory)) {
          $moh = new \sound\MOHs();
          $result = self::returnResult($moh->getMOHFiles($request_data->directory));
          unset($moh);
        } else {
          $result = self::returnError('danger', 'Директория не указана');
        }
      } break; 
      case "upload-files": {
        if(isset($request_data->directory)) {
          if(isset($request_data->files)) {
            $errors = array();
            $path = \sound\MOHs::getMOHDirectory().'/'.$request_data->directory;
            //$moh = new \sound\MOHs();
            foreach($request_data->files as $file) {
              // $mimeMagicFile = __DIR__;
              // while((strlen($mimeMagicFile)>1)&&(basename($mimeMagicFile)!='web')) {
              //   $mimeMagicFile = dirname($mimeMagicFile);
              // }
              // $mimeMagicFile .= '/music.mime';
              // $finfo = new \finfo(FILEINFO_MIME);
              // $finfo_own = new \finfo(FILEINFO_MIME, $mimeMagicFile);
              // if((strpos($finfo->file($file->tmp_name), 'audio/')===0)||(strpos($finfo_own->file($file->tmp_name), 'audio/')===0)) {
              //   copy($file->tmp_name, $moh->getMOHDirectory().'/'.$request_data->directory.'/'.$file->name); 
              // } else {
              //   $errors[] = 'Файл '.$file->name.' не является аудио файлом';
              // }
              if (!file_exists($path.'/'.$file->name)) {
                if ($file->size < 268435456) {
                  $info = pathinfo($file->name);
                  if(in_array($info['extension'], array_keys(\sound\Sounds::getFormats()))) {
                    //$sound = new \sound\MOH('custom/'.pathinfo($file->name, PATHINFO_FILENAME));
                    if(empty($file->language)) $file->language = 'other';
                    if (!file_exists($path)) {
                      mkdir($path);
                    }
                    umask(0007);
                    if (!copy($file->tmp_name, $path.'/'.$file->name)){
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
        } else {
          $result = self::returnError('danger', 'Директория не указана');
        }
      } break;    
      case "delete-file": {
        if(isset($request_data->directory)) {
          if(isset($request_data->file)) {
            error_log(\sound\MOHs::getMOHDirectory().'/'.$request_data->file);
            if (unlink(\sound\MOHs::getMOHDirectory().'/'.$request_data->file)){
              $result = self::returnSuccess();
            } else {
              $result = self::returnError('danger', 'Не удалось удалить файл');
            } 
          } else {
            $result = self::returnError('danger', 'Файлы не выбраны');
          }
        } else {
          $result = self::returnError('danger', 'Директория не указана');
        }
      } break; 
    }
    return $result;
  }
    
}

?>