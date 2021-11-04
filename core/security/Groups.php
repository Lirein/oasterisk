<?php

namespace security;

class Groups extends \module\Collection {
  
  /**
   * Базовые привилегии доступа, используемые ядром OAS´terisk:<br>
   * <b>system_info</b> - Получение информации о системе (чтение состояния)<br>
   * <b>system_control</b> - Управление системой (отправка команд)<br>
   * <b>realtime</b> - Получение событий в режиме реального времени<br>
   * <b>agent</b> - Является оператором очереди вызовов<br>
   * <b>dialing</b> - Осуществление исходящих вызовов<br>
   * <b>message</b> - Отправка мгновенных сообщений<br>
   * <b>settings_reader</b> - Чтение настроек<br>
   * <b>settings_writer</b> - Изменение настроек<br>
   * <b>dialplan_reader</b> - Чтение диалплана<br>
   * <b>dialplan_writer</b> - Именение диалплана<br>
   * <b>security_reader</b> - Чтение настроек безопасности<br>
   * <b>security_writer</b> - Изменение настроек безопасности<br>
   * <b>cdr</b> - Просмотр дурналов детализации вызовов<br>
   * <b>invoke_commands</b> - Выполнение команд технологической платформы<br>
   * <b>debug</b> - Отладка и подключение к консоли технологической платформы<br>
   * 
   * @var array $internal_priveleges
   */
  public static $internal_priveleges = array(
    'system_info' => array('read' => array('system','call','reporting'), 'write' => array('system','reporting')),
    'system_control' => array('read' => array('system','call','reporting'), 'write' => array('system','command','call','reporting')),
    'realtime' => array('read' => array('dtmf','cc','aoc','user'), 'write' => array()),
    'agent' => array('read' => array('agent'), 'write' => array('agent')),
    'dialing' => array('read' => array('call','dtmf','cc',), 'write' => array('originate','call')),
    'message' => array('read' => array('user'), 'write' => array('message')),
    'settings_reader' => array('read' => array('system','config','reporting'), 'write' => array('reporting','system')),
    'settings_writer' => array('read' => array('system','config','reporting'), 'write' => array('reporting','system','config')),
    'dialplan_reader' => array('read' => array('system','dialplan','reporting'), 'write' => array('reporting','system')),
    'dialplan_writer' => array('read' => array('system','dialplan','reporting'), 'write' => array('reporting','system','dialplan')),
    'security_reader' => array('read' => array('security'), 'write' => array('system')),
    'security_writer' => array('read' => array('security'), 'write' => array('system','config')),
    'cdr' => array('read' => array('cdr','log'), 'write' => array('system')),
    'invoke_commands' => array('read' => array('agi'), 'write' => array('command','agi', 'aoc', 'user')),
    'debug' => array('read' => array('verbose'), 'write' => array())
  );

  /**
   * Базовые роли (группы безопасности) доступа к графическому интерфейсу:<br>
   * <b>full_control</b> - Полный доступ<br>
   * <b>admin</b> - Администрирование техплатформы<br>
   * <b>technician</b> - Чтение и редактирование настроек<br>
   * <b>operator</b> - Управление техплатформой<br>
   * <b>agent</b> - Оператор очереди вызовов<br>
   * <b>manager</b> - Просмотр журналов и состояния системы<br>
   *
   * @var array $internal_roles
   */
  public static $internal_roles = array(
    'full_control' => array('system_info','system_control','settings_reader','settings_writer','dialplan_reader','dialplan_writer','security_reader','security_writer','cdr','invoke_commands','realtime','dialing','message','agent','debug'),
    'admin' => array('settings_reader','settings_writer','dialplan_reader','dialplan_writer','security_reader','security_writer','cdr'),
    'technician' => array('settings_reader','settings_writer','dialplan_reader','dialplan_writer'),
    'operator' => array('system_info','realtime','invoke_commands','dialing','message','agent'),
    'manager' => array('system_info', 'cdr')
  );

