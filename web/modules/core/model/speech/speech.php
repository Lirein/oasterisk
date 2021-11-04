<?php

namespace vosk;

class SpeechModel extends \Module implements \module\IAGI {

  public function agi(\stdClass $request_data) {
    if(isset($request_data->grammar)&&isset($request_data->audio)) {
      $speech = $this->agi->get_variable('SPEECH(status)', TRUE);
      $this->agi->verbose("[$speech]", 3);
      if(!$speech) {
        $this->agi->exec('SpeechCreate', 'vosk');
        if(!isset($request_data->mode)) $request_data->mode = 'grammar';
        switch($request_data->mode) {
          case 'quiet': $this->agi->set_variable('SPEECH_ENGINE(mode)', 'quiet'); break;
          case 'grammar': $this->agi->set_variable('SPEECH_ENGINE(mode)', 'grammar'); break;
          case 'hgrammar': $this->agi->set_variable('SPEECH_ENGINE(mode)', 'hgrammar'); break;
          default: $this->agi->set_variable('SPEECH_ENGINE(mode)', 'immediate');
        }
        if(isset($request_data->language)) {
          $this->agi->set_variable('SPEECH_ENGINE(language)', $request_data->language);
        } else {
          $this->agi->set_variable('SPEECH_ENGINE(language)', $this->agi->get_variable('CHANNEL(language)', TRUE));
        }
        if(isset($request_data->server)) {
          $this->agi->set_variable('SPEECH_ENGINE(server)', $request_data->language);
        }
      }
      $grammars = explode('&', $this->agi->get_variable('GRAMMARS', TRUE));
      $gramfile = null;
      if($grammars[0] == '') $grammars = array();
      if(!in_array($request_data->grammar, $grammars)) {
        $grammar = new \core\GrammarSubject($request_data->grammar);
        $gramfile = tempnam(sys_get_temp_dir(), 'gram');
        if(file_put_contents($gramfile, (string)$grammar)!==false) {
          $this->agi->exec('SpeechLoadGrammar', $request_data->grammar.','.$gramfile);
          $grammars[] = $request_data->grammar;
          $this->agi->set_variable('GRAMMARS', implode('&', $grammars));
        }
      }
      if(in_array($request_data->grammar, $grammars)) {
        $this->agi->exec('SpeechActivateGrammar', $request_data->grammar);
      }
      $dialplan = new \core\Dialplan();
      $currentcontext = $this->agi->get_variable('CONTEXT', TRUE);
      $context = $dialplan->getContext($currentcontext);
      $maxlen = 0;
      foreach($context->extents as $exten => $extendata) {
        if(preg_match('/^[0-9*#A-D+]+$/', $exten)) {
          $len = strlen($exten);
          if($len > $maxlen) $maxlen = $len;
        }
      }
      if($maxlen>0) {
        $this->agi->set_variable('SPEECH_DTMF_MAXLEN', (string)$maxlen);
      }
      $this->agi->exec('SpeechBackground', $request_data->audio.',25');
      if($gramfile) unlink($gramfile);
      $this->agi->exec('SpeechDeactivateGrammar', $request_data->grammar);
      $callerid = $this->agi->get_variable('CALLERID(name)', TRUE);
      $resultcount = $this->agi->get_variable('SPEECH(results)', TRUE);
      $text = array();
      $resulttext = '';
      $resultgrammar = 'unknown';
      for($i = 0; $i < $resultcount; $i++) {
        $resultitext = $this->agi->get_variable('SPEECH_TEXT('.$i.')', TRUE);
        $resultigrammar = $this->agi->get_variable('SPEECH_GRAMMAR('.$i.')', TRUE);
        if($i == 0) {
          $resulttext = $resultitext;
          $resultgrammar = $resultigrammar;
        }
        if($resultigrammar != 'dtmf') {
          $text[] = $resultitext;
        }
      }
      $this->agi->set_variable('TEXT', implode(' ', $text));
      $this->agi->log('NOTICE', 'CID: '.$callerid.' Grammar: '.$resultgrammar.' Result: '.$resulttext);
      if($resultgrammar == 'dtmf') {
        $hasexten = $this->agi->get_variable('DIALPLAN_EXISTS('.$currentcontext.','.$resulttext.')', TRUE);
        if($hasexten) { //Если есть искомая точка входа
          $this->agi->setContext($currentcontext, $resulttext, 1);
        } else {
          $hasexten = $this->agi->get_variable('DIALPLAN_EXISTS('.$currentcontext.',i)', TRUE);
          if($hasexten) { //Если есть точка входа invalid
            $this->agi->setContext($currentcontext, 'i', 1);
          }
          //Иначе идем на следующую инструкцию
        }
      } elseif($resultgrammar == 'unknown') {
        $hasexten = $this->agi->get_variable('DIALPLAN_EXISTS('.$currentcontext.',i)', TRUE);
        if($hasexten) { //Если есть точка входа invalid
          $this->agi->setContext($currentcontext, 'i', 1);
        } else {
          $hasexten = $this->agi->get_variable('DIALPLAN_EXISTS('.$currentcontext.',unknown)', TRUE);
          if($hasexten) { //Если есть точка входа invalid
            $this->agi->setContext($currentcontext, 'unknown', 1);
          }  
        }
      } else {
        $hasexten = $this->agi->get_variable('DIALPLAN_EXISTS('.$currentcontext.','.$resultgrammar.')', TRUE);
        if($hasexten) { //Если есть искомая точка входа
          $this->agi->setContext($currentcontext, $resultgrammar, 1);
        } else {
          $hasexten = $this->agi->get_variable('DIALPLAN_EXISTS('.$currentcontext.',i)', TRUE);
          if($hasexten) { //Если есть точка входа invalid
            $this->agi->setContext($currentcontext, 'i', 1);
          }
          //Иначе идем на следующую инструкцию
        }
      }
    }
  }

}
