<?php

namespace core;

class MusicOnHoldSettings extends \view\Collection {

  public static function getLocation() {
    return 'settings/general/musiconhold';
  }

  public static function getAPI() {
    return 'general/musiconhold';
  }

  public static function getViewLocation() {
    return 'general/musiconhold';
  }

  public static function getMenu() {
    return (object) array('name' => 'Музыка на удержании', 'prio' => 3, 'icon' => 'QueueMusicSharpIcon', 'mode' => 'advanced'); //oi oi-headphones
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function implementation() {
    ?>
      <script> 

      async function init(parent, data) {

        this.id = null;

        this.browseDialog = new widgets.dialog(dialogcontent, null, _("Выбор директории"));
        this.browseDialog.onOpen = this.loadDirectories;
        this.browseDialog.applyfunc = (dialog) => {
          data = this.browseDialog.getValue();
          if(data.directory) {
            this.directory.setValue(data.directory);
            this.loadDirectory();
          } else {
            this.directory.setValue('');
          }
          this.browseDialog.hide();
          return true;
        };

        this.dialogDirectory = new widgets.list(this.browseDialog, {id: 'directory', lines: 5, tree: true}, _('Выберите директорию'));
        this.dialogDirectory.selfalign = {xs: 12};

        this.title = new widgets.input(parent, {id: 'title'}, _("Отображаемое имя"),  _("Наименование класса, отображаемое в графическом интерфейсе"));
        //this.id = new widgets.input(parent, {id: 'id', pattern: /[a-zA-Z0-9_-]+/},  _("Наименование класса"),  _("Внутренний идентификатор класса"));
        this.mode = new widgets.select(parent, {id: 'mode', options: [{id: 'files', title: _('Файлы')}, {id: 'quietmp3', title: _('MP3 с буферизацией (тихий)')}, {id: 'mp3', title: _('MP3 с буферизацией (громкий)')}, {id: 'quietmp3nb', title: _('MP3 без буферизации (тихий)')}, {id: 'mp3nb', title: _('MP3 без буферизации (громкий)')}, {id: 'custom', title: _('Приложение')}]}, _("Режим"));

        this.mode.onChange = (e) => { 
          if(e.getValue() == 'custom') {
            this.applicationsettings.show();
            this.directory.hide();
          } else {
            this.applicationsettings.hide();
            this.directory.show();
          }
        };

        this.directory = new widgets.input(parent, {id: 'directory', pattern: /[a-zA-Z0-9_/-]+/}, _("Директория воспроизведения"));
        this.browsebtn = new widgets.iconbutton(this.directory, {id: 'browsebtn', icon: 'FolderSharpIcon', color: 'default'});
        this.browsebtn.onClick = () => {
          this.browseDialog.show();
        };

        this.sort = new widgets.select(parent, {id: 'sort', options: [{id: '', title: _('Без сортировки')},{id: 'random', title: _('Случайный порядок')}, {id: 'alpha', title: _('По алфавиту')}], clean: true, search: false}, _("Сортировка")); 

        [this.formats, this.sounds] = await Promise.all([this.asyncRequest('formats', null, 'rest/sound'),  this.asyncRequest('get-sounds')]);

        this.applicationsettings = new widgets.section(parent, null);
        this.application = new widgets.input(this.applicationsettings, {id: 'application', pattern: /[a-zA-Z0-9_/${}-]+/}, _("Вызов приложения"));
        this.format = new widgets.select(this.applicationsettings, {id: 'format', options: this.formats, clean: true, search: false}, _("Формат файлов"));
        this.killescalationdelay = new widgets.input(this.applicationsettings, {id: 'kill_escalation_delay', prefix: _("миллисекунд"), pattern: /[0-9]+/}, _("Задержка перед отключением сигнала"));
        this.killmethod = new widgets.select(this.applicationsettings, {id: 'kill_method', options: [{id: 'process_group', title: _('Приложение и связанные процессы')}, {id: 'process', title: _('Только приложение')}], clean: true, search: false}, _("Получатель сигнала отключения")); 

        this.digit = new widgets.input(parent, {id: 'digit', pattern: /[0-9*#]/}, _("Клавиша доступа"), _("Если задано, при прослушивании музыки на удержании абонент может нажать эту клавишу, чтобы переключиться на соотвествующий класс музыки"));       
        this.announcement = new widgets.select(parent, {id: 'announcement', options: this.sounds, value: 'beep', readonly: false}, _("Звук уведомления"), _("Если задан, проигрывается абоненту при переводе на удержание, а также между треками"));        
              
        this.files = new widgets.list(parent, {id: 'filelist', lines: 4, draggable: true, tree: true}, _("Содержимое директории"));
        this.files.selfalign = {xs: 12};
        this.files.onDropFiles = (sender, droppedFiles) => {
          let files = [];
          for(let file in droppedFiles) {
            if(droppedFiles[file] instanceof File) files.push(droppedFiles[file]);
          };
          this.uploadFiles(files);
        };
        this.files.onChange = (sender, item) => {
          this.lastdata = null;
          if (!isSet(item.value)){
            sendSingleRequest('audio', {file: item.id}, this.__proto__.constructor.defaultapi).success((data) => {
              this.audio.show();
              this.audio.setValue({value: data, filename: item.id});
            });
            this.deletebtn.show();
          }
        }
        
        this.audio = new widgets.audio(this.files, null);
        this.audio.hide();

        this.uploadbtn = new widgets.iconbutton(this.files, {color: 'primary', icon: 'CloudUploadSharpIcon'});
        this.uploadbtn.onClick = () => {
          this.newfile.enable();
          this.newfile.onChange = (sender) => {
            if (!sender.getValue() == ''){
              this.uploadFiles([sender.getValue()]);
            }
          };
          this.newfile.open();
        };

        this.deletebtn = new widgets.iconbutton(this.files, {color: 'error', icon: 'DeleteSharpIcon'});
        this.deletebtn.hide();
        this.deletebtn.onClick = this.deleteFile;

        this.newfile = new widgets.file(parent, {});
        this.newfile.hide();     

        this.hasAdd = true;
        this.hasSave = true;
      }

      function setValue(data) {
        if(!isSet(data.id)) data.id = null;
        if(!isSet(this.data)) this.data = {}
        this.parent.setValue(data);
        this.mode.onChange(this.mode);
        if (data.directory){
          this.loadDirectory();
        }
        this.data = data;
      }

      function setMode(mode) {
        this.parent.renderLock();
        switch(mode) {
          case 'basic': {
            this.digit.hide();
            this.announcement.hide();
          } break;
          default: {
            this.digit.show();
            this.announcement.show();
          }
        }
        this.parent.renderUnlock();
        this.parent.redraw();
      }

      function getValue() {
        let result = rootcontent.getValue();
        result.id = this.data.id;
        return result;
      }

      async function add() {
        this.setValue({id: false, title: _('Новый профиль'), zones: [], mode: "quietmp3", directory: "moh", digit: "", announcement: "", sort: "", applicationsettings: "", format: "", kill_escalation_delay: "500", kill_method: "process_group"});
        setCurrentID(null);
        return true;
      };

      async function remove(id, title) {
        let modalresult = await showdialog(_('Удаление канала'),_('Вы уверены что действительно хотите удалить класс <b>"{0}"</b>?').format(title),"error",['Yes','No']);
        if(modalresult=='Yes') {
          return await super.remove(id, title);
        }
        return false
      }

      function loadDirectories() {
        this.sendRequest('get-directories').success((data) => {
          let path = this.directory.getValue();
          this.browseDialog.setValue({directory: {options: data, value: path}});
        });
      }

      async function loadDirectory() {
        let data = await this.asyncRequest('get-files', {directory: this.directory.getValue()});
        this.files.setValue({options: data, clean: true, noclean: true});
      }

      async function uploadFiles(files) {
        try {
          await this.asyncRequest('upload-files', {files: files, directory: this.directory.getValue()});
        } catch (e) {
        }
        this.loadDirectory();
      }

      async function deleteFile() {
        let modalresult = await showdialog(_('Удаление файла'),_('Вы уверены что действительно хотите удалить этот файл?'),"error",['Yes','No']);
        if(modalresult=='Yes') {
          await this.asyncRequest('delete-file', {file: this.files.getValue(), directory: this.directory.getValue()});
          this.loadDirectory();
        }
      }

      </script>
    <?php
  }
}

?>