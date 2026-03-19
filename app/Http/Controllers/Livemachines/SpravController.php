<?php

namespace App\Http\Controllers\Livemachines;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Livemachines\TechParam;

class SpravController extends Controller {
    private $techParam = null;
    private $dbConnection;

    public function __construct() {
        $this->dbConnection = DB::connection('livemachines');
        $this->techParam    = new TechParam([], $this->dbConnection);
    }

    /**
     * Страница с техническими характеристиками
     */
    public function tech_list() {
        return view('livemachines/tech/list', [
            'groups' => $this->techParam->get_groups(),
        ]);
    }

    /**
     * AJAX -> JSON
     * Получение списка технических параметров
     */
    public function tech_data_ajax(Request $request) {
        // Параметры DataTable
        $draw        = $request->get('draw');
        $start       = (int)$request->get('start', 0);
        $length      = (int)$request->get('length', 10);
        $search      = $request->get('search')['value'] ?? '';
        $orderColumn = $request->get('order')[0]['column'] ?? 0;
        $orderDir    = $request->get('order')[0]['dir'] ?? 'asc';

        // Фильтры
        $groupId    = $request->get('group_id', 'none');
        $additional = $request->get('additional', 0);

        // Получаем список технических параметров
        $result = $this->techParam->get_list($groupId, $additional, $start, $length, $search, $orderColumn, $orderDir);
        
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
    public function tech_destroy(int $id) {
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
    public function tech_edit_data(Request $request, $paramNameId = null) {
        try {
            // Проверяем, это создание нового параметра?
            $isNew = $request->get('new') === 'true' || $paramNameId === null;

            // Получаем значение additional из запроса
            $additional = $request->get('additional', '0'); // По умолчанию '0'

            if ($isNew) {
                // Валидация
                $request->validate(['additional' => 'integer|in:0,1']);

                // Для нового параметра возвращаем пустые данные
                return response()->json([
                    'success' => true,
                    'data'    => [
                        'param' => [
                            'id'      => null,
                            'name'    => '',
                            'type_id' => 1,
                        ],
                        'group_links' => [],
                        'values'      => [],
                        'param_files' => [],
                        'additional'  => $additional,
                        'checked'     => 0,
                    ]
                ]);
            }

            // Валидация
            $request->validate([
                'id'         => 'integer',
                'additional' => 'integer|in:0,1'
            ]);

            $param = $this->techParam->get_info_from_id($paramNameId); // Получаем основную информацию о параметре
            
            if (!$param) {
                return response()->json([
                    'success' => false,
                    'message' => 'Параметр не найден'
                ], 404);
            }
            
            $groupLinks = $this->techParam->get_param_group_links($paramNameId, $additional); // Получаем привязки к группам с информацией о файлах
            $checked    = $this->techParam->get_param_checked($paramNameId);                  // Получаем дополнительную информацию о параметре (checked)
            $values     = $this->techParam->get_units_and_values($paramNameId, $additional);  // Получаем все значения с привязкой к файлам
            $paramFiles = $this->techParam->get_param_files($paramNameId, $additional);       // Получаем список всех файлов для параметра
            
            return response()->json([
                'success' => true,
                'data'    => [
                    'param'       => $param,
                    'group_links' => $groupLinks,
                    'values'      => $values,
                    'param_files' => $paramFiles,
                    'additional'  => $additional,
                    'checked'     => $checked,
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
    public function tech_get_references() {
        try {
            $groups = $this->techParam->get_groups(0, false); // Получение списка всех групп технических характеристик
            $units  = $this->techParam->get_units();          // Получение списка всех единиц измерения
            $files  = $this->techParam->get_files();          // Получение списка всех файлов
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
     * Валидация и подготовка входных данных
     */
    private function tech_validate_and_prepare($request, $paramNameId = null) {
        Log::debug($request);

        // Искусственная задержка (для режима разработки)
        if (config('app.debug')) {
            usleep(500000);
        }

        if ($paramNameId == 'new') {
            $paramNameId = 0;
        } elseif (is_numeric($paramNameId) && (int)$paramNameId > 0) {
            $paramNameId = (int)$paramNameId;
        } else {
            return 'Неверный идентификатор параметра';
        }

        // Очищаем от пробелов входные данные
        $request->merge([
            'name' => trim($request->name ?? '')
        ]);

        // Валидация
        $request->validate([
            'name'              => 'required|string|max:255',
            'group_links'       => 'array',
            'values'            => 'array',
            'additional'        => 'integer|in:0,1',
            'checked'           => 'integer|in:0,1',
            'additional_filter' => 'integer|in:0,1',
        ]);

        $paramName = $request->name ?? '';
        $paramName = preg_replace('/\s+/', ' ', $request->name);

        // Наименование параметра
        if ($paramNameId > 0) {
            $paramInfo = $this->techParam->get_info_from_id($paramNameId);
            $newParamNameId = $paramNameId;
            if (!$paramInfo) return 'Параметр не найден';

            // Редактирование существующего параметра
            $paramInfo = $this->techParam->get_info_from_id($paramNameId);
            if (!$paramInfo) return 'Параметр не найден';
            
            // Проверяем, не занято ли новое название другим параметром
            $existingParam = $this->techParam->get_info_from_name($paramName, false);
            
            if ($existingParam && $existingParam->id != $paramNameId) {
                // Название уже принадлежит другому параметру - объединяем
                $newParamNameId = $existingParam->id;
            } else {
                // Название свободно или принадлежит текущему параметру
                $newParamNameId = $paramNameId;
            }
        } else {
            $paramInfo      = $this->techParam->get_info_from_name($paramName, true);
            $paramNameId    = $paramInfo->id;
            $newParamNameId = $paramNameId;
        }

        // Подготовка флагов
        $additional = $request->additional ?? 0; // дополнительный параметр
        $checked    = $request->checked    ?? 0; // статус проверки

        // Фильтр вида параметра
        $additionalFilter = $request->additional_filter ?? 0;

        // Привязки к группам
        $validGroups = [];
        $groups      = $request->group_links ?? [];
        
        foreach($groups as $group) {
            $paramId = $group['param_id'];
            $groupId = (int)$group['group_id'];
            $fileId  = (int)$group['file_id'];

            $el = explode('_', $paramId);

            if (count($el) == 2 && $el[0] == 'param' && is_numeric($el[1]) && $el[1] > 0){
                $paramId = $el[1];
            } elseif (count($el) == 3 && $el[0] == 'new' && $el[1] == 'param' && is_numeric($el[2]) && $el[2] > 0) {
                $paramId = 'new_' . $el[2];
            } else {
                return 'Ошибка в параметре группы';
            }

            $validGroups[$paramId] = [
                'groupId' => $groupId,
                'fileId'  => $fileId,
            ];
        }

        //Log::debug($validGroups);

        // Единицы измерения и значения
        $values = $request->values ?? [];
        $validValues = [];
        
        foreach($values as $row) {
            $paramId = $row['param_id'];
            $unitId  = (int)$row['unit_id'];
            $fileId  = (int)$row['file_id'];

            $valueRaw = $row['value'] ?? '';
            $value    = (string)trim(preg_replace('/\s+/', ' ', $valueRaw));

            $el = explode('_', $paramId);

            if (count($el) == 2 && $el[0] == 'param' && is_numeric($el[1]) && $el[1] > 0){
                $paramId = $el[1];
            } elseif (count($el) == 3 && $el[0] == 'new' && $el[1] == 'param' && is_numeric($el[2]) && $el[2] > 0) {
                $paramId = 'new_' . $el[2];
            } else {
                return 'Ошибка в ед.измерения и/или значениях';
            }

            $validValues[$paramId] = [
                'unitId' => $unitId,
                'value'  => $value,
                'fileId' => $fileId,
            ];
        }

        //Log::debug($validValues);

        $validParams = [];

        foreach($validValues as $key => $validValue)
        {
            if (isset($validGroups[$key])) {
                $validParams[$key] = array_merge($validGroups[$key], $validValue);
            }
        }

        //Log::debug($validParams);

        return [
            'paramName'        => $paramName,
            'paramNameId'      => $paramNameId,
            'newParamNameId'   => $newParamNameId,
            'validParams'      => $validParams,
            'additionalFilter' => $additionalFilter,
            'additional'       => $additional,
            'checked'          => $checked
        ];
    }

    /**
     * Создание нового параметра
     */
    public function tech_create(Request $request)
    {
        // Валидируем и подготавливаем входные данные
        $validAndPrepareData = $this->tech_validate_and_prepare($request, 'new');

        if (is_string($validAndPrepareData)) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $validAndPrepareData
            ]);
        }

        // Обновляем данные
        $result = $this->techParam->create(
            $validAndPrepareData['paramName'],
            $validAndPrepareData['paramNameId'],
            $validAndPrepareData['newParamNameId'],
            $validAndPrepareData['validParams'],
            $validAndPrepareData['additionalFilter'],
            $validAndPrepareData['additional'],
            $validAndPrepareData['checked']
        );

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
     * Обновление параметра
     */
    public function tech_update(Request $request, $paramNameId) {
        // Валидируем и подготавливаем входные данные
        $validAndPrepareData = $this->tech_validate_and_prepare($request, $paramNameId);

        if (is_string($validAndPrepareData)) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $validAndPrepareData
            ]);
        }

        // Обновляем данные
        $result = $this->techParam->set(
            $validAndPrepareData['paramName'],
            $validAndPrepareData['paramNameId'],
            $validAndPrepareData['newParamNameId'],
            $validAndPrepareData['validParams'],
            $validAndPrepareData['additionalFilter'],
            $validAndPrepareData['additional'],
            $validAndPrepareData['checked']
        );

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
    public function group_create(Request $request) {
        try {
            $request->validate([
                'name' => 'required|string|max:255'
            ]);
            
            $name = $request->name ?? '';
            $name = preg_replace('/\s+/', ' ', trim($name));
            $name = trim($name);
            
            $connection = DB::connection('livemachines');
            
            // Проверяем, существует ли уже такая группа
            $exists = $this->techParam->get_group_info_from_name($name);
            
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
