<?php

namespace queue;

/**
 * Модуль работы с очередями вызовов.
 * Перейти в очередь:
 * AGI(agi=queue,goto=queue_name)
 * Очередь запоминает последнюю позицию абонента в очереди и если не было соединения с оператором производит подключение под последней позицией в очереди.
 * AGI(agi=queue,goto=queue_name,save)
 * Добавить оператора в очередь:
 * AGI(agi=queue,add=queue_name)
 * AGI(agi=queue,add=queue_name,operator=contactid)
 * Удалить оператора из очереди:
 * AGI(agi=queue,remove=queue_name)
 * AGI(agi=queue,remove=queue_name,operator=contactid)
 * Сбросить состояние очереди и операторов:
 * AGI(agi=queue,reset=queue_name)
 */
class QueueModule extends \Module implements \module\IAGI {

  public function agi(\stdClass $request_data) {
    if(isset($request_data->goto)) {

    } elseif(isset($request_data->add)) { //

    } elseif(isset($request_data->remove)) { //

    } elseif(isset($request_data->reset)) { //

    }
  }

}

?>