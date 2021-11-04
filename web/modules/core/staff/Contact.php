<?php

namespace staff;

/**
 * Интерфейс реализующий субьект коллекции
 * Должен содержать набор приватных свойств и геттеры/сеттеры для их обработки
 * Метод save - сохраняет субьект
 * Метод delete вызывает метод delete класса коллекции
 */
class Contact extends \channel\Peer {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var MorphingCollection $collection
   */
  static $collection = 'staff\\Group';

  private $group = null;

  private $old_group = null;

  private $linked_group = null;

  private $title;

  /**
   * Ссылка на оригинального пира
   *
   * @var \channel\Peer $peer
   */
  private $peer = null;

  private $alias = null;

  private $old_alias = null;

  private $contactTitle;

  private $externprops;

  /**
   * Конекст в кором содератся записи контакта
   *
   * @var \dialplan\Context $contextData
   */
  private $contextData;

  /**
   * Приложения диалплана
   *
   * @var \dialplan\Application[] $contactActions
   */
  private $contactActions;

  /**
   * Конструктор с идентификатором - инициализирует субьект коллекции
   * 
   * @param string $id Идентификатор элемента коллекции. Если идентификатор не задан, генерирует новый идентификатор, прежний идентификатор равен null. Если идентификатор задан - ищет субьект с указанным идентификатором или возвращает исключение в случае его отсутствия.
   */
  public function __construct(string $id = null, bool $minimal = false) {
    parent::__construct();
    $id = explode('@', $id);
    $exten = $id[0];
    $group = $id[1];
    if(!is_numeric($exten)) {
      if(strpos($exten, 'contact_')===0) {
        $exten = substr($exten, 7);
      }
    }
    $this->contextData = new \dialplan\Context('staff-'.$group, $minimal);
    if(!$this->contextData) {
      $this->id = null;
      $this->old_id = null;
      return;
    }
    if(!is_numeric($exten)) {
      foreach($this->contextData as $entry => $entrypoints) {
        if(is_numeric($entry)) {
          $firstentry = $entrypoints[array_key_first($entrypoints)];
          if(($firstentry instanceof \core\AGIApplication)&&$firstentry->native&&($firstentry->agi instanceof \dial\DialModule)) {
            if($firstentry->contact) {
              $contact = explode('@', $firstentry->contact, 2);
              if(strtolower(($contact[0])==strtolower($exten))&&($contact[1]==$group)) {
                $exten = $entry;
                $id = $exten.'@'.$group;
                break;
              }
            }
          }
        }
      }
    }
    $this->id = $exten;
    $this->group = $group;
    $this->title = $exten;
    $this->contactTitle = '';
    $this->alias = '';
    $this->old_alias = '';
    $this->peer = null;
    $this->externprops = new \stdClass();
    $this->old_id = $exten;
    $this->old_group = $group;
    $this->linked_group = null;
    $this->contactActions = null;
    $this->interLock($this->id.'@'.$this->group);
    $extendata = $this->contextData->$exten;
    if(!isset($extendata)) $extendata = array();
    $extenkeys = array_keys($extendata);
    $index = array_shift($extenkeys);
    $firstentry = isset($index)?$extendata[$index]:null;
    if(($firstentry instanceof \core\AGIApplication)&&$firstentry->native&&($firstentry->agiclass === 'dial\\DialModule')) {
      $contact = explode('@', $firstentry->contact, 2);     
      $this->alias = trim($contact[0]);
      $this->old_alias = $this->alias;
      if(trim($contact[1]) == $group) {
        if($firstentry->title!=$exten) $this->title = $firstentry->title;
      } else {
        $this->old_group = null;
        $this->linked_group = trim($contact[1]);
      }
    }
    $titledata = explode(':', $this->title, 2);
    $this->contactTitle = isset($titledata[1])?trim($titledata[1]):'';
    if(preg_match('/^\((.*)\) (.*)$/m', trim($titledata[0]), $match)) {
      $this->title = trim($match[2]);
      if($minimal) {
        $this->peer = $match[1];
      } else {
        $this->peer = \channel\Peers::find(trim($match[1]));
      }
    } else {
      $this->title = trim($titledata[0]);
    }
    if($this->alias) {
      $contactalias = 'contact_'.$this->alias;
      if($this->old_group) {
        if($minimal) {
          $this->contactActions = array();
        } else {
          $this->contactActions = $this->contextData->$contactalias;
        }
      } elseif($this->linked_group) {
        $linkedContext = \dialplan\Dialplan::find('staff-'.$this->linked_group);
        if($linkedContext) {
          if($minimal) {
            $this->contactActions = array();
          } else {
            $this->contactActions = $this->contextData->$contactalias;
          }
        }
        unset($linkedContext);
      }
    }
    $modules = findModulesByClass('staff\Property', true);
    if($modules&&count($modules)) {
      foreach($modules as $module) {
        $classname = $module->class;
        $info = $classname::info();
        $propertyclass = $info->class;
        $contactprops = new $classname($exten.'@staff-'.$group);
        $this->externprops->$propertyclass = $contactprops->getProperties();
        unset($contactprops);
        if (!$this->externprops->$propertyclass){
          $this->externprops->$propertyclass=new \stdClass();
        }
      }
    }
    $this->interUnlock($this->id.'@'.$this->group);
  }

