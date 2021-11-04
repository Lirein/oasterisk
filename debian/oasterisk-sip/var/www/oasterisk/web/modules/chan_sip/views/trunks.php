<?php

namespace sip;

class SIPTrunkSettings extends \core\ViewModule {

  public static function getLocation() {
    return 'settings/trunks/sip';
  }

  public static function getMenu() {
    return (object) array('name' => 'SIP', 'prio' => 2);
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

  public static function getZoneInfo() {
    $result = new \SecZoneInfo();
    $result->zoneClass = 'sip';
    $result->getObjects = function () {
                              $ini = new \INIProcessor('/etc/asterisk/sip.conf');
                              $profiles=array();

                              foreach($ini as $k => $v) {
                                $profile = new \stdClass();
                                if(isset($v->type)&&(isset($v->remotesecret))||($v->type=='peer')) {
                                  $profile->id = $k;
                                  $title=$k;
                                  $callerid=(string) $v->callerid;
                                  if($callerid!='') {
                                    if(preg_match('/(.*)<.*>/',$callerid,$match)) {
                                      $title=$match[1];
                                    } else {
                                      $title=$callerid;
                                    }
                                  }
                                  $description=$v->getComment();
                                  $profile->text = $description?$description:$title;
                                  $profiles[]=$profile;
                                }
                              }
                              return $profiles;
                            };
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    static $getdefaulttrunks = '{
      "transport": ",",
      "host": "",
      "qualify": "",
      "encryption": "",
      "authuser": "",
      "secret": "",
      "fromuser": "",
      "fromdomain": "",
      "callerid": "",
      "context": "",
      "dtmfmode": "",
      "insecure": "",
      "disallow": [],
      "allow": [],
      "nat": ""
    }';
    static $defaulttrunks = '{
      "transport": ",",
      "host": "",
      "qualify": "!no",
      "encryption": "!no",
      "authuser": "",
      "secret": "",
      "fromuser": "",
      "fromdomain": "",
      "callerid": "",
      "context": "",
      "dtmfmode": "",
      "insecure": "no",
      "disallow": [],
      "allow": [],
      "nat": "no"
    }';
    $globalProperty = '{"transport": ",udp", "allow": [], "disallow": [], "register": []}';
    $zonesmodule=getModuleByClass('core\SecZones');
    if($zonesmodule) $zonesmodule->getCurrentSeczones();
    $result = new \stdClass();
    switch($request) {
      case "siptrunks": {
        $ini = new \INIProcessor('/etc/asterisk/sip.conf');
        $returnData = $ini->general->getDefaults($globalProperty);
        $returnData->trunks = array();

        if(count($returnData->disallow)==0) {
          $returnData->allow = array_merge($returnData->allow, array('alaw','ulaw','gsm','h263'));
        }

        $codecs = new \core\Codecs();
        $codecs->extractCodecs($returnData);

        $tpldata = array();
        foreach($ini as $k => $v) {
          if(isset($v->type)&&(isset($v->remotesecret))||($v->type=='peer')) {
            $profile = new \stdClass();
            $profile->id = $k;
            $profile->data = new \stdClass();
            $profile->data->templates = $v->getTemplateNames();
            if($v->isTemplate()) {
              $profile->istemplate = true;
              $profile->data = object_merge($profile->data, $v->getDefaults($getdefaulttrunks));
              if($profile->data->insecure=='very') $profile->data->insecure='port,invite';
              $tpldata[$k]=$profile;
            } else {
              $profile->istemplate = false;
            }
            $profile->title = empty($v->getComment())?$k:($v->getComment());
            if(self::checkEffectivePriv('sip', $profile->id, 'settings_reader')) $returnData->trunks[]=$profile;
          }
        }
        $extratpl=array();
        foreach($returnData->trunks as $trunk) {
          foreach($trunk->data->templates as $tpl) {
            $hastrunk=false;
            foreach($returnData->trunks as $tpltrunk) {
              if($tpltrunk->id==$tpl) {
                $hastrunk=true;
                break;
              }
            }
            if(!$hastrunk) {
              $extratpl[]=$tpldata[$tpl];
            }
          }
        }
        $returnData->templates=$extratpl;
        $result = self::returnResult($returnData);
      } break;
      case "siptrunk-profile": {
        if(isset($request_data->trunk)&&self::checkEffectivePriv('sip', $request_data->trunk, 'settings_reader')) {
          $profile = new \stdClass();
          $ini = new \INIProcessor('/etc/asterisk/sip.conf');
          $trunk = $request_data->trunk;
          if(isset($ini->$trunk)) {
            $v = $ini->$trunk;            
            if(isset($v->type)&&(isset($v->remotesecret))||($v->type=='peer')) {
              $profile->id = $trunk;
              $profile->title = empty($v->getComment())?$trunk:$v->getComment();
              $profile->templates = $v->getTemplateNames();
              $profile->istemplate=$v->isTemplate();
              $profile = object_merge($profile, $v->getDefaults($getdefaulttrunks));              
              if($profile->insecure=='very') $profile->insecure='port,invite';
              
              $origuser=isset($v->fromuser)?$v->fromuser:'';
              if($origuser=='') $origuser=$trunk;
              $orighost=isset($v->host)?$v->host:'';
              $register = array();
              $ini->general->normalize($globalProperty);
              if(isset($ini->general->register)) {
                $register = $ini->general->register;
                if($register instanceof \INIPropertyArray) {
                  $register = $register->getValue();
                } else {
                  if(!is_array($register)) $register=array($register);
                }
              }
              $profile->regnum = '';
              $profile->register = false;
              foreach($register as $regkey => $reg) {
                if(preg_match('/(peer\?|)((tcp|udp|tls):\/\/|)(.+?)(@(.+?)|)(:(.+?)(:(.+?)|)|)@([^\/:~]+)(:(\d+)|)(\/([a-zA-Z0-9_\-]+)|)(~\d+|)/', $reg, $match)) {
//                 $transport=$match[3];
                   $fromuser=$match[4];
//                 $domain=$match[6];
//                 $password=$match[8];
//                 $login=$match[10];
                   $host=$match[11];
//                 $port=$match[13];
                   $regnum=$match[15];
                   if(($host==$orighost) && (($fromuser==$origuser)||($fromuser==$trunk))) {
                     $profile->regnum = $regnum;
                     $profile->register = true;
                     break;
                   }
                }
              }
              if($zonesmodule&&!$this->checkZones()) {
                $profile->zones=$zonesmodule->getObjectSeczones('sip', $profile->id);
              }
              $profile->readonly=!self::checkEffectivePriv('sip', $profile->id, 'settings_writer');
              if(self::checkEffectivePriv('sip', $profile->id, 'settings_reader')) $result = self::returnResult($profile);
            }
          }
        }
      } break;
      case "siptrunk-profile-set": {
        //$_POST = $data
        if(isset($request_data->orig_id)&&self::checkEffectivePriv('sip', $request_data->orig_id, 'settings_writer')) {
          $profile = array();
          $ini = new \INIProcessor('/etc/asterisk/sip.conf');
          $id = $request_data->id;
          $orig_id = isset($request_data->orig_id)?$request_data->orig_id:'';
          if(($request_data->orig_id!='')&&($request_data->orig_id!=$request_data->id)) {
            if(isset($ini->$id)) {
              $result=self::returnError('danger', "Транк с таким иденификатором уже существует");              
              break;
            }
            $zones = $zonesmodule->getObjectSeczones('sip', $orig_id);
            foreach($zones as $zone) {
              $zonesmodule->removeSeczoneObject($zone, 'sip', $orig_id);
            }
            if($ini->$orig_id->isTemplate()) {
              foreach($ini as $user => $userdata) {
                foreach($userdata->getTemplateNames() as $template) {
                  if($template==$orig_id) {
                    $userdata->removeTemplate($template);
                    $userdata->addTemplate($ini->$id);
                    break;
                  }
                }
              }
            }
            if(isset($ini->$orig_id))
              unset($ini->$orig_id);
          }
          if($orig_id == '') {
            if(isset($ini->$id)) {
              $result=self::returnError('danger', "Транк с таким иденификатором уже существует");
              break;
            }
          }
          $register = array();
          $ini->general->normalize($globalProperty);
          if(isset($ini->general->register)) {
            $register = $ini->general->register;
            if($register instanceof \INIPropertyArray) {
              $register = $register->getValue();
            } else {
              if(!is_array($register)) $register=array((string)$register);
            }
          }
          if($orig_id!='') {
            $origuser=isset($ini->$orig_id->fromuser)?(string)$ini->$orig_id->fromuser:(string)$orig_id;
            $orighost=isset($ini->$orig_id->host)?(string)$ini->$orig_id->host:(string)$ini->$orig_id->fromdomain;
            //remove registry
            foreach($register as $regkey => $reg) {
              if(preg_match('/(peer\?|)((tcp|udp|tls):\/\/|)(.+?)(@(.+?)|)(:(.+?)(:(.+?)|)|)@([^\/:~]+)(:(\d+)|)(\/([a-zA-Z0-9_\-]+)|)(~\d+|)/', $reg, $match)) {
                //                 $transport=$match[3];
                 $fromuser=$match[4];
//                 $domain=$match[6];
//                 $password=$match[8];
//                 $login=$match[10];
                 $host=$match[11];
//                 $port=$match[13];
//                 $regnum=$match[15];
                 if(($host==$orighost) && ($fromuser==$origuser)) {
                   unset($register[$regkey]);
                   break;
                 }
              }
            }
          }
          if(findModuleByPath('settings/security/seczones')&&($zonesmodule&&self::checkZones())) {
            $zones = $zonesmodule->getObjectSeczones('sip', $id);
            foreach($zones as $zone) {
              $zonesmodule->removeSeczoneObject($zone, 'sip', $id);
            }
            if(isset($request_data->zones)) foreach($request_data->zones as $zone) {
              $zonesmodule->addSeczoneObject($zone, 'sip', $id);
            }
          }
          if(!isset($ini->$id)&&$zonesmodule&&$this->checkZones()) {
            $eprivs = $zonesmodule->getCurrentPrivs('sip', $id);
            $zone = isset($eprivs['settings_writer'])?$eprivs['settings_writer']:false;
            if(!$zone) $zone = isset($eprivs['settings_reader'])?$eprivs['settings_reader']:false;
            if($zone) {
              $zonesmodule->addSeczoneObject($zone, 'sip', $id);
            } else {
              $result = self::returnError('danger', 'Отказано в доступе');
              break;
            }
          }
          if(!isset($ini->$id)) $ini->$id=array();
          
          $ini->$id->setTemplate($request_data->istemplate=='true');
          $ini->$id->clearTemplates();
          if(is_array($request_data->templates)) {
            foreach($request_data->templates as $template) $ini->$id->addTemplate($ini->$template);
          }
          
          $ini->$id->setDefaults($defaulttrunks, $request_data);
          $ini->$id->type = 'peer';
          // здесь $data = $reader->readFile('/etc/asterisk/sip.conf');         
          // if(isset($_POST['secret'])) {
          //   self::setSectionValue($data, $_POST['id'], ($tmp[';type']!='friend')?';remotesecret':';secret', $_POST['secret']);
          //   self::setSectionValue($data, $_POST['id'], ($tmp[';type']!='friend')?';secret':';remotesecret', '');
          //   if(($tmp[';type']!='friend')&&($_POST['secret']=='')) self::setSectionValue($data, $_POST['id'], ';remotesecret', ' ');
          // }
          $ini->$id->setComment($request_data->title);

          if(($request_data->istemplate!='true')&&isset($request_data->register)&&($request_data->register=='true')) {
            $tmp = $ini->$id;
            //add registry
            $reg='';
            if(is_array($tmp->transport)&&count(explode(',',$tmp->transport))&&(explode(',',$tmp->transport)[0]!='udp')) $reg.=explode(',',$tmp->transport)[0].'://';
            $login=$request_data->id;
            if(isset($tmp->fromuser)&&($tmp->fromuser!='')) $login=$tmp->fromuser;
            $reg.=$login;
            if(isset($tmp->fromdomain)&&($tmp->fromdomain!='')&&($tmp->fromdomain!=$tmp->host)) $reg.='@'.$tmp->fromdomain;
            if((isset($tmp->secret)&&($tmp->secret!=''))||(isset($tmp->remotesecret)&&($tmp->remotesecret!=''))) {
              $reg.=':'.(isset($tmp->secret)?($tmp->secret):($tmp->remotesecret));
              if(isset($tmp->authuser)&&($tmp->authuser!='')) $reg.=':'.$tmp->authuser;
            }
            if(isset($tmp->host)&&($tmp->host!='')) $reg.='@'.$tmp->host;
            if(isset($request_data->regnum)&&($request_data->regnum!='')) $reg.='/'.$request_data->regnum;
            $register[]=$reg;
          }
          $ini->general->register = $register;
          $ini->save();
          $result = self::returnSuccess();
          $this->reloadConfig();
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "siptrunk-profile-remove": {
        //$_POST = $data
        if(isset($request_data->id)&&self::checkPriv('settings_writer')) {
          $id = $request_data->id;
          $ini = new \INIProcessor('/etc/asterisk/sip.conf');
          if(isset($ini->$id)) {
            $k = $request_data->id;
            $v = $ini->$k;
            if(isset($v->type)&&(isset($v->remotesecret)||($v->type=='peer'))) {
              if($zonesmodule) {
                foreach($zonesmodule->getObjectSeczones('sip', $id) as $zone) {
                  $zonesmodule->removeSeczoneObject($zone, 'sip', $id);
                }
              }
              $register = array();
              if(isset($ini->general->register)) {
                $register = $ini->general->register;
                if($register instanceof \INIPropertyArray) {
                  $register = $register->getValue();
                } else {
                  if(!is_array($register)) $register=array($register);
                }
              }
              $origuser=isset($v->fromuser)?$v->fromuser:'';
              if($origuser=='') $origuser=isset($v->authuser)?$v->authuser:'';
              if($origuser=='') $origuser=$request_data->id;
              $orighost=$v->host;
              //remove registry
              foreach($register as $regkey => $reg) {
                if(preg_match('/(peer\?|)((tcp|udp|tls):\/\/|)(.+?)(@(.+?)|)(:(.+?)(:(.+?)|)|)@([^\/:~]+)(:(\d+)|)(\/([a-zA-Z0-9_\-]+)|)(~\d+|)/', $reg, $match)) {
                  $fromuser=$match[4];
                  $host=$match[11];
                  if(($host==$orighost) && ($fromuser==$origuser)) {
                    unset($register[$regkey]);
                    break;
                  }
                }
              }
              unset($ini->$id);
              $ini->general->register = $register;
              $ini->save();
              $result = self::returnSuccess();
              $this->reloadConfig();
            } else {
              $result = self::returnError('danger', 'Учетная запись не является транком');
            }
          } else {
            $result = self::returnError('danger', 'Транка с таким идентификатором не найдено');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "codecs": {
        $returnData = array();
        $codec = new \core\Codecs();
        $codecs = $codec->getByClass();
        foreach($codecs as $info) {
          $returnData[]=(object) array('text' => $info->title, 'id' => $info->name);
        }
        $result = self::returnResult($returnData);
      } break;
    }
    return $result;
  }

  public function scripts() {
    ?>
      <script>
      var sip_trunk=null;
      var sip_trunk_id='<?php echo isset($_GET['id'])?$_GET['id']:'0'; ?>';
      var sip_trunk_templates=[];
      var sip_trunk_links=[];
      var sip_codecs=[];
      var sip_transport=[];

      function updateCodecs() {
        sendRequest('codecs').success(function(data) {
          card.setValue({codecs: data});
          return false;
        });
      }

      function updateSIPTrunks() {
        sendRequest('siptrunks').success(function(data) {
          sip_codecs=data.codecs;
          sip_transport=data.transport;
          var hasactive=false;
          sip_trunk_templates=[];
          sip_trunk_links=[];
          var items = []
          if(data.trunks.length) {
            for(var i = 0; i < data.trunks.length; i++) {
              if(data.trunks[i].id==sip_trunk_id) hasactive=true;
              if(data.trunks[i].istemplate) sip_trunk_templates.push({id: data.trunks[i].id, text: data.trunks[i].title, data: data.trunks[i].data});
              sip_trunk_links.push({id: data.trunks[i].id, templates: data.trunks[i].data.templates});
              items.push({id: String(data.trunks[i].id), title: String(data.trunks[i].title), active: data.trunks[i].id==sip_trunk_id, class: data.trunks[i].istemplate?'info':null});
            }
          }
          sip_trunk_templates=sip_trunk_templates.concat(data.templates);
          rightsidebar_set('#sidebarRightCollapse', items);
          if(!hasactive) {
            card.hide();
            window.history.pushState(sip_trunk_id, $('title').html(), '/'+urilocation);
            sip_trunk_id='';
            sidebar_apply(null);
            rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
            if(data.trunks.length>0) loadSIPTrunk(data.trunks[0].id);
          } else {
            loadSIPTrunk(sip_trunk_id);
          }
          return false;
        });
      }

      function getSIPTrunkTemplate(templateName) {
        var result = null;
        for(var i=0;i<sip_trunk_templates.length;i++) {
          if(sip_trunk_templates[i].id==templateName) {
            result = sip_trunk_templates[i];
            break;
          }
        }
        return result;
      }

      function removeSIPTrunkAvailTemplate(templates, template, selfonly) {
         for(var i=0; i<templates.length; i++) {
           if(templates[i].id==template) {
             template=templates.splice(i,1)[0];
            if(typeof selfonly=='undefined') for(var j=0; j<template.data.templates.length; j++) {
               templates = removeSIPTrunkAvailTemplate(templates, template.data.templates[j]);
             }
             break;
           }
         }
         if(typeof template != 'string') {
           template = template.id;
         }
         var haselem = false;
         do {
           haselem = false;
           for(var i=0; i<templates.length; i++) {
             if(find(templates[i].data.templates, template)!=-1) {
               tpl=templates.splice(i,1)[0];
               templates = removeSIPTrunkAvailTemplate(templates, tpl.id);
               haselem=true;
               break;
             }
           }
         } while(haselem);
         return templates;
      }

      function expandSIPTemplate(tpl_data, template) {
        for(var i=0; i<template.data.templates.length; i++) {
          tpl_data=expandSIPTemplate(tpl_data, getSIPTrunkTemplate(template.data.templates[i]));
        }
        if(template.data.host!='') tpl_data.host=template.data.host;
        if(template.data.qualify!=='') tpl_data.qualify=template.data.qualify;
        if(template.data.encryption!=='') tpl_data.encryption=template.data.encryption;
        if(template.data.login!='') tpl_data.login=template.data.login;
        if(template.data.secret!='') tpl_data.secret=template.data.secret;
        if(template.data.fromuser!='') tpl_data.fromuser=template.data.fromuser;
        if(template.data.fromdomain!='') tpl_data.fromdomain=template.data.fromdomain;
        if(template.data.callerid!='') tpl_data.callerid=template.data.callerid;
        if(template.data.context!='') tpl_data.context=template.data.context;
        if(template.data.dtmfmode!='') tpl_data.dtmfmode=template.data.dtmfmode;
        if(template.data.insecure!='') tpl_data.insecure=template.data.insecure;
        if(template.data.nat!='') tpl_data.nat=template.data.nat;
        if(template.data.transport!=null) tpl_data.transport=template.data.transport;
        if(template.data.disallow == null) template.data.disallow = [];
        if(template.data.allow == null) template.data.allow = sip_codecs.slice(0);
        if(tpl_data.allow==null) {
          tpl_data.disallow=template.data.disallow;
          var i=find(template.data.disallow,'all');
          if(i!=-1) template.data.disallow.splice(i,1);
          tpl_data.allow=template.data.allow;
        } else {
          var codecs=tpl_data.allow;
          if(codecs==null) codecs=[];
          for(var i=0; i<template.data.disallow.length; i++) {
            if(template.data.disallow[i]=='all') {
              codecs=[];
            } else {
              j=codecs.indexOf(template.data.disallow[i]);
              if(j!=-1) codecs.splice(j, 1);
            }
          }
          tpl_data.allow = codecs.concat(template.data.allow);
        }
        return tpl_data;
      }

      function updateSIPTrunk() {
        var tpl_data={host: '', qualify: '', encryption: '', login: '', secret: '', fromuser: '', fromdomain: '', callerid: '', context: '', dtmfmode: '', insecure: '', nat: '', allow: null, disallow: null, transport: sip_transport, regnum: ''};
        if(sip_trunk!=null) {
          tpl_data.allow = sip_codecs.slice();
          for(var i=0; i<sip_trunk.templates.length; i++) {
            tpl_data=expandSIPTemplate(tpl_data, getSIPTrunkTemplate(sip_trunk.templates[i]));
          }

          tpl_data.desc='';
          if(sip_trunk.id!='') tpl_data.name=sip_trunk.id;
          if(sip_trunk.title!='') tpl_data.desc=sip_trunk.title;
          if(sip_trunk.secret!='') tpl_data.secret=sip_trunk.secret;
          if(sip_trunk.callerid!='') tpl_data.callerid=sip_trunk.callerid;
          if(sip_trunk.login!='') tpl_data.login=sip_trunk.login;
          if(sip_trunk.host!='') tpl_data.host=sip_trunk.host;
          if(tpl_data.host=='') tpl_data.host='dynamic';
          tpl_data.istemplate = sip_trunk.istemplate;
          if(sip_trunk.fromuser!='') tpl_data.fromuser=sip_trunk.fromuser;
          if(sip_trunk.fromdomain!='') tpl_data.fromdomain=sip_trunk.fromdomain;
          if(sip_trunk.context!='') tpl_data.context=sip_trunk.context;
          if(sip_trunk.dtmfmode!='') tpl_data.dtmfmode=sip_trunk.dtmfmode;
          if(sip_trunk.insecure!='') tpl_data.insecure=sip_trunk.insecure;
          if(sip_trunk.nat!='') tpl_data.nat=sip_trunk.nat;
          if(sip_trunk.qualify!=='') tpl_data.qualify=sip_trunk.qualify;
          if(sip_trunk.encryption!=='') tpl_data.encryption=sip_trunk.encryption;
          if(sip_trunk.regnum!=='') tpl_data.regnum=sip_trunk.regnum;
          if(!((sip_trunk.transport==null)||(sip_trunk.transport.length==0))) tpl_data.transport=sip_trunk.transport;
          tpl_data.register=sip_trunk.register;

          var overridecodecs=false;
          tpl_data.codecs=tpl_data.allow;
          if(!sip_trunk.disallow) sip_trunk.disallow=[];
          if(tpl_data.codecs==null) tpl_data.codecs=[];
          for(var i=0; i<sip_trunk.disallow.length; i++) {
            if(sip_trunk.disallow[i]=='all') {
              tpl_data.codecs=[];
              overridecodecs=true;
            } else {
              j=tpl_data.codecs.indexOf(sip_trunk.disallow[i]);
              if(j!=-1) tpl_data.codecs.splice(j, 1);
            }
          }
          if(sip_trunk.allow!=null) tpl_data.codecs = tpl_data.codecs.concat(sip_trunk.allow);
          delete tpl_data.allow;
          delete tpl_data.disallow;
          tpl_data.overridecodecs=overridecodecs;
          var availtemplates=[];

          for(var i=0;i<sip_trunk.templates.length;i++) {
            for(var j=0;j<sip_trunk_templates.length;j++) {
              if(sip_trunk_templates[j].id==sip_trunk.templates[i]) {
                availtemplates.push({id: sip_trunk_templates[j].id, text: sip_trunk_templates[j].text, data: sip_trunk_templates[j].data, checked: true});
                break;
              }
            }
          }
          for(var i=0;i<sip_trunk_templates.length;i++) {
            var has_tpl=false;
            for(var j=0;j<availtemplates.length;j++) {
              if(sip_trunk_templates[i].id==availtemplates[j].id) {
                has_tpl=true;
                break;
              }
            }
            if(!has_tpl) availtemplates.push({id: sip_trunk_templates[i].id, text: sip_trunk_templates[i].text, data: sip_trunk_templates[i].data, checked: false});
          }
          if(sip_trunk.istemplate) availtemplates = removeSIPTrunkAvailTemplate(availtemplates,sip_trunk.id,true);
          tpl_data.templates={value: availtemplates, clean: true};
          tpl_data.regnum=sip_trunk.regnum;
          tpl_data.zones=sip_trunk.zones;
        } else {
          tpl_data.name='new-trunk';
          tpl_data.desc=_('new-trunk',"Новый канал");
          var availtemplates=[];
          for(var i=0;i<sip_trunk_templates.length;i++) {
            availtemplates.push({id: sip_trunk_templates[i].id, text: sip_trunk_templates[i].text, data: sip_trunk_templates[i].data, checked: false});
          }
          tpl_data.istemplate=false;
          sip_trunk=Object.assign({},tpl_data);
          sip_trunk.templates=[];
          sip_trunk.codecs=[];
          sip_trunk.transport=[];
          tpl_data.templates={value: availtemplates, clean: true};
          tpl_data.codecs=sip_codecs;
          tpl_data.transport=sip_transport;
          tpl_data.zones=[];
          tpl_data.overridecodecs=false;
        }
        tpl_data.codecs={value: tpl_data.codecs, uncheck: true};
        tpl_data.transport={value: tpl_data.transport, uncheck: true};
        card.setValue({context: {value: context_data, clear: true}});
        card.setValue(tpl_data);
        if(sip_trunk.readonly) card.disable(); else card.enable();
        var haslinks=false;
        if(sip_trunk.istemplate) for(var i=0; i<sip_trunk_links.length; i++) {
          if(find(sip_trunk_links[i].templates, sip_trunk.id)!=-1) haslinks=true;
        }
        if(haslinks) {
          card.node.querySelector('#istemplate').widget.disable();
        }
        if(sip_trunk.istemplate) {
          card.node.querySelector('#register').widget.disable();
          card.node.querySelector('#regnum').widget.disable();
        }
        if(haslinks)
          rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);

        card.show();
      }

      function addSIPTrunkTemplate(sender, item) {
        sip_trunk.templates.push(item.id);
        updateSIPTrunk();
        return false;
      }

      function updateSIPTrunkTemplate(sender) {
        sip_trunk.templates=card.getValue().templates;
        updateSIPTrunk();
        return false;
      }

      function removeSIPTrunkTemplate(sender, item) {
        if(typeof item.removed == 'undefined') {
          showdialog('Удаление шаблона','Вы уверены что хотите удалить шаблон '+item.text+' из списка?','question',['Yes','No'], function(btn) {
            if(btn=='Yes') {
              item.removed=true;
              if(sender.listRemove(sender.list, item)) sender.list.delete(item.id);
            }
          });
          return false;
        } else {
          var j=sip_trunk.templates.findIndex(function(i) {return i==item.id});
          if(j!=-1) sip_trunk.templates.splice(j,1);
          updateSIPTrunk();
          return false;
        }
      }

      function loadSIPTrunk(trunk) {
        sendRequest('siptrunk-profile', {trunk: trunk}).success(function(data) {
          sip_trunk = data;
          sip_trunk.readonly = data.readonly;
          rightsidebar_activate('#sidebarRightCollapse', data.id);
          rightsidebar_init('#sidebarRightCollapse', data.readonly?null:sbdel, sbadd, sbselect);
          sidebar_apply(data.readonly?null:sbapply);
          sip_trunk_id=data.id;
          updateSIPTrunk();
          window.history.pushState(sip_trunk_id, $('title').html(), '/'+urilocation+'?id='+sip_trunk_id);
          return false;
        });
      }

      function addSIPTrunk() {
        sip_trunk_id='';
        sip_trunk=null;
        rightsidebar_activate('#sidebarRightCollapse', null);

        card.setValue({zones: []});
        updateSIPTrunk();
        card.show();
        sidebar_apply(sbapply);
        rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
      }

      function removeSIPTrunk() {
        showdialog('Удаление канала','Вы уверены что действительно хотите удалить канал SIP?',"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            var data = {};
            data.id = sip_trunk_id;
            sendRequest('siptrunk-profile-remove', data).success(function() {
              sip_trunk_id='';
              updateSIPTrunks();
            });
          }
        });
      }

      function getSIPTrunkData() {
        var data = card.getValue();
        data.orig_id = sip_trunk_id;
        data.id = data.name;
        data.title = data.desc;
        var tpl_data={host: '', qualify: '', encryption: '', login: '', secret: '', fromuser: '', fromdomain: '', callerid: '', context: '', dtmfmode: '', insecure: '', nat: '', transport: sip_transport, allow: null, disallow: null};
        tpl_data.allow = sip_codecs.slice();
        for(var i=0; i<data.templates.length; i++) {
          tpl_data=expandSIPTemplate(tpl_data, getSIPTrunkTemplate(data.templates[i]));
        }
        if(data.transport.equals(tpl_data.transport)) delete data.transport;
        if(data.overridecodecs) {
          data.disallow=['all'];
          data.allow=data.codecs;
        } else {
          data.disallow=[];
          data.allow=[];
          for(var i=0; i<tpl_data.allow.length; i++) {
            if(find(data.codecs,tpl_data.allow[i])==-1) { //Disable codec - disable
              data.disallow.push(tpl_data.allow[i]);
            }
          }
          for(var i=0; i<data.codecs.length; i++) {
            if(find(tpl_data.allow,data.codecs[i])==-1) { //New codec - enable
              data.allow.push(data.codecs[i]);
            }
          }
          if((!data.overridecodecs)&&(data.allow.length==0)&&(data.disallow.length==tpl_data.allow.length)) data.disallow=[];
          if(data.overridecodecs&&(data.disallow.length==tpl_data.allow.length)) data.disallow=['all'];
        }
        for(akey in tpl_data) {
          if(tpl_data[akey]===data[akey]) data[akey]='';
        }
        if(data.qualify===true) data.qualify='true';
        if(data.qualify===false) data.qualify='false';
        if(data.encryption===true) data.encryption='true';
        if(data.encryption===false) data.encryption='false';
        return data;
      }

      function sendSIPTrunkData() {
        data=getSIPTrunkData();
        sendRequest('siptrunk-profile-set', data).success(function() {
          showalert('success','Канал успешно изменен');
          sip_trunk_id=data.id;
          updateSIPTrunks();
          return false;
        });
      }
 
      function sendSIPTrunk() {
        var proceed = false;
        var data = card.getValue();
        data.orig_id = sip_trunk_id;
        data.id = data.name;
        if(data.id=='') {
          showalert('warning','Не задан идентификатор транка');
          return false;
        }
        if((data.orig_id!='')&&(data.id!=data.orig_id)) {
          showdialog('Идентификатор канала изменен','Выберите действие с каналом:',"warning",['Rename','Copy', 'Cancel'],function(e) {
            if(e=='Rename') {
              proceed=true;
            }
            if(e=='Copy') {
              proceed=true;
              sip_trunk_id='';
            }
            if(proceed) {
              sendSIPTrunkData();
            }
          });
        } else {
          proceed=true;
        }
        if(proceed) {
          sendSIPTrunkData();
        }
      }

      function sbselect(e, item) {
        loadSIPTrunk(item);
      }

      function sbapply(e) {
        sendSIPTrunk();
      }

<?php
  if(self::checkPriv('settings_writer')) {
?>

      function sbadd(e) {
        addSIPTrunk();
      }

      function sbdel(e) {
        removeSIPTrunk();
      }

<?php
  } else {
?>

    var sbadd=null;
    var sbdel=null;

<?php
  }
?>

      $(function () {
        var items=[];
        rightsidebar_set('#sidebarRightCollapse', items);
        rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
        sidebar_apply(null);

          card = new widgets.section(rootcontent,null);
          var cols = new widgets.section(card,null);
          cols.node.classList.add('row');
          var subcard1 = new widgets.columns(cols,2);
          var subcard2 = new widgets.columns(cols,2);

          obj = new widgets.input(subcard1, {id: 'desc'}, "Отображаемое имя", "Наименование транка, отображаемое в графическом интерфейсе");
          obj = new widgets.input(subcard1, {id: 'name', pattern: '[a-zA-Z0-9_-]+'}, "Наименование транка", "Внутренний идентификатор транка, используемый при входящих и исходящих вызовах");
          obj = new widgets.checkbox(subcard1, {single: true, id: 'istemplate', value: false}, "Является шаблоном");
          obj = new widgets.collection(subcard1, {id: 'templates'}, "Шаблоны", "Параметры, определяемые шаблоном, могут наследоваться транками или другими шаблонами.<br>Можно указать несколько наследуемых шаблонов, при этом порядок применения параметров определяется порядком определения шаблонов.<br>Изменить порядок определения шаблонов можно при помощи перетаскивания.");
          obj.onAdd = addSIPTrunkTemplate;
          obj.onRemove = removeSIPTrunkTemplate;
          obj.onChange = updateSIPTrunkTemplate;
<?php
        if(findModuleByPath('settings/security/seczones')) {
          $zonesmodule=getModuleByClass('core\SecZones');
          if($zonesmodule) $zonesmodule->getCurrentSeczones();
          if($zonesmodule&&self::checkZones()) {
            printf('var values = [];');
            foreach($zonesmodule->getSeczones() as $zone => $name) {
              printf('values.push({id: "%s", text: "%s"});', $zone, $name);
            }
            printf('obj = new widgets.collection(subcard1, {id: "zones", value: values}, "Зоны безопасности");');
          }
        }
?>
          obj = new widgets.input(subcard1, {id: 'host', placeholder: 'dynamic', pattern: '[a-zA-Z0-9._-]+', value: 'dynamic'}, "Сервер подключения", "<i>Имя узла</i>/<i>IP адрес</i> сервера провайдера или шлюза.<br>При входящей регистрации для шлюза с динамическим адресом указывается значение <i>dynamic</i>");
          obj = new widgets.checkbox(subcard1, {single: true, id: 'qualify', value: false}, "Отслеживать состояние линии");
          obj = new widgets.list(subcard2, {id: 'transport', value: [{id: 'tls', text: 'TLS'},{id: 'tcp', text: 'TCP'},{id: 'udp', text: 'UDP'}], checkbox: true, sorted: true}, "Транспорт", "Устанавливает перечень и порядок выбора транспорта.<br>Для изменения подрядка используется перетаскивание.<br>Для исходящей <i>«Регистрации»</i> используется первый указанный транспорт.");
          obj = new widgets.checkbox(subcard2, {single: true, id: 'encryption', value: false}, "Шифровать мультимедийный поток");
          obj = new widgets.input(subcard1, {id: 'login', pattern: '[a-z0-9_.-]+'}, "Логин");
          obj = new widgets.input(subcard1, {id: 'secret', password: true}, "Пароль");
          obj = new widgets.input(subcard1, {id: 'fromdomain', pattern: '[a-z0-9_.-]+', placeholder: 'asterisk'}, "Домен", "Указывает значение домена в поле from исходящего вызова.<br>Пустое значение эквивалентно адресу сервера или шлюза. <br>Используется преимущественно при типе транка <b>«Провайдер»</b>");
          obj = new widgets.input(subcard1, {id: 'fromuser', pattern: '[a-z0-9_.-]+'}, "Звонки от", "Подменяет значение логина в поле from исходящего вызова.<br>Используется преимущественно при типе транка <b>«Провайдер»</b>");
          obj = new widgets.input(subcard1, {id: 'callerid', pattern: '(|(|")[а-яА-ЯA-Za-z0-9_ .-]+(|")) {0,1}(|<([a-z0-9.@_-]{0,}(|>)))'}, "Идентификатор абонента", "Подменяет отображаемое имя абонента при исходящем вызове через транк.<br>Может быть указано в формате <b>имя</b> <i>&lt;номер&gt;</i><br>Поле <i>номер</i> является необязательным и указывается, если логин отличается от обратного номера");
          obj = new widgets.select(subcard1, {id: 'context', value: context_data, clean: true, search: true}, "Контекст канала", "Наименование контекста, в который попадает обработка всех входящих вызовов.<br>В указанном контексте должен быть определен набираемый абонентом номер или шаблон такого номера.");
          obj = new widgets.select(subcard1, {id: 'dtmfmode', value: [{id: 'rfc2833', text: 'Стандарт RFC 2833'}, {id: 'auto', text: 'Автоматически'}, {id: 'inband', text: 'Голосовой поток'}, {id: 'info', text: 'SIP Info'}]}, "Тип согласования DTMF", "Определяет режим отправки и определения входящей сигнализации<br><b>«Автоматически»</b> — использует <i>«Cтандарт RFC 2833»</i>, или переходит в режим <i>«Голосовой поток»</i> в случае приема такой сигнализации<br><b>«Стандарт RFC 2833»</b> — сигнализация передается в отдельном потоке<br><b>«Голосовой поток»</b> — сигнализация передается в том же потоке, что и речь. Работает только для кодеков <i>G.711µ</i> и <i>G.711a</i>, в остальных случаях используется <i>«Стандарт RFC 2833»</i><br><b>«SIP Info»</b> — Используется цифровая сигнализация через заголовки SIP протокола, не предоставляет функцию определения сигнализации в режиме «реального времени»");
          obj = new widgets.select(subcard1, {id: 'insecure', value: [{id: 'port', text: 'По порту и IP адресу'}, {id: 'no', text: 'Обязательная аутентификация'}, {id: 'invite', text: 'Согласно регистрации'}, {id: 'port,invite', text: 'Согласно регистрации или порту и IP адресу'}]}, "Безопасность вызовов", "<b>«Обязательная аутентификация»</b> — требует аутентификацию для каждого входящего вызова<br><b>«По порту и IP адресу»</b> — не запрашивает аутентификацию для входящих вызовов, если таковая производилась с того же IP адреса и номера порта<br><b>«Согласно регистрации»</b> — аутентификация для входящих вызовов не запрашивается, если логин в запросе совпадает с логином в настройках<br><b>«Согласно регистрации или порту и IP адресу»</b> — в соответствующих случаях аутентификация не запрашивается<br><b><i>Внимание: </i></b>Если используется несколько номеров от одного провайдера, и указание режима безопасности согласно <i>«Порту и адресу»</i> переходит в контекст первого определенного транка, то в этом случае необходимо разграничение принимаемых входящих вызовов по регистрируемым номерам у такого провайдера <i>(смотрите инструкции для регистрации)</i>");
          obj = new widgets.select(subcard1, {id: 'nat', value: [{id: '', text: 'Не задана', checked: true},{id: 'no', text: 'Отключена' },{id: 'auto_force_rport', text: 'Порт источника SIP (авто)'},{id: 'auto_comedia', text: 'Порт источника RTP (авто)'},{id: 'auto_force_rport,auto_comedia', text: 'Порт источника SIP потом RTP (авто)'},{id: 'force_rport', text: 'Порт источника SIP'},{id: 'comedia', text: 'Порт источника RTP'},{id: 'force_rport,comedia', text: 'Порт источника SIP потом RTP'}]}, _("nat","Трансляция адресов"), "Управляет адресом и портом назначения RTP трафика и информации в SDP во время согласования мультимедийного потока.<br><b>«Не задана»</b> — наследуется от родительского шаблона или из параметров протокола SIP.<br><b>«Отключена»</b> — в SDP всегда передается локальный адрес, а RTP передается по адресу и номеру порта, указываемом в SDP от шлюза/провайдера при согласовании мультимедийного потока.<br><b>«Порт источника SIP»</b> — в качестве адреса и порта назначения RTP трафика используется адрес и порт, с которого принят пакет согласования мультимедийного потока.<br><b>«Порт источника RTP»</b> — в качестве адреса и порта назначения RTP трафика используется адрес и порт, с которого принимается входящий RTP трафик по согласованному мультимедийному потоку.<br><b>«Порт источника SIP потом RTP»</b> — в качестве адреса и порта назначения RTP трафика используется адрес и порт, с которого принят пакет согласования мультимедийного потока. После приема входящего RTP трафика по согласованному мультимедийному потоку, порт меняется на номер порта, с которого был передан входящий RTP.<br><b><i>Примечение:</i></b> Режимы <i>«авто»</i> включаются, если IP адрес, с которого получен SDP не принадлежит локальному диапазону адресов, при этом в исходящем SDP указывается внешний публичный адрес.");
          obj = new widgets.checkbox(subcard2, {single: true, id: 'register', value: false}, "Регистрация","Осуществляет исходящую аутентификацию на шлюзе или у провайдера для приема входящих вызовов.<br>Для шлюза регистрация обычно не требуется.");
          obj = new widgets.input(subcard2, {id: 'regnum', pattern: '[a-zA-Z0-9_-]+'}, "Абонентский номер", "Указывает номер, который будет передаваться в <i>«Контекст канала»</i> при входящем вызове по <i>«Регистрации»</i>.<br>Если значение не указано, входящий звонок адресуется на номер <i>s</i>.");
          obj = new widgets.checkbox(subcard2, {id: 'overridecodecs', value: false}, "Переопределить кодеки", "Отменяет унаследованный перечень кодеков от шаблона и общих настроек SIP");
          obj = new widgets.list(subcard2, {id: 'codecs', value: [], checkbox: true, sorted: true}, "Поддерживыемые кодеки", "Устанавливает перечень и порядок согласования кодеков.<br>Для изменения порядка согласования кодеков используется перетаскивание.<br>Порядок не может быть изменен для унаследованных кодеков.");
          cols.simplify();
          card.hide();
          updateCodecs();
          updateSIPTrunks();
      })
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
