<?php

namespace App\Models\Livemachines;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TechParam extends Model
{
    protected $db;
    protected $pdo;

    /**
     * Подключение к БД
     */
    public function __construct()
    {
        $this->db  = DB::connection('livemachines');
        $this->pdo = $this->db->getPdo();
    }

    /**
     * Получение списка всех групп технических характеристик
     * Получение списка всех групп технических характеристик к которым привязан указанный технический параметр
     */
    public function get_groups($paramId = 0)
    {
        if ($paramId > 0)
        {
            $sqlWhereParam = "AND `dirty_param_dirty_param_name_id` = " . $this->pdo->quote((int)$paramId);
            $sqlJoinParam  = "INNER JOIN `dirty_param` ON (`dirty_group_id` = `dirty_param_dirty_group_id` AND `dirty_group_dirty_type_id` = `dirty_param_dirty_type_id` AND `dirty_param_remove_user_id` = 0)";
        } else {
            $sqlWhereParam = "";
            $sqlJoinParam  = "";
        }

        $baseSql = "
            SELECT
                `dirty_group_id`   as `id`,
                `dirty_group_name` as `name`
            FROM `dirty_group`
                {$sqlJoinParam}
            WHERE 1
                {$sqlWhereParam}
                AND `dirty_group_dirty_type_id`  = 1
                AND `dirty_group_remove_user_id` = 0
            GROUP BY
                `id`
            ORDER BY
                `name` ASC
        ";

        if ($paramId > 0) return $this->db->select($baseSql);

        return $this->db->select("(SELECT 'none' as `id`, 'Без группы' as `name`) UNION ({$baseSql})");
    }

