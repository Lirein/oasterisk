<?php

namespace core;

use stdClass;

class LicensesREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'licenses';
  }

  private static function licensecmp(&$a, &$b) {
    if($a->valid === $b->valid) {
      return strcmp($a->id, $b->id);
    }
    return ($a->valid < $b->valid)?-1:1;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "upload": {
        if(isset($request_data->codename)&&isset($request_data->data)&&self::checkPriv('settings_writer')) {
          $license = \module\Licenses::find($request_data->codename);
          if($license->activate($request_data->data)){
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Не удается активировать лицензию');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      }
      case "csr": {
        if(isset($request_data->codename)&&self::checkPriv('settings_writer')) {
          try {
            $license = \module\Licenses::find($request_data->codename);
            $result = self::returnResult(urlencode($license->createRequest()));
          } catch(\Exception $e) {
            $result = self::returnError('danger', $e->getMessage());
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "remove": {
        if(isset($request_data->codename)&&self::checkPriv('settings_writer')) {
          try {
            $license = \module\Licenses::find($request_data->codename);
            if($license->delete()) {
              $result = self::returnSuccess();
            } else {
              $result = self::returnError('danger', 'Невозможно удалить файл лицензии');
            }
          } catch(\Exception $e) {
            $result = self::returnError('danger', $e->getMessage());
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "get": {
        $return_data = new stdClass();
        $list = array();
        $licenses = new \module\Licenses();
        foreach($licenses as $module) {
          $licenseInfo = $module->cast();
          $entry = new \stdClass();
          $entry->title = $licenseInfo->name;
          $entry->id = $licenseInfo->codename;
          $entry->valid = $licenseInfo->valid;
          $entry->location = new \stdClass();
          if($licenseInfo->license) {
            $entry->location->country = $licenseInfo->license->country;
            $entry->location->city = $licenseInfo->license->location;
            $entry->location->region = $licenseInfo->license->region;
            $entry->company = $licenseInfo->license->org;
            $entry->certserial = $licenseInfo->license->hash;
            $entry->diskserial = $licenseInfo->license->serial;
            $entry->validfrom = $licenseInfo->license->from;
            $entry->validto = $licenseInfo->license->to;
          } else {
            $entry->location->country = null;
            $entry->location->city = null;
            $entry->location->region = null;
            $entry->company = null;
            $entry->certserial = null;
            $entry->diskserial = null;
            $entry->validfrom = null;
            $entry->validto = null;
          }
          $entry->agreement = $licenseInfo->agreement;
          $list[] = $entry;
        }
        uasort($list, array(__CLASS__, 'licensecmp'));
        $return_data->licenses = array_values($list);
        $result=self::returnResult($return_data);
      } break;
    }
    return $result;
  }
    
}

?>