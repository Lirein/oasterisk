<?php

namespace core;

class Dialplan extends Module implements \JSONInterface {

  private $ini;

  public function __construct() {
    parent::__construct();
    $this->ini = new \INIProcessor('/etc/asterisk/extensions.conf');
  }
  
  public function reloadConfig() {
    $this->ami->send_request('Command', array('Command' => 'dialplan reload'));
  }

  public function getContexts() {
    $list = array();
    foreach($this->ini as $k => $v) {
      if(($k!='general')&&($k!='globals')) {
        $ctx = new \stdClass();
        $ctx->id = $k;
        $ctx->title = empty($v->getComment())?$k:$v->getComment();
        $ctx->text = $ctx->title;
        if(self::checkEffectivePriv('dialplan_context', $k, 'dialplan_reader')) $list[]=$ctx;
      }
    }
    return $list;
  }

  public function getContext($context) {
    if(isset($this->ini->$context)) {
      $contextdata = new \stdClass();
      foreach($this->ini->$context as $exten => $value) {
        if($value instanceof \INIPropertyExten) {
          $actiondata = array();
          $actions = $value->getValue();
          foreach($actions as $prio => $action) {
            $actiondata[$prio] = (object) array('synonym' => $action->getAlias(), 'value' => (string) $action, 'title' => ($action->getComment()?$action->getComment():$exten));
          }
          $contextdata->$exten = $actiondata;
        }
      }
      $result = (object) array('id' => $context,
       'title' => empty($this->ini->$context->getComment())?$context:$this->ini->$context->getComment(),
       'extents' => $contextdata);
      return $result;
    }
    return null;
  }

  public function saveContext($oldcontext, $context, $title, $extensions) {
    $result = new \stdClass();
    if(isset($oldcontext)&&($oldcontext!='')&&($oldcontext!=$context)) {
      if(isset($this->ini->$context)) {
        $result = self::returnError('danger', 'Контекст с таким именем уже существует');
        return $result;
      }
      if(isset($this->ini->$oldcontext))
        unset($this->ini->$oldcontext);
    }
    if((!isset($oldcontext))||$oldcontext=='') {
      if(isset($this->ini->$context)) {
        $result = self::returnError('danger', 'Контекст с таким именем уже существует');
        return $result;
      }
    }
    if(isset($this->ini->$context)) unset($this->ini->$context);
    if(isset($extensions)) {
      foreach($extensions as $extension => $extendata) {
        if(isset($this->ini->$context->$extension)) unset($this->ini->$context->$extension);
        foreach($extendata as $prio => $data) {
          $this->ini->$context->addField('exten', $extension.','.$prio.($data->synonym?('('.$data->synonym.')'):'').','.$data->value);
          if(isset($data->title)&&($data->title!=$extension)) {
            $actions = $this->ini->$context->$extension->getValue();
            $actions[1]->setComment($data->title);
          }
        }
      }
    } else {
      if(isset($this->ini->$context)) {
        unset($this->ini->$context);
      }
    }
    if(isset($title)) {
      $this->ini->$context->setComment($title);
    }
    $this->ini->save();
    $this->reloadConfig();
    $result = self::returnSuccess('Контекст успешно сохранен');
    return $result;
  }

  public function removeContext($context) {
    $result = new \stdClass();
    if(!isset($this->ini->$context)) {
      $result = self::returnError('danger', 'Контекста с таким именем не существует');
      return $result;
    }
    if(isset($this->ini->$context)) unset($this->ini->$context);
    $this->ini->save();
    $this->reloadConfig();
    $result = self::returnSuccess('Контекст успешно удален');
    return $result;
  }

  public function getApplications() {
    $result = array();
    $applist=$this->ami->Command('core show applications');
    if(is_array($applist)) {
      if(isset($applist['data'])) $applist=$applist['data'];
      else return $result;
    }
    $applist=explode("\n",$applist);
    foreach($applist as $appentry) {
      if($pos=strpos($appentry,':')) {
        $result[trim(substr($appentry,0,$pos))]=trim(substr($appentry,$pos+1));
      }
    }
    return $result;
  }

  public function getFunctions() {
    $result = array();
    $funclist=$this->ami->Command('core show functions');
    if(is_array($funclist)) {
      if(isset($funclist['data'])) $funclist=$funclist['data'];
      else return $result;
    }
    $funclist=explode("\n",$funclist);
    array_shift($funclist);
    array_shift($funclist);
    array_pop($funclist);
    foreach($funclist as $funcentry) {
      $pos=strpos($funcentry,' ');
      if($pos!==0) {
        $funcname=substr($funcentry,0,$pos);
        $result[$funcname]=trim(substr($funcentry,59));
      } elseif($pos===0) {
        $result[$funcname].=$funcentry;
      }
    }
    return $result;
  }

