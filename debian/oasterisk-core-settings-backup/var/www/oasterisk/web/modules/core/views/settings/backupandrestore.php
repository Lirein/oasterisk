<?php

namespace core;

class BackupAndRestoreSettings extends ViewModule {

  private static $versions = array(
    '0.9' => array(__CLASS__, 'restore_09'),
  );

  private static $asterisketc = null;
  private static $asteriskdb = null;

  protected static function init() {
    global $_CACHE;
    if((self::$asterisketc==null)||(self::$asteriskdb==null)) {
      self::$asterisketc=$_CACHE->get('asterisketc');
      self::$asteriskdb=$_CACHE->get('asteriskdb');
      if((!self::$asterisketc)||(!self::$asteriskdb)) {
        $data = new \INIProcessor('/etc/asterisk/asterisk.conf');
        if(isset($data->directories->astetcdir)) self::$asterisketc=(string) $data->directories->astetcdir;
        if(isset($data->directories->astdbdir)) self::$asteriskdb=(string) $data->directories->astdbdir;
        $_CACHE->set('asterisketc',self::$asterisketc,60);
        $_CACHE->set('asteriskdb',self::$asteriskdb,60);
      }
    }
  }

  public static function getLocation() {
    return 'settings/backupandrestore';
  }

  public static function getMenu() {
    return (object) array('name' => 'Резервные копии', 'prio' => 13, 'icon' => 'oi oi-collapse-down');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkPriv('dialplan_reader');
    $result &= self::checkPriv('security_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  protected static function get_metadata($source) {
    $data = false;
    $file = null;
    if(file_exists($_SERVER['DOCUMENT_ROOT'].'/../backups/'.$source)) {
      $file=$_SERVER['DOCUMENT_ROOT'].'/../backups/'.$source;
    } elseif((strpos($source,sys_get_temp_dir())===0)&&file_exists($source)) {
      $file=$source;
    }
    if(($file!=null)&&file_exists($file)) {
      $zip = new \ZipArchive;
      if(($ret = $zip->open($file)) === TRUE) {
        $fd = $zip->getStream('manifest.xml');
        if($fd) {
          $contents = stream_get_contents($fd);
          fclose($fd);
          try {
            $data = new \SimpleXMLElement($contents);
          } catch(\Exception $e) {
            $data = false;
          }
        }
      }
    } else {
      try {
        $data = new \SimpleXMLElement($source);
      } catch(\Exception $e) {
        $data = false;
      }
    }
    return $data;
  }

  protected static function new_metadata($name) {
    $data = new \SimpleXMLElement('<backup></backup>');
    $data->addChild('title',$name);
    $versions = array_keys(self::$versions);
    $lastversion=array_pop($versions);
    $data->addChild('version',$lastversion);
    $dt = new \DateTime();
    $data->addChild('date',$dt->format(\DateTime::ISO8601));
    return $data->asXML();
  }

  protected static function extract_to_temp($source) {
    $result = false;
    if(file_exists($_SERVER['DOCUMENT_ROOT'].'/../backups/'.$source)) {
      $zip = new \ZipArchive;
      if(($ret = $zip->open($_SERVER['DOCUMENT_ROOT'].'/../backups/'.$source)) === TRUE) {
        $tmpdir = tempnam(sys_get_temp_dir(), 'OTK');
        if(unlink($tmpdir)&mkdir($tmpdir, 0700)) {
          if($zip->extractTo($tmpdir)) {
            $result=$tmpdir;
          } else {
            self::recursiveRemoveDirectory($tmpdir);
          }
        }
        $zip->close();
      }
    }
    return $result;
  }

  protected static function restore_09($source) {
    $result = false;
    $extractdir = self::extract_to_temp($source);
    if($extractdir) {
      if(file_exists($extractdir.'/config')&&file_exists($extractdir.'/db')) {
        self::recursiveRemoveDirectory(self::$asterisketc, true);
        unlink(self::$asteriskdb.'/astdb.sqlite3');
        self::copyDirectory($extractdir.'/config',self::$asterisketc);
        self::copyDirectory($extractdir.'/db',self::$asteriskdb);
        $result = true;
      }
      self::recursiveRemoveDirectory($extractdir);
    }
    return $result;
  }

  protected static function copyDirectory($source, $destination) {
    foreach(glob("{$source}/*") as $file) {
        if(is_dir($file)) {
            mkdir($destination.'/'.basename($file),0770);
            self::copyDirectory($file,$destination.'/'.basename($file));
        } else {
            copy($file,$destination.'/'.basename($file));
        }
    }
  }

  protected static function recursiveRemoveDirectory($directory, $first=false) {
    foreach(glob("{$directory}/*") as $file) {
        if(is_dir($file)) {
            self::recursiveRemoveDirectory($file);
        } else {
            unlink($file);
        }
    }
    if(!$first) rmdir($directory);
  }

  protected static function createbackup($name) {
    if(!extension_loaded('zip')) {
        return false;
    }
    $result = false;
    $tmpdir = tempnam(sys_get_temp_dir(), 'OTK');
    if(unlink($tmpdir)&mkdir($tmpdir, 0770)) {
      mkdir($tmpdir.'/config', 0770);
      mkdir($tmpdir.'/db', 0770);
      self::copyDirectory(self::$asterisketc, $tmpdir.'/config');
      copy(self::$asteriskdb.'/astdb.sqlite3',$tmpdir.'/db/astdb.sqlite3');
      $zip = new \ZipArchive();
      $dt = new \DateTime();
      $destination=$_SERVER['DOCUMENT_ROOT'].'/../backups/'.$dt->format('YmdHis').'.zip';
      if($zip->open($destination, \ZIPARCHIVE::CREATE)) {
        if(is_dir($tmpdir) === true) {
          $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tmpdir), \RecursiveIteratorIterator::SELF_FIRST);
          foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if(in_array(substr($file, strrpos($file, '/')+1), array('.', '..'))) continue;
            $file = realpath($file);

            if(is_dir($file) === true) {
                $zip->addEmptyDir(str_replace($tmpdir . '/', '', $file . '/'));
            } else if(is_file($file) === true) {
                $zip->addFromString(str_replace($tmpdir . '/', '', $file), file_get_contents($file));
            }
          }
        }
        $zip->addFromString('manifest.xml',self::new_metadata($name));
        $result = true;
      }
      self::recursiveRemoveDirectory($tmpdir);
      $result = $result&&$zip->close();
    }
    return $result;
  }

  private static function backupcmp(&$a, &$b) {
    return strcmp($a->time, $b->time);
  }

  public function json(string $request, \stdClass $request_data) {
    self::init();
    $result = new \stdClass();
    switch($request) {
      case "upload": {
        $result = self::returnError('warning','Не передан файл резервной копии');
        if(isset($request_data->data)) {
          $result= self::returnError('warning','Невозможно сохранить файл резервной копии');
          $tmpfile = tempnam(sys_get_temp_dir(), 'OTK');
          if(unlink($tmpfile)&&(file_put_contents($tmpfile,base64_decode(substr($request_data->data, strpos($request_data->data, ',')+1)))!==FALSE)) {
            $result= self::returnError('warning','Файл не является файлом резервной копии');
            $metadata=self::get_metadata($tmpfile);
            if($metadata) {
              $result= self::returnError('warning','Версия формата резервной копии не поддерживается, обновите OAS´terisk');
              if(isset(self::$versions[(string)$metadata->version])) {
                $result= self::returnError('warning','Невозможно получить дату резервной копии');
                try {
                  $dt = \DateTime::createFromFormat(\DateTime::ISO8601,(string)$metadata->date);
                  if($dt===FALSE) {
                    throw new \Exception('Unable to create date from string');
                  }
                  $destination=$_SERVER['DOCUMENT_ROOT'].'/../backups/'.$dt->format('YmdHis').'.zip';
                } catch(\Exception $e) {
                  $dt = new \DateTime();
                  $destination=$_SERVER['DOCUMENT_ROOT'].'/../backups/'.$dt->format('YmdHis').'.zip';
                }
                if(copy($tmpfile,$destination)) {
                  $result= self::returnSuccess('Резервная копия успешно загружена');
                  touch($destination,$dt->getTimestamp());
                }
              }
            }
            unlink($tmpfile);
          }
        }
      } break;
      case "remove": {
        if(isset($request_data->codename)&&self::checkPriv('security_writer')&&self::checkPriv('settings_writer')&&self::checkPriv('dialplan_writer')) {
          $result = self::returnError('danger', 'Не удалось удалить файл резервной копии');
          if(file_exists($_SERVER['DOCUMENT_ROOT'].'/../backups/'.$request_data->codename.'.zip')) {
             unlink($_SERVER['DOCUMENT_ROOT'].'/../backups/'.$request_data->codename.'.zip');
             $result = self::returnSuccess('Файл резервной копии удален');
          }
        }
      } break;
      case "restore": {
        $result =self::returnError('danger', 'Не удалось востановить резервную копию');
        if(isset($request_data->codename)&&self::checkPriv('security_writer')&&self::checkPriv('settings_writer')&&self::checkPriv('dialplan_writer')) {
          if(file_exists($_SERVER['DOCUMENT_ROOT'].'/../backups/'.$request_data->codename.'.zip')) {
            $metadata = self::get_metadata($request_data->codename.'.zip');
            if($metadata) {
              if(isset(self::$versions[(string)$metadata->version])) {
                if(self::$versions[(string)$metadata->version]($request_data->codename.'.zip')) {
                  try {
                    $this->ami->send_request('Command', array('Command' => 'core restart gracefully'));
                  } catch(\Exception $e) {
                    ;
                  }
                  $this->cache->delete('modules');
                  $result = self::returnSuccess('Резервная копия успешно восстановлена');
                }
              }
            }
          }
        }
      } break;
      case "create": {
        if(isset($request_data->codename)) {
          $ret = self::createBackup($request_data->codename);
          if ($ret !== true) {
            $result = self::returnError('danger', 'Невозможно создать резервную копию');
            error_log(sprintf('Failed with %d', $ret));
          } else {
            $result = self::returnSuccess('Резервная копия создана успешно');
          }
        }
      } break;
      case "list": {
        $list = array();
        $dir = $_SERVER['DOCUMENT_ROOT'].'/../backups/';
        if(is_dir($dir)) {
          if($dh = opendir($dir)) {
            while(($file = readdir($dh)) !== false) {
              $metadata = self::get_metadata($file);
              if($metadata) {
                $info = new \stdClass();
                $info->codename=substr($file,0,-4);
                $info->name=(string)$metadata->title;
                $info->time=(string)$metadata->date;
                $list[]=$info;
              }
            }
            closedir($dh);
          }
        }
        uasort($list, array(__CLASS__, 'backupcmp'));
        $result = self::returnResult(array_values($list));
      } break;
    }
    return $result;
  }

  public function scripts() {
    global $location;
    ?>
    <script>
      function upload(event, obj) {
        var reader = new FileReader();
        reader.onloadend = function(evt) {
          if(evt.target.readyState == FileReader.DONE) {
            sendRequest('upload', {data: evt.target.result}).success(function(data) {
              loadBackups();
              return true;
            });
          }
        }
        reader.readAsDataURL(event.target.files[0]);
      }

      function loadBackups() {
        sendRequest('list').success(function(data) {
          var backups=$('#backup-list').html('');
          if(data.length) {
            backups.append('<li class="list-group-item list-group-item-light">Резервные копии</li>');
            for(var i = 0; i < data.length; i++) {
<?php
  if(self::checkPriv('security_writer')&&self::checkPriv('settings_writer')&&self::checkPriv('dialplan_writer')) {
?>
              backups.append('<li class="list-group-item list-group-item"><span class="badge badge-pill badge-secondary">'+getDateTime(new Date(data[i].time))+'</span> '+data[i].name+'<span class="btn-group right" style="top: 0.3rem;"><a href="/backups/'+data[i].codename+'.zip" class="btn btn-primary">Скачать</a><button type="button" class="btn btn-warning" onClick="restoreConfig(\''+data[i].codename+'\')">Восстановить</button><button type="button" class="btn btn-danger" onClick="deleteBackup(\''+data[i].codename+'\')">Удалить</button></span</li>');
<?php } else { ?>
              backups.append('<li class="list-group-item list-group-item"><span class="badge badge-pill badge-secondary">'+getDateTime(new Date(data[i].time))+'</span> '+data[i].name+'<span class="btn-group right" style="top: 0.3rem;"><a href="/backups/'+data[i].codename+'.zip" class="btn btn-primary">Скачать</a></span</li>');
<?php } ?>
            }
          } else {
            backups.append('<li class="list-group-item list-group-item-danger">Резервных копий не обнаружено</li>');
          }
        });
      }

      function deleteBackup(name) {
        showdialog('Удалить резервную копию','Вы уверены что хотите удалить резервную копию?','error',['Yes','No'], function(result) { 
          if(result=='Yes') {
            sendRequest('remove', {codename: name}).success(function(data) {
              loadBackups();
              return true;
            });
          }
        });
      }

      function restoreConfig(name) {
        showdialog('Заменить конфигурацию','Вы уверены что хотите заменить текущую конфигурацию из файла резервной копии?','warning',['Yes','No'], function(result) { 
          if(result=='Yes') {
            sendRequest('restore', {codename: name}).success(function(data) {
              loadBackups();
              return true;
            });
          }
        });
      }

      function createBackup(obj) {
        var dialog = $('#backup-dialog');
        if(typeof obj == "undefined") {
          dialog.modal('show');
        } else {
          var codename = dialog.find('#codename');
          if(codename.get(0).checkValidity()) {
            dialog.modal('hide');
            sendRequest('create', {codename: codename.val()}).success(function(data) {
              loadBackups();
              return true;
            });
          } else {
            showalert('warning','Не корректное имя резервной копии');
          }
        }
      }

      $(function () {
        loadBackups();
      });
    </script>
    <?php
  }

  public function render() {
    ?>
         <div class="modal fade" id="backup-dialog" tabindex="-1" role="dialog" aria-hidden="true">
           <div class="modal-dialog" role="document">
             <div class="modal-content">
               <div class="modal-header">
                 <h5 class="modal-title" id="exampleModalLabel">Создание резервной копии</h5>
                 <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                   <span aria-hidden="true">&times;</span>
                 </button>
               </div>
             <div class="modal-body">
               <div class="form-group">
                 <label for="codename">Наименование резервной копии</label>
                 <input type="text" class="form-control" id="codename" placeholder="Введите имя файла резервной копии" pattern="[a-zA-Z0-9а-яА-Я\-_ ]+">
               </div>
             </div>
             <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
               <button type="button" class="btn btn-primary" onClick='createBackup(this)'>Создать</button>
             </div>
           </div>
         </div>
       </div>
       <div class="form-group">
        <ul class="list-group" id='backup-list'>
        </ul>
       </div>
       <div class="form-group">
         <button style="margin-right: 1rem;" class="btn btn-success" onClick="createBackup()">Создать резервную копию</button><input id="backup-upload" class="custom-file-input" style="min-width:0px; max-width:0px;" onchange="upload(event,this)" accept=".zip" type="file"><button class="btn btn-info" onclick="$(this).prev().click()">Загрузить резервную копию</button>
       </div>
    <?php
  }

}

?>