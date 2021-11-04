<?php

namespace sphinx;

class SphinxModule extends \Module implements \module\IAGI {

  public static function check() {
    global $_AMI;
    $result = true;
    if(isset($_AMI)) $result &= self::checkPriv('dialing');
    $result &= self::checkLicense('oasterisk-core');
    return $result;
  }

  public static function test_phrase($jsgf, $string) {
    $content = file_get_contents($jsgf);
    if($content===false) return false;
    $lines = explode("\n", $content);
    $name = '';
    $grams = array();
    $public = array();
    if((count($lines)>0)&&strpos($lines[0],'#JSGF')===0) {
      foreach($lines as $line) {
        $line = trim($line);
        if(strpos($line, 'grammar ')===0) {
          if(preg_match('/grammar\s+(.*);/', $line, $match)) {
            $name = $match[1];
          }
        } elseif(strpos($line, '<')===0) {
          if(preg_match('/<([a-z0-9-]+)>\s+=\s+(.*);/', $line, $match)) {
            $grams[$match[1]] = $match[2];
          }
        } elseif(strpos($line, 'public')===0) {
          if(preg_match('/public\s+<([a-z0-9-]+)>\s+=\s+(.*);/', $line, $match)) {
            if(preg_match_all('/<([a-z0-9-]+)>/',$match[2],$matches)) {
              $public=$matches[1];
            }
          }
        }
      }
      $finished = false;
      while(!$finished) {
        $hasany = false;
        foreach($grams as $gramkey => $gram) {
          if(preg_match('/<[a-z0-9-]+>/', $gram)) {
            $hasany = true;
            foreach($grams as $subgramkey => $subgram) {
              $grams[$gramkey]=str_replace('<'.$subgramkey.'>', '('.$subgram.')', $grams[$gramkey]);
            }
          }
        }
        if(!$hasany) $finished = true;
      }
    }
    foreach($grams as $gramkey => $gram) {
      $grams[$gramkey]=str_replace('[', '(', $grams[$gramkey]);
      $grams[$gramkey]=str_replace(']', '){0,1}', $grams[$gramkey]);
      $grams[$gramkey]=str_replace(' ', '', $grams[$gramkey]);
      while(strpos($grams[$gramkey],' |')!==false) $grams[$gramkey]=str_replace(' |', '|', $grams[$gramkey]);
      while(strpos($grams[$gramkey],'| ')!==false) $grams[$gramkey]=str_replace('| ', '|', $grams[$gramkey]);
    }
    $result = false;
    $string=str_replace('|', '', $string);
    $string=str_replace(' ', '', $string);
    foreach($public as $gramkey) {
  //  printf("\"%s\" has matched to %s = /^(%s)$/\n",$string, $gramkey, $grams[$gramkey]);
      if(preg_match('/^('.$grams[$gramkey].')$/u', $string)) {
        $result = $gramkey;
        break;
      }
    }
    if(!$result) {
      foreach($public as $gramkey) {
    //  printf("\"%s\" has matched to %s = /^(%s)$/\n",$string, $gramkey, $grams[$gramkey]);
        if(preg_match('/('.$grams[$gramkey].')/u', $string)) {
          $result = $gramkey;
          break;
        }
      }
    }
    return $result;
  }

