<?php

/**
 * Модель для работы со справочником модели
 */

namespace App\Models\Livemachines;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModelModel extends Model
{
    protected $db;
    protected $pdo;
    protected static $eventFired = false; // Чтобы в профайлере не отображалось что модель подключена несколько раз из за $this->fireModelEvent()

    /**
     * Подключение к БД
     */
    public function __construct(array $attributes = [], $connection = null) {
        parent::__construct($attributes);
        
        // Используем переданное соединение или создаем новое
        $this->db  = $connection ?? DB::connection('livemachines');
        $this->pdo = $this->db->getPdo();
        
        // Регистрируем в профайлере (только 1 раз)
        if (config('app.debug') && !self::$eventFired) {
            $this->fireModelEvent('retrieved', false);
            self::$eventFired = true;
        }
    }

    /**
     * Получение списка моделей
     */
    public function get_list($start, $length, $search, $orderColumn, $orderDir) {
        // Допустимые названия полей
        $columns = [
            0 => 'name'
        ];

        // Условие по поиску
        $sqlWhereSearch  = "";
        if (!empty($search)) {
            $searchTerm = $this->pdo->quote('%' . $search . '%');
            $sqlWhereSearch = "AND `dirty_model_name` LIKE {$searchTerm}";
        }

        // Сортировка
        if (isset($columns[$orderColumn])) {
            $orderField = $columns[$orderColumn];

            // Проверяем направление сортировки
            $orderDir = strtolower($orderDir);
            if (!in_array($orderDir, ['asc', 'desc'])) {
                $orderDir = 'asc'; // Значение по умолчанию
            }

            $sqlSort = " ORDER BY `{$orderField}` {$orderDir}";
        } else {
            $sqlSort = "";
        }
        $sql =
        "
        SELECT
            SQL_CALC_FOUND_ROWS
            `id`,
            `name`,
            IF(`manufs` > 0, `manufs`, '-') as `manufs`,
            IF(`files`  > 0, `files`,  '-') as `files`
        FROM
            (
                SELECT
                    `dirty_model_id`                 as `id`,
                    `dirty_model_name`               as `name`,
                    COUNT(DISTINCT `dirty_manuf_id`) as `manufs`,
                    COUNT(DISTINCT `dirty_file_id`)  as `files`
                FROM `dirty_model`
                    LEFT JOIN `dirty_model_file` ON (`dirty_model_id` = `dirty_model_file_dirty_model_id`)
                    LEFT JOIN `dirty_file`       ON (`dirty_file_id`  = `dirty_model_file_dirty_file_id`)
                    LEFT JOIN `dirty_manuf_file` ON (`dirty_file_id`  = `dirty_manuf_file_dirty_file_id`)
                    LEFT JOIN `dirty_manuf`      ON (`dirty_manuf_id` = `dirty_manuf_file_dirty_manuf_id`  AND `dirty_manuf_remove_user_id` = 0)
                WHERE 1
                    AND `dirty_model_remove_user_id` = 0
                    AND (dirty_file_id IS NULL OR `dirty_file_remove_user_id` = 0)
                    {$sqlWhereSearch}
                GROUP BY
                    `id`
            ) as `tmp`
        {$sqlSort}
        LIMIT {$start}, {$length}
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
}
