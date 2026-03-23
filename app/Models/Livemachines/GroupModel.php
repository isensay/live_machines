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
    protected $paramTypeId;

    protected static $eventFired = false; // Чтобы в профайлере не отображалось, что модель подключена несколько раз из за $this->fireModelEvent()

    /**
     * Подключение к БД
     */
    public function __construct(array $attributes = [], $connection = null, $paramTypeId = -1) {
        parent::__construct($attributes);
        
        // Используем переданное соединение или создаем новое
        $this->db = $connection ?? DB::connection('livemachines');
        $this->pdo = $this->db->getPdo();
        
        // Регистрируем в профайлере (только 1 раз)
        if (config('app.debug') && !self::$eventFired) {
            $this->fireModelEvent('retrieved', false);
            self::$eventFired = true;
        }

        $this->paramTypeId = $paramTypeId;
    }

    /**
     * Получение списка всех групп технических характеристик
     * Получение списка всех групп технических характеристик к которым привязан указанный технический параметр
     */
    public function get_list($isHaveParam = false, $search = "", $start = 0, $length = 0, $orderColumn = 'name', $orderDir = 'asc') {
        // Допустимые названия полей
        $columns = [
            0 => 'name'
        ];

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

            $sqlSort = " ORDER BY `{$orderField}` {$orderDir}";
        } else {
            $sqlSort = "";
        }

        if ($isHaveParam === true) {
            $sqlJoinParam  = "INNER JOIN `dirty_param` ON (`dirty_group_id` = `dirty_param_dirty_group_id` AND `dirty_group_dirty_type_id` = `dirty_param_dirty_type_id` AND `dirty_param_remove_user_id` = 0)";
        } else {
            $sqlJoinParam  = "";
        }

        // Лимит
        $sqlLimit = ($length > 0) ? "LIMIT {$start}, {$length}" : "";

        $sql = "
            SELECT
                SQL_CALC_FOUND_ROWS
                `dirty_group_id`   as `id`,
                `dirty_group_name` as `name`
            FROM `dirty_group`
            {$sqlJoinParam}
            WHERE 1
                {$sqlWhereSearch}
                AND `dirty_group_dirty_type_id`  = {$this->paramTypeId}
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

        /*
        $baseSql = "
            SELECT
                `dirty_group_id`   as `id`,
                `dirty_group_name` as `name`
            FROM `dirty_group`
                {$sqlJoinParam}
            WHERE 1
                {$sqlWhereParam}
                AND `dirty_group_dirty_type_id`  = {$this->paramTypeId}
                AND `dirty_group_remove_user_id` = 0
            GROUP BY
                `id`
            ORDER BY
                `name` ASC
        ";
        */

        return $this->db->select($baseSql);
    }

    /**
     * Получение информации о группе по ID
     */
    public function get_group_info_from_id($groupId) {
        $sql = "
            SELECT *
            FROM `dirty_group` 
            WHERE 1
                AND `dirty_group_id`             = ? 
                AND `dirty_group_dirty_type_id`  = {$this->paramTypeId}
                AND `dirty_group_remove_user_id` = 0
        ";
        return $this->db->selectOne($sql, [(int)$groupId]);
    }

    /**
     * Получение информации о группе по наименованию
     */
    public function get_group_info_from_name($groupName) {
        $sql = "
            SELECT *
            FROM `dirty_group` 
            WHERE 1
                AND `dirty_group_name`           = ? 
                AND `dirty_group_dirty_type_id`  = {$this->paramTypeId}
                AND `dirty_group_remove_user_id` = 0
        ";
        return $this->db->selectOne($sql, [(string)$groupName]);
    }

    /**
     * Создание новой группы
     */
    public function create($name) {
        try {
            // Проверяем, существует ли уже такая группа
            $exists = $this->get_group_info_from_name($name);
            
            if ($exists) return 'Группа с таким названием уже существует';
            
            // Создаем новую группу
            $sql = "
                INSERT INTO `dirty_group` 
                SET `dirty_group_name`          = ?, 
                    `dirty_group_dirty_type_id` = {$this->paramTypeId},
                    `dirty_group_add_user_id`   = ?,
                    `dirty_group_add_date`      = UNIX_TIMESTAMP()
            ";

            $this->db->insert($sql, [(string)$name, (int)auth()->id()]);
            
            return $this->pdo->lastInsertId();
        } catch (\Exception $e) {
            Log::error('Error creating group: ' . $e->getMessage());
            return $e->getMessage();
        }
    }
}