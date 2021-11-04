<?php

namespace core;

class NumberFormatViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'numberformat';
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    return $result;
  }

  public function implementation() {
    ?>
      <script>
      async function init(parent, data) {
        if(!isSet(data)) data = {};

        this.formats = [
          {id: 'itut', title: _('ITU-T (+CXXXXYYYYYY)')},
          {id: 'leadingzero', title: _('Совместимый (00CXXXXYYYYYY)')},
          {id: 'russianeight', title: _('Страны СНГ (8XXXXYYYYYY)')},
          {id: 'custom', title: _('Задать вручную')}
        ];

        this.format = new widgets.select(parent, {id: 'format', clean: true, search: false, value: this.formats}, _('Формат номера'), _('Укажите формат номера используемый оператором связи'));

        this.cut = new widgets.input(parent, {id: 'cut', pattern: /[0-9]+/, placeholder: '1'}, _('Вырезать N символов'), _('Указывает количество знаков, удаляемых с начала номера в формате ITU-T'));

        this.add = new widgets.input(parent, {id: 'add', pattern: /[+]|[+][0-9]+|[0-9]+/, placeholder: '8'}, _('Дополнить номер'), _('Указывает знаки которыми нужно дополнить номер'));

        this.samplestring = _('Пример: {0} ←→ {1}');

        this.sample = new widgets.label(parent, null, '');

        this.format.onChange = this.onFormatSelect;

        this.cut.onInput = this.onCustomChange;

        this.add.onInput = this.onCustomChange;
      }

      function setValue(data) {
        let format = 'itut';
        if(!isSet(data.format)) {
          this.formats.forEach(f => { if(isSet(data[f.id])) format = f.id; });
        }
        let newdata = {};
        if(format == 'custom') {
          newdata.cut = data.custom.cut;
          newdata.add = data.custom.add;
        }
        newdata.format = format;
        this.parent.setValue(newdata);
        this.onFormatSelect(this.format);
      }

      function getValue() {
        let result = {};
        let format = this.format.getValue();
        if(format == 'custom') {
          result.custom = {
            cut: this.cut.getValue(),
            add: this.add.getValue()
          };
        } else {
          result[format] = true;
        }
        return result;
      }

      function onFormatSelect(sender) {
        if(sender.getValue() == 'custom') {
          this.cut.show();
          this.add.show();
          this.sample.show();
          this.onCustomChange(this.cut);
        } else {
          this.cut.hide();
          this.add.hide();
          this.sample.hide();
        }
      }

      function onCustomChange(sender) {
        let cut = this.cut.getValue();
        let add = this.add.getValue();
        let string = _('+78001234567');
        this.sample.setLabel(this.samplestring.format(string, add+string.substring(cut)));
      }
      </script>
    <?php
  }
}