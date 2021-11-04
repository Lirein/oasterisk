<?php

namespace core;

class CustomSound extends \sound\Sound {

  /**
   * Приватное свойство со ссылкой на класс реализующий интерфейс коллекции
   *
   * @var \core\CustomSounds $collection
   */
  static $collection = 'core\\CustomSounds';

  private $name = null;

  public function __construct(string $id = null) {
    parent::__construct($id);
  }

  /**
   * Метод осуществляет проверку существования приватного свойства и возвращает его значение
   *
   * @param mixed $property Имя свойства
   * @return mixed Значение свойства
   */
  public function __get($property){
    return parent::__get($property);
  }

  /**
   * Метод осуществляет установку нового значения приватного свойства
   *
   * @param mixed $property Имя свойства
   * @param mixed $value Значение свойства
   */
  public function __set($property, $value){
    return parent::__set($property, $value);
  }

  /**
   * Сохраняет аудиофайл на диске
   * 
   * @param stdClass $file Аудиофайл
   * @return bool Возвращает истину в случае успешного сохранения субьекта
   */
  public function upload($file){
    if(empty($file->language)) $file->language = 'other';
    $path = \sound\Sounds::getSoundsDir().'/'.(($file->language=='other')?'':($file->language.'/')).'custom';
    if (!file_exists($path)) {
      mkdir($path);
    }
    umask(0007);
    return copy($file->tmp_name, $path.'/'.$file->name); 
  }

  /**
   * Удаляет субъект коллекции, если формат не указан удаляет все форматы коллекции
   *
   * @param string $language Язык аудиофайла, по умолчанию other
   * @param string $format Формат аудиофайла, по умолчанию не указан.
   * @return bool Возвращает истину в случае успешного удаление субьекта
   */
  public function delete() {
    $result = true;
    list($language, $format) = func_get_args();
    if(empty($language)) $language = 'other';
    if(empty($format)) $format = null;
    if ($format) {
      $result = unlink(\sound\Sounds::getSoundsDir().'/'.(($language=='other')?'':($language.'/')).$this->id.'.'.$format);
    } else {
      foreach ($this->languages[$language] as $fileformat){
        $result &= unlink(\sound\Sounds::getSoundsDir().'/'.(($language=='other')?'':($language.'/')).$this->id.'.'.$fileformat);
      }
    }
    return $result;
  }

}

?>
