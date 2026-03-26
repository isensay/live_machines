<?php

/**
 * Модель для работы со справочником групп технических характеристик и комплектаций
 */

namespace App\Models\Livemachines;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManufModel extends Model
{
    protected $db;
    protected $pdo;
    protected static $eventFired = false; // Чтобы в профайлере не отображалось, что модель подключена несколько раз из за $this->fireModelEvent()

    /**
     * Получаем соединение к БД
     */
    public function __construct(array $attributes = [], $connection = null) {
        parent::__construct($attributes);
        
        // Используем переданное соединение или создаем новое
        $this->db = $connection ?? DB::connection('livemachines');
        $this->pdo = $this->db->getPdo();
        
        // Регистрируем в профайлере (только 1 раз)
        if (config('app.debug') && !self::$eventFired) {
            $this->fireModelEvent('retrieved', false);
            self::$eventFired = true;
        }
    }

    /**
     * Получение списка
     */
    public function get_list($search = "", $start = 0, $length = 0, $orderColumn = 0, $orderDir = 'asc') {
        // Допустимые названия полей
        $columns = [
            0 => 'name'
        ];

        // Условие по поиску
        $sqlWhereSearch  = "";
        if (!empty($search)) {
            $searchTerm     = $this->pdo->quote('%' . $search . '%');
            $sqlWhereSearch = "AND `dirty_manuf_name` LIKE {$searchTerm}";
        }

        // Сортировка
        if (isset($columns[$orderColumn])) {
            $orderField = $columns[$orderColumn];

            // Проверяем направление сортировки
            $orderDir = strtolower($orderDir);
            if (!in_array($orderDir, ['asc', 'desc'])) {
                $orderDir = 'asc'; // Значение по умолчанию
            }

            $sqlSort = "ORDER BY `{$orderField}` " . strtoupper($orderDir);
        } else {
            $sqlSort = "";
        }

        // Лимит
        $sqlLimit = ($length > 0) ? "LIMIT {$start}, {$length}" : "";

        $sql = "
            SELECT
                SQL_CALC_FOUND_ROWS
                `dirty_manuf_id`   as `id`,
                `dirty_manuf_name` as `name`,
                GROUP_CONCAT(DISTINCT `dirty_country_name` ORDER BY `dirty_country_name` ASC SEPARATOR '<BR>') as `country`,
                COUNT(DISTINCT `dirty_model_id`) as `models`,
                COUNT(DISTINCT `dirty_file_id`)  as `files`
            FROM `dirty_manuf`
            LEFT JOIN `dirty_manuf_file`    ON (`dirty_manuf_id`   = `dirty_manuf_file_dirty_manuf_id`)
            LEFT JOIN `dirty_file`          ON (`dirty_file_id`    = `dirty_manuf_file_dirty_file_id`)
            LEFT JOIN `dirty_model_file`    ON (`dirty_file_id`    = `dirty_model_file_dirty_file_id`     AND `dirty_file_remove_user_id`  = 0)
            LEFT JOIN `dirty_model`         ON (`dirty_model_id`   = `dirty_model_file_dirty_model_id`    AND `dirty_model_remove_user_id` = 0)
            LEFT JOIN `dirty_manuf_country` ON (`dirty_manuf_id`   = `dirty_manuf_country_dirty_manuf_id` AND `dirty_manuf_country_remove_user_id` = 0)
            LEFT JOIN `dirty_country`       ON (`dirty_country_id` = `dirty_manuf_country_dirty_country_id`)
            WHERE 1
                {$sqlWhereSearch}
                AND `dirty_manuf_remove_user_id` = 0
                AND (`dirty_file_id` IS NULL OR `dirty_file_remove_user_id` = 0)
            GROUP BY
                `id`
            {$sqlSort}
            {$sqlLimit}
        ";

        // Выполняем финальный запрос
        $data = $this->db->select($sql);

        // Получаем счетчики
        $filteredResult = $this->db->selectOne("SELECT FOUND_ROWS() as `total`");
        $totalRecords   = $filteredResult->total ?? 0;
        
        return [
            'data'  => $data,
            'total' => $totalRecords,
        ];
    }

    /**
     * Получение информации о записи по ID
     */
    public function get_info_from_id($id) {
        $sql = "
            SELECT
                `dirty_manuf_id`   as `id`,
                `dirty_manuf_name` as `name`,
                MAX(IF(`dirty_manuf_country_dirty_country_id` IS NOT NULL, `dirty_manuf_country_dirty_country_id`, 0)) as `country`
            FROM `dirty_manuf`
            LEFT JOIN `dirty_manuf_country` ON (`dirty_manuf_id` = `dirty_manuf_country_dirty_manuf_id` AND `dirty_manuf_country_remove_user_id` = 0)
            WHERE 1
                AND `dirty_manuf_id`             = ? 
                AND `dirty_manuf_remove_user_id` = 0
        ";
        return $this->db->selectOne($sql, [(int)$id]);
    }

