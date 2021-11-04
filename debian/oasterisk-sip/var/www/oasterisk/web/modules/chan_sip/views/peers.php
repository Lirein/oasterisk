<?php

namespace sip;

class SIPPeerSettings extends \core\ViewModule {

  public static function getLocation() {
    return 'settings/peers/sip';
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
                                if(isset($v->type)&&!isset($v->remotesecret)&&(($v->type=='friend')||($v->type=='user'))) {
                                  $profile->id = $k;
                                  $title = $k;
                                  $callerid = (string) $v->callerid;
                                  if($callerid != '') {
                                    if(preg_match('/(.*)<.*>/',$callerid,$match)) {
                                      $title = $match[1];
                                    } else {
                                      $title = $callerid;
                                    }
                                  }
                                  $description = $v->getComment();
                                  $profile->text = $description?$description:$title;
                                  $profiles[] = $profile;
                                }
                              }
                              return $profiles;
                            };
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    static $getdefaultpeers = '{
      "transport": ",",
      "type": "",
      "host": "",
      "qualify": "",
      "encryption": "",     
      "secret": "",
      "fromdomain": "",
      "callerid": "",
      "context": "",
      "dtmfmode": "",
      "insecure": "",
      "disallow": [],
      "allow": [],
      "nat": ""
    }';
    static $defaultpeers = '{
      "transport": ",",
      "type": "",
      "host": "",
      "qualify": "!no",
      "encryption": "!no",     
      "secret": "",
      "fromdomain": "",
      "callerid": "",
      "context": "",
      "dtmfmode": "",
      "insecure": "no",
      "disallow": [],
      "allow": [],
      "nat": "no"
    }';
    $zonesmodule=getModuleByClass('core\SecZones');
    if($zonesmodule) $zonesmodule->getCurrentSeczones();
    $result = new \stdClass();
    switch($request) {
      case "sipusers": {
        $ini = new \INIProcessor('/etc/asterisk/sip.conf');
        $globalProperty = '{"transport": ",udp", "allow": [], "disallow": []}';
        $returnData = $ini->general->getDefaults($globalProperty);
        $returnData->users=array();
        
        if(count($returnData->disallow)==0) {
          $returnData->allow = array_merge($returnData->allow, array('alaw','ulaw','gsm','h263'));
        }
        
        $codecs = new \core\Codecs();
        $codecs->extractCodecs($returnData);

        $tpldata = array();
        foreach($ini as $k => $v) { 
          if(isset($v->type)&&!isset($v->remotesecret)&&(($v->type=='friend')||($v->type=='user'))) {
            $profile = new \stdClass();
            $profile->id = $k;
            $profile->data = new \stdClass();
            $profile->data->templates = $v->getTemplateNames();
            if($v->isTemplate()) {
              $profile->istemplate = true;
              $profile->data = object_merge($profile->data, $v->getDefaults($getdefaultpeers));
              if($profile->data->insecure=='very') $profile->data->insecure='port,invite';
              $tpldata[$k]=$profile;
            } else {
              $profile->istemplate = false;
            }
            $title=$k;
            $callerid=(string) $v->callerid;
            if($callerid!='') {
              if(preg_match('/(.*)<.*>/', $callerid, $match)) {
                $title=$match[1];
              } else {
                $title=$callerid;
              }
            }
            $profile->title = empty($v->getComment())?$title:($v->getComment());
            if(self::checkEffectivePriv('sip', $profile->id, 'settings_reader')) $returnData->users[]=$profile;
          }
        }
        $extratpl=array();
        foreach($returnData->users as $user) {
          foreach($user->data->templates as $tpl) {
            $hasuser=false;
            foreach($returnData->users as $tpluser) {
              if($tpluser->id==$tpl) {
                $hasuser=true;
                break;
              }
            }
            if(!$hasuser) {
              $extratpl[]=$tpldata[$tpl];
            }
          }
        }
        $returnData->templates=$extratpl;
        $result = self::returnResult($returnData);
      } break;
      case "sipuser-profile": {
        if(isset($request_data->user)) {
          $user = $request_data->user;
          $profile = new \stdClass();
          $ini = new \INIProcessor('/etc/asterisk/sip.conf');
          if(isset($ini->$user)) {
            $k = $user;
            $v = $ini->$k;
            if(isset($v->type)&&!isset($v->remotesecret)&&(($v->type=='friend')||($v->type=='user'))) {
              $profile->id = $k;
              $profile->title = empty($v->getComment())?'':$v->getComment();
              $profile->templates = $v->getTemplateNames();
              $profile->istemplate=$v->isTemplate();
              $profile = object_merge($profile, $v->getDefaults($getdefaultpeers));
              if($profile->insecure=='very') $profile->insecure='port,invite';
              if($zonesmodule&&!$this->checkZones()) {
                $profile->zones=$zonesmodule->getObjectSeczones('sip', $profile->id);
              }
              $profile->readonly=!self::checkEffectivePriv('sip', $profile->id, 'settings_writer');
              if(self::checkEffectivePriv('sip', $profile->id, 'settings_reader')) $result = self::returnResult($profile);
            }
          }
        }
      } break;
      case "sipuser-profile-set": {
        //$_POST = $data        
        if(isset($request_data->orig_id)&&self::checkEffectivePriv('sip', $request_data->orig_id, 'settings_writer')) {
          $profile = array();
          $ini = new \INIProcessor('/etc/asterisk/sip.conf');
          $id = $request_data->id;
          $orig_id = isset($request_data->orig_id)?$request_data->orig_id:'';
          if(($request_data->orig_id!='')&&($request_data->orig_id!=$request_data->id)) {
            if(isset($ini->$id)) {
              $result=self::returnError('danger', "Абонент с таким иденификатором уже существует");
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
          if($orig_id=='') {
            if(isset($ini->$id)) {
              $result=self::returnError('danger', "Абонент с таким иденификатором уже существует");
              break;
            }
          }
          if(findModuleByPath('settings/security/seczones')&&($zonesmodule&&!self::checkZones())) {
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

          $ini->$id->setTemplate($request_data->istemplate=='true');
          $ini->$id->clearTemplates();
          if(is_array($request_data->templates)) {
            foreach($request_data->templates as $template) $ini->$id->addTemplate($ini->$template);
          }

          $ini->$id->setDefaults($defaultpeers, $request_data); 
          $ini->$id->setComment($request_data->title);
          $ini->save();
          $result = self::returnSuccess();
          $this->reloadConfig();
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "sipuser-profile-remove": {
        if(isset($request_data->id)&&self::checkPriv('settings_writer')) {
          $id = $request_data->id;
          $ini = new \INIProcessor('/etc/asterisk/sip.conf');
          if(isset($ini->$id)) {
            $k = $id;
            $v = $ini->$k;
            if(isset($v->type)&&!isset($v->remotesecret)&&(($v->type=='friend')||($v->type=='user'))) {
              if($zonesmodule) {
                foreach($zonesmodule->getObjectSeczones('sip', $k) as $zone) {
                  $zonesmodule->removeSeczoneObject($zone, 'sip', $k);
                }
              }
              unset($ini->$id);
              $ini->save();
              $result = self::returnSuccess();
              $this->reloadConfig();
            } else {
              $result = self::returnError('danger', 'Учетная запись не является абонентом');
            }
          } else {
            $result = self::returnError('danger', 'Учетная запись не существует');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "codecs": {
        $returnData = array();
        $codec = getModuleByClass('core\Codecs');
        $codecs = ($codec)?$codec->getByClass():array();
        foreach($codecs as $info) {
          $returnData[]=array('text'=>$info->title, 'id'=>$info->name);
        }
        $result = self::returnResult($returnData);
      } break;
    }
    return $result;
  }

  public function scripts() {
    ?>
      <script>
      var sip_user=null;
      var sip_user_id='<?php echo isset($_GET['id'])?$_GET['id']:'0'; ?>';
      var sip_user_templates=[];
      var sip_user_links=[];
      var sip_codecs=[];
      var sip_transport=[];
      var card = null;
 
      function updateCodecs() {
        sendRequest('codecs').success(function(data) {
          card.setValue({codecs: data});
          return false;
        });
      }

      function updateSIPUsers() {
        sendRequest('sipusers').success(function(data) {
          sip_codecs=data.codecs;
          sip_transport=data.transport;
          var hasactive=false;
          sip_user_templates=[];
          sip_user_links=[];
          var items=[];
          if(data.users.length) {
            for(var i = 0; i < data.users.length; i++) {
              if(data.users[i].id==sip_user_id) hasactive=true;
              if(data.users[i].istemplate) sip_user_templates.push({id: data.users[i].id, text: data.users[i].title, data: data.users[i].data});
              sip_user_links.push({id: data.users[i].id, templates: data.users[i].data.templates});
              items.push({id: String(data.users[i].id), title: String(data.users[i].title), active: data.users[i].id==sip_user_id, class: data.users[i].istemplate?'info':null});
            }
          };
          sip_user_templates=sip_user_templates.concat(data.templates);
          card.setValue([{id: 'templates', value: sip_user_templates, clean: true}]);
          rightsidebar_set('#sidebarRightCollapse', items);
          if(!hasactive) {
            card.hide();
            window.history.pushState(sip_user_id, $('title').html(), '/'+urilocation);
            sip_user_id='';
            rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
            sidebar_apply(null);
            if(data.users.length>0) loadSIPUser(data.users[0].id);
          } else {
            loadSIPUser(sip_user_id);
          }
          return false;
        });
      }

      function getSIPUserTemplate(templateName) {
        var result = null;
        for(var i=0;i<sip_user_templates.length;i++) {
          if(sip_user_templates[i].id==templateName) {
            result = sip_user_templates[i];
            break;
          }
        }
        return result;
      }

      function removeSIPUserAvailTemplate(templates, template, selfonly) {
         for(var i=0; i<templates.length; i++) {
           if(templates[i].id==template) {
             template=templates.splice(i,1)[0];
             if(typeof selfonly=='undefined') for(var j=0; j<template.data.templates.length; j++) {
               templates = removeSIPUserAvailTemplate(templates, template.data.templates[j]);
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
               templates = removeSIPUserAvailTemplate(templates, tpl.id);
               haselem=true;
               break;
             }
           }
         } while(haselem);
         return templates;
      }

      function expandSIPTemplate(tpl_data, template) {
        for(var i=0; i<template.data.templates.length; i++) {
          tpl_data=expandSIPTemplate(tpl_data, getSIPUserTemplate(template.data.templates[i]));
        }
        if(template.data.type=='') tpl_data.type=template.data.type;
        if(template.data.host!='') tpl_data.host=template.data.host;
        if(template.data.qualify!=='') tpl_data.qualify=template.data.qualify;
        if(template.data.encryption!=='') tpl_data.encryption=template.data.encryption;
        if(template.data.secret!='') tpl_data.secret=template.data.secret;
        if(template.data.fromdomain!='') tpl_data.fromdomain=template.data.fromdomain;
        if(template.data.callerid!='') tpl_data.callerid=template.data.callerid;
        if(template.data.context!='') tpl_data.context=template.data.context;
        if(template.data.dtmfmode!='') tpl_data.dtmfmode=template.data.dtmfmode;
        if(template.data.insecure!='') tpl_data.insecure=template.data.insecure;
        if(template.data.nat!='') tpl_data.nat=template.data.nat;
        if(template.data.transport!=null) tpl_data.nat=template.data.transport;
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

      function updateSIPUser() {
        var tpl_data={type: '', host: '', qualify: '', encryption: '', secret: '', fromdomain: '', callerid: '', context: '', dtmfmode: '', insecure: '', nat: '', allow: null, disallow: null, transport: sip_transport};
        if(sip_user!=null) {
          tpl_data.allow = sip_codecs.slice();
          tpl_data.transport = sip_transport.slice();
          for(var i=0; i<sip_user.templates.length; i++) {
            tpl_data=expandSIPTemplate(tpl_data, getSIPUserTemplate(sip_user.templates[i]));
          }
          tpl_data.desc='';
          if(sip_user.type!='') tpl_data.type=sip_user.type;
          if(sip_user.id!='') tpl_data.name=sip_user.id;
          if(sip_user.title!='') tpl_data.desc=sip_user.title;
          if(sip_user.fromdomain!='') tpl_data.fromdomain=sip_user.fromdomain;
          if(sip_user.secret!='') tpl_data.secret=sip_user.secret;
          if(sip_user.callerid!='') tpl_data.callerid=sip_user.callerid;
          if(sip_user.host!='') tpl_data.host=sip_user.host;
          if(tpl_data.host=='') tpl_data.host='dynamic';
          tpl_data.istemplate = sip_user.istemplate;
          if(sip_user.context!='') tpl_data.context=sip_user.context;
          if(sip_user.dtmfmode!='') tpl_data.dtmfmode=sip_user.dtmfmode;
          if(sip_user.insecure!='') tpl_data.insecure=sip_user.insecure;
          if(sip_user.nat!='') tpl_data.nat=sip_user.nat;
          if(sip_user.qualify!=='') tpl_data.qualify=sip_user.qualify;
          if(sip_user.encryption!=='') tpl_data.encryption=sip_user.encryption;
          if(!((sip_user.transport==null)||(sip_user.transport.length==0))) tpl_data.transport=sip_user.transport;

          var overridecodecs=false;
          tpl_data.codecs=tpl_data.allow;
          if(!sip_user.disallow) sip_user.disallow=[];
          if(tpl_data.codecs==null) tpl_data.codecs=[];
          for(var i=0; i<sip_user.disallow.length; i++) {
            if(sip_user.disallow[i]=='all') {
              tpl_data.codecs=[];
              overridecodecs=true;
            } else {
              j=tpl_data.codecs.indexOf(sip_user.disallow[i]);
              if(j!=-1) tpl_data.codecs.splice(j, 1);
            }
          }
          if(sip_user.allow!=null) tpl_data.codecs = tpl_data.codecs.concat(sip_user.allow);
          delete tpl_data.allow;
          delete tpl_data.disallow;
          tpl_data.overridecodecs=overridecodecs;
          var availtemplates=[];

          for(var i=0;i<sip_user.templates.length;i++) {
            for(var j=0;j<sip_user_templates.length;j++) {
              if(sip_user_templates[j].id==sip_user.templates[i]) {
                availtemplates.push({id: sip_user_templates[j].id, text: sip_user_templates[j].text, data: sip_user_templates[j].data, checked: true});
                break;
              }
            }
          }
          for(var i=0;i<sip_user_templates.length;i++) {
            var has_tpl=false;
            for(var j=0;j<availtemplates.length;j++) {
              if(sip_user_templates[i].id==availtemplates[j].id) {
                has_tpl=true;
                break;
              }
            }
            if(!has_tpl) availtemplates.push({id: sip_user_templates[i].id, text: sip_user_templates[i].text, data: sip_user_templates[i].data, checked: false});
          }
          if(sip_user.istemplate) availtemplates = removeSIPUserAvailTemplate(availtemplates,sip_user.id,true);
          tpl_data.templates={value: availtemplates, clean: true};
          tpl_data.zones=sip_user.zones;
        } else {
          tpl_data.name='new-user';
          tpl_data.desc=_('new-user',"Новый пользователь");
          var availtemplates=[];
          for(var i=0;i<sip_user_templates.length;i++) {
            availtemplates.push({id: sip_user_templates[i].id, text: sip_user_templates[i].text, data: sip_user_templates[i].data, checked: false});
          }
          tpl_data.istemplate=false;
          sip_user=Object.assign({},tpl_data);
          sip_user.templates=[];
          sip_user.codecs=[];
          sip_user.transport=[];
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
        if(sip_user.readonly) card.disable(); else card.enable();
        var haslinks=false;
        if(sip_user.istemplate) for(var i=0; i<sip_user_links.length; i++) {
          if(find(sip_user_links[i].templates, sip_user.id)!=-1) haslinks=true;
        }
        if(haslinks) {
          card.node.querySelector('#istemplate').widget.disable();
        }
      }

      function addSIPUserTemplate(sender, item) {
        sip_user.templates.push(item.id);
        updateSIPUser();
        return false;
      }

      function updateSIPUserTemplate(sender) {
        sip_user.templates=card.getValue().templates;
        updateSIPUser();
        return false;
      }

      function removeSIPUserTemplate(sender, item) {
        if(typeof item.removed == 'undefined') {
          showdialog('Удаление шаблона','Вы уверены что хотите удалить шаблон '+item.text+' из списка?','question',['Yes','No'], function(btn) {
            if(btn=='Yes') {
              item.removed=true;
              if(sender.listRemove(sender.list, item)) sender.list.delete(item.id);
            }
          });
          return false;
        } else {
          var j=sip_user.templates.findIndex(function(i) {return i==item.id});
          if(j!=-1) sip_user.templates.splice(j,1);
          updateSIPUser();
          return false;
        }
      }

      function loadSIPUser(user) {
        sendRequest('sipuser-profile', {user: user}).success(function(data) {
          sip_user = data;
          rightsidebar_activate('#sidebarRightCollapse', user);
          rightsidebar_init('#sidebarRightCollapse', data.readonly?null:sbdel, sbadd, sbselect);
          sidebar_apply(data.readonly?null:sbapply);

          sip_user_id=data.id;
          var haslinks=false;
          if(data.istemplate) for(var i=0; i<sip_user_links.length; i++) {
            if(find(sip_user_links[i].templates, data.id)!=-1) haslinks=true;
          }
          if(haslinks) rootcontent.querySelector('#istemplate').widget.disable();
            else rootcontent.querySelector('#istemplate').widget.enable();
          if(typeof sip_user.zones == 'undefined') sip_user.zones=[];
          card.setValue({zones: sip_user.zones});
          updateSIPUser();
          window.history.pushState(sip_user_id, $('title').html(), '/'+urilocation+'?id='+sip_user_id);
          card.show();
          if(haslinks)
            rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
        });
      }

      function addSIPUser() {
        sip_user_id='';
        sip_user=null;
        rightsidebar_activate('#sidebarRightCollapse', null);

        card.setValue({zones: []});
        updateSIPUser();

        card.show();
        sidebar_apply(sbapply);
        rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
      }

      function removeSIPUser() {
        showdialog('Удаление канала','Вы уверены что действительно хотите удалить абонента SIP?',"error",['Yes','No'],function(e) {
          if(e=='Yes') {
            var data = {};
            data.id = sip_user_id;
            sendRequest('sipuser-profile-remove', data).success(function(data) {
              sip_user_id='';
              updateSIPUsers();
            });
          }
        });
      }

      function getSIPUserData() {
        var data = card.getValue();
        data.orig_id = sip_user_id;

        var tpl_data={type: '', host: '', qualify: '', encryption: '', login: '', secret: '', fromdomain: '', callerid: '', context: '', dtmfmode: '', insecure: '', nat: '', allow: null, disallow: null, transport: sip_transport};
        tpl_data.allow = sip_codecs.slice();
        tpl_data.transport = sip_transport.slice();
        for(var i=0; i<data.templates.length; i++) {
          tpl_data=expandSIPTemplate(tpl_data, getSIPUserTemplate(data.templates[i]));
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
        delete data.codecs;
        data.id=data.name;
        data.title=data.desc;
        for(akey in tpl_data) {
          if(tpl_data[akey]===data[akey]) data[akey]='';
        }
        if(data.qualify===true) data.qualify='true';
        if(data.qualify===false) data.qualify='false';
        if(data.encryption===true) data.encryption='true';
        if(data.encryption===false) data.encryption='false';
        return data;
      }

      function sendSIPUserData() {
        var data = getSIPUserData();
        sendRequest('sipuser-profile-set', data).success(function() {
          sip_user_id=data.id;
          updateSIPUsers();
          return true;
        });
      }
 
      function sendSIPUser() {
        var proceed = false;
        var data = card.getValue();
        data.orig_id = sip_user_id;
        data.id = data.name;
        if(data.id=='') {
          showalert('warning','Не задан идентификатор абонента');
          return false;
        }
        if((data.orig_id!='')&&(data.id!=data.orig_id)) {
          showdialog('Идентификатор абонента изменен','Выберите действие с абонентом:',"warning",['Rename','Copy', 'Cancel'],function(e) {
            if(e=='Rename') {
              proceed=true;
            }
            if(e=='Copy') {
              proceed=true;
              sip_user_id='';
            }
            if(proceed) {
              sendSIPUserData();
            }
          });
        } else {
          proceed=true;
        }
        if(proceed) {
          sendSIPUserData();
        }
      }

      function sbselect(e, item) {
        loadSIPUser(item);
      }

      function sbapply(e) {
        sendSIPUser();
      }

<?php
  if(self::checkPriv('settings_writer')) {
?>

      function sbadd(e) {
        addSIPUser();
      }

      function sbdel(e) {
        removeSIPUser();
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

//place widgets here >>>>
          card = new widgets.section(rootcontent,null);
          var cols = new widgets.section(card,null);
          cols.node.classList.add('row');
          var subcard1 = new widgets.columns(cols,2);
          var subcard2 = new widgets.columns(cols,2);

          var obj = new widgets.select(subcard1, {id: 'type', value: [{id: 'user', text: 'Таксофон'}, {id: 'friend', text: 'Абонент', checked: true}]}, "Тип абонента", "<b>«Абонент»</b> — может как осуществлять исходящие, так и принимать входящие вызовы<br><b>«Таксофон»</b> — может осуществлять только исходящие вызовы");
          obj = new widgets.input(subcard1, {id: 'desc'}, "Отображаемое имя", "Наименование абонента, отображаемое в графическом интерфейсе<br>Если указано пустое значение - используется <i>«Идентификатор абонента»</i> или логин учетной записи");
          obj = new widgets.input(subcard1, {id: 'name', pattern: '[a-zA-Z0-9_-]+', placeholder: 'login'}, "Учетная запись", "Логин и внутренний идентификатор абонента, используемый при входящих и исходящих вызовах");
          obj = new widgets.checkbox(subcard1, {single: true, id: 'istemplate', value: false}, "Является шаблоном");
          obj = new widgets.collection(subcard1, {id: 'templates', value: []}, "Шаблоны", "Параметры, определяемые шаблоном могут наследоваться абонентами или другими шаблонами.<br>Можно указать несколько наследуемых шаблонов, при этом порядок применения параметров определяется порядком определения шаблонов.<br>Изменить порядок определения шаблонов можно при помощи перетаскивания.");
          obj.onAdd = addSIPUserTemplate;
          obj.onRemove = removeSIPUserTemplate;
          obj.onChange = updateSIPUserTemplate;
<?php
        if(findModuleByPath('settings/security/seczones')) {
          $zonesmodule=getModuleByClass('core\SecZones');
          if($zonesmodule) $zonesmodule->getCurrentSeczones();
          if($zonesmodule&&(!self::checkZones())) {
            printf('var values = [];');
            foreach($zonesmodule->getSeczones() as $zone => $name) {
              printf('values.push({id: "%s", text: "%s"});', $zone, $name);
            }
            printf('obj = new widgets.collection(subcard1, {id: "zones", value: values}, "Зоны безопасности");');
          }
        }
?>
          obj = new widgets.input(subcard1, {id: 'host', placeholder: 'dynamic', pattern: '[a-zA-Z0-9._-]+', value: 'dynamic'}, "Адрес узла", "IP адрес абонентского устройства<br>Для абонента с динамическим адресом, указывается значение <i>dynamic</i>");
          obj = new widgets.checkbox(subcard1, {single: true, id: 'qualify', value: false}, "Отслеживать состояние линии");
          obj = new widgets.list(subcard2, {id: 'transport', value: [{id: 'tls', text: 'TLS'},{id: 'tcp', text: 'TCP'},{id: 'udp', text: 'UDP'}], checkbox: true, sorted: true}, "Транспорт", "Устанавливает перечень и порядок выбора транспорта<br>Для изменения подрядка используется перетаскивание");
          obj = new widgets.checkbox(subcard2, {single: true, id: 'encryption', value: false}, "Шифровать мультимедийный поток");
          obj = new widgets.input(subcard1, {id: 'secret', password: true}, "Пароль");
          obj = new widgets.input(subcard1, {id: 'fromdomain', pattern: '[a-z0-9_.-]+', placeholder: 'asterisk'}, "Домен", "Указывает значение домена в поле from исходящего вызова.<br>Пустое значение эквивалентно адресу сервера или шлюза<br>Используется преимущественно при типе канала <b>«Провайдер»</b>");
          obj = new widgets.input(subcard1, {id: 'callerid', pattern: '(|(|")[а-яА-ЯA-Za-z0-9_ .-]+(|")) {0,1}(|<([a-z0-9.@_-]{0,}(|>)))'}, "Идентификатор абонента", "Отображаемое имя абонента при исходящем вызове.<br>Может быть указано в формате <nobr><b>имя</b> <i>&lt;номер&gt;</i></nobr><br>Поле <i>номер</i> является необязательным и указывается если логин отличается от обратного номера");
          obj = new widgets.select(subcard1, {id: 'context', value: context_data, clean: true, search: true}, "Контекст абонента", "Наименование контекста в который попадает обработка всех входящих вызовов.<br>В указанном контексте должен быть определен набираемый абонентом номер или шаблон такого номера");
          obj = new widgets.select(subcard1, {id: 'dtmfmode', value: [{id: 'rfc2833', text: 'Стандарт RFC 2833'}, {id: 'auto', text: 'Автоматически'}, {id: 'inband', text: 'Голосовой поток'}, {id: 'info', text: 'SIP Info'}]}, "Тип согласования DTMF", "Определяет режим отправкии и определения входящей сигнализации<br><b>«Автоматически»</b> — использует <i>«Cтандарт RFC 2833»</i>, или переходит в режим <i>«Голосовой поток»</i> в случае приема такой сигнализации<br><b>«Стандарт RFC 2833»</b> — сигнализация передается в отдельном потоке<br><b>«Голосовой поток»</b> — сигнализация передается в том же потоке, что и речь. Работает только для кодеков <i>G.711µ</i> и <i>G.711a</i>, в остальных случаях используется <i>«Стандарт RFC 2833»</i><br><b>«SIP Info»</b> — Используется цифровая сигнализация через заголовки SIP протокола, не предоставляет функцию определения сигнализации в режиме «реального времени»");
          obj = new widgets.select(subcard1, {id: 'insecure', value: [{id: 'port', text: 'По порту и IP адресу'}, {id: 'no', text: 'Обязательная аутентификация'}, {id: 'invite', text: 'Согласно регистрации'}, {id: 'port,invite', text: 'Согласно регистрации или порту и IP адресу'}]}, "Безопасность вызовов", "<b>«Обязательная аутентификация»</b> — требует аутентификацию для каждого входящего вызова<br><b>«По порту и IP адресу»</b> — не запрашивает аутентификацию для входящих вызовов, если таковая производилась с того же IP адреса и номера порта<br><b>«Согласно регистрации»</b> — аутентификация для входящих вызовов не запрашивается, если логин абонента в запросе совпадает с логином в настройках<br><b>«Согласно регистрации или порту и IP адресу»</b> — в соответствующих случаях аутентифкация не запрашивается<br><b><i>Внимание:</i> </b>Если используется несколько абонентов за одним IP адресом, указание режима безопасности согласно <i>«Порту и адресу»</i> переходит в контекст первого зарегистрировавшегося абонента. В случае использования разных контекстов необходимо указывать режим <i>Согласно регистрации</i>");
          obj = new widgets.select(subcard1, {id: 'nat', value: [{id: '', text: 'Не задана', checked: true},{id: 'no', text: 'Отключена' },{id: 'auto_force_rport', text: 'Порт источника SIP (авто)'},{id: 'auto_comedia', text: 'Порт источника RTP (авто)'},{id: 'auto_force_rport,auto_comedia', text: 'Порт источника SIP потом RTP (авто)'},{id: 'force_rport', text: 'Порт источника SIP'},{id: 'comedia', text: 'Порт источника RTP'},{id: 'force_rport,comedia', text: 'Порт источника SIP потом RTP'}]}, _("nat","Трансляция адресов"), "Управляет адресом и портом назначения RTP трафика и информации в SDP во время согласования мультимедийного потока.<br><b>«Не задана»</b> — наследуется от родительского шаблона или из пареметров протокола SIP<br><b>«Отключена»</b> — в SDP всегда передается локальный адрес, а RTP передается по адресу и номеру порта, указываемом в SDP абонента при согласовании мультимедийного потока<br><b>«Порт источника SIP»</b> — в качестве адреса и порта назначения RTP трафика используется адрес и порт с которого принят пакет согласования мультимедийного потока<br><b>«Порт источника RTP»</b> — в качестве адреса и порта назначения RTP трафика используется адрес и порт с которого принимается входящий RTP трафик со согласованному мультимедийному потоку<br><b>«Порт источника SIP потом RTP»</b> — в качестве адреса и порта назначения RTP трафика используется адрес и порт с которого принят пакет согласования мультимедийного потока, после приема входящего RTP трафика по согласованному мультимедийному потоку порт меняется на номер порта с которого был передан входящий RTP<br><b><i>Примечение:</i></b> Режимы <i>«авто»</i> включаются если IP адрес, с которого получен SDP не принадлежит локальному диапазону адресов, при этом в исходящем SDP указывается внешний публичный адрес");
          obj = new widgets.checkbox(subcard2, {id: 'overridecodecs', value: false}, "Переопределить кодеки", "Отменяет унаследованный перечень кодеков от шаблона и общих настроек SIP");
          obj = new widgets.list(subcard2, {id: 'codecs', value: [], checkbox: true, sorted: true}, "Поддерживыемые кодеки", "Устанавливает перечень и порядок согласования кодеков.<br>Для изменения порядка согласования кодеков используется перетаскивание<br>Порядок не может быть изменен для унаследованных кодеков.");

          cols.simplify();
          card.hide();
          updateCodecs();
          updateSIPUsers();
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
