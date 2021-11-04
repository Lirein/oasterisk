<?php

namespace sip;

class SIPDriverSettings extends \core\ViewModule {

  public static function getLocation() {
    return 'settings/drivers/sip';
  }

  public static function getMenu() {
    return (object) array('name' => 'SIP', 'prio' => 1);
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function reloadConfig() {
    $this->ami->send_request('Command', array('Command' => 'sip reload'));
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    static $generalparams = '{
      "allowguest": "!yes",
      "match_auth_username": "!no",
      "allowoverlap": "dtmf",
      "allowtransfer": "!yes",
      "realm": "",
      "domainasrealm": "!no",
      "recordonfeature": "mixmon",
      "recordofffeature": "mixmon",
      "bindaddr": "0.0.0.0",
      "udpbindaddr": "",
      "tcpbindaddr": "",
      "tlsbindaddr": "",
      "tcpenable": "!no",
      "tlsenable": "!no",      
      "tcpauthtimeout": "",
      "tcpauthlimit": "",
      "websocket_enabled": "!no",
      "websocket_write_timeout": "",
      "srvlookup": "!yes",       
      "pedantic": "!yes",
      "tos_sip": "cs3",
      "tos_audio": "ef",
      "tos_video": "af41",
      "tos_text": "af41",
      "cos_sip": "3",
      "cos_audio": "5",
      "cos_video": "4",
      "cos_text": "3",
      "maxexpiry": "3600",
      "minexpiry": "60",
      "defaultexpiry": "120",
      "submaxexpiry": "3600",
      "subminexpiry": "60",
      "mwiexpiry": "3600",
      "maxforwards": "70",
      "qualifyfreq": "60",
      "qualifygap": "100",
      "qualifypeers": "1",
      "keepalive": "60",
      "notifymimetype": "text/plain",
      "buggymwi": "!no",
      "mwi_from": "",
      "vmexten": "asterisk",
      "preferred_codec_only": "!yes",
      "autoframing": "!no",
      "mohinterpret": "default",
      "mohsuggest": "default",
      "parkinglot": "default",
      "language": "ru",
      "tonezone": "utc",
      "relaxdtmf": "!no",
      "trustrpid": "!no",
      "sendrpid": "!no",
      "rpid_update": "!no",
      "trust_id_outbound": "!no",
      "prematuremedia": "!yes",
      "progressinband": "!no",
      "useragent": "Asterisk PBX",
      "promiscredir": "!no",
      "usereqphone": "!no",
      "dtmfmode": "rfc2833",
      "compactheaders": "!no",
      "videosupport": "no",
      "textsupport": "!no",
      "maxcallbitrate": "384",
      "authfailureevents": "!no",
      "alwaysauthreject": "!yes",
      "auth_options_requests": "!no",
      "accept_outofcall_message": "!yes",
      "outofcall_message_context": "messages",
      "auth_message_requests": "!yes",
      "g726nonstandard": "!no",
      "outboundproxy": "",
      "supportpath": "!no",
      "rtsavepath": "!no",      
      "matchexternaddrlocally": "!no",
      "dynamic_exclude_static": "!no",
      "contactdeny": [],       
      "contactpermit": [],       
      "contactacl": "",
      "rtp_engine": "asterisk",
      "regcontext": "",
      "regextenonqualify": "!no",
      "legacy_useroption_parsing": "!no",
      "send_diversion": "!yes",
      "shrinkcallerid": "!yes",
      "use_q850_reason": "!no",
      "refer_addheaders": "!yes",
      "autocreatepeer": "!no",
      "tlsdontverifyserver": "!no",
      "tlscipher": "",
      "tlsclientmethod": "sslv2",
      "t1min": "100",
      "timert1": "500",
      "timerb": "32000",
      "rtptimeout": "60",
      "rtpholdtimeout": "300",
      "rtpkeepalive": "0",
      "session-timers": "accept",
      "session-expires": "1800",
      "session-minse": "90",
      "session-refresher": "uac",
      "sipdebug": "!no",
      "recordhistory": "!no",
      "dumphistory": "!no",
      "allowsubscribe": "!yes",
      "subscribecontext": "default",
      "notifyringing": "!yes",
      "notifyhold": "!no",
      "notifycid": "!no",
      "callcounter": "!yes",
      "t38pt_udptl": "!no",
      "faxdetect": "!no",
      "registertimeout": "20",
      "registerattempts": "0",
      "register_retry_403": "!no",
      "mwi": [],
      "localnet": [],
      "externaddr": "",
      "externtcpport": "",
      "externtlsport": "",
      "externhost": "",
      "externrefresh": "180",
      "media_address": "",
      "subscribe_network_change_event": "!yes",
      "icesupport": "!no",
      "directmedia": "yes",
      "directrtpsetup": "!no",
      "directmediadeny": [],
      "directmediapermit": [],
      "directmediaacl": "",
      "ignoresdpversion": "!no",
      "sdpsession": "Asterisk PBX",
      "sdpowner": "root",
      "encryption": "!no",
      "encryption_taglen": "80",
      "avpf": "!yes",
      "force_avp": "!no",
      "rtcachefriends": "!no",
      "rtsavesysname": "!no",
      "rtupdate": "!yes",
      "rtautoclear": "!yes",
      "ignoreregexpire": "!no",
      "domain": [],
      "allowexternaldomains": "!no",
      "autodomain": "!no",
      "fromdomain": "",
      "snom_aoc_enabled": "!no",
      "jbenable": "!no",
      "jbforce": "!no",
      "jbmaxsize": "200",
      "jbresyncthreshold": "1000",
      "jbimpl": "fixed",
      "jbtargetextra": "40",
      "jblog": "!no",
      "auth": "",
      "transport": ",",
      "context": "default",
      "disallow": [],
      "allow": [],
      "nat": "auto_comedia,auto_force_rport"
    }';

    $ini = new \INIProcessor('/etc/asterisk/sip.conf');
    switch($request) {
      case "get-codecs": {
        $returnData = array();
        $codec = new \core\Codecs();
        $codecs = $codec->getByClass();
        foreach($codecs as $info) {
          $returnData[]=(object) array('text'=>$info->title, 'id'=>$info->name);
        }
        return self::returnResult($returnData);
      } break;
      case "get-transports": {
        $returnData=array((object) array('id' => 'udp', 'text' => 'UDP'));
        if(isset($ini->general->tcpenable)&&$ini->general->tcpenable->getValue()) $returnData[]=(object) array('id' => 'tcp', 'text' => 'TCP');
        if(isset($ini->general->tlsenable)&&$ini->general->tlsenable->getValue()) $returnData[]=(object) array('id' => 'tls', 'text' => 'TLS');
        return self::returnResult($returnData);
      } break;
      case "sipdriver-get": {
        $returnData=$ini->general->getDefaults($generalparams);
        
        if(count($returnData->disallow)==0) {
          $returnData->allow += array('alaw','ulaw','gsm','h263');
        }
        
        $result = self::returnResult($returnData);
      } break;
      case "sipdriver-set": {
        if(self::checkPriv('settings_writer')) {
          $ini = new \INIProcessor('/etc/asterisk/sip.conf');
        
          $ini->general->setDefaults($generalparams, $request_data);
          
          if($ini->save()) {
            $result = self::returnSuccess();
            self::reloadConfig();
          } else {
            $result = self::returnError('danger', 'Невозможно сохранить настройки');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "siptls-get": {
        $profile = new \stdClass();
        $ini = new \INIProcessor('/etc/asterisk/sip.conf');

        $tlscertfile=$ini->general->tlscertfile;
        $certdata=false;
        if(($tlscertfile!='')&&file_exists($tlscertfile)) {
          if($filedata=file_get_contents($tlscertfile)) {
            $certdata=openssl_x509_parse($filedata);
          }
        }
        if($certdata) {
          if(isset($certdata['issuer']['O'])) $profile->issuer=$certdata['issuer']['O'];
          elseif(isset($certdata['issuer']['emailAddress'])) $profile->issuer=$certdata['issuer']['emailAddress'];
          elseif(isset($certdata['issuer']['CN'])) $profile->issuer=$certdata['issuer']['CN'];
          else $profile->issuer='';
          if(isset($certdata['subject']['O'])) $profile->subject=$certdata['subject']['O'];
          elseif(isset($certdata['subject']['emailAddress'])) $profile->subject=$certdata['subject']['emailAddress'];
          elseif(isset($certdata['subject']['CN'])) $profile->subject=$certdata['subject']['CN'];
          else $profile->subject='';
          if(isset($certdata['subject']['L'])) $profile->location=$certdata['subject']['L'];
          elseif(isset($certdata['subject']['ST'])) $profile->location=$certdata['subject']['ST'];
          else $profile->location='';
          $profile->validfrom=$certdata['validFrom_time_t'];
          $profile->validto=$certdata['validTo_time_t'];
          if(isset($certdata['subject']['CN'])) $profile->cn=$certdata['subject']['CN'];
          else $profile->cn='';
          $profile->alias=array();
          if(isset($certdata['extensions']['subjectAltName'])) {
            $profile->alias=explode(',',$certdata['extensions']['subjectAltName']);
            foreach($profile->alias as $key => $value) {
              if(strpos($value,':')!==false) $profile->alias[$key]=substr($value,strpos($value,':')+1);
            }
          }
        } else {
          $profile->issuer='Сертификат не указан';
          $profile->subject='';
          $profile->location='';
          $profile->validfrom='';
          $profile->validto='';
          $profile->cn='';
          $profile->alias=array();
        }
        $result = self::returnResult($profile);
      } break;
      case "siptls-set": {
        //$_POST = $request_data
        if(self::checkPriv('settings_writer')) {
          $ini = new \INIProcessor('/etc/asterisk/sip.conf');
          $result = self::returnError('danger', 'Неизвестная ошибка');
          if(isset($request_data->import->certorpfx)) {
            $cacertdata='';
            $certdata='';
            $pkeydata='';
            if(isset($request_data->import->privatekey)) {
              $pkeydata=file_get_contents($request_data->import->privatekey->tmp_name);
              $certdata=file_get_contents($request_data->import->certorpfx->tmp_name);
              if(isset($request_data->import->cacert)) $cacertdata=file_get_contents($request_data->import->cacert->tmp_name);
            } else {
              $p12data=file_get_contents($request_data->import->certorpfx->tmp_name);
              $pass="";
              $certs = array();
              if(isset($request_data->import->pass)) $pass=$request_data->import->pass;
              if(openssl_pkcs12_read($p12data, $certs, $pass)) {
                $certdata=$certs['cert'];
                $pkeydata=$certs['pkey'];
                if(isset($certs['extracerts'])) {
                  foreach($certs['extracerts'] as $cert) {
                    $cacertdata.=$cert;
                  }
                }
              } else {
                if(!isset($request_data->import->pass)) $result=self::returnResult(true);
                else $result = self::returnError('danger', 'Неверный пинкод ключевого контейнера');
                return $result;
              }
            }
            
            if($certdata&&$pkeydata) {
              $cert=openssl_x509_read($certdata);
              $pkey=openssl_pkey_get_private($pkeydata);
              if(openssl_x509_check_private_key($cert, $pkey)) {
                if(openssl_x509_export_to_file($cert, '/etc/asterisk/cert.pem')&&(openssl_pkey_export_to_file($pkey, '/etc/asterisk/pkey.pem'))) {
                  $ini->general->tlscertfile = '/etc/asterisk/cert.pem'; // ''
                  $ini->general->tlsprivatekey = '/etc/asterisk/pkey.pem'; // ''
                  $ini->general->tlscafile = ''; // ''
                  $ini->general->tlscapath = '/etc/ssl/certs';
                  if($cacertdata) {
                    if(file_put_contents('/etc/asterisk/ca.pem', $cacertdata)) {
                      $ini->general->tlscafile = '/etc/asterisk/ca.pem'; // ''
                    }
                  }
                  $result = self::returnSuccess();
                } else {
                  $result = self::returnError('danger', 'Невозможно сохранить сертификат в файл');
                }
              } else {
                $result = self::returnError('warning', 'Закрытый ключ не соответствует сертификату');
              }
            } else {
              $result = self::returnError('warning', 'Не переданы сертификат и закрытый ключ');
            }
          } elseif(isset($request_data->request->certpem)||(isset($request_data->request->cert))) {
            if(isset($request_data->request->certpem)) {
              $certdata=$request_data->request->certpem;
            } else {
              $certdata=file_get_contents($request_data->request->cert->tmp_name);
            }
            if($certdata) {
              $cert=openssl_x509_read($certdata);
              $pkey=file_get_contents('/etc/asterisk/pkey.pem');
              if(openssl_x509_check_private_key($cert, $pkey)) {
                if(openssl_x509_export_to_file($cert, '/etc/asterisk/cert.pem')) {
                  $ini->general->tlscertfile = '/etc/asterisk/cert.pem'; // ''
                  $ini->general->tlsprivatekey = '/etc/asterisk/pkey.pem'; // ''
                  $ini->general->tlscafile = ''; // ''
                  $ini->general->tlscapath = '/etc/ssl/certs';
                  $result = self::returnSuccess();
                } else {
                  $result = self::returnError('danger', 'Невозможно сохранить сертификат в файл');
                }
              } else {
                $result = self::returnError('warning', 'Закрытый ключ не соответствует сертификату');
              }
            } else {
              $result = self::returnError('warning', 'Не передан сертификат');
            }
          } elseif(isset($request_data->pem->certpem)&&(isset($request_data->pem->privatekeypem))) {
            $certdata=$request_data->pem->certpem;
            $pkeydata=$request_data->pem->privatekeypem;
            if($certdata&&$pkeydata) {
              $pkey=openssl_pkey_get_private($pkeydata);
              $cert=openssl_x509_read($certdata);
              if(openssl_x509_check_private_key($cert, $pkey)) {
                if(openssl_x509_export_to_file($cert, '/etc/asterisk/cert.pem')&&(openssl_pkey_export_to_file($pkey, '/etc/asterisk/pkey.pem'))) {
                  $ini->general->tlscertfile = '/etc/asterisk/cert.pem'; // ''
                  $ini->general->tlsprivatekey = '/etc/asterisk/pkey.pem'; // ''
                  $ini->general->tlscafile = ''; // ''
                  $ini->general->tlscapath = '/etc/ssl/certs';
                  if(isset($request_data->pem->cacertpem)) {
                    if(file_put_contents('/etc/asterisk/ca.pem', $request_data->pem->cacertpem)) {
                      $ini->general->tlscafile = '/etc/asterisk/ca.pem'; // ''
                    }
                  }
                  $result = self::returnSuccess();
                } else {
                  $result = self::returnError('danger', 'Невозможно сохранить сертификат в файл');
                }
              } else {
                $result = self::returnError('warning', 'Закрытый ключ не соответствует сертификату');
              }
            } else {
              $result = self::returnError('warning', 'Не переданы сертификат и закрытый ключ');
            }
          } else {
            $result = self::returnError('danger', 'Недостаточно данных для сохранения сертификата');
          }

          if($result->status == 'success') {
            $ini->save();
            $this->reloadConfig();
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "siptls-request": {
        if(self::checkPriv('settings_writer')) {
          $key=openssl_pkey_new(array("private_key_bits" => 4096, "private_key_type" => OPENSSL_KEYTYPE_RSA));
          $license = new \core\CoreLicense();
          $licenseInfo = $license->info();
          $dn = array(
            "countryName" => $licenseInfo->license->country,
            "stateOrProvinceName" => $licenseInfo->license->region,
            "localityName" => $licenseInfo->license->location,
            "organizationName" => $licenseInfo->license->org,
            "commonName" => $_SERVER['SERVER_NAME'],
          );
          $req=openssl_csr_new($dn, $key, array('digest_alg' => 'sha256'));
          openssl_csr_export($req, $csrout);
          if(openssl_pkey_export_to_file($key, '/etc/asterisk/pkey.pem')) {
            $result = self::returnResult($csrout);
          } else {
            $result = self::returnError('danger', 'Невозможно сохранить закрытый ключ');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      }
    }
    return $result;
  }

  public function scripts() {
    ?>
      <script>
      widgets.domains=class domainsWidget extends baseWidget {
        constructor(parent, data, label, hint) {
          super(parent,data,label,hint);
          this.node = document.createElement('div');
          this.node.widget = this;
          this.node.className='form-group row';
          if(this.label) {
            this.label.className = 'col-12 form-label';
            this.node.appendChild(this.label);
          }
          this.context_data=[];
          this.context_select = {theme: "bootstrap", minimumInputLength: 1, ajax: {
              transport: function(params, success, failure) {
                // fitering if params.data.q available
                var items = [];
                if (params.data && params.data.q) {
                  items = this.sender.context_data.filter(function(item) {
                      return new RegExp(params.data.q).test(item.text);
                  });
                }
                var promise = new Promise(function(resolve, reject) {
                  resolve({results: items});
                });
                promise.then(success);
                promise.catch(failure);
              },
              sender: this
            },
            allowClear: true,
            placeholder: 'По умолчанию',
            dropdownAutoWidth: true,
          };
          this.content = document.createElement('div');
          this.content.className='form-group col-12';
          this.node.appendChild(this.content);
          this.setParent(parent);
          this.inputdiv = this.createDomain('asterisk', '');
          this.inputdiv.classList.add('col-12');
          this.inputdiv.widget=this;
          this.inputdiv.btn.classList.remove('oi-minus');
          this.inputdiv.btn.classList.add('oi-plus');
          this.inputdiv.btn.onclick=this.newDomain;
          this.node.appendChild(this.inputdiv);
          this.inputdiv.contextselect.style.width='100%';
          $(this.inputdiv.contextselect).select2(this.context_select).next().css('width', '100%');
          if((typeof data != 'undefined') && data ) this.setValue(data);
        }
        inputKeypress(sender) {
          var BACKSPACE = 8;
          var DELETE = 46;
          var TAB = 9;
          var LEFT = 37 ;
          var UP = 38 ;
          var RIGHT = 39 ;
          var DOWN = 40 ;
          var END = 35 ;
          var HOME = 35 ;
          var result = false;
          // Checking backspace and delete  
          if(sender.keyCode == BACKSPACE || sender.keyCode == DELETE || sender.keyCode == TAB 
              || sender.keyCode == LEFT || sender.keyCode == UP || sender.keyCode == RIGHT || sender.keyCode == DOWN)  {
              result = true;
          }
          if(sender.target.pattern) {
            var expr=RegExp('^'+sender.target.pattern+'$','g');
            result = expr.test(sender.target.value.substr(0,sender.target.selectionStart)+sender.key+sender.target.value.substr(sender.target.selectionEnd));
          } else result = true;
          return result;
        }
        createDomain(domain, context) {
          var inputdiv = document.createElement('div');
          inputdiv.className = 'input-group';
          inputdiv.input = document.createElement('input');
          inputdiv.input.type='text';
          inputdiv.input.className='form-control col-6';
          inputdiv.input.pattern='[0-9a-z._-]+';
          inputdiv.input.value=domain;
          inputdiv.input.widget=this;
          inputdiv.input.entry=inputdiv;
          inputdiv.input.onkeypress=this.inputKeypress;
          inputdiv.appendChild(inputdiv.input);
          inputdiv.sub = document.createElement('span');
          inputdiv.sub.className='col-6 row';
          inputdiv.appendChild(inputdiv.sub);
          inputdiv.contextselect = document.createElement('select');
          inputdiv.contextselect.className='custom-select';
/*          var opt = document.createElement('option');
          opt.value='';
          opt.textContent=_('default','По умолчанию');
          inputdiv.contextselect.appendChild(opt);*/
          if(context!='') {
            var opt = document.createElement('option');
            opt.value=context;
            opt.textContent=context;
            inputdiv.contextselect.appendChild(opt);
          }
          inputdiv.contextselect.value=context;
          inputdiv.sub.appendChild(inputdiv.contextselect);
          inputdiv.subbtn = document.createElement('span');
          inputdiv.subbtn.className='input-group-append';
          inputdiv.appendChild(inputdiv.subbtn);
          inputdiv.btn = document.createElement('button');
          inputdiv.btn.className='btn btn-secondary oi oi-minus';
          inputdiv.btn.style.top='0px';
          inputdiv.btn.onclick=this.removeDomain;
          inputdiv.btn.widget=this;
          inputdiv.btn.entry=inputdiv;
          inputdiv.subbtn.appendChild(inputdiv.btn);
          return inputdiv;
        }
        getDomain(sender) {
          var domaindata = {domain: '', context: ''};
          domaindata.domain = sender.input.value;
          domaindata.context = sender.contextselect.value;
          return domaindata;
        }
        removeDomain(sender) {
          var result = true;
          sender.target.entry.parentNode.removeChild(sender.target.entry);
          return false;
        }
        newDomain(sender) {
          var result = true;
          var data = sender.target.widget.getDomain(sender.target.entry);
          for(var i=0; i<sender.target.widget.content.childNodes.length; i++) {
            if(sender.target.widget.content.childNodes[i].input.value==data.domain) return false;
          }
          var entry=sender.target.widget.createDomain(data.domain, data.context);
          entry.widget=sender.target.widget;
          entry.input.disabled=true;
          sender.target.widget.content.appendChild(entry);
          entry.contextselect.style.width='100%';
          $(entry.contextselect).select2(sender.target.widget.context_select).next().css('width', '100%');
          return result;
        }
        setValue(avalue) {
          if(typeof avalue == 'string') {
            avalue = {value: [avalue]};
          } else if((typeof avalue == 'object') && (avalue instanceof Array)) {
            avalue = {value: avalue};
          } else if(avalue===null) {
            avalue = {value: []};
          }
          if((typeof avalue.id == 'undefined')&&(this.content.id == '')) avalue.id='domains-'+Math.random().toString(36).replace(/[^a-z]+/g, '').substr(2);
          if(typeof avalue.id != 'undefined') {
            this.content.id=avalue.id;
            if(this.label) this.label.htmlFor=this.content.id;
          }
          if(typeof avalue.context_data != 'undefined') {
            this.context_data = avalue.context_data;
          }
          if(typeof avalue.value != 'undefined') {
            this.content.textContent='';
            for(var i=0; i<avalue.value.length; i++) {
              if(typeof avalue.value[i] == 'string') {
                var list=avalue.value[i].split(',');
                if(typeof list[1] != 'undefined') {
                  avalue.value[i]={domain: list[0], context: list[1]};
                } else {
                  avalue.value[i]={domain: list[0], context: ''};
                }
              }
              var entry=this.createDomain(avalue.value[i].domain, avalue.value[i].context);
              entry.input.disabled=true;
              entry.widget=this;
              this.content.appendChild(entry);
              entry.contextselect.style.width='100%';
              $(entry.contextselect).select2(this.context_select).next().css('width', '100%');
            }
          }
          return true;
        }
        disable() {
          var nodes = this.node.querySelectorAll('input');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=true;
          }
          nodes = this.node.querySelectorAll('select');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=true;
          }
          nodes = this.node.querySelectorAll('button');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=true;
          }
          return true;
        }
        disabled() {
          return this.inputdiv.btn.disabled;
        }
        enable() {
          var nodes = this.node.querySelectorAll('input');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=false;
          }
          nodes = this.node.querySelectorAll('select');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=false;
          }
          nodes = this.node.querySelectorAll('button');
          for(var i=0; i<nodes.length; i++) {
            nodes[i].disabled=false;
          }
          return true;
        }
        getID() {
          return this.content.id;
        }
        getValue() {
          var result=[];
          for(var i=0; i<this.content.childNodes.length; i++) {
            var entry=this.getDomain(this.content.childNodes[i])
            if(entry.context!='') {
              result.push(entry.domain+','+entry.context);
            } else {
              result.push(entry.domain);
            }
          }
          return result;
        }
      }

      var sip_driver=null;
      var sip_codecs=[];

      function updateSIPCodecs() {
        sendRequest('get-codecs').success(function(data) {
          sip_codecs=data;
          card.setValue({codecs: {value: data, clean:true}});
          card.setValue({context: {value: context_data, clean: true}, outofcall_message_context: {value: context_data, clean: true}, domain: {context_data: context_data}});

        });
      }

      function updateSIPTransports() {
        sendRequest('get-transports').success(function(data) {
          card.setValue({transport: {value: data, clean:true}});
        });
      }

      function updateSIPDriver() {
        sendRequest('sipdriver-get').success(function(data) {
          sip_driver=data;
          var codecs = [];
          if(sip_driver.disallow) for(var i=0; i<sip_driver.disallow.length; i++) {
            if(sip_driver.disallow[i]=='all') {
              codecs=[];
            }
          }
          if(sip_driver.allow) for(var i=0; i<sip_driver.allow.length; i++) {
            if(sip_driver.allow[i]=='all') {
              codecs=[];
              for(var j=0; j<sip_codecs.length; j++) {
                codecs.push(sip_codecs[j].id);
              }
            } else {
              j=findById(sip_codecs,sip_driver.allow[i]);
              if(j!=-1) codecs.push(sip_driver.allow[i]);
            }
          }
          for(param in sip_driver) {
            if((typeof sip_driver[param]=='object')&&(sip_driver[param] instanceof Array)) sip_driver[param]={value: sip_driver[param], clean: true};
          }
          sip_driver.codecs=codecs;
          sip_driver.transport.clean=false;
          card.setValue(sip_driver);
        });
      }

      function updateSIPTLS() {
        sendRequest('siptls-get').success(function(data) {
          if(data.issuer=='') data.issuer=_('noissuer','Издатель не определен');
          if(data.subject=='') data.sublect=_('nosubject','Субъект не определен');
          if(data.validfrom!='') data.validfrom=new moment(data.validfrom*1000).format('DD.MM.YYYY');
          if(data.validto!='') data.validto=new moment(data.validto*1000).format('DD.MM.YYYY');
          data.alias={value: data.alias, clean: true};
          tlsdialog.setValue({info: data});
        });
      }

      function sendSIPTLS(sender, pass) {
        var data = sender.getValue();
        if(typeof pass != 'undefined') data.import.pass = pass;
        passdialog.setValue({pass: ''});
        sendRequest('siptls-set', data).success(function(ret) {
          if(ret) {
            tlsdialog.hide();
            passdialog.show();
          } else {
            tlsdialog.hide();
            showalert('success', 'Параметры TLS успешно сохранены');
            updateSIPTLS();
          }
          return false;
        });
        return false;
      }

      function sendSIPTLSRequestPass(sender) {
        sendSIPTLS(tlsdialog, sender.getValue().pass);
        return true;
      }

      function sendSIPTLSRequest(sender) {
        sendRequest('siptls-request').success(function(data) {
          var blob = new Blob([data], {type: "application/x-pem-file"});
          saveAs(blob, "siptls.req");
        });
        return true;
      }

      function sendSIPDriverData(sender) {
        var data = card.getValue();
        data.disallow=['all'];
        data.allow=data.codecs;
        delete data.codecs;
        sendRequest('sipdriver-set', data).success(function() {
          showalert('success','Параметры канального драйвера успешно сохранены');
          updateSIPDriver();
          return false;
        });
      }
 
      function sendSIPDriver() {
        var proceed = false;
        showdialog('Сохранение параметров канального дравера','Вы уверены, что хотите сохранить параметры канального драйвера?',"warning",['Yes','No'],function(e) {
          if(e=='Yes') {
            proceed=true;
          }
          if(proceed) {
            sendSIPDriverData();
          }
        });
      }

      <?php
      if(self::checkPriv('settings_writer')) {
?>
      function sbapply() {
        sendSIPDriver();
      }
<?php
      } else {
?>
       var sbapply = null;
<?php
      }
?>

      var card = null;
      var tlsdialog = null;

      $(function () {
        var obj=null;
        var section = null;
        var tabs = null;
        var tab = null;

        passdialog = new widgets.dialog(rootcontent, null, _("pfx_pass","Запрос пароля сертификата"));
        passdialog.onSave=sendSIPTLSRequestPass;
        passdialog.closebtn.setLabel(_('cancel','Отмена'));
        passdialog.savebtn.setLabel(_('apply','Принять'));
        obj = new widgets.input(passdialog, {id: 'pass', password: true}, _("pass","Пароль"));
        passdialog.simplify();

        tlsdialog = new widgets.dialog(rootcontent, null, _("tls_parameters","Параметры TLS"));
        tlsdialog.onSave=sendSIPTLS;
        tabs = new widgets.tabs(tlsdialog, null);
        tab = new widgets.tab(tabs, 'info', _("info","Информация"));
        obj = new widgets.input(tab, {id: 'issuer'}, _("cert_issuer","Издатель"));
        obj = new widgets.input(tab, {id: 'subject'}, _("cert_subject","Субъект"));
        obj = new widgets.input(tab, {id: 'location'}, _("cert_location","Адрес"));

        var fromobj = new widgets.input(tab, {id: 'validfrom'}, _("rtp_port_from","Срок действия с"));
        fromobj.inputdiv.classList.remove('col-md-7');
        fromobj.inputdiv.classList.add('col-md-3');
        obj = new widgets.input(tab, {id: 'validto'}, _("rtp_port_to","по"));
        obj.label.classList.add('col-md-1');
        obj.label.classList.add('pl-md-0');
        obj.label.classList.add('pr-md-0');
        obj.label.style['text-align']='center';
        obj.inputdiv.classList.remove('col-md-7');
        obj.inputdiv.classList.add('col-md-3');
        obj.node.className='';
        fromobj.node.appendChild(obj.label);
        fromobj.node.appendChild(obj.inputdiv);

        obj = new widgets.input(tab, {id: 'cn'}, _("cert_cn","Каноническое имя"));
        obj = new widgets.list(tab, {id: 'alias'}, _("cert_alias","Синонимы"));
        tab.disable();

        tab = new widgets.tab(tabs, 'import', _("import","Импорт"));
        obj = new widgets.file(tab, {id: 'privatekey', accept: 'application/pkcs8'}, _("cert_priv","Закрытый ключ"));
        obj = new widgets.file(tab, {id: 'cacert', accept: 'application/x-x509-ca-cert,application/x-pem-file'}, _("cert_ca","Сертификат УЦ"));
        obj = new widgets.file(tab, {id: 'certorpfx', accept: 'application/x-x509-user-cert,application/x-pkcs12,application/x-pem-file'}, _("cert_orpfx","Сертификат/PFX"));

        tab = new widgets.tab(tabs, 'request', _("request","Запрос"));
        obj = new widgets.section(tab, null);
        obj.node.classList.add('text-center');
        obj = new widgets.button(obj, null, _("cert_request","Сгенерировать запрос"));
        obj.onClick=sendSIPTLSRequest;
        obj = new widgets.file(tab, {id: 'cert', accept: 'application/x-x509-user-cert,application/x-pem-file'}, _("cert","Сертификат"));
        obj = new widgets.text(tab, {id: 'certpem', rows: 5}, _("cert_pem","Сертификат в формате PEM"));

        tab = new widgets.tab(tabs, 'pem', _("pem","Формат PEM"));
        obj = new widgets.text(tab, {id: 'privatekeypem', rows: 3}, _("cert_priv","Закрытый ключ"));
        obj = new widgets.text(tab, {id: 'cacertpem', rows: 3}, _("cert_ca","Сертификат УЦ"));
        obj = new widgets.text(tab, {id: 'certpem', rows: 3}, _("cert","Сертификат"));

        card = new widgets.section(rootcontent,null);
        var cols = new widgets.section(card,null);
        cols.node.classList.add('row');
        var subcard1 = new widgets.columns(cols,2)
        subcard1 = new widgets.section(subcard1,null,_("generic_settings", "Основные параметры"));
        var subcard2 = new widgets.columns(cols,2);
        subcard3 = new widgets.section(subcard2,null,_("security_settings", "Параметры безопасности"));
        subcard2 = new widgets.section(subcard2,null,_("network_settings", "Сетевые параметры"));

        obj = new widgets.select(subcard1, {id: 'context', value: context_data, clean: true, search: true}, "Контекст по умолчанию", "Наименование контекста, в который попадает обработка всех входящих вызовов.<br>В указанном контексте должен быть определен набираемый абонентом номер или шаблон такого номера.");
        obj = new widgets.list(subcard1, {id: 'codecs', value: [], clean: true, checkbox: true, sorted: true}, "Поддерживыемые кодеки", "Устанавливает перечень и порядок согласования кодеков.<br>Для изменения порядка согласования кодеков используется перетаскивание.<br>Порядок не может быть изменен для унаследованных кодеков.");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'faxdetect', value: false}, "Включить поддержку факса");
        obj = new widgets.checkbox(subcard1, {single: true, id: 'accept_outofcall_message', value: false}, "Включить текстовые сообщения");
        obj = new widgets.select(subcard1, {id: 'videosupport', value: [{id: 'no', text: 'Выключить'}, {id: 'yes', text: 'Если пригласил клиент'}, {id: 'always', text: 'Всегда поддерживать'}]}, "Поддержка видео");
        obj = new widgets.select(subcard1, {id: 'outofcall_message_context', value: context_data, clean: true, search: true}, "Контекст сообщений", "Наименование контекста, в который попадает обработка всех текстовых сообщений.<br>В указанном контексте должен быть определен номер назначения текстового сообщения или шаблон такого номера.");

        obj = new widgets.input(subcard2,{id: 'bindaddr', value: '0.0.0.0', placeholder: '0.0.0.0', pattern: '([0-9]{1,3}\\.){0,3}[0-9]{0,3}'},_("bindaddr","Общий интерфейс"));
        obj = new widgets.input(subcard2,{id: 'udpbindaddr', value: '0.0.0.0', placeholder: '0.0.0.0[:5060]', pattern: '([0-9]{1,3}\\.){0,3}[0-9]{0,3}(|:[0-9]{0,5})'},_("udp_bindaddr","Интерфейс для UDP"));
        obj = new widgets.checkbox(subcard2, {single: true, id: 'tcpenable', value: false}, "Включить поддержку TCP");
        obj = new widgets.input(subcard2,{id: 'tcpbindaddr', value: '0.0.0.0', placeholder: '0.0.0.0[:5060]', pattern: '([0-9]{1,3}\\.){0,3}[0-9]{0,3}(|:[0-9]{0,5})'},_("tcp_bindaddr","Интерфейс для TCP"));
        obj = new widgets.checkbox(subcard2, {single: true, id: 'tlsenable', value: false}, "Включить поддержку TLS");
        obj = new widgets.input(subcard2,{id: 'tlsbindaddr', value: '0.0.0.0', placeholder: '0.0.0.0[:5061]', pattern: '([0-9]{1,3}\\.){0,3}[0-9]{0,3}(|:[0-9]{0,5})'},_("tls_bindaddr","Интерфейс для TLS"));
        obj = new widgets.select(subcard2, {id: 'tlsclientmethod', value: [{id: 'tlsv1', text: 'TLSv1.1'}, {id: 'sslv2', text: 'SSLv2'}, {id: 'sslv3', text: 'SSLv3'}]},_("tlc_clientmethod","Метод соединения TLS"),"В целях безопасности рекомендуется выбирать TLSv1.1, так как в протоколах SSLv2/SSLv3 обнаружены критические уязвимости. В целях обратной совместимости в доверенной среде может быть указан менее стойкий алгоритм, например, для связи с АТС/шлюзом <i><b>Avaya</i></b>.");
        section = new widgets.section(subcard2,null);
        section.node.classList.add('form-group');
        section.node.classList.add('text-center');
        obj = new widgets.button(section,{class: 'secondary'},_("tls_settings","Параметры TLS..."));
        obj.onClick = function(sender) {
          tlsdialog.show();
        }

        obj = new widgets.list(subcard2,{id: 'transport', checkbox: true, sorted: true, value: []},_("transport","Поддерживыемые протоколы"));
        obj = new widgets.input(subcard2,{id: 'extipsip', value: '0.0.0.0', pattern: '([0-9]{1,3}\\.){0,3}[0-9]{0,3}(|:[0-9]{0,5})'},_("external_sip_ip","Внешний IP (SIP)"));

        obj = new widgets.iplist(subcard2, {id: 'localnet'}, _("local_net","Локальные адреса"), _("local_net_hint","Укажите адреса, подключение с которых считается локальным. Адреса вне этого списка считаются работающими во внешней сети или за NAT."));
        obj = new widgets.select(subcard2, {id: 'nat', value: [{id: '', text: 'Не задана', checked: true},{id: 'no', text: 'Отключена' },{id: 'auto_force_rport', text: 'Порт источника SIP (авто)'},{id: 'auto_comedia', text: 'Порт источника RTP (авто)'},{id: 'auto_force_rport,auto_comedia', text: 'Порт источника SIP потом RTP (авто)'},{id: 'force_rport', text: 'Порт источника SIP'},{id: 'comedia', text: 'Порт источника RTP'},{id: 'force_rport,comedia', text: 'Порт источника SIP потом RTP'}]}, _("nat","Трансляция адресов"), _("nat_forclients_hint","Управляет адресом и портом назначения RTP трафика и информации в SDP во время согласования мультимедийного потока.<br><b>«Не задана»</b> — наследуется от родительского шаблона или из параметров протокола SIP.<br><b>«Отключена»</b> — в SDP всегда передается локальный адрес, а RTP передается по адресу и номеру порта, указываемом в SDP при согласовании мультимедийного потока.<br><b>«Порт источника SIP»</b> — в качестве адреса и порта назначения RTP трафика используется адрес и порт, с которого принят пакет согласования мультимедийного потока.<br><b>«Порт источника RTP»</b> — в качестве адреса и порта назначения RTP трафика используется адрес и порт, с которого принимается входящий RTP трафик по согласованному мультимедийному потоку.<br><b>«Порт источника SIP потом RTP»</b> — в качестве адреса и порта назначения RTP трафика используется адрес и порт, с которого принят пакет согласования мультимедийного потока. После приема входящего RTP трафика по согласованному мультимедийному потоку порт меняется на номер порта, с которого был передан входящий RTP.<br><b><i>Примечение:</i></b> Режимы <i>«авто»</i> включаются, если IP адрес, с которого получен SDP не принадлежит локальному диапазону адресов, при этом в исходящем SDP указывается внешний публичный адрес."));
        obj = new widgets.select(subcard2, {id: 'directmedia', value: [{id: 'no', text: 'Запретить'}, {id: 'yes', text: 'Разрешить'}, {id: 'nonat', text: 'Запретить за NAT'}, {id: 'outgoing', text: 'После соединения'}, {id: 'update', text: 'Метод UPDATE'}, {id: 'update,nonat', text: 'Метод UPDATE за NAT'}]},_("directmedia","Использование P2P RTP"),"Устанавливает режим прямого перенаправления RTP трафика между SIP абонентами.<br>Отключите, если требуется запись разговоров или СОРМ");
        obj = new widgets.checkbox(subcard2, {single: true, id: 'directrtpsetup', value: false}, "Устанавливать P2P RTP при согласовании звонка","Автоматически согласует адреса и порты абонентских терминалов при согласовании вызова без использования REINVITE или UPDATE запроса.");
        obj = new widgets.iplist(subcard2, {id: 'directmediadeny'}, _("directmdeia_deny","Запретить P2P RTP"), _("directmediadeny_hint","Указывается список IP адресов для запрета Peer2Peer RTP трафика.<br>Укажите 0.0.0.0/0 для запрета Peer2Peer всем абонентским терминалам."));
        obj = new widgets.iplist(subcard2, {id: 'directmediapermit'}, _("directmedia_permit","Разрешить P2P RTP"), _("directmediapermit_hint","Укажите писок IP адресов для разрешения Peer2Peer RTP трафика.<br>Правила на разрешения перекрывают собой правила на запрет Peer2Peer RTP"));

        obj = new widgets.checkbox(subcard3, {single: true, id: 'alwaysauthreject', value: false}, "Всегда отвечать неверным паролем","По умолчанию RFC SIP протокола декларирует разные ответы при входящей регистрации для отсутствующих абонентов и неверного пароля.<br>Включение данной настройки позволяет всегда отвечать ошибкой \"Неверный пароль\", если указанного абонента не существует.");
        obj = new widgets.checkbox(subcard3, {single: true, id: 'authfailureevents', value: false}, "Регистрировать события отказ в доступе","Создает уведомление в форме <i>события</i> об отказе в доступе вида peerstatus: rejected. Может использоваться внешними механизмами аудита");
        obj = new widgets.checkbox(subcard3, {single: true, id: 'auth_options_requests', value: false}, "Требовать аутентификации запроса OPTIONS","По умолчанию аутентификации требуют только запросы REGISTER и INVITE.");
        obj = new widgets.checkbox(subcard3, {single: true, id: 'auth_message_requests', value: false}, "Аутентификация отправки текстовых сообщений");
        obj = new widgets.checkbox(subcard3, {single: true, id: 'dynamic_exclude_static', value: false}, "Запретить регистрацию с фиксированных IP адресов", "Запрещает проходить регистрацию абонентских терминалов с IP адреса, для которого уже задан абонент с таким статическим IP в настройках. Позволяет запретить регистрацию со стороны провайдера или шлюза.");
        obj = new widgets.checkbox(subcard3, {single: true, id: 'tlsdontverifyserver', value: false}, "Не проверять сертификат сервера TLS","Позволяет подключаться к другим АТС по протоколу TLS с самоподписанными и истекшими сертификатами. Допускает отличающееся имя домена и/или IP адреса узла от указанного в сертификате.");
        obj = new widgets.checkbox(subcard3, {single: true, id: 'encryption', value: false}, "Шифровать мультимедийный поток","Включает режим принудительного шифрования мультимедийного потока sRTP. Требует поддержки абонентскими терминалами, не обеспечивает защиты при использовании протоколов TCP и UDP.");
        obj = new widgets.checkbox(subcard3, {single: true, id: 'allowexternaldomains', value: false}, "Разрешить звонки на нелокальные домены","Разрешает принимать запросы INVITE и REFER на нелокальные домены.<br>Если отключено, то прямые звонки SIP2SIP невозможны.");
        obj = new widgets.checkbox(subcard3, {single: true, id: 'autodomain', value: false}, "Автоматически определить домен","Автоматически считает IP адрес сетевого интерфейса и FQDN хоста как имя домена.");
        obj = new widgets.domains(subcard3, {id: 'domain', context_data: context_data}, _("domains","Обслуживаемые домены"), _("domains_hint","Определяет перечень обслуживаемых доменов и контекстов, в которых производится обработка анонимных вызовов для каждого домена.<br>Если контекст не указан, используется <i>Контекст по умолчанию.</i>"));
        obj = new widgets.iplist(subcard3, {id: 'contactdeny'}, _("contact_deny","Запретить регистрацию"), _("contactdeny_hint","Указывается список IP адресов для запрета входящей регистрации абонентских терминалов.<br>Укажите 0.0.0.0/0 для запрета регистрации всем абонентским терминалам."));
        obj = new widgets.iplist(subcard3, {id: 'contactpermit'}, _("contact_permit","Разрешить регистрацию"), _("contactdeny_hint","Укажите писок IP адресов для разрешения входящей регистрации абонентских терминалов.<br>Правила на разрешения перекрывают собой правила на запрет регистрации."));

<?php
        if(!self::checkPriv('settings_writer')) printf("card.disable();\n");
?>
        sidebar_apply(sbapply);
        updateSIPCodecs();
        updateSIPTransports();
        updateSIPDriver();
        updateSIPTLS();
      })
      </script>
    <?php
  }

  public function render() {
    ?>
    <?php
  }

}

?>