    /**
     * Получение списка технических характеристик
     */
    public function get_list(
        $groupId,
        $start,
        $length,
        $search,
        $orderColumn,
        $orderDir
    )
    {
        // Определяем маппинг колонок прямо в модели
        $columns = [
            0 => 'paramName',
            1 => 'groups',
            2 => 'files',
        ];
        
        // Условие по группе
        if ($groupId == 'none') {
            $sqlWhereGroup = "AND `dirty_param_dirty_group_id` = 0";
        } else {
            $groupIdInt = (int)$groupId;
            $sqlWhereGroup = ($groupIdInt > 0) ? "AND `dirty_param_dirty_group_id` = ".$this->pdo->quote((int)$groupIdInt) : "";
        }

        // Условие по поиску
        $sqlWhereSearch = "";
        if (!empty($search)) {
            $minFulltextLength = 3; // Минимальная длина для FULLTEXT (По умолчанию MySQL имеет параметр ft_min_word_len = 4 (для MyISAM) или innodb_ft_min_token_size = 3)
            
            // Удаляем пробелы и считаем длину первого слова
            $firstWord  = trim(explode(' ', $search)[0]);
            $wordLength = mb_strlen($firstWord);
            
            if ($wordLength >= $minFulltextLength) {
                $searchTerm = addcslashes($search, '+-<>()~*"');
                $sqlWhereSearch = "AND MATCH(`dirty_param_name_value`) AGAINST('{$searchTerm}' IN BOOLEAN MODE)";
            } else {
                $searchTerm = $this->pdo->quote('%' . $search . '%');
                $sqlWhereSearch = "AND `dirty_param_name_value` LIKE {$searchTerm}";
            }
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
        
        $sql = "
            SELECT
                SQL_CALC_FOUND_ROWS
                `dirty_param_name_id`    as `paramNameId`,
                `dirty_param_name_value` as `paramName`,
                GROUP_CONCAT(DISTINCT IF(`dirty_group_name` IS NOT NULL, `dirty_group_name`, '-') SEPARATOR '<br><br>') as `groups`,
                GROUP_CONCAT(DISTINCT `dirty_file_name`  SEPARATOR '<br>') as `files`
            FROM `dirty_param_name`
                LEFT JOIN `dirty_param` ON (1
                    AND `dirty_param_name_id`        = `dirty_param_dirty_param_name_id` 
                    AND `dirty_param_dirty_type_id`  = `dirty_param_name_dirty_type_id` 
                    AND `dirty_param_remove_user_id` = 0
                )
                INNER JOIN `dirty_file`  ON (`dirty_file_id` = `dirty_param_dirty_file_id` AND `dirty_file_remove_user_id` = 0)
                LEFT JOIN `dirty_group` ON (1
                    AND `dirty_param_dirty_group_id` = `dirty_group_id`
                    AND `dirty_group_dirty_type_id`  = `dirty_param_name_dirty_type_id`
                )
            WHERE 1
                AND `dirty_param_name_dirty_type_id` = 1
                {$sqlWhereGroup}
                {$sqlWhereSearch}
            GROUP BY `paramNameId`
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
     * Получение информации о технической характеристике по ID
     */
    public function get_info_from_id($paramId) {
        // Получаем основную информацию о параметре
        $sql = "
            SELECT
                `dirty_param_name_id`            as `id`,
                `dirty_param_name_value`         as `name`,
                `dirty_param_name_dirty_type_id` as `type_id`
            FROM `dirty_param_name`
            WHERE 1
                AND `dirty_param_name_id`            = ?
                AND `dirty_param_name_dirty_type_id` = 1
        ";
        
        return $this->db->selectOne($sql, [(int)$paramId]);
    }

    /**
     * Получение информации о технической характеристике по ID
     */
    public function get_info_from_name($name) {
        // Получаем основную информацию о параметре
        $sql = "
            SELECT
                `dirty_param_name_id`            as `id`,
                `dirty_param_name_value`         as `name`,
                `dirty_param_name_dirty_type_id` as `type_id`
            FROM `dirty_param_name`
            WHERE 1
                AND `dirty_param_name_value`         = ?
                AND `dirty_param_name_dirty_type_id` = 1
        ";
        
        return $this->db->selectOne($sql, [(string)$name]);
    }

    /**
     * Получение списка всех единиц измерения
     */
    public function get_units() {
        $unitsSql = "
            (
                SELECT
                    0          as `id`,
                    'Выберите' as `name`,
                    'text'     as `type`
            )
            UNION
            (
                SELECT
                    `dirty_param_unit_id`    as `id`,
                    `dirty_param_unit_value` as `name`,
                    'text'                   as `type`
                FROM `dirty_param_unit`
                WHERE 1
                    AND `dirty_param_unit_dirty_type_id` = 1
                GROUP BY
                    `id`
                ORDER BY
                    `name` ASC
            )
        ";
    
        return $this->db->select($unitsSql);
    }

    /**
     * Получение списка всех единиц измерения и значений для указаноого параметра
     */
    public function get_units_and_values($paramId) {
        $valuesSql = "
            SELECT
                `dirty_param_unit_id`      as `unit_id`,
                `dirty_param_unit_value`  as `unit_name`,
                `dirty_param_value_id`    as `value_id`,
                `dirty_param_value_value` as `value`,
                '' as `value_text`,
                0  as `file_id`
            FROM `dirty_param`
                INNER JOIN `dirty_param_unit`  ON (`dirty_param_unit_id`  = `dirty_param_dirty_param_unit_id`  AND `dirty_param_unit_dirty_type_id`  = `dirty_param_dirty_type_id`)
                LEFT  JOIN `dirty_param_value` ON (`dirty_param_value_id` = `dirty_param_dirty_param_value_id` AND `dirty_param_value_dirty_type_id` = `dirty_param_dirty_type_id`)
            WHERE 1
                AND `dirty_param_dirty_param_name_id` = ?
                AND `dirty_param_dirty_type_id`       = 1
                AND `dirty_param_remove_date`         = 0
            GROUP BY
                `unit_id`,
                `value_id`
            ORDER BY
                `unit_name` ASC,
                `value` ASC
        ";
        
        return $this->db->select($valuesSql, [(int)$paramId]);
    }

    /**
     * Обновление данных
     */
    public function set($name, $fromParamNameId, $toParamNameId, $units)
    {
        $this->db->beginTransaction();

        // Перепривязываем все записи в таблице "dirty_param" к новому пармметру
        if ($fromParamNameId == $toParamNameId) {
            $this->db->update("UPDATE `dirty_param_name` SET `dirty_param_name_value` = ? WHERE `dirty_param_name_id` = ? AND `dirty_param_name_dirty_type_id` = 1 LIMIT 1", [(string)$name, (int)$fromParamNameId]);
        }
        elseif ($toParamNameId > 0) {}
        else {
            $this->db->update("INSERT INTO `dirty_param_name` SET `dirty_param_name_value` = ?, `dirty_param_name_dirty_type_id` = 1", [(string)$name]);
            $toParamNameId = $this->pdo->lastInsertId();
        }

        if ($toParamNameId > 0 && $fromParamNameId <> $toParamNameId) {
            $this->db->update("UPDATE `dirty_param` SET `dirty_param_dirty_param_name_id` = ? WHERE `dirty_param_dirty_param_name_id` = ? AND `dirty_param_dirty_type_id` = 1", [(int)$toParamNameId, (int)$fromParamNameId]);
        }

        //$paramNameId = $this->pdo->lastInsertId();
        $this->db->commit();
        //$dbLm->rollBack();
    }
}