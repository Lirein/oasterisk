<?php
interface AGIInterface {
/**
 * Метод реализующий функцию AGI со стороны техплатформы
 *
 * @param object $request_data Данные запроса
 * @return integer Возвращает код возврата сценария
 */
    public function agi(\stdClass $request_data);
}
?>