<!DOCTYPE html>
<html lang="ru">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="minimum-scale=1, initial-scale=1, width=device-width" />

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <!-- Fonts to support Material Design -->
    <link rel="stylesheet" href="<?=fingerprint('/css/roboto.css')?>" />
    <!-- Icons to support Material Design -->
    <link rel="stylesheet" href="<?=fingerprint('/css/Chart.min.css')?>">
    <title></title>
  </head>
  <body style='margin: 0px;'>
    <header>
    </header>
    <section class='dialogs'></section>
    <main style='background-color: #f5f5f5; min-height: 100vh;'>
    <div class='alerts'></div>
    <nav style="display: flex;">
     <div class='mainmenu'></div>
     <content style="flex-grow: 1; height: 100vh; padding-left: 12px; padding-right: 12px;"></content>
    </nav>
    </main>
    <footer>
    </footer>
    <!-- jQuery first, then Tether, then Bootstrap JS. -->
    <script src="<?=fingerprint('/js/clsx.min.js')?>"></script>
    <script src="<?=fingerprint('/js/moment-with-locales.js')?>"></script>
    <script src="<?=fingerprint('/js/react.development.js')?>"></script>
    <script src="<?=fingerprint('/js/react-dom.development.js')?>"></script>
    <script src="<?=fingerprint('/js/material-ui.development.js')?>"></script>
    <script src="<?=fingerprint('/js/material-ui-pickers.umd.min.js')?>"></script>
    <script src="<?=fingerprint('/js/asterisk.js')?>"></script>
    <script src="<?=fingerprint('/js/material-icons.js')?>"></script>
    <script src="<?=fingerprint('/js/reactTextMask.js')?>"></script>
    <script src="<?=fingerprint('/js/widgets.js')?>"></script>
    <script>
      var colorscheme = maintheme;
      var alerts = document.querySelector('div.alerts');
      var rootcontent = new widgets.section(document.querySelector('content'), {grayscaled: true});
      rootcontent.renderLock();
      window.onresize=function() {
        rootcontent.resize();
      }
      rootcontent.nogap = true;
      rootcontent.height = '100%';
      rootcontent.itemsalign = {xs: 12, sm: 6, md: 4, lg: 3, xl: 2};
      rootcontent.nested = new widgets.section(rootcontent);
      rootcontent.nested.paper = true;
      rootcontent.login = new widgets.input(rootcontent.nested, {id: 'login', value: ''}, _('Пользователь'), _('Введите имя пользователя'));
      rootcontent.password = new widgets.input(rootcontent.nested, {id: 'passwd', value: '', password: true, secure: true}, _('Пароль'), _('Введите свой пароль'));
      rootcontent.submit = new widgets.button(rootcontent.nested, {color: 'primary'}, _('Войти'));
      rootcontent.submit.selfalign = {xs: 12, style: {textAlign: 'center'}};
      rootcontent.submit.onClick = () => {
        sendSingleRequest('login', rootcontent.getValue(), location.href).success(() => {
          location.reload();
        });
      }
      rootcontent.login.onEnter = rootcontent.submit.onClick;
      rootcontent.password.onEnter = rootcontent.submit.onClick;
      rootcontent.renderUnlock();
      rootcontent.render();
    </script>
  </body>
</html>
