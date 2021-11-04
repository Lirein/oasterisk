<?php


set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__DIR__));

session_start();

preg_match('/([a-zA-Z0-9\-\/_]*).*/',$_SERVER['REQUEST_URI'],$match);
$location = $match[1];
if(!isset($_GET['json'])||($location!='/')) {
 $location=substr($location,1);
}
unset($match);

require 'core/asterisk.php';
require 'web/modules.php';
require 'web/menus.php';

$managecmds = array();
$permissions = array();

function SetObjectSuffix(&$obj, string $suffix) {
  $result = array();
  foreach($obj as $key => $value) {
    if(is_integer($key)) $key = 'f_'.$key;
    if(is_array($value)) {
      $result[$key] = SetObjectSuffix($value, $suffix);
    } else {
      $result[$key][$suffix] = $value;
    }
  }
  return $result;
}

function ConvertToObject(&$obj) {
  if(is_array($obj)) {
    $alphanum = false;
    foreach(array_keys($obj) as $key) $alphanum |= !is_numeric($key);
    if($alphanum) $obj = (object) $obj;
  }
  if(is_array($obj)) {
    foreach($obj as $key => $value) {
      ConvertToObject($obj[$key]);
    }
  } elseif(is_object($obj)) {
    foreach($obj as $key => $value) {
      ConvertToObject($obj->$key);
    }
  } elseif(is_string(($obj))) {
    if(($obj=='false')||($obj=='true')) {
      $obj = $obj=='true';
    }
  }
}

