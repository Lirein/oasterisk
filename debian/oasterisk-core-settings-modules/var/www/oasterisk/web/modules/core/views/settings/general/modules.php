<?php

namespace core;

class ModulesSettings extends ViewModule {

  public static function getLocation() {
    return 'settings/general/modules';
  }

  public static function getMenu() {
    return (object) array('name' => 'Загружаемые модули', 'prio' => 10, 'icon' => 'oi oi-puzzle-piece');
  }

  public static function _getModuleDesc() {
    $desc = array(
      'cdr' => array(
        'adaptive_odbc' => 'Запись в ODBC',
        'custom' => 'Произвольный CSV',
        'manager' => 'Команды AMI',
        'sqlite3_custom' => 'Запись в SQLite3',
        'syslog' => 'Системный журнал',
        'sqlite' => 'Запись в SQLite',
      ),
      'pbx' => array(
        'gtkconsole' => 'Консоль GTK',
        'kdeconsole' => 'Консоль KDE',
        'config' => 'Текстовые файлы',
        'realtime' => 'Внешние источники данных',
        'spool' => 'Планировщик',
        'loopback' => 'Автоконфигурация'
      ),
      'resource' => array(
        'adsi' => 'Ресурсы ADSI',
        'agi' => 'Интерфейс AGI',
        'ari' => 'Интерфейс RESTful',
        'ari_applications' => 'RESTful - Стазис',
        'ari_asterisk' => 'RESTful - Функции',
        'ari_bridges' => 'RESTful - Мосты',
        'ari_channels' => 'RESTful - Каналы',
        'ari_device_states' => 'RESTful - Устройства',
        'ari_endpoints' => 'RESTful - Абоненты',
        'ari_events' => 'RESTful - WebSocket',
        'ari_model' => 'RESTful - Модели',
        'ari_playbacks' => 'RESTful - Музыка',
        'ari_recordings' => 'RESTful - Запись',
        'ari_sounds' => 'RESTful - Воспроизведение',
        'calendar' => 'Поддержка календарей',
        'calendar_caldav' => 'Календари CalDAV',
        'calendar_ews' => 'Календари MS Exchange EWS',
        'calendar_exchange' => 'Календари MS Exchange',
        'calendar_icalendar' => 'Календари iCalendar',
        'clialiases' => 'Синонимы CLI',
        'clioriginate' => 'Синонимы вызова из CLI',
        'config_odbc' => 'Чтение из ODBC',
        'config_curl' => 'Чтение из cURL',
        'config_sqlite3' => 'Чтение из SQLite3',
        'config_pgsql' => 'Чтение из PostgreSQL',
        'config_mysql' => 'Чтение из MySQL',
        'convert' => 'Форматы звука из CLI',
        'crypto' => 'Работа с ЭЦП',
        'curl' => 'Движок cURL',
        'fax' => 'Поддержка Факса',
        'fax_spandsp' => 'Факс для G.711 и T.38',
        'format_attr_celt' => 'Атрибуты формата CELT',
        'format_attr_g729' => 'Атрибуты формата G.729',
        'format_attr_h263' => 'Атрибуты формата H.263',
        'format_attr_h264' => 'Атрибуты формата H.264',
        'format_attr_opus' => 'Атрибуты формата Opus',
        'format_attr_silk' => 'Атрибуты формата SILK',
        'format_attr_siren14' => 'Атрибуты формата Siren14',
        'format_attr_siren7' => 'Атрибуты формата Siren7',
        'format_attr_vp8' => 'Атрибуты формата VP8',
        'http_post' => 'Поддержка HTTP POST',
        'http_websocket' => 'Поддержка HTTP WebSocket',
        'limit' => 'Ограничение ресурсов',
        'manager_devicestate' => 'AMI - Устройства',
        'manager_presencestate' => 'AMI - Состояние абонентов',
        'monitor' => 'Запись разговоров',
        'musiconhold' => 'Музыка на удержании',
        'mutestream' => 'Воспроизведение тишины',
        'odbc' => 'Драйвер ODBC',
        'odbc_transaction' => 'Транзакции ODBC',
        'parking' => 'Парковка вызовов',
        'realtime' => 'Функции реального времени',
        'rtp_asterisk' => 'Стек RTP',
        'rtp_multicast' => 'Поддержка RTP Multicast',
        'security_log' => 'Журнал безопасности',
        'smdi' => 'Интерфейс SMDI',
        'sorcery_astdb' => 'Хранение данных AstDB',
        'sorcery_config' => 'Хранение данных в файлах',
        'sorcery_memory' => 'Хранение данных в памяти',
        'sorcery_memory_cache' => 'Кеширование данных в памяти',
        'sorcery_realtime' => 'Хранение данных в БД',
        'speech' => 'Распознавание речи',
        'srtp' => 'Поддержка Secure RTP (SRTP)',
        'stasis' => 'Стазис приложения',
        'stasis_answer' => 'Стазис - Ответ',
        'stasis_device_state' => 'Стазис - Устройства',
        'stasis_playback' => 'Стазис - Воспроизведение',
        'stasis_recording' => 'Стазис - Запись',
        'stasis_snoop' => 'Стазис - Будильник',
        'stun_monitor' => 'Мониторинг STUN',
        'timing_timerfd' => 'Таймер средств ФС',
        'xmpp' => 'Интерфейс XMPP',
      ),
      'function' => array(
        'aes' => 'Шифрование AES',
        'base64' => 'Кодирование Base64',
        'blacklist' => 'Черный список CallerID',
        'callcompletion' => 'Завершение звонка',
        'callerid' => 'Управление CallerID',
        'cdr' => 'Поля журнала',
        'channel' => 'Параметры канала',
        'config' => 'Параметры конфигурации',
        'curl' => 'Вызов cURL',
        'cut' => 'Вырезать часть строки',
        'db' => 'Работа с AstDB',
        'devstate' => 'Состояние устройств',
        'dialgroup' => 'Группы звонков',
        'dialplan' => 'План вызовов',
        'enum' => 'Получение функций',
        'env' => 'Переменные окружения',
        'extstate' => 'Состояние точки входа',
        'global' => 'Глобальные переменные',
        'groupcount' => 'Количество групп',
        'hangupcause' => 'Причина отбоя',
        'holdintercept' => 'Состояние удержания',
        'iconv' => 'Преобразование кодировки',
        'jitterbuffer' => 'Буферизация звука',
        'lock' => 'Исключительные блокировки',
        'logic' => 'Логические выражения',
        'math' => 'Математические выражения',
        'md5' => 'Алгоритм MD5',
        'module' => 'Статус модуля',
        'odbc' => 'Чтение ODBC',
        'periodic_hook' => 'Обработчики плана вызовов',
        'presencestate' => 'Состояние абонента',
        'rand' => 'Случайная функция',
        'realtime' => 'Внешние источники данных',
        'sha1' => 'Алгоритм SHA-1',
        'shell' => 'Вызов приложений Linux',
        'sorcery' => 'Параметры объектов',
        'speex' => 'Подавление шума Speex',
        'sprintf' => 'Форматирование sprintf()',
        'srv' => 'Получение DNS SRV',
        'strings' => 'Работа со строками',
        'sysinfo' => 'Информация о системе',
        'talkdetect' => 'Определение голоса',
        'timeout' => 'Таймер',
        'uri' => 'Работа с URI',
        'version' => 'Версия платформы',
        'vmcount' => 'Количество сообщений',
        'volume' => 'Громкость звука',
      ),
      'channel' => array(
        'bridge_media' => 'Канальный мост',
        'iax2' => 'Между АТС Asterisk',
        'motif' => 'Драйвер Motif',
        'ooh323' => 'Драйвер H.323',
        'phone' => 'Linux Telephony API',
        'rtp' => 'Передача RTP',
        'sip' => 'Драйвер SIP',
        'pjsip' => 'Новый драйвер SIP',
        'dongle' => 'USB 3G/GSM Модем',
        'mobile' => 'Драйвер Bluetooth',
        'dahdi' => 'Драйвер плат Digium™',
        'alsa' => 'Звуковой адаптер'
      ),
      'codec' => array(
        'a_mu' => 'Транскодер G.711µ ⇔ G.711a',
        'adpcm' => 'Энкодер/Декодер ADPCM',
        'alaw' => 'Энкодер/Декодер G.711a',
        'g722' => 'Энкодер/Декодер ITU G.722-64',
        'g726' => 'Энкодер/Декодер ITU G.726-32',
        'gsm' => 'Энкодер/Декодер GSM',
        'lpc10' => 'Энкодер/Декодер LPC10',
        'resample' => 'Ресемплирование',
        'speex' => 'Энкодер/Декодер Speex',
        'ulaw' => 'Энкодер/Декодер G.711µ',
      ),
      'format' => array(
        'g719' => 'Формат ITU G.719',
        'g723' => 'Формат G.723.1',
        'g726' => 'Формат G.726 (16/24/32/40kbps)',
        'g729' => 'Формат G.729',
        'gsm' => 'Формат GSM',
        'h263' => 'Формат H.263',
        'h264' => 'Формат H.264',
        'ilbc' => 'Формат iLBC',
        'mp3' => 'Формат MP3',
        'ogg_vorbis' => 'Формат OGG/Vorbis',
        'pcm' => 'G.711a/µ 8KHz (PCM,PCMA,AU)',
        'siren14' => 'ITU G.722.1 Annex C',
        'siren7' => 'Формат ITU G.722.1',
        'sln' => 'Формат SLN',
        'wav' => 'Формат WAV/WAV16',
        'wav_gsm' => 'Формат MS WAV',
      ),
      'application' => array(
        'agent_pool' => 'Вызов агента очереди',
        'authenticate' => 'Аутентификация',
        'bridgewait' => 'Удержание вызова',
        'cdr' => 'Запись журнала',
        'celgenuserevent' => 'Произвольные события журнала',
        'channelredirect' => 'Перенаправление звонка',
        'chanspy' => 'Прослушивание канала',
        'confbridge' => 'Конференц-мост',
        'controlplayback' => 'Управление музыкой',
        'db' => 'Управление AstDB',
        'dial' => 'Вызов абонента',
        'directed_pickup' => 'Перехват звонка',
        'directory' => 'Адресная книга',
        'disa' => 'Прямой входящий доступ (DISA)',
        'dumpchan' => 'Дамп состояния канала',
        'echo' => 'Простой эхо-тест',
        'exec' => 'Выполнение приложений',
        'followme' => 'Следуй-за-мной',
        'forkcdr' => 'Копировать запись журнала',
        'macro' => 'Вызвать макрос',
        'milliwatt' => 'Генерировать сигнал',
        'mixmonitor' => 'Запись звонка',
        'originate' => 'Внутренний вызов',
        'page' => 'Вещать на громкоговоритель',
        'playback' => 'Воспроизвести',
        'playtones' => 'Отправить DTMF (inband)',
        'privacy' => 'Контроль номера',
        'queue' => 'Входящая очередь звонков',
        'read' => 'Чтение кодов DTMF',
        'readexten' => 'Чтение точки входа',
        'record' => 'Запись в файл',
        'sayunixtime' => 'Произнести время',
        'senddtmf' => 'Отправить DTMF',
        'sendtext' => 'Передать текст',
        'softhangup' => 'Программно положить трубку',
        'speech_utils' => 'Речевые функции',
        'stack' => 'Подпрограммы',
        'stasis' => 'Вызов Стазис приложения',
        'system' => 'Вызов программы Linux',
        'talkdetect' => 'Определение голоса',
        'transfer' => 'Перевод звонка',
        'userevent' => 'Произвольное событие',
        'verbose' => 'Напечатать в консоль',
        'voicemail' => 'Голосовая почта',
        'voicemail_imapstorage' => 'Голосовая почта IMAP',
        'waituntil' => 'Пауза',
        'while' => 'Цикл «Пока»',
      ),
      'cel' => array(
        'custom' => 'Произвольный CSV',
        'manager' => 'Команды AMI',
        'odbc' => 'Запись в ODBC',
      ),
      'bridge' => array(
        'builtin_features' => 'Встроенный мост',
        'builtin_interval_features' => 'Временные интервалы',
        'holding' => 'Удержание вызова',
        'native_rtp' => 'Объединение RTP',
        'simple' => 'Объединение двух каналов',
        'softmix' => 'Мультиканальный микшер',
      ),
    );
    return $desc;
  }

