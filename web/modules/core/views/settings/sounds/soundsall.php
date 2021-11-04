<?php

namespace core;

class SoundsAllSettingsView extends \view\View {

  public static function getLocation() {
    return 'settings/sound/all';
  }

  public static function getAPI() {
    return 'sound';
  }

  public static function getViewLocation() {
    return 'sound/all';
  }

  public static function getMenu() {
    return (object) array('name' => 'Общие записи', 'prio' => 2, 'icon' => 'AlbumSharpIcon');
  }

  public static function check() {
    $result = true;
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function implementation() {
    ?>
      <script>
      async function init(parent, data) {

        [this.languages, this.formats, this.options] = await Promise.all([this.asyncRequest('languages'),  this.asyncRequest('formats'), this.asyncRequest('menuItems')]);

        this.lastlanguage = localStorage.getItem('lastlanguage');
        if(!this.lastlanguage) this.lastlanguage = this.languages[0].id;
        this.lastformat = localStorage.getItem('lastformat');
        if(!this.lastformat) this.lastformat = this.formats[0].id;
        
        this.options = this.options.map(this.mapfunc);

        this.files = new widgets.list(parent, {id: 'filelist', options: this.options, lines: 4, tree: true}, _("Содержимое директории"));
        this.files.selfalign = {xs: 12};
        this.files.onChange = this.onFileSelect;

        this.lang = new widgets.select(this.files, {id: 'language', options: []}, _("Язык"));
        this.lang.onChange = this.languageSelect;
        this.lang.hide();
        this.format = new widgets.select(this.files, {id: 'format', options: []}, _("Формат"));
        this.format.onChange = this.formatSelect;
        this.format.hide();

        this.uploadbtn = new widgets.iconbutton(this.files, {color: 'primary', icon: 'CloudUploadSharpIcon'}, null, _("Загрузить"));
        this.uploadbtn.onClick = () => {
          this.newfile.open();
        };

        this.deletebtn = new widgets.iconbutton(this.files, {color: 'error', icon: 'DeleteSharpIcon'}, null, _("Удалить"));
        this.deletebtn.hide();
        this.deletebtn.onClick = this.deleteFile;
        
        this.files.onDropFiles = (sender, files) => {
          if(files.length>1) {
            this.uploaddialog.itemsalign = {xs: 12};
            this.uploadname.hide();
          } else {
            this.uploaddialog.itemsalign = {xs: 12, md: 6};
            let filename = files[0].name.split('.');
            this.uploadname.setValue(filename[0]);
            this.uploadname.show();
          }
          this.uploaddialog.setApply(() => {
            this.uploadFiles(files);
          });
          this.uploaddialog.show();
        };     

        this.uploaddialog = new widgets.dialog(dialogcontent, null, _('Выберите язык аудиозаписи'));
        this.uploadLanguage = new widgets.select(this.uploaddialog, {id: 'uploadLanguage', options: this.languages, value:'ru'});
        this.uploadname = new widgets.input(this.uploaddialog, {id: 'uploadname'}, _("Имя файла"));
        this.newfile = new widgets.file(parent, {});
        this.newfile.hide();
        this.newfile.onChange = (sender) => {
          let files = sender.getValue();
          this.uploaddialog.itemsalign = {xs: 12, md: 6};
          let filename = files[0].name.split('.');
          this.uploadname.setValue(filename[0]);
          this.uploadname.show();
          this.uploaddialog.setApply(() => {
            this.uploadFiles(files);
          });
          this.uploaddialog.show()
        };

        this.recordsection = new widgets.section(null);
        this.recordsection.setParent(this.files);
        await require('waverecorder', this.recordsection, {languages: this.languages});

        // this.recordsection.view.onUpdate = (sender) => {
        //   this.reload();
        // }

        // parent.onResize = () => {
        //   this.recordsection.resize();
        // }

        this.audio = new widgets.audio(this.files, null);
        this.audio.hide();
      }

      function setValue(data) {
      }

      async function reload() {
        this.audio.clear();
        this.options = await this.asyncRequest('menuItems');
        this.options = this.options.map(this.mapfunc);
        this.files.setValue({options: this.options, value: ''});
        this.lang.clear();
        this.lang.hide();
        this.format.clear();
        this.format.hide();
        this.audio.hide();
        this.deletebtn.hide();
        this.newfile.clear();
      }

      function mapfunc (item, index) {
        if(isSet(item.value)) {
          return _extends(item, {icon: 'FolderSharpIcon', value: item.value.map(this.mapfunc)});
        } else {
          return _extends(item, {icon: 'MusicNoteSharpIcon'});
        }
      };

      function languageSelect(sender) {
        this.lastlanguage = sender.getValue();
        localStorage.setItem('lastlanguage', this.lastlanguage);
        this.formatUpdate(this.files.getValue());
      }

      function formatSelect(sender) {
        this.lastformat = sender.getValue();
        localStorage.setItem('lastformat', this.lastformat);
      }
      
      function onFileSelect(e, item){
        this.lastdata = null;
        //let index = this.files.options.indexOfId(item);
        //let value = null;
        // if (index != -1) {
        //   value = this.files.options[index];
        // } else {
        //   let id = item.split('/');
        //   index = this.files.options.indexOfId(id[0]);
        //   let options = this.files.options[index].value;
        //   value = options[options.indexOfId(item)];
        // }
        if (isSet(item)) {
          let lastlanginlist = false;
          if (!isSet(item.value)){
            if (!item.system){
              this.deletebtn.show(); 
            }
            this.sendRequest('get', {id: item.id}).success((data) => {
              this.parent.renderLock();
              this.lastdata = data;
              let languages = [];
              if(isSet(data.languages)){
                for(let language in data.languages) {
                  for(let i in this.languages) {
                    if(this.languages[i].id == language) {
                      languages.push(this.languages[i]);
                    }
                  }
                  if (this.lastlanguage == language) lastlanginlist = true;;
                }
              }
              if (!lastlanginlist) this.lastlanguage = languages[0].id;
              this.lang.setValue({options: languages, value: this.lastlanguage});
              this.lang.show();
              this.formatUpdate(data.id);
              this.parent.renderUnlock();
            });    
          }
        }
      }

      function formatUpdate(item){
        this.parent.renderLock();
        let formats = [];
        let language = this.lang.getValue();
        let lastformatinlist = false;
        if ((isSet(this.lastdata.languages)) && (isSet(this.lastdata.languages[language]))){
          for (let index = 0, len = this.lastdata.languages[language].length; index < len; ++index) {
            for(let j in this.formats) {
              if(this.formats[j].id == this.lastdata.languages[language][index]) { 
                formats.push(this.formats[j]);
              }
            }
            if (this.lastformat == this.lastdata.languages[language][index]) lastformatinlist = true;
          }
        }
        if (!lastformatinlist) this.lastformat = formats[0].id;
        this.format.setValue({options: formats, value: this.lastformat});
        this.format.show();

        sendSingleRequest('stream', {id: item, language: this.lang.getValue(), format: this.format.getValue()}, 'rest/sound').success((data) => {
          this.audio.show();
          this.audio.setValue({value: data, filename: item+'.'+this.lastformat});
        });
        this.parent.renderUnlock();
      }

      async function deleteFile() {
        let modalresult = await showdialog('Удаление файла','Вы уверены что действительно хотите удалить этот файл?',"error",['Yes','No']);
        if(modalresult=='Yes') {
          await this.asyncRequest('delete', {id: this.files.getValue(),language: this.lang.getValue(),format:this.format.getValue()}, 'rest/sound/custom');
          this.reload();  
        }
      }


      function uploadFiles(files) {
        if(files.length == 1) {
          let name = null;
          let filename = files[0].name.split('.');
          if (filename.length == 1) {
            alert('Невозможно получить расширение файла');
            this.uploaddialog.hide();
            return false;
          } else {
            name = this.uploadname.getValue()+'.'+filename[filename.length-1];
          }
          let file = new File([files[0]], name, {type: files[0].type});
          files = [file];
        }
        try {
          this.sendRequest('upload', {files: files, language: this.uploadLanguage.getValue()}, 'rest/sound/custom');
        } catch (e) {
        }
        this.reload();  
        this.uploaddialog.hide();
      }

      </script>;
    <?php
  }

}

?>