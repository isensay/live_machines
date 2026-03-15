<?php

namespace App\Models\Livemachines;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TechParam extends Model
{
    protected $db;
    protected $pdo;

    protected static $eventFired       = false; // Чтобы в профайлере не отображалось что модель подключена несколько раз из за $this->fireModelEvent()
    protected static $sharedConnection = null;  // Статическое свойство для хранения единого подключения

    /**
     * Подключение к БД
     */
    public function __construct(array $attributes = [], $connection = null)
    {
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
    public function get_groups($paramId = 0, $virtualItems = true)
    {
        if ($paramId > 0) {
            $sqlWhereParam = "AND `dirty_param_dirty_param_name_id` = " . $this->pdo->quote((int)$paramId);
            $sqlJoinParam  = "INNER JOIN `dirty_param` ON (`dirty_group_id` = `dirty_param_dirty_group_id` AND `dirty_group_dirty_type_id` = `dirty_param_dirty_type_id` AND `dirty_param_remove_user_id` = 0)";
        } else {
            $sqlWhereParam = "";
            $sqlJoinParam  = "";
        }

        $sqlVirtualItems = ($virtualItems === true) ? "UNION (SELECT 'groupandno' as `id`, '- С группой и без -' as `name`)" : "";

        $baseSql = "
            (SELECT 'none' as `id`, '- Без группы -' as `name`)
            {$sqlVirtualItems}
            UNION
            (
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
            ) 
        ";

        return $this->db->select($baseSql);
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
    public function get_param_files($paramId, $additional)
    {
        $sql = "
            SELECT DISTINCT
                `dirty_file_id`   as `id`,
                `dirty_file_name` as `name`
            FROM `dirty_param`
                INNER JOIN `dirty_file` ON (`dirty_file_id` = `dirty_param_dirty_file_id` AND `dirty_file_remove_user_id` = 0)
            WHERE 1
                AND `dirty_param_dirty_param_name_id` = ?
                AND `dirty_param_additional`          = ?
                AND `dirty_param_dirty_type_id`       = 1
                AND `dirty_param_remove_user_id`      = 0
            ORDER BY
                `dirty_file_name` ASC
        ";
        return $this->db->select($sql, [(int)$paramId, (int)$additional]);
    }

    /**
     * Получение списка технических характеристик
     */
    public function get_list(
        $groupId,
        $additional,
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
                AND `dirty_param_additional`         = ?
                {$sqlWhereGroup}
                {$sqlWhereSearch}
            GROUP BY `paramNameId`
            {$sqlHaving}
            {$sqlSort}
            LIMIT {$start}, {$length}
        ";
        
        // Выполняем финальный запрос
        $data = $this->db->select($sql, [$additional]);

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
        $sql = "
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
        return $this->db->select($sql);
    }

    /**
     * Получение списка всех единиц измерения и значений для указанного параметра
     */
    public function get_units_and_values($paramId, $additional) {
        $sql = "
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
                AND `dirty_param_additional`          = ?
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
        return $this->db->select($sql, [(int)$paramId, (int)$additional]);
    }

    /**
     * Получение значения additional для параметра
     */
    //public function get_param_additional($paramId) {
    //    $sql = "
    //        SELECT MAX(`dirty_param_additional`) as `dirty_param_additional`
    //        FROM `dirty_param`
    //        WHERE 1
    //            AND `dirty_param_dirty_param_name_id` = ?
    //            AND `dirty_param_dirty_type_id`       = 1
    //            AND `dirty_param_remove_user_id`      = 0
    //    ";
    //    $result = $this->db->selectOne($sql, [$paramId]);
    //    return $result ? $result->dirty_param_additional : 0;
    //}

    /**
     * Получение значения checked для параметра
     */
    public function get_param_checked($paramId)
    {
        $sql = "
            SELECT MAX(`dirty_param_checked`) as `dirty_param_checked`
            FROM `dirty_param`
            WHERE 1
                AND `dirty_param_dirty_param_name_id` = ?
                AND `dirty_param_dirty_type_id`       = 1
                AND `dirty_param_remove_user_id`      = 0
        ";
        $result = $this->db->selectOne($sql, [$paramId]);
        return $result ? $result->dirty_param_checked : 0;
    }

    /**
     * Получение привязок параметра к группам с информацией о файлах
     */
    public function get_param_group_links($paramId, $additional) {
        $sql = "
            SELECT
                `dirty_param_dirty_group_id` as `group_id`,
                `dirty_group_name`           as `group_name`,
                `dirty_param_dirty_file_id`  as `file_id`,
                `dirty_file_name`            as `file_name`
            FROM `dirty_param`
                LEFT JOIN `dirty_group` ON (`dirty_group_id` = `dirty_param_dirty_group_id` AND `dirty_group_dirty_type_id` = `dirty_param_dirty_type_id` AND `dirty_group_remove_user_id` = 0)
                INNER JOIN `dirty_file` ON (`dirty_file_id`  = `dirty_param_dirty_file_id`  AND `dirty_file_remove_user_id` = 0)
            WHERE 1
                AND `dirty_param_dirty_param_name_id` = ?
                AND `dirty_param_additional`          = ?
                AND `dirty_param_dirty_type_id`       = 1
                AND `dirty_param_remove_user_id`      = 0
            GROUP BY
                `dirty_param_dirty_group_id`,
                `dirty_param_dirty_file_id`
            ORDER BY
                `group_name` ASC,
                `file_name` ASC
        ";
        return $this->db->select($sql, [(int)$paramId, (int)$additional]);
    }

    /**
     * Обновление данных
     */
    public function set($name, $fromParamNameId, $toParamNameId, $groupLinks, $values, $additional, $checked)
    {
        try {
            $this->db->beginTransaction();
            $currentUserId = auth()->id();

            //====== НАИМЕНОВАНИЕ ПАРАМЕТРА ======\\
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
                
                if ($fromParamNameId > 0) {
                    // Перепривязываем существующие записи к новому параметру
                    $this->db->update("UPDATE `dirty_param` SET `dirty_param_dirty_param_name_id` = ? WHERE `dirty_param_dirty_param_name_id` = ? AND `dirty_param_dirty_type_id` = 1 AND `dirty_param_remove_user_id` = 0", [(int)$toParamNameId, (int)$fromParamNameId]);
                    
                    // Помечаем старый параметр как удаленный
                    $this->db->update("UPDATE `dirty_param_name` SET `dirty_param_name_remove_user_id` = ?, `dirty_param_name_remove_date` = UNIX_TIMESTAMP() WHERE `dirty_param_name_id` = ? AND `dirty_param_name_dirty_type_id` = 1", [$currentUserId, (int)$fromParamNameId]);
                }
            }

            //====== ГРУППЫ ======\\
            // Получаем все группы к которому привязан текущий параметр
            $sql = "
                SELECT
                    `dirty_param_dirty_group_id` as `groupId`,
                    `dirty_file_id`              as `fileId`,
                    `dirty_param_additional`     as `additional`
                FROM `dirty_param`
                INNER JOIN `dirty_file` ON (`dirty_file_id`  = `dirty_param_dirty_file_id`  AND `dirty_file_remove_user_id` = 0)
                WHERE 1
                    AND `dirty_param_dirty_param_name_id` = ?
                    AND `dirty_param_dirty_type_id`       = 1
                    AND `dirty_param_remove_user_id`      = 0
            ";
            $dbGroups = $this->db->select($sql, [(int)$toParamNameId]);

            //Log::debug($dbGroups);
            //Log::debug($groupLinks);

            $updateGroup     = [];
            $additionalLinks = [];
            $removeFiles     = [];

            // Собираем массив с группами из базы данных
            foreach($dbGroups as $dbGroup) {
                $isFindFile = false;

                foreach($groupLinks as $key => $groupLink) {
                    if ((int)$groupLink['file_id'] == (int)$dbGroup->fileId) {
                        $isFindFile = true;
                        break;
                    }
                }

                if (!$isFindFile) {
                    $removeFiles[] = [
                        'groupId' => $dbGroup->groupId,
                        'fileId'  => $dbGroup->fileId,
                    ];
                }

                if (!isset($updateGroup[$dbGroup->fileId])) $updateGroup[$dbGroup->fileId] = [];
                $updateGroup[$dbGroup->fileId][] = ['from' => $dbGroup->groupId];
                $additionalLinks[$dbGroup->groupId] = $dbGroup->additional;
            }

            //Log::debug('remove', $removeFiles);

            // Собираем массив с группами отправленными пользователем
            foreach($groupLinks as $key => $groupLink) {
                $rowGroupId = $groupLink['group_id'];
                $rowFileId  = $groupLink['file_id'];

                if (!isset($updateGroup[$rowFileId])) $updateGroup[$rowFileId] = [];

                foreach($updateGroup[$rowFileId] as $i => $row) {
                    if (!isset($updateGroup[$rowFileId][$i]['from'])) $updateGroup[$rowFileId][$i]['from'] = 0;
                    if (!isset($updateGroup[$rowFileId][$i]['to']))   $updateGroup[$rowFileId][$i]['to']   = 0;

                    $updateGroup[$rowFileId][$i]['to'] = $rowGroupId;
                }

                if (!isset($updateGroup[$rowFileId][0]['to'])) $updateGroup[$rowFileId][$i]['to'] = $rowGroupId;
            }

            foreach($removeFiles as $row) {
                $sql = "
                    UPDATE `dirty_param`
                    SET
                        `dirty_param_remove_user_id` = " . $this->pdo->quote((int)$currentUserId) . ",
                        `dirty_param_remove_date`    = UNIX_TIMESTAMP()
                    WHERE 1
                        AND `dirty_param_dirty_param_name_id` = " . $this->pdo->quote((int)$toParamNameId) . "
                        AND `dirty_param_dirty_group_id`      = " . $this->pdo->quote((int)$row['groupId']) . "
                        AND `dirty_param_dirty_file_id`       = " . $this->pdo->quote((int)$row['fileId']) . "
                        AND `dirty_param_additional`          = " . $this->pdo->quote((int)$additionalLinks[$row['groupId']]) . "
                        AND `dirty_param_remove_user_id`      = 0
                ";
                $this->db->insert($sql);
            }

            foreach($updateGroup as $fileId => $rows)
            {
                foreach($rows as $i => $row)
                {
                    if (!isset($row['from']) && isset($row['to'])) {
                        $sql = "
                            INSERT IGNORE INTO `dirty_param`
                            SET
                                `dirty_param_dirty_param_name_id`  = " . $this->pdo->quote((int)$toParamNameId) . ",
                                `dirty_param_dirty_param_unit_id`  = " . $this->pdo->quote(0) . ",
                                `dirty_param_dirty_param_value_id` = " . $this->pdo->quote(0) . ",
                                `dirty_param_additional`           = " . $this->pdo->quote((int)$additional) . ",
                                `dirty_param_checked`              = " . $this->pdo->quote((int)$checked) . ",
                                `dirty_param_dirty_type_id`        = 1,
                                `dirty_param_dirty_group_id`       = " . $this->pdo->quote((int)$row['to']) . ",
                                `dirty_param_dirty_file_id`        = " . $this->pdo->quote((int)$fileId) . ",
                                `dirty_param_checked_user_id`      = " . $this->pdo->quote((int)$checked == 1 ? (int)$currentUserId : 0) . ",
                                `dirty_param_checked_date`         = " . $this->pdo->quote((int)$checked == 1 ? time() : 0) . "
                        ";
                        //Log::debug($sql);
                        $this->db->insert($sql);
                    } elseif (isset($row['from'])) {
                        $sql = "
                            UPDATE `dirty_param`
                            SET
                                `dirty_param_checked`         = " . $this->pdo->quote((int)$checked) . ",
                                `dirty_param_dirty_group_id`  = " . $this->pdo->quote(isset($row['to']) ? (int)$row['to'] : 0) . ",
                                `dirty_param_additional`      = " . $this->pdo->quote((int)$additional) . ",
                                `dirty_param_checked_user_id` = " . $this->pdo->quote((int)$checked == 1 ? (int)$currentUserId : 0) . ",
                                `dirty_param_checked_date`    = " . $this->pdo->quote((int)$checked == 1 ? time() : 0) . "
                            WHERE 1
                                AND `dirty_param_dirty_param_name_id` = " . $this->pdo->quote((int)$toParamNameId) . "
                                AND `dirty_param_dirty_group_id`      = " . $this->pdo->quote((int)$row['from']) . "
                                AND `dirty_param_dirty_file_id`       = " . $this->pdo->quote((int)$fileId) . "
                                AND `dirty_param_additional`          = " . $this->pdo->quote((int)$additionalLinks[$row['from']]) . "
                                AND `dirty_param_dirty_type_id`       = 1
                                AND `dirty_param_remove_user_id`      = 0
                        ";
                        //Log::debug($sql);
                        $this->db->update($sql);
                    }
                }
            }

            

            Log::debug($updateGroup);

            //Log::debug($values);

            //$dbGroupLinks = [];
            //$newBindGroup = [];
            //$unBindGroups = [];

            /*
            foreach($dbGroups as $dbGroup) {
                $key = $dbGroup->groupId . '-' . $dbGroup->fileId;
                $dbGroupLinks[$key] = 1;
                if (!isset($groupLinks[$key]) && !isset($unBindGroups[$key])) {
                    $unBindGroups[$key] = $dbGroup->additional;
                }
            }

            foreach($groupLinks as $key => $groupLink) {
                if (!isset($dbGroupLinks[$key]) && !in_array($key, $newBindGroup)) {
                    $newBindGroup[] = $key;
                }
            }

            foreach($unBindGroups as $key => $rowAdditional) {
                $el  = explode('-', $key);
                $sql = "
                    UPDATE `dirty_param`
                    SET
                        `dirty_param_remove_user_id` = " . $this->pdo->quote((int)$currentUserId) . ",
                        `dirty_param_remove_date`    = UNIX_TIMESTAMP()
                    WHERE 1
                        AND `dirty_param_dirty_param_name_id` = " . $this->pdo->quote((int)$toParamNameId) . "
                        AND `dirty_param_dirty_group_id`      = " . $this->pdo->quote((int)$el[0]) . "
                        AND `dirty_param_dirty_file_id`       = " . $this->pdo->quote((int)$el[1]) . "
                        AND `dirty_param_additional`          = " . $this->pdo->quote((int)$rowAdditional) . "
                        AND `dirty_param_remove_user_id`      = 0
                ";
                $this->db->update($sql);
            }
            */


            //Log::debug($unBindGroups);
            //Log::debug($newBindGroup);

            $this->db->commit();
            return true;

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