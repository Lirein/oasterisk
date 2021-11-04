<?php

namespace core;

class AsteriskCoreSettings extends \view\View {

  public static function getLocation() {
    return 'settings/general/core';
  }

  public static function getAPI() {
    return 'general/core';
  }

  public static function getViewLocation() {
    return 'general/core';
  }

  public static function getMenu() {
    return (object) array('name' => 'Параметры ядра', 'prio' => 1, 'icon' => 'SettingsIcon', 'mode' => 'expert');
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

        [this.users, this.groups, this.mac, this.languages] = await Promise.all([this.asyncRequest('user-get'),  this.asyncRequest('group-get'), this.asyncRequest('mac-get'), this.asyncRequest('languages', null, 'rest/sound')]);

        this.subcardleft = new widgets.section(parent,null);

        this.subcard1 = new widgets.section(this.subcardleft,null,_("Параметры запуска"));        
        this.verbose = new widgets.select(this.subcard1, {id: 'verbose', options: [{id: "0", title:  _("Не выводить")}, {id: "1", title:  _("уровень 1")}, {id: "2", title:  _("уровень 2")}, {id: "3", title:  _("уровень 3")}, {id: "4", title:  _("уровень 4")}, {id: "5", title:  _("уровень 5")}, {id: "6", title:  _("уровень 6")}, {id: "7", title:  _("уровень 7")}, {id: "8", title:  _("уровень 8")}, {id: "9", title:  _("уровень 9")}], search: false}, _("Уровень детализации консоли"));
        this.debug = new widgets.select(this.subcard1, {id: 'debug', options: [{id: "0", title:  _("Не выводить")}, {id: "1", title:  _("уровень 1")}, {id: "2", title:  _("уровень 2")}, {id: "3", title:  _("уровень 3")}, {id: "4", title:  _("уровень 4")}, {id: "5", title:  _("уровень 5")}, {id: "6", title:  _("уровень 6")}, {id: "7", title:  _("уровень 7")}, {id: "8", title:  _("уровень 8")}, {id: "9", title:  _("уровень 9")}], search: false}, _("Уровень отладки"));
        //this.verbose = new widgets.input(this.subcard1, {id: 'verbose', pattern: /[0-9]/}, _("Уровень детализации консоли"));
        //this.debug = new widgets.input(this.subcard1, {id: 'debug', pattern: /[0-9]/}, _("Уровень отладки"));
        this.alwaysfork = new widgets.checkbox(this.subcard1, {single: true, id: 'alwaysfork', value: false}, _("Всегда отключаться от консоли"), _("Несовместимость с использованием текстовой консоли управления"));
        this.nofork = new widgets.checkbox(this.subcard1, {single: true, id: 'nofork', value: false}, _("Всегда работать в рамках родительского процесса"));
        this.runuser = new widgets.select(this.subcard1, {id: 'runuser', options: this.users, clean: true, search: false}, _("Учетная запись"), _("Пользователь, под учетной записью которого выполняется ядро технологической платформы"));
        this.rungroup = new widgets.select(this.subcard1, {id: 'rungroup', options: this.groups, clean: true, search: false}, _("Группа безопасности"), _("Группа, под учетной записью которой выполняется ядро технологической платформы"));
        this.quiet = new widgets.checkbox(this.subcard1, {single: true, id: 'quiet', value: false}, _("Не выводить диагностическую информацию при запуске"));
        this.timestamp = new widgets.checkbox(this.subcard1, {single: true, id: 'timestamp', value: false}, _("Указывать метку времени для каждого события в консоли"));
        this.execincludes = new widgets.checkbox(this.subcard1, {single: true, id: 'execincludes', value: false}, _("Разрешить <em class='text-info'>#exec</em> записи в файлах конфигурации"));
        this.console = new widgets.checkbox(this.subcard1, {single: true, id: 'console', value: false}, _("Всегда запускать в консольном режиме"));
        this.highpriority = new widgets.checkbox(this.subcard1, {single: true, id: 'highpriority', value: false}, _("Запускать c наивысшим приоритетом в режиме реального времени"));
        this.initcrypto = new widgets.checkbox(this.subcard1, {single: true, id: 'initcrypto', value: false}, _("Инициализировать шифрование при запуске"));
        this.systemname = new widgets.input(this.subcard1, {id: 'systemname', pattern: /[0-9_A-z]+/, placeholder: 'Не задано'}, _("Имя системы"), _("Используется как префикс uniqueid CDR"));
        this.autosystemname = new widgets.checkbox(this.subcard1, {single: true, id: 'autosystemname', value: false}, _("Автоматически задавать имя системы на основании имени хоста"), _("В случае неудачи использует 'localhost' или 'имя системы', если оно задано"));

        this.subcard2 = new widgets.section(this.subcardleft,null,_("Внешний вид и язык"));
        this.nocolor = new widgets.checkbox(this.subcard2, {single: true, id: 'nocolor', value: false}, _("Отключить цвета консоли"));
        this.documentationlanguage = new widgets.select(this.subcard2, {id: 'documentation_language', options: [{id: 'ru_RU', title: _("Русский")}, {id: 'en_US', title: _("Английский")}], search: false}, _("Язык"));
        this.forceblackbackground = new widgets.checkbox(this.subcard2, {single: true, id: 'forceblackbackground', value: false}, _("Установить чёрный фон"), _("На терминалах со светлым фоном заставить ядро технологической платформы установить черный цвет фона, чтобы цвета в консоли выглядели правильно"));
        this.defaultlanguage = new widgets.select(this.subcard2, {id: 'defaultlanguage', options: this.languages, search: false}, _("Язык звуковых оповещений"));       
        this.lightbackground = new widgets.checkbox(this.subcard2, {single: true, id: 'lightbackground', value: false}, _("Использовать цвета совместимые со светлым фоном"), _("Когда используется цвет в консоли, выводятся цвета совместимые со светлым фоном. Если выключена, используются цвета которые хорошо смотрятся на черном фоне"));
        
        this.subcardright = new widgets.section(parent,null);

        this.subcard4 = new widgets.section(this.subcardright,null,_("Поведение ядра"));
        this.dontwarn = new widgets.checkbox(this.subcard4, {single: true, id: 'dontwarn', value: false}, _("Отключить предупреждения"));
        this.dumpcore = new widgets.checkbox(this.subcard4, {single: true, id: 'dumpcore', value: false}, _("Выполнить дамп ядра при сбое"));
        this.languageprefix = new widgets.checkbox(this.subcard4, {single: true, id: 'languageprefix', value: false}, _("Перфикс языка в пути к файлу перед подкаталогом"), _("Если включен, то перфикс языка находится перед подкаталогом, например ../ru/digits/1.gsm.\n Если выключен, то префикс после имени каталога, например: (digits/ru/1.gsm)."));
        this.transmitsilence = new widgets.checkbox(this.subcard4, {single: true, id: 'transmit_silence', value: false}, _("Транслировать тишину в линию"), _("Транслирует комфортную тишину вместо сигнализации о наличии тишины в линии"));
        this.transcode_via_sln = new widgets.checkbox(this.subcard4, {single: true, id: 'transcode_via_sln', value: false}, _("Определить перекодировку через SLINEAR"));
        this.hideconnect = new widgets.checkbox(this.subcard4, {single: true, id: 'hideconnect', value: false}, _("Не показывать сообщения о подключении удаленных консолей"));
        this.lockconfdir = new widgets.checkbox(this.subcard4, {single: true, id: 'lockconfdir', value: false}, _("Заблокировать каталог с конфигурационными файлами"));
        this.livedangerously = new widgets.checkbox(this.subcard4, {single: true, id: 'live_dangerously', value: false}, _("Включить выполнение «опасных» функций из внешних источников"), _("Некоторые функции и приложения (например, SHELL) опасны тем, что могут предоставлять дополнительные привилегии"));
        this.stdexten = new widgets.select(this.subcard4, {id: 'stdexten', options: [{id: 'gosub', title: _("GoSub")}, {id: 'macro', title: _("Macro")}], clean: true, search: false}, _("Как вызывать подпрограмму stdexten"), _("macro - вызывать с помощью макроса, предназначено для старых версий ядра технологической платформы. gosub - вызывать с помощь GoSub"));
        this.entityid = new widgets.select(this.subcard4, {id: 'entityid', options: this.mac, clean: true, search: false}, _("ID объекта системы"));
        this.rtpptdynamic = new widgets.input(this.subcard4, {id: 'rtp_pt_dynamic', pattern: /[0-9]+/}, _("Количество разрешенных форматов при согласовании кодека"));
        this.cachemediaframes = new widgets.checkbox(this.subcard4, {single: true, id: 'cache_media_frames', value: false}, _("Кэшировать медиа файлы"));
        this.cacherecordfiles = new widgets.checkbox(this.subcard4, {single: true, id: 'cache_record_files', value: false}, _("Кэшировать записи"), _("Когда идет запись, файл сохраняется в директорию кэша записей"));
        this.recordcachedir = new widgets.input(this.subcard4, {id: 'record_cache_dir', pattern: /[0-9/_A-z]+/}, _("Директория кэша записей"));
      
        this.subcard3 = new widgets.section(this.subcardright,null,_("Ограничения платформы")); 
        this.mindtmfduration = new widgets.input(this.subcard3, {id: 'mindtmfduration', prefix: _("миллисекунд"), pattern: /[0-9]+/}, _("Минимальная длительность сообщений DTMF"), _("Если ядро технологической платформы получает сообщение DTMF с длительностью менее этого значения, значение продолжительности сообщения DTMF будет изменено на указанное в этом параметре"));
        this.maxcalls = new widgets.input(this.subcard3, {id: 'maxcalls', pattern: /[0-9]+/, placeholder: 'Не ограничено'}, _("Максимальное количество одновременных звонков"));
        this.maxload = new widgets.input(this.subcard3, {id: 'maxload', pattern: /[0-9]+/}, _("Максимальная нагрузка на процессор"), _("Ядро технологической платформы прекратит принимать новые вызовы, если средняя нагрузка превышает этот предел"));
        this.maxfiles = new widgets.input(this.subcard3, {id: 'maxfiles', pattern: /[0-9]+/}, _("Максимальное количество файлов"), _("Устанавливает максимальное количество дескрипторов файлов, которое разрешено открыть ядру технологической платформы. Опция является общей для установки этого предела до очень высокого значения"));
        this.minmemfree = new widgets.input(this.subcard3, {id: 'minmemfree', prefix: _("Мб"), pattern: /[0-9]+/}, _("Минимальная свободная память"), _("Ядро технологической платформы прекратит принимать новые вызовы, если объём свободной памяти упадёт ниже этой отметки"));     
        
        this.onReset = this.reset; 

        this.hasSave = true;
      }      

