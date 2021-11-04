<?php

namespace scheduler;

abstract class Contacts extends \Module {

  /**
   * Номер последнего контакта в списке
   *
   * @var int $lastcontact
   */
  protected $lastcontact;

  /**
   * Мдентификатор последнего контакта в списке для проверки изменений в списке при перезагрузке кэша
   *
   * @var string $lastcontactid
   */
  protected $lastcontactid;

  /**
   * Наименование группы списка контактов
   *
   * @var string $groupname
   */
  protected $groupname;

  static $defaultparams = '{"lastcontact": 0, "lastcontactid": ""}';
  
  static $maxbuffer = 100;

  /**
   * Конструктор - инициализирует класс списка контактов
   * 
   * @param string $group Указывает группу контактов с которой работает список
   */
  public function __construct(string $group) {
    parent::__construct();
    $this->lastcontact = 0;
    $this->lastcontactid = '';
    $this->groupname = $group;
    $contactinfo = \config\DB::readDataItem('contactlist/'.basename(str_replace('\\', '/', get_class($this))), 'group', $this->groupname, self::$defaultparams);
    if($contactinfo) {
      $this->lastcontact = $contactinfo->lastcontact;
      $this->lastcontactid = $contactinfo->lastcontactid;  
    }
  }

  /**
   * Статический метод возвращающий ассоциативный список "ключ = название" всех групп контактов
   *
   * @return array
   */
  abstract public static function getGroups();

  /**
   * Возвращает идентификатор целевой группы с которой работает список
   *
   * @return string
   */
  final public function getGroup() {
    return $this->groupname;
  }

  /**
   * Возвращает массив из не более чем $maxbuffer (100) контактов, должна быть реализована в классе наследнике
   *
   * @param integer $first Первый запрашиваемый элемент списка, если он входит в в диапазон 0..($maxbufer - 1) из
   * ста элментов, возвращает всю страницу начиная с искомого элемента, например при $first = 10 и количестве элементов 72 вернет элементы с 0 по 71.
   * @return Contact[] Массив контактов или null если список пуст
   */
  abstract protected function fetch(int $first);

  /**
   * Пененосит контакт из одной группы в другую, для реализации в классе наследнике
   *
   * @param string $contactId Идентификатор контакта
   * @param string $group Идентификатор новой группы контактов
   * @return bool Возвращает истину при успешном перемещении элемента списка
   */
  abstract protected function moveContact(string $contactId, string $group);

  /**
   * Возвращает один элемент списка контактов и перемещает его в хвост списка (сдвигает элемент очереди)
   *
   * @return Contact Контакт для обзвона
   */
  final public function get() {
    $this->lock(basename(str_replace('\\', '/', get_class($this))).'_cache');
    $cached_data = $this->cache->get(basename(str_replace('\\', '/', get_class($this))).'_cache');
    if($cached_data) {
      if(!isset($cached_data[$this->lastcontact%static::$maxbuffer])||(count($cached_data)==0)) {
        $cached_data = null;
      }
    }
    if(!$cached_data) {
      $cached_data = $this->fetch($this->lastcontact);
      if(!$cached_data||!isset($cached_data[$this->lastcontact%static::$maxbuffer])) {
        $this->lastcontact = 0;
        $this->lastcontactid = '';
        $cached_data = $this->fetch($this->lastcontact);
      } else {
        if(($cached_data[$this->lastcontact%static::$maxbuffer]->id!=$this->lastcontactid)&&($this->lastcontactid!='')) {
          $start = intdiv($this->lastcontact, static::$maxbuffer)*static::$maxbuffer;
          for($i=0; $i < count($cached_data); $i++) {
            if($cached_data[$i]->id == $this->lastcontactid) {
              $this->lastcontact = $start + $i;
              break;
            }
          }
          if($cached_data[$this->lastcontact%static::$maxbuffer]->id!=$this->lastcontactid) {
            if($this->lastcontact%static::$maxbuffer >= static::$maxbuffer/2) {
              $start = $start + static::$maxbuffer;
              $cached_data = $this->fetch($start);
            } elseif(($this->lastcontact%static::$maxbuffer < static::$maxbuffer/2) && (($this->lastcontact-static::$maxbuffer) > 0)) {
              $start = $start-static::$maxbuffer;
              $cached_data = $this->fetch($start);
            } else {
              $this->lastcontact = 0;
              $this->lastcontactid = '';
              $cached_data = $this->fetch($this->lastcontact);
            }
            if(($cached_data[$this->lastcontact%static::$maxbuffer]->id!=$this->lastcontactid)&&($this->lastcontactid!='')) {
              for($i = 0; $i < count($cached_data); $i++) {
                if($cached_data[$i]->id == $this->lastcontactid) {
                  $this->lastcontact = $start + $i;
                  break;
                }
              }
              if($cached_data[$this->lastcontact%static::$maxbuffer]->id!=$this->lastcontactid) {
                $this->lastcontact = 0;
                $this->lastcontactid = '';
                $cached_data = $this->fetch($this->lastcontact); 
              }
            }
          }
        }
      }
    }
    if($cached_data) {
      $result = $cached_data[$this->lastcontact%static::$maxbuffer];
      $this->lastcontact++;
      if(($this->lastcontact%static::$maxbuffer>=count($cached_data))||($this->lastcontact%static::$maxbuffer==0)) {
        if($this->lastcontact%static::$maxbuffer>=count($cached_data)) {
          $cached_data = $this->fetch($this->lastcontact);
          if($cached_data) {
            if($this->lastcontact%static::$maxbuffer>=count($cached_data)) {
              $cached_data = null;
            }
          }
        } else {
          $cached_data = $this->fetch($this->lastcontact);
        }
        if($cached_data) {
          $this->lastcontactid = $cached_data[$this->lastcontact%static::$maxbuffer]->id;
        } else {
          $this->lastcontact = 0;
          $this->lastcontactid = '';
        }
      } else {
        $this->lastcontactid = $cached_data[$this->lastcontact%static::$maxbuffer]->id;
      }
      \config\DB::writeDataItem('contactlist/'.basename(str_replace('\\', '/', get_class($this))), 'group', $this->groupname, self::$defaultparams, (object)array('lastcontact' => $this->lastcontact, 'lastcontactid' => $this->lastcontactid));
      if($cached_data) {
        $this->cache->set(basename(str_replace('\\', '/', get_class($this))).'_cache', $cached_data, 60*3); //Сохраняем кэш на 3 минуты
      } else {
        $this->cache->delete(basename(str_replace('\\', '/', get_class($this))).'_cache');
      }
      $this->unlock(basename(str_replace('\\', '/', get_class($this))).'_cache');
      return $result;
    }
    $this->unlock(basename(str_replace('\\', '/', get_class($this))).'_cache');
    return null;
  }

