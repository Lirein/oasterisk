<?php

namespace core;

class WaveRecorder extends \view\ViewPort {

  public static function getViewLocation() {
    return 'waverecorder';
  }

  public static function check() {
    return true;
  }

  public function implementation() {
    ?>
      <script>
      async function init(parent, data) {
        this.uri = 'rest/sound/custom';
        this.onUpdate = null;
        this.onError = null;
        this.record = new widgets.iconbutton(parent, {color: 'primary', icon: 'MicIcon'}), null, _("Начать запись");
        this.record.onClick = async function() {
          if(await this.recorder.start()) {
            this.record.hide();
            this.progress.show();
            this.stoprecord.show();
            this.audio.hide();
            this.saveRecord.hide();
          } else {
            alert('Невозможно получить доступ к микрофону');
          }
        }.bind(this);
        
        this.stoprecord = new widgets.iconbutton(parent, {color: 'primary', icon: 'StopIcon'});
        this.stoprecord.onClick = async function() {
          this.progress.hide();
          await this.recorder.stop();
          this.record.show();
          this.stoprecord.hide();
          this.saveRecord.show();
          this.progress.setValue(0);
          this.audio.setValue({value: this.recorder.getValue()});
          this.audio.show();
        }.bind(this);
        this.stoprecord.hide();

        this.audio = new widgets.audio(parent, null,  _('Послушать запись'));
        this.audio.hide();
        
        this.saveRecord = new widgets.button(parent, {class: 'secondary'}, _('Сохранить запись'));
        this.saveRecord.onClick = function() {
          this.clipSavedialog.show();
        }.bind(this);   
        this.saveRecord.hide();

        this.recordsection = new widgets.section(null);
        this.recordsection.setParent(parent);
        
        this.recorder = await require('streamrecorder', this.recordsection);
        // this.recorder.onError = (sender, err) => {
        //   if(this.onError) this.onError(this, err);
        // };

        this.progress = new widgets.volumemeter(parent, {value: 0});

        this.recorder.onVisual = (sender, value) => {
          this.progress.setValue(value);
        };
        this.progress.hide();
      
        this.clipSavedialog = new widgets.dialog(dialogcontent, null, _('Сохранение записи'));
        this.clipName = new widgets.input(this.clipSavedialog, {id: 'clipName'}, _("Введите название"));
        this.clipLanguage = new widgets.select(this.clipSavedialog, {id: 'clipLanguage', options: data.languages, value:'ru'}, _("Язык"));

        this.clipSavedialog.applyfunc = function() {
          this.sendRequest('record', {name: this.clipName.getValue(), language: this.clipLanguage.getValue(), file: this.recorder.getValue()}, this.uri).success(function() { 
            if(this.onUpdate) this.onUpdate(this);
            this.clipSavedialog.hide();
          }.bind(this));   
        }.bind(this);  
      }

      function setValue(data) {
        if(isSet(data) && isSet(data.uri)) {
          this.uri = data.uri;
        }
      }

      function getValue() {
        return null;
      }


      </script>
    <?php
  }
}