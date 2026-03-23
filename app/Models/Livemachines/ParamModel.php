<?php

/**
 * Модель для работы со справочниками параметров, ед.измерения и значений технических характеристик и комплектаций
 * $paramTypeId: 1 - технические характеристики; 2 - комплектации
 */

namespace App\Models\Livemachines;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ParamModel extends Model
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
     * Получение списка всех файлов (источников)
     */
    public function get_files() {
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
    public function get_param_files($paramNameId, $additional) {
        $sql = "
            SELECT DISTINCT
                `dirty_file_id`   as `id`,
                `dirty_file_name` as `name`
            FROM `dirty_param`
                INNER JOIN `dirty_file` ON (`dirty_file_id` = `dirty_param_dirty_file_id` AND `dirty_file_remove_user_id` = 0)
            WHERE 1
                AND `dirty_param_dirty_param_name_id` = ?
                AND `dirty_param_additional`          = ?
                AND `dirty_param_dirty_type_id`       = {$this->paramTypeId}
                AND `dirty_param_remove_user_id`      = 0
            ORDER BY
                `dirty_file_name` ASC
        ";
        return $this->db->select($sql, [(int)$paramNameId, (int)$additional]);
    }

    /**
     * Получение списка технических характеристик
     */
    public function get_list($groupId, $additional, $start, $length, $search, $orderColumn, $orderDir) {
        // Определяем маппинг колонок прямо в модели
        $columns = [
            0 => 'paramName',
        ];
        
        // Условие по группе
        $sqlWhereGroup = "";
        $sqlHaving     = "";
        if (is_numeric($groupId) && (int)$groupId >= 0) {
            $sqlWhereGroup = "AND `dirty_param_dirty_group_id` = ".$this->pdo->quote((int)$groupId);
        } elseif ($groupId == 'check') {
            $sqlWhereGroup = "AND `dirty_param_checked` = {$this->paramTypeId}";
        } elseif ($groupId == 'nocheck') {
            $sqlWhereGroup = "AND `dirty_param_checked` = 0";
        } elseif ($groupId == 'groupandno') {
            $sqlHaving = "HAVING `groupMinId` = 0 AND `groupMaxId` > 0";
        }

        // Условие по виду параметра (основной или дополнительный)
        if (in_array($additional, [-1,0,1])) {
            $sqlWhereAdditional = ($additional >= 0) ? "AND `dirty_param_additional` = ".$this->pdo->quote((int)$additional) : "";
        } else {
            return [
                'data'  => [],
                'total' => 0,
            ];
        }

        // Условие по поиску
        $sqlWhereSearch  = "";
        if (!empty($search)) {
            $minFulltextLength = 3; // Минимальная длина для FULLTEXT (По умолчанию MySQL имеет параметр ft_min_word_len = 4 (для MyISAM) или innodb_ft_min_token_size = 3)
            
            // Удаляем пробелы и считаем длину первого слова
            $firstWord  = trim(explode(' ', $search)[0]);
            $wordLength = mb_strlen($firstWord);
            
            if (1>2 && $wordLength >= $minFulltextLength) {
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
                    AND `dirty_param_dirty_type_id`  = {$this->paramTypeId}
                    AND `dirty_param_remove_user_id` = 0
                )
                INNER JOIN `dirty_file` ON (`dirty_file_id` = `dirty_param_dirty_file_id` AND `dirty_file_remove_user_id` = 0)
                LEFT JOIN `dirty_group` ON (1
                    AND `dirty_param_dirty_group_id` = `dirty_group_id`
                    AND `dirty_group_dirty_type_id`  = `dirty_param_name_dirty_type_id`
                )
            WHERE 1
                AND `dirty_param_name_dirty_type_id`  = {$this->paramTypeId}
                AND `dirty_param_name_remove_user_id` = 0
                {$sqlWhereAdditional}
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
    public function get_info_from_id($paramNameId) {
        // Получаем основную информацию о параметре
        $sql = "
            SELECT
                `dirty_param_name_id`            as `id`,
                `dirty_param_name_value`         as `name`,
                `dirty_param_name_dirty_type_id` as `type_id`
            FROM `dirty_param_name`
            WHERE 1
                AND `dirty_param_name_id`             = ?
                AND `dirty_param_name_dirty_type_id`  = {$this->paramTypeId}
                AND `dirty_param_name_remove_user_id` = 0
        ";
        return $this->db->selectOne($sql, [(int)$paramNameId]);
    }

    /**
     * Получение информации о технической характеристике по имени
     */
    public function get_info_from_name($name, $create = false) {
        // Получаем информацию о наименовании параметра
        $sqlSelect = "
            SELECT
                `dirty_param_name_id`            as `id`,
                `dirty_param_name_value`         as `name`,
                `dirty_param_name_dirty_type_id` as `type_id`
            FROM `dirty_param_name`
            WHERE 1
                AND `dirty_param_name_value`          = ?
                AND `dirty_param_name_dirty_type_id`  = {$this->paramTypeId}
                AND `dirty_param_name_remove_user_id` = 0
        ";
        $result = $this->db->selectOne($sqlSelect, [(string)$name]);

        if ($result) return $result;

        // Добавляем наименование параметра и получаем информацию о нем
        if ($create === true) {
            $sqlInsert = "
            INSERT INTO `dirty_param_name`
            SET
                `dirty_param_name_value`         = ?,
                `dirty_param_name_dirty_type_id` = {$this->paramTypeId},
                `dirty_param_name_add_user_id`   = ?,
                `dirty_param_name_add_date`      = UNIX_TIMESTAMP()
            ";
            $this->db->insert($sqlInsert, [(string)$name, (int)auth()->id()]);
            return $this->db->selectOne($sqlSelect, [(string)$name]);
        }
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
                    AND `dirty_param_unit_dirty_type_id`  = {$this->paramTypeId}
                    AND `dirty_param_unit_remove_user_id` = 0
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
    public function get_units_and_values($paramNameId, $additional) {
        $sql = "
            SELECT
                `dirty_param_id`          as `param_id`,
                `dirty_param_unit_id`     as `unit_id`,
                `dirty_param_unit_value`  as `unit_name`,
                `dirty_param_value_id`    as `value_id`,
                `dirty_param_value_value` as `value`,
                '' as `value_text`,
                `dirty_param_dirty_file_id` as `file_id`,
                `dirty_file_name` as `file_name`
            FROM `dirty_param`
                LEFT JOIN `dirty_param_unit`  ON (`dirty_param_unit_id`  = `dirty_param_dirty_param_unit_id`  AND `dirty_param_unit_dirty_type_id`  = {$this->paramTypeId} AND `dirty_param_unit_remove_user_id`  = 0)
                LEFT JOIN `dirty_param_value` ON (`dirty_param_value_id` = `dirty_param_dirty_param_value_id` AND `dirty_param_value_dirty_type_id` = {$this->paramTypeId} AND `dirty_param_value_remove_user_id` = 0)
                LEFT JOIN `dirty_file` ON (`dirty_file_id` = `dirty_param_dirty_file_id` AND `dirty_file_remove_user_id` = 0)
            WHERE 1
                AND `dirty_param_dirty_param_name_id` = ?
                AND `dirty_param_additional`          = ?
                AND `dirty_param_dirty_type_id`       = {$this->paramTypeId}
                AND `dirty_param_remove_user_id`      = 0
            GROUP BY
                `dirty_param_id`
            ORDER BY
                `unit_name` ASC,
                `value` ASC
        ";
        return $this->db->select($sql, [(int)$paramNameId, (int)$additional]);
    }

    /**
     * Получение значения checked для параметра
     */
    public function get_param_checked($paramNameId) {
        $sql = "
            SELECT MAX(`dirty_param_checked`) as `dirty_param_checked`
            FROM `dirty_param`
            WHERE 1
                AND `dirty_param_dirty_param_name_id` = ?
                AND `dirty_param_dirty_type_id`       = {$this->paramTypeId}
                AND `dirty_param_remove_user_id`      = 0
        ";
        $result = $this->db->selectOne($sql, [$paramNameId]);
        return $result ? $result->dirty_param_checked : 0;
    }

    /**
     * Получение привязок параметра к группам с информацией о файлах
     */
    public function get_param_group_links($paramNameId, $additional) {
        $sql = "
            SELECT
                `dirty_param_id`             as `param_id`,
                `dirty_param_dirty_group_id` as `group_id`,
                `dirty_group_name`           as `group_name`,
                `dirty_param_dirty_file_id`  as `file_id`,
                `dirty_file_name`            as `file_name`
            FROM `dirty_param`
                LEFT JOIN `dirty_group` ON (`dirty_group_id` = `dirty_param_dirty_group_id` AND `dirty_group_dirty_type_id` = {$this->paramTypeId} AND `dirty_group_remove_user_id` = 0)
                INNER JOIN `dirty_file` ON (`dirty_file_id`  = `dirty_param_dirty_file_id`  AND `dirty_file_remove_user_id` = 0)
            WHERE 1
                AND `dirty_param_dirty_param_name_id` = ?
                AND `dirty_param_additional`          = ?
                AND `dirty_param_dirty_type_id`       = {$this->paramTypeId}
                AND `dirty_param_remove_user_id`      = 0
            GROUP BY
                `dirty_param_id`
            ORDER BY
                `group_name` ASC,
                `file_name` ASC
        ";
        return $this->db->select($sql, [(int)$paramNameId, (int)$additional]);
    }

    /**
     * Получение ID значения по тексту
     */
    public function get_value_id_from_text($value) {
        $valueRaw = $value ?? '';
        $value    = (string)trim(preg_replace('/\s+/', ' ', $valueRaw));
            
        $result = $this->db->selectOne(
            "SELECT `dirty_param_value_id` FROM `dirty_param_value` WHERE `dirty_param_value_value` = ? AND `dirty_param_value_dirty_type_id` = {$this->paramTypeId}", 
            [$value]
        );

        if ($result) return (int)$result->dirty_param_value_id;

        $this->db->insert(
            "
            INSERT INTO `dirty_param_value`
            SET
                `dirty_param_value_value`         = ?,
                `dirty_param_value_dirty_type_id` = {$this->paramTypeId},
                `dirty_param_value_add_user_id`   = ?,
                `dirty_param_value_add_date`      = UNIX_TIMESTAMP()
            ",
            [(string)$value, (int)auth()->id()]
        );

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Создание параметра
     */
    public function create($paramName, $fromParamNameId, $toParamNameId, $params, $additionalFilter, $additional, $checked) {
        try {
            $this->db->beginTransaction();
            $currentUserId = auth()->id();

            if ($toParamNameId > 0) {
                $this->db->update("UPDATE `dirty_param_name` SET `dirty_param_name_value` = ? WHERE `dirty_param_name_id` = ?  AND
                `dirty_param_name_dirty_type_id` = {$this->paramTypeId}", [(string)$paramName, (int)$toParamNameId]);
            }

            // Обновляем имеющиеся параметры в базе данных
            foreach($params as $paramId => $param) {
                $valueId = $this->get_value_id_from_text($param['value']);

                $sql = "
                    INSERT IGNORE INTO `dirty_param`
                    SET
                        `dirty_param_dirty_param_name_id`  = " . $this->pdo->quote((int)$toParamNameId) . ",
                        `dirty_param_dirty_param_unit_id`  = " . $this->pdo->quote((int)$param['unitId']) . ",
                        `dirty_param_dirty_param_value_id` = " . $this->pdo->quote((int)$valueId) . ",
                        `dirty_param_dirty_group_id`       = " . $this->pdo->quote((int)$param['groupId']) . ",
                        `dirty_param_additional`           = " . $this->pdo->quote((int)$additional) . ",
                        `dirty_param_checked`              = " . $this->pdo->quote((int)$checked) . ",
                        `dirty_param_dirty_type_id`        = " . $this->paramTypeId . ",
                        `dirty_param_dirty_file_id`        = " . $this->pdo->quote((int)$param['fileId']) . ",
                        `dirty_param_checked_user_id`      = " . $this->pdo->quote((int)$checked == 1 ? (int)$currentUserId : 0) . ",
                        `dirty_param_checked_date`         = " . $this->pdo->quote((int)$checked == 1 ? time() : 0) . "
                ";
                Log::debug($sql);
                $this->db->insert($sql);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            Log::error('Error create param: ' . $e->getMessage());
            $this->db->rollBack();
            return $e->getMessage();
        }
        
    }

    /**
     * Обновление параметра
     */
    //public function update($paramName, $fromParamNameId, $toParamNameId, $params, $additionalFilter, $additional, $checked) {
    public function set($data) {
        try {
            $paramName        = $data['paramName'];
            $fromParamNameId  = $data['paramNameId'];
            $toParamNameId    = $data['newParamNameId'];
            $params           = $data['validParams'];
            $additionalFilter = $data['additionalFilter'];
            $additional       = $data['additional'];
            $checked          = $data['checked'];

            $currentUserId = auth()->id();

            $this->db->beginTransaction();
            

            // Получаем все группы к которому привязан текущий параметр
            $sql = "
                SELECT
                    `dirty_param_id`             as `paramId`,
                    `dirty_param_dirty_group_id` as `groupId`,
                    `dirty_file_id`              as `fileId`,
                    `dirty_param_additional`     as `additional`
                FROM `dirty_param`
                INNER JOIN `dirty_file` ON (`dirty_file_id`  = `dirty_param_dirty_file_id`  AND `dirty_file_remove_user_id` = 0)
                WHERE 1
                    AND `dirty_param_dirty_param_name_id` = ?
                    AND `dirty_param_additional`          = ?
                    AND `dirty_param_dirty_type_id`       = {$this->paramTypeId}
                    AND `dirty_param_remove_user_id`      = 0
            ";
            $dbGroups = $this->db->select($sql, [(int)$fromParamNameId, (int)$additionalFilter]);

            if ($toParamNameId == 0) {
                $this->db->insert("INSERT INTO `dirty_param_name` SET `dirty_param_name_value` = ?, `dirty_param_name_dirty_type_id` = {$this->paramTypeId}, `dirty_param_name_add_user_id` = ?, `dirty_param_name_add_date` = UNIX_TIMESTAMP()", [(string)$paramName, $currentUserId]);
                $toParamNameId = $this->pdo->lastInsertId();
            } else {
                $this->db->update("UPDATE `dirty_param_name` SET `dirty_param_name_value` = ? WHERE `dirty_param_name_id` = ? AND `dirty_param_name_dirty_type_id` = {$this->paramTypeId}", [(string)$paramName, (int)$toParamNameId]);
            }

            // Удаляем если параметр имеющийся в базе данных не передан
            foreach($dbGroups as $dbGroup) {
                if (!isset($params[$dbGroup->paramId])) {
                    $sql = "
                        UPDATE `dirty_param`
                        SET
                            `dirty_param_remove_user_id` = " . $this->pdo->quote((int)$currentUserId) . ",
                            `dirty_param_remove_date`    = UNIX_TIMESTAMP()
                        WHERE 1
                            AND `dirty_param_id`             = " . $this->pdo->quote((int)$dbGroup->paramId) . "
                            AND `dirty_param_dirty_type_id`  = {$this->paramTypeId}
                            AND `dirty_param_remove_user_id` = 0
                    ";
                    $this->db->update($sql);
                }
            }

            //Log::debug($params);

            // Обновляем имеющиеся параметры в базе данных
            foreach($params as $paramId => $param) {

                $valueId = $this->get_value_id_from_text($param['value']);

                if (is_numeric($paramId)) {
                    $sql = "
                        UPDATE `dirty_param`
                        SET
                            `dirty_param_dirty_param_name_id`  = " . $this->pdo->quote((int)$toParamNameId) . ",
                            `dirty_param_dirty_param_unit_id`  = " . $this->pdo->quote((int)$param['unitId']) . ",
                            `dirty_param_dirty_param_value_id` = " . $this->pdo->quote((int)$valueId) . ",
                            `dirty_param_dirty_group_id`       = " . $this->pdo->quote((int)$param['groupId']) . ",
                            `dirty_param_additional`           = " . $this->pdo->quote((int)$additional) . ",
                            `dirty_param_checked`              = " . $this->pdo->quote((int)$checked) . ",
                            `dirty_param_checked_user_id`      = " . $this->pdo->quote((int)$checked == 1 ? (int)$currentUserId : 0) . ",
                            `dirty_param_checked_date`         = " . $this->pdo->quote((int)$checked == 1 ? time() : 0) . "
                        WHERE 1
                            AND `dirty_param_id`             = " . $this->pdo->quote((int)$paramId) . "
                            AND `dirty_param_dirty_type_id`  = " . $this->paramTypeId . "
                            AND `dirty_param_remove_user_id` = 0
                    ";
                    $this->db->update($sql);
                } else {
                    $sql = "
                        SELECT *
                        FROM `dirty_param`
                        WHERE 1
                            AND `dirty_param_dirty_param_name_id`  = " . $this->pdo->quote((int)$toParamNameId) . "
                            AND `dirty_param_dirty_param_unit_id`  = " . $this->pdo->quote((int)$param['unitId']) . "
                            AND `dirty_param_dirty_param_value_id` = " . $this->pdo->quote((int)$valueId) . "
                            AND `dirty_param_dirty_group_id`       = " . $this->pdo->quote((int)$param['groupId']) . "
                            AND `dirty_param_additional`           = " . $this->pdo->quote((int)$additional) . "
                            AND `dirty_param_dirty_type_id`        = " . $this->paramTypeId . "
                            AND `dirty_param_dirty_file_id`        = " . $this->pdo->quote((int)$param['fileId']) . "
                            AND `dirty_param_remove_user_id`       = 0
                    ";
                    $result = $this->db->selectOne($sql);
                    if ($result) {
                        $this->db->rollBack();
                        return 'один из наборов параметров имеет дубль';
                    }

                    $sql = "
                        INSERT INTO `dirty_param`
                        SET
                            `dirty_param_dirty_param_name_id`  = " . $this->pdo->quote((int)$toParamNameId) . ",
                            `dirty_param_dirty_param_unit_id`  = " . $this->pdo->quote((int)$param['unitId']) . ",
                            `dirty_param_dirty_param_value_id` = " . $this->pdo->quote((int)$valueId) . ",
                            `dirty_param_dirty_group_id`       = " . $this->pdo->quote((int)$param['groupId']) . ",
                            `dirty_param_additional`           = " . $this->pdo->quote((int)$additional) . ",
                            `dirty_param_checked`              = " . $this->pdo->quote((int)$checked) . ",
                            `dirty_param_dirty_type_id`        = " . $this->paramTypeId . ",
                            `dirty_param_dirty_file_id`        = " . $this->pdo->quote((int)$param['fileId']) . ",
                            `dirty_param_checked_user_id`      = " . $this->pdo->quote((int)$checked == 1 ? (int)$currentUserId : 0) . ",
                            `dirty_param_checked_date`         = " . $this->pdo->quote((int)$checked == 1 ? time() : 0) . "
                    ";
                    $this->db->insert($sql);
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
     * Удаление параметра
     */
    public function remove($paramNameId) {
        try {
            // Начинаем транзакцию
            $this->db->beginTransaction();

            $sql =
            "
            SELECT
                `dirty_param_name_id`    as `paramNameId`,
                `dirty_param_name_value` as `paramName`,
                GROUP_CONCAT(DISTINCT `dirty_file_id`) as `fileIds`
            FROM `dirty_param_name`
                LEFT JOIN `dirty_param` ON (`dirty_param_name_id` = `dirty_param_dirty_param_name_id` AND `dirty_param_dirty_type_id` = `dirty_param_name_dirty_type_id` AND `dirty_param_remove_user_id` = 0)
                LEFT JOIN `dirty_file`  ON (`dirty_file_id`       = `dirty_param_dirty_file_id`)
            WHERE 1
                AND `dirty_param_name_id` = ?
                AND `dirty_param_name_dirty_type_id` = {$this->paramTypeId}
                AND (dirty_file_id IS NULL OR `dirty_file_remove_user_id` = 0)
            ";
            
            $record = $this->db->selectOne($sql, [$paramNameId]);
            
            if (!$record) return 'Запись не найдена';

            $fileIds = isset($record->fileIds) ? $record->fileIds : "";

            if ($fileIds == "") return true;

            $updated = $this->db->update("UPDATE `dirty_file` SET `dirty_file_remove_user_id` = ?, `dirty_file_remove_date` = UNIX_TIMESTAMP() WHERE `dirty_file_id` IN ({$fileIds})", [auth()->id()]);
            
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
                AND `dirty_param_value_dirty_type_id` = {$this->paramTypeId}
                AND `dirty_param_value_remove_user_id` = 0
            LIMIT 1
        ";
        return $this->db->selectOne($sql, [(string)$value]);
    }
}