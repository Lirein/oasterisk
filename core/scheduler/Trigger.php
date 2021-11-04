<?php

namespace scheduler;

/**
 * @ingroup triggers
 * Класс триггера расписания. Запускатеся при инициалиазции события расписания.
 */
abstract class Trigger extends \Module {

  /**
   * Возвращает отображаемое имя триггера
   *
   * @return void
   */
  abstract static public function getName();

  //TODO: Добавить метод получения спецификации доступных методов триггера

  /**
   * Запуск триггера, должен вернуть дополненную структуру данных и новый набор переменных для задания по расписанию
   *
   * @param \stdClass $variables Начальные данные для триггера
   * @return \stdClass Новые данные для задачи по расписанию
   */
  abstract public function start($variables);

  /**
   * Запускает функцию характерную для работающего триггера
   *
   * @param \stdClass $request_data Набор данных AGI сценария
   * @return bool Возвращает истину в случае успешного выполнения
   */
  abstract public function trigger($request_data);

  /**
   * Возвращает список переменных, которые модифицирует или добавляет тригген
   *
   * @return void
   */
  public function vars() {
    return array();
  }

}

?>