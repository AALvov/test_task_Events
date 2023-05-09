<?php

/**
 * Класс для работы с событиями, в дальнейшем может быть расширен, например, для создания методов
 * изменения, удаления событий
 */
class Events
{
    private PDO $connection;

    /** В конструктор класса передаем
     * @param $host - хост БД
     * @param $port - порт БД
     * @param $dbname - название БД
     * @param $user - имя пользователя БД
     * @param $password - пароль пользователя БД
     */
    public function __construct(string $host, int $port, string $dbname, string $user, string $password)
    {
        $this->connection = new PDO("mysql:host=$host;dbname=$dbname;port=$port", $user, $password);
    }

    /**
     * Первый метод, позволяющий сохранить событий, входные аргументы:
     * @param $eventName - название события
     * @param $userStatus - статус пользователя
     */
    public function save_event(string $eventName, string $userStatus)
    {
        $timestamp = date('Y-m-d H:i:s'); // добавляем служебную информацию Дата события
        $userIP = $_SERVER['HTTP_CLIENT_IP']; // добавляем служебную информацию ip пользователя
        //формируем SQL-запрос
        $sql = "INSERT INTO events (event_name, user_ip, user_status, event_data) VALUES ('$eventName','$userIP', '$userStatus', '$timestamp')";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
    }

    /**
     * Второй метод для полечения аггрегированой информации, аргументы:
     * @param $eventName - название события
     * @param $dateStart - дата, начиная с которой будем выбирать события
     * @param $dateEnd - дата, до которой будем выбирать события
     * @param $aggregation - параметр аггрегации
     * @return false|string|string[] - возвращаем JSON, либо false, если не удалось его сформировать,
     * либо массив с ошибкой, если неверный параметр аггрегации
     */
    public function getStatistics(string $eventName, string $dateStart, string $dateEnd, string $aggregation): array|bool|string
    {
        //Формируем условие для запроса
        $conditions = [];
        if ($eventName != '') {
            $eventName = addslashes(quotemeta($eventName)); //название события можеть иметь спец символы, поэтому их нужно обработать
            $conditions[] = "event_name = '$eventName' ";
        }
        if ($dateStart != '') {
            $conditions[] = "event_date >= '$dateStart' ";
        }
        if ($dateEnd != '') {
            $conditions[] = "event_date <= '$dateEnd' ";
        }
        //Формируем запрос
        $query = "SELECT ";
        switch ($aggregation) {
            case 'event':
                $query .= "event_name, COUNT(*) as count from events ";
                break;
            case 'user':
                $query .= "user_ip, COUNT(*) as count from events ";
                break;
            case 'status':
                $query .= "user_status, COUNT(*) as count from events ";
                break;
            default:
                return ['error' => 'Invalid aggregation'];
        }
        if (count($conditions) > 0) {
            $query .= 'WHERE ' . implode(' AND ', $conditions);
        }
        $query .= "GROUP BY " . ($aggregation == 'event' ? 'event_name' : ($aggregation == 'user' ? 'user_ip' : 'user_status'));
        $stmt = $this->connection->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($result);
    }
}



