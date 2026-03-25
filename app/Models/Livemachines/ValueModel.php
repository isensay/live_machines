<?php

/**
 * Модель для работы со справочником групп технических характеристик и комплектаций
 * $paramTypeId: 1 - технические характеристики; 2 - комплектации
 */

namespace App\Models\Livemachines;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValueModel extends Model
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
    public function get_list($search = "", $start = 0, $length = 0, $orderColumn = 'name', $orderDir = 'asc') {
        // Допустимые названия полей
        $columns = [
            0 => 'name'
        ];

        // Условие по поиску
        $sqlWhereSearch  = "";
        if (!empty($search)) {
            $searchTerm     = $this->pdo->quote('%' . $search . '%');
            $sqlWhereSearch = "AND `dirty_param_value_value` LIKE {$searchTerm}";
        }

        // Сортировка
        if (isset($columns[$orderColumn])) {
            $orderField = $columns[$orderColumn];

            // Проверяем направление сортировки
            $orderDir = strtolower($orderDir);
            if (!in_array($orderDir, ['asc', 'desc'])) {
                $orderDir = 'asc'; // Значение по умолчанию
            }

            $sqlSort = "ORDER BY `{$orderField}` {$orderDir}";
        } else {
            $sqlSort = "";
        }

        // Лимит
        $sqlLimit = ($length > 0) ? "LIMIT {$start}, {$length}" : "";

        $sql = "
            SELECT
                SQL_CALC_FOUND_ROWS
                `dirty_param_value_id`    as `id`,
                `dirty_param_value_value` as `name`,
                COUNT(DISTINCT `dirty_param_dirty_param_name_id`) as `params`,
                GROUP_CONCAT(DISTINCT `dirty_file_name` SEPARATOR '<BR>') as `files`
            FROM `dirty_param_value`
            LEFT JOIN `dirty_param` ON (`dirty_param_value_id` = `dirty_param_dirty_param_value_id` AND `dirty_param_value_dirty_type_id` = `dirty_param_dirty_type_id` AND `dirty_param_remove_user_id` = 0)
            LEFT JOIN `dirty_file` ON (`dirty_file_id` = `dirty_param_dirty_file_id`)
            WHERE 1
                {$sqlWhereSearch}
                AND `dirty_param_value_dirty_type_id`  = 1
                AND `dirty_param_value_remove_user_id` = 0
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
                `dirty_param_value_id`    as `id`,
                `dirty_param_value_value` as `name`
            FROM `dirty_param_value` 
            WHERE 1
                AND `dirty_param_value_id`             = ? 
                AND `dirty_param_value_dirty_type_id`  = 1
                AND `dirty_param_value_remove_user_id` = 0
        ";
        return $this->db->selectOne($sql, [(int)$id]);
    }

    /**
     * Получение информации ID записи по наименованию
     */
    public function get_id_from_name($name, $create = false) {
        // Ищем группу в БД
        $sql = "
            SELECT
                `dirty_param_value_id`    as `id`,
                `dirty_param_value_value` as `name`
            FROM `dirty_param_value` 
            WHERE 1
                AND `dirty_param_value_value`          = ? 
                AND `dirty_param_value_dirty_type_id`  = 1
                AND `dirty_param_value_remove_user_id` = 0
        ";
        $result = $this->db->selectOne($sql, [(string)$name]);

        if (!$result && !$create) return 'Запись не найдена';

        // Если требуется создать или обновить (например изменить регистр)
        try {
            // Начинаем транзакцию
            $this->db->beginTransaction();

            // Обновляем регистр
            if ($result) {
                $sql = "
                    UPDATE `dirty_param_value`
                    SET
                        `dirty_param_value_value` = ?
                    WHERE
                        `dirty_param_value_id`    = ?
                ";
                $this->db->update($sql, [(string)$name, $result->id]);
                $id = (int)$result->id;
            // Создаем
            } else {
                $sql = "
                    INSERT INTO `dirty_param_value`
                    SET
                        `dirty_param_value_value`         = " . $this->pdo->quote((string)$name) . ",
                        `dirty_param_value_dirty_type_id` = 1,
                        `dirty_param_value_add_user_id`   = " . $this->pdo->quote((int)auth()->id()) . ",
                        `dirty_param_value_add_date`      = UNIX_TIMESTAMP()
                ";
                $this->db->insert($sql);
                $id = $this->pdo->lastInsertId();
            }

            $this->db->commit();
            return $id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            Log::error('Error creating group name: ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    /**
     * Обновление данных
     */
    public function set($id, $name) {
        try {
            $this->db->beginTransaction();

            // Проверяем, существует ли запись с таким ID
            $info = $this->get_info_from_id($id);

            if (!$info) return 'Группа не найдена';

            // Проверяем, отличается ли наименование
            $dbId = $this->get_id_from_name($name);

            if (is_string($dbId) || $id == $dbId) {
                // Обновляем написание
                $this->db->update("UPDATE `dirty_param_value` SET `dirty_param_value_value` = ? WHERE `dirty_param_value_id` = ?", [(string)$name, (int)$info->id]);
            } else {
                // Перепривязываем
                $this->db->update("UPDATE `dirty_param` SET `dirty_param_dirty_param_value_id` = ? WHERE `dirty_param_dirty_param_value_id` = ?", [(int)$dbId, (int)$info->id]);

                // Удаляем
                $this->db->update("
                    UPDATE `dirty_param_value`
                    SET
                        `dirty_param_value_remove_user_id` = ?,
                        `dirty_param_value_remove_date`    = UNIX_TIMESTAMP()
                    WHERE
                        `dirty_param_value_id` = ?
                    ",
                    [auth()->id(), (int)$id]
                );
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
     * Удаление
     */
    public function remove($id) {
        try {
            // Начинаем транзакцию
            $this->db->beginTransaction();
            
            // Проверяем существует ли запись
            $info = $this->get_info_from_id($id);

            if (!$info) return 'Запись не найдена';

            // Получаем список файлов
            $fileIds = $this->db->selectOne("SELECT GROUP_CONCAT(DISTINCT `dirty_param_dirty_file_id`) as `ids` FROM `dirty_param` WHERE `dirty_param_dirty_param_value_id` = ? AND `dirty_param_remove_user_id` = 0", [(int)$id])->ids ?? "";

            // Удаляем файлы
            if ($fileIds <> "") {
                $this->db->update("
                    UPDATE `dirty_file`
                    SET
                        `dirty_file_remove_user_id` = ?,
                        `dirty_file_remove_date`    = UNIX_TIMESTAMP()
                    WHERE 1
                        AND `dirty_file_id` IN ({$fileIds})
                        AND `dirty_file_remove_user_id` = 0
                    ",
                    [auth()->id()]
                );
            }

            $this->db->commit();

            return true;

            // Отвязываем все параметры
            $this->db->update("
                UPDATE `dirty_param`
                SET
                    `dirty_param_remove_user_id` = ?,
                    `dirty_param_remove_date`    = UNIX_TIMESTAMP()
                WHERE 1
                    AND `dirty_param_dirty_param_value_id` = ?
                    AND `dirty_param_dirty_type_id`       = 1
                ",
                [auth()->id(), (int)$id]
            );
            
            // Удаляем запись
            $update = $this->db->update("
                UPDATE `dirty_param_value`
                SET
                    `dirty_param_value_remove_user_id` = ?,
                    `dirty_param_value_remove_date`    = UNIX_TIMESTAMP()
                WHERE
                    `dirty_param_value_id` = ?",
                [auth()->id(), (int)$id]
            );
            
            // Завершаем транзакцию
            if ($update) {
                $this->db->commit();
                return true;
            }
            
            // Откатываем транзакцию
            $this->db->rollBack();

            return 'Ошибка при удалении';
        } catch (\Exception $e) {
            $this->db->rollBack();
            Log::error('Delete error: '.$e->getMessage());
            return 'Ошибка: '.$e->getMessage();
        }
    }
}