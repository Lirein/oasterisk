<?php

namespace core;

class AppearenceSettings extends \view\View {
  
  public static function getLocation() {
    return 'settings/general/appearence';
  }

  public static function getAPI() {
    return 'general/appearence';
  }

  public static function getViewLocation() {
    return 'general/appearence';
  }

  public static function getMenu() {
    return (object) array('name' => 'Внешний вид', 'prio' => 3, 'icon' => 'StyleSharpIcon', 'mode' => 'advanced');
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
        
        this.currentselection = new widgets.select(parent, {options: [
          {id: 'system', title: _('Общесистемные')},
          {id: 'group', title: _('Группа безопасности')},
          {id: 'user', title: _('Учетная запись')}
        ], multiple: true, value: []}, _("Настроенные стили"));
        this.currentselection.disable();

        this.list = new widgets.select(parent, {id: 'savetype', options: [], minlines: 0, value:'user'}, _('Настраиваемый стиль'));
        this.list.onChange = (e) => {
          this.setcolors(e.getValue());
        };

        this.colorscheme = new widgets.toggle(parent, {value: true}, _('Тёмная тема настроек'), _('Переключатель режима просмотра темы оформления'));
        this.colorscheme.selfalign = {xs: 12};
        this.colorscheme.onChange = this.pickerchange;

        this.templates = new widgets.select(parent, {id: 'templates', options: await this.asyncRequest('templates'), search: false}, _("Тема оформления"));
        // this.templates.hide();
        this.colorpads = new widgets.section(parent, null);   
        this.colorpads.itemsalign = {xs: 12, sm: 6, md: 4, lg: 6, xl: 4};        
        this.colorpads.cPrimary = new widgets.colorpicker(this.colorpads, {id: 'primary', default: "#689f38"}, 'Основной');
        this.colorpads.cSecondary = new widgets.colorpicker(this.colorpads, {id: 'secondary', default: "#4e342e"}, 'Дополнительный');
        this.colorpads.cSuccess = new widgets.colorpicker(this.colorpads, {id: 'success', default: "#4caf50"}, 'Успешно');
        this.colorpads.cInfo = new widgets.colorpicker(this.colorpads, {id: 'info', default: "#2196f3"}, 'Информация');
        this.colorpads.cWarning = new widgets.colorpicker(this.colorpads, {id: 'warning', default: "#ff9800"}, 'Внимание');
        this.colorpads.cError = new widgets.colorpicker(this.colorpads, {id: 'error', default: "#f44336"}, 'Ошибка');

        this.colorpads.cPrimary.onChange=this.pickerchange;
        this.colorpads.cSecondary.onChange=this.pickerchange;
        this.colorpads.cSuccess.onChange=this.pickerchange;
        this.colorpads.cInfo.onChange=this.pickerchange;
        this.colorpads.cWarning.onChange=this.pickerchange;
        this.colorpads.cError.onChange=this.pickerchange;

        this.preview = new widgets.section(parent, null);
        this.preview.list = new widgets.list(this.preview, {options: [
          //{title: "Тестовый элемент списка"},
          {color: 'primary', title: "Тестовый элемент списка"},
          {color: 'secondary', title: "Тестовый элемент списка"},
          {color: 'success', title: "Тестовый элемент списка"},
          {color: 'info', title: "Тестовый элемент списка"},
          {color: 'warning', title: "Тестовый элемент списка"},
          {color: 'error', title: "Тестовый элемент списка"},
        ]});        

        this.previewbuttons = new widgets.section(this.preview);
        this.previewbuttons.itemsalign = {xs: 4, xl: 2};

        this.preview.primaryBtn = new widgets.button(this.previewbuttons, {color: 'primary'}, 'Кнопка');
        this.preview.secondaryBtn = new widgets.button(this.previewbuttons, {color: 'secondary'}, 'Кнопка');
        this.preview.successBtn = new widgets.button(this.previewbuttons, {color: 'success'}, 'Кнопка');
        this.preview.infoBtn = new widgets.button(this.previewbuttons, {color: 'info'}, 'Кнопка');
        this.preview.warningBtn = new widgets.button(this.previewbuttons, {color: 'warning'}, 'Кнопка');
        this.preview.errorBtn = new widgets.button(this.previewbuttons, {color: 'error'}, 'Кнопка');

        this.onReset = this.reset;

        this.hasSave = true;
      };

