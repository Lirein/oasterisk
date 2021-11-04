<?php

namespace dialplan;

class Dialplan extends \module\Collection {

  private $ini;

  public static $dlpln = null;

  private static $ctxcache = array();

  private static function locateContext(string $id) {
    if(empty($id)) return null;
    if(!isset(self::$ctxcache[$id])) {
      if(empty($id)) return null;
      self::$ctxcache[$id] = null;
      if(self::$dlpln == null) self::$dlpln = new Dialplan();
      $keys = self::$dlpln->keys();
      if(in_array($id, $keys)) self::$ctxcache[$id] = new Context($id);
    }
    return self::$ctxcache[$id];
  }

  public function __construct() {
    $this->ini = self::getINI('/etc/asterisk/extensions.conf');
    parent::__construct();
  }

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->items = array();
    foreach($this->ini as $context => $entry) {
      if(($context!='general')&&($context!='globals')) {
        $this->items[] = $context;
      }
    }
    reset($this->items);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return mixed
   */
  public function current() {
    $context = current($this->items);
    return self::locateContext($context);
  }

  /**
   * Осуществляет поиск контекста диалплана
   *
   * @param string $id Идентификатор контекста
   * @return \dialplan\Context Найденный контекст
   */
  public static function find(string $id) {
    return self::locateContext($id);
  }
//   public function getApplications() {
//     $result = array();
//     $applist=$this->ami->Command('core show applications');
//     if(is_array($applist)) {
//       if(isset($applist['data'])) $applist=$applist['data'];
//       elseif(isset($applist['Output'])) $applist=$applist['Output'];
//       else return $result;
//     }
//     $applist=explode("\n",$applist);
//     foreach($applist as $appentry) {
//       if($pos=strpos($appentry,':')) {
//         $result[trim(substr($appentry,0,$pos))]=trim(substr($appentry,$pos+1));
//       }
//     }
//     return $result;
//   }

//   public function getFunctions() {
//     $result = array();
//     $funclist=$this->ami->Command('core show functions');
//     if(is_array($funclist)) {
//       if(isset($funclist['data'])) $funclist=$funclist['data'];
//       else return $result;
//     }
//     $funclist=explode("\n",$funclist);
//     array_shift($funclist);
//     array_shift($funclist);
//     array_pop($funclist);
//     foreach($funclist as $funcentry) {
//       $pos=strpos($funcentry,' ');
//       if($pos!==0) {
//         $funcname=substr($funcentry,0,$pos);
//         $result[$funcname]=trim(substr($funcentry,59));
//       } elseif($pos===0) {
//         $result[$funcentry].=$funcentry;
//       }
//     }
//     return $result;
//   }

//   public function fillParams($params, &$attributes, $parent=null) {
//     foreach($params as $param) {
//        if(isset($param['paramsleft'])) {
//          foreach($param['paramsleft'] as $paramleft) {
//            if(!isset($attributes[$paramleft['param']])) {
//              $attributes = array_merge(array($paramleft['param'] => array('description' => '')), $attributes);
//            }
//          }
//        }
//        if($param['param']=='...') {
//          $attributes[$parent]['multiple']=true;
//          $attributes[$parent]['sepmul']=$param['sepleft'];
//          continue;
//        }
//        if(!isset($attributes[$param['param']])) {
//          $attributes[$param['param']]=array('description' => '');
//        }
//        if(isset($attributes[$param['param']])) {
//          if($parent) {
//            $attributes[$param['param']]['require']=$parent;
//          }
//          $attributes[$param['param']]['multiple']=false;
//          if(isset($param['sep'])) $attributes[$param['param']]['sep']=$param['sep'];
//          if(isset($param['sepleft'])) $attributes[$param['param']]['sepleft']=$param['sepleft'];
//          if(isset($param['optional'])) $attributes[$param['param']]['optional']=$param['optional'];
//          if(isset($param['paramsleft'])) $this->fillParams($param['paramsleft'], $attributes, $param['param']);
//          if(isset($param['params'])) $this->fillParams($param['params'], $attributes, $param['param']);
//        }
//     }
//   }

//   public function expandParams($params,&$index = null) {
//     $result=array();
//     $sep=null;
//     $leftsep=null;
//     $subarr=null;
//     $subarrleft=null;
//     if(isset($index)) {
//       $i=&$index;
//       $i++;
//     } else {
//       $i=0;
//     }
//     $start=$i;
//     $r=$i;
//     while($i<strlen($params)) {
//       if(($params{$i}==',')||($params[$i]=='&')||($params[$i]=='^')||($params[$i]=='|')||($params[$i]=='?')||($params[$i]==':')||($params[$i]=='=')||($params[$i]=='(')||($params[$i]==')')) {
//         if($i-$r>0) {
//           if(!isset($sep)) {
//             $sep=$params{$i};
//           }
//           $entry=array();
//           $entry['param']=substr($params,$r,$i-$r);
//           $entry['sepleft']=$leftsep;
//           $entry['sep']=$sep;
//           if(isset($subarrleft)) {
//             $entry['paramsleft']=$subarrleft;
//             $subarrleft=null;
//           }
//           $result[]=$entry;
//           if(isset($sep)) $sep=$params[$i];
//           $leftsep=null;
//           $sep=null;
//           $r=$i+1;
//         } else {
//           $leftsep=$params[$r];
//           $r++;
//         }
//       } elseif($params[$i]=='[') {
//         $entry=null;
//         if($i-$r>0) {
//           $entry=array();
//           $entry['param']=substr($params,$r,$i-$r);
//           $entry['sepleft']=$leftsep;
//           $entry['sep']=$sep;
//         }
//         $subarr=$this->expandParams($params,$i);
//         foreach($subarr as $k => $v) {
//           $subarr[$k]['optional']=true;
//         }
//         if(isset($entry)) {
//           if(isset($subarrleft)) {
//             $entry['paramsleft']=$subarrleft;
//             $subarrleft=null;
//           }
//           if(isset($subarr)) {
//             $entry['params']=$subarr;
//             $subarr=null;
//           }
//           $result[]=$entry;
//         } else {
//           if($r==$start) {
//             $subarrleft=$subarr;
//           } else {
//             $result=array_merge($result,$subarr);
//           }
//           $subarr=null;
//         }
//         $leftsep=null;
//         $sep=null;
//         $r=$i+1;
//       } elseif($params{$i}==']') break;
//       $i++;
//     }
//     if($i-$r>0) {
//       if(!isset($sep)&&($i+1<strlen($params))&&($params[$i+1]!=']')) $sep=$params[$i+1];
//       $entry=array();
//       $entry['param']=substr($params,$r,$i-$r);
//       if($sep&&($sep=='[')) $sep=null;
//       if($leftsep=='[') $leftsep=null;
//       $entry['sep']=$sep;
//       $entry['sepleft']=$leftsep;
//       if(isset($subarr)) {
//         $entry['params']=$subarr;
//         $subarr=null;
//       }
//       if(isset($subarrleft)) {
//         $entry['paramsleft']=$subarrleft;
//         $subarrleft=null;
//       }
//       $result[]=$entry;
//       if(isset($sep)) $sep=$params[$i];
//     }
//     return $result;
//   }

//   public function parseAppInfo($appinfo) {
//     $result=array();
//     $result['params'] = array();
//     $result['synopsis'] = '';
//     $result['description'] = '';
//     $result['seealso'] = '';
//     $result['syntax'] = '';
//     $result['examples'] = array();
//     $result['variables'] = array();
//     $i=0;
//     $argument='';
//     $opt=null;
//     foreach ($appinfo as $line) {
//       // Sections
//       if (strlen($line)&&($line{0} == '[')) {
//         $tmp = explode(']', $line);
//         $sectionname=trim(substr($tmp[0], 1));
//         $i=0;
//         continue;
//       }

//       if(strpos($line,'-= Info about application')) {
//         $appdata=explode('\'', $line);
//         if(isset($appdata[1])) $result['name']=$appdata[1];
//         continue;
//       }

//       if(isset($sectionname))
//       switch($sectionname) {
//         case "Synopsis": {
//           $result['synopsis'].=$line.' ';
//         } break;
//         case "See Also": {
//           $result['seealso'].=$line.' ';
//         } break;
//         case "Syntax": {
//           $result['syntax'].=$line;
//         } break;
//         case "Description": {
//           if($i) {
//             if(isset($example)) unset($example);
//             $example = &$result['examples'][$i-1];
//             if(trim($line)=='') { if(!isset($example['lines'])) $example['lines']=array(); }
//             elseif($line{0}==' ') $example['lines'][]=trim($line);
//             elseif(isset($example['lines'])) {
//               $i=0;
// //              $result['description'].=$line.' ';
//             } else {
//               $example['description'].=((strlen($line)>30)?' ':"\n").$line;
//             }
//           }
//           if($i==0) {
//             if(strpos($line,'Example: ')===0) {
//               if(isset($example)) unset($example);
//               $example = &$result['examples'][];
//               $i=count($result['examples']);
//               $example['description']=substr($line,9);
//             } else {
//               $result['description'].=((strlen($line)>30)?' ':"\n").$line;
//             }
//           }
//         } break;
//         case "Arguments": {
//           if(strlen($line)&&($line{0}!=' ')&&(!strpos($line,' '))) {
//             $argument=trim($line);
//             $result['params'][$argument] = array('description' => '');
//           } elseif($argument!='') {
//             $words=explode(' ',$line);
//             if((strpos($line, '    ')===0)&&(strpos($words[4],':')&&(strpos($words[4],'NOTE:')===FALSE))) { //option
//               if(!isset($result['params'][$argument]['options'])) $result['params'][$argument]['options']=array();
//               if(isset($opt)) unset($opt);
//               $opt=&$result['params'][$argument]['options'][$line{4}];
//               $opt=array();
//               $opt['syntax']=substr($line,4,strrpos($words[4],':'));
//               $opt['description']=substr($line,strrpos($words[4],':')+5).' ';
//             } elseif(isset($opt)) {
//               if($line!='') {
//                 $opt['description'].=trim($line).' ';
//               }
//             } else {
//               $result['params'][$argument]['description'] .= trim($line).((strlen($line)>30)?' ':"\n");
//             }
//           }
//         }
//       }
//     }
//     $result['seealso']=strlen(trim($result['seealso']))?explode(',',$result['seealso']):array();
//     foreach($result['seealso'] as $k => $v) {
//       $result['seealso'][$k]=trim($v);
//     }
//     preg_match('/^([A-Za-z]+)\((.*)\)$/m',$result['syntax'],$match);
//     $match=$this->expandParams($match[2]);
//     $tmpattr=$result['params'];
//     $result['params']=array();
//     $this->fillParams($match,$result['params']);
//     foreach($tmpattr as $attr => $value) {
//       if(isset($value['description'])) $result['params'][$attr]['description']=$value['description'];
//       if(isset($value['options'])) $result['params'][$attr]['options']=$value['options'];
//     }
//     return $result;
//   }

//   public function getApplication($appname) {
//     $appname=mb_strtolower($appname);
//     $appmodule=getModulesByClass("dialplan\\${appname}Application");
//     if($appmodule) {
//       $result=$appmodule->getApplication();
//     } else {
//       $result = array();
//       $appinfo=$this->ami->Command('core show application '.$appname);
//       if(is_array($appinfo)) {
//         if(isset($appinfo['data'])) $appinfo=$appinfo['data'];
//         else return $result;
//       }
//       $appinfo=explode("\n",$appinfo);
//       $result=$this->parseAppInfo($appinfo);
//       if(!isset($result['name'])) $result['name']=$appname;
//     }
//     return $result;
//   }

//   public function getFunction($funcname) {
//     $funcname=mb_strtoupper($funcname);
//     $funcmodule=getModulesByClass("dialplan\\${funcname}Function");
//     if($funcmodule) {
//       $result=$funcmodule->getFunction();
//     } else {
//       $result = array();
//       $funcinfo=$this->ami->Command('core show function '.$funcname);
//       if(is_array($funcinfo)) {
//         if(isset($funcinfo['data'])) $funcinfo=$funcinfo['data'];
//         else return $result;
//       }
//       $funcinfo=explode("\n",$funcinfo);
//       $result=$this->parseAppInfo($funcinfo);
//       if(!isset($result['name'])) $result['name']=$funcname;
//     }
//     return $result;
//   }

}

?>