  /**
   *  Перематывает итератор на первый элемент массива полей
   *
   * @return void 
   */
  public function rewind() {
    $this->items = array_keys(self::$internal_roles);
    $groupjson = '[{"id": ""}]';
    $items = \config\DB::readData('customgroup', $groupjson);
    foreach($items as $group) {
      $this->items[] = $group->id;
    }
    reset($this->items);
  }

  /**
   * Возвращает текущий элемент массива полей
   *
   * @return Group
   */
  public function current() {
    $group = current($this->items);
    return new Group($group);
  }

  /**
   * Возвращает полный список привилегий ядра технологической платформы в зависимости от перечня базовых привилений OAS´tersik
   *
   * @param array $privs Массив с набором базовых привилений OAS´tersik
   * @return object Структура с двумя свойствами набора привилегий ядра на чтение (read) и запись (write)
   */
  public static function expandPrivs(array $privs) {
    $read = array();
    $write = array();
    foreach($privs as $priv) {
      if(isset(self::$internal_priveleges[$priv])) {
        $read = array_merge($read, array_flip(self::$internal_priveleges[$priv]['read']));
        $write = array_merge($write, array_flip(self::$internal_priveleges[$priv]['write']));
      }
    }
    return (object) array('read' => array_keys($read), 'write' => array_keys($write));
  }

  public static function findbyRight(array $read, array $write) {
    $role = null;
    $allprivs = self::expandPrivs(array_keys(self::$internal_priveleges));
    if(in_array('all',$read)) {
      $read = $allprivs->read;
    }
    if(in_array('all',$write)) {
      $write = $allprivs->write;
    }
    foreach(self::$internal_roles as $rolename=>$role) {
      $roleprivs = self::expandPrivs($role['privs']);
      if((count(array_intersect($read, $roleprivs->read))==count($roleprivs->read))&&(count(array_intersect($write,$roleprivs->write))==count($roleprivs->write))) {
        $role = $rolename;
        break;
      }
    }
    return new \security\Group($role);
  }

  public static function SubjectAdd(int $event, \module\ISubject &$subject) {
    if(!$subject instanceof \module\Subject) return;
    $rest = null;
    if(property_exists($subject, 'restinterface')) $rest = $subject::$restinterface;
    if(!$rest) return;
    $resturi = $rest::getServiceLocation();
    $objectid = $subject->id;
    $groups = new \security\Groups();
    foreach($groups as $group) {
      $group->addObject($resturi, $objectid);
      $group->save();
    }
  }

  public static function SubjectRename(int $event, \module\ISubject &$subject) {
    if(!$subject instanceof \module\Subject) return;
    if($subject instanceof \security\Group) return;
    $rest = null;
    if(property_exists($subject, 'restinterface')) $rest = $subject::$restinterface;
    if(!$rest) return;
    $resturi = $rest::getServiceLocation();
    $objectid = $subject->old_id;
    $newobjectid = $subject->id;
    $groups = new \security\Groups();
    foreach($groups as $group) {
      $group->removeObject($resturi, $objectid);
      $group->addObject($resturi, $newobjectid);
      error_log('save group '.$group->id);
      $group->save();
    }
  }

  public static function SubjectRemove(int $event, \module\ISubject &$subject) {
    if(!$subject instanceof \module\Subject) return;
    $rest = null;
    if(property_exists($subject, 'restinterface')) $rest = $subject::$restinterface;
    if(!$rest) return;
    $resturi = $rest::getServiceLocation();
    $objectid = $subject->old_id;
    $groups = new \security\Groups();
    foreach($groups as $group) {
      $group->removeObject($resturi, $objectid);
      $group->save();
    }
  }

  public static function register() {
    self::setHandler(self::ADD, 'ISubject', array(__CLASS__, 'SubjectAdd'));
    self::setHandler(self::RENAME, 'ISubject', array(__CLASS__, 'SubjectRename'));
    self::setHandler(self::REMOVE, 'ISubject', array(__CLASS__, 'SubjectRemove'));
  }
  
}
?>