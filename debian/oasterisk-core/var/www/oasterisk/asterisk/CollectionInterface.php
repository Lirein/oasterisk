<?php

/**
 * Интерфейс реализующий коллекцию субьектов
 */
interface Collection extends Iterator {

    /**
     * Конструктор без аргументов - инициализирует коллекцию объектов
     */
    public function __construct();

    /**
     * Создает новый элемент коллекции
     *
     * @param \Subject $subject Субъект который необходимо добавить в коллекцию. Контроль типов обязателен.
     * @return bool Возвращает истину если удалось добавить субьект в коллекцию.
     */
    public static function add(\Subject &$subject);

    /**
     * Удаляет субьект из коллекции
     *
     * @param \Subject $subject Субьект который необходимо удалить из коллекции.
     * @return bool Возвращает истину если субьект удалось удалить из коллекции.
     */
    public static function remove(\Subject &$subject);

    /**
     * Метод возвращает количество элементов коллекции
     *
     * @return integer Возвращает количество элементов коллекции
     */
    public function count();

}
?>