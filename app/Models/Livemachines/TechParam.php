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

        return $this->db->select("(SELECT 'none' as `id`, '- Без группы -' as `name`) UNION (SELECT 'groupandno' as `id`, '- С группой и без -' as `name`) UNION ({$baseSql})");
    }

    /**
     * Получение списка всех файлов (источников)
     */
    public function get_files()
    {
        $sql = "
            SELECT
                `dirty_file_id`   as `id`,
                `dirty_file_name` as `name`
            FROM `dirty_file`
            WHERE 1
                AND `dirty_file_remove_user_id` = 0
            ORDER BY
                `name` ASC
        ";
        
        return $this->db->select($sql);
    }

    /**
     * Получение списка файлов для конкретного параметра
     */
    public function get_param_files($paramId)
    {
        $sql = "
            SELECT DISTINCT
                `dirty_file_id`   as `id`,
                `dirty_file_name` as `name`
            FROM `dirty_param`
                INNER JOIN `dirty_file` ON (`dirty_file_id` = `dirty_param_dirty_file_id` AND `dirty_file_remove_user_id` = 0)
            WHERE 1
                AND `dirty_param_dirty_param_name_id` = ?
                AND `dirty_param_dirty_type_id` = 1
                AND `dirty_param_remove_user_id` = 0
            ORDER BY
                `dirty_file_name` ASC
        ";
        
        return $this->db->select($sql, [(int)$paramId]);
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
        $sqlWhereGroup = "";
        $sqlHaving     = "";
        if ($groupId == 'none') {
            $sqlWhereGroup = "AND `dirty_param_dirty_group_id` = 0";
        } elseif ($groupId == 'check') {
            $sqlWhereGroup = "AND `dirty_param_checked` = 1";
        } elseif ($groupId == 'nocheck') {
            $sqlWhereGroup = "AND `dirty_param_checked` = 0";
        } elseif ($groupId == 'groupandno') {
            $sqlHaving = "HAVING `groupMinId` = 0 AND `groupMaxId` > 0";
        } else {
            $groupIdInt = (int)$groupId;
            $sqlWhereGroup = ($groupIdInt > 0) ? "AND `dirty_param_dirty_group_id` = ".$this->pdo->quote((int)$groupIdInt) : "";
        }

        // Условие по поиску
        $sqlWhereSearch  = "";
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
                GROUP_CONCAT(DISTINCT `dirty_file_name`  SEPARATOR '<br>') as `files`,
                MIN(`dirty_param_dirty_group_id`) as `groupMinId`,
                MAX(`dirty_param_dirty_group_id`) as `groupMaxId`
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
            {$sqlHaving}
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
     * Получение информации о технической характеристике по имени
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
     * Получение списка всех единиц измерения и значений для указанного параметра
     */
    public function get_units_and_values($paramId) {
        $valuesSql = "
            SELECT
                `dirty_param_unit_id`      as `unit_id`,
                `dirty_param_unit_value`  as `unit_name`,
                `dirty_param_value_id`    as `value_id`,
                `dirty_param_value_value` as `value`,
                '' as `value_text`,
                `dirty_param_dirty_file_id` as `file_id`,
                `dirty_file_name` as `file_name`
            FROM `dirty_param`
                LEFT JOIN `dirty_param_unit`  ON (`dirty_param_unit_id`  = `dirty_param_dirty_param_unit_id`  AND `dirty_param_unit_dirty_type_id`  = `dirty_param_dirty_type_id`)
                LEFT JOIN `dirty_param_value` ON (`dirty_param_value_id` = `dirty_param_dirty_param_value_id` AND `dirty_param_value_dirty_type_id` = `dirty_param_dirty_type_id`)
                LEFT JOIN `dirty_file` ON (`dirty_file_id` = `dirty_param_dirty_file_id` AND `dirty_file_remove_user_id` = 0)
            WHERE 1
                AND `dirty_param_dirty_param_name_id` = ?
                AND `dirty_param_dirty_type_id`       = 1
                AND `dirty_param_remove_user_id`      = 0
            GROUP BY
                `unit_id`,
                `value_id`,
                `file_id`
            ORDER BY
                `unit_name` ASC,
                `value` ASC
        ";
        
        return $this->db->select($valuesSql, [(int)$paramId]);
    }

    /**
     * Получение привязок параметра к группам с информацией о файлах
     */
    public function get_param_group_links($paramId) {
        $sql = "
            SELECT
                `dirty_param_dirty_group_id` as `group_id`,
                `dirty_group_name` as `group_name`,
                `dirty_param_dirty_file_id` as `file_id`,
                `dirty_file_name` as `file_name`
            FROM `dirty_param`
                LEFT JOIN `dirty_group` ON (`dirty_group_id` = `dirty_param_dirty_group_id` AND `dirty_group_dirty_type_id` = `dirty_param_dirty_type_id` AND `dirty_group_remove_user_id` = 0)
                INNER JOIN `dirty_file` ON (`dirty_file_id` = `dirty_param_dirty_file_id` AND `dirty_file_remove_user_id` = 0)
            WHERE 1
                AND `dirty_param_dirty_param_name_id` = ?
                AND `dirty_param_dirty_type_id` = 1
                AND `dirty_param_remove_user_id` = 0
                AND `dirty_param_dirty_group_id` > 0
            GROUP BY
                `dirty_param_dirty_group_id`,
                `dirty_param_dirty_file_id`
            ORDER BY
                `group_name` ASC,
                `file_name` ASC
        ";
        
        return $this->db->select($sql, [(int)$paramId]);
    }

    /**
     * Обновление данных
     */
    public function set($name, $fromParamNameId, $toParamNameId, $groupLinks, $values, $additional, $checked)
    {
        try {
            $this->db->beginTransaction();
            $currentUserId = auth()->id();

            // Наименование параметра (начало)
            if ($fromParamNameId == $toParamNameId) {
                $this->db->update("UPDATE `dirty_param_name` SET `dirty_param_name_value` = ? WHERE `dirty_param_name_id` = ? AND `dirty_param_name_dirty_type_id` = 1 LIMIT 1", [(string)$name, (int)$fromParamNameId]);
            }
            elseif ($toParamNameId > 0) {
                // Перепривязываем существующие записи к другому параметру
                $this->db->update("UPDATE `dirty_param` SET `dirty_param_dirty_param_name_id` = ? WHERE `dirty_param_dirty_param_name_id` = ? AND `dirty_param_dirty_type_id` = 1 AND `dirty_param_remove_user_id` = 0", [(int)$toParamNameId, (int)$fromParamNameId]);
                
                // Помечаем старый параметр как удаленный
                $this->db->update("UPDATE `dirty_param_name` SET `dirty_param_name_remove_user_id` = ?, `dirty_param_name_remove_date` = UNIX_TIMESTAMP() WHERE `dirty_param_name_id` = ? AND `dirty_param_name_dirty_type_id` = 1", [$currentUserId, (int)$fromParamNameId]);
            }
            else {
                $this->db->insert("INSERT INTO `dirty_param_name` SET `dirty_param_name_value` = ?, `dirty_param_name_dirty_type_id` = 1, `dirty_param_name_add_user_id` = ?, `dirty_param_name_add_date` = UNIX_TIMESTAMP()", [(string)$name, $currentUserId]);
                $toParamNameId = $this->pdo->lastInsertId();
                
                // Перепривязываем существующие записи к новому параметру
                if ($fromParamNameId > 0) {
                    $this->db->update("UPDATE `dirty_param` SET `dirty_param_dirty_param_name_id` = ? WHERE `dirty_param_dirty_param_name_id` = ? AND `dirty_param_dirty_type_id` = 1 AND `dirty_param_remove_user_id` = 0", [(int)$toParamNameId, (int)$fromParamNameId]);
                    
                    // Помечаем старый параметр как удаленный
                    $this->db->update("UPDATE `dirty_param_name` SET `dirty_param_name_remove_user_id` = ?, `dirty_param_name_remove_date` = UNIX_TIMESTAMP() WHERE `dirty_param_name_id` = ? AND `dirty_param_name_dirty_type_id` = 1", [$currentUserId, (int)$fromParamNameId]);
                }
            }
            // Наименование параметра (начало)

            $this->db->commit();
            return true;

            // Помечаем все существующие записи параметра как удаленные
            //$this->db->update("UPDATE `dirty_param` SET `dirty_param_remove_user_id` = ?, `dirty_param_remove_date` = UNIX_TIMESTAMP() WHERE `dirty_param_dirty_param_name_id` = ? AND `dirty_param_dirty_type_id` = 1 AND `dirty_param_remove_user_id` = 0", [$currentUserId, (int)$toParamNameId]);

            // Добавляем новые привязки к группам
            foreach ($groupLinks as $link) {
                $groupId = (int)$link['group_id'];
                $fileId = (int)$link['file_id'];
                
                if ($groupId > 0 && $fileId > 0) {
                    $this->db->insert("
                        INSERT INTO `dirty_param` 
                        SET `dirty_param_dirty_param_name_id` = ?,
                            `dirty_param_dirty_type_id` = 1,
                            `dirty_param_dirty_group_id` = ?,
                            `dirty_param_dirty_file_id` = ?,
                            `dirty_param_additional` = ?,
                            `dirty_param_add_user_id` = ?,
                            `dirty_param_add_date` = UNIX_TIMESTAMP()
                    ", [$toParamNameId, $groupId, $fileId, $additional, $currentUserId]);
                }
            }

            // Добавляем новые значения
            foreach ($values as $value) {
                $unitId = (int)$value['unit_id'];
                $fileId = (int)$value['file_id'];
                $valueText = trim($value['value']);
                
                if ($fileId > 0 && !empty($valueText)) {
                    // Проверяем существует ли такое значение
                    $valueInfo = $this->get_value_info($valueText);
                    $valueId = $valueInfo ? $valueInfo->id : 0;
                    
                    if ($valueId == 0) {
                        // Создаем новое значение
                        $this->db->insert("
                            INSERT INTO `dirty_param_value` 
                            SET `dirty_param_value_value` = ?,
                                `dirty_param_value_dirty_type_id` = 1,
                                `dirty_param_value_add_user_id` = ?,
                                `dirty_param_value_add_date` = UNIX_TIMESTAMP()
                        ", [$valueText, $currentUserId]);
                        $valueId = $this->pdo->lastInsertId();
                    }
                    
                    // Создаем запись параметра со значением
                    $this->db->insert("
                        INSERT INTO `dirty_param` 
                        SET `dirty_param_dirty_param_name_id` = ?,
                            `dirty_param_dirty_type_id` = 1,
                            `dirty_param_dirty_param_unit_id` = ?,
                            `dirty_param_dirty_param_value_id` = ?,
                            `dirty_param_dirty_file_id` = ?,
                            `dirty_param_additional` = ?,
                            `dirty_param_add_user_id` = ?,
                            `dirty_param_add_date` = UNIX_TIMESTAMP()
                    ", [$toParamNameId, $unitId, $valueId, $fileId, $additional, $currentUserId]);
                }
            }

            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error updating param: ' . $e->getMessage());
            $this->db->rollBack();
            return $e->getMessage();
        }
    }

    /**
     * Получение информации о значении по тексту
     */
    public function get_value_info($value) {
        $sql = "
            SELECT
                `dirty_param_value_id` as `id`
            FROM `dirty_param_value`
            WHERE 1
                AND `dirty_param_value_value` = ?
                AND `dirty_param_value_dirty_type_id` = 1
                AND `dirty_param_value_remove_user_id` = 0
            LIMIT 1
        ";
        
        return $this->db->selectOne($sql, [(string)$value]);
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
                AND `dirty_group_dirty_type_id`  = 1 
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
                AND `dirty_group_dirty_type_id`  = 1 
                AND `dirty_group_remove_user_id` = 0
        ";
        return $this->db->selectOne($sql, [(string)$groupName]);
    }
}