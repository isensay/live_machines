<?php

namespace App\Http\Controllers\Livemachines;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Livemachines\TechParam;

class SpravController extends Controller
{
    /**
     * Страница с техническими характеристиками
     */
    public function tech_list()
    {
        $techParam = new TechParam();
        return view('livemachines/tech/list', [
            'groups' => $techParam->get_groups(),
            'files' => $techParam->get_files()
        ]);
    }

    /**
     * AJAX -> JSON
     * Получение списка технических параметров
     */
    public function tech_data_ajax(Request $request)
    {
        // Параметры DataTable
        $draw        = $request->get('draw');
        $start       = (int)$request->get('start', 0);
        $length      = (int)$request->get('length', 10);
        $search      = $request->get('search')['value'] ?? '';
        $orderColumn = $request->get('order')[0]['column'] ?? 0;
        $orderDir    = $request->get('order')[0]['dir'] ?? 'asc';
        
        $groupId = $request->get('group_id', 'none');

        $techParam = new TechParam();
                
        $result = $techParam->get_list($groupId,  $start, $length, $search, $orderColumn, $orderDir);
        
        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $result['total'],
            'recordsFiltered' => $result['total'],
            'data' => $result['data']
        ]);
    }

    /**
     * Удаление всех файлов с которыми связана техническая характеристика
     */
    public function tech_destroy(int $id)
    {
        try {
            $dbLm = DB::connection('livemachines');
            
            // Начинаем транзакцию
            $dbLm->beginTransaction();

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
                AND `dirty_param_name_dirty_type_id` = 1
                AND (dirty_file_id IS NULL OR `dirty_file_remove_user_id` = 0)
            ";
            
            $record = $dbLm->selectOne($sql, [$id]);
            
            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Запись не найдена'
                ], 404);
            }

            $fileIds = isset($record->fileIds) ? $record->fileIds : "";

            if ($fileIds == "")
            {
                if (request()->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Запись успешно удалена',
                        'id' => $id
                    ]);
                }

                return redirect()
                    ->route('lm_tech.list')
                    ->with('success', 'Запись успешно удалена');
            }

            $updated = $dbLm->update("UPDATE `dirty_file` SET `dirty_file_remove_user_id` = ?, `dirty_file_remove_date` = UNIX_TIMESTAMP() WHERE `dirty_file_id` IN ({$fileIds})", [auth()->id()]);
            
            if ($updated)
            {
                $dbLm->commit();

                if (request()->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Запись успешно удалена',
                        'id' => $id
                    ]);
                }

                return redirect()
                    ->route('lm_tech.list')
                    ->with('success', 'Запись успешно удалена');
            }
            
            $dbLm->rollBack();

            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при удалении'
                ], 500);
            }
            
            return redirect()
                ->route('lm_tech.list')
                ->with('error', 'Ошибка при удалении');
                
        } catch (\Exception $e) {
            Log::error('Delete error: '.$e->getMessage());
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка: '.$e->getMessage()
                ], 500);
            }
            
            return redirect()
                ->route('lm_tech.list')
                ->with('error', 'Ошибка при удалении');
        }
    }

    /**
     * Получить данные для редактирования
     */
    public function tech_edit_data($id)
    {
        try {
            $techParam = new TechParam();

            // Получаем основную информацию о параметре
            $param = $techParam->get_info_from_id($id);
            
            if (!$param) {
                return response()->json([
                    'success' => false,
                    'message' => 'Параметр не найден'
                ], 404);
            }
            
            // Получаем привязки к группам с информацией о файлах
            $groupLinks = $techParam->get_param_group_links($id);
            
            // Получаем дополнительную информацию о параметре (additional)
            $additional = $this->get_param_additional($id);

            // Получаем дополнительную информацию о параметре (checked)
            $checked = $this->get_param_checked($id);

            // Получаем все значения с привязкой к файлам
            $values = $techParam->get_units_and_values($id);
            
            // Получаем список всех файлов для параметра
            $paramFiles = $techParam->get_param_files($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'param' => $param,
                    'group_links' => $groupLinks,
                    'values' => $values,
                    'param_files' => $paramFiles,
                    'additional' => $additional,
                    'checked' => $checked,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting edit data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки данных: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить значение additional для параметра
     */
    private function get_param_additional($paramId)
    {
        $dbLm = DB::connection('livemachines');
        
        $sql = "
            SELECT MAX(`dirty_param_additional`) as `dirty_param_additional`
            FROM `dirty_param`
            WHERE 1
                AND `dirty_param_dirty_param_name_id` = ?
                AND `dirty_param_dirty_type_id` = 1
                AND `dirty_param_remove_user_id` = 0
        ";
        
        $result = $dbLm->selectOne($sql, [$paramId]);
        
        return $result ? $result->dirty_param_additional : 0;
    }

    /**
     * Получить значение checked для параметра
     */
    private function get_param_checked($paramId)
    {
        $dbLm = DB::connection('livemachines');
        
        $sql = "
            SELECT MAX(`dirty_param_checked`) as `dirty_param_checked`
            FROM `dirty_param`
            WHERE 1
                AND `dirty_param_dirty_param_name_id` = ?
                AND `dirty_param_dirty_type_id` = 1
                AND `dirty_param_remove_user_id` = 0
        ";
        
        $result = $dbLm->selectOne($sql, [$paramId]);
        
        return $result ? $result->dirty_param_checked : 0;
    }

    /**
     * Получить справочники для формы редактирования
     */
    public function tech_get_references()
    {
        try {
            $techParam = new TechParam();

            // Получение списка всех групп технических характеристик
            $groups = $techParam->get_groups();

            // Получение списка всех единиц измерения
            $units = $techParam->get_units();
            
            // Получение списка всех файлов
            $files = $techParam->get_files();

        } catch(\Exception $e) {
            Log::error('Error getting references: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки данных: ' . $e->getMessage()
            ], 500);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'groups' => $groups,
                'units'  => $units,
                'files'  => $files
            ]
        ]);
    }

    /**
     * Обновление параметра
     */
    public function tech_update(Request $request, $id)
    {
        Log::debug($request);

        $techParam = new TechParam();
        
        // Искусственная задержка (для режима разработки)
        if (config('app.debug')) {
            usleep(500000);
        }

        // Очищаем входные данные
        $request->merge([
            'name' => trim($request->name ?? '')
        ]);

        // Валидация
        $request->validate([
            'name' => 'required|string|max:255',
            'group_links' => 'array',
            'values' => 'array',
            'additional' => 'integer|in:0,1',
            'checked' => 'integer|in:0,1'
        ]);

        // Наименование параметра (начало)
        $paramInfo = $techParam->get_info_from_id($id);

        $result = false;

        if (!$paramInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Параметр не найден'
            ]);
        }
            
        $paramName      = preg_replace('/\s+/', ' ', $request->name);
        $lowerParamName = mb_strtolower($paramName);
        
        if ($lowerParamName == mb_strtolower($paramInfo->name)) {
            $newParamNameId = $id; // Обновляем если например изменились регистры символов
        } elseif ($paramInfo = $techParam->get_info_from_name($paramName)) {
            $newParamNameId = $paramInfo->id; // Перепривязываем к уже имеющемуся в БД
        } else {
            $newParamNameId = 0; // Создаем новый параметр
        }
        // Наименование параметра (конец)

        // Подготовка флага "дополнительный параметр"
        $additional = $request->additional ?? 0;

        // Подготовка статуса проверки
        $checked = $request->checked ?? 0;

        // Привязки к группам
        $groupLinks = $request->group_links ?? [];
        $validGroupLinks = [];
        
        foreach($groupLinks as $link) {
            $groupId = (int)$link['group_id'];
            $fileId = (int)$link['file_id'];
            
            if ($groupId > 0 && $fileId > 0 && $techParam->get_group_info_from_id($groupId)) {
                // Проверяем уникальность комбинации группа-файл
                $key = $groupId . '-' . $fileId;
                if (!isset($validGroupLinks[$key])) {
                    $validGroupLinks[$key] = [
                        'group_id' => $groupId,
                        'file_id' => $fileId
                    ];
                }
            }
        }

        // Единицы измерения и значения
        $values = $request->values ?? [];
        $validValues = [];
        
        foreach($values as $row) {
            $unitId = (int)$row['unit_id'];
            $fileId = (int)$row['file_id'];
            $value  = (string)trim(preg_replace('/\s+/', ' ', $row['value']));
            
            if (!empty($value) && $fileId > 0) {
                $key = $unitId . '-' . $fileId . '-' . md5($value);
                if (!isset($validValues[$key])) {
                    $validValues[$key] = [
                        'unit_id' => $unitId,
                        'file_id' => $fileId,
                        'value' => $value
                    ];
                }
            }
        }

        Log::debug('Updating param', [
            'name'        => $paramName,
            'from_id'     => $id,
            'to_id'       => $newParamNameId,
            'group_links' => $validGroupLinks,
            'values'      => $validValues,
            'additional'  => $additional,
            'checked'     => $checked,
        ]);

        // Обновляем данные
        $result = $techParam->set($paramName, $id, $newParamNameId, $validGroupLinks, $validValues, $additional, $checked);

        if ($result === true) {
            return response()->json([
                'success' => true,
                'message' => 'Параметр успешно обновлен'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: '.$result
            ]);
        }
    }

    /**
     * Создание новой группы
     */
    public function group_create(Request $request)
    {
        $techParam = new TechParam();

        try {
            $request->validate([
                'name' => 'required|string|max:255'
            ]);
            
            $name = preg_replace('/\s+/', ' ', trim($request->name));
            $name = trim($name);
            
            $connection = DB::connection('livemachines');
            
            // Проверяем, существует ли уже такая группа
            $exists = $techParam->get_group_info_from_name($name);
            
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Группа с таким названием уже существует'
                ]);
            }
            
            // Создаем новую группу
            $connection->insert("
                INSERT INTO `dirty_group` 
                SET `dirty_group_name` = ?, 
                    `dirty_group_dirty_type_id` = 1,
                    `dirty_group_add_user_id` = ?,
                    `dirty_group_add_date` = UNIX_TIMESTAMP()
            ", [$name, auth()->id()]);
            
            $newId = $connection->getPdo()->lastInsertId();
            
            return response()->json([
                'success' => true,
                'message' => 'Группа успешно создана',
                'group' => [
                    'id' => $newId,
                    'name' => $name
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error creating group: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании группы: ' . $e->getMessage()
            ]);
        }
    }





























    /**
     * Список моделей
     */
    public function model_list()
    {
        $dbLm = DB::connection('livemachines');
        $sql =
        "
        SELECT
            `modelId`,
            `modelName`,
            IF(`manufCount` > 0, `manufCount`, '') as `manufCount`,
            `fileCount`
        FROM
            (
                SELECT
                    `dirty_model_id`                 as `modelId`,
                    `dirty_model_name`               as `modelName`,
                    COUNT(DISTINCT `dirty_manuf_id`) as `manufCount`,
                    COUNT(DISTINCT `dirty_file_id`)  as `fileCount`
                FROM `dirty_model`
                    LEFT JOIN `dirty_model_file` ON (`dirty_model_id` = `dirty_model_file_dirty_model_id`)
                    LEFT JOIN `dirty_file`       ON (`dirty_file_id`  = `dirty_model_file_dirty_file_id`)
                    LEFT JOIN `dirty_manuf_file` ON (`dirty_file_id`  = `dirty_manuf_file_dirty_file_id`)
                    LEFT JOIN `dirty_manuf`      ON (`dirty_manuf_id` = `dirty_manuf_file_dirty_manuf_id`  AND `dirty_manuf_remove_user_id` = 0)
                WHERE 1
                    AND `dirty_model_remove_user_id` = 0
                    AND (dirty_file_id IS NULL OR `dirty_file_remove_user_id` = 0)
                GROUP BY
                    `modelId`
            ) as `tmp`
        ";
        $data = $dbLm->select($sql);
        return view('livemachines/model/list', [
            'title'       => 'Список справочников - Adoxa',
            'description' => '',
            'data'        => $data,
        ]);
    }

    /**
     * Список производителей
     */
    public function manuf_list()
    {
        $dbLm = DB::connection('livemachines');
        $sql =
        "
        SELECT
            `manufId`,
            `manufName`,
            `countryName`,
            IF(`fileCount` > 0, `fileCount`, '') as `fileCount`
        FROM
            (
                SELECT
                    `dirty_manuf_id`   as `manufId`,
                    `dirty_manuf_name` as `manufName`,
                    GROUP_CONCAT(DISTINCT IF(`dirty_country_name` IS NOT NULL, `dirty_country_name`, '-') SEPARATOR ', ') as `countryName`,
                    COUNT(DISTINCT `dirty_file_id`) as `fileCount`
                FROM `dirty_manuf`
                    LEFT JOIN `dirty_manuf_file`    ON (`dirty_manuf_id`   = `dirty_manuf_file_dirty_manuf_id`)
                    LEFT JOIN `dirty_file`          ON (`dirty_file_id`    = `dirty_manuf_file_dirty_file_id`)
                    LEFT JOIN `dirty_manuf_country` ON (`dirty_manuf_id`   = `dirty_manuf_country_dirty_manuf_id`   AND `dirty_manuf_country_remove_user_id` = 0)
                    LEFT JOIN `dirty_country`       ON (`dirty_country_id` = `dirty_manuf_country_dirty_country_id` AND `dirty_country_remove_user_id`       = 0)
                WHERE 1
                    AND `dirty_manuf_remove_user_id` = 0
                    AND (dirty_file_id IS NULL OR `dirty_file_remove_user_id` = 0)
                GROUP BY
                    `manufId`
            ) as `tmp`
        ";
        $data = $dbLm->select($sql);
        return view('livemachines/manuf/list', [
            'title'       => 'Список справочников - Adoxa',
            'description' => '',
            'data'        => $data,
        ]);
    }

    /**
     * Список стран
     */
    public function country_list()
    {
        $dbLm = DB::connection('livemachines');
        $sql =
        "
        SELECT
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
                GROUP BY
                    `countryId`
            ) as `tmp`
        ";
        $data = $dbLm->select($sql);
        return view('livemachines/country/list', [
            'title'       => 'Список справочников - Adoxa',
            'description' => '',
            'data'        => $data,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }
}
