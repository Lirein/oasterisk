<?php

namespace pjsip;

class SIPProviderViewPort extends \view\ViewPort {

  public static function getViewLocation() {
    return 'provider/pjsip';
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
        if(typeof data == 'undefined') data = {};

        this.providers = [{id: 'ruscomp', title: _('Русская компания')},
                          {id: 'multifon', title: _('МультиФон')},
                          {id: 'sipnet', title: _('SIPNet')},
                          {id: 'other', title: _('Настройка вручную...')},
                         ];
        this.provider = new widgets.select(parent, {id: 'provider', expand: true, search: false, clean: true, value: this.providers}, _('Провайдер'), _('Выберите провайдера из списка'));
        this.provider.onChange = this.onSelectProvider;

        this.ruscomp = new widgets.section(parent, null);

        this.ruscomp.phone = new widgets.label(this.ruscomp, {expand: true}, _('Телефон технической поддержки ООО «Русская Компания» <a href="tel:+73452390000">+7 (3452) 39-00-00</a>.'));

        this.ruscomp.contract = new widgets.input(this.ruscomp, {id: 'contract', pattern: '[0-9]+', placeholder: _('12345678')}, _('Номер договора'));
    
        this.ruscomp.password = new widgets.input(this.ruscomp, {id: 'password', password: true}, _('Пароль'));

        this.ruscomp.hide();

        this.multifon = new widgets.section(parent, null);

        this.multifon.phone = new widgets.label(this.multifon, {expand: true}, _('Телефон технической поддержки ПАО «Мегафон» <a href="tel:+78004441137">+7 800 444-11-37</a>.'));

        this.multifon.contract = new widgets.input(this.multifon, {id: 'contract', pattern: '[0-9]+', placeholder: _('12345678')}, _('Номер телефона'));

        this.multifon.contract.onInput = this.phoneChange;
    
        this.multifon.password = new widgets.input(this.multifon, {id: 'password', password: true}, _('Пароль'));

        this.multifon.hide();

        this.sipnet = new widgets.section(parent, null);

        this.sipnet.phone = new widgets.label(this.sipnet, {expand: true}, _('Телефон технической поддержки ООО «Сипнет» <a href="tel:+78003331401">+7 800 333-14-01</a>.'));

        this.sipnet.contract = new widgets.input(this.sipnet, {id: 'contract', pattern: '0|00|00[0-9]+', placeholder: _('0012345678')}, _('SIP ID'));
    
        this.sipnet.password = new widgets.input(this.sipnet, {id: 'password', password: true}, _('Пароль'));

        this.sipnet.hide();

        this.other = new widgets.section(parent, null);

        this.other.address = new widgets.input(this.other, {id: 'address', pattern: '([0-9]{1,3}[.]){0,3}[0-9]{1,3}|([a-z0-9]+[.]*)+', placeholder: _('000.000.000.000')}, _('Адрес сервера'));

        this.other.domian = new widgets.input(this.other, {id: 'domain', pattern: '([a-z0-9]+[.]*)+', placeholder: _('12345678')}, _('Домен для регистрации'));

        this.other.login = new widgets.input(this.other, {id: 'login', placeholder: _('12345678')}, _('Имя пользователя'));
    
        this.other.password = new widgets.input(this.other, {id: 'password', password: true}, _('Пароль'));

        this.other.advanced = new widgets.section(this.other, null);

        this.other.advanced.proxy = new widgets.input(this.other.advanced, {id: 'proxy', expand: true, pattern: '([0-9]{1,3}[.]){0,3}[0-9]{1,3}|([a-z0-9]+[.]*)+', placeholder: _('000.000.000.000')}, _('Адрес прокси-сервера'));

        this.other.advanced.protocol = new widgets.select(this.other.advanced, {id: 'protocol', clean: true, value: [{id: 'tcp', title: 'TCP'}, {id: 'udp', title: 'UDP'}, {id: 'tls', title: 'TLS'}]}, _('Протокол'), _('Укажите используемый протокол для исходящих запросов и регистрации'));

        this.other.advanced.port = new widgets.input(this.other.advanced, {id: 'port', pattern: '[0-9]{5}', placeholder: _('5060')}, _('Порт'));

        this.other.advanced.hide();

        this.other.numberformat = new widgets.section(this.other, 'numberformat');

        await require('numberformat', this.other.numberformat);

        this.other.hide();
      }

      function phoneChange(sender, data) {
        if(this.parent.parent instanceof baseWidget) {
          if(this.parent.parent.getValue().phone == data.oldvalue) this.parent.parent.setValue({phone: data.value});
        }
      }

