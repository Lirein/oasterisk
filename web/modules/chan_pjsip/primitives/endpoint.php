<?php

namespace pjsip;

class Endpoint extends \module\Subject {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \pjsip\Endpoints $collection
   */
  public static $collection = 'pjsip\\Endpoints';

  private $ini;

  private $title = null;

  private static $defaultparams = '{
    "100rel": "yes",
    "aggregate_mwi": "!yes",
    "disallow": [],
    "allow": [],
    "incoming_offer_codec_prefs": "prefer: pending, operation: intersect, keep: all, transcode: allow",
    "outgoing_offer_codec_prefs": "prefer: pending, operation: union, keep: all, transcode: allow",
    "incoming_answer_codec_prefs": "prefer: pending, operation: intersect, keep: all, transcode: allow",
    "outgoing_answer_codec_prefs": "prefer: pending, operation: intersect, keep: all, transcode: allow",
    "allow_overlap": "!yes",
    "aors": ",",
    "auth": ",",
    "callerid": "",
    "callerid_privacy": "allowed_not_screened",
    "callerid_tag": "",
    "context": "default",
    "direct_media_glare_mitigation": "none",
    "direct_media_method": "invite",
    "trust_connected_line": "!yes",
    "send_connected_line": "!yes",
    "connected_line_method": "invite",
    "direct_media": "!yes",
    "disable_direct_media_on_nat": "!no",
    "dtmf_mode": "rfc4733",
    "media_address": "",
    "bind_rtp_to_media_address": "!no",
    "force_rport": "!yes",
    "ice_support": "!no",
    "identify_by": ",username,ip",
    "redirect_method": "user",
    "mailboxes": ",",
    "mwi_subscribe_replaces_unsolicited": "!no",
    "voicemail_extension": "",
    "moh_suggest": "default",
    "outbound_auth": ",",
    "outbound_proxy": "",
    "rewrite_contact": "!no",
    "rtp_ipv6": "!no",
    "rtp_symmetric": "!no",
    "send_diversion": "!yes",
    "send_pai": "!no",
    "send_rpid": "!no",
    "rpid_immediate": "!no",
    "timers_min_se": "90",
    "timers": "yes",
    "timers_sess_expires": "1800",
    "transport": "",
    "trust_id_inbound": "!no",
    "trust_id_outbound": "!no",
    "use_ptime": "!no",
    "use_avpf": "!no",
    "force_avp": "!no",
    "media_use_received_transport": "!no",
    "media_encryption": "no",
    "media_encryption_optimistic": "!no",
    "g726_non_standard": "!no",
    "inband_progress": "!no",
    "call_group": ",",
    "pickup_group": ",",
    "named_call_group": ",",
    "named_pickup_group": ",",
    "device_state_busy_at": 0,
    "t38_udptl": "!no",
    "t38_udptl_ec": "none",
    "t38_udptl_maxdatagram": 0,
    "fax_detect": "!no",
    "fax_detect_timeout": 0,
    "t38_udptl_nat": "!no",
    "t38_udptl_ipv6": "!no",
    "tone_zone": "",
    "language": "",
    "one_touch_recording": "!no",
    "record_on_feature": "automixmon",
    "record_off_feature": "automixmon",
    "rtp_engine": "asterisk",
    "allow_transfer": "!yes",
    "user_eq_phone": "!no",
    "moh_passthrough": "!no",
    "sdp_owner": "-",
    "sdp_session": "Asterisk",
    "tos_audio": 0,
    "tos_video": 0,
    "cos_audio": 0,
    "cos_video": 0,
    "allow_subscribe": "!yes",
    "sub_min_expiry": 0,
    "from_user": "",
    "mwi_from_user": "",
    "from_domain": "",
    "dtls_verify": "no",
    "dtls_rekey": 0,
    "dtls_auto_generate_cert": "!no",
    "dtls_cert_file": "",
    "dtls_private_key": "",
    "dtls_cipher": ",",
    "dtls_ca_file": "",
    "dtls_ca_path": "",
    "dtls_setup": "",
    "dtls_fingerprint": "SHA-1",
    "srtp_tag_32": "!no",
    "set_var": [],
    "message_context": "",
    "accountcode": "",
    "incoming_call_offer_pref": "local",
    "outgoing_call_offer_pref": "remote",
    "rtp_keepalive": 0,
    "rtp_timeout": 0,
    "rtp_timeout_hold": 0,
    "acl": ",",
    "deny": ",",
    "permit": ",",
    "contact_acl": ",",
    "contact_deny": ",",
    "contact_permit": ",",
    "subscribe_context": "",
    "contact_user": "",
    "asymmetric_rtp_codec": "!no",
    "rtcp_mux": "!no",
    "refer_blind_progress": "!yes",
    "notify_early_inuse_ringing": "!no",
    "max_audio_streams": 1,
    "max_video_streams": 1,
    "bundle": "!no",
    "webrtc": "!no",
    "incoming_mwi_mailbox": "",
    "follow_early_media_fork": "!yes",
    "accept_multiple_sdp_answers": "!no",
    "suppress_q850_reason_headers": "!no",
    "ignore_183_without_sdp": "!no",
    "stir_shaken": "!no"
  }';

  public function __construct(string $id = null) {
    $this->ini = self::getINI('/etc/asterisk/pjsip.conf');
    parent::__construct($id);
    $defaultparams = json_decode(self::$defaultparams);
    if(isset($this->ini->$id)) {
      $v = $this->ini->$id;
      if(isset($v->type)&&($v->type=='endpoint')) {
        $this->ini->$id->normalize(self::$defaultparams);
        foreach($defaultparams as $param => $value) {
          $this->data->$param = $this->ini->$id->$param->getValue();
        }    
        $this->interLock($id);
        $aors = array();
        foreach($this->data->aors as $aor) {
          $entry = AORs::find($aor);
          if($entry !== null) $aors[] = $entry;
        }
        $this->data->aors = $aors;
        $auth = array();
        foreach($this->data->auth as $eauth) {
          $entry = Auths::find($eauth);
          if($entry !== null) $auth[] = $entry;
        }
        $this->data->auth = $auth;
        $auth = array();
        foreach($this->data->outbound_auth as $eauth) {
          $entry = Auths::find($eauth);
          if($entry !== null) $auth[] = $entry;
        }
        $this->data->outbound_auth = $auth;
        $this->data->transport = Transports::find($this->data->transport);
        $acls = array();
        foreach($this->data->acl as $acl) {
          $entry = \security\ACLs::find($acl);
          if($entry !== null) $alcs[] = $entry;
        }
        $this->data->acl = $acls;
        $acls = array();
        foreach($this->data->contact_acl as $acl) {
          $entry = \security\ACLs::find($acl);
          if($entry !== null) $alcs[] = $entry;
        }
        $this->data->contact_acl = $acls;
        $this->data->context = \dialplan\Dialplan::find($this->data->context);
        $this->data->message_context = \dialplan\Dialplan::find($this->data->message_context);
        $this->data->subscribe_context = \dialplan\Dialplan::find($this->data->subscribe_context);
        $this->interUnlock($id);
        $this->data->templates = $v->getTemplateNames();
        $this->data->istemplate = $v->isTemplate();
        $this->old_id = $id;
        $this->title = $this->ini->$id->getComment();
      }
    } 
    if(!$this->title) $this->title = $id;
    $this->id = $id;
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {
    parent::__destruct();
    unset($this->ini);
  }

  public function __isset($property){
    if($property=='title') return true;
    return parent::__isset($property);
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
    if($property=='title') return $this->title;
    return parent::__get($property);
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    if($property=='id') {
      if($this->id == $this->name) {
        $this->title = $value;
      }
      $this->id = $value;
      return true;
    } 
    if($property=='title') {
      $this->title = $value;
      return true;
    } 
    if($property=='100rel') {
      switch($value) {
        case 'no':
        case 'required': {
          $this->data->$property = $value;
        } break;
        default: {
          $this->data->$property = 'yes';
        } break;
      }
      return true;
    }
    if($property=='callerid_privacy') {
      switch($value) {
        case 'allowed_passed_screen':
        case 'allowed_failed_screen':
        case 'allowed':
        case 'prohib_not_screened':
        case 'prohib_passed_screen':
        case 'prohib_failed_screen':
        case 'prohib':
        case 'unavailable':
        case 'required': {
          $this->data->$property = $value;
        } break;
        default: {
          $this->data->$property = 'allowed_not_screened';
        } break;
      }
      return true;
    }
    if($property=='direct_media_glare_mitigation') {
      switch($value) {
        case 'outgoing':
        case 'incoming': {
          $this->data->$property = $value;
        } break;
        default: {
          $this->data->$property = 'none';
        } break;
      }
      return true;
    }
    if(($property=='connected_line_method')||($property=='direct_media_method')) {
      switch($value) {
        case 'update': {
          $this->data->$property = $value;
        } break;
        case 'reinvite': {
          $this->data->$property = 'invite';
        } break;
        default: {
          $this->data->$property = 'invite';
        } break;
      }
      return true;
    }
    if($property=='dtmf_mode') {
      switch($value) {
        case 'inband':
        case 'info':
        case 'auto':
        case 'auto_info': {
          $this->data->$property = $value;
        } break;
        default: {
          $this->data->$property = 'rfc4733';
        } break;
      }
      return true;
    }
    if($property=='identify_by') {
      if(is_array($value)) {
        $this->data->$property = array();
        foreach($value as $ventry) {
          switch($ventry) {
            case 'auth_username':
            case 'ip': {
              $this->data->$property[] = $value;
            } break;
            default: {
              $this->data->$property[] = 'username';
            } break;
          }
        }
        $this->data->$property = array_unique($this->data->$property);
      } else {
        $this->data->$property = array('username', 'ip');
      }
      return true;
    }
    if($property=='redirect_method') {
      switch($value) {
        case 'uri_core':
        case 'uri_pjsip': {
          $this->data->$property = $value;
        } break;
        default: {
          $this->data->$property = 'user';
        } break;
      }
      return true;
    }
    if($property=='timers') {
      switch($value) {
        case 'no':
        case 'required':
        case 'always': {
          $this->data->$property = $value;
        } break;
        case 'forced': {
          $this->data->$property = 'always';
        } break;
        default: {
          $this->data->$property = 'yes';
        } break;
      }
      return true;
    }
    if($property=='media_encryption') {
      switch($value) {
        case 'sdes':
        case 'dtls': {
          $this->data->$property = $value;
        } break;
        default: {
          $this->data->$property = 'no';
        } break;
      }
      return true;
    }
    if($property=='t38_udptl_ec') {
      switch($value) {
        case 'fec':
        case 'redundancy': {
          $this->data->$property = $value;
        } break;
        default: {
          $this->data->$property = 'none';
        } break;
      }
      return true;
    }
    if(($property=='record_on_feature')||($property=='record_off_feature')) {
      switch($value) {
        case 'automon': {
          $this->data->$property = $value;
        } break;
        default: {
          $this->data->$property = 'automixmon';
        } break;
      }
      return true;
    }
    if($property=='dtls_verify') {
      switch($value) {
        case 'yes':
        case 'fingerprint':
        case 'certificate': {
          $this->data->$property = $value;
        } break;
        default: {
          $this->data->$property = 'no';
        } break;
      }
      return true;
    }
    if($property=='dtls_fingerprint') {
      switch($value) {
        case 'SHA-256': {
          $this->data->$property = $value;
        } break;
        default: {
          $this->data->$property = 'SHA-1';
        } break;
      }
      return true;
    }
    if($property=='rtp_engine') {
      switch($value) {
        case 'multicast': {
          $this->data->$property = $value;
        } break;
        default: {
          $this->data->$property = 'asterisk';
        } break;
      }
      return true;
    }
    if($property=='incoming_call_offer_pref') {
      switch($value) {
        case 'local_first':
        case 'remote':
        case 'remote_first': {
          $this->data->$property = $value;
        } break;
        default: {
          $this->data->$property = 'local';
        } break;
      }
      return true;
    }
    if($property=='outgoing_call_offer_pref') {
      switch($value) {
        case 'local':
        case 'local_first':
        case 'remote_first': {
          $this->data->$property = $value;
        } break;
        default: {
          $this->data->$property = 'remote';
        } break;
      }
      return true;
    }
    if(($property=='incoming_offer_codec_prefs')||($property=='outgoing_offer_codec_prefs')||($property=='incoming_answer_codec_prefs')||($property=='outgoing_answer_codec_prefs')) {
      $values = explode(',', $value);
      $keyvalues = array();
      foreach($values as $entry) {
        $keyval = explode(':', $entry);
        if(count($keyval)==2) {
          $keyvalues[trim($keyval[0])] = trim($keyval[1]);
        }
      }
      if(empty($keyvalues['prefer'])) $keyvalues['prefer'] = 'pending';
      if(empty($keyvalues['keep'])) $keyvalues['keep'] = 'all';
      if(empty($keyvalues['transcode'])) $keyvalues['transcode'] = 'allow';
      switch($keyvalues['prefer']) {
        case 'configured': {
          $keyvalues['prefer'] = 'configured';
        } break;
        default: {
          $keyvalues['prefer'] = 'pending';          
        } break;
      }
      if($property=='outgoing_offer_codec_prefs') {
        if(empty($keyvalues['operation'])) $keyvalues['operation'] = 'union';
        switch($keyvalues['operation']) {
          case 'intersect': break;
          case 'only_preferred': break;
          case 'only_nonpreferred': break;
          default: {
            $keyvalues['operation'] = 'union';          
          } break;
        }
      } else {
        if(empty($keyvalues['operation'])) $keyvalues['operation'] = 'intersect';
        switch($keyvalues['operation']) {
          case 'only_preferred': break;
          case 'only_nonpreferred': break;
          default: {
            $keyvalues['operation'] = 'intersect';          
          } break;
        }
      }
      switch($keyvalues['keep']) {
        case 'first': break;
        default: {
          $keyvalues['keep'] = 'all';          
        } break;
      }
      switch($keyvalues['transcode']) {
        case 'prevent': break;
        default: {
          $keyvalues['transcode'] = 'allow';          
        } break;
      }
      $this->data->$property = sprintf('prefer: %s, operation: %s, keep: %s, transcode: %s', $keyvalues['prefer'], $keyvalues['operation'], $keyvalues['keep'], $keyvalues['transcode']);
    }
    return parent::__set($property, $value);
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save() {
    $entry = $this->id;
    if(!$entry) return false;
    if($this->old_id!==null) {
      if($this->id!=$this->old_id) {
        self::$collection::rename($this);
        $oldname = $this->old_id;
        $this->ini->$entry = $this->ini->$oldname; //Перемещаем секцию под новым именем
        $this->ini->$entry->setName($entry);
        unset($this->ini->$oldname);
      } else {
        self::$collection::change($this);
      }
    } else { //Инициализируем секцию
      self::$collection::add($this);
      $this->ini->$entry->normalize(self::$defaultparams);
    }
    $this->ini->$entry->type='endpoint';
    foreach($this->data as $property => $value) {
      if($value instanceof \module\Subject) {
        $this->ini->$entry->$property = $value->old_id;  
      } else if(is_array($value)) {
        $entries = array();
        foreach($value as $ventry) {
          if($ventry instanceof \module\Subject) {
            $entries[] = $ventry->old_id;
          } else {
            $entries[] = $ventry;
          }
        }
        $this->ini->$entry->$property = $entries;
      } else {
        $this->ini->$entry->$property = $value;
      }
    }
    if($this->title == $this->id) {
      $this->ini->$entry->setComment('');
    } else {
      $this->ini->$entry->setComment($this->title);
    }
    $this->old_id = $this->id;
    return $this->ini->save();
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    $entry = $this->old_id;
    if(isset($this->ini->$entry)) {
      unset($this->ini->$entry);
      $this->ini->save();
      return parent::delete();
    }
    return false;
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    return $this->ami->send_request('Command', array('Command' => 'pjsip reload res_pjsip.so'))!==false;
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast() {
    $keys = parent::cast();
    $keys->title = $this->title;
    return $keys;
  }
    
  /**
   * Устанавливает все свойства новыми значениями
   *
   * @param stdClass $request_data Объект со свойствами - ключ→значение 
   */
  public function assign($request_data){
    parent::assign($request_data);
    foreach($request_data as $key => $value) {
      if($key == 'title') $this->title = $value;
    }
    return true;
  }

}

?>