  public function agi(\stdClass $request_data) {
    $gram='';
    $dic='';
    $context=false;
    $macro=false;
    $play=array();
    if(isset($request_data->context)) {
      $context=$request_data->context;
    }
    if(isset($request_data->macro)) {
      $macro=$request_data->macro;
    }
    if(isset($request_data->gram)) {
      $gram=$request_data->gram;
    }
    if(isset($request_data->dic)) {
      $dic=$request_data->dic;
    }
    if(isset($request_data->play)) {
      $play=explode('&',$request_data->play);
    }
    foreach($play as $file) {
      $this->agi->stream_file($file,'',0);
    }
    if(!$this->agi->audio) {
      if(file_exists('/proc/' . getmypid() . '/fd/3')) {
//        $this->agi->audio = popen('cat /proc/' . getmypid() . '/fd/3 | sox -v 1.07 -t s16 -r 16000 -c 1 - -r 16000 -t s16 -', 'r');
        $this->agi->audio = popen('cat /proc/' . getmypid() . '/fd/3 | sox -t s16 -r 16000 -c 1 - -t s16 -r 16000 - gain 18 | sox -v 0.17 -t s16 -r 16000 -c 1 - -r 16000 -t s16 -', 'r');
//        $this->agi->audio = popen('cat /proc/' . getmypid() . '/fd/3', 'r');
        $this->agi->verbose('Open audio stream '.'/proc/' . getmypid() . '/fd/3',3);
      } elseif(file_exists('/dev/fd/3')) {
        $this->agi->audio = fopen('/dev/fd/3', 'r');
        $this->agi->verbose('Open audio stream '.'/dev/fd/3',3);
      }
      if(!$this->agi->audio) {
        $this->agi->verbose('Unable to open audio stream ',3);
        return false;
      }
    }
    $socket = stream_socket_client('tcp://127.0.0.1:1069');
    if($socket) {
      stream_set_blocking($this->agi->audio, false);
      stream_set_blocking($socket, false);
      if($dic) fwrite($socket, json_encode(array('command' => 'dic', 'data' => $dic))."\n");
      if($gram) fwrite($socket, json_encode(array('command' => 'gram', 'data' => $gram))."\n");
      fwrite($socket, json_encode(array('command' => 'decode'))."\n");
//      $streams = array($this->agi->audio, $socket);
      $streams = array($this->agi->audio);
      $stringdata = array();
      $k = 0;
      $changes = 0;
      $lowchanges = 0;
      $operator = false;

      while(true) {
        $c = stream_select($streams, $w = NULL, $e = NULL, 5);
        if($c===false) {
           $this->agi->verbose('Error',3);
           break;
        }
        if($c>0) {
          if(feof($this->agi->audio)) break;
          $data = stream_get_contents($this->agi->audio, 1024);
//          $this->agi->verbose(sprintf('Read audio %d',strlen($data)),3);
          if(strlen($data)>0) {
//            $this->agi->verbose('Send audio data to CMUSphinx',3);
            fwrite($socket, $data);
          }
          $data = stream_get_contents($socket);
          $break = false;
          if(strlen($data)>0) {
            $lines=explode("\n", $data);
            foreach($lines as $line) {
              if(trim($line)=='') continue;
              $return = json_decode($line);
              $this->agi->verbose('Received line '.$line.' response = '.$return->command.', result = '.$return->result,3);
              if($return->result=='error') {
                $break = true;
                break;
              }
              if(($macro!==false)&&($return->command=='decode')&&($return->result=='hypotesis')) {
//                $this->agi->verbose('Received response = '.$return->command.', result = '.$return->result.', words = '.print_r($return->words,true),3);
                $data = array();
                foreach($return->words as $key => $word) {
                  $word=explode('(', $word)[0];
                  $weight = 0;
                  for($i=0; $i<strlen($word); $i++) {
                    $weight += ord($word[$i])*($i+1);
                  }
                  $data[] = array('word' => $word, 'weight' => $weight);
                }
                $stringdata[$k]=$data;
                if($k>0) {
                  $i=0;
                  $j=0;
                  $cnt=0;
                  while($i<count($stringdata[$k])) {
                    if($j<count($stringdata[$k-1])) {
                      if($stringdata[$k][$i]['weight']!=$stringdata[$k-1][$j]['weight']) {
                        if(($j+1<count($stringdata[$k-1]))&&($stringdata[$k][$i]['weight']==$stringdata[$k-1][$j+1]['weight'])) {
                          $j++;
                        } elseif(($j-1>0)&&($stringdata[$k][$i]['weight']==$stringdata[$k-1][$j-1]['weight'])) {
                          $j--;
                        } else {
                          $cnt++;
                        }
                      }
                    }
                    $j++; $i++;
                  }
                  if($cnt>1) {
                    $lowchanges=0;
                    $changes++;
                  } elseif(($cnt == 1) && (count($stringdata[$k]) == 1)) {
                    $lowchanges++;
//                  } else {
//                    $lowchanges=0;
//                    $changes=0;
                  }
                }
                $this->agi->verbose("Changes ${changes}:${lowchanges}",3);
                $k++;
                if($changes>1) {
                  $this->agi->verbose("Probably misspell recognition", 3);
                  if($this->agi->get_variable('DIALPLAN_EXISTS('.$context.',o,1)', TRUE)) {
                    $return->result='end';
                    $operator = true;
                  }
                } elseif($lowchanges>2) {
                  $this->agi->verbose("Probably misspell recognition on lowsense", 3);
                  if($this->agi->get_variable('DIALPLAN_EXISTS('.$context.',o,1)', TRUE)) {
                    $return->result='end';
                    $operator = true;
                  }
                } else {
                  $this->agi->set_variable('RETURN', '');
                  $this->agi->set_variable('RETVAL',implode('|',$return->words));
                  $this->agi->exec('Macro', $macro);
                  $macrores = $this->agi->get_variable('RETURN', TRUE);
                  if($macrores=='exit') {
                    $break = true;
                    break;
                  }
                  if($macrores=='run') {
                    $return->result='end';
                  }
                }
              }
              if(($return->command=='decode')&&($return->result=='end')) {
//                $this->agi->verbose('Received response = '.$return->command.', result = '.$return->result.', words = '.print_r($return->words,true),3);
                $this->agi->set_variable('RETVAL',implode('|',$return->words));
                if($operator) {
                  $match='o';
                } else {
                  $match=self::test_phrase('/usr/share/pocketsphinx/model/ru_zero/'.$gram.'.gram', implode('|',$return->words));
                  if($match===false) $match='i';
                  if(!$this->agi->get_variable('DIALPLAN_EXISTS('.$context.','.$match.',1)', TRUE)) $match='i';
                }
                $this->agi->exec('GoSub', (($context!==false)?($context.','):'').$match.',1');
                $break = true;
                break;
              }
            }
          }
          if($break) break;
        }
      }
      fclose($socket);
    }
  }

}
?>

