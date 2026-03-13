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
        return view('livemachines/tech/list', ['groups' => $techParam->get_groups()]);
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

            //Log::info('Delete method called', ['id' => $id, 'method' => request()->method(), 'user_id' => auth()->id(), 'record' => $record, 'fileIds' => $fileIds]);

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


    // ---------------------

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
            
            // Получаем группы, к которым привязан параметр
            $groups = $techParam->get_groups($id);

            // ЗАГЛУШКА
            if (config('app.debug')) {
                usleep(500000);
            }
            
            // Получение списка всех единиц измерения и значений для указаноого параметра
            $values = $techParam->get_units_and_values($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'param' => $param,
                    'groups' => $groups,
                    'values' => $values
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

        } catch(\Exeption $e) {
            Log::error('Error getting edit data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки данных: ' . $e->getMessage()
            ], 500);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'groups' => $groups,
                'units'  => $units
            ]
        ]);
    }

    /**
     * Обновление параметра
     */
    public function tech_update(Request $request, $id)
    {
        $techParam = new TechParam();
        
        // ЗАГЛУШКА
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
            'groups' => 'array',
            'values' => 'array'
        ]);

        // Наименование параметра (начало)
        $paramInfo = $techParam->get_info_from_id($id);

        $result = false;

        if (!$paramInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Парематр не найден'
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

        // Группа
        $groups   = $request->groups ?? [];
        $groupIds = [];
        foreach($groups as $groupId) {
            $groupId = (int)$groupId;
            if ($groupId > 0 && !in_array($groupId, $groupIds) && $techParam->get_group_info_from_id($groupId)) {
                $groupIds[] = $groupId;
            }
        }

        // Единицы измерения и значения
        $values   = $request->values ?? [];
        $valueArr = [];
        foreach($values as $row) {
            $unitId = (int)$row['unit_id'];
            $value  = (string)trim(preg_replace('/\s+/', ' ', $row['value']));
            $valueLower = mb_strtolower($value);
            $key = $unitId . '-|||-' . $valueLower;
            if ($valueLower <> '' && (($unitId == 0 || ($unitId > 0 && 1 > 0)) && !isset($valueArr[$key]))) {
                $valueArr[$key] = ['unit_id' => $unitId, 'value' => $value];
            }
        }

        Log::debug($valueArr);

        //======= ЗАГЛУШКА ========
        //return response()->json([
        //    'success' => true,
        //    'message' => 'Параметр успешно обновлен'
        //]);

        // Обновляем данные
        $result = $techParam->set($paramName, $id, $newParamNameId, $groupIds, []);

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

    // ---------------------



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
