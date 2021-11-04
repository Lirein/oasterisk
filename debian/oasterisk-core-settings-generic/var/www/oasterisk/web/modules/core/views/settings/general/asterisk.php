<?php

namespace core;

class AsteriskCoreSettings extends ViewModule {
  private static $interfacedir = '/sys/class/net';
  
  public static function getLocation() {
    return 'settings/general/core';
  }

  public static function getMenu() {
    return (object) array('name' => 'Параметры ядра', 'prio' => 1, 'icon' => 'oi oi-target');
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
    $this->ami->send_request('Command', array('Command' => 'core reload'));
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    static $generalparams = '{
      "verbose": "0",
      "debug": "0",
      "alwaysfork": "!no",
      "nofork": "!no",
      "quiet": "!no",
      "timestamp": "!no",
      "execincludes": "!no",
      "console": "!no",
      "highpriority": "!no",
      "initcrypto": "!yes",
      "nocolor": "!no",
      "dontwarn": "!no",
      "dumpcore": "!no",
      "languageprefix": "!no",
      "systemname": "",
      "autosystemname": "!no",
      "mindtmfduration": "80",
      "maxcalls": "",
      "maxload": "1",
      "maxfiles": "1024",
      "minmemfree": "0",
      "cache_media_frames": "!yes",
      "cache_record_files": "!no",
      "record_cache_dir": "/tmp",
      "transmit_silence": "!no",
      "transcode_via_sln": "!no",
      "runuser": "root",
      "rungroup": "root",
      "lightbackground": "!no",
      "forceblackbackground": "!no",
      "defaultlanguage": "en",
      "documentation_language": "en_US",
      "hideconnect": "!yes",
      "lockconfdir": "!no",
      "stdexten": "gosub",
      "live_dangerously": "!no",
      "entityid": "",
      "rtp_pt_dynamic": "96"
    }';
    switch($request) {
      case "core-get":{
        $ini = new \INIProcessor('/etc/asterisk/asterisk.conf');     
        $result = self::returnResult($ini->options->getDefaults($generalparams));
      } break;
      case "core-set":{
        if($this->checkPriv('settings_writer')) {
          $ini = new \INIProcessor('/etc/asterisk/asterisk.conf');
          $ini->options->setDefaults($generalparams, $request_data);
          
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
      case "get-languages":{
        $sounds = new \core\Sounds();
        $languagesList = array();
        foreach($sounds->getLanguages() as $lang) {
          $languagesList[] = (object) array('id' => $lang, 'text' => $lang);
        }
        $result = self::returnResult($languagesList);
      } break;
      case "mac-get":{
        $list = array();
        if($dh = opendir(self::$interfacedir)) {
          while(($file = readdir($dh)) !== false) {
            if(is_dir(self::$interfacedir . '/' . $file)) {
              if($file[0]!='.') {
                if(file_exists(self::$interfacedir . '/' . $file . '/address')) {
                  $list[] = trim(file_get_contents(self::$interfacedir . '/' . $file . '/address'));
                }
              }
            }
          }
          closedir($dh);
        }
        $result = self::returnResult($list);
      } break;
      case "user-get":{
        $list = array();
        $data = file_get_contents('/etc/passwd');
        $lines = explode("\n", $data);
        foreach($lines as $line) {
          $pos = strpos($line, ':');
          if($pos!==false) {
            $user = substr($line, 0, $pos);
            $list[] = $user;
          }
        }
        $result = self::returnResult($list);
      } break;
      case "group-get":{
        $list = array();
        $data = file_get_contents('/etc/group');
        $lines = explode("\n", $data);
        foreach($lines as $line) {
          $pos = strpos($line, ':');
          if($pos!==false) {
            $user = substr($line, 0, $pos);
            $list[] = $user;
          }
        }
        $result = self::returnResult($list);
      } break;
    }
    return $result;
  }

  public function scripts() {
    ?>
      <script>

      var language_data = [];

      function loadCoreData() {
        sendRequest('core-get').success(function(data) {
          card.setValue(data);
        });
      }

      function sendCoreData() {
        sendRequest('core-set', card.getValue()).success(function() {
          loadCoreData()
          return true;
        });
      }

      function loadUsers() {
        sendRequest('user-get').success(function(data) {
          card.setValue({runuser: {value: data, clean: true}});
        });
      }

      function loadGroups() {
        sendRequest('group-get').success(function(data) {
          card.setValue({rungroup: {value: data, clean: true}});
        });
      }

      function loadMacAddresses() {
        sendRequest('mac-get').success(function(data) {
          card.setValue({entityid: {value: data, clean: true}});
        });
      }

      $(function () {
        sendRequest('get-languages').success(function(languages) {
          language_data.splice(0);
          language_data.push.apply(language_data, {id: '', text: 'Не указано'});
          language_data.push.apply(language_data, languages);
          card.setValue({defaultlanguage: {value: language_data, clean: true}})
        });
        $('[data-checkbox="tooltip"]').tooltip();
        $('[data-checkbox="popover"]').popover();
      });


<?php
if(self::checkPriv('settings_writer')) {
      ?>

      function sbapply(e) {
        sendCoreData();
      }

<?php
} else {
      ?>

    var sbapply=null;

<?php
}
    ?>

      $(function () {
        sidebar_apply(sbapply);
        card = new widgets.section(rootcontent,null);
        var cols = new widgets.section(card,null);
        cols.node.classList.add('row');
        var col1 = new widgets.columns(cols,2);
        var col2 = new widgets.columns(cols,2);

        subcard1 = new widgets.section(col1,null,_("Параметры запуска"));

        obj = new widgets.input(subcard1, {id: 'verbose', pattern: '[0-9]+'}, 
            "Уровень детализации консоли");
        obj = new widgets.input(subcard1, {id: 'debug', pattern: '[0-9]'}, 
            "Уровень отладки");
        
        obj = new widgets.checkbox(subcard1, {single: true, id: 'alwaysfork', value: false},
            "Всегда отключаться от консоли", "Несовместимость с использованием текстовой консоли управления");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'nofork', value: false},
            "Всегда работать в рамках родительского процесса");
          
        obj = new widgets.select(subcard1, {id: 'runuser', value: [], search: false}, 
            "Учетная запись",
            "Пользователь, под учетной записью которого выполняется ядро технологической платформы");
        obj = new widgets.select(subcard1, {id: 'rungroup', value:[], search: false}, 
            "Группа безопасности",
            "Группа, под учетной записью которой выполняется ядро технологической платформы");

        obj = new widgets.checkbox(subcard1, {single: true, id: 'quiet', value: false},
            "Не выводить диагонстическую информацию при запуске");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'timestamp', value: false},
            "Указывать метку времени для каждого события в консоли");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'execincludes', value: false},
            "Разрешить <em class='text-info'>#exec</em> записи в файлах конфигурации");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'console', value: false},
            "Всегда запускать в консольном режиме");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'highpriority', value: false},
            "Запускать c наивысшим приоритетом в режиме реального времени");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'initcrypto', value: false},
            "Инициализировать шифрование при запуске");

        obj = new widgets.input(subcard1, {id: 'systemname', pattern: '[0-9_A-z]+', placeholder: 'Не задано'}, 
            "Имя системы", 
            "Используется как префикс uniqueid CDR");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'autosystemname', value: false},
            "Автоматически задавать имя системы на основании имени хоста",
            "В случае неудачи использует 'localhost' или 'имя системы', если оно задано");

        subcard2 = new widgets.section(col1,null,_("Внешний вид и язык"));

        obj = new widgets.checkbox(subcard2, {single: true, id: 'nocolor', value: false},
            "Отключить цвета консоли");
        obj = new widgets.checkbox(subcard2, {single: true, id: 'lightbackground', value: false},
            "Использовать цвета совместимые со светлым фоном",
            "Когда используется цвет в консоли, выводятся цвета совместимые со светлым фоном. Если выключена, используются цвета которые хорошо смотрятся на черном фоне");
        obj = new widgets.checkbox(subcard2, {single: true, id: 'forceblackbackground', value: false},
            "Установить чёрный фон",
            "На терминалах со светлым фоном заставить ядро технологической платформы установить черный цвет фона, чтобы цвета в консоли выглядели правильно");
        
        obj = new widgets.select(subcard2, {id: 'documentation_language', value: [{id: 'ru_RU', text: 'Русский'}, {id: 'en_US', text: 'Английский'}], clean: true, search: false}, 
            "Язык");
        obj = new widgets.select(subcard2, {id: 'defaultlanguage', value: language_data, clean: true, search: false}, 
            "Язык звуковых оповещений");       

        subcard4 = new widgets.section(col2,null,_("Ограничения платформы"));
            
        obj = new widgets.input(subcard4, {id: 'mindtmfduration', pattern: '[0-9]+'}, 
            "Минимальная длительность сообщений DTMF (в миллисекундах)", 
            "Если ядро технологической платформы получает сообщение DTMF с длительностью менее этого значения, значение продолжительности сообщения DTMF будет изменено на указанное в этом параметре");
        obj = new widgets.input(subcard4, {id: 'maxcalls', pattern: '[0-9]+', placeholder: 'Не ограничено'}, 
            "Максимальное количество одновременных звонков");
        obj = new widgets.input(subcard4, {id: 'maxload', pattern: '[0-9]+'}, 
            "Максимальная нагрузка на процессор",
            "Ядро технологической платформы прекратит принимать новые вызовы, если средняя нагрузка превышает этот предел");
        obj = new widgets.input(subcard4, {id: 'maxfiles', pattern: '[0-9]+'}, 
            "Максимальное количество файлов",
            "Устанавливает максимальное количество дескрипторов файлов, которое разрешено открыть ядру технологической платформы. Опция является общей для установки этого предела до очень высокого значения");
        obj = new widgets.input(subcard4, {id: 'minmemfree', pattern: '[0-9]+'}, 
            "Минимальная свободная память (в Мегабайт)",
            "Ядро технологической платформы прекратит принимать новые вызовы, если объём свободной памяти упадёт ниже этой отметки");
                 
        subcard8 = new widgets.section(col2,null,_("Поведение ядра"));
        
        obj = new widgets.checkbox(subcard8, {single: true, id: 'dontwarn', value: false},
            "Отключить предупреждения");
        obj = new widgets.checkbox(subcard8, {single: true, id: 'dumpcore', value: false},
            "Выполнить дамп ядра при сбое");
        obj = new widgets.checkbox(subcard8, {single: true, id: 'languageprefix', value: false},
            "Перфикс языка в пути к файлу перед подкаталогом", 
            "Если включен, то перфикс языка находится перед подкаталогом, например ../ru/digits/1.gsm.\n Если выключен, то префикс после имени каталога, например: (digits/ru/1.gsm).");
        obj = new widgets.checkbox(subcard8, {single: true, id: 'transmit_silence', value: false},
            "Транслировать тишину в линию", "Транслирует комфортную тишину вместо сигнализации о наличии тишины в линии");
        obj = new widgets.checkbox(subcard8, {single: true, id: 'transcode_via_sln', value: false},
            "Определить перекодировку через SLINEAR");
        obj = new widgets.checkbox(subcard8, {single: true, id: 'hideconnect', value: false},
            "Не показывать сообщения о подключении удаленных консолей");
        obj = new widgets.checkbox(subcard8, {single: true, id: 'lockconfdir', value: false},
            "Заблокировать каталог с конфигурационными файлами");
        obj = new widgets.checkbox(subcard8, {single: true, id: 'live_dangerously', value: false},
            "Включить выполнение «опасных» функций из внешних источников",
            "Некоторые функции и приложения (например, SHELL) опасны тем, что могут предоставлять дополнительные привилегии");
        obj = new widgets.select(subcard8, {id: 'stdexten', value: [{id: 'gosub', text: 'GoSub'}, {id: 'macro', text: 'Macro'}], clean: true, search: false}, 
            "Как вызывать подпрограмму stdexten",
            "macro - вызывать с помощью макроса, предназначено для старых версий ядра технологической платформы. gosub - вызывать с помощь GoSub");
        obj = new widgets.select(subcard8, {id: 'entityid', value: [], clean: true, search: false},
            "ID объекта системы");
        obj = new widgets.input(subcard8, {id: 'rtp_pt_dynamic', pattern: '[0-9]+'}, 
            "Количество разрешенных форматов при согласовании кодека");

        obj = new widgets.checkbox(subcard8, {single: true, id: 'cache_media_frames', value: false},
            "Кэшировать медиа файлы");
        obj = new widgets.checkbox(subcard8, {single: true, id: 'cache_record_files', value: false},
            "Кэшировать записи",
            "Когда идет запись, файл сохраняется в директорию кэша записей");
        obj = new widgets.input(subcard8, {id: 'record_cache_dir', pattern: '[0-9/_A-z]+'}, 
            "Директория кэша записей");

<?php
if(!self::checkPriv('settings_writer')) {
      ?>
    card.disable();
<?php
}
    ?>
        loadUsers();
        loadGroups();
        loadMacAddresses();
        loadCoreData();
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