function startProcessing() {
  global $_AMI;
  global $_CACHE;
  global $_MANAGECMDS;
  global $location;
  $_AMI = new \AMI();
  if(empty(trim($_SESSION['login']))||!$_AMI->connect('[::1]', $_SESSION['login'], $_SESSION['passwd'])) {
    error_log('not connected with '.$_SESSION['login'].' with reason: '.$_AMI->lastlog);
    unset($_AMI);
    unset($GLOBALS['_AMI']);
  }
  if(isset($_AMI)) {
    $_CACHE = new \Memcached;
    $_CACHE->addServer('localhost',0);
    $_MANAGECMDS = $_AMI->listCommands();
    array_shift($_MANAGECMDS);
    array_shift($_MANAGECMDS);
    $_MANAGECMDS=array_keys($_MANAGECMDS);
    updateModules();
    $module = getModuleByPath($location);
    if(!isset($_POST['json'])) {
      if(!$module) {
        $newlocation = getSiblingPath($location);
        if($newlocation) $location = $newlocation;
        $module = getModuleByPath($location);
      }
    }
    if($module) {
      global $moduleclass;
      $moduleclass=get_class($module);
      session_write_close();
      if((strpos($location, 'rest/')===0)||(strpos($location, 'view/')===0)||$moduleclass::checkScope($location)) {
        if(isset($_GET['json'])) {
          $json_result = new stdClass();
          $request = $_GET['json'];
          unset($_GET['json']);
          $requestdata = (object) array_merge($_GET, $_POST);
          if(isset($_FILES)&&count($_FILES)) {
            object_merge($requestdata, (object) $_FILES);
          }
          if(strpos($location, 'rest/')===0) {
            $submodule = getModuleByPath($location);
            if($submodule&&is_subclass_of($submodule, 'module\\IJSON')) $json_result = object_merge($json_result, $submodule->json($request, $requestdata));
          } else {
            $loc=explode('/',$location);
            $locpath='';
            foreach($loc as $locpart) {
              if($locpath!='') $locpath.='/';
              $locpath.=$locpart;
              $submodule = getModuleByPath($locpath);
              if($submodule&&is_subclass_of($submodule, 'module\\IJSON')) $json_result = object_merge($json_result, $submodule->json($request, $requestdata));
            }
          }
        } elseif((isset($_POST['json'])&&is_array($_POST['json']))||(isset($_FILES['json']))) {
          $json_result = new stdClass();
          $json_result->results = new stdClass();
          if(isset($_FILES['json'])&&isset($_FILES['json']['tmp_name'])) {
            foreach(array_keys($_FILES['json']['tmp_name']) as $filerequest) {
              if(!isset($_POST['json'][$filerequest])) $_POST['json'][$filerequest] = array();
            }
          }
          foreach($_POST['json'] as $request => $requestdata) {
            $requestdata = (object) $requestdata;
            if(isset($_FILES['json'])&&isset($_FILES['json']['tmp_name'][$request])) {
              $file = array();
              $file = array_merge_recursive($file, SetObjectSuffix($_FILES['json']['tmp_name'][$request], 'tmp_name'));
              $file = array_merge_recursive($file, SetObjectSuffix($_FILES['json']['name'][$request], 'name'));
              $file = array_merge_recursive($file, SetObjectSuffix($_FILES['json']['type'][$request], 'type'));
              $file = array_merge_recursive($file, SetObjectSuffix($_FILES['json']['error'][$request], 'error'));
              $file = array_merge_recursive($file, SetObjectSuffix($_FILES['json']['size'][$request], 'size'));
              $requestdata = object_merge($requestdata, (object) $file);
            }
            ConvertToObject($requestdata);
            $_GET = $_POST = $_FILES = array();
            $json_result->results->$request = new stdClass();
            if(strpos($location, 'rest/')===0) {
              $submodule = getModuleByPath($location);
              if($submodule&&is_subclass_of($submodule, 'module\\IJSON')) $json_result->results->$request = object_merge($json_result->results->$request, $submodule->json($request, $requestdata));
            } else {
              $loc=explode('/',$location);
              $locpath='';
              foreach($loc as $locpart) {
                if($locpath!='') $locpath.='/';
                $locpath.=$locpart;
                $submodule = getModuleByPath($locpath);
                if($submodule&&is_subclass_of($submodule, 'module\\IJSON')) $json_result->results->$request = object_merge($json_result->results->$request, $submodule->json($request, $requestdata));
              }
            }
          }
        } elseif((strpos($location, 'view/')===0)&&($module instanceof \view\IViewPort)) {
          header('Content-Type: application/javascript');
          $viewlocation = $moduleclass::getViewLocation();
          if($module instanceof \view\Menu) {
            $menu = $moduleclass::getMenu();
          } else {
            $menu = null;
          }
          $api = $moduleclass::getAPI();
          if(empty($viewlocation)) $viewlocation = $moduleclass::getLocation();
          printf("views['%s'] = class %sView extends viewport {\n", str_replace('\\', '/', $viewlocation), str_replace('/', '_', $viewlocation));
          ob_start();
          $module->implementation();
          $buffer = str_replace('function ', '', str_replace('</script>', '', str_replace('<script>', '', ob_get_contents())));
          ob_clean();         
          printf("%s}\nviews['%s'].defaultapi = '%s';\n", $buffer, str_replace('\\', '/', $viewlocation), ($api=='')?'':('rest/'.$api));
          printf("views['%s'].title = '%s';\n", str_replace('\\', '/', $viewlocation), (($menu)?($menu->name):''));
        } else {
          if($_SERVER['REQUEST_METHOD'] == 'GET') {
            include dirname(__DIR__).'/web/index.php';
          }
        }
        if(isset($json_result->content)&&isset($json_result->content_type)) {
          header('Content-Type: ' . $json_result->content_type);
          if(isset($json_result->content_name)) header('Content-Disposition: attachment; filename='.$json_result->content_name);
          header('Expires: 0');
          header('Cache-Control: must-revalidate');
          header('Pragma: public');
          header('Content-Length: ' . strlen($json_result->content));
          printf("%s", $json_result->content);
        } else {
          if(isset($json_result)) {
            $encoded_result = json_encode($json_result);
            if($encoded_result === false) {
              $json_error = json_last_error();
              switch ($json_error) {
                case JSON_ERROR_NONE:
                  $json_error = 'Ошибок нет';
                break;
                case JSON_ERROR_DEPTH:
                  $json_error = 'Достигнута максимальная глубина стека';
                break;
                case JSON_ERROR_STATE_MISMATCH:
                  $json_error = 'Некорректные разряды или несоответствие режимов';
                break;
                case JSON_ERROR_CTRL_CHAR:
                  $json_error = 'Некорректный управляющий символ';
                break;
                case JSON_ERROR_SYNTAX:
                  $json_error = 'Синтаксическая ошибка, некорректный JSON';
                break;
                case JSON_ERROR_UTF8:
                  $json_error = 'Некорректные символы UTF-8, возможно неверно закодирован';
                break;
                case JSON_ERROR_RECURSION:
                  $json_error = 'Обнаружена рекурсия';
                break;
                case JSON_ERROR_INF_OR_NAN:
                  $json_error = 'Неопределенное значение или бесконечность';
                break;
                case JSON_ERROR_UNSUPPORTED_TYPE:
                  $json_error = 'Не поддерживаемый тип данных';
                break;
                case JSON_ERROR_INVALID_PROPERTY_NAME:
                  $json_error = 'Некорректное имя свойства';
                break;
                default:
                  $json_error = 'Неизвестная ошибка: '.json_last_error_msg();
                break;
              }
              if(isset($json_result->status)) {
                $json_result = \Module::returnError('danger', 'JSON: '.$json_error);
              } else {
                foreach($json_result->results as $request => $result) {
                  $json_result->results->$request = \Module::returnError('danger', 'JSON: '.$json_error);
                }
              }
              $encoded_result = json_encode($json_result);
            }
            echo $encoded_result;
          }
        }
      } else {
        header('HTTP/1.0 403 Forbidden');
        include dirname(__DIR__).'/web/e403.php';
      }
    } else {
      if(isset($_GET['json'])&&($location=='/')) {
        include dirname(__DIR__).'/web/json.php';
      } else {
        header('HTTP/1.0 404 Forbidden');
        include dirname(__DIR__).'/web/e404.php';
      }
    }
    try {
      $_AMI->disconnect();
    } catch(\Exception $e) {
      ;
    }
  }
}