  public function fillParams($params, &$attributes, $parent=null) {
    foreach($params as $param) {
       if(isset($param['paramsleft'])) {
         foreach($param['paramsleft'] as $paramleft) {
           if(!isset($attributes[$paramleft['param']])) {
             $attributes = array_merge(array($paramleft['param'] => array('description' => '')), $attributes);
           }
         }
       }
       if($param['param']=='...') {
         $attributes[$parent]['multiple']=true;
         $attributes[$parent]['sepmul']=$param['sepleft'];
         continue;
       }
       if(!isset($attributes[$param['param']])) {
         $attributes[$param['param']]=array('description' => '');
       }
       if(isset($attributes[$param['param']])) {
         if($parent) {
           $attributes[$param['param']]['require']=$parent;
         }
         $attributes[$param['param']]['multiple']=false;
         if(isset($param['sep'])) $attributes[$param['param']]['sep']=$param['sep'];
         if(isset($param['sepleft'])) $attributes[$param['param']]['sepleft']=$param['sepleft'];
         if(isset($param['optional'])) $attributes[$param['param']]['optional']=$param['optional'];
         if(isset($param['paramsleft'])) $this->fillParams($param['paramsleft'], $attributes, $param['param']);
         if(isset($param['params'])) $this->fillParams($param['params'], $attributes, $param['param']);
       }
    }
  }

  public function expandParams($params,&$index = null) {
    $result=array();
    $sep=null;
    $leftsep=null;
    $subarr=null;
    $subarrleft=null;
    if(isset($index)) {
      $i=&$index;
      $i++;
    } else {
      $i=0;
    }
    $start=$i;
    $r=$i;
    while($i<strlen($params)) {
      if(($params{$i}==',')||($params{$i}=='&')||($params{$i}=='^')||($params{$i}=='|')||($params{$i}=='?')||($params{$i}==':')||($params{$i}=='=')||($params{$i}=='(')||($params{$i}==')')) {
        if($i-$r>0) {
          if(!isset($sep)) {
            $sep=$params{$i};
          }
          $entry=array();
          $entry['param']=substr($params,$r,$i-$r);
          $entry['sepleft']=$leftsep;
          $entry['sep']=$sep;
          if(isset($subarrleft)) {
            $entry['paramsleft']=$subarrleft;
            $subarrleft=null;
          }
          $result[]=$entry;
          if(isset($sep)) $sep=$params{$i};
          $leftsep=null;
          $sep=null;
          $r=$i+1;
        } else {
          $leftsep=$params{$r};
          $r++;
        }
      } elseif($params{$i}=='[') {
        $entry=null;
        if($i-$r>0) {
          $entry=array();
          $entry['param']=substr($params,$r,$i-$r);
          $entry['sepleft']=$leftsep;
          $entry['sep']=$sep;
        }
        $subarr=$this->expandParams($params,$i);
        foreach($subarr as $k => $v) {
          $subarr[$k]['optional']=true;
        }
        if(isset($entry)) {
          if(isset($subarrleft)) {
            $entry['paramsleft']=$subarrleft;
            $subarrleft=null;
          }
          if(isset($subarr)) {
            $entry['params']=$subarr;
            $subarr=null;
          }
          $result[]=$entry;
        } else {
          if($r==$start) {
            $subarrleft=$subarr;
          } else {
            $result=array_merge($result,$subarr);
          }
          $subarr=null;
        }
        $leftsep=null;
        $sep=null;
        $r=$i+1;
      } elseif($params{$i}==']') break;
      $i++;
    }
    if($i-$r>0) {
      if(!isset($sep)&&($i+1<strlen($params))&&($params{$i+1}!=']')) $sep=$params{$i+1};
      $entry=array();
      $entry['param']=substr($params,$r,$i-$r);
      if($sep&&($sep=='[')) $sep=null;
      if($leftsep=='[') $leftsep=null;
      $entry['sep']=$sep;
      $entry['sepleft']=$leftsep;
      if(isset($subarr)) {
        $entry['params']=$subarr;
        $subarr=null;
      }
      if(isset($subarrleft)) {
        $entry['paramsleft']=$subarrleft;
        $subarrleft=null;
      }
      $result[]=$entry;
      if(isset($sep)) $sep=$params{$i};
    }
    return $result;
  }

