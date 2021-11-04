<?php

namespace core;

class GrammarREST extends \module\Rest {

  public static function getServiceLocation() {
    return 'grammars';
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "menuItems":{
        $grammars = new Grammars();
        $objects = array();
        foreach($grammars as $id => $data) {
          $objects[]=(object)array('id' => $id, 'title' => $data->title);
        }
        $result = self::returnResult($objects);
        unset($grammars);
      } break;
      case "get": {
        if(isset($request_data->id)&&self::checkEffectivePriv('grammar', $request_data->id, 'settings_reader')) {
          $grammar = new Grammar($request_data->id);
          $result = self::returnResult($grammar->cast());
          unset($grammar);
        }
      } break;
      case "set": {
        if(isset($request_data->id)&&self::checkEffectivePriv('grammar', $request_data->id, 'settings_writer')) {
          $grammar = new Grammar($request_data->id);
          $grammar->assign($request_data);
          if($grammar->save()){
            $grammar->reload();
            $result = self::returnResult((object)array('id' => $grammar->id));
          } else {
            $result = self::returnError('danger', 'Не удалось сохранить грамматику');
          }
          unset($grammar);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "add": {
        if((empty($request_data->id)||($request_data->id == 'false'))&&self::checkPriv('settings_writer')) {
          $grammar = new Grammar();
          $grammar->assign($request_data);
          if($grammar->save()){
            $grammar->reload();
            $result = self::returnResult((object)array('id' => $grammar->id));
          } else {
            $result = self::returnError('danger', 'Не удалось сохранить грамматику');
          }
          unset($grammar);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "remove": {
        if(isset($request_data->id)&&self::checkPriv('settings_writer')) {
          $grammar = new Grammar($request_data->id);
          if($grammar->delete()) {
            $grammar->reload();
            $result = self::returnSuccess();
          } else {
            $result = self::returnError('danger', 'Не удалось удалить грамматику');
          }
          unset($grammar);
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "import": {
        if(isset($request_data->id)&&isset($request_data->file)&&self::checkPriv('settings_writer')) {
          $jsgf = explode("\n", file_get_contents($request_data->file->tmp_name));
          $grams = array();
          $allgrams = array();
          $public = array();
          //$grammar = 'import-grammar';
          foreach($jsgf as $line) {
            $line = trim($line);
            if((strpos($line, '<') === 0)&&(strpos($line, '=') !== false)&&(strpos($line, ';') !== false)) {
              $key = substr($line, strpos($line, '<')+1, strpos($line, '>')-strpos($line, '<')-1);
              $value = trim(substr($line, strpos($line, '=')+1, strpos($line, ';')-strpos($line, '=')-1));
              $allgrams[$key] = $value;
            } elseif((strpos($line, 'public ') === 0)&&(strpos($line, '=') !== false)&&(strpos($line, ';') !== false)) {
              $key = substr($line, strpos($line, '<')+1, strpos($line, '>')-strpos($line, '<')-1);
              $value = trim(substr($line, strpos($line, '=')+1, strpos($line, ';')-strpos($line, '=')-1));
              $pubgrams = array();
              $i=0;
              while(($pos = strpos($value, '<', $i))!==false) {
                $i = strpos($value, '>', $pos);
                if($i === false) break;
                $pubgrams[] = substr($value, $pos+1, $i-$pos-1);
              }
              $public[$key] = $pubgrams;
            } //elseif((strpos($line, 'grammar ') === 0&&(strpos($line, ';') !== false))) {
            //   $grammar = trim(substr($line, 8, strpos($line, ';')-8));
            // }
          }
          foreach($public as $pubgrams) {
            foreach($pubgrams as $gram) {
              if(!isset($grams[$gram])) {
                if(isset($allgrams[$gram])) {
                  $grams[$gram] = array();
                  if(strpos($allgrams[$gram], '<')!==false) {
                    foreach($allgrams as $subgramname => $subgramdata) {
                      if(strpos($allgrams[$gram], '<'.$subgramname.'>')!==false) {
                        $subgram = $subgramdata;
                        $i = 0;
                        while(($pos = strpos($subgram, '<', $i)) !== false) {
                          $i = strpos($subgram, '>', $pos);
                          if($i === false) break;
                          $incgram = substr($subgram, $pos+1, $i-$pos-1);
                          error_log($gram.' - '.$incgram);
                          if(isset($allgrams[$incgram])) {
                            $subgram = str_replace('<'.$incgram.'>', $allgrams[$incgram], $subgram);
                          } else {
                            $subgram = str_replace('<'.$incgram.'>', '', $subgram);
                          }
                        }
                        $grams[$gram][] = $subgram;
                      }
                    }
                  } else {
                    $grams[$gram][] = $allgrams[$gram];
                  }
                }
              }
            }
          }
          unset($allgrams);
          unset($pubgrams);
          $gram = new Grammar($request_data->id);
          $gram->grams = $grams;
          if($gram->save()){
            $gram->reload();
            $result = self::returnSuccess('Импорт успешно завершен');
          } else {
            $result = self::returnError('danger', 'Не удалось импортировать грамматику');
          }
        } else {
          $result = self::returnError('danger', 'Отказано в доступе');
        }
      } break;
      case "export": {
        if(isset($request_data->id)&&self::checkEffectivePriv('grammar', $request_data->id, 'settings_reader')) {
          $grammar = new Grammar($request_data->id);
          $result = self::returnData((string)$grammar, 'application/octet-stream', $grammar->id.'.jsgf');
          unset($grammar);
        }
      } break;
    }
    return $result;
  }
    
}

?>