      function pickerchange(sender) {
        colorscheme = createMuiTheme({
          palette: {
            primary: {
              main: this.colorscheme.getValue()?((this.colorpads.cSecondary.getValue()==null)?'#ffffff':this.colorpads.cSecondary.getValue()):((this.colorpads.cPrimary.getValue()==null)?'#ffffff':this.colorpads.cPrimary.getValue()),
            },
            secondary: {
              main: this.colorscheme.getValue()?((this.colorpads.cPrimary.getValue()==null)?'#ffffff':this.colorpads.cPrimary.getValue()):((this.colorpads.cSecondary.getValue()==null)?'#ffffff':this.colorpads.cSecondary.getValue()),
            },
            success: {
              main: (this.colorpads.cSuccess.getValue()==null)?'#ffffff':this.colorpads.cSuccess.getValue(),
            },
            info: {
              main: (this.colorpads.cInfo.getValue()==null)?'#ffffff':this.colorpads.cInfo.getValue(),
            },
            warning: {
              main: (this.colorpads.cWarning.getValue()==null)?'#ffffff':this.colorpads.cWarning.getValue(),
            },
            error: {
              main: (this.colorpads.cError.getValue()==null)?'#ffffff':this.colorpads.cError.getValue(),
            }
          },
        });
        this.parent.render();
        appbar.render();
        appbar.mainmenu.render();
      } 

      function setValue(data) {
        this.data = data;
        if(isEmpty(data)) return;
        this.parent.renderLock();
        let listdata = [{id: _("user"), title: _("Настройки пользователя")}];
        if(data.savegroup) listdata.push({id: _("group"), title: _("Настройки группы безопасности")});
        if(data.savesystem) listdata.push({id: _("system"), title: _("Глобальные настройки")});
        this.list.setValue({options: listdata, value: 'user'});

        this.colorscheme.setValue(true);

        let modeoptions = [];
        if(isSet(data.user)) modeoptions.push('user');
        if(isSet(data.group)) modeoptions.push('group');
        if(isSet(data.system)) modeoptions.push('system');
        this.currentselection.setValue({value: modeoptions});
        this.currentselection.disable();

        this.setcolors(this.list.getValue());

        this.parent.renderUnlock();
        this.parent.redraw();
      }

      function setcolors(mode) {
        this.parent.renderLock();
        if(isSet(this.data[mode])) {
          if(this.data[mode].primary=='#ffffff') {
            this.colorpads.cPrimary.value = null;
            this.colorpads.cPrimary.color = null;
          } else {
            this.colorpads.cPrimary.setValue(this.data[mode].primary);
          }
          if(this.data[mode].secondary=='#ffffff') {
            this.colorpads.cSecondary.value = null;
            this.colorpads.cSecondary.color = null;
          } else {
            this.colorpads.cSecondary.setValue(this.data[mode].secondary);
          }
          if(this.data[mode].info=='#ffffff') {
            this.colorpads.cInfo.value = null;
            this.colorpads.cInfo.color = null;
          } else {
            this.colorpads.cInfo.setValue(this.data[mode].info);
          }
          if(this.data[mode].success=='#ffffff') {
            this.colorpads.cSuccess.value = null;
            this.colorpads.cSuccess.color = null;
          } else {
            this.colorpads.cSuccess.setValue(this.data[mode].success);
          }
          if(this.data[mode].warning=='#ffffff') {
            this.colorpads.cWarning.value = null;
            this.colorpads.cWarning.color = null;
          } else {
            this.colorpads.cWarning.setValue(this.data[mode].warning);
          }
          if(this.data[mode].error=='#ffffff') {
            this.colorpads.cError.value = null;
            this.colorpads.cError.color = null;
          } else {
            this.colorpads.cError.setValue(this.data[mode].error);
          }
        } else {
          this.colorpads.cPrimary.setValue(this.data.defaults.primary);
          this.colorpads.cSecondary.setValue(this.data.defaults.secondary);
          this.colorpads.cInfo.setValue(this.data.defaults.info);
          this.colorpads.cSuccess.setValue(this.data.defaults.success);
          this.colorpads.cWarning.setValue(this.data.defaults.warning);
          this.colorpads.cError.setValue(this.data.defaults.error);
        }
        this.parent.renderUnlock();
        this.parent.redraw();
      }

      function getValue() {
        let data = {};
        data.primary = this.colorpads.cPrimary.getValue();
        data.secondary = this.colorpads.cSecondary.getValue();
        data.success = this.colorpads.cSuccess.getValue();
        data.info = this.colorpads.cInfo.getValue();
        data.warning = this.colorpads.cWarning.getValue();
        data.error = this.colorpads.cError.getValue();
        data.savetype = this.list.getValue();
        return data;
      }

      async function send () {
        super.send();
        this.load();
        appbar.render();
        appbar.mainmenu.render();
      }

      function reset(){
        super.reset();
        this.pickerchange();
      }
      
    </script>
    <?php
}

}

?>