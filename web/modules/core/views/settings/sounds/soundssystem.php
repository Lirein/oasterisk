<?php

namespace core;

class SoundsSystemSettingsView extends \view\View {

  public static function getLocation() {
    return 'settings/sound/system';
  }

  public static function getAPI() {
    return 'sound/system';
  }

  public static function getViewLocation() {
    return 'sound/system';
  }

  public static function getMenu() {
    return (object) array('name' => 'Системные', 'prio' => 1, 'icon' => 'LibraryMusicSharpIcon');
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
     
        [this.languages, this.formats, this.options] = await Promise.all([this.asyncRequest('languages', {}, 'rest/sound'), this.asyncRequest('formats', {}, 'rest/sound'), this.asyncRequest('menuItems')]);
        this.lastlanguage = localStorage.getItem('lastlanguage');
        if(!this.lastlanguage) this.lastlanguage = this.languages[0].id;
        this.lastformat = localStorage.getItem('lastformat');
        if(!this.lastformat) this.lastformat = this.formats[0].id;
        this.options = this.options.map(this.mapfunc);

        this.files = new widgets.list(parent, {id: 'filelist', options: this.options, lines: 4, tree: true}, _("Список аудиофайлов"));
        this.files.selfalign = {xs: 12};

        this.files.onChange = this.onFileSelect;

        this.lang = new widgets.select(this.files, {id: 'language', options: []}, _("Язык"));
        this.lang.onChange = this.languageSelect;
        this.lang.hide();
        this.format = new widgets.select(this.files, {id: 'format', options: []}, _("Формат"));
        this.format.onChange = this.formatSelect;
        this.format.hide();

        this.audio = new widgets.audio(this.files, null);
        this.audio.hide();
      }

      function setValue(data) {
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
      
      function onFileSelect(e, item) {
        this.lastdata = null;
        if (isSet(item)) {
          let lastlanginlist = false;
          if (!isSet(item.value)){
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

      </script>;
    <?php
  }

}

?>