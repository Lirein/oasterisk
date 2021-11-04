<?php

namespace core;

/**
 * @ingroup coreapi
 * Класс работы с базой данных AseriskDB, умеет сохренение и загрузку массивов и структур данных.
 */
abstract class ASTDBStore extends Module {

  public static function readData($path, $json) {
    $result = new \stdClass();
    if(is_array($json)) $json = json_encode($json);
    if(is_string($json)) $json = json_decode($json);
    if(is_array($json)) {
      if(count($json)&&isset($json[0])&&is_array($json[0])) {
        $count = self::getDB($path, 'count');
        for($i = 0; $i<$count; $i++) {
          $key = self::getDB($path.'/entry_'.($i+1), 'id');
          $result->$key = self::readData($path.'/entry_'.($i+1), $json[0]);
        }
      } elseif(count($json)&&isset($json[0])&&is_object($json[0])) {
        $count = self::getDB($path, 'count');
        for($i = 0; $i<$count; $i++) {
          $key = self::getDB($path.'/entry_'.($i+1), 'id');
          $result = self::readData($path.'/entry_'.($i+1), $json[0]);
        }       
      } elseif(count($json)&&isset($json[0])) {
        $result = [];
        $count = self::getDB($path, 'count');
        for($i = 0; $i<$count; $i++) {
          $key = self::getDB($path.'/entry_'.($i+1), 'id');
          $result[] = $key;
        }
      }
    } else {
      foreach($json as $keyname => $keyvalue) {
        if(is_array($keyvalue)) {
          if(count($keyvalue)&&isset($keyvalue[0])) {
            $result->$keyname = array();
            $count = self::getDB($path.'/'.$keyname, 'count');
            for($i = 0; $i<$count; $i++) {
              $result->$keyname[] = self::readData($path.'/'.$keyname.'/entry_'.($i+1), $keyvalue[0]);
            }
          }
        } elseif(is_object($keyvalue)) {
          $result->$keyname = self::readData($path.'/'.$keyname, $keyvalue);
        } else {
          $result->$keyname = self::getDB($path, $keyname);
        }
      }
    }
    return $result;
  }
  
  public static function writeData($path, $json, $data) {
    $result = true;
    if(is_array($json)) $json = json_encode($json);
    if(is_string($json)) $json = json_decode($json);
    self::deltreeDB($path);
    if(is_array($json)) {
      if(is_object($data)&&count($json)&&isset($json[0])) {
        foreach($data as $key => $value) {
          $result &= self::writeDataItem($path, 'id', $key, $json[0], $value);
        }
      } elseif(is_array($data)&&count($json)&&isset($json[0])) {
        $count = 0;
        foreach($data as $value) {
          $result &= self::setDb($path.'/entry_'.($count+1), 'id', $value);
          $count++;
        }
        self::setDB($path, 'count', $count);
      } else {
        $result = false;
      }
    } else {
      foreach($json as $keyname => $keyvalue) {
        if(isset($data->$keyname)) {
          if(is_array($keyvalue)&&is_array($data->$keyname)) {
            if(count($keyvalue)&&isset($keyvalue[0])) {
              $count = 0;
              foreach($data->$keyname as $value) {
                $result &= self::writeData($path.'/'.$keyname.'/entry_'.($count+1), $keyvalue[0], $value);
                $count++;
              }
              self::setDB($path.'/'.$keyname, 'count', $count);
            }
          } elseif(is_object($keyvalue)) {
            $result &= self::writeData($path.'/'.$keyname, $keyvalue, $data->$keyname);
          } else {
            $result &= self::setDB($path, $keyname, $data->$keyname);
          }
        }
      }
    }
    return $result; 
  }
  
