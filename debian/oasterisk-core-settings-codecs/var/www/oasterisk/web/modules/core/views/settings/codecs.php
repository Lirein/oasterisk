<?php

namespace core;

class CodecsSettings extends ViewModule {

  public static function getLocation() {
    return 'settings/general/codecs';
  }

  public static function getMenu() {
    return (object) array('name' => 'Параметры кодеков', 'prio' => 7, 'icon' => 'oi oi-pulse');
  }

  public static function check() {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    $codecs = new \core\Codecs();
    switch($request) {
      case "codec": {
        if(isset($request_data->class)) {
          $codec = $codecs->get($request_data->class);
          if($codec) {
            $codecinfo = new \stdClass();
            $codecinfo->info = $codec->title;
            $codecinfo->html = $codec->codec->render();
            $codecinfo->props = $codec->codec->getProp();
            $result = self::returnResult($codecinfo);
          } else {
            $result = self::returnError('danger', 'Кодек не найден');
          }
        }
      } break;
      case "set": {
        if(isset($request_data->class)&&isset($request_data->props)&&($request_data->props!=='false')) {
          if(self::checkPriv('settings_writer')) {
            $codec = $codecs->get($request_data->class);
            if($codec) {
              $status = $codec->codec->setProp($request_data->props);
              if($status) {
                $result = self::returnSuccess();
              } else {
                $result = self::returnError('danger', 'Не удалось установить параметры кодека');
              }
            } else {
              $result = self::returnError('danger', 'Кодек не найден');
            }
          } else {
            $result = self::returnError('danger', 'Отказано в доступе');
          }
        } else {
          $result = self::returnError('danger', 'Не передан класс кодека или его параметры');
        }
      } break;
    }
    return $result;
  }

  public function scripts() {
    $codecs = new \core\Codecs();
    $codecs = $codecs->get();
    ?>
    <script>
      var current_codec=null;

      function saveCodec(codec) {
        var props = $('#codec-properties input');
        var proplist = {};
        for(var i=0; i<props.length; i++) {
          if(props[i].id!='codec-name') {
            var val;
            if(props[i].type=='checkbox') val=$(props[i]).prop('checked');
            else val=$(props[i]).val();
            proplist[props[i].id] = val;
          }
        }
        sendRequest('set', {class: codec, props: proplist});
      }

      function loadCodec(codec) {
        sendRequest('codec', {class: codec}).success(function(data) {
          rightsidebar_activate('#sidebarRightCollapse', codec);
          sidebar_apply(sbapply);
          current_codec=codec;
          var props = $('#codec-properties');
          props.html('<div class="form-group row"><label for="codec-name" class="col form-label">Наименование кодека</label><div class="col-12 col-md-7"><input class="form-control" type="text" value="'+data.info+'" id="codec-name" disabled></div></div>');
          props.append(data.html);
          for(var i in data.props) {
            if((typeof data.props[i])=='boolean') {
              props.find('#'+i).prop('checked',data.props[i]);
            } else if(((typeof data.props[i])=='string')&&((data.props[i]=='false')||(data.props[i]=='true'))) {
              props.find('#'+i).prop('checked', (data.props[i]=='true'));
            } else {
              props.find('#'+i).val(data.props[i]);
            }
          }
          props.find('[data-toggle="tooltip"]').tooltip();
          props.find('[data-toggle="popover"]').popover();
<?php
  if(!self::checkPriv('settings_writer')) {
?>
          props.find('input').prop('disabled',true);
<?php
  }
?>
        });
      }

<?php
  if(self::checkPriv('settings_writer')) {
?>

      function sbapply(e) {
        saveCodec(current_codec);
      }

<?php
  } else {
?>

    var sbapply=null;

<?php
  }
?>

      function sbselect(e, sel) {
        loadCodec(sel);
      }

      $(function () {
        $('#codec-list button:first-child').trigger('click');
        var items=[];
    <?php
      foreach($codecs as $codec) {
        if(method_exists($codec->codec, 'render')) printf("items.push({id: '%s', title: '%s'});\n", $codec->name, $codec->title);
      }
    ?> 
        rightsidebar_set('#sidebarRightCollapse', items);
        rightsidebar_init('#sidebarRightCollapse', null, null, sbselect);
        sidebar_apply(null);
        if(items.length>0) {
          loadCodec(items[0].id);
        }
      })
    </script>
    <?php
  }

  public function render() {
   ?>
 <form>
  <div class="col" id='codec-properties'>
  </div>
 </form>
    <?php
  }

}

?>