    /**
     * Получение информации ID записи по наименованию
     */
    public function get_id_from_name($name) {
        try {
            $sql = "
                SELECT
                    `dirty_manuf_id`   as `id`,
                    `dirty_manuf_name` as `name`
                FROM `dirty_manuf` 
                WHERE 1
                    AND `dirty_manuf_name`           = ? 
                    AND `dirty_manuf_remove_user_id` = 0
            ";
            $result = $this->db->selectOne($sql, [(string)$name]);
            return ($result) ? (int)$result->id : 0;
        } catch (\Exception $e) {
            Log::error('Error creating manuf name: ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    /**
     * Обновление данных
     */
    public function create($name, $country) {
        try {
            // Проверяем, существует ли запись
            $id = $this->get_id_from_name($name);

            if ($id > 0) return 'Запись уже существует';

            $this->db->beginTransaction();

            // Создаем запись
            $this->db->insert("INSERT INTO `dirty_manuf` SET `dirty_manuf_name` = ?", [(string)$name]);

            $id = (int)$this->pdo->lastInsertId();

            // Привязываем к стране
            $this->db->update("
                INSERT INTO `dirty_manuf_country`
                SET
                    `dirty_manuf_country_dirty_manuf_id`   = " . $this->pdo->quote((int)$id) . ",
                    `dirty_manuf_country_dirty_country_id` = " . $this->pdo->quote((int)$country) . ",
                    `dirty_manuf_country_add_user_id`      = " . $this->pdo->quote(auth()->id()) . ",
                    `dirty_manuf_country_add_date`         = UNIX_TIMESTAMP()
            ");

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            Log::error('Create error: '.$e->getMessage());
            $this->db->rollBack();
            return $e->getMessage();
        }
    }

    /**
     * Обновление данных
     */
    public function set($id, $name, $country) {
        try {
            $this->db->beginTransaction();

            // Проверяем, существует ли запись с таким ID
            $info = $this->get_info_from_id($id);

            if (!$info) return 'Запись не найдена';

            // Обновляем написание
            $this->db->update("UPDATE `dirty_manuf` SET `dirty_manuf_name` = ? WHERE `dirty_manuf_id` = ?", [(string)$name, (int)$id]);
            
            // Привязываем к стране
            $this->db->update("
                INSERT INTO `dirty_manuf_country`
                SET
                    `dirty_manuf_country_dirty_manuf_id`   = " . $this->pdo->quote((int)$id) . ",
                    `dirty_manuf_country_dirty_country_id` = " . $this->pdo->quote((int)$country) . ",
                    `dirty_manuf_country_add_user_id`      = " . $this->pdo->quote(auth()->id()) . ",
                    `dirty_manuf_country_add_date`         = UNIX_TIMESTAMP()
                ON DUPLICATE KEY UPDATE
                    `dirty_manuf_country_dirty_country_id` = " . $this->pdo->quote((int)$country) . "
            ");

            // Удаляем если привязаны другие страны
            $this->db->update("
                UPDATE IGNORE `dirty_manuf_country`
                SET
                    `dirty_manuf_country_remove_user_id` = " . $this->pdo->quote(auth()->id()) . ",
                    `dirty_manuf_country_remove_date`    = UNIX_TIMESTAMP()
                WHERE 1
                    AND `dirty_manuf_country_dirty_manuf_id`   =  " . $this->pdo->quote((int)$id) . "
                    AND `dirty_manuf_country_dirty_country_id` <> " . $this->pdo->quote((int)$country) . "
                    AND `dirty_manuf_country_remove_user_id` = 0
            ");

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            Log::error('Update error: '.$e->getMessage());
            $this->db->rollBack();
            return $e->getMessage();
        }
    }

    /**
     * Удаление
     */
    public function remove($id) {
        try {
            // Начинаем транзакцию
            $this->db->beginTransaction();
            
            // Проверяем существует ли запись
            $info = $this->get_info_from_id($id);

            if (!$info) return 'Запись не найдена';

            // Удаляем
            $this->db->update("
                UPDATE `dirty_manuf`
                SET
                    `dirty_manuf_remove_user_id` = ?,
                    `dirty_manuf_remove_date`    = UNIX_TIMESTAMP()
                WHERE 1
                    AND `dirty_manuf_id` = ?
                    AND `dirty_manuf_remove_user_id` = 0
                ",
                [auth()->id(), (int)$id]
            );

            $this->db->commit();

            return true;

        } catch (\Exception $e) {
            $this->db->rollBack();
            Log::error('Delete error: '.$e->getMessage());
            return 'Ошибка: '.$e->getMessage();
        }
    }
}