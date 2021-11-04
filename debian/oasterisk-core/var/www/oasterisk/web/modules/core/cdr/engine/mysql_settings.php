<?php

namespace core;

class MysqlCdrEngineSettings extends CdrEngineSettings {

  private static $defaultsettings = '{
    "hostname": "localhost",
    "table": "cdr",
    "dbname": "cdrdb",
    "password": "asterisk",
    "user": "asterisk",
    "port": 3306,
    "sock": ""
  }';

  private static $mysql = '{
    "hostname": "",
    "dbname": "",
    "table": "",
    "password": "",
    "user": "",
    "port": "3306",
    "sock": "",
    "cdrzone": "UTC",
    "usegmtime": "!no",
    "charset": "",
    "compat": "!no",
    "ssl_ca": "",
    "ssl_cert": "",
    "ssl_key": ""
  }';

  private static $connection = null;

  public function info() {
    return (object) array("id" => 'mysql', "title" => 'База данных MySQL');
  }

  public static function check() {
    $result = true;
    $result &= self::checkModule('cdr', 'mysql', true);
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "createdb": {
        if(isset($request_data->db)&&self::checkPriv('settings_writer')) {
          $this->createDb($request_data->db);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "createtable": {
        if(isset($request_data->table)&&self::checkPriv('settings_writer')) {
          $this->createTable($request_data->table);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "ssl-get": {
        $profile = new \stdClass();
        $ini = new \INIProcessor('/etc/asterisk/cdr_mysql.conf');

        $sslcertfile=$ini->global->ssl_cert;
        $certdata=false;
        if(($sslcertfile!='')&&file_exists($sslcertfile)) {
          if($filedata=file_get_contents($sslcertfile)) {
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
      case "ssl-set": {
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
                  $ini->global->ssl_cert = '/etc/asterisk/cert.pem'; // ''
                  $ini->global->ssl_key = '/etc/asterisk/pkey.pem'; // ''
                  $ini->global->ssl_ca = ''; // ''
                  if($cacertdata) {
                    if(file_put_contents('/etc/asterisk/ca.pem', $cacertdata)) {
                      $ini->global->ssl_ca = '/etc/asterisk/ca.pem'; // ''
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
                  $ini->global->ssl_cert = '/etc/asterisk/cert.pem'; // ''
                  $ini->global->ssl_key = '/etc/asterisk/pkey.pem'; // ''
                  $ini->global->ssl_ca = ''; // ''
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
                  $ini->global->ssl_cert = '/etc/asterisk/cert.pem'; // ''
                  $ini->global->ssl_key = '/etc/asterisk/pkey.pem'; // ''
                  $ini->global->ssl_ca = ''; // ''
                  if(isset($request_data->pem->cacertpem)) {
                    if(file_put_contents('/etc/asterisk/ca.pem', $request_data->pem->cacertpem)) {
                      $ini->global->ssl_ca = '/etc/asterisk/ca.pem'; // ''
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
            //self::reloadConfig();
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "ssl-request": {
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
  
  public function getParams() {
    $result = new \stdClass();
    $ini = new \INIProcessor('/etc/asterisk/cdr_mysql.conf');
    $result = $ini->global->getDefaults(self::$mysql);
    $result->connected = self::checkSettings();
    return $result;
  }

  public function setParams($data) {
    $ini = new \INIProcessor('/etc/asterisk/cdr_mysql.conf');
    $ini->global->setDefaults(self::$mysql, $data);
    return $ini->save();
  }

  public function scripts() {
    ?>
      var ssldialog = null;
      var createtblbtn = null;
      var createdbbtn = null;
      var warninglabel = null;

      loadhandlers.push(function(data) {
        switch(data.connected) {
          case 1: {
            createtblbtn.hide();
            createdbbtn.hide();
            warninglabel.show();
          } break;
          case 2: {
            createtblbtn.hide();
            createdbbtn.show();
            warninglabel.hide();
          } break
          case 3: {
            createtblbtn.show();
            createdbbtn.hide();
            warninglabel.hide();
          } break
          default: {
            createtblbtn.hide();
            createdbbtn.hide();
            warninglabel.hide();
          } break;
        }
      });

      ssldialog = new widgets.dialog(rootcontent, null, _("SSL соединения"));
      ssldialog.onSave=function(sender, pass) {
        var data = sender.getValue();
        if(typeof pass != 'undefined') data.import.pass = pass;
        sendRequest('cdr-action', {id: cdr_id, action: 'ssl-set'}).success(function() {
          ssldialog.hide();
          showalert('success', 'Параметры SSL успешно сохранены');
          updateSSL();
        });
      }
      tabs = new widgets.tabs(ssldialog, null);
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
      obj = new widgets.file(tab, {id: 'cacert', accept: 'application/x-x509-ca-cert,application/x-pem-file'}, _("ssl_ca","path to CA cert"));
      obj = new widgets.file(tab, {id: 'certorpfx', accept: 'application/x-x509-user-cert,application/x-pkcs12,application/x-pem-file'}, _("ssl_cert","path to cert"));
      obj = new widgets.file(tab, {id: 'privatekey', accept: 'application/pkcs8'}, _("ssl_key","path to keyfile"));
      
      tab = new widgets.tab(tabs, 'request', _("request","Запрос"));
      obj = new widgets.section(tab, null);
      obj.node.classList.add('text-center');
      obj = new widgets.button(obj, null, _("cert_request","Сгенерировать запрос"));
      obj.onClick=sendSSLRequest;
      obj = new widgets.file(tab, {id: 'cert', accept: 'application/x-x509-user-cert,application/x-pem-file'}, _("cert","Сертификат"));
      obj = new widgets.text(tab, {id: 'certpem', rows: 5}, _("cert_pem","Сертификат в формате PEM"));
     
      tab = new widgets.tab(tabs, 'pem', _("pem","Формат PEM"));
      obj = new widgets.text(tab, {id: 'privatekeypem', rows: 3}, _("cert_priv","Закрытый ключ"));
      obj = new widgets.text(tab, {id: 'cacertpem', rows: 3}, _("cert_ca","Сертификат УЦ"));
      obj = new widgets.text(tab, {id: 'certpem', rows: 3}, _("cert","Сертификат"));
      
      warninglabel = new widgets.label(card, null, _("Невозможно установить соединение с базой данных"));
      warninglabel.label.classList.add('text-danger');
      warninglabel.hide();
      
      obj = new widgets.input(card, {id: 'hostname'}, "Имя хоста", "Если сервер базы данных запущен на той же машине, что и сервер технологической платформы, для связи с локальным сокетом Unix можно задать имя хоста");
      obj = new widgets.input(card, {id: 'dbname'}, "Имя базы данных");
      createdbbtn = new widgets.button(obj.inputdiv, {class: 'primary'}, _('Создать'));
      createdbbtn.onClick = function() {
        let data = card.getValue();
        sendRequest('cdr-action', {id: cdr_id, action: 'createdb', db: data.dbname}).success(function() {
          loadCDR(cdr_id);
        });    
      }
      var objtab = new widgets.input(card, {id: 'table'}, "Имя таблицы");
      createtblbtn = new widgets.button(objtab.inputdiv, {class: 'primary'}, _('Создать'));
      createtblbtn.onClick = function() {
        let data = card.getValue();
        sendRequest('cdr-action', {id: cdr_id, action: 'createtable', table: data.table}).success(function() {
          loadCDR(cdr_id);
        });      
      }
      obj = new widgets.input(card, {id: 'password', password: true}, "Пароль пользователя");
      obj = new widgets.input(card, {id: 'user'}, "Имя пользователя");
      obj = new widgets.input(card, {id: 'port'}, "Порт", "Если имя хоста задано не как «localhost», тогда будет пытаться присоединиться к указанному порту или использовать порт по умолчанию.");
      obj = new widgets.input(card, {id: 'sock'}, "Сокет файл", "Если имя хоста не задано или задано как «localhost», тогда будет пытаться, подсоединиться к указанному сокет файлу или использовать сокет файл по умолчанию");
      obj = new widgets.select(card, {id: 'cdrzone', value: ['UTC'], search:false}, "Часовой пояс");
      obj = new widgets.checkbox(card, {single: true, id: 'usegmtime', value: false}, "Использовать при журналировании среднее время по Гринчичу");
      obj = new widgets.select(card, {id: 'charset', value: ['utf8'], search:false}, "Кодировка запросов");
      obj = new widgets.checkbox(card, {single: true, id: 'compat', value: false}, "Устанавливать дату публикации записи вместо даты начала вызова");
      
      obj = new widgets.button(card,{class: 'secondary'},_("SSL соединения"));
      obj.onClick = function(sender) {
        ssldialog.show();
      }
      updateSSL();

    <?php
  }

  private static function TableExists($sql, $table) {
    $res = $sql->query("SHOW TABLES LIKE \"".$sql->real_escape_string($table)."\"");
    return $res&&($res->num_rows > 0);
  }

  private static function checkSettings() {
    $ini = new \INIProcessor('/etc/asterisk/cdr_mysql.conf');
    $settings = $ini->global->getDefaults(self::$defaultsettings);
    if(self::$connection == null) {
      try {
        self::$connection = @new \mysqli($settings->hostname, $settings->user, $settings->password, $settings->dbname, $settings->port, $settings->sock);
      } catch(\Exception $e) {
        self::$connection = null;
      }
    }
    if(!self::$connection) {
      self::$connection = null;
      return 1;
    } else {
      if(self::$connection->connect_error!='') {
        self::$connection = null;
        return 1;
      }
      if(!@self::$connection->select_db($settings->dbname)) return 2;
      if(!self::TableExists(self::$connection, $settings->table)) return 3;
    }
    return 0;
  }

  public static function selectable() {
    return self::checkSettings()==0;
  }

  public function enable() {
    $ini = new \INIProcessor('/etc/asterisk/cdr_mysql.conf');
    $ini->global->table = 'cdr';
    $ini->save();
    unset($ini);
  }

  public function disable() {
    $ini = new \INIProcessor('/etc/asterisk/cdr_mysql.conf');
    if (isset($ini->global)&&isset($ini->global->table)){
      unset($ini->global->table);
    }
    $ini->save();
    unset($ini);
  }

  public function createDb($db) {
    self::checkSettings();
    if(self::$connection) {
      self::$connection->query("CREATE DATABASE ".self::$connection->real_escape_string($db));
    }
  }

  public function createTable($table) {
    self::checkSettings();
    if(self::$connection) {
      $res = self::$connection->query("CREATE TABLE ".self::$connection->real_escape_string($table)." (
      id int(11) NOT NULL AUTO_INCREMENT,
      start datetime DEFAULT NULL,
      answer datetime DEFAULT NULL,
      end datetime DEFAULT NULL,
      clid varchar(80) DEFAULT NULL,
      src varchar(80) DEFAULT NULL,
      dst varchar(80) DEFAULT NULL,
      dcontext varchar(80) DEFAULT NULL,
      channel varchar(80) DEFAULT NULL,
      dstchannel varchar(80) DEFAULT NULL,
      lastapp varchar(80) DEFAULT NULL,
      lastdata varchar(80) DEFAULT NULL,
      duration int(11) DEFAULT NULL,
      billsec int(11) DEFAULT NULL,
      disposition varchar(45) DEFAULT NULL,
      amaflags varchar(45) DEFAULT NULL,
      accountcode varchar(20) DEFAULT NULL,
      uniqueid varchar(150) NOT NULL,
      linkedid varchar(150) DEFAULT NULL,
      peeraccount varchar(20) DEFAULT NULL,
      recordingfile varchar(255) DEFAULT NULL,
      sequence int(11) NOT NULL,
      action varchar(35) DEFAULT NULL,
      PRIMARY KEY (id)
      ) ENGINE=InnoDB AUTO_INCREMENT=21666 DEFAULT CHARSET=utf8mb4");
    }
  }

}

?>
