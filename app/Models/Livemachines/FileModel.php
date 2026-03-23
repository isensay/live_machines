<?php

/**
 * Модель для работы со справочником файлов КП
 */

namespace App\Models\Livemachines;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FileModel extends Model
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
     * Получение списка файлов
     */
    function get_list() {
        $sql =
        "
        SELECT
            SQL_CALC_FOUND_ROWS
            `dirty_file_id`   as `id`,
            `dirty_file_name` as `name`
        FROM `dirty_file`
            INNER JOIN `dirty_param` ON (`dirty_file_id` = `dirty_param_dirty_file_id` AND `dirty_param_dirty_type_id` = 1 AND `dirty_param_remove_user_id` = 0)
        WHERE 1
            AND `dirty_file_remove_user_id` = 0
        GROUP BY
            `id`
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
}