  public function __serialize() {
    $keys = array();
    $keys['id'] = $this->id;
    $keys['group'] = $this->group;
    $keys['old_id'] = $this->old_id;
    $keys['old_group'] = $this->old_group;
    $keys['linked_group'] = $this->linked_group;
    $keys['alias'] = $this->alias;
    $keys['old_alias'] = $this->old_alias;
    $keys['name'] = $this->title;
    $peerclass = ($this->peer)?get_class($this->peer):null;
    $keys['peer'] = ($this->peer)?($peerclass::getTypeName().'/'.$this->peer->id):null;
    $keys['title'] = $this->contactTitle;
    $keys['externprops'] = $this->externprops;
    return $keys;
  }

  public function __unserialize(array $keys)
  {
    $this->id = $keys['id'];
    $this->group = $keys['group'];
    $this->old_id = $keys['old_id'];
    $this->old_group = $keys['old_group'];
    $this->linked_group = $keys['linked_group'];
    $this->title = $keys['name'];
    $this->alias = $keys['alias'];
    $this->old_alias = $keys['old_alias'];
    $this->contactTitle = $keys['title'];
    if($keys['peer']) $this->peer = \channel\Peers::find($keys['peer']);
    $this->externprops = $keys['externprops'];
    if($this->old_group) {
      $this->contextData = \dialplan\Dialplan::find('staff-'.$this->old_group);
    } else {
      $this->contextData = \dialplan\Dialplan::find('staff-'.$this->group);
    }
    $contactalias = $this->old_alias;
    if($contactalias) {
      if($this->linked_group) {
        $linkedGroup = \dialplan\Dialplan::find('staff-'.$this->linked_group);
        if($linkedGroup) {
          $this->contactActions = $linkedGroup->$contactalias;
        }
      } else {
        $this->contactActions = $this->contextData->$contactalias;
      }
    }
  }

  public function __isset($property) {
    if(in_array($property, array('id', 'group', 'uniqueid', 'uniqueoldid', 'old_id', 'old_group', 'name', 'title', 'externprops', 'alias', 'old_alias',
       'numbers', 'channels', 'peer'))) return true;
    return false;
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
    if($property=='id') return $this->id;
    if($property=='group') return $this->group;
    if($property=='uniqueid') return $this->id.'@'.$this->group;
    if($property=='uniqueoldid') return ($this->old_group&&$this->old_id)?($this->old_id.'@'.$this->old_group):null;
    if($property=='old_id') return $this->old_id;
    if($property=='old_group') return $this->old_group;
    if($property=='name') return $this->title;
    if($property=='title') return $this->contactTitle;
    if($property=='externprops') return $this->externprops;
    if($property=='alias') return $this->alias;
    if($property=='old_alias') return $this->old_alias;
    if($property=='numbers') return $this->getNumbers()->numbers;
    if($property=='channels') return $this->getNumbers()->channels;
    if($property=='peer') return $this->peer;
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    if($property=='id') {
      if($this->id == $this->title) {
        $this->contactTitle = $value;
      }
      $this->id = $value;
      return true;
    } 
    if($property=='alias') {
      if($this->alias == $this->title) {
        $this->contactTitle = $value;
      }
      $this->alias = $value;
      return true;
    } 
    if($property=='title') {
      $this->contactTitle = $value;
      return true;
    }
    if($property=='name') {
      $this->title = $value;
      return true;
    }
    return false;
  }

  private function getNumbers() {
    $result = new \stdClass();
    $result->numbers = array();
    $result->channels = array();
    $id = $this->old_id;
    if(isset($this->contextData)) foreach($this->contextData->$id as $action) {
      if($action instanceof \core\DialApplication) {
        foreach($action->destinations as $line) {
          if($line instanceof \channel\Peer) {
            $result->channels[] = $line->getDial();
            if($line instanceof \staff\Contact) $result->numbers[] = $line->old_id;
          } else {
            $result->numbers[] = $line->dialnumber;
            $result->channels[] = $line->getDial($line->dialnumber);
          }
        }
      }
    }
    return $result;
  }

