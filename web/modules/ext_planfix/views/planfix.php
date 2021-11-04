<?php

namespace planfix;

class PlanfixSettings extends \view\View {

  public static function getLocation() {
    return 'settings/integration/planfix';
  }

  public static function getMenu() {
    return (object) array('name' => 'ПланФикс™', 'prio' => 1, 'icon' => 'oi oi-flag');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-planfix');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "get-config": {
        if(self::checkPriv('settings_reader')) {
          $return = new \stdClass();
          $return->users = array();
          $ini = self::getINI('/etc/asterisk/manager.conf');
          foreach($ini as $k => $v) {
            if(isset($v->secret)) { 
              $profile = new \stdClass();
              $profile->id = $k;
              $profile->title = empty($v->getComment())?$k:$v->getComment();
              $return->users[]=$profile;
            }
          }
          $dialplan=new \core\Dialplan();
          $return->contexts=array();
          foreach($dialplan->getContexts() as $v) {
            $return->contexts[] = (object) array('id' => $v->id, 'title' => $v->title);
          }
          $return->user=self::getDB('integration/planfix','user');
          $return->userpwd=self::getDB('integration/planfix','userpwd');
          $return->callcontext=self::getDB('integration/planfix','context');
          $return->extsimple=(self::getDB('integration/planfix','simplemap')!='0');
          $return->extnum = new \stdClass();
          $extcount=self::getDB('integration/planfix','mapcount');
          for($i=0; $i<$extcount; $i++) {
            $key=rawurldecode(self::getDB('integration/planfix','mapkey'.$i));
            $value=rawurldecode(self::getDB('integration/planfix','mapval'.$i));
            if(!empty($key)) $return->extnum->$key=$value;
          }
          $return->localurl=self::getDB('integration/planfix','localurl');
          $return->sipdomain=self::getDB('integration/planfix','domain');
          if($return->sipdomain=='') $return->sipdomain=$_SERVER['SERVER_NAME'];
          $return->planfixapi=self::getDB('integration/planfix','apiurl');
          if($return->planfixapi=='') $return->planfixapi='https://пользователь.planfix.ru/tel/api';
          $return->planfixtoken=self::getDB('integration/planfix','token');
          $return->localtoken=self::getDB('integration/planfix','localtoken');
          if($return->localtoken=='') {
            $return->localtoken=sha1(random_bytes(4096));
            self::setDB('integration/planfix','localtoken',$return->localtoken);
          }
          $return->planfixxmltoken=self::getDB('integration/planfix','xmltoken');
          $return->planfixxmlkey=self::getDB('integration/planfix','xmlkey');
          $result = self::returnResult($return);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "set-config": {
        if(self::checkPriv('settings_writer')) {
          self::setDB('integration/planfix','user',$request_data->user);
          self::setDB('integration/planfix','userpwd',$request_data->userpwd);
          self::setDB('integration/planfix','context',$request_data->callcontext);
          self::setDB('integration/planfix','simplemap', ($request_data->extsimple=='true')?'1':'0');
          $extnum=json_decode($request_data->extnum);
          self::setDB('integration/planfix','mapcount',count($extnum));
          $i=0;
          foreach($extnum as $k => $v) {
            self::setDB('integration/planfix','mapkey'.$i,rawurldecode($k));
            self::setDB('integration/planfix','mapval'.$i,rawurldecode($v));
            $i++;
          }
          self::setDB('integration/planfix','localurl',$request_data->localurl);
          self::setDB('integration/planfix','domain',$request_data->sipdomain);
          self::setDB('integration/planfix','apiurl',$request_data->planfixapi);
          self::setDB('integration/planfix','token',$request_data->planfixtoken);
          self::setDB('integration/planfix','xmltoken',$request_data->planfixxmltoken);
          self::setDB('integration/planfix','xmlkey',$request_data->planfixxmlkey);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "get-passwd": {
        if(self::checkPriv('security_reader')) {
          $ini = self::getINI('/etc/asterisk/manager.conf');
          $username = $request_data->user;
          if(isset($ini->$username->secret)) {
            $result = self::returnResult((string) $ini->$username->secret);
          } else {
            $result = self::returnError('warning', 'Пароль пользователя не задан');
          }
        }
      }
    }
    return $result;
  }

  public function implementation() {
    global $location;
    ?>
    <script>
      var mapsimple={};
      var mapfull={};

      function updateConfig() {
        var props=$('#config-data');
        this.sendRequest('get-config').success(function(data) {
          sidebar_apply(sbapply);
          var user = props.find('#ami-user');
          user.html('');
          for(var i=0; i<data.users.length; i++) {
            user.append('<option value="'+data.users[i].id+'">'+data.users[i].title+'</option>');
          }
          user.val(data.user);
          props.find('#ami-passwd').val(data.userpwd);
          var context = props.find('#call-context');
          context.html('');
          for(var i=0; i<data.contexts.length; i++) {
            context.append('<option value="'+data.contexts[i].id+'">'+data.contexts[i].title+'</option>');
          }
          context.val(data.callcontext);
          props.find('#call-domain').val(data.sipdomain);
          props.find('#planfix-api').val(data.planfixapi);
          props.find('#planfix-token').val(data.planfixtoken);
          props.find('#local-token').val(data.localtoken);
          props.find('#planfix-xmltoken').val(data.planfixxmltoken);
          props.find('#planfix-xmlkey').val(data.planfixxmlkey);
          $(props.find('#simpleprocess input').get(data.extsimple?0:1)).parent().button('toggle');
          mapsimple={};
          mapfull={};
          fillmaplist(data.extsimple,data.extnum);
          createLocalLink();
        });
      }

      function sendConfig() {
        var data = {};
        var props=$('#config-data');
        data.user = props.find('#ami-user').val();
        data.userpwd = props.find('#ami-passwd').val();
        data.callcontext = props.find('#call-context').val();
        data.sipdomain = props.find('#call-domain').val();
        data.planfixapi = props.find('#planfix-api').val();
        data.planfixtoken = props.find('#planfix-token').val();
        data.localtoken = props.find('#local-token').val();
        data.extsimple = props.find('#simpleprocess input').get(0).checked;
        data.extnum=JSON.stringify(readMapList('#maplist'));
        data.localurl='https://'+window.location.host;
        data.planfixxmltoken = props.find('#planfix-xmltoken').val();
        data.planfixxmlkey = props.find('#planfix-xmlkey').val();
        this.sendRequest('set-config', data).success(function() {
          updateConfig();
          return true;
        });
      }

      function fillmaplist(extsimple, extnum) {
          var maplist = $('#maplist');
          maplist.html('');
          for(var keys in extnum) {
            addentry(extsimple, maplist.get(0), keys, extnum[keys]);
          }
          addentry(extsimple, maplist.get(0));
      }

      function simpletoggle() {
        var extsimple=$('#simpleprocess input').get(0).checked;
        if(extsimple) {
          mapfull=readMapList('#maplist');
          fillmaplist(extsimple, mapsimple);
        } else {
          mapsimple=readMapList('#maplist');
          fillmaplist(extsimple, mapfull);
        }
      }

      function createLocalLink() {
        var localapi=$('#local-api');
        var user=$('#ami-user');
        var userpass=$('#ami-passwd');
        localapi.val('https://'+window.location.host+'/integration?json=run&run=planfix&login='+user.val()+'&passwd='+userpass.val());
      }

      function readMapList(sender) {
        var result = {};
        $(sender).find('>div').each(function(i, entry) {
          var inputs = entry.querySelectorAll('input');
          if(inputs[0].value!='') result[inputs[0].value]=inputs[1].value;
        });
        return result;
      }

      function addentry(issimple,list,key,value) {
        var node = $('<div class="form-group row mb-0"><div class="form-group col-xs-12 col-md-6 mb-0"><small class="form-text text-muted">Количество цифр</small><input class="form-control" type="text" onInput="checkmap(this)"></div><div class="form-group col-xs-12 col-md-6 mb-0"><small class="form-text text-muted">Префикс</small><input class="form-control" type="text" onInput="checkmap(this)"></div></div>');
        node.appendTo($(list));
        var inputs = node.find('input');
        var small = node.find('small');
        if(isSet(key)) inputs.get(0).value=key;
        if(isSet(value)) inputs.get(1).value=value;
        if(!issimple) {
          var small = node.find('small');
          small.get(0).innerHTML='Выражение';
          small.get(1).innerHTML='Замена';
        }
      }

      function checkmap(obj) {
        var node=obj.parentNode.parentNode;
        var inputs=node.querySelectorAll('input');
        if((inputs[0].value=='')&&(inputs[1].value=='')&&(node.nextSibling!=null)) {
          var prev=node.previousElementSibling;
          if(prev==null) prev=node.nextElementSibling;
          $(node).remove();
          if(prev!=null) prev.querySelector('input').focus();
        } else if(node.nextSibling==null) {
          addentry($('#simpleprocess input').get(0).checked,node.parentNode);
        }
      }
 
      function copytext(obj) {
        var input=obj.parentNode.previousElementSibling;
        input.select();
        document.execCommand('copy');
        input.setSelectionRange(0,0);
        showalert('success','Текст был скопирован в буфер обмена');
      }
 
<?php
  if(self::checkPriv('settings_writer')) {
?>

      function sbapply(e) {
        sendConfig();
      }

<?php
  } else {
?>

    var sbapply=null;

<?php
  }
?>

<?php
  if(self::checkPriv('security_reader')) {
?>
      function getPasswd() {
        var props=$('#config-data');
        this.sendRequest('get-passwd', {user: props.find('#ami-user').val()}).success(function(data) {
          props.find('#ami-passwd').val(data);
          createLocalLink();
          return false;
        });
      }
<?php
  } else {
?>
      function getPasswd() {
         createLocalLink();
      }
<?php
  }
?>

      $(function () {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();

        orderList('#maplist', null, '>*');
        sidebar_apply(null);
        updateConfig();
      });
    </script>
    <?php
  }

  public function render() {
    ?>
<div class="row" id='config-data'>
 <div class="col-sm-12 pb-3">
  <div class="card">
   <div class="card-header">
    Общие параметры
   </div>
   <div class="card-body row">
    <div class="col-xs-12 col-xl-6">
    <div class="form-group row">
     <label for="ami-user" class="col form-label">Учетная запись для исходящих вызовов</label>
     <div class="input-group col-12 col-md-7">
      <select class="custom-select col-12" id="ami-user" onChange="getPasswd();">
      </select>
     </div>
    </div>
    <div class="form-group row">
     <label for="ami-passwd" class="col form-label">Пароль учетной записи для исходящих вызовов</label>
     <div class="col-12 col-md-7">
      <input class="form-control" id="ami-passwd" type="password" onInput="createLocalLink();">
     </div>
    </div>
    <div class="form-group row">
     <label for="call-context" class="col form-label">Контекст для исходящих вызовов</label>
     <div class="input-group col-12 col-md-7">
      <select class="custom-select col-12" id="call-context">
      </select>
     </div>
    </div>
    <div class="form-group row">
     <label for="call-domain" class="col form-label">SIP домен</label>
     <div class="col-12 col-md-7">
      <input class="form-control" id="call-domain">
     </div>
    </div>
    </div>
    <div class="col-xs-12 col-xl-6">
     <div class="form-group row">
      <label for="simpleprocess" class="col form-label">Режим обработки номеров
       <span class="badge badge-pill badge-info" data-toggle="popover" data-placement="top" title="Режим обработки номеров" data-content="<b>«Простой фильтр»</b> — добавляет префикс к заданному количеству цифр, а так же преобразует стандартные последовательности <i>00*</i>, <i>810*</i>, <i>8*</i> и <i>7*</i> к форме <i>+(код страны)(код региона)(номер)</i><br><b>«Поиск и замена»</b> — осуществляет поиск регулярного выражения и замену номера на соответствующее значение. Используется синтаксис:<br>&nbsp;<b>^</b> — Начало номера<br>&nbsp;<b>$</b> — Конец номера<br>&nbsp;<b>[]</b> — набор символов<br>&nbsp;<b>*</b> — ноль или более символов<br>&nbsp;<b>+</b> — один или более символов<br>&nbsp;<b>{n}</b> — n символов<br>&nbsp;<b>{n,}</b> — n или более символов<br>&nbsp;<b>{,n}</b> — от нуля до n символов<br>&nbsp;<b>{n,m}</b> — от n до m символов<br>&nbsp;<b>()</b> — выделить группу<br>&nbsp;<b>|</b> — логическое ИЛИ<br><br>Для замены используется подстановка вида <b>$n</b> или <b>\n</b>, где <i>n</i> - номер группы, обозначенный круглыми скобками, <i>n = 0</i> подставляет весь номер" data-trigger='hover' data-html=true>?</span>
      </label>
      <div class="col-12 col-md-7 text-center">
       <div id='simpleprocess' class="btn-group btn-group-toggle" data-toggle="buttons"><label class="btn btn-secondary active"><input type="radio" checked name="options" autocomplete="off" onChange="simpletoggle()">Простой фильтр</label><label class="btn btn-secondary"><input type="radio" name="options" autocomplete="off" onChange="simpletoggle()">Поиск и замена</label></div>
      </div>
     </div>
     <div class="form-group row mb-0">
      <div class="col-12">
       <div class="card pl-3 pr-3 pb-3" id='maplist'>
       </div>
      </div>
     </div>
    </div>
   </div>
  </div>
 </div>
 <div class="col-sm-12 col-md-6 pb-3">
  <div class="card">
   <div class="card-header">
    Из ПланФикс
   </div>
   <div class="card-body">
    <div class="form-group row">
     <label for="planfix-api" class="col form-label">Адрес для принятия запросов</label>
     <div class="col-12 col-md-7">
      <input class="form-control" id="planfix-api">
     </div>
    </div>
    <div class="form-group row">
     <label for="planfix-token" class="col form-label">Ключ авторизации ПланФикса</label>
     <div class="col-12 col-md-7">
      <input class="form-control" id="planfix-token">
     </div>
    </div>
   </div>
  </div>
 </div>
 <div class="col-sm-12 col-md-6 pb-3">
  <div class="card">
   <div class="card-header">
    Для ПланФикс
   </div>
   <div class="card-body">
    <div class="form-group row">
     <label for="local-api" class="col form-label">Адрес АТС</label>
     <div class="input-group col-12 col-md-7">
      <input class="form-control" id="local-api" style="pointer-events: none;">
      <div class="input-group-append">
       <button class="btn btn-light text-dark" type="button" onClick='copytext(this);'><span class="oi oi-clipboard"></span></button>
      </div>
     </div>
    </div>
    <div class="form-group row">
     <label for="local-token" class="col form-label">Ключ авторизации АТС</label>
     <div class="input-group col-12 col-md-7">
      <input class="form-control" id="local-token" style="pointer-events: none;">
      <div class="input-group-append">
       <button class="btn btn-light text-dark" type="button" onClick='copytext(this);'><span class="oi oi-clipboard"></span></button>
      </div>
     </div>
    </div>
   </div>
  </div>
 </div>
 <div class="col-sm-12 col-md-6 pb-3">
  <div class="card">
   <div class="card-header">
    Для XML API v1
   </div>
   <div class="card-body">
    <div class="form-group row">
     <label for="planfix-xmlkey" class="col form-label">Токен аутентификации</label>
     <div class="col-12 col-md-7">
      <input class="form-control" id="planfix-xmlkey">
     </div>
    </div>
    <div class="form-group row">
     <label for="planfix-xmltoken" class="col form-label">Токен авторизации</label>
     <div class="col-12 col-md-7">
      <input class="form-control" id="planfix-xmltoken">
     </div>
    </div>
   </div>
  </div>
 </div>
</div>
<?php
  }

}

?>