<?php

namespace config;

class NetPlan {
  /**
   * Внутреннее хранилище имени файла
   *
   * @var string
   */
  private $_filename;

  /**
   * Перечень физических адаптеров
   *
   * @var NetPlanEthernet[]
   */
  public $ethernets = array();

  /**
   * 
   *
   * @var NetPlanBonding[]
   */
  public $bonds = array();

  /**
   * 
   *
   * @var NetPlanVlan[]
   */
  public $vlans = array();

  /**
   * 
   *
   * @var NetPlanBridge[]
   */
  public $bridges = array();

  /**
   * 
   *
   * @var NetPlanWiFi[]
   */
  public $wifis = array();

  public $version = 2;
  public $renderer = 'networkd';

  /**
   * Конструктор
   *
   * @param string $filename Имя загружаемого файла
   */
  public function __construct(string $filename) {
    $this->_filename = $filename;
    $this->load();
  }

  /**
   * Деструктор
   */
  public function __destruct() {
    foreach($this->ethernets as $value) {
      unset($value);
    }
    foreach($this->bonds as $value) {
      unset($value);
    }
    foreach($this->vlans as $value) {
      unset($value);
    }
    foreach($this->bridges as $value) {
      unset($value);
    }
    foreach($this->wifis as $value) {
      unset($value);
    }
    
  }

  /**
   * Преобразует экземпляр класса в строку
   *
   * @return string Возвращает список секций в виде строки
   */
  public function __toString() {
    $struct = array('network' => array(
      'version' => $this->version,
      'renderer' => $this->renderer,
    ));
    foreach ($this->ethernets as $id => $adapter) {
      $struct['network']['ethernets'][$id] = $adapter->toArray();
    }
    foreach ($this->bonds as $id => $adapter) {
      $struct['network']['bonds'][$id] = $adapter->toArray();
    }
    foreach ($this->vlans as $id => $adapter) {
      $struct['network']['vlans'][$id] = $adapter->toArray();
    }
    foreach ($this->bridges as $id => $adapter) {
      $struct['network']['bridges'][$id] = $adapter->toArray();
    }
    foreach ($this->wifis as $id => $adapter) {
      $struct['network']['wifis'][$id] = $adapter->toArray();
    }
    
    return yaml_emit($struct);
  }

  /**
   * Читает файл конфигурации. Заполняет массив секций
   *
   * @throws \Exception Выдаёт исключение, если файл не существует или нечитабельный.
   * @return void
   */
  public function load() {
    if(!file_exists($this->_filename) || !is_readable($this->_filename)) {
      throw new \Exception("The file ".$this->_filename." doesn't exist or is not readable");
    }
    $ndocs = 0;
    $struct = yaml_parse_file($this->_filename, 0, $ndocs);

    if (isset($struct['network'])) {
      foreach($struct['network'] as $key => $value) {
        switch ($key) {
          case 'version': {
            $this->version = $value;
          } break;
          case 'renderer': {
            $this->renderer = $value;
          } break;
          case 'ethernets': {
            foreach($value as $id => $data) {
              $this->ethernets[$id] = new NetPlanEthernet($id);
              $this->ethernets[$id]->assign($data);
            }
            //$this->ethernets = $value;
          } break;
          case 'wifis': {
            foreach($value as $id => $data) {
              $this->wifis[$id] = new NetPlanWiFi($id);
              $this->wifis[$id]->assign($data);
            }
          } break;
          case 'bonds': {
            foreach($value as $id => $data) {
              $this->bonds[$id] = new NetPlanBonding($id);
              $this->bonds[$id]->assign($data);
            }
          } break;
          case 'bridges': {
            foreach($value as $id => $data) {
              $this->bridges[$id] = new NetPlanBridge($id);
              $this->bridges[$id]->assign($data);
            }
          } break;
          case 'vlans': {
            foreach($value as $id => $data) {
              $this->vlans[$id] = new NetPlanVlan($id);
              $this->vlans[$id]->assign($data);
            }
          } break;
          default: {

          }
        }
      }
    }
  }

  /**
   * Сохраняет конфигурацию
   *
   * @return bool Возвращает true в случае успеха операции
   */
  public function save() {
    return $this->saveAs($this->_filename);
  }

  /**
   * Сохраняет конфигурацию в указанный файл
   *
   * @param string $filename Имя файла для записи
   * @throws \Exception Возвращает исключение, если запись в файл невозможна
   * @return bool Возвращает true в случае успеха операции
   */
  public function saveAs(string $filename) {
    if(file_put_contents($filename, (string) $this) === false) {
      throw new \Exception("Impossible to write to file ".$filename);
    }
    exec('sudo netplan generate');
    exec('sudo netplan apply');
    return true;
  }

}
