<?php

namespace App\Models\Livemachines;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CountryModel extends Model
{
    protected $db;
    protected $pdo;

    protected static $eventFired = false; // Чтобы в профайлере не отображалось что модель подключена несколько раз из за $this->fireModelEvent()

    public $paramTypeId;

    /**
     * Подключение к БД
     */
    public function __construct(array $attributes = [], $connection = null, $paramTypeId = -1) {
        parent::__construct($attributes);
        
        // Используем переданное соединение или создаем новое
        $this->db  = $connection ?? DB::connection('livemachines');
        $this->pdo = $this->db->getPdo();
        
        // Регистрируем в профайлере (только 1 раз)
        if (config('app.debug') && !self::$eventFired) {
            $this->fireModelEvent('retrieved', false);
            self::$eventFired = true;
        }

        $this->paramTypeId = $paramTypeId;
    }

    /**
     * Получение списка стран
     */
    public function get_list($search, $start, $length, $orderColumn, $orderDir) {
        // Допустимые названия полей
        $columns = [
            0 => 'countryName'
        ];

        // Условие по поиску
        $sqlWhereSearch  = "";
        if (!empty($search)) {
            $searchTerm = $this->pdo->quote('%' . $search . '%');
            $sqlWhereSearch = "AND `dirty_country_name` LIKE {$searchTerm}";
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
            `countryId`,
            `countryName`,
            IF(`manufCount` > 0, `manufCount`, '') as `manufCount`,
            IF(`fileCount`  > 0, `fileCount`,  '') as `fileCount`
        FROM
            (
                SELECT
                    `dirty_country_id`               as `countryId`,
                    `dirty_country_name`             as `countryName`,
                    COUNT(DISTINCT `dirty_manuf_id`) as `manufCount`,
                    COUNT(DISTINCT `dirty_file_id`)  as `fileCount`
                FROM `dirty_country`
                    LEFT JOIN `dirty_country_file`  ON (`dirty_country_id` = `dirty_country_file_dirty_country_id`)
                    LEFT JOIN `dirty_file`          ON (`dirty_file_id`    = `dirty_country_file_dirty_file_id`)
                    LEFT JOIN `dirty_manuf_country` ON (`dirty_country_id` = `dirty_manuf_country_dirty_country_id`   AND `dirty_manuf_country_remove_user_id` = 0)
                    LEFT JOIN `dirty_manuf`         ON (`dirty_manuf_id`   = `dirty_manuf_country_dirty_manuf_id`)
                WHERE 1
                    AND `dirty_country_remove_user_id` = 0
                    AND (dirty_file_id IS NULL OR `dirty_file_remove_user_id` = 0)
                    {$sqlWhereSearch}
                GROUP BY
                    `countryId`
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
    public function get_info_from_id($countryId) {
        $sql =
        "
        SELECT
            `dirty_country_id`   as `id`,
            `dirty_country_name` as `name`
        FROM `dirty_country`
        WHERE 1
            AND `dirty_country_id` = ?
            AND `dirty_country_remove_user_id` = 0
        ";
        return $this->db->selectOne($sql, [(int)$countryId]);
    }

    /**
     * Получение информации ID по наименованию
     * при $create = true: создается запись, если ее нет
     */
    public function get_id_from_name($countryName, $create = false) {
        try {
            $sql =
            "
            SELECT
                `dirty_country_id` as `id`
            FROM `dirty_country`
            WHERE 1
                AND `dirty_country_name` = ?
                AND `dirty_country_remove_user_id` = 0
            ";
            $result    = $this->db->selectOne($sql, [(string)$countryName]);
            $countryId = ($result) ? $result->id : 0;

            if ($create == true) {
                // Обновляем, если допустим требуется поменять регистр
                if ($countryId > 0) {
                    $this->db->update("UPDATE `dirty_country` SET `dirty_country_name` = ? WHERE `dirty_country_id` = ?", [(string)$countryName, (int)$countryId]);
                } else {
                    $this->db->insert("INSERT INTO `dirty_country` SET `dirty_country_name` = ?", [(string)$countryName]);
                    $countryId = $this->pdo->lastInsertId();
                }
            } elseif($countryId == 0) {
                return 'Запись не найдена';
            } 
            
            return $countryId;
        } catch (\Exception $e) {
            Log::error('Delete error: '.$e->getMessage());
            return $e->getMessage();
        }
    }

    /**
     * Создание новой записи
     */
    public function create($countryName) {
        try {
            $this->db->beginTransaction();

            $countryId = $this->get_id_from_name($countryName, true);

            if (is_numeric($countryId)) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return $countryId;
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
    public function edit($countryId, $countryName) {
        try {
            $this->db->beginTransaction();

            $dbCountryId = $this->get_id_from_name($countryName, true);
        
            if (!is_numeric($dbCountryId)) return $dbCountryId;

            
            if ($countryId > 0 && $countryId <> $dbCountryId) {
                // Перепривязываем связку страны с файлами
                $this->db->update("UPDATE `dirty_country_file` SET `dirty_country_file_dirty_country_id` = ? WHERE `dirty_country_file_dirty_country_id` = ?", [(int)$dbCountryId, (int)$countryId]);

                // Перепривязываем связку страны с производителем
                $this->db->update("UPDATE `dirty_manuf_country` SET `dirty_manuf_country_dirty_country_id` = ? WHERE `dirty_manuf_country_dirty_country_id` = ?", [(int)$dbCountryId, (int)$countryId]);

                // Удаляем страну из справочника
                $this->db->update("UPDATE `dirty_country` SET `dirty_country_remove_user_id` = ?, `dirty_country_remove_date` = UNIX_TIMESTAMP()  WHERE `dirty_country_id` = ?", [auth()->id(), (int)$countryId]);
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
    public function remove($countryId) {
        try {
            // Начинаем транзакцию
            $this->db->beginTransaction();
            
            // Проверяем существует ли запись
            $record = $this->get_info_from_id($countryId);
            
            if (!$record) return 'Запись не найдена';

            $updated = $this->db->update("
                UPDATE `dirty_country`
                SET
                    `dirty_country_remove_user_id` = ?,
                    `dirty_country_remove_date`    = UNIX_TIMESTAMP()
                WHERE
                    `dirty_country_id` = ?",
                [auth()->id(), (int)$countryId]
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