      function setValue(data) {
        if(typeof data.host == 'undefined') return;
        switch(data.host) {
          case 'sip.t72.ru': {
            this.provider.setValue('ruscomp');
            this.ruscomp.contract.setValue(data.fromuser);
            this.ruscomp.password.setValue(data.secret);
          } break;
          case 'sbc.megafon.ru': {
            this.provider.setValue('multifon');
            this.multifon.contract.setValue(data.fromuser);
            this.multifon.password.setValue(data.secret);
          } break;
          case 'sipnet.ru': {
            this.provider.setValue('sipnet');
            this.sipnet.contract.setValue(data.frumuser);
            this.sipnet.password.setValue(data.secret);
          } break;
          default: {
            this.provider.setValue('other');
            let newdata = {};
            newdata.advanced = false;
            if((data.port != null) || !(data.transport.has('udp') && data.transport.length==1) || (data.proxy != null)) newdata.advanced = true;
            newdata.address = data.host;
            newdata.login = data.fromuser;
            newdata.domian = data.fromdomain;
            newdata.password = data.secret;
            newdata.proxy = data.outboundproxy;
            newdata.protocol = data.transport.has('tls')?'tls':(data.transport.has('tcp')?'tcp':'udp');
            newdata.port = data.port;
            newdata.numberformat = {itut: true};
            if(typeof data.numberformat != 'undefined') newdata.numberformat = data.numberformat;
            this.other.setValue(newdata);
          } break;
        }
        this.onSelectProvider(this.provider);
      }

      function getValue() {
        let result = {};
        switch(this.provider.getValue()) {
          case 'ruscomp': {
            result.host = 'sip.t72.ru';
            result.fromuser = this.ruscomp.contract.getValue();
            result.username = result.fromuser;
            result.authuser = result.fromuser;
            result.fromdomain = 'sip.t72.ru';
            result.secret = this.ruscomp.password.getValue();
            result.outboundproxy = null;
            result.transport = 'udp';
            result.port = null;
            result.codecs = ['ulaw'];
            result.numberformat = {russianeight: true};
          } break;
          case 'multifon': {
            result.host = 'sbc.megafon.ru';
            result.fromuser = this.multifon.contract.getValue();
            result.username = result.fromuser;
            result.authuser = result.fromuser;
            result.fromdomain = 'multifon.ru';
            result.secret = this.multifon.password.getValue();
            result.outboundproxy = null;
            result.transport = 'tcp,udp';
            result.port = null;
            result.codecs = ['ulaw', 'alaw'];
            result.numberformat = {itut: true};
          } break;
          case 'sipnet': {
            result.host = 'sipnet.ru';
            result.fromuser = this.sipnet.contract.getValue();
            result.username = result.fromuser;
            result.authuser = result.fromuser;
            result.fromdomain = 'sipnet.ru';
            result.secret = this.sipnet.password.getValue();
            result.outboundproxy = null;
            result.transport = 'udp,tcp';
            result.port = null;           
            result.codecs = ['g729', 'ulaw', 'alaw'];
            result.numberformat = {itut: true};
          } break;
          default: {
            result.host = this.other.address.getValue();
            result.fromuser = this.other.login.getValue();
            result.username = result.fromuser;
            result.authuser = result.fromuser;
            result.fromdomain = this.other.domian.getValue();
            result.secret = this.other.password.getValue();
            result.outboundproxy = this.other.advanced.proxy.getValue();
            result.transport = this.other.advanced.protocol.getValue();
            if(result.transport != 'udp') result.transport += ',udp';
            result.port = this.other.advanced.port.getValue();
            result.codecs = ['opus', 'g729', 'ulaw', 'alaw'];
            result.numberformat = this.numberformat.view.getValue();
          } break;
        }
        return result;
      }

      function setMode(mode) {
        switch(mode) {
          case 'basic': {
            this.other.advanced.hide();
          } break;
          case 'advanced': {
            this.other.advanced.show();
          } break;
          case 'expert': {
            this.other.advanced.show();
          } break;
        }
      }

      function onSelectProvider(sender) {
        for(provider in this.providers) {
          this[this.providers[provider].id].hide(); 
        };
        switch(sender.getValue()) {
          case 'ruscomp': this.ruscomp.show(); break;
          case 'multifon': this.multifon.show(); break;
          case 'sipnet': this.sipnet.show(); break;
          default: this.other.show(); break;
        }
      }

    </script>
    <?php
  }

}

?>