      function reset() {
        this.setValue({ "verbose": "0",
                        "debug": "0",
                        "alwaysfork": "false",
                        "nofork": "false",
                        "quiet": "false",
                        "timestamp": "false",
                        "execincludes": "false",
                        "console": "false",
                        "highpriority": "false",
                        "initcrypto": "true",
                        "nocolor": "false",
                        "dontwarn": "false",
                        "dumpcore": "false",
                        "languageprefix": "false",
                        "systemname": "",
                        "autosystemname": "false",
                        "mindtmfduration": "80",
                        "maxcalls": "",
                        "maxload": "1",
                        "maxfiles": "1024",
                        "minmemfree": "0",
                        "cache_media_frames": "true",
                        "cache_record_files": "false",
                        "record_cache_dir": "/tmp",
                        "transmit_silence": "false",
                        "transcode_via_sln": "false",
                        "runuser": "root",
                        "rungroup": "root",
                        "lightbackground": "false",
                        "forceblackbackground": "false",
                        "defaultlanguage": "en",
                        "documentation_language": "en_US",
                        "hideconnect": "true",
                        "lockconfdir": "false",
                        "stdexten": "gosub",
                        "live_dangerously": "false",
                        "entityid": "",
                        "rtp_pt_dynamic": "96"});
      };
      
      </script>
    <?php
  }

}

?>