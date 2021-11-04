<?php

namespace core;

class GrammarSettings extends \view\Collection {
  
  public static function getLocation() {
    return 'settings/grammars';
  }

  public static function getAPI() {
    return 'grammars';
  }

  public static function getViewLocation() {
    return 'grammars';
  }

  public static function getMenu() {
    return (object) array('name' => 'Грамматики', 'prio' => 4, 'icon' => 'ReceiptSharpIcon');
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
        
        this.title = new widgets.input(parent, {id: 'title', expand: true}, "Имя");
        this.title.selfalign = {xs: 12};
        this.newfile = new widgets.file(parent, {accept: 'application/jsgf'});
        this.newfile.hide();
        this.importbtn = new widgets.iconbutton(this.title, {id: 'importbtn', color: 'default', icon: 'CloudUploadSharpIcon'}, "Импорт");
        this.importbtn.onClick = () => {
          this.newfile.enable();
          this.newfile.open();
          this.newfile.onChange = (sender) => {
            if(!sender.getValue() == '') {
              this.importGrammar(this.importbtn, sender.getValue());
            }
          }
        }
        this.exportbtn = new widgets.iconbutton(this.title, {id: 'exportbtn', color: 'default', icon: 'CloudDownloadSharpIcon'}, "Экспорт");
        this.exportbtn.onClick = this.exportGrammar;
        //this.id = new widgets.input(parent, {id: 'id'}, "Внутренний идентификатор грамматики");
        this.grams = new widgets.collection(parent, {id: 'grams', data: {keytext: _('Грамма'), valuetext: _('Фраза')}, options: [true], value: [], select: 'keyvalue/multipleentry', entry: 'keyvalue/multipleentry'}, _("Грамматики"));
        this.grams.selfalign = {xs: 12};
        // this.grams = new widgets.keyvaluelist(parent, {id: 'grams', keypattern: /[a-z0-9]*/, valuefilter: this.valuefilter, value: [], multiple: true, expand: true}, _("Грамматики"));  

        this.hasAdd = true;
        this.hasSave = true;
      }

      function add() {
        this.setValue({
          id: 0,
          title: _('Новая грамматика'),
          grams: [],
          readonly: false,
        });
        return true;
      }

      function setValue(data) {
        super.setValue(data);
        if(data.readonly) this.parent.disable(); else this.parent.enable();
      }

      function getValue() {
        let result = this.parent.getValue();
        result.id = this.data.id;
        return result;
      }

      async function add() {
        this.setValue({id: false, title: _('Новая грамматика'), grams: []});
        setCurrentID(null);
      };

      async function remove(id, title) {
        let modalresult = await showdialog(_('Удаление грамматики'),_('Вы уверены что действительно хотите удалить грамматику <b>\"{0}\"</b>?').format(title),"error",['Yes','No']);
        if(modalresult=='Yes') {
          return await super.remove(id, title);
        }
        return false;
      }

      async function importGrammar(sender, data) {
        await this.asyncRequest('import', {id: this.data.id, file: data});
        await this.load(this.data.id);
      }
      
      function exportGrammar(sender) {
        window.location = '/rest/grammars?json=export&id='+this.data.id;
      }

      function valuefilter(sender, value) {       
        let arr = [...value.matchAll('(\\[[^\\(\\]]*\\]|\\([^\\(\\]]*\\)|[^\\[\\(]+)+')];
        if(arr.length==0) return true;
        return arr[0][0]==value;
      }

      </script>
    <?php
  }

}

?>