  public static function readDataItem($path, $key, $item, $json) {
    $result = new \stdClass();
    if(is_array($json)) $json = json_encode($json);
    if(is_string($json)) $json = json_decode($json);
    $basename = basename($path);
    if(is_object($json)&&(!empty($key))&&isset($json->$key)&&is_array($json->$key)) {
      $path = $path.'/'.$key;
      $json = $json->$key;
      if(count($json)) $json = $json[0];
      else $json = null;
      $key = null;
    } elseif(is_object($json)&&isset($json->$basename)&&is_array($json->$basename)) {
      $json = $json->$basename[0];
    }
    if(is_object($json)&&count($json)) {
      if(($key != null) && !isset($json->$key)) {
        $json->$key = '';
      }
      if($key == null) {
        if(isset($json->id)) {
          $key = 'id';
        } elseif(isset($json->key)) {
          $key = 'key';
        } elseif(isset($json->name)) {
          $key = 'name';
        } else {
          return null;
        }
      }
    } elseif(is_array($json)) {
      $key = 'id';
    } else {
      $key = null;
    }
    if($key) {
      $found = false;
      $count = self::getDB($path, 'count');
      for($i = 0; $i<$count; $i++) {
        $id = self::getDB($path.'/entry_'.($i+1), $key);
        if($id == $item) {
          $result = self::readData($path.'/entry_'.($i+1), $json);
          $found = true;
          break;
        }
      }
      if(!$found&&!is_array($json)) $result = $json;
      if(isset($result->$key)) unset($result->$key);
    } else {
      return null;
    }
    return $result;
  }
  
  public static function writeDataItem($path, $key, $item, $json, $data) {
    $result = true;
    if(is_array($json)) $json = json_encode($json);
    if(is_string($json)) $json = json_decode($json);
    $basename = basename($path);
    if(is_object($json)&&(!empty($key))&&isset($json->$key)&&is_array($json->$key)) {
      $path = $path.'/'.$key;
      $json = $json->$key;
      if(count($json)) $json = $json[0];
      else $json = null;
      $key = null;
    } elseif(is_object($json)&&isset($json->$basename)&&is_array($json->$basename)) {
      $json = $json->$basename[0];
    }
    if(is_object($json)) {
      if(($key != null) && !isset($json->$key)) {
        $json->$key = '';
      }
      if($key == null) {
        if(isset($json->id)) {
          $key = 'id';
        } elseif(isset($json->key)) {
          $key = 'key';
        } elseif(isset($json->name)) {
          $key = 'name';
        } else {
          return null;
        }
      }
      if(($key != null) && !isset($data->$key)) {
        $data->$key = $item;
      }
    } elseif(is_array($json)) {
      $key = 'id';
    } else {
      $key = null;
    }
    if($key) {
      $count = self::getDB($path, 'count');
      $found = false;
      for($i = 0; $i<$count; $i++) {
        $id = self::getDB($path.'/entry_'.($i+1), $key);
        if($id == $item) {
          $result &= self::writeData($path.'/entry_'.($i+1), $json, $data);
          if(is_array($json)) {
            self::setDB($path.'/entry_'.($i+1), $key, $item);
          }
          $found = true;
          break;
        }
      }
      if(!$found) {
        $count++;
        $result &= self::writeData($path.'/entry_'.$count, $json, $data);
        if(is_array($json)) {
          self::setDB($path.'/entry_'.$count, $key, $item);
        }
        self::setDB($path, 'count', $count);
      }
    } else {
      return false;
    }
    return $result; 
  }
    
  public static function deleteData($family, $key) {
    self::deltreeDB($family.'/'.$key);
  }

  public static function deleteDataItem($path, $key, $item, $json) {
    $result = true;
    if(is_array($json)) $json = json_encode($json);
    if(is_string($json)) $json = json_decode($json);
    $basename = basename($path);
    if(is_object($json)&&(!empty($key))&&isset($json->$key)&&is_array($json->$key)) {
      $path = $path.'/'.$key;
      $json = $json->$key;
      if(count($json)) $json = $json[0];
      else $json = null;
      $key = null;
    } elseif(is_object($json)&&isset($json->$basename)&&is_array($json->$basename)) {
      $json = $json->$basename[0];
    }
    if(is_object($json)&&count($json)) {
      if(($key != null) && !isset($json->$key)) {
        $json->$key = '';
      }
      if($key == null) {
        if(isset($json->id)) {
          $key = 'id';
        } elseif(isset($json->key)) {
          $key = 'key';
        } elseif(isset($json->name)) {
          $key = 'name';
        } else {
          return null;
        }
      }
      $count = self::getDB($path, 'count');
      for($i = 0; $i<$count; $i++) {
        $id = self::getDB($path.'/entry_'.($i+1), $key);
        if($id == $item) {
          for($j = $i+1; $j<$count; $j++) {
            $data = self::readData($path.'/entry_'.($j+1), $json);
            $result &= self::writeData($path.'/entry_'.$j, $json, $data);
          }
          self::deltreeDB($path.'/entry_'.$count);
          self::setDB($path, 'count', $count-1);
          break;
        }
      }
    } else {
      return false;
    }
    return $result; 
  }

}
?>