  /**
   * Пененосит контакт из одной группы в другую оставляя указатель элемента на текущей позиции
   *
   * @param string $contactId Идентификатор контакта
   * @param string $group Идентификатор новой группы контактов
   * @return bool Возвращает истину при перемещении элемента списка
   */
  final public function move(string $contactId, string $group) {
    if($this->moveContact($contactId, $group)) {
      $this->lock(basename(str_replace('\\', '/', get_class($this))).'_cache');
      $cached_data = $this->cache->get(basename(str_replace('\\', '/', get_class($this))).'_cache');
      if($cached_data) {
        $found = false;        
        for($i=0; $i<count($cached_data); $i++) {
          if($cached_data[$i]->id == $contactId) {
            unset($cached_data[$i]);
            $cached_data = array_values($cached_data);
            if($i<$this->lastcontact) $this->lastcontact--;
            $found = true;
            break;
          }
        }
        if(!$found) {
          $this->lastcontact--;
          $this->lastcontactid = '';
          $cached_data = $this->fetch($this->lastcontact);
        }
        $this->cache->set(basename(str_replace('\\', '/', get_class($this))).'_cache', $cached_data, 60*3); //Сохраняем кэш на 3 минуты
        \config\DB::writeDataItem('contactlist/'.basename(str_replace('\\', '/', basename(str_replace('\\', '/', get_class($this))))), 'group', $this->groupname, self::$defaultparams, (object)array('lastcontact' => $this->lastcontact, 'lastcontactid' => $this->lastcontactid));
      } else {
        $this->lastcontact--;
        $this->lastcontactid = '';
        \config\DB::writeDataItem('contactlist/'.basename(str_replace('\\', '/', basename(str_replace('\\', '/', get_class($this))))), 'group', $this->groupname, self::$defaultparams, (object)array('lastcontact' => $this->lastcontact, 'lastcontactid' => $this->lastcontactid));
      }
      $this->unlock(basename(str_replace('\\', '/', get_class($this))).'_cache');
      return true;
    }
    return false;
  }
  
  /**
   * Очищает кэш и метаинформацию о списке контактов
   *
   * @return void
   */
  final public function flush() {
    $this->lastcontact = 0;
    $this->lastcontactid = '';
    $this->lock(basename(str_replace('\\', '/', get_class($this))).'_cache');
    $this->cache->remove(basename(str_replace('\\', '/', get_class($this))).'_cache');
    \config\DB::deleteDataItem('contactlist/'.basename(str_replace('\\', '/', get_class($this))), 'group', $this->groupname, self::$defaultparams);
    $this->unlock(basename(str_replace('\\', '/', get_class($this))).'_cache');
  }

  /**
   * Обновляет поле контакта
   * 
   * @param string $contactId Идентификатор контакта
   * @param string $field Наименование поля
   * @param string $value Значене поля
   * @return bool Возвращает истину в случае успешной смены значения поля
   */
  abstract public function update(string $contactId, string $field, string $value);

}
?>