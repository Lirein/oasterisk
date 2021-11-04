<?php

namespace pjsip;

class PeerViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'peer/pjsip';
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
        this.rel100 = new widgets.select(parent, {id: '100rel', default: 'yes', value: 'yes', options: [
          {id: 'no', title: _('Отключено')},
          {id: 'yes', title: _('Включено')},
          {id: 'required', title: _('Принудительно')}
        ]}, _('Ответы стандарта RFC3262 на запросы BLF'));
        this.aggregate_mwi = new widgets.checkbox(parent, {id: 'aggregate_mwi', value: true}, _('Объединять MWI в один NOTIFY'), _('Если параметр включен, объединяет статусы наличия сообщений из нескольих почтовых ящиков в единое уведомление о непрослушанных сообщениях'));
        this.codecs = new widgets.select(parent, {id: 'codecs', default: ['opus', 'h264', 'g792', 'g711a', 'g711u'], value: [], multiple: true, options: []}, _('Используемые кодеки'));
        this.incoming_offer_codec_prefs = new widgets.section(parent, {id: 'incoming_offer_codec_prefs', small: true}, _('Приоритеты кодеков при согласовании входящего звонка'));
        // require('pjsip/scodecprefs', this.incoming_offer_codec_prefs, {prefer: 'pending', operation: 'intersect', keep: 'all', transcode: 'allow'});
        this.outgoing_offer_codec_prefs = new widgets.section(parent, {id: 'outgoing_offer_codec_prefs', small: true}, _('Приоритеты кодеков при согласовании исходящего звонка'));
        // require('pjsip/scodecprefs', this.outgoing_offer_codec_prefs, {prefer: 'pending', operation: 'union', keep: 'all', transcode: 'allow'});
        this.incoming_answer_codec_prefs = new widgets.section(parent, {id: 'incoming_answer_codec_prefs', small: true}, _('Приоритеты кодеков при согласовании ответа на входящий звонок'));
        // require('pjsip/scodecprefs', this.incoming_answer_codec_prefs, {prefer: 'pending', operation: 'intersect', keep: 'all', transcode: 'allow'});
        this.outgoing_answer_codec_prefs = new widgets.section(parent, {id: 'outgoing_answer_codec_prefs', small: true}, _('Приоритеты кодеков при согласовании ответа на исходящий звонок'));
        // require('pjsip/scodecprefs', this.outgoing_answer_codec_prefs, {prefer: 'pending', operation: 'intersect', keep: 'all', transcode: 'allow'});
        this.allow_overlap = new widgets.checkbox(parent, {id: 'allow_overlap', value: true}, _('Поддержка раннего аудио'), _('Включает поддержку передачи аудиотракта до момента поднятия трубки. Позволяет проигрывать музыку на удержании и обрабатывать IVR до начала тарификации.'));
        this.aors = new widgets.input(parent, {id: 'aors', default: [], value: ''}, _('Набор объектов сопоставлений с абонентом'));
        this.auth = new widgets.input(parent, {id: 'auth', default: [], value: ''}, _('Аутентификация абонента'));
        this.callerid = new widgets.input(parent, {id: 'callerid', default: '', value: ''}, _('Идентификатор абонента'));
        this.callerid_privacy = new widgets.select(parent, {id: 'callerid_privacy', default: 'allowed_not_screened', options: [
          {id: 'allowed_not_screened', title: _('Разрешена без мониторинга')},
          {id: 'allowed_passed_screens', title: _('Разрешена при мониторинге')},
          {id: 'allowed_failed_screen', title: _('Разрашена кроме мониторинга')},
          {id: 'allowed', title: _('Разрешена всегда')},
          {id: 'prohib_not_screened', title: _('Запрещена без мониторинга')},
          {id: 'prohib_passed_screen', title: _('Запрещена при мониторинге')},
          {id: 'prohib_failed_screen', title: _('Запрещена кроме мониторинга')},
          {id: 'prohib', title: _('Запрещена всегда')},
          {id: 'unavailable', title: _('Недоступно')}
        ]}, _('Уровень приватности при передаче CallerID'));
        this.callerid_tag = new widgets.input(parent, {id: 'callerid_tag', default: '', value: ''}, _('Внутренний идентификатор абонента для звонков'));
        this.context = new widgets.select(parent, {id: 'context', default: 'default', options: []}, _('Направление вызовов'));
        this.direct_media_glare_mitigation = new widgets.select(parent, {id: 'direct_media_glare_mitigation', default: 'none', options: [
          {id: 'none', title: _('Отключено')},
          {id: 'outgoing', title: _('Исходящие')},
          {id: 'incoming', title: _('Входящие')}
        ]}, _('Избегать дублирования (re)INVITE сигнализации'));
        this.direct_media_method = new widgets.select(parent, {id: 'direct_media_method', default: 'invite', options: [
          {id: 'invite', title: _('INVITE')},
          {id: 'reinvite', title: _('REINVITE')},
          {id: 'update', title: _('UPDATE')}
        ]}, _('Способ обновления данных о потоке RTP'));
        this.trust_connected_line = new widgets.checkbox(parent, {id: 'trust_connected_line', value: true}, _('Разрешить прием обновлений о доступных линиях связи'));
        this.send_connected_line = new widgets.checkbox(parent, {id: 'send_connected_line', value: true}, _('Отправлять сведения о доступных линиях'));
        this.connected_line_method = new widgets.select(parent, {id: 'connected_line_method', default: 'invite', options: [
          {id: 'invite', title: _('INVITE')},
          {id: 'reinvite', title: _('REINVITE')},
          {id: 'update', title: _('UPDATE')}
        ]}, _('Метод обновления информации о доступных линиях'));
        this.direct_media = new widgets.checkbox(parent, {id: 'direct_media', value: true}, _('Разрешать прямые P2P соединения'));
        this.disable_direct_media_on_nat = new widgets.checkbox(parent, {id: 'disable_direct_media_on_nat', value: false}, _('Запретить прямые P2P соединения за NAT'));
        this.dtmf_mode = new widgets.select(parent, {id: 'dtmf_mode', default: 'rfc4733', options: [
          {id: 'rfc4733', title: _('RFC4733/RFC2833')},
          {id: 'inband', title: _('Inband')},
          {id: 'info', title: _('INFO')},
          {id: 'auto', title: _('Автосогласование')},
          {id: 'auto_info', title: _('Outofband')}
        ]}, _('Режим работы DTMF'), _(''));
        this.dtmf_mode.onChange = (sender) => {
          switch(sender.getValue()) {
            case 'rfc4733': {
              sender.setHint('Передает тоновый сигнал в отдельном аудио-тракте, обратно совместимо со стандартом RFC2833');
            } break;
            case 'inband': {
              sender.setHint('Тоновая сигнализация передается в том же аудио тракте что и голос');
            } break;
            case 'info': {
              sender.setHint('Для сигнализации используется текстовое представление в запросе SIP INFO');
            } break;
            case 'auto': {
              sender.setHint('Попытка согласования RFC4733 или переход на Inband');
            } break;
            case 'auto_info': {
              sender.setHint('Попытка согласования RFC4733 или переход на SIP INFO');
            } break;
            default: {
              sender.setHint('');
            }
          }
        }
        this.media_address = new widgets.input(parent, {id: 'media_address', default: '', value: ''}, _('Адрес для приема мультимедиа (в SDP)'), _('Перекрывается соответствующим параметром транспорта'));
        this.bind_rtp_to_media_address = new widgets.checkbox(parent, {id: 'bind_rtp_to_media_address', value: false}, _('Выбирать интерфейс согласно мультимедиа адресу'));
        this.force_rport = new widgets.checkbox(parent, {id: 'force_rport', value: true}, _('Принудительно отправлять RTP по порту источника'));
        this.ice_support = new widgets.checkbox(parent, {id: 'ice_support', value: false}, _('Включить поддержку I.C.E.'));
        this.identify_by = new widgets.select(parent, {id: 'identify_by', default: ['username','ip'], multiple: true, readonly: true, options: [
          {id: 'username', title: _('Поле From')},
          {id: 'auth_username', title: _('Логин пользователя')},
          {id: 'ip', title: _('IP адрес')},
          {id: 'header', title: _('Параметр заголовка')},
        ]}, _('Способы сопоставления абонента'));
        this.redirect_method = new widgets.select(parent, {id: 'redirect_method', default: 'user', options: [
          {id: 'user', title: _('Номер телефона')},
          {id: 'uri_core', title: _('SIP URI')},
          {id: 'uri_pjsip', title: _('TEL URI')},
        ]}, _('Способ отбработки перенаправений'));
        this.mailboxes = new widgets.select(parent, {id: 'mailboxes', default: [], options: []}, _('Почтовые ящики'));
        this.mwi_subscribe_replaces_unsolicited = new widgets.checkbox(parent, {id: 'mwi_subscribe_replaces_unsolicited', value: false}, _('Подписка на MWI запрещает неконтролируемые уведомелния'));
        this.voicemail_extension = new widgets.input(parent, {id: 'voicemail_extension', default: '', value: ''}, _('Номер телефона голосовой почты'));
        this.moh_suggest = new widgets.select(parent, {id: 'moh_suggest', default: 'default', options: []}, _('Класс музыки на удержании'));
        this.outbound_auth = new widgets.select(parent, {id: 'outbound_auth', default: '', options: []}, _('Исходящая аутентификация'));
        this.outbound_proxy = new widgets.input(parent, {id: 'outbound_proxy', default: '', value: ''}, _('Исходящий SIP прокси'));
        this.rewrite_contact = new widgets.checkbox(parent, {id: 'rewrite_contact', value: false}, _('Заменять адрес контакта на фактический адрес источника'));
        this.rtp_ipv6 = new widgets.checkbox(parent, {id: 'rtp_ipv6', value: false}, _('Включить поддержку IPv6 RTP'));
        this.rtp_symmetric = new widgets.checkbox(parent, {id: 'rtp_symmetric', value: false}, _('Симметричный режим RTP'));
        this.send_diversion = new widgets.checkbox(parent, {id: 'send_diversion', value: true}, _('Отправлять информацию о номере-приемнике вызова'));
        this.send_pai = new widgets.checkbox(parent, {id: 'send_pai', value: false}, _('Отправлять техническую инфомацию об абоненте'));
        this.send_rpid = new widgets.checkbox(parent, {id: 'send_rpid', value: false}, _('Отправлять техническую информацию о собеседнике'));
        this.rpid_immediate = new widgets.checkbox(parent, {id: 'rpid_immediate', value: false}, _('Немедленно отправлять изменения состояния звонка'));
        this.timers = new widgets.select(parent, {id: 'timers', default: 'yes', options: [
          {id: 'no', title: _('Не использовать')},
          {id: 'yes', title: _('Разрешить согласование')},
          {id: 'required', title: _('Требовать согласование')},
          {id: 'always', title: _('Всегда использовать')},
        ]}, _('Использовать таймеры таймаутов'));
        this.timers_min_se = new widgets.input(parent, {id: 'timers_min_se', default: '90', value: ''}, _('Минимальное значение таймаута сессии'));
        this.timers_sess_expires = new widgets.input(parent, {id: 'timers_sess_expires', default: '1800', value: ''}, _('Максимальное значение таймаута сессии'));
        this.transport = new widgets.select(parent, {id: 'transport', default: '', options: []}, _('Использовать транспорт'));
        this.trust_id_inbound = new widgets.checkbox(parent, {id: 'trust_id_inbound', value: false}, _('Принимать техническую информацию для входящих звонков'));
        this.trust_id_outbound = new widgets.checkbox(parent, {id: 'trust_id_outbound', value: false}, _('Принимать техническую информацию для исходящих звонков'));
        this.use_ptime = new widgets.checkbox(parent, {id: 'use_ptime', value: false}, _('Использовать запрошенный оконечным оборудованием интервал отправки RTP'));
        this.use_avpf = new widgets.checkbox(parent, {id: 'use_avpf', value: false}, _('Разрешать объединение Audio и Video потоков'));
        this.force_avp = new widgets.checkbox(parent, {id: 'force_avp', value: false}, _('Требовать объединение Audio и Video потоков'));
        this.media_use_received_transport = new widgets.checkbox(parent, {id: 'media_use_received_transport', value: false}, _('Отправлять ответы с транспорта-приемника'));
        this.media_encryption = new widgets.select(parent, {id: 'media_encryption', default: 'no', options: [
          {id: 'no', title: _('Отключено')},
          {id: 'sdes', title: _('SRTP')},
          {id: 'dtls', title: _('DTLS')},
        ]}, _('Шифрование мультимедиа'));
        this.media_encryption_optimistic = new widgets.checkbox(parent, {id: 'media_encryption_optimistic', value: false}, _('Не требовать обязательного шифрования'));
        this.g726_non_standard = new widgets.checkbox(parent, {id: 'g726_non_standard', value: false}, _('Включить альтернативный кодек G.726'), _('Использует тип упаковки кадра AAL2, альтернативой можно указать кодек G.726/AAL2'));
        this.inband_progress = new widgets.checkbox(parent, {id: 'inband_progress', value: false}, _('Генерировать гудки в аудиотракт'));
        this.call_group = new widgets.input(parent, {id: 'call_group', default: [], value: ''}, _('Группа вызовов'), _('Нумерованная группа вызовов, все номера групп к которым принадлежит данный абонент могут использоваться для перехвата вызова'));
        this.pickup_group = new widgets.input(parent, {id: 'pickup_group', default: [], value: ''}, _('Группа перехвата'), _('Нумерованная группа перехвата указывают номера групп вызовов перехват которых разрешен данному абоненту'));
        this.named_call_group = new widgets.input(parent, {id: 'named_call_group', default: [], value: ''}, _('Именованная группа вызовов'), _('Именованная группа вызовов, все номера групп к которым принадлежит данный абонент могут использоваться для перехвата вызова'));
        this.named_pickup_group = new widgets.input(parent, {id: 'named_pickup_group', default: [], value: ''}, _('Именованная группа перехвата'), _('Именованная группа перехвата указывают номера групп вызовов перехват которых разрешен данному абоненту'));
        this.device_state_busy_at = new widgets.input(parent, {id: 'device_state_busy_at', default: '0', value: ''}, _('Пороговое число каналов'), _('Задает число каналов при которым сигнализация устройства выдает статус "Занят"'));
        this.t38_udptl = new widgets.checkbox(parent, {id: 't38_udptl', value: false}, _('Включить поддержку T.38 UDPTL'));
        this.t38_udptl_ec = new widgets.select(parent, {id: 't38_udptl_ec', default: 'none', options: [
          {id: 'none', title: _('Отсутствует')},
          {id: 'fec', title: _('Нарастающим итогом')},
          {id: 'redudancy', title: _('Избыточность')},
        ]}, _('Режим коррекции ошибок T.38'));
        this.t38_udptl_maxdatagram = new widgets.input(parent, {id: 't38_udptl_maxdatagram', default: '0', value: ''}, _('Максимальный размер кадра T.38'));
        this.fax_detect = new widgets.checkbox(parent, {id: 'fax_detect', value: false}, _('Автоопределение несущей факса (CNG)'));
        this.fax_detect_timeout = new widgets.input(parent, {id: 'fax_detect_timeout', default: '0', value: ''}, _('Таймаут автоопределения несущей факса с момента ответа'));
        this.t38_udptl_nat = new widgets.checkbox(parent, {id: 't38_udptl_nat', value: false}, _('Поддержка NAT для T.38'), _('Отправлять пакеты T.38 по адресу источника'));
        this.t38_udptl_ipv6 = new widgets.checkbox(parent, {id: 't38_udptl_ipv6', value: false}, _('Поддержка IPv6 для T.38'));
        this.tone_zone = new widgets.select(parent, {id: 'tone_zone', default: '', options: []}, _('Стандарт тоновых сигналов'));
        this.language = new widgets.input(parent, {id: 'language', default: '', value: ''}, _('Язык уведомлений'));
        this.one_touch_recording = new widgets.checkbox(parent, {id: 'one_touch_recording', value: false}, _('Поддержка записи с горячей клавиши'));
        this.record_on_feature = new widgets.input(parent, {id: 'record_onoff_feature', default: 'automixmon', options: [
          {id: 'automixmon', title: _('Общий аудиотракт')},
          {id: 'automon', title: _('Входящий и исходящий аудиотракты')},
        ]}, _('Функция записи'));
        this.rtp_engine = new widgets.input(parent, {id: 'rtp_engine', default: 'asterisk', options: [
          {id: 'asterisk', title: _('Unicast')},
          {id: 'multicast', title: _('Multicast')},
        ]}, _('Механизм RTP'));
        this.allow_transfer = new widgets.checkbox(parent, {id: 'allow_transfer', value: true}, _('Разрешить трансфер (перевод) вызова'));
        this.user_eq_phone = new widgets.checkbox(parent, {id: 'user_eq_phone', value: false}, _('Добавлять атрибут user=phone в SIP URI'));
        this.moh_passthrough = new widgets.checkbox(parent, {id: 'moh_passthrough', value: false}, _('Передавать состояние линии "На удержании"'));
        this.sdp_owner = new widgets.input(parent, {id: 'sdp_owner', default: '-', value: ''}, _('Строковое значение имени пользователя в SDP'));
        this.sdp_session = new widgets.input(parent, {id: 'sdp_session', default: 'Asterisk', value: ''}, _('Строковое значение идентификатора сессии в SDP'));
        this.tos_audio = new widgets.input(parent, {id: 'tos_audio', default: '0', value: ''}, _('DSCP TOS биты класса трафика для Аудио'));
        this.tos_video = new widgets.input(parent, {id: 'tos_video', default: '0', value: ''}, _('DSCP TOS биты класса трафика для Видео'));
        this.cos_audio = new widgets.input(parent, {id: 'cos_audio', default: '0', value: ''}, _('Приоритет RTP пакетов для Аудио'));
        this.cos_video = new widgets.input(parent, {id: 'cos_video', default: '0', value: ''}, _('Приоритет RTP пакетов для Видео'));
        this.allow_subscribe = new widgets.checkbox(parent, {id: 'allow_subscribe', value: true}, _('Разрашить подписку на события именения состояний линий связи'));
        this.sub_min_expiry = new widgets.input(parent, {id: 'sub_min_expiry', default: '0', value: ''}, _('Минимально допустимое время действия подписки на события'));
        this.from_user = new widgets.input(parent, {id: 'from_user', default: '', value: ''}, _('Логин пользователя вседа передаваемый в поле From'));
        this.mwi_from_user = new widgets.input(parent, {id: 'mwi_from_user', default: '', value: ''}, _('Логин пользователя вседа передаваемый в поле From для событий MWI'));
        this.from_domain = new widgets.input(parent, {id: 'from_domain', default: '', value: ''}, _('Домен пользователя, всегда передаваемый в поле From'));
        this.dtls_verify = new widgets.select(parent, {id: 'dtls_verify', default: 'no', options: [
          {id: 'no', title: _('Не проверять')},
          {id: 'fingerprint', title: _('Только отпечаток')},
          {id: 'certificate', title: _('Только сертификат')},
          {id: 'yes', title: _('Отпечаток и сертификат')},
        ]}, _('Режим проверки пользовательского сертификата'));
        this.dtls_rekey = new widgets.input(parent, {id: 'dtls_rekey', default: '0', value: ''}, _('Интервал обновления ключей для сессии RTP'));
        this.dtls_auto_generate_cert = new widgets.checkbox(parent, {id: 'dtls_auto_generate_cert', value: false}, _('Автоматически генерировать пользовательский сертификат'));
        this.dtls_cert_file = new widgets.input(parent, {id: 'dtls_cert_file', default: '', value: ''}, _('Сертификат пользователя'));
        this.dtls_cert_file_btn = new widgets.file(this.dtls_cert_file, {value: ''}, _('Обзор...'));
        this.dtls_private_key = new widgets.input(parent, {id: 'dtls_private_key', default: '', value: ''}, _('Закрытый ключ сертификата пользователя'));
        this.dtls_private_key_btn = new widgets.file(this.dtls_private_key, {value: ''}, _('Обзор...'));
        this.dtls_cipher = new widgets.select(parent, {id: 'dtls_cipher', multiple: true, default: [], options: []}, _('Алгоритмы шифрования'));
        this.dtls_ca_file = new widgets.input(parent, {id: 'dtls_ca_file', default: '', value: ''}, _('Сертификат УЦ'));
        this.dtls_ca_path = new widgets.input(parent, {id: 'dtls_ca_path', default: '', value: ''}, _('Путь к сертфикатам УЦ'));
        this.dtls_setup = new widgets.select(parent, {id: 'dtls_setup', default: '', options: [
          {id: 'active', title: _('Требовать использование шифрование')},
          {id: 'passive', title: _('Разрешать использование шифрования')},
          {id: 'actpass', title: _('Разрешать и предлагать использование шифрования')},
        ]}, _('Режим работы шифрования'));
        this.dtls_fingerprint = new widgets.input(parent, {id: 'dtls_fingerprint', default: 'SHA-1', value: ''}, _('Отпечаток сертификата'));
        this.srtp_tag_32 = new widgets.checkbox(parent, {id: 'srtp_tag_32', value: false}, _('Использовать 32х байтные метки sRTP вместо 80и-байтных'));
        this.set_var = new widgets.collection(parent, {id: 'set_var', default: [], options: [], entry: 'keyvalue/entry'}, _('Канальные переменные абонента'));
        this.message_context = new widgets.select(parent, {id: 'message_context', default: '', options: []}, _('Контекст для приема сообщений'));
        this.accountcode = new widgets.input(parent, {id: 'accountcode', default: '', value: ''}, _('Идентификатор абонента для биллинга'));
        this.incoming_call_offer_pref = new widgets.select(parent, {id: 'incoming_call_offer_pref', default: 'local', options: [
          {id: 'local', title: _('Пересечение набора кодеков с приоритетом локальных')},
          {id: 'local_first', title: _('Самый первый поддерживаемый локальный кодек')},
          {id: 'remote', title: _('Пересечение набора кодеков с приоритетом терминала')},
          {id: 'remote_first', title: _('Самый первый поддерживаемый кодек терминала')},
        ]}, _('Приоритет выбора кодеков для входящего звонка'));
        this.outgoing_call_offer_pref = new widgets.select(parent, {id: 'outgoing_call_offer_pref', default: 'remote', options: [
          {id: 'local', title: _('Пересечение набора кодеков с приоритетом локальных')},
          {id: 'local_first', title: _('Самый первый поддерживаемый локальный кодек')},
          {id: 'remote', title: _('Пересечение набора кодеков с приоритетом терминала')},
          {id: 'remote_first', title: _('Самый первый поддерживаемый кодек терминала')},
        ]}, _('Приоритет выбора кодека для исходящего звонка'));
        this.rtp_keepalive = new widgets.input(parent, {id: 'rtp_keepalive', default: '0', value: ''}, _('Таймаут между кадрами тишины (белого шума) для RTP'));
        this.rtp_timeout = new widgets.input(parent, {id: 'rtp_timeout', default: '0', value: ''}, _('Максимальная длительность звонка без входящего аудио'));
        this.rtp_timeout_hold = new widgets.input(parent, {id: 'rtp_timeout_hold', default: '0', value: ''}, _('Максимальная длительность удержания без входящего аудио'));
        this.acl = new widgets.select(parent, {id: 'acl', default: [], options: [], multiple: true}, _('Списки прав доступа'));
        this.permit = new widgets.input(parent, {id: 'permit', default: [], value: ''}, _('Разрешенные адреса подключения'));
        this.contact_acl = new widgets.input(parent, {id: 'contact_acl', default: [], value: ''}, _('Списки прав доступа для контактоы'));
        this.contact_permit = new widgets.input(parent, {id: 'contact_permit', default: [], value: ''}, _('Разрешенные адреса подключения контактов'));
        this.subscribe_context = new widgets.input(parent, {id: 'subscribe_context', default: '', value: ''}, _('Контекст для мониторинга состояний абонентов'));
        this.contact_user = new widgets.input(parent, {id: 'contact_user', default: '', value: ''}, _('Принудительно указать имя пользователя в заголовке Contact'));
        this.asymmetric_rtp_codec = new widgets.checkbox(parent, {id: 'asymmetric_rtp_codec', value: false}, _('Асимметричное кодирование RTP'));
        this.rtcp_mux = new widgets.checkbox(parent, {id: 'rtcp_mux', value: false}, _('Разрешить микширование Аудио и Видео RTP потоков'));
        this.refer_blind_progress = new widgets.checkbox(parent, {id: 'refer_blind_progress', value: true}, _('Уведомлять о прогрессе слепого перевода'), _('Некоторые абонентские терминалы Mitel/Aastra требуют обязательного подтверждения слепого перевода'));
        this.notify_early_inuse_ringing = new widgets.checkbox(parent, {id: 'notify_early_inuse_ringing', value: false}, _('Всегда отправлять состояние звонка'), _('Отправлять ли состояние линий для новых звонков, если абонент уже использует другие каналы'));
        this.max_audio_streams = new widgets.input(parent, {id: 'max_audio_streams', default: '1', value: ''}, _('Максимальное число потоков Аудио'));
        this.max_video_streams = new widgets.input(parent, {id: 'max_video_streams', default: '1', value: ''}, _('Максимальное число потоков Видео'));
        this.bundle = new widgets.checkbox(parent, {id: 'bundle', value: false}, _('Объединять виды RTP в один порт'));
        this.webrtc = new widgets.checkbox(parent, {id: 'webrtc', value: false}, _('Включить поддержку WebRTC'), _('Автоматически включает поддержку микширования и мультиплексирования RTP, DTLS и автоматический выпуск сертификатов'));
        this.incoming_mwi_mailbox = new widgets.select(parent, {id: 'incoming_mwi_mailbox', default: '', options: []}, _('Имя почтового ящика для приема входящих голосовых сообщений'));
        this.follow_early_media_fork = new widgets.checkbox(parent, {id: 'follow_early_media_fork', value: true}, _('Следовать адресу назначения в RTP при смене маршрута'));
        this.accept_multiple_sdp_answers = new widgets.checkbox(parent, {id: 'accept_multiple_sdp_answers', value: false}, _('Разрешать несколько пакетов SDP'));
        this.suppress_q850_reason_headers = new widgets.checkbox(parent, {id: 'suppress_q850_reason_headers', value: false}, _('Подавить загловки Q.850 со статусом соединения'));
        this.ignore_183_without_sdp = new widgets.checkbox(parent, {id: 'ignore_183_without_sdp', value: false}, _('Предотвратить передачу кода 183 с PRI каналов'));
        this.stir_shaken = new widgets.input(parent, {id: 'stir_shaken', default: "!no", value: ''}, _('Проверять и отправлять загловок Identity'));

      }

      function setValue(data) {
        this.data = data;
        this.parent.setValue(data);
      }

      function getValue() {
        let result = {};
        return result;
      }

      function setMode(mode) {
        switch(mode) {
          case 'basic': {
          } break;
          case 'advanced': {
          } break;
          case 'expert': {
          } break;
        }
      }

    </script>
    <?php
  }

}

?>