if(isset($_SESSION['login'])) {
  startProcessing();
}

if(!isset($_AMI)) {
  if(isset($_GET['json'])&&($_GET['json']=='login')) {
    $result = (object)array('status' => 'success', 'result' => null);
    if(isset($_POST['login'])) {
      $_SESSION['login'] = $_POST['login'];
      $_SESSION['passwd'] = $_POST['passwd'];
      $_AMI = new \AMI();
      if(!$_AMI->connect('[::1]', $_SESSION['login'], $_SESSION['passwd'])) {
        $result->status = 'danger';
        $result->statustext = 'Неверное имя пользователя или пароль';
      } else {
        $_AMI->disconnect();
      }
      unset($_AMI);
    } else {
      $result->status = 'danger';
      $result->statustext = 'Имя пользователя не указано';
    }
    echo json_encode($result);
  } else if(isset($_GET['json'])&&($_GET['json']=='run')&&isset($_GET['run'])) {
    if(isset($_GET['login'])) {
      $_SESSION['login'] = $_GET['login'];
      $_SESSION['passwd'] = $_GET['passwd'];
      $_GET['json']=$_GET['run'];
      $_POST = $_GET;
      unset($_POST['run']);
      unset($_POST['json']);
      startProcessing();
    }
  } else {
    if(strpos($location, 'view/')===0) {
      echo "location.reload();";
    } else {
      if(isset($_GET['json'])||isset($_POST['json'])) {
        header('HTTP/1.0 401 Unauthorized');
        $json_result = \Module::returnError('danger', 'Сессия истекла');
        echo json_encode($json_result);
      } else {
        include dirname(__DIR__).'/web/login.php';
      }
    }
  }
}


?>
