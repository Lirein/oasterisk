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
    <main style='background-color: #fff; min-height: 100%;'>
    <div class='alerts'></div>
    <nav style="display: flex;">
     <div class='mainmenu'></div>
     <content style="flex-grow: 1;"></content>
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
    <script src="<?=fingerprint('/js/ReactDnD.min.js')?>"></script>
    <script src="<?=fingerprint('/js/Chart.min.js')?>"></script>
    <script src="<?=fingerprint('/js/FileSaver.min.js')?>"></script>
    <script src="<?=fingerprint('/js/react-chartjs-2.min.js')?>"></script>
    <script src="<?=fingerprint('/js/RecordRTC.min.js')?>"></script>
    <script src="<?=fingerprint('/js/widgets.js')?>"></script>
    <script>
      var userdata = null;
      var urilocation = '<?=$location?>';
      var colorscheme = maintheme;
      var appbar = null;
      var dialogcontent = null;
      var rootcontent = null;
      var alerts = document.querySelector('div.alerts');
      (async () => {
        try {
          let colors = await asyncRequest('get', {}, 'rest/general/appearence');
          if(colors) {
            if(isSet(colors.user)) colors = colors.user;
            else if(isSet(colors.group)) colors = colors.group;
            else if(isSet(colors.system)) colors = colors.system;
            else if(isSet(colors.defaults)) colors = colors.defaults;
            maintheme = createMuiTheme({
              palette: {
                primary: {
                  main: colors.primary,
                },
                secondary: {
                  main: colors.secondary,
                },
                success: {
                  main: colors.success,
                },
                info: {
                  main: colors.info,
                },
                warning: {
                  main: colors.warning,
                },
                error: {
                  main: colors.error,
                }
              },
            });
            darktheme = createMuiTheme({
              palette: {
                primary: {
                  main: colors.secondary,
                },
                secondary: {
                  main: colors.primary,
                },
                success: {
                  main: colors.success,
                },
                info: {
                  main: colors.info,
                },
                warning: {
                  main: colors.warning,
                },
                error: {
                  main: colors.error,
                }
              },
            });
          }
        } finally {

        }
        colorscheme = ((urilocation.split('/')[0]=='settings')?darktheme:maintheme);
        appbar = new widgets.appbar(document.querySelector('header'), null, "");
        appbar.mainmenu = new widgets.mainmenu(document.querySelector('nav > div.mainmenu'));
        sendRequest('getmenu', {}, 'rest/core').success(function(data) {
          appbar.mainmenu.setValue({items: data});
        });
        sendRequest('get', {id: '<?=$moduleclass::$user->id?>'}, 'rest/security/user').success(function(data) {
          userdata = data;
          appbar.mainmenu.render();
        });
        dialogcontent = new widgets.section(document.querySelector('section.dialogs'));
        dialogcontent.itemsalign = null;
        rootcontent = new widgets.section(document.querySelector('content'));
        rootcontent.renderLock();
        rootcontent.setLabel = appbar.setLabel; //Смена заголовка
        rootcontent.setReset = appbar.setReset; //Установка кнопки "Сбросить"
        rootcontent.setApply = appbar.setApply; //Установка кнопки "Сохранить"
        rootcontent.setAppend = appbar.setAppend; //Установка кнопки "Создать"
        rootcontent.setElement = appbar.setElement; //Смена выделенного объекта
        appbar.onReturn = (sender) => {
          rootcontent.onReturn(sender);
        }
        window.onpopstate = function(event) {
          loadLocation(document.location.pathname.substr(1), event.state);
        }

        window.onresize=function() {
          rootcontent.resize();
        }
        setViewMode('<?=$moduleclass::$user->getViewMode()?>');

        rootcontent.renderUnlock();
        loadLocation(urilocation);
      })();
    </script>
  </body>
</html>
