<?php

/**
 * Модель для работы со справочником групп технических характеристик и комплектаций
 * $paramTypeId: 1 - технические характеристики; 2 - комплектации
 */

namespace App\Models\Livemachines;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GroupModel extends Model
{
    protected $db;
    protected $pdo;

    protected static $eventFired = false; // Чтобы в профайлере не отображалось, что модель подключена несколько раз из за $this->fireModelEvent()

    /**
     * Подключение к БД
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
     * Получение списка всех групп технических характеристик
     * Получение списка всех групп технических характеристик к которым привязан указанный технический параметр
     */
    public function get_list($typeId = 0, $isHaveParam = false, $search = "", $start = 0, $length = 0, $orderColumn = 'name', $orderDir = 'asc') {
        // Допустимые названия полей
        $columns = [
            0 => 'name'
        ];

        // Тип
        $sqlWhereType = (in_array((int)$typeId, [1,2])) ? "AND `dirty_group_dirty_type_id` = ".(int)$typeId : "";

        // Условие по поиску
        $sqlWhereSearch  = "";
        if (!empty($search)) {
            $searchTerm     = $this->pdo->quote('%' . $search . '%');
            $sqlWhereSearch = "AND `dirty_group_name` LIKE {$searchTerm}";
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

        // Обязательная привязка к характеристикам
        if ($isHaveParam === true) {
            $sqlJoinParam = "INNER";
        } else {
            $sqlJoinParam = "LEFT";
        }

        // Лимит
        $sqlLimit = ($length > 0) ? "LIMIT {$start}, {$length}" : "";

        $sql = "
            SELECT
                SQL_CALC_FOUND_ROWS
                `dirty_group_id`   as `id`,
                `dirty_group_name` as `name`,
                COUNT(DISTINCT `dirty_param_dirty_param_name_id`) as `params`,
                COUNT(DISTINCT `dirty_file_id`) as `files`
            FROM `dirty_group`
            {$sqlJoinParam} JOIN `dirty_param` ON (`dirty_group_id` = `dirty_param_dirty_group_id` AND `dirty_group_dirty_type_id` = `dirty_param_dirty_type_id` AND `dirty_param_remove_user_id` = 0)
            LEFT JOIN `dirty_file` ON (`dirty_file_id` = `dirty_param_dirty_file_id` AND `dirty_param_remove_user_id` = 0)
            WHERE 1
                {$sqlWhereSearch}
                {$sqlWhereType}
                AND `dirty_group_remove_user_id` = 0
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
     * Получение информации о группе по ID
     */
    public function get_info_from_id($groupId) {
        $sql = "
            SELECT
                `dirty_group_id`            as `id`,
                `dirty_group_name`          as `name`,
                `dirty_group_dirty_type_id` as `typeId`
            FROM `dirty_group` 
            WHERE 1
                AND `dirty_group_id`             = ? 
                AND `dirty_group_remove_user_id` = 0
        ";
        return $this->db->selectOne($sql, [(int)$groupId]);
    }

    /**
     * Получение информации о группе по наименованию
     */
    public function get_id_from_name($typeId, $groupName, $create = false) {
        // Тип
        $typeId = (in_array((int)$typeId, [1,2])) ? (int)$typeId : 0;

        // Ищем группу в БД
        $sql = "
            SELECT
                `dirty_group_id`   as `id`,
                `dirty_group_name` as `name`
            FROM `dirty_group` 
            WHERE 1
                AND `dirty_group_dirty_type_id`  = ?
                AND `dirty_group_name`           = ? 
                AND `dirty_group_remove_user_id` = 0
        ";
        $result = $this->db->selectOne($sql, [(int)$typeId, (string)$groupName]);

        if (!$result && !$create) return 'Запись не найдена';

        // Если требуется создать или обновить (например изменить регистр)
        try {
            // Приводим к верхнему регистру
            $groupName = mb_strtoupper($groupName);

            // Начинаем транзакцию
            $this->db->beginTransaction();

            // Обновляем регистр
            if ($result) {
                $sql = "
                    UPDATE `dirty_group`
                    SET
                        `dirty_group_name` = ?
                    WHERE
                        `dirty_group_id`   = ?
                ";
                $this->db->update($sql, [(string)$groupName, $result->id]);
                $groupId = (int)$result->id;
            // Создаем
            } else {
                $sql = "
                    INSERT INTO `dirty_group`
                    SET
                        `dirty_group_name`          = " . $this->pdo->quote((string)$groupName) . ",
                        `dirty_group_dirty_type_id` = " . $this->pdo->quote((int)$typeId) . ",
                        `dirty_group_add_user_id`   = " . $this->pdo->quote((int)auth()->id()) . ",
                        `dirty_group_add_date`      = UNIX_TIMESTAMP()
                ";
                $this->db->insert($sql);
                $groupId = $this->pdo->lastInsertId();
            }

            $this->db->commit();
            return $groupId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            Log::error('Error creating group name: ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    /**
     * Обновление данных
     */
    public function edit($groupId, $groupName) {
        try {
            $this->db->beginTransaction();

            $groupName = mb_strtoupper($groupName);

            $groupInfo = $this->get_info_from_id($groupId);

            if (!$groupInfo) return 'Группа не найдена';
            
            $this->db->update("UPDATE `dirty_group` SET `dirty_group_name` = ? WHERE `dirty_group_id` = ?", [(string)$groupName, (int)$groupInfo->id]);

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
    public function remove($groupId) {
        try {
            // Начинаем транзакцию
            $this->db->beginTransaction();
            
            // Проверяем существует ли запись
            $record = $this->get_info_from_id($groupId);

            if (!$record) return 'Запись не найдена';

            // Отвязываем все параметры от группы
            $unbind = $this->db->update("
                UPDATE `dirty_param`
                SET
                    `dirty_param_dirty_group_id` = 0
                WHERE 1
                    AND `dirty_param_dirty_group_id` = ?
                    AND `dirty_param_dirty_type_id`  = ?
                ",
                [(int)$groupId, (int)$record->typeId]
            );
            
            // Удаляем группу
            $delete = $this->db->update("
                UPDATE `dirty_group`
                SET
                    `dirty_group_remove_user_id` = ?,
                    `dirty_group_remove_date`    = UNIX_TIMESTAMP()
                WHERE
                    `dirty_group_id` = ?",
                [auth()->id(), (int)$groupId]
            );
            
            if ($delete) {
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