<?php

namespace core;

class CDRSettingsViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'cdr/settings';
  }

  public static function getAPI() {
    return 'logs/cdr';
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
        parent.itemsalign = {xs: 12, lg: 6};
        this.enable = new widgets.checkbox(parent, {id: 'enable', value: false}, _("Включить журналирование деталей вызовов"));
        this.unanswered = new widgets.checkbox(parent, {id: 'unanswered', value: false}, _("Журналировать неотвеченные вызовы"));
        this.congestion = new widgets.checkbox(parent, {id: 'congestion', value: false}, _("Журналировать вызовы отвергнутые из-за перегрузки каналов"), _("Setting this to yes will report each call that fails to complete due to congestion conditions"));
        this.endbeforehexten = new widgets.checkbox(parent, {id: 'endbeforehexten', value: false}, _("Закрывать вывод деталей вызовов до запуска расширения h в диалплане"), _("Обычно запись деталей вызовов не прекращается пока диалплан полностью не завершит работу"));
        this.initiatedseconds = new widgets.checkbox(parent, {id: 'initiatedseconds', value: false}, _("При вычислении поля 'billsec' использовать точные значения (до микросекунд)"), _("По-умолчанию, округляется вверх. Это помогает гарантировать поведение CDR Asterisk аналогичному поведению телекоммуникационных компаний"));
        this.batch = new widgets.checkbox(parent, {id: 'batch', value: false}, _("Сохранять детали вызовов в очередь и журналировать пакетами"), _("Снижает нагрузку на ядро технологической платформы. Внимание: может привести к потере данных при небезопасном завершении работы"));
        this.size = new widgets.input(parent, {id: 'size'}, _("Максимальное число записей в одном пакете")); 
        this.time = new widgets.input(parent, {id: 'time'}, _("Максимальное количество секунд между пакетами"), _("Пакет записей будет журналирован по завершению этого периода времени, даже если размер не был достигнут")); 
        this.scheduleronly = new widgets.checkbox(parent, {id: 'scheduleronly', value: false}, _("Загружать пакеты в едином потоке планировщика"));
        this.safeshutdown = new widgets.checkbox(parent, {id: 'safeshutdown', value: false}, _("Блокировать выключение, пока данные не сохранены"));
        this.activeengines = new widgets.list(parent, {id: 'activeengines', options: await this.asyncRequest('get-modules', {id: 'settings'}), checkbox: true, sorted: true}, _("Движки"));
        this.hasSave = true;
        this.onReset = this.reset;
      }

      function reset() {
        this.setValue({
                        "id": "settings",
                        "enable": true,
                        "unanswered": true,
                        "congestion": false,
                        "endbeforehexten": false,
                        "initiatedseconds": false,
                        "batch": false,
                        "size": "100",
                        "time": "300",
                        "scheduleronly": false,
                        "safeshutdown": true
                      });
      }
      
    </script>
    <?php
  }

}

?>
