<?php

namespace core;

class LicensesSettings extends \view\View {

  public static function getLocation() {
    return 'settings/licenses';
  }

  public static function getAPI() {
    return 'licenses';
  }

  public static function getViewLocation() {
    return 'licenses';
  }

  public static function getMenu() {
    return (object) array('name' => 'Лицензии', 'prio' => 12, 'icon' => 'CardMembershipSharpIcon');
  }

  public static function check($write = false) {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    return $result;
  }

  public function implementation() {
    ?>
    <script>
      function init(parent, data) {
        this.dialog = new widgets.dialog(dialogcontent, {id: 'license-dialog', fullWidth: false, maxWidth: 'md'});
        this.dialog.textlabel = new widgets.label(this.dialog, {asHTML: true}, '');
        this.dialog.textlabel.selfalign = {
          xs: 12,
          style: {
            textAlign: 'justify',
          }
        }
        this.dialog.savebtn = new widgets.button(null, {id: 'savebtn', color: 'secondary'}, _('Принять'));
        this.dialog.buttons.push(this.dialog.savebtn);

        // this.dialog.textarea.container.current.onscroll = scroll => { if(scroll.target.scrollTop == scroll.target.scrollTopMax) this.dialog.savebtn.enable(); }
        this.dialog.savebtn.onClick = this.activate;

        this.licensedialog = new widgets.dialog(dialogcontent, {id: 'activate-license-dialog'});
        this.licensedialog.iframe = new widgets.label(this.licensedialog, {id: 'licenseframe', asHTML: true});
        this.licensedialog.iframe.selfalign = {
          styles: {
            width: '0px',
            height: '0px',
            border: 0,
            opacity: 0,
          }
        }
        this.licensedialog.mainlabel = new widgets.label(this.licensedialog, {id: 'label', expand: true}, '');
        this.licensedialog.timer = null;

        this.dialog.onOpen = dialog => {
          setTimeout(e => {
            dialog.container.current.scrollTop = 0;
            dialog.container.current.onscroll = this.checkLicense;
          }, 300);
        };

        this.licenses = new widgets.table(parent, {id: 'licenses', expand: true, sorted: true, head: {title: _('Модуль'), company: _('Владелец'), validto: _('Действительна до'), controls: ''}, value: [], clean: true}, _('Доступные лицензии'));
        this.licenses.selfalign = {xs: 12};
        this.licenses.disableCellSort('controls');
        this.licenses.setHeadWidth('controls', '120px');
        this.licenses.setCellFilter('validto', this.filterValidTo);
        this.licenses.setCellFilter('controls', this.filterControls);
        this.licenses.setCellControl('controls', [
          new widgets.iconbutton(null, {id: 'reset', color: 'default', onClick: this.revoke, icon: 'SettingsBackupRestoreSharpIcon'}, null, _('Сбросить лицензию')),
          new widgets.iconbutton(null, {id: 'agreement', color: 'primary', onClick: this.showLicense, icon: 'AssignmentSharpIcon'}, null, _('Просмотр лицензионного соглашения')),
        ]);
        window.addEventListener("message", this.acceptLicense, false);
      }

      function acceptLicense(event) {
        if(event.origin == 'https://cert.oas.su') {
          setTimeout(e => {this.licensedialog.hide()}, 300);
          let answer=event.data;
          if(this.licensedialog.timer) {
            clearTimeout(this.licensedialog.timer);
            this.licensedialog.timer = null;
            switch(answer) {
              case "У вас отсутствуют права для получения лицензии": 
              case "Не передан запрос на сертификат": 
              case "Не удается найти сертификат на сервере":
              case "У вас отсутствуют доступные лицензии": {
                showalert('danger', _(answer));
              } break;
              case "Сертификат отозван": {
                this.sendRequest('remove', {codename: this.dialog.id}).success(function(data) {
                  showalert('success','Лицензия успешно аннулирована');
                  this.load();
                });
              } break;
              default: {
                this.sendRequest('upload', {codename: this.dialog.id, data: answer}).success(function() {
                  showalert('success','Лицензия успешно активирована');
                  this.load();
                  return false;
                });
              }
            }
          }
        }
      }

      function licenseTimeout() {
        this.licensedialog.timer = null;
        showalert('danger', _('Запрос лицензии не может быть совершен, проверьте подключение к серверу лицензирования или повторите попытку позднее.'));
      }

      function filterValidTo(sender, value) {
        if(value == null) return _('Не активна');
        value = getDateTime(new Date(value));
        if(value == '31.12.9999 00:00:00') value = _('Бессрочная лицензия');
        return value;
      }

      function filterControls(sender, value, data, controls) {
        if(data.valid) {
          controls[controls.indexOfId('reset')].show();
          return {agreement: {icon: 'AssignmentTurnedInSharpIcon'}};
        } else {
          controls[controls.indexOfId('reset')].hide();
          return {agreement: {icon: 'AssignmentSharpIcon'}};
        }
      }

      function checkLicense(event) {
        if(event.originalTarget.scrollTop==event.originalTarget.scrollTopMax) {
          this.dialog.savebtn.enable();
          this.dialog.redraw();
        }
      }

      function showLicense(button, entry) {
        this.dialog.textlabel.setLabel(entry.item.agreement);
        this.dialog.id = entry.item.id;
        // this.dialog.savebtn.disable();
        this.dialog.setLabel(entry.item.title);
        if(entry.item.valid) {
           this.dialog.savebtn.hide();
        } else {
          this.dialog.savebtn.show();
          this.dialog.savebtn.disable();
        }
        this.dialog.show();
      }

      function activate(sender) {
        this.sendRequest('csr', {codename: this.dialog.id}).success(function(data) {
          this.dialog.hide();
          this.licensedialog.mainlabel.setLabel(_('Пожалуйста, подождите пока идет запрос на выдачу лицензии'));
          this.licensedialog.timer = setTimeout(this.licenseTimeout, 20000);
          this.licensedialog.show();
          this.licensedialog.iframe.setLabel("<iframe style='width: 0px; height: 0px; border: none;' src='https://cert.oas.su/license?embed=true&req="+data+"'>");
        }.bind(this));
      }

      function revoke(sender, entry) {
        this.dialog.id = entry.item.id;
        this.licensedialog.mainlabel.setLabel(_('Пожалуйста, подождите пока идет аннулирование лицензии'));
        this.licensedialog.timer = setTimeout(this.licenseTimeout, 20000);
        this.licensedialog.show();
        this.licensedialog.iframe.setLabel("<iframe style='width: 0px; height: 0px; border: none;' src='https://cert.oas.su/license?embed=true&revoke="+entry.item.diskserial+"&product="+entry.item.id+"'>");
      }
    </script>
    <?php
  }

}

?>
