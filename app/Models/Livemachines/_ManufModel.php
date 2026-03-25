<?php

/**
 * Модель для работы со справочником стран
 */

namespace App\Models\Livemachines;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManufModel extends Model
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
    public function get_list() {
        $sql =
        "
        SELECT
            `id`,
            `name`,
            IF(`country` IS NULL, '-', `country`) as `country`,
            IF(`models` > 0, `models`, '-') as `models`,
            IF(`files`  > 0, `files`,  '-') as `files`
        FROM
            (
                SELECT
                    `dirty_manuf_id`   as `id`,
                    `dirty_manuf_name` as `name`,
                    GROUP_CONCAT(DISTINCT `dirty_country_name` ORDER BY `dirty_country_name` ASC SEPARATOR '<br>') as `country`,
                    COUNT(DISTINCT `dirty_model_id`) as `models`,
                    COUNT(DISTINCT `dirty_file_id`)  as `files`
                FROM `dirty_manuf`
                    LEFT JOIN `dirty_manuf_file`    ON (`dirty_manuf_id`   = `dirty_manuf_file_dirty_manuf_id`)
                    LEFT JOIN `dirty_file`          ON (`dirty_file_id`    = `dirty_manuf_file_dirty_file_id`)
                    LEFT JOIN `dirty_manuf_country` ON (`dirty_manuf_id`   = `dirty_manuf_country_dirty_manuf_id`   AND `dirty_manuf_country_remove_user_id` = 0)
                    LEFT JOIN `dirty_country`       ON (`dirty_country_id` = `dirty_manuf_country_dirty_country_id` AND `dirty_manuf_remove_user_id` = 0)
                    LEFT JOIN `dirty_model_file`    ON (`dirty_file_id`    = `dirty_model_file_dirty_file_id`)
                    LEFT JOIN `dirty_model`         ON (`dirty_model_id`   = `dirty_model_file_dirty_model_id` AND `dirty_model_remove_user_id` = 0)
                WHERE 1
                    AND `dirty_manuf_remove_user_id` = 0
                    AND (dirty_file_id IS NULL OR `dirty_file_remove_user_id` = 0)
                GROUP BY
                    `id`
                ORDER BY
                    `name` ASC
            ) as `tmp`
        ";
        return $this->db->select($sql);
        
    }
}
