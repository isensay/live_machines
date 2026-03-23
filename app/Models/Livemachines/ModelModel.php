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
    public function get_list($search, $start, $length, $orderColumn, $orderDir) {
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

    /**
     * Получение информации по ID
     */
    public function get_info_from_id($modelId) {
        $sql =
        "
        SELECT
            `dirty_model_id`   as `id`,
            `dirty_model_name` as `name`
        FROM `dirty_model`
        WHERE 1
            AND `dirty_model_id` = ?
            AND `dirty_model_remove_user_id` = 0
        ";
        return $this->db->selectOne($sql, [(int)$modelId]);
    }

    /**
     * Получение информации ID по наименованию
     * при $create = true: создается запись, если ее нет
     */
    public function get_id_from_name($modelName, $create = false) {
        try {
            $sql =
            "
            SELECT
                `dirty_model_id` as `id`
            FROM `dirty_model`
            WHERE 1
                AND `dirty_model_name` = ?
                AND `dirty_model_remove_user_id` = 0
            ";
            $result  = $this->db->selectOne($sql, [(string)$modelName]);
            $modelId = ($result) ? $result->id : 0;

            if ($create == true) {
                // Обновляем, если допустим требуется поменять регистр
                if ($modelId > 0) {
                    $this->db->update("UPDATE `dirty_model` SET `dirty_model_name` = ? WHERE `dirty_model_id` = ?", [(string)$modelName, (int)$modelId]);
                } else {
                    $this->db->insert("INSERT INTO `dirty_model` SET `dirty_model_name` = ?, `dirty_model_add_user_id` = ?, `dirty_model_add_date` = UNIX_TIMESTAMP()", [(string)$modelName, (int)auth()->id()]);
                    $modelId = $this->pdo->lastInsertId();
                }
            } elseif($modelId == 0) {
                return 'Запись не найдена';
            } 
            
            return $modelId;
        } catch (\Exception $e) {
            Log::error('Delete error: '.$e->getMessage());
            return $e->getMessage();
        }
    }

    /**
     * Создание новой записи
     */
    public function create($modelName) {
        try {
            $this->db->beginTransaction();

            $modelId = $this->get_id_from_name($modelName, true);

            if (is_numeric($modelId)) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return $modelId;
            }
        } catch (\Exception $e) {
            Log::error('Create error: '.$e->getMessage());
            $this->db->rollBack();
            return $e->getMessage();
        }
    }

    /**
     * Обновление данных
     */
    public function edit($modelId, $modelName) {
        try {
            $this->db->beginTransaction();

            $dbModelId = $this->get_id_from_name($modelName, true);
        
            if (!is_numeric($dbModelId)) return $dbModelId;

            
            if ($modelId > 0 && $modelId <> $dbModelId) {
                // Перепривязываем связку модели с файлами
                $this->db->update("UPDATE `dirty_model_file` SET `dirty_model_file_dirty_model_id` = ? WHERE `dirty_model_file_dirty_model_id` = ?", [(int)$dbModelId, (int)$modelId]);

                // Удаляем модель из справочника
                $this->db->update("UPDATE `dirty_model` SET `dirty_model_remove_user_id` = ?, `dirty_model_remove_date` = UNIX_TIMESTAMP()  WHERE `dirty_model_id` = ?", [auth()->id(), (int)$modelId]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            Log::error('Update error: '.$e->getMessage());
            $this->db->rollBack();
            return $e->getMessage();
        }
    }

    /**
     * Удаление параметра
     */
    public function remove($modelId) {
        try {
            // Начинаем транзакцию
            $this->db->beginTransaction();
            
            // Проверяем существует ли запись
            $record = $this->get_info_from_id($modelId);
            
            if (!$record) return 'Запись не найдена';

            $updated = $this->db->update("
                UPDATE `dirty_model`
                SET
                    `dirty_model_remove_user_id` = ?,
                    `dirty_model_remove_date`    = UNIX_TIMESTAMP()
                WHERE
                    `dirty_model_id` = ?",
                [auth()->id(), (int)$modelId]
            );
            
            if ($updated)
            {
                $this->db->commit();
                return true;
            }
            
            $this->db->rollBack();

            return 'Ошибка при удалении';
        } catch (\Exception $e) {
            Log::error('Delete error: '.$e->getMessage());

            return 'Ошибка: '.$e->getMessage();
        }
    }
}
