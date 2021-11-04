<?php

  spl_autoload_register('oAsteriskAutoloader');

  function oAsteriskAutoloader($class) {
    $lclass = str_replace('\\', '/', $class);
    if(file_exists(dirname(__DIR__).'/core/'.$lclass.'.php')) {
      require_once dirname(__DIR__).'/core/'.$lclass.'.php';
    } else {
      $module = findModuleByClass($class);
      if($module) require_once $module->path;
    }
  }

  function updateModules() {
    global $_WEBMODULES;
    global $_CACHE;

    function getSubModules($path) {
      $result = array();
      $dirlist = array();
      if($dh = opendir(__DIR__.'/modules/'."$path")) {
        while(($file = readdir($dh)) !== false) {
          if($file[0] != '.') {
            if(substr($file, -4)=='.php') { //if it is a PHP file
              $oldclasses = get_declared_classes();
              require_once __DIR__.'/modules/'."$path/$file";
              $newclasses = get_declared_classes();
              $newclasses = array_diff($newclasses, $oldclasses);
              foreach($newclasses as $class) {
                $reflection = new \ReflectionClass($class);
                if($reflection->isSubclassOf('\view\IViewPort')&&!$reflection->isAbstract()) {
                  $viewlocation = $class::getViewLocation();
                  if($viewlocation) {
                    $classinfo = new \stdClass();
                    $parent = array();
                    $parentclass = $reflection;
                    while($parentclass = $parentclass->getParentClass()) {
                      $parentclassname = $parentclass->getName();
                      if($parentclassname != 'Module') $parent[] = $parentclassname;
                    }
                    $parent += $reflection->getInterfaceNames();
                    $classinfo->path = $reflection->getFileName();
                    $classinfo->namespace = $reflection->getNamespaceName();
                    $classinfo->location = 'view/'.$viewlocation;
                    $classinfo->parentclass = $parent;
                    $classinfo->class = $class;
                    $result[] = $classinfo;
                  }
                }              
                if($reflection->isSubclassOf('Module')) {
                  $classinfo = new \stdClass();
                  $parent = array();
                  if(!$reflection->isAbstract()) {
                    $parentclass = $reflection;
                    while($parentclass = $parentclass->getParentClass()) {
                      $parentclassname = $parentclass->getName();
                      if($parentclassname != 'Module') $parent[] = $parentclassname;
                    }
                  }
                  $parent += $reflection->getInterfaceNames();
                  $classinfo->path = $reflection->getFileName();
                  $classinfo->namespace = $reflection->getNamespaceName();
                  if(!$reflection->isAbstract()) {
                    $classinfo->location = $class::getLocation();
                  } else {
                    $classinfo->location = null;
                  }
                  $classinfo->parentclass = $parent;
                  $classinfo->class = $class;
                  $result[] = $classinfo;
                }
                unset($reflection);
              }
            } elseif(is_dir(__DIR__.'/modules/'."$path/$file")) {
              $dirlist[]="$path/$file";
            }
          }
        }
        closedir($dh);
      }
      foreach($dirlist as $dir) {
        $result = array_merge($result, getSubModules($dir));
      }
      return $result;
    }

    $_WEBMODULES = $_CACHE->get('webmodules');
    if(!$_WEBMODULES) {
      $result = array();
      if(is_dir(__DIR__.'/modules/core')) $result = getSubModules('core');
      if($dh = opendir(__DIR__.'/modules')) {
        while(($file = readdir($dh)) !== false) {
          if(($file[0] != '.') && ($file != 'core')) {
            if(is_dir(__DIR__.'/modules'."/$file")) {
              $result = array_merge($result, getSubModules("$file"));
            }
          }
        }
        closedir($dh);
      }
      $_WEBMODULES = $result;
      $_CACHE->set('webmodules', $_WEBMODULES, 240);
    }
//    error_log('loaded '.count($_WEBMODULES).' modules');
  }

  function findModuleByPath($path) {
    global $_WEBMODULES;
    if(empty($path)) return null;
    $result = null;
    if(!empty($_WEBMODULES)) {
      foreach($_WEBMODULES as $module) {
        if($module->location==$path) {
          $result = $module;
          break;
        }
      }
    }
    return $result;
  }

  function findModulesByPath($path) {
    global $_WEBMODULES;
    if(empty($path)) return null;
    $result = array();
    if(!empty($_WEBMODULES)) {
      foreach($_WEBMODULES as $module) {
        if(dirname($module->location) == $path) {
          $result[$module->location] = $module;
        }
      }
    }
    return array_values($result);
  }

  function getModuleByPath($path) {
    $result = findModuleByPath($path);
    if($result) {
      $classname = $result->class;
      $result = null;
      if($classname::check()) $result = new $classname();
    }
    return $result;
  }

  function findModulesByNamespace($namespace) {
    global $_WEBMODULES;

    $result = array();
    if(!empty($_WEBMODULES)) {
      foreach($_WEBMODULES as $module) {
        if($module->namespace==$namespace) {
          $result[] = $module;
        }
      }
    }
    return $result;
  }

  function getModulesByNameSpace($namespace) {
    global $_AGI;
    $result = findModulesByNameSpace($namespace);
    foreach($result as $key => $module) {
      $classname = $module->class;
      if(isset($_AGI)||$classname::check()) {
        $result[$key] = new $classname();
      } else {
        unset($result[$key]);
      }
    }
    return $result;
  }

  function findModuleByNamespace($namespace) {
    global $_WEBMODULES;

    $result = null;
    if(!empty($_WEBMODULES)) {
      foreach($_WEBMODULES as $module) {
        if($module->namespace==$namespace) {
          $result = $module;
          break;
        }
      }
    }
    return $result;
  }

  function getModuleByNameSpace($namespace) {
    $result = findModuleByNameSpace($namespace);
    if($result) {
      $classname = $result->class;
      $result = null;
      if($classname::check()) $result = new $classname();
    }
    return $result;
  }

  function findModulesByMainClass($class, $check = false) {
    global $_WEBMODULES;

    $result = array();
    if(!empty($_WEBMODULES)) {
      foreach($_WEBMODULES as $module) {       
        if(count($module->parentclass)&&($module->parentclass[0]==$class)) {
          $classname = $module->class;
          if((!$check)||$classname::check()) $result[] = $module;
        }
      }
    }
    return $result;
  }

  function findModulesByClass($class, $check = false) {
    global $_WEBMODULES;

    $result = array();
    if(!empty($_WEBMODULES)) {
      foreach($_WEBMODULES as $module) {       
        if(in_array($class, $module->parentclass)) {
          $classname = $module->class;
          if((!$check)||$classname::check()) $result[] = $module;
        }
      }
    }
    return $result;
  }

  function getModulesByClass($class) {
    $result = findModulesByClass($class);
    foreach($result as $key => $module) {
      $classname = $module->class;
      if($classname::check()) {
        $result[$key] = new $classname();
      } else {
        unset($result[$key]);
      }
    }
    return $result;
  }

  function findModuleByClass($class) {
    global $_WEBMODULES;

    $result = null;
    if(!empty($_WEBMODULES)) {
      foreach($_WEBMODULES as $module) {
        if($module->class==$class) {
          $result = $module;
          break;
        }
      }
    }
    return $result;
  }

  function getModuleByClass($class) {
    $result = findModuleByClass($class);
    if($result) {
      $classname = $result->class;
      $result = null;
      if($classname::check()) $result = new $classname();
    }
    return $result;
  }

?>