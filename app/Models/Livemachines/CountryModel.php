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
}