  /**
   * Сохраняет субьект в коллекции
   *
   * @return bool Возвращает истину в случае успешного сохранения субъекта
   */
  public function save() {
    if(empty($this->id)) return false;
    $result = false;
    $id = $this->id;
    $newcontactinfo = new \core\AGIApplication('agi=dial,contact='.$this->id.'@'.($this->linked_group?$this->linked_group:$this->group));
    $newcontactinfo->title = ($this->peer?($this->peer->getTypeName().'/'.$this->peer->id.' '):'').$this->name.($this->title?(': '.$this->title):'');
    if(empty($this->old_id)) {
      \staff\Group::add($this); 
      $this->contextData->$id = array(1 => $newcontactinfo);
      $contact = 'contact_'.$this->alias;
      $this->contextData->$contact = $this->contactActions;
      return $this->contextData->save();
    } else {
      $oldid = $this->old_id;
      $contact = 'contact_'.$this->old_alias;
      if($this->contextData&&isset($this->contextData->$oldid)) {
        unset($this->contextData->$oldid);
        if(isset($this->contextData->$contact)) unset($this->contextData->$contact);
      }
      $contact = 'contact_'.$this->alias;
      $this->contextData->$id = array(1 => $newcontactinfo);
      if(!$this->linked_group) $this->contextData->$contact = $this->contactActions;
      if(($this->old_id!=$this->id)&&($this->old_group==$this->group)) {
        Group::rename($this); 
        $result = $this->contextData->save();
      } else {
        Group::change($this);
        $result = $this->contextData->save();
      }
    }
    return $result;
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    if(!$this->old_id) return false;
    $oldid = $this->old_id;
    unset($this->contextData->$oldid);
    if($this->old_group) {
      $oldid = 'contact_'.$this->old_alias;
      unset($this->contextData->$oldid);
    }
    $this->contextData->save();
    \staff\Group::remove($this);
    return true;
  }

  public function reload() {
    return $this->contextData->reload();
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast(){
    $keys = array();
    $keys['type'] = static::getTypeName();
    $keys['id'] = $this->id.'@'.$this->group;
    $keys['alias'] = $this->alias;
    $keys['name'] = $this->title;
    $keys['title'] = $this->contactTitle;
    if($this->peer) {
      if(is_string($this->peer)) {
        $keys['peer'] = $this->peer;  
      } else {
        $keys['peer'] = $this->peer->cast();
        $keys['peer']->id = $this->peer->getTypeName().'/'.$keys['peer']->old_id;
        unset($keys['peer']->old_id);
      }
    } else {
      $keys['peer'] = null;
    }
    foreach($this->externprops as $extern => $props){
      $keys[$extern] = $props;
    }
    $actions = array();
    if($this->contactActions) foreach($this->contactActions as $action){
      $actions[] = $action->cast();
    }
    $keys['actions'] = $actions;
    $numbers = $this->getNumbers();
    $keys['numbers'] = $numbers->numbers;
    $keys['channels'] = $numbers->channels;
    return (object)$keys;
  }

  public function assign($request_data){
    foreach($request_data as $key => $value) {
      if(in_array($key, array('id', 'title', 'name', 'alias'))) $this->__set($key, $value); 
      elseif($key == 'actions') {
        $this->contactActions = array();
        foreach($value as $action){
          $app = \dialplan\Application::find($action->name);
          if($app) {
            $app->assign($action);
            $this->contactActions[] = $app;
          }
        }
      }
    }
    $modules = findModulesByClass('staff\Property', true);
    if($modules&&count($modules)) {
      foreach($modules as $module) {
        $classname = $module->class;
        $info = $classname::info();
        $propertyclass = $info->class;
        if(isset($request_data->$propertyclass)) {
          $contactprops = new $classname($this->id.'@staff-'.$this->group);
          $contactprops->setProperties($request_data->$propertyclass);
          unset($contactprops);
        }
      }
    }    
    return true;
  }

  public function getDial() {
    return 'Local/'.$this->id.'@staff-'.$this->group.'/n';
  }

  public function checkDial(string $dial) {
    $dials = explode('&', $dial);
    $result = false;
    foreach($dials as $dialentry) {
      if(strpos($dialentry, 'Local/'.$this->id.'@staff-'.$this->group)===0) {
        $result=true;
        break;
      }
    }
    return $result;
  }

  public function checkChannel(string $channel, string $phone) {
    $result = false;
    if((strpos($channel, 'Local/'.$this->id.'@staff-'.$this->group)===0)||
       (strpos($channel, 'Local/contact_'.$this->alias.'@staff-'.$this->group)===0)) $result=true;
    if(!$result&&$this->contactActions) {
      foreach($this->contactActions as $action) {
        if($action instanceof \core\DialApplication) {
          foreach($action->destinations as $peer) {
            $peer->checkChannel($channel, $phone);
          }
        }
      }
    }
    return $result;
  }

  //TODO: Добавить триггеры на изменение/удаление контекста

}
?>