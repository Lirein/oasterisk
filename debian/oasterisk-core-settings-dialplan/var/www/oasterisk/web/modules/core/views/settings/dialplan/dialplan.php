<?php

namespace core;

class DialplanSettingsView extends ViewModule {

  public static $dialplan = null;

  public static function init() {
    if(!isset(self::$dialplan)) {
      self::$dialplan = new \core\Dialplan();
    }
  }

  public static function getLocation() {
    return 'settings/dialplan/common';
  }

  public static function getMenu() {
    return (object) array('name' => 'Редактор', 'prio' => 100, 'icon' => 'oi oi-excerpt');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('dialplan_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public static function getZoneInfo() {
    $result = new \SecZoneInfo();
    $result->zoneClass = 'dialplan_context';
    $result->getObjects = function () {
                              self::init();
                              return self::$dialplan->getContexts();
                            };
    return $result;
  }


  public function json(string $request, \stdClass $request_data) {
    self::init();
    $result = self::$dialplan->json($request, $request_data);
    return $result;
  }

  public function scripts() {
    self::init();
    ?>
    <script>
      var context_id='<?php echo @$_GET['id']; ?>';
    <?php
    self::$dialplan->scripts();
    ?>

      function updateContexts() {
        sendRequest('context-list').success(function(data) {
          var hasactive=false;
          var items=[];
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              if(data[i].id==context_id) hasactive=true;
              if(data[i].title=='') {
                data[i].title=data[i].id;
              }
              items.push({id: data[i].id, title: data[i].title, active: data[i].id==context_id});
            }
          };
          rightsidebar_set('#sidebarRightCollapse', items);
          if(!hasactive) {
            var content=$('#content');
            content.addClass('invisible');
            window.history.pushState(context_id, $('title').html(), '/'+urilocation);
            context_id='';
            rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
            sidebar_apply(null);
            if(data.length>0) loadContext(data[0].id);
          } else {
            loadContext(context_id);
          }
        });
      }

      function loadContext(acontext) {
        var content=$('#content');
        var context=null;
        if(content.find('>div').length) {
          context=content.find('>div').get(0).widget;
        } else {
          context=new widgets.context(content.get(0));
        }
        context.load(acontext, function(data) {
          rightsidebar_activate('#sidebarRightCollapse', acontext);
          rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
          sidebar_apply(sbapply);
          context_id=data.id;
          window.history.pushState(context_id, $('title').html(), '/'+urilocation+'?id='+context_id);
          content.removeClass('invisible');
          content.parent().find('#context-id').val(data.id);
          content.parent().find('#context-title').val(data.title);
<?php
  if(!self::checkPriv('dialplan_writer')) {
?>
          content.parent().find('input').prop('disabled', true);
<?php
  }
?>
        });
      }

      function addContext() {
        var content=$('#content');
        var context=null;
        if(content.find('>div').length) {
          context=content.find('>div').get(0).widget;
        } else {
          context=new widgets.context(content.get(0));
        }
        context.setValue();
        rightsidebar_activate('#sidebarRightCollapse', null);
        rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
        sidebar_apply(sbapply);
        context_id='';
        window.history.pushState(context_id, $('title').html(), '/'+urilocation+'?id='+context_id);
        content.removeClass('invisible');
        content.parent().find('#context-id').val('new-context');
        content.parent().find('#context-title').val('Новый контекст');
      }

      function loadContext(acontext) {
        var content=$('#content');
        var context=null;
        if(content.find('>div').length) {
          context=content.find('>div').get(0).widget;
        } else {
          context=new widgets.context(content.get(0));
        }
        context.load(acontext, function(data) {
          rightsidebar_activate('#sidebarRightCollapse', acontext);
          rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
          sidebar_apply(sbapply);
          context_id=data.id;
          window.history.pushState(context_id, $('title').html(), '/'+urilocation+'?id='+context_id);
          content.removeClass('invisible');
          content.parent().find('#context-id').val(data.id);
          content.parent().find('#context-title').val(data.title);
<?php
  if(!self::checkPriv('dialplan_writer')) {
?>
          content.parent().find('input').prop('disabled', true);
<?php
  }
?>
        });
      }

      function sendContext() {
        var data = {};
        var props=$('#content').parent();
        data.oldcontext = context_id;
        data.context = props.find('#context-id').val();
        data.title = props.find('#context-title').val();
        data.extents = JSON.stringify(props.find('#content > div').get(0).widget.getValue().extents);
        sendRequest('context-set', data).success(function() {
          updateContexts();
          return true;
        });
      }


      function sbselect(e, item) {
        loadContext(item);
      }
            
<?php
  if(self::checkPriv('dialplan_writer')) {
?>

      function sbadd(e) {
        addContext();
      }

      function sbapply(e) {
        sendContext();
      }
         
      function sbdel(e) {
        removeContext();
      }

<?php
  } else {
?>

    var sbadd=null;
    var sbapply=null;
    var sbdel=null;

<?php
  }
?>

      $(function() {
        var items=[];
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();

        rightsidebar_set('#sidebarRightCollapse', items);
        rightsidebar_init('#sidebarRightCollapse', null, sbadd, sbselect);
        sidebar_apply(null);
        updateContexts();
      });
    </script>
    <?php
  }

  public function render() {
    self::init();
    ?>
         <div class="form-group row">
          <label for="context-title" class="col form-label">Отображаемое имя
           <span class="badge badge-pill badge-info" data-toggle="popover" data-placement="top" title="Отображаемое имя" data-content="Наименование контекста, отображаемое в графическом интерфейсе<br>Если указано пустое значение - используется <i>«Идентификатор контекста»</i>" data-trigger='hover' data-html=true>?</span>
          </label>
          <div class="col-12 col-md-7">
           <input class="form-control" type="text" value="" id="context-title">
          </div>
         </div>
         <div class="form-group row">
          <label for="context-id" class="col form-label">Идентификатор контекста
           <span class="badge badge-pill badge-info" data-toggle="popover" data-placement="top" title="Идентификатор контекста" data-content="Внутренний идентификатор контекста логики работы коллцентра" data-trigger='hover' data-html=true>?</span>
          </label>
          <div class="col-12 col-md-7">
           <input class="form-control" type="text" value="" id="context-id">
          </div>
         </div>
       <div class="form-row" id='content'>
       </div>
    <?php
  }

}

?>