  public function parseAppInfo($appinfo) {
    $result=array();
    $result['params'] = array();
    $result['synopsis'] = '';
    $result['description'] = '';
    $result['seealso'] = '';
    $result['syntax'] = '';
    $result['examples'] = array();
    $result['variables'] = array();
    $i=0;
    $argument='';
    $opt=null;
    foreach ($appinfo as $line) {
      // Sections
      if (strlen($line)&&($line{0} == '[')) {
        $tmp = explode(']', $line);
        $sectionname=trim(substr($tmp[0], 1));
        $i=0;
        continue;
      }

      if(strpos($line,'-= Info about application')) {
        $appdata=explode('\'', $line);
        if(isset($appdata[1])) $result['name']=$appdata[1];
        continue;
      }

      if(isset($sectionname))
      switch($sectionname) {
        case "Synopsis": {
          $result['synopsis'].=$line.' ';
        } break;
        case "See Also": {
          $result['seealso'].=$line.' ';
        } break;
        case "Syntax": {
          $result['syntax'].=$line;
        } break;
        case "Description": {
          if($i) {
            if(isset($example)) unset($example);
            $example = &$result['examples'][$i-1];
            if(trim($line)=='') { if(!isset($example['lines'])) $example['lines']=array(); }
            elseif($line{0}==' ') $example['lines'][]=trim($line);
            elseif(isset($example['lines'])) {
              $i=0;
//              $result['description'].=$line.' ';
            } else {
              $example['description'].=((strlen($line)>30)?' ':"\n").$line;
            }
          }
          if($i==0) {
            if(strpos($line,'Example: ')===0) {
              if(isset($example)) unset($example);
              $example = &$result['examples'][];
              $i=count($result['examples']);
              $example['description']=substr($line,9);
            } else {
              $result['description'].=((strlen($line)>30)?' ':"\n").$line;
            }
          }
        } break;
        case "Arguments": {
          if(strlen($line)&&($line{0}!=' ')&&(!strpos($line,' '))) {
            $argument=trim($line);
            $result['params'][$argument] = array('description' => '');
          } elseif($argument!='') {
            $words=explode(' ',$line);
            if((strpos($line, '    ')===0)&&(strpos($words[4],':')&&(strpos($words[4],'NOTE:')===FALSE))) { //option
              if(!isset($result['params'][$argument]['options'])) $result['params'][$argument]['options']=array();
              if(isset($opt)) unset($opt);
              $opt=&$result['params'][$argument]['options'][$line{4}];
              $opt=array();
              $opt['syntax']=substr($line,4,strrpos($words[4],':'));
              $opt['description']=substr($line,strrpos($words[4],':')+5).' ';
            } elseif(isset($opt)) {
              if($line!='') {
                $opt['description'].=trim($line).' ';
              }
            } else {
              $result['params'][$argument]['description'] .= trim($line).((strlen($line)>30)?' ':"\n");
            }
          }
        }
      }
    }
    $result['seealso']=strlen(trim($result['seealso']))?explode(',',$result['seealso']):array();
    foreach($result['seealso'] as $k => $v) {
      $result['seealso'][$k]=trim($v);
    }
    preg_match('/^([A-Za-z]+)\((.*)\)$/',$result['syntax'],$match);
    $match=$this->expandParams($match[2]);
    $tmpattr=$result['params'];
    $result['params']=array();
    $this->fillParams($match,$result['params']);
    foreach($tmpattr as $attr => $value) {
      if(isset($value['description'])) $result['params'][$attr]['description']=$value['description'];
      if(isset($value['options'])) $result['params'][$attr]['options']=$value['options'];
    }
    return $result;
  }

  public function getApplication($appname) {
    $appname=mb_strtolower($appname);
    $appmodule=getModulesByClass("dialplan\\${appname}Application");
    if($appmodule) {
      $result=$appmodule->getApplication();
    } else {
      $result = array();
      $appinfo=$this->ami->Command('core show application '.$appname);
      if(is_array($appinfo)) {
        if(isset($appinfo['data'])) $appinfo=$appinfo['data'];
        else return $result;
      }
      $appinfo=explode("\n",$appinfo);
      $result=$this->parseAppInfo($appinfo);
      if(!isset($result['name'])) $result['name']=$appname;
    }
    return $result;
  }

  public function getFunction($funcname) {
    $funcname=mb_strtoupper($funcname);
    $funcmodule=getModulesByClass("dialplan\${funcname}Function");
    if($funcmodule) {
      $result=$funcmodule->getFunction();
    } else {
      $result = array();
      $funcinfo=$this->ami->Command('core show function '.$funcname);
      if(is_array($funcinfo)) {
        if(isset($funcinfo['data'])) $funcinfo=$funcinfo['data'];
        else return $result;
      }
      $funcinfo=explode("\n",$funcinfo);
      $result=$this->parseAppInfo($funcinfo);
      if(!isset($result['name'])) $result['name']=$funcname;
    }
    return $result;
  }