  public function getModuleDesc() {
    return self::_getModuleDesc();
  }

  public static function check($write = false) {
    $result = true;
    $result &= self::checkPriv('system_info');
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  private static function modulecmp(&$a, &$b) {
    return strcmp($a->name, $b->name);
  }

  public function json(string $request, \stdClass $request_data) {
    $modules = self::getAsteriskModules();
    $result = array();
    switch($request) {
      case "desc": {
        $result = self::returnResult(\core\ModulesSettings::_getModuleDesc());
      } break;
      case "list": {
        $return = new \stdClass;
        $return->autoload=$modules->autoload;
        $return->pbx=$modules->pbx;
        $return->channel=$modules->channel;
        $return->codec=$modules->codec;
        $return->format=$modules->format;
        $return->cdr=$modules->cdr;
        $return->cel=$modules->cel;
        $return->bridge=$modules->bridge;
        $return->resource=$modules->resource;
        $return->application=$modules->application;
        $return->function=$modules->function;
        $result = self::returnResult($return);
      } break;
      case "set": {
        if(isset($request_data->autoload)&&self::checkPriv('settings_writer')) {
          $data = new \INIProcessor('/etc/asterisk/modules.conf');
          $data->modules=array();
          $data->modules->autoload=$request_data->autoload?'yes':'no';
          foreach($request_data->data as $section => $modules) {
            $prefix='';
            switch($section) {
              case 'resource': $prefix='res_'; break;
              case 'application': $prefix='app_'; break;
              case 'function': $prefix='func_'; break;
              default: $prefix=$section.'_';
            }
            foreach($modules as $module) {
              $p=$prefix.$module->id.'.so';
              if($module->state!=1) $data->modules->$p=$module->state==0?'noload':'load';
            }
          }
        }
        if($data->save()) {
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Не удалось сохранить настройки модулей ');
        }
        $this->cache->delete('modules');
      } break;
    }
    return $result;
  }

  public function scripts() {
    global $location;
    ?>
    <script>
      function mapSection(section) {
        switch(section) {
          case 'pbx': return 'Конфигурация платформы'; break;
          case 'channel': return 'Канальные драйверы'; break;
          case 'codec': return 'Аудио/Видео кодеки'; break;
          case 'format': return 'Форматы передачи данных RTP'; break;
          case 'cdr': return 'Детализация вызовов'; break;
          case 'cel': return 'Детализация событий звонка'; break;
          case 'bridge': return 'Объединение голосовых каналов'; break;
          case 'resource': return 'Разделяемые ресурсы'; break;
          case 'application': return 'Приложения логики управления'; break;
          case 'function': return 'Функции переменных логики управления'; break;
          default: return section;
        }
      }

      var desc = null;

      function mapModule(section, name) {
        var result=name;
        if(typeof desc[section] != 'undefined') {
          if(typeof desc[section][name] != 'undefined') {
            result = desc[section][name];
          }
        }
        return result;
      }

      function updateDescriptions() {
        sendRequest('desc').success(function(data) {
          desc = data;
        });
      }

      function updateModules() {
        sendRequest('list').success(function(data) {
          $('#module-list').html('');
          var modules=$('<ul class="list-group d-inline-block cel-12 c-1 c-md-2 c-lg-3 c-xl-4 pr-3" id="confbridge-users-list"></ul>').appendTo($('#module-list'));
          $('#module-autoload').prop('checked',data.autoload);
          for(section in data) {
            if(section=='autoload') continue;
            sectiontitle=mapSection(section);
            var section_list = $('<li class="list-group-item virtual d-inline-block w-100 pl-0 pr-0"><div class="group-header">'+sectiontitle+'</div><ul class="list-group col-12 pr-0"></ul></li>').appendTo(modules).find('ul');
            for(module in data[section]) {
              var item=$('<li class="small list-group-item pt-1 pb-1 ml-3 mr-3" id="module-'+section+'-'+module+'">'+mapModule(section, module)+'</li>').appendTo(section_list);
              if(data[section][module].loaded) {
                item.addClass('list-group-item-success');
              } else {
                item.addClass('list-group-item-danger');
              }
              var check=$('<div class="custom-control custom-checkbox float-right toggle"><input type="checkbox" class="custom-control-input" id="'+section+'-'+module+'" onInput="event.preventDefault(); event.stopPropagation(); changeState(event.originalTarget); return false;"><label class="custom-control-label" for="'+section+'-'+module+'">&nbsp;</label><span class="bg"></span></div>').appendTo(item).find('input');
              var state=0;
              if(data[section][module].mode=='unknown') state=1; else
              if(data[section][module].mode=='load') state=2; else
              if(data[section][module].mode=='preload') state=2;
              setState(check,state);
<?php if(self::checkPriv('settings_writer')) { ?>
                orderList(section_list.get(0), function(e) { });
<?php } else { ?>
                $('#module-autoload').prop('disabled',true);
                check.prop('disabled',true);
<?php } ?>
            }
          }
        });
      }

      function sendModules() {
        sendRequest('set', {autoload: $('#module-autoload').prop('checked'), data: readStateList('#module-list')}).success(function(data) {
          showalert('success','Конфигурация модулей успешно сохранена');
          updateModules();
          return false;
        });
      }

      function readStateList(sender) {
        var result = {
         'pbx': [],
         'channel': [],
         'codec': [],
         'format': [],
         'cdr': [],
         'cel': [],
         'bridge': [],
         'resource': [],
         'application': [],
         'function': []
        };
        $(sender).find('input[type=checkbox]').each(function(i, input) {
          result[input.id.split('-')[0]].push({id: input.id.split('-')[1], state: $(input).data('checked')});
        });
        return result;
      }

      function setState(obj, state) {
        obj.data('checked',state);
        if(state==0) {
          obj.prop('checked',false);
          obj.prop('indeterminate',false);
        } else {
          if(state==2) {
            obj.prop('checked',true);
            obj.prop('indeterminate',false);
          } else {
            obj.prop('indeterminate',true);
          }
        }
      }

      function changeState(obj) {
        obj=$(obj);
        if(obj.data('checked')==2) {
          obj.data('checked',0);
          obj.prop('checked',false);
          obj.prop('indeterminate',false);
        } else {
          if(obj.data('checked')==1) {
            obj.data('checked',2);
            obj.prop('checked',true);
            obj.prop('indeterminate',false);
          } else {
            obj.data('checked',1);
            obj.prop('checked',false);
            obj.prop('indeterminate',true);
          }
        }
      }

<?php
  if(self::checkPriv('settings_writer')) {
?>

      function sbapply(e) {
        sendModules();
      }

<?php
  } else {
?>

    var sbapply=null;

<?php
  }
?>
      $(function () {
        updateDescriptions();
        updateModules();
        sidebar_apply(sbapply);
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
      });
    </script>
    <?php
  }

  public function render() {
    ?>
       <div class="form-group">
        <div class="col">
         <label class="custom-control custom-checkbox">
          <input type="checkbox" class="custom-control-input" id="module-autoload">
          <span class="custom-control-label">Автоматическая загрузка модулей
           <span class="badge badge-pill badge-info" data-toggle="popover" data-placement="top" title="Автоматическая загрузка модулей" data-content="Модули технологической платформы могут иметь три состояния загрузки:<ul><li class='text-danger'>Отключен</li><li class='text-dark'>По умолчанию</li><li class='text-success'>Включен</li></ul>Производится автоматическая загрузка всех найденых модулей технологической платформы явно не напрещенных для загрузки состоянием <span class='text-danger'>«Отключен»</span>.<br>Если автоматическая загрузка модулей отключена, загружаются только те модули технологической платформы, которые отмечены для загрузки состоянием <span class='text-success'>«Включен»</soan>." data-trigger='hover' data-html=true>?</span>
          </span>
         </label>
        </div>
       </div>
       <div class="form-group" style="margin-right: -1.5rem;">
        <div class="d-flex flex-wrap col-12 pl-1 pr-0" id="module-list">
        </div>
       </div>
    <?php
  }

}

?>