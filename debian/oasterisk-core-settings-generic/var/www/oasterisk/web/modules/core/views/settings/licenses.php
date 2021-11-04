<?php

namespace core;

class LicensesSettings extends ViewModule {

  private static $licensemodules = null;

  protected static function init() {
    if(self::$licensemodules==null)
       self::$licensemodules=getModulesByClass('core\LicenseModule');
  }

  public static function getLocation() {
    return 'settings/licenses';
  }

  public static function getMenu() {
    return (object) array('name' => 'Лицензии', 'prio' => 12, 'icon' => 'oi oi-badge');
  }

  public static function check($write = false) {
    $result = true;
    $result &= self::checkPriv('settings_reader');
    return $result;
  }

  private static function licensecmp(&$a, &$b) {
    if($a->valid === $b->valid) {
      return strcmp($a->codename, $b->codename);
    }
    return ($a->valid < $b->valid)?-1:1;
  }

  /**
   * Создает новый запрос на сертификат для указанного модуля
   *
   * @param string $module_name
   * @return string Возвращает CSR запрос на сертификат в кодировке base64
   */
  public function createRequest(string $module_name) {
    $dn = array(
      "countryName" => "RU",
      "name" => $module_name,
      "serialNumber" => implode(',', $this->getRootSerial())
    );

    $privkey = openssl_pkey_new();
    $csr = openssl_csr_new($dn, $privkey);

    openssl_csr_export($csr, $csrout);
    return $csrout;
  }

