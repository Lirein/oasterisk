<!DOCTYPE html>
<html lang="ru">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?=fingerprint('/css/bootstrap.min.css')?>">
    <link rel="stylesheet" href="<?=fingerprint('/css/bootstrap-datetimepicker.css')?>">
    <link rel="stylesheet" href="<?=fingerprint('/css/bootstrap-colorpicker.min.css')?>">
    <link rel="stylesheet" href="<?=fingerprint('/css/select2.min.css')?>">
    <link rel="stylesheet" href="<?=fingerprint('/css/select2-bootstrap.min.css')?>">
    <link rel="stylesheet" href="<?=fingerprint('/css/open-iconic-bootstrap.min.css')?>">
    <link rel="stylesheet" href="<?=fingerprint('/css/gijgo/core.min.css')?>">
    <link rel="stylesheet" href="<?=fingerprint('/css/gijgo/checkbox.min.css')?>">
    <link rel="stylesheet" href="<?=fingerprint('/css/gijgo/datepicker.min.css')?>">
    <link rel="stylesheet" href="<?=fingerprint('/css/gijgo/tree.min.css')?>">
    <link rel="stylesheet" href="<?=fingerprint('/css/Chart.min.css')?>">
    <link rel="stylesheet" href="/?json=colorcss">
    <link rel="stylesheet" href="<?=fingerprint('/css/sidebar.css')?>">
    <link rel="stylesheet" href="<?=fingerprint('/css/dashboard.css')?>">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <!-- jQuery first, then Tether, then Bootstrap JS. -->
    <script src="<?=fingerprint('/js/core.js')?>"></script>
    <script src="<?=fingerprint('/js/jquery.min.js')?>"></script>
    <script src="<?=fingerprint('/js/tether.min.js')?>"></script>
    <script src="<?=fingerprint('/js/asterisk.js')?>"></script>
    <script src="<?=fingerprint('/js/widgets.js')?>"></script>
    <script>
      if(isTouchDevice()) {
        $("<link/>", {
          rel: "stylesheet",
          type: "text/css",
          href: "/css/sidebar-touch.css"
        }).appendTo("head");
      }
    </script>
    <title><?= $moduleclass::getMenu()->name ?></title>
      <?php
       $locmodules = array();
       $loc=explode('/',$location);
       $locpath=array_shift($loc);
       foreach($loc as $locpart) {
         $locpath.='/'.$locpart;
         $submodule = findModuleByPath($locpath);
         if($submodule) {
           $submoduleclass = $submodule->class;
           $submoduleobj =  new $submoduleclass();
           $locmodules[] = (object) array('path' => $submodule->path, 'module' => $submoduleobj);
         }
       }
      ?>
  </head>
  <body>
    <div class="modal fade" id='messagebox'>
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header" style="border-top-left-radius: .3rem; border-top-right-radius: .3rem;">
            <h5 class="modal-title"></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" id="OK">ОК</button>
            <button type="button" class="btn btn-success" id="Yes">Да</button>
            <button type="button" class="btn btn-success" id="YesToAll">Да для всех</button>
            <button type="button" class="btn btn-success" id="Copy">Копировать</button>
            <button type="button" class="btn btn-warning" id="No">Нет</button>
            <button type="button" class="btn btn-warning" id="NoToAll">Нет для всех</button>
            <button type="button" class="btn btn-warning" id="Rename">Переименовать</button>
            <button type="button" class="btn btn-success" id="Apply">Принять</button>
            <button type="button" class="btn btn-danger" id="Cancel">Отмена</button>
          </div>
        </div>
      </div>
    </div>
    <div class='alerts col-xs-12 col-sm-6 col-md-4 col-lg-3'></div>
     <nav class="navbar navbar-expand-sm navbar-dark fixed-top bg-dark justify-content-start">
      <span class="navbar-brand"><img class="inverted" src="/favicon.svg" width="25" height="25" alt="OAS'terisk"> OAS'terisk</span>
      <div class="navbar-expand d-flex" id="navbarsExampleDefault" style="flex-grow: 1;">
        <ul class="navbar-nav mr-auto">
          <?=getMainMenu()?>
        </ul>
        <ul class="navbar-nav mr-right">
          <li class="nav-item d-flex"><a class='nav-link' href="#" onClick="logout()"><i class="oi oi-account-logout"></i><span class="d-none d-md-inline-block">Выход</span></a></li>
        </ul>
        <button class="navbar-toggler navbar-nav d-flex d-sm-none" style="right: 0.5rem; top: 0.5rem;" type="button" data-toggle="collapse" data-target="#sidebarCollapse" aria-controls="sidebarCollapse" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
      </div>
    </nav>

    <main role="main" class="d-flex align-items-stretch pt-4 mt-2">
     <div class="sidebar" id='sidebarCollapse'>
      <div class="container">
        <ul class="list-unstyled" id="lmenuaccordion">
            <?=getLeftMenu()?>
        </ul>
        <ul class="list-unstyled list-unstyled-bottom bg-dark d-none d-sm-block">
            <li><a onClick='leftsidebar_toggle(this);'><i class="oi oi-fullscreen-exit"  style="transform: rotate(90deg);"></i><span></span></a></li>
        </ul>
      </div>
     </div>
     <div class="mx-3 container-fluid position-relative">
      <div id='root-content'>
      <?php
       foreach($locmodules as $module) {
         if(is_subclass_of($module->module, 'core\ViewModule')) $module->module->render();
       }
      ?>
      </div>
      <footer class='small'>
       <p>Copyright © 2019 — <a href='https://oas.su/' target='_blank'>«OAS» LLC</a>
      <?php
       $version = null;
       $path = $locmodules[count($locmodules)-1]->path;
       $locpath=dirname(__FILE__).'/modules/';
       $path = explode('/', substr($path, strlen($locpath)));
       foreach($path as $pathpart) {
         $locpath.=$pathpart.'/';
         if(file_exists($locpath.'version')) {
           $version = file_get_contents($locpath.'version');
         }
       }
       if($version!==null) {
         printf(" — version %s", $version);
       }
      ?>
       </p>
      </footer>
     </div>
     <div class="sidebar sidebar-right disabled" id='sidebarRightCollapse'>
      <div class="container">
        <ul class="list-unstyled" id='rmenuaccordion'>
        </ul>
        <ul class="list-unstyled list-unstyled-bottom row bg-dark">
            <li><a onClick='rightsidebar_toggle(this);'><i class="oi oi-fullscreen-exit d-none d-sm-inline-block"></i><i class="oi oi-fullscreen-enter d-sm-none"></i><span></span></a></li>
            <li class='bg-danger delbtn'><a id='delbtn'><i class="oi oi-trash"></i></a></li>
            <li class='bg-success addbtn'><a id='addbtn'><i class="oi oi-plus"></i></a></li>
        </ul>
      </div>
     </div>
     <button class='bg-danger sb-delbtn disabled' id='delbtn'><i class="oi oi-trash"></i></button>
     <button class='bg-success sb-addbtn disabled' id='addbtn'><i class="oi oi-plus"></i></button>
     <button class='bg-success sb-applybtn disabled' id='applybtn'><i class="oi oi-check"></i></button>
    </main>

    <script src="<?=fingerprint('/js/popper.min.js')?>"></script>
    <script src="<?=fingerprint('/js/bootstrap.min.js')?>"></script>
    <script src="<?=fingerprint('/js/moment-with-locales.js')?>"></script>
    <script src="<?=fingerprint('/js/bootstrap-datetimepicker.js')?>"></script>
    <script src="<?=fingerprint('/js/bootstrap-colorpicker.min.js')?>"></script>
    <script src="<?=fingerprint('/js/select2.min.js')?>"></script>
    <script src="<?=fingerprint('/js/gijgo/core.min.js')?>"></script>
    <script src="<?=fingerprint('/js/gijgo/datepicker.min.js')?>"></script>
    <script src="<?=fingerprint('/js/gijgo/checkbox.min.js')?>"></script>
    <script src="<?=fingerprint('/js/gijgo/tree.min.js')?>"></script>
    <script src="<?=fingerprint('/js/Chart.min.js')?>"></script>
    <script src="<?=fingerprint('/js/chartjs-plugin-colorschemes.min.js')?>"></script>
    <script src="<?=fingerprint('/locales/select2.ru.js')?>"></script>
    <script src="<?=fingerprint('/js/ie10-viewport-bug-workaround.js')?>"></script>
    <script src="<?=fingerprint('/js/contextmenu.js')?>"></script>
    <script src="<?=fingerprint('/js/table2CSV.js')?>"></script>
    <script src="<?=fingerprint('/js/FileSaver.min.js')?>"></script>
    <script src="<?=fingerprint('/js/Sortable.js')?>"></script>
    <script src="<?=fingerprint('/js/sidebar.js')?>"></script>
    <script>
      var urilocation = '<?=$location?>';
      var rootcontent = document.querySelector('#root-content');
      function logout() {
        $.getJSON('/?json=logout', function(data) {
          location.reload();
        });
        return false;
      }
      $(document).ready(function() {
        window.onresize=function() {
          for(var i=0; i<rootcontent.childNodes.length; i++) {
            if(typeof rootcontent.childNodes[i].widget != 'undefined') {
              rootcontent.childNodes[i].widget.resize();
            }
          }
        }
        if(localStorage.getItem("sidebar-right")!='true') {
          var $el = $('.sidebar-right span.sidebar-pin').parent();
          var $clone = $el.clone().appendTo($el.parent());
          $el.remove();
          $el = $clone;
          $el.addClass("hidden");
        }
      });
    </script>
      <?php
       foreach($locmodules as $module) {
         if(method_exists($module->module, 'scripts')) $module->module->scripts();
       }
      ?>
  </body>
</html>
