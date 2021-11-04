<?php

namespace sound;

/**
 * Интерфейс реализующий субьект коллекции
 * Должен содержать набор приватных свойств и геттеры/сеттеры для их обработки
 * Метод save - сохраняет субьект
 * Метод delete вызывает метод delete класса коллекции
 */
class Sound extends \module\MorphingSubject {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \sound\Sounds $collection
   */
  static $collection = 'sound\\Sounds';

  private $files;

  private $soundsdir;

  public function __construct(string $id = null) {
    $this->id = $id;
    $this->files = array();
    $this->soundsdir = Sounds::getSoundsDir();
    $languages = Sounds::getLanguages();
    foreach($languages as $language) {
      if($language=='other') $dirdata = Sounds::getDir();
      else $dirdata = Sounds::getDir($language, $language);
      if(isset($dirdata[$id])) {
        $this->old_id = $id;
        if (isset($dirdata[$id][$language])){
          $this->files[$language] = $dirdata[$id][$language];
        }
      }
    }
  }
  
  public function getStream($language = '', $format = '') {
    reset($this->files);
    if($language == '') $language = key($this->files);
    if(!$language) return null;
    if($format == '') $format = $this->files[$language][0];
    if(!$format) return null;
    $filename = $this->soundsdir.'/'.(($language=='other')?'':($language.'/')).$this->old_id.'.'.$format;
    if(!file_exists($filename)) return null;
    $tmpfilename = tempnam('/tmp', 'oasterisk-music-');
    unlink($tmpfilename);
    $tmpfilename .= '.wav';
    $informat = '';
    switch ($format) {
      case "gsm": $informat = '-t gsm -r8000 -c1'; break;
      case "alaw": $informat = '-t al -r8000 -c1'; break;
      case "ulaw": $informat = '-t ul -r8000 -c1'; break;
      case "sln": $informat = '-t s16 -r8000 -c1'; break;
      case "sln12": $informat = '-t s16 -r12000 -c1'; break;
      case "sln16": $informat = '-t s16 -r16000 -c1'; break;
      case "sln24": $informat = '-t s16 -r24000 -c1'; break;
      case "sln32": $informat = '-t s16 -r32000 -c1'; break;
      case "sln44": $informat = '-t s16 -r44000 -c1'; break;
      case "sln48": $informat = '-t s16 -r48000 -c1'; break;
      case "sln96": $informat = '-t s16 -r96000 -c1'; break;
      case "sln192": $informat = '-t s16 -r192000 -c1'; break;
      case "vox": $informat = '-t raw -r 8000 -c1 -U -b 8'; break;

      // case "g722": {
      //   $sox = sprintf('ffmpeg -f g722 -i %s -acodec pcm_s16le -ar 16000 %s', $filename, $tmpfilename);
      // } break;
      // case "g719":{
      //   $sox = sprintf('sox %s -b 16 -c 1 -t wav %s rate -I 8000', $filename, $tmpfilename);
      // } break;

    }
    $sox = sprintf('sox %s "%s" -c1 -t wav -e floating-point -r44100 "%s"', $informat, $filename, $tmpfilename);
    //$sox = sprintf('sox %s "%s" "%s"', $informat, $filename, $tmpfilename);
// TODO opus
    system($sox);
    $stream = file_get_contents($tmpfilename);
    unlink($tmpfilename);
    return $stream;             
  }

  /**
   * Деструктор - освобождает память
   */
  public function __destruct() {

  }

  public function __serialize() {
    $keys = array();
    $keys['id'] = $this->id;
    $keys['old_id'] = $this->old_id;
    $keys['files'] = serialize($this->files);
    return $keys;
  }

  public function __unserialize(array $keys) {
    $this->soundsdir = Sounds::getSoundsDir();
    $this->idf = $keys['id'];
    $this->old_id = $keys['old_id'];
    $this->files = unserialize($keys['files']);
  }

  public function __isset($property){
    if(in_array($property, array('id', 'old_id', 'title', 'name', 'languages'))) return true;
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
    if($property=='old_id') return $this->old_id;
    if(($property=='title') || ($property=='name')) return basename($this->id);
    if($property=='languages') return $this->files;
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    return false;
  }

  /**
   * Сохраняет настройки
   *
   * @return bool Возвращает истину в случае успешного сохранения
   */
  public function save() {
        
  }

  /**
   * Удаляет субьект коллекции
   *
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    $file = null;
    $file = Sounds::getSoundsDir().'/'.$this->id;
    return unlink($file);
  }

  /**
   * Перезагружает
   *
   * @return bool Возвращает истину в случае успешной перезагрузки
   */
  public function reload(){
    return $this->ami->send_request('Command', array('Command' => 'sound reload'))!==false;
  }

  /**
   * Возвращает все свойства в виде объекта со свойствами
   *
   * @return \stdClass
   */
  public function cast() {
    $keys = array();
    $keys['id'] = $this->__get('id'); //'path',
    $keys['title'] = $this->__get('title'); //'path',
    $keys['languages'] = $this->__get('languages'); //: [{lang: <id>, formats: []}, ]
    return (object)$keys;
  }
    
  /**
   * Устанавливает все свойства новыми значениями
   *
   * @param stdClass $request_data Объект со свойствами - ключ→значение 
   */
  public function assign($request_data){   
    return false;
  }

}
?>