  public function json(string $request, \stdClass $request_data) {

    self::init();
    $result = new \stdClass();
    switch($request) {
      case "upload": {
        if(isset($request_data->codename)&&isset($request_data->data)&&self::checkPriv('settings_writer')) {
          file_put_contents('../licensing/'.$request_data->codename.'.crt',$request_data->data);
          $result = self::returnSuccess();
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      }
      case "csr": {
        if(isset($request_data->codename)&&self::checkPriv('settings_writer')) {
          $result = self::returnResult(urlencode(self::createRequest($request_data->codename)));
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "list": {
        $list = array();
        foreach(self::$licensemodules as $module) {
          $info = $module->info();
          if($info->license) {
            $info->license->from = $info->license->from->format(\DateTime::ISO8601);
            $info->license->to = $info->license->to->format(\DateTime::ISO8601);
          }
          $list[]=$info;
        }
        uasort($list, array(__CLASS__, 'licensecmp'));
        $result=self::returnResult(array_values($list));
      } break;
    }
    return $result;
  }

  public function scripts() {
    self::init();
    ?>
    <script>
      var readed = false;

      function checkLicense(obj) {
        if($(obj).scrollTop() >= (obj.scrollHeight - $(obj).height()-40))
          readed = true;
        if(readed) $('#license-apply').prop('disabled',false);
      }

      function showLicense(caption, text, func) {
        var content = $('#license-content').html(decodeURI(text)).find('#license-name').html(decodeURI(caption));
        $('#license-name').html(decodeURI(caption));
        $('#license-dialog').find('#modal-result').val('none');
        if(typeof func === 'function') {
          $('#license-apply').prop('disabled',true).show().next().text('Не принимаю');;
          $('#license-dialog').modal('show').on('hidden.bs.modal', function(e) { func($(this).find('#modal-result').val()); $(e.target).unbind('hidden.bs.modal'); }).on('shown.bs.modal', function(e) {setTimeout(function(e) {readed = false; $('#license-content').scrollTop(0); $('#license-apply').prop('disabled',true)}, 100); $(e.target).unbind('shown.bs.modal'); });
        } else {
          $('#license-apply').hide().next().text('Закрыть');
          $('#license-dialog').modal('show').on('shown.bs.modal', function(e) { setTimeout(function(e) {$('#license-content').scrollTop(0);},100); $(e.target).unbind('shown.bs.modal'); });
        }
      }

      function setResult(obj, text) {
        $(obj).parent().parent().find('#modal-result').val(text);
      }

      function upload(event, obj) {
        var reader = new FileReader();
        reader.onloadend = function(evt) {
          if(evt.target.readyState == FileReader.DONE) {
            sendRequest('upload', {codename: obj.id, data: evt.target.result}).success(function() {
              showalert('success','Файл лицензии успешно загружен');
              updateLicenses();
              return false;
            });
          }
        }
        reader.readAsBinaryString(event.target.files[0]);
      }

      function getCSR(codename) {
        sendRequest('csr', {codename: codename}).success(function(data) {
          window.open('https://cert.oas.su/license?req='+data, '_blank');
        });
      }

      function updateLicenses() {
        sendRequest('list').success(function(data) {
          var licenses=$('#license-list').html('');
          if(data.length) {
            for(var i = 0; i < data.length; i++) {
              var item=$('<div class="p-2 col-sm-12 col-lg-6 col-xl-4"><div class="card mb-1"></div></div>').appendTo(licenses).children();
              var itemhead=$('<div class="card-header">'+data[i].name+'</div>').appendTo(item);
              var itembody=$('<div class="card-body text-dark pb-3" style="line-height: 1rem; background-color: #fbfbfa !important;"></div>').appendTo(item);
              if(data[i].license) {
                var itemtitle=$('<h5 class="card-title">'+data[i].license.org+'</h5>').appendTo(itembody);
                $('<span class="d-block w-100 mb-1"><span class="w-25 d-inline-block">От:</span><span class="d-inline-block">'+getDateTime(new Date(data[i].license.from))+'</span></span>').appendTo(itembody);
                $('<span class="d-block w-100 mb-1"><span class="w-25 d-inline-block">До:</span><span class="d-inline-block">'+getDateTime(new Date(data[i].license.to))+'</span></span>').appendTo(itembody);
              } else {
                var itemtitle=$('<span class="text-center w-100 d-block"><h6 class="card-title">Лицензия не активирована</h6></span>').appendTo(itembody);
              }
              if(data[i].license&&data[i].valid) {
                item.addClass('bg-success').addClass('text-white');
                itembody.append('<span class="text-center w-100 d-block mb-2"><button class="d-inline-block btn btn-secondary btn-sm mt-2" onClick="showLicense(\''+encodeURI(data[i].name).replace(/\'/g,'\\\'')+'\', \''+encodeURI(data[i].agreement).replace(/\'/g,'\\\'')+'\')">Просмотреть соглашение</button></span>');
              } else {
                if(data[i].license) item.addClass('bg-danger').addClass('text-white');
<?php if(self::checkPriv('settings_writer')) { ?>
                itembody.append('<button class="btn btn-secondary mt-2" onClick="showLicense(\''+encodeURI(data[i].name).replace(/\'/g,'\\\'')+'\', \''+encodeURI(data[i].agreement).replace(/\'/g,'\\\'')+'\', function(e) { if(e==\'ok\') getCSR(\''+data[i].codename+'\')})">Запросить</button><input type="file" id="'+data[i].codename+'" class="custom-file-input" style="min-width:0px; max-width:0px;" onChange="upload(event,this)" accept=".crt,.cer"><button class="btn btn-info mt-2 float-right" onClick="$(event.target).prev().click()">Активировать</button>');
<?php } else { ?>
                itembody.append('<span class="text-center w-100 d-block mb-2"><button class="d-inline-block btn btn-secondary btn-sm mt-2" onClick="showLicense(\''+encodeURI(data[i].name).replace(/\'/g,'\\\'')+'\', \''+encodeURI(data[i].agreement).replace(/\'/g,'\\\'')+'\')">Просмотреть соглашение</button></span>');
<?php } ?>
              }
            }
          };
        });
      }

      $(function () {
        updateLicenses();
        $('#license-content').scroll(function() { checkLicense(this); });

      });
    </script>
    <?php
  }

  public function render() {
    self::init();
    ?>
<div class="modal fade" id='license-dialog'>
 <div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
   <div class="modal-header">
    <h5 class="modal-title">Лицензионное соглашение</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
     <span aria-hidden="true">&times;</span>
    </button>
   </div>
   <div class="modal-body">
    <div class="form-group">
     <label for="exampleFormControlTextarea1">Текст лицензионного соглашения на <span id='license-name'></label>
     <div class="form-control" id="license-content" style="width: 100%; height: 400px; display: block; overflow-y: scroll;"></div>
    </div>
   </div>
   <div class="modal-footer">
    <input type='hidden' id='modal-result' value=''>
    <button type="button" class="btn btn-success" id='license-apply' data-dismiss="modal" disabled onClick='setResult(this, "ok")'>Принимаю</button>
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Не принимаю</button>
   </div>
  </div>
 </div>
</div>
       <div class="form-group">
        <div class="d-flex flex-wrap col-12 p-1" id="license-list">
        </div>
       </div>
    <?php
  }

}

?>
