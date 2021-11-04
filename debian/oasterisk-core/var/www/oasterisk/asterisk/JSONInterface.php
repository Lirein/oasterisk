<?php
interface JSONInterface {
/**
 * Метод реализующий обработку JSON запроса со стороны фронтенда
 *
 * @param string $request Идентификатор запрашиваемых данных
 * @param object $request_data Объект запроса данных
 * @return object Объект результата
 */
    public function json(string $request, \stdClass $request_data);
}
?>