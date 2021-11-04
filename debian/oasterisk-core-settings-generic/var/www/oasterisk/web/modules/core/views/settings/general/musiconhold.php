<?php

namespace core;

class MusicOnHoldSettings extends ViewModule {
  public static function getLocation() {
    return 'settings/general/musiconhold';
  }

  public static function getMenu() {
    return (object) array('name' => 'Музыка на удержании', 'prio' => 3, 'icon' => 'oi oi-musical-note'); //oi oi-headphones
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  /**
   * Перегружает конфигурацию на стороне технологической платформы
   *
   * @return void
   */
  public function reloadConfig() {
    $this->ami->send_request('Command', array('Command' => 'moh'));
  }

  private function sortlist($a, $b) {
    return strcmp($a->text, $b->text);
  }

  private function getMOHDirectory() {
    $ini = new \INIProcessor('/etc/asterisk/asterisk.conf');
    $return = (string) $ini->directories->astdatadir;
    unset($ini);
    return $return;
  }

  private function getMOHDirectories($dir = '', $rootdir = '') {
    if($rootdir == '') $rootdir = $this->getMOHDirectory();
    $list = array();
    if($dh = opendir($rootdir . ($dir?('/'.$dir):''))) {
      while(($file = readdir($dh)) !== false) {
        if(is_dir($rootdir . ($dir?('/'.$dir):'') . '/' . $file)) {
          if($file[0]!='.') {
            $directryinfo = (object) array('id' => ($dir?($dir.'/'):'').$file, 'text' => $file, 'icon' => 'oi oi-folder');
            $directryinfo->value = $this->getMOHDirectories(($dir?($dir.'/'):'').$file, $rootdir);
            $list[] = $directryinfo;
          }
        }
      }
      closedir($dh);
    }
    usort($list, array($this, 'sortlist'));
    return $list; 
  }

  private function getMOHFiles($dir = '', $rootdir = '') {
    if($rootdir == '') $rootdir = $this->getMOHDirectory();
    $list = array();
    if($dh = opendir($rootdir . ($dir?('/'.$dir):''))) {
      while(($file = readdir($dh)) !== false) {
        if(is_dir($rootdir . ($dir?('/'.$dir):'') . '/' . $file)) {
          if($file[0]!='.') {
            $directoryinfo = (object) array('id' => ($dir?($dir.'/'):'').$file, 'text' => $file, 'icon' => 'oi oi-folder');
            $directoryinfo->value = $this->getMOHFiles(($dir?($dir.'/'):'').$file, $rootdir);
            $list[] = $directoryinfo;
          }
        } else {
          $directoryinfo = (object) array('id' => ($dir?($dir.'/'):'').$file, 'text' => $file, 'icon' => 'oi oi-file');
          $list[] = $directoryinfo;
        }
      }
      closedir($dh);
    }
    usort($list, array($this, 'sortlist'));
    return $list; 
  }

  public static function getZoneInfo() {
    $result = new \SecZoneInfo();
    $result->zoneClass = 'moh';
    $result->getObjects = function () {
                              $profiles = array();
                              $ini = new \INIProcessor('/etc/asterisk/musiconhold.conf');
                              foreach($ini as $k => $v) {
                                if(isset($v->mode)) {
                                  $profile = new \stdClass();
                                  if(!empty($v->getDescription())) {
                                    $profile->text = $v->getDescription();
                                  } else {
                                    $profile->text = $k;
                                  }
                                }
                              }
                              return $profiles;
                           };
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    $defaultMohparams = '{
      "mode": "",
      "directory": "",
      "digit": "",   
      "announcement": "",
      "sort": "",
      "application": "",
      "format": "",
      "kill_escalation_delay": "500",
      "kill_method": "process_group"
    }';
    $zonesmodule=getModuleByClass('core\SecZones');
    if($zonesmodule) $zonesmodule->getCurrentSeczones();
    $result = new \stdClass();
    switch($request) {
      case "audio": {
        $filename = $this->getMOHDirectory().'/'.$request_data->file;
        $tmpfilename = tempnam('/tmp', 'oasterisk-music-');
        unlink($tmpfilename);
        $tmpfilename .= '.mp3';
        $sox = sprintf("sox \"%s\" \"%s\"", $filename, $tmpfilename);
        system($sox); 
        $result = self::returnFile($tmpfilename);
        unlink($tmpfilename);
      } break;
      case "mohs":{
        $ini = new \INIProcessor('/etc/asterisk/musiconhold.conf');
        $returnData = array();
        foreach($ini as $k => $v){
          if(isset($v->mode)) {
            $profile = new \stdClass();
            $profile->id = $k;
            $profile->title = empty($v->getComment())?$k:($v->getComment());
            if(self::checkEffectivePriv('moh', $profile->id, 'settings_reader')) $returnData[]=$profile;
          }
        }
        $result = self::returnResult($returnData);
      } break;
      case "moh-profile-get": {
        if(isset($request_data->moh)&&self::checkEffectivePriv('moh', $request_data->moh, 'settings_reader')) {
          $profile = new \stdClass();
          $ini = new \INIProcessor('/etc/asterisk/musiconhold.conf');
          $moh = $request_data->moh;
          if(isset($ini->$moh)) {
            $v = $ini->$moh;            
            $profile->id = $moh;
            $profile->title = empty($v->getComment())?$moh:$v->getComment();
            $profile = object_merge($profile, $v->getDefaults($defaultMohparams));              
            if($zonesmodule&&!$this->checkZones()) {
              $profile->zones=$zonesmodule->getObjectSeczones('moh', $profile->id);
            }
            $profile->readonly = !self::checkEffectivePriv('moh', $profile->id, 'settings_writer');
            $result = self::returnResult($profile);
          }
        }
      } break;
      case "moh-profile-set": {
        if(isset($request_data->orig_id)&&self::checkEffectivePriv('moh', $request_data->orig_id, 'settings_writer')) {
          $ini = new \INIProcessor('/etc/asterisk/musiconhold.conf');
          $id = $request_data->id;
          $orig_id = isset($request_data->orig_id)?$request_data->orig_id:'';
          if(($request_data->orig_id!='')&&($request_data->orig_id!=$request_data->id)) {
            if(isset($ini->$id)) {
              $result=self::returnError('danger', "Класс с таким иденификатором уже существует");              
              break;
            }
            $zones = $zonesmodule->getObjectSeczones('moh', $orig_id);
            foreach($zones as $zone) {
              $zonesmodule->removeSeczoneObject($zone, 'moh', $orig_id);
            }
            
            if(isset($ini->$orig_id))
              unset($ini->$orig_id);
          }
          if($orig_id == '') {
            if(isset($ini->$id)) {
              $result=self::returnError('danger', "Класс с таким иденификатором уже существует");
              break;
            }
          }
           
          if(findModuleByPath('settings/security/seczones')&&($zonesmodule&&self::checkZones())) {
            $zones = $zonesmodule->getObjectSeczones('moh', $id);
            foreach($zones as $zone) {
              $zonesmodule->removeSeczoneObject($zone, 'moh', $id);
            }
            if(isset($request_data->zones)) foreach($request_data->zones as $zone) {
              $zonesmodule->addSeczoneObject($zone, 'moh', $id);
            }
          }
          if(!isset($ini->$id)&&$zonesmodule&&$this->checkZones()) {
            $eprivs = $zonesmodule->getCurrentPrivs('moh', $id);
            $zone = isset($eprivs['settings_writer'])?$eprivs['settings_writer']:false;
            if(!$zone) $zone = isset($eprivs['settings_reader'])?$eprivs['settings_reader']:false;
            if($zone) {
              $zonesmodule->addSeczoneObject($zone, 'moh', $id);
            } else {
              $result = self::returnError('error', 'Отказано в доступе');
              break;
            }
          }
          if(!isset($ini->$id)) $ini->$id=array();
          
          $ini->$id->setDefaults($defaultMohparams, $request_data);
          $ini->$id->setComment($request_data->title);

          if($ini->save()) {
            $result = self::returnSuccess();
            $this->reloadConfig();
          } else {
            $result = self::returnError('danger', 'Невозможно сохранить настройки');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "moh-profile-remove": {
        if(isset($request_data->id)&&self::checkPriv('settings_writer')) {
          $id = $request_data->id;
          $ini = new \INIProcessor('/etc/asterisk/musiconhold.conf');
          if(isset($ini->$id)) {
            $k = $request_data->id;
            $v = $ini->$k;
            if($zonesmodule) {
              foreach($zonesmodule->getObjectSeczones('moh', $id) as $zone) {
                $zonesmodule->removeSeczoneObject($zone, 'moh', $id);
              }
            }
            unset($ini->$id);
            if($ini->save()) {
              $result = self::returnSuccess();
              $this->reloadConfig();
            } else {
              $result = self::returnError('danger', 'Невозможно сохранить настройки');
            }
            
          } else {
            $result = self::returnError('danger', 'Класса с таким идентификатором не найдено');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "get-sounds":{
        $sounds = new \core\Sounds();
        $soundList = array();
        foreach($sounds->get() as $v => $dummy) {
          $soundList[] = (object) array('id' => $v, 'text' => $v);
        }
        $result = self::returnResult($soundList);
      } break;
      case "get-formats":{
        $list = $this->ami->send_request('Command', array('Command' => 'core show file formats'));
        $formatList = explode(' ',$list['data']);
        $result = self::returnResult($formatList);
      } break;
      case "get-directories":{
        $result = self::returnResult($this->getMOHDirectories());
      } break;     
      case "get-files":{
        if(isset($request_data->directory)&&($request_data->directory)) {
          $result = self::returnResult($this->getMOHFiles($request_data->directory));
        } else {
          $result = self::returnError('danger', 'Директория не указана');
        }
      } break; 
      case "upload-files": {
        if(isset($request_data->directory)) {
          if(isset($request_data->files)) {
            $errors = array();
            foreach($request_data->files as $file) {
              $mimeMagicFile = __DIR__;
              while((strlen($mimeMagicFile)>1)&&(basename($mimeMagicFile)!='web')) {
                $mimeMagicFile = dirname($mimeMagicFile);
              }
              $mimeMagicFile .= '/music.mime';
              $finfo = new \finfo(FILEINFO_MIME);
              $finfo_own = new \finfo(FILEINFO_MIME, $mimeMagicFile);
              if((strpos($finfo->file($file->tmp_name), 'audio/')===0)||(strpos($finfo_own->file($file->tmp_name), 'audio/')===0)) {
                copy($file->tmp_name, $this->getMOHDirectory().'/'.$request_data->directory.'/'.$file->name); 
              } else {
                $errors[] = 'Файл '.$file->name.' не является аудио файлом';
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
            if (unlink($this->getMOHDirectory().'/'.$request_data->file)){
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

  public function scripts() {
    ?>
      <script>
      var browseDialog = null;
      var dialogDirectory = null;
      var files = null;

      var moh=null;
      var moh_id='<?php echo isset($_GET['id'])?$_GET['id']:'0'; ?>';
      var sound_data = [];
      var format_data = [];
      var directory = null;
      var application = null;
      var mode = null;
      var audio = null;
      var newfile = null;

      function loadMusicProfiles() {
        sendRequest('mohs').success(function(data) {
          var hasactive = false;
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              if(data[i].id==moh_id) {
                hasactive=true;
                data[i].active = true;
                break;
              }
            }
          };
          rightsidebar_set('#sidebarRightCollapse', data);
          if(!hasactive) {
            card.hide();
            window.history.pushState(moh_id, $('title').html(), '/'+urilocation);
            moh_id='';
            rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
            sidebar_apply(null);
            if(data.length>0) loadMusicData(data[0].id);
          } else {
            loadMusicData(moh_id);
          }
          return false;
        });
      }


      function loadMusicData(id) {
        sendRequest('moh-profile-get', {moh: id}).success(function(data) {
          moh = data;
          card.setValue(data);
          mode.onChange(mode);
          loadDirectory();

          if(data.readonly) {
            card.disable(); 
          } else {
            card.enable();
            directory.disable();
          }
          rightsidebar_activate('#sidebarRightCollapse', id);
          rightsidebar_init('#sidebarRightCollapse', data.readonly?null:sbdel, sbadd, sbselect);
          sidebar_apply(data.readonly?null:sbapply);
          moh_id = data.id;
          card.show();
        });
      }

      function addMusicProfile() {
        moh_id='';
        var tpl_data={id: 'new-profile', title: 'Новый профиль', zones: [], mode: "quietmp3", directory: "moh", digit: "", announcement: "", sort: "", application: "", format: "", kill_escalation_delay: "500", kill_method: "process_group"};
        rightsidebar_activate('#sidebarRightCollapse', null);

        card.setValue(tpl_data);

        card.show();
        sidebar_apply(sbapply);
        rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
      };

      function removeMusicData() {
        showdialog('Удаление канала','Вы уверены что действительно хотите удалить этот класс?',"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            var data = {};
            data.id = moh_id;
            sendRequest('moh-profile-remove', data).success(function(data) {
              moh_id='';
              loadMusicProfiles();
            });
          }
        });
      }


      function sendMusicData() {
        var proceed = false;
        var data = card.getValue();
        data.orig_id = moh_id;
        if(data.id=='') {
          showalert('warning','Не задан идентификатор');
          return false;
        }
        if((data.orig_id!='')&&(data.id!=data.orig_id)) {
          showdialog('Идентификатор абонента изменен','Выберите действие с абонентом:',"warning",['Rename','Copy', 'Cancel'],function(e) {
            if(e=='Rename') {
              proceed=true;
            }
            if(e=='Copy') {
              proceed=true;
              moh_id='';
            }
          });
        } else {
          proceed=true;
        }
        if(proceed) {
          sendRequest('moh-profile-set', data).success(function() {
            moh_id=data.id;
            loadMusicProfiles();
            return true;
          });
        }
      }

      function loadDirectories() {
        sendRequest('get-directories').success(function(data) {
          browseDialog.setValue({directory: {value: data, clean: true}});
          let path = directory.getValue();
          dialogDirectory.tree.select(dialogDirectory.tree.getNodeById(path));
          let id = '';
          let pathparts = path.split('/');
          for(part in pathparts) {
            id += pathparts[part];
            let node = dialogDirectory.tree.getNodeById(id);
            if(node) dialogDirectory.tree.expand(node);
            id += '/';
          }
        });
      }

      function loadDirectory() {
        sendRequest('get-files', {directory: directory.getValue()}).success(function(data) {
          files.setValue({value: data, clean: true, noclean: true});
        });
      }

      function uploadFiles(files) {
        sendRequest('upload-files', {files: files, directory: directory.getValue()}).success(function() { 
            loadDirectory();
            return false; 
          });
      }

      function deleteFile(file) {
        sendRequest('delete-file', {file: file, directory: directory.getValue()}).success(function() { 
            loadDirectory();
            return false; 
          });
      }

      function loadFormats() {
        sendRequest('get-formats').success(function(data) {
          card.setValue({format: {value: data, clean: true}});
        });
      }

      function loadSounds() {
        sendRequest('get-sounds').success(function(sounds) {
          sound_data.splice(0);
          sound_data.push.apply(sound_data, {id: '', text: 'Не указано'});
          sound_data.push.apply(sound_data, sounds);
        });
      }

      $(function () {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
      });

      function sbselect(e, item) {
        loadMusicData(item);
      }

<?php
  if(self::checkPriv('settings_writer')) {
?>

      function sbadd(e) {
        addMusicProfile();
      }

      function sbdel(e) {
        removeMusicData();
      }


<?php
   } else {
      ?>

    var sbapply=null;

<?php
}

?>

      function sbapply(e) {
        sendMusicData();
      }

      $(function () {
        var items=[];
        sidebar_apply(sbapply);

        browseDialog = new widgets.dialog(rootcontent, null, "Выбор директории");
        browseDialog.onOpen = loadDirectories;
        browseDialog.onSave = function(dialog) {
          data = dialog.getValue();
          if(data.directory.length>0) {
            directory.setValue(data.directory[0]);
            loadDirectory();
          } else {
            directory.setValue('');
          }
          return true;
        };

        dialogDirectory = new widgets.tree(browseDialog, {id: 'directory', height: '300px'}, 'Выберите директорию');
        browseDialog.savebtn.setLabel('Выбрать');
        browseDialog.closebtn.setLabel('Отмена');

        card = new widgets.section(rootcontent,null);
        
        obj = new widgets.input(card, {id: 'title'},
            "Отображаемое имя", 
            "Наименование класса, отображаемое в графическом интерфейсе");
        obj = new widgets.input(card, {id: 'id', pattern: '[a-zA-Z0-9_-]+'}, 
            "Наименование класса", 
            "Внутренний идентификатор класса");
        mode = new widgets.select(card, {id: 'mode', value: [{id: 'files', text: 'Файлы'}, {id: 'quietmp3', text: 'MP3 с буферизацией (тихий)'}, {id: 'mp3', text: 'MP3 с буферизацией (громкий)'}, {id: 'quietmp3nb', text: 'MP3 без буферизации (тихий)'}, {id: 'mp3nb', text: 'MP3 без буферизации (громкий)'}, {id: 'custom', text: 'Приложение'}], clean: true, search: false}, 
            "Режим",
            );

        mode.onChange = function(e) { 
          if(e.getValue() == 'custom') {
            application.show();
            directory.hide();
          } else {
            application.hide();
            directory.show();
          }
        };

        directory = new widgets.input(card, {id: 'directory', pattern: '[a-zA-Z0-9_/-]+'}, 
            "Директория воспроизведения");
        obj = new widgets.button(directory.inputdiv, {id: 'browsebtn', class: 'primary'}, _('Обзор...'));
        obj.onClick = function() {
          browseDialog.show();
        }

        application = new widgets.section(card, null);
        obj = new widgets.input(application, {id: 'application', pattern: '[a-zA-Z0-9_/-${}]+'}, 
            "Вызов приложения");
        obj = new widgets.select(application, {id: 'format', value: [], clean: true, search: true}, 
            "Формат файлов");
        obj = new widgets.input(application, {id: 'kill_escalation_delay', pattern: '[0-9]+'}, 
            "Задержка перед отключением сигнала");
        obj = new widgets.select(application, {id: 'kill_method', value: [{id: 'process_group', text: 'Приложение и связанные процессы'}, {id: 'process', text: 'Только приложение'}], clean: true, search: false}, 
            "Получатель сигнала отключения");
            
        obj = new widgets.input(card, {id: 'digit', pattern: '[0-9*#]'}, 
            "Клавиша доступа",
            "Если задано, при прослушивании музыки на удержании абонент может нажать эту клавишу, чтобы переключиться на соотвествующий класс музыки");
        
        announcement = new widgets.select(card, {id: 'announcement', value: sound_data, clean: true, search: true},
            "Звук уведомления",
            "Если задан, проигрывается абоненту при переводе на удержание, а также между треками");        
        obj = new widgets.select(card, {id: 'sort', value: [{id: '', text: 'Без сортировки'},{id: 'random', text: 'Случайный порядок'}, {id: 'alpha', text: 'По алфавиту'}], clean: true, search: false}, 
            "Сортировка"); 
              
        files = new widgets.tree(card, {id: 'filelist', height: '300px', draggable: true}, "Содержимое директории");
        files.onDropFiles = function(sender, droppedFiles) {
          let files = [];
          for(file in droppedFiles) {
            if(droppedFiles[file] instanceof File) files.push(droppedFiles[file]);
          };
          uploadFiles(files);
        }

        files.deletebtn = new widgets.button(files.label, {class: 'danger', icon: 'oi oi-trash'}, '');
        files.deletebtn.hide();
        files.deletebtn.node.style.float = 'right';
        files.deletebtn.onClick = function() {
          showdialog('Удаление файла','Вы уверены что действительно хотите удалить этот файл?',"error",['Yes','No'],function(e) {
            if(e=='Yes') {
              deleteFile(files.getValue()[0]);
            }
          });
        }

        files.playbtn = new widgets.button(files.label, {class: 'success', icon: 'oi oi-media-play'}, '');
        files.playbtn.hide();
        files.playbtn.node.style.float = 'right';
        
        files.onSelect = function(e) { 
          setTimeout(function() {
            if((window.location.origin + '/'+urilocation+'?json=audio&file='+files.getValue()[0]!=audio.getValue())) {
              if(audio.audio.paused) {
                files.playbtn.setValue({icon: 'oi oi-media-play'});
              } else {
                files.playbtn.setValue({icon: 'oi oi-media-stop'});
              };
            } else {
              if(audio.audio.paused) {
                files.playbtn.setValue({icon: 'oi oi-media-play'});
              } else {
                files.playbtn.setValue({icon: 'oi oi-media-pause'});
              };
            }
          }, 100);
          e.playbtn.show();
          e.deletebtn.show();
        };
        files.onUnselect = function(e) { 
          e.playbtn.hide();
          e.deletebtn.hide();
        };

        newfile = new widgets.file(card, {});
        newfile.hide();
        files.uploadbtn = new widgets.button(files.label, {class: 'secondary', icon: 'oi oi-data-transfer-upload'}, '');
        files.uploadbtn.node.style.margin = '0px 10px';
        files.uploadbtn.onClick = function() {
          newfile.enable();
          newfile.input.click();
          newfile.onChange = function (sender){
            if (!sender.getValue() == ''){
              uploadFiles([sender.getValue()]);
            }
          }
        }
        
        audio = new widgets.audio(card, null);
        audio.hide();
        audio.onPlay = function() { files.playbtn.setValue({icon: 'oi oi-media-pause'}); };
        audio.onPause = function() { files.playbtn.setValue({icon: 'oi oi-media-play'}); };

        files.playbtn.onClick = function() { 
          if((window.location.origin + '/'+urilocation+'?json=audio&file='+files.getValue()[0]!=audio.getValue())&&(audio.audio.paused)) {
            audio.setValue('/'+urilocation+'?json=audio&file='+files.getValue()[0]);
          }
          audio.playpause(audio);
        }
        card.hide();
<?php
if(!self::checkPriv('settings_writer')) {
      ?>
    card.disable();
<?php
}
    ?>
        rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
        sidebar_apply(null);
        loadSounds();
        loadFormats();
        loadMusicProfiles();
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