  public function json(string $request, \stdClass $request_data) {
    $result = new \stdClass();
    switch($request) {
      case "context-list": {
        $result = self::returnResult($this->getContexts());
      } break;
      case "context-get": {
        $result = self::returnResult($this->getContext($request_data->context));
      } break;
      case "context-set": {
        $oldcontext=isset($request_data->oldcontext)?$request_data->oldcontext:null;
        $context=isset($request_data->context)?$request_data->context:null;
        $title=isset($request_data->title)?$request_data->title:null;
        $extents=isset($request_data->extents)?json_decode($request_data->extents):null;
        if($extents!=null) {
          $tmpextents = array();
          foreach($extents as $exten => $extendata) {
            foreach($extendata as $prio => $value) {
              $tmpextents[$exten][$prio] = $value;
            }
          }
          $extents=$tmpextents;
        }
        $result = self::returnResult($this->saveContext($oldcontext,$context,$title,$extents));
      } break;
      case "application-list": {
        $result = self::returnResult($this->getApplications());
      } break;
      case "application-get": {
        $result = self::returnResult($this->getApplication($request_data->application));
      } break;
      case "function-list": {
        $result = self::returnResult($this->getFunctions());
      } break;
      case "function-get": {
        $result = self::returnResult($this->getFunction($request_data->function));
      } break;
    }
    return $result;
  }

  public function scripts() {
    ?>
    var applications = null;
    var functions = null;

    widgets.context = class contextWidget extends baseWidget {
      constructor(parent, data, label, hint) {
        super(parent, data);
        this.node=document.createElement('div');
        this.node.widget=this;
        this.node.className='col-12';
        this.onchange=null;
        this.context=null;
        this.setParent(parent);
        if(typeof data == 'object') {
          this.setValue(data);
        } else {
          this.load(data);
        }
      }
      load(acontext, aonloaded) {
        if(acontext) {
          var ctx=this;
          sendRequest('context-get', {context: acontext}).success(function(data) {
            ctx.context=acontext;
            ctx.setValue(data);
            if(typeof aonloaded == 'function') aonloaded(data);
          });
        }
      }
      async setValueAsync(avalue) {
        this.node.innerHTML='';
        if((typeof avalue != 'undefined')&&(typeof avalue.extents == 'object')) {
          var exten=null;
          for(exten in avalue.extents) {
            await this.addEntry(exten,avalue.extents[exten]);
          }
        }
        await this.addEntry();
        return true;
      }
      setValue(avalue) {
        this.setValueAsync(avalue);
        return true;
      }
      getValue() {
        var result = {extents: {}}
        for(var i=0; i<this.node.childNodes.length; i++) {
          var entry = this.node.childNodes[i].childNodes[0].childNodes[1].value;
          var entrydata = this.node.childNodes[i].childNodes[1].childNodes[0].widget.getValue();
          if(entry!='') {
            result.extents[entry]=entrydata;
          }
        }
        return result;
      }
      onChange(sender) {
        var canprocess = true;
        var target=null;
        if(typeof sender.target != 'undefined') {
          target=sender.target
        } else {
          target=sender;
        }
        if(target) {
          if(this.onchange) canprocess=this.onchange(target);
          if(canprocess) {
            if(target.nodeName == 'INPUT') {
              var entry=target.parentNode.parentNode;
              if(target.value!='') {
                if(entry.nextSibling==null) {
                  entry.parentNode.widget.addEntry();
                }
              } else {
                if(entry.nextSibling!=null) {
                  if(entry.nextSibling.childNodes[0].childNodes[1].value=='') {
                    $(entry.nextSibling).remove();
                  }
                }
              }
              canprocess=false;
            }
          }
        }
        return canprocess;
      }
      async addEntry(entryname,entrydata) {
        var entry=document.createElement('div');
        entry.className='form-row mb-2 context-entry';
        var entryExten=document.createElement('div');
        var entryData=document.createElement('div');
        entryExten.className='form-group col-xs-12 col-md-3';
        entryData.className='form-group col mb-0';
        entry.appendChild(entryExten);
        entry.appendChild(entryData);
        entryExten.innerHTML='<small class="form-text text-muted">Точка входа</small><input type="text" class="form-control">';
        entryExten=entryExten.childNodes[1];
        entryExten.oninput=this.onChange;
        if(typeof entryname != 'undefined') {
          entryExten.value=entryname;
        }
        var wentry=new widgets.exten(entryData);
        if(typeof entrydata != 'undefined') {
          await wentry.setValueAsync(entrydata);
        } else {
          await wentry.setValueAsync();
        }
        this.node.appendChild(entry);

        return entry;
      }
    };

    widgets.exten = class extenWidget extends baseWidget {
      constructor(parent, data, label, hint) {
        super(parent, data);
        this.node=document.createElement('div');
        this.node.widget=this;
        this.onchange=null;
        this.node.className='w-100';
        this.setParent(parent);
      }
      onDrag(sender) {
      
      }
      async setValueAsync(avalue) {
        this.node.innerHTML='';
        if(typeof avalue == 'object') {
          var prio=null;
          for(prio in avalue) {
            await this.addEntry(prio,avalue[prio]);
          }
        }
        await this.addEntry();
        orderList(this.node, this.onDrag, '>*');
        return true;
      }
      setValue(avalue) {
        this.setValueAsync(avalue);
        return true;
      }
      getValue() {
        var result = {};
        var r=1;
        for(var i=0; i<this.node.childNodes.length; i++) {
          var value = this.node.childNodes[i].childNodes[0].childNodes[1].widget.getValue();
          if(value) {
            result[r] = { synonym: this.node.childNodes[i].childNodes[1].childNodes[1].value,
                          value: value
                        };
            r++;
          }
        }
        return result;
      }
      onChange(sender) {
        var canprocess = true;
        var target=null;
        if(typeof sender.target != 'undefined') {
          target=sender.target
        } else {
          target=sender;
        }
        if(target) {
          if(canprocess) {
            if(target.nodeName == 'SELECT') {
              var entry=target.parentNode.parentNode;
              if(entry.parentNode.widget.onchange) canprocess=entry.parentNode.widget.onchange(target);
              if(target.value!='0') {
                if(entry.nextSibling==null) {
                  entry.parentNode.widget.addEntry();
                }
                if(!$(entry.childNodes[2]).hasClass('show')) entry.childNodes[3].childNodes[0].onclick(entry.childNodes[2].childNodes[0]);
              } else {
                if(entry.nextSibling!=null) {
                  var select = entry.nextSibling.childNodes[0].childNodes[1];
                  if((select.value=='')||(select.value=='0')) {
                    $(entry.nextSibling).remove();
                  }
                }
              }
            }
            canprocess=false;
          }
        }
        return canprocess;
      }
      async addEntry(entryname,entrydata) {
        var entry=document.createElement('div');
        entry.className='form-row exten-prio mb-2';
        if(typeof entryname != 'undefined') entry.id='prio-'+entryname;
        var entrySynonym=document.createElement('div');
        var entryData=document.createElement('div');
        var entryParams=document.createElement('div');
        entrySynonym.className='form-group col-xs-12 col-md-3 mb-0';
        entryData.className='form-group col mb-0';
        entryParams.className='form-group col-12 mb-0 align-items-start collapse';
        var entryToggle=document.createElement('div');
        entryToggle.className='col-12';
        entryToggle.innerHTML="<small class='text-secondary' onClick=\"$(this.parentNode.previousSibling).collapse('toggle'); if(!$(this.childNodes[0]).toggleClass('oi-turn').hasClass('oi-turn')) $(this.childNodes[2]).text('Свернуть'); else $(this.childNodes[2]).text('Развернуть'); \"><span class='oi oi-chevron-top oi-turn' ></span>&nbsp;<span>Развернуть</span></small>";
        entry.appendChild(entryData);
        entry.appendChild(entrySynonym);
        entry.appendChild(entryParams);
        entry.appendChild(entryToggle);
        entrySynonym.innerHTML='<small class="form-text text-muted">Синоним</small><input type="text" class="form-control">';
        entrySynonym=entrySynonym.childNodes[1];
        var wentry=new widgets.application(entryData);
        wentry.onchange=this.onChange;
        wentry.setParamControl(entryParams);
        if(typeof entrydata != 'undefined') {
          entrySynonym.value=entrydata.synonym;
          await wentry.setValueAsync(entrydata.value);
        } else {
          await wentry.setValueAsync();
        }

        this.node.appendChild(entry);
        return entry;
      }
    }

    widgets.application = class applicationWidget extends baseWidget {
      constructor(parent, data, label, hint) {
        super(parent, data);
        this.nodetitle=document.createElement('small');
        this.nodetitle.widget=this;
        this.nodetitle.className='form-text text-muted';
        this.nodetitle.innerHTML='Приложение';
        this.node=document.createElement('select');
        this.node.widget=this;
        this.node.className='custom-select';
        this.node.innerHTML='<option value=0>Не задано</option><option value="">Вручную</option></select>';
        this.node.oninput=this.onSelect;
        this.setParent(parent);
        this.contentControl=null;
        this.applist=null;
        this.onchange=null;
        this.data='';
        this.app='';
        this.regex=new RegExp('^(\\w+)(|\\((.*)\\))$');
      }
      setParent(aobject) {
        if(typeof aobject == 'string') {
          aobject = document.querySelector(aobject);
        }
        if(this.nodetitle) {
          if(aobject instanceof baseWidget) {
            if(aobject.getContent()) {
              aobject.getContent().appendChild(this.nodetitle);
            }
          } else {
            aobject.appendChild(this.nodetitle);
          }
        }
        if(this.node) {
          if(aobject instanceof baseWidget) {
            if(aobject.getContent()) {
              aobject.getContent().appendChild(this.node);
            }
          } else {
            aobject.appendChild(this.node);
          }
        }
      }
      setParamControl(aobject) {
        if(typeof aobject == 'string') {
          aobject = document.querySelector(aobject);
        }
        this.contentControl=aobject;
      }
      parseValue(avalue) {
        var match=this.regex.exec(avalue);
        return match;
      }
      parseParams(avalue,params) {
        var regex='';
        var subregex='';
        var result={};
        for(let param in params) {
          subregex='([a-zA-Z0-9_$';
          if(typeof params[param].sepmul != 'undefined') subregex+="\\"+params[param].sepmul;
          subregex+='\\:\\}\\{\\[\\]\\"\\ \\=\\-\\.\\(\\)\\!\\/\\@]*)';
          if(typeof params[param].sepleft != 'undefined') subregex="\\"+params[param].sepleft+subregex;
          if(typeof params[param].sep != 'undefined') subregex=subregex+"\\"+params[param].sep;
          if(params[param].optional) {
            subregex='('+subregex+'|)';
          } else {
            subregex='('+subregex+')';
          }
          if(params[param].multiple) subregex+='+';
          regex+=subregex;
        }
        var expr=new RegExp(regex);
        var match=expr.exec(avalue);
        if(match == null) {
          match=[];
        }
        var i=0;
        for(let param in params) {
          i++;
          if(typeof match[i*2] !='undefined') {
            result[param]=match[i*2];
          } else {
            result[param]='';
          }
        }
        return result;
      }
      async loadApplication(appname) {
        return new Promise((resolve, reject) => {
          sendRequest('application-get', {application: appname}).success(function(data) {
            if(typeof data != 'undefined') {
              var i=0;
              for(let param in data.params) {
                data.params[param].position=i;
                i++;
              }
              applications[appname].info=data;
            }
            resolve();
          });
        });
      }
      async getApplication(appname) {
        if(appname=='') return null;
        appname=appname.toLowerCase();
        if(typeof applications[appname] != 'undefined') {
          if(applications[appname].info!=null) {
            return applications[appname].info;
          } else {
            await this.loadApplication(appname);
            if(typeof applications[appname].info != null) {
              return applications[appname].info;
            } else {
              return null;
            }
          }
        } else {
          return null;
        }
      }
      loadApplications() {
        return new Promise((resolve, reject) => {
          sendRequest('application-list').success(function(data) {
            applications={};
            for(let app in data) {
              applications[app.toLowerCase()]={title: app, alt: data[app], info: null};
            };
            resolve(applications);
          }).error(function() {
            applications={};
            resolve(applications);
          });
        });
      }
      async getApplications() {
        if(applications==null) {
          await this.loadApplications();
          return applications;
        } else {
          return applications;
        }
      }
      async setValueAsync(avalue) {
        if(this.applist==null) {
          this.applist=await this.getApplications();
          for(let app in this.applist) {
             var option = document.createElement('option');
             option.value=app;
             option.innerHTML=this.applist[app].title;
             this.node.appendChild(option);
          }
        }
        var appdata = null;
        if(typeof avalue != 'undefined') appdata=this.parseValue(avalue);
        if(appdata) {
          this.app=appdata[1];
          this.data=appdata[3];
          if(typeof this.applist[appdata[1].toLowerCase()] !='undefined') {
            this.node.value=appdata[1].toLowerCase();
          } else {
            this.node.value='';
          }
        } else {
          this.node.value=0;
          if(typeof avalue != 'undefined') {
            this.app=avalue;
          } else {
            this.app='';
          }
          this.data='';
        }
        await this.onSelect(this.node);
        return true;
      }
      async onSelect(sender) {
        var target=null;
        if(typeof sender.target != 'undefined') {
          target=sender.target
          if(target.widget.onchange) target.widget.onchange(target);
        } else {
          target=sender;
        }
        if(target) {
          var widget = target.widget;
          var appinfo=await widget.getApplication(target.value);
          if(widget.contentControl) {
            widget.contentControl.innerHTML='';
            $(widget.contentControl).removeClass('mb-2');
          }
          if((widget.contentControl)&&(appinfo)) {
            if(typeof widget.applist[target.value] != 'undefined') {
              widget.app=widget.applist[target.value].title;
            } else {
              widget.app=target.value;
            }
            var params = widget.parseParams(widget.data,appinfo.params);
            for(let param in appinfo.params) {
              if(typeof appinfo.params[param].sepleft != 'undefined') {
                var entrySep = document.createElement('label');
                entrySep.className='control-label';
                entrySep.innerHTML=appinfo.params[param].sepleft;
                widget.contentControl.appendChild(entrySep);
              }
              if(appinfo.params[param].multiple) {
                var wentry=new widgets.multiple(widget.contentControl,{params: appinfo.params[param]},param);
              } else {
                var wentry=new widgets.text(widget.contentControl,null,param);
              }
              await wentry.setValueAsync(params[param]);
              if(typeof appinfo.params[param].sep != 'undefined') {
                var entrySep = document.createElement('label');
                entrySep.className='control-label';
                entrySep.innerHTML=appinfo.params[param].sep;
                widget.contentControl.appendChild(entrySep);
              }
            }
            $(widget.contentControl).addClass('mb-2').addClass('form-inline');
          } else {
            if((widget.contentControl)&&(target.value!='0')) {
              var entryAppName = document.createElement('div');
              entryAppName.className='w-100 mb-0';
              entryAppName.innerHTML='<small class="form-text text-muted">Имя приложения</small><input type="text" class="form-control">';
              widget.contentControl.appendChild(entryAppName);
              entryAppName=entryAppName.childNodes[1];
              entryAppName.value=widget.app;
              var wentry=new widgets.text(widget.contentControl, null, 'Параметры приложения');
              await wentry.setValueAsync(widget.data);
            }
            $(widget.contentControl).addClass('mb-2');
          }
        }
      }
      setValue(avalue) {
        this.setValueAsync(avalue);
        return true;
      }
      expandsep(appinfo,param,values) {
        var result = '';
        if(values[param]=='') {
          if(typeof appinfo.params[param].sepleft != 'undefined') {
            result+=appinfo.params[param].sepleft;
          }
          if(typeof appinfo.params[param].require != 'undefined') {
            if(typeof appinfo.params[param].sep != 'undefined') {
              result+=appinfo.params[param].sep;
            }
            result+=this.expandsep(appinfo,appinfo.params[param].require,values);
          }
        }
        return result;
      }
      expandleftsep(appinfo,param,values) {
        var result = '';
        if(values[param]=='') {
          if(typeof appinfo.params[param].require != 'undefined') {
            result+=this.expandleftsep(appinfo,appinfo.params[param].require,values);
            if(typeof appinfo.params[param].sepleft != 'undefined') {
              result+=appinfo.params[param].sepleft;
            }
          }
          if(typeof appinfo.params[param].sep != 'undefined') {
            result+=appinfo.params[param].sep;
          }
        }
        return result;
      }
      getValue() {
        var result = '';
        if(this.node.value=='0') {
          result = false;
        } else if(this.node.value=='') {
          result=this.contentControl.childNodes[0].childNodes[1].value;
          var val=this.contentControl.childNodes[1].widget.getValue();
          result+='('+(val?val:'')+')';
        } else {
          result=this.applist[this.node.value].title;
          var resultdata = '';
          var appinfo=applications[this.node.value].info;
          var i=0;
          var resultparams = {};
          for(let param in appinfo.params) {
            if(typeof appinfo.params[param].sepleft != 'undefined') {
              i++;
            }
            resultparams[param] = this.contentControl.childNodes[i].widget.getValue();
            resultparams[param] = (resultparams[param])?resultparams[param]:'';
            i++;
            if(typeof appinfo.params[param].sep != 'undefined') {
              i++;
            }
          }
          for(let param in appinfo.params) {
            if((resultparams[param]!='')||(appinfo.params[param].optional!=true)) {
              var hasbracket=false;
              if(typeof appinfo.params[param].require != 'undefined') {
                if(appinfo.params[param].position>appinfo.params[appinfo.params[param].require].position)
                  resultdata+=this.expandleftsep(appinfo,appinfo.params[param].require,resultparams);
                if(typeof appinfo.params[param].sepleft != 'undefined') {
                  resultdata+=appinfo.params[param].sepleft;
                }
                resultdata+=resultparams[param];
                if(typeof appinfo.params[param].sep != 'undefined') {
                  resultdata+=appinfo.params[param].sep;
                }
                if(appinfo.params[param].position<appinfo.params[appinfo.params[param].require].position)
                  resultdata+=this.expandsep(appinfo,appinfo.params[param].require,resultparams);
              } else {
                if(typeof appinfo.params[param].sepleft != 'undefined') {
                  resultdata+=appinfo.params[param].sepleft;
                }
                resultdata+=resultparams[param];
                if(typeof appinfo.params[param].sep != 'undefined') {
                  resultdata+=appinfo.params[param].sep;
                }
              }
            } else {
              if(((typeof appinfo.params[param].sepleft != 'undefined')&&(appinfo.params[param].sepleft=='('))||
                   ((typeof appinfo.params[param].sep != 'undefined')&&(appinfo.params[param].sep=='('))) {
                    var r=0;
                    var hasbracket=false;
                    var needsep=false;
                    for(let subparam in appinfo.params) {
                      if(r>=appinfo.params[param].position) {
                        if(((typeof appinfo.params[subparam].sepleft != 'undefined')&&(appinfo.params[subparam].sepleft==')'))||
                           ((typeof appinfo.params[subparam].sep != 'undefined')&&(appinfo.params[subparam].sep==')'))) {
                           hasbracket=true;
                        }
                      }
                      if(hasbracket&&(resultparams[subparam]!='')) needsep=true;
                      r++;
                    }
                    if(needsep) resultdata+='(';
              }
              if(typeof appinfo.params[param].require != 'undefined') {
                if(appinfo.params[param].position>appinfo.params[appinfo.params[param].require].position) {
                  if((typeof appinfo.params[param].sep != 'undefined')&&(appinfo.params[param].sep==')')) {
                    var r=0;
                    var hasbracket=false;
                    var needsep=false;
                    for(let subparam in appinfo.params) {
                      if((r>=appinfo.params[appinfo.params[param].require].position)&&(r<=appinfo.params[param].position)) {
                        if(((typeof appinfo.params[subparam].sepleft != 'undefined')&&(appinfo.params[subparam].sepleft=='('))||
                           ((typeof appinfo.params[subparam].sep != 'undefined')&&(appinfo.params[subparam].sep=='('))) {
                           hasbracket=true;
                        }
                      }
                      if(hasbracket&&(resultparams[subparam]!='')) needsep=true;
                      r++;
                    }
                    if(needsep) resultdata+=appinfo.params[param].sep;
                  }
                }
              }
            }
          }
          result+='('+resultdata+')';
        }
        return result;
      }
    }

    widgets.text = class textWidget extends baseWidget {
      constructor(parent, data, label, hint) {
        super(parent, data);
        this.onchange=null;
        this.node=document.createElement('div');
        this.node.widget=this;
        this.setParent(parent);
//        this.widget.className='input-group';
        if(typeof label != 'undefined') {
          this.nodetitle=document.createElement('small');
          this.nodetitle.className='form-text text-muted';
          this.nodetitle.innerHTML=label;
          this.node.appendChild(this.nodetitle);
        } else {
          this.nodetitle=null;
        }
        this.input=document.createElement('input');
        this.input.type='text';
        this.input.className='form-control';
        this.input.oninput=this.onInput;
        this.node.appendChild(this.input);
      }
      async setValueAsync(avalue) {
        this.input.value=avalue;
        return true;
      }
      setValue(avalue) {
        this.setValueAsync(avalue);
        return true;
      }
      async onInput(sender) {
        var target=null;
        if(typeof sender.target != 'undefined') {
          target=sender.target.parentNode;
          if(target.widget.onchange) target.widget.onchange(target);
        } else {
          target=sender;
        }
      }
      getValue() {
        return this.input.value;
      }
    }

    widgets.multiple = class multipleWidget extends baseWidget {
      constructor(parent, data, label, hint) {
        super(parent, data);
        this.node=document.createElement('div');
        this.node.widget=this;
        this.setParent(parent);
        this.paraminfo = data.params;
        if(typeof label != 'undefined') {
          this.nodetitle=document.createElement('small');
          this.nodetitle.className='form-text text-muted';
          this.nodetitle.innerHTML=label;
          this.node.appendChild(this.nodetitle);
        } else {
          this.nodetitle=null;
        }
        this.contentControl=document.createElement('div');
        this.node.appendChild(this.contentControl);
        this.onchange=null;
      }
      async setValueAsync(avalue) {
        this.contentControl.innerHTML='';
        var params;
        avalue=avalue.trim();
        if(avalue!='') {
          params=avalue.split(this.paraminfo.sepmul);
        } else {
          params=[];
        }
        for(var i=0; i<params.length; i++) {
          var wentry=new widgets.text(this.contentControl);
          wentry.onchange=this.onInput;
          await wentry.setValueAsync(params[i]);
          var entrySep = document.createElement('label');
          entrySep.className='control-label';
          entrySep.innerHTML=this.paraminfo.sepmul;
          wentry.node.appendChild(entrySep);
          wentry.node.className='form-inline';
        }
        var wentry=new widgets.text(this.contentControl);
        wentry.onchange=this.onInput;
        await wentry.setValueAsync('');
        return true;
      }
      setValue(avalue) {
        this.setValueAsync(avalue);
        return true;
      }
      getValue() {
        var result = '';
        for(var i = 0; i<this.contentControl.childNodes.length-1; i++) {
          var val=this.contentControl.childNodes[i].widget.getValue();
          if(i>0) result+=this.paraminfo.sepmul;
          result+=val;
        }
        return result;
      }
      async onInput(sender) {
        var target=null;
        if(typeof sender.target != 'undefined') {
          target=sender.target.parentNode.parentNode;
          if(target.widget.onchange) target.widget.onchange(target);
        } else {
          target=sender;
        }
        if(target) {
          if(target.widget.getValue()!='') {
            if(target.nextSibling==null) {
              var widget = target.parentNode.parentNode.widget;
              var entrySep = document.createElement('label');
              entrySep.className='control-label';
              entrySep.innerHTML=widget.paraminfo.sepmul;
              target.appendChild(entrySep);
              target.className='form-inline';
              var wentry=new widgets.text(widget.contentCountrol);
              wentry.onchange=widget.onInput;
              await wentry.setValueAsync('');
            }
          } else {
            if(target.nextSibling!=null) {
              if(target.nextSibling.widget.getValue()=='') {
                $(target).remove();
              }
            }
          }
        }
      }
    }

    <?php
  }

}

?>