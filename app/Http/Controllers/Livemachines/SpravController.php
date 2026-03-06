<?php

namespace App\Http\Controllers\Livemachines;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SpravController extends Controller
{
    /**
     * Список тех.характеристик
     */
    public function tech_list()
    {
        //echo '<pre>';
        //echo 'PHP timezone: ' . date_default_timezone_get() . "\n";
        //echo 'Current time: ' . date('Y-m-d H:i:s') . "\n";
        //echo 'Server timezone: ' . exec('date +%Z') . "\n";
        //echo '</pre>';
        //exit;

        $dbLm = DB::connection('livemachines');
        $sql =
        "
        (
            SELECT
                'none' as `id`,
                'Без группы' as `name`
        )
        UNION
        (
            SELECT
                `dirty_group_id`   as `id`,
                `dirty_group_name` as `name`
            FROM `dirty_group`
                LEFT JOIN `dirty_param` ON (`dirty_group_id` = `dirty_param_dirty_group_id` AND `dirty_group_dirty_type_id` = `dirty_param_dirty_type_id` AND `dirty_param_remove_user_id` = 0)
            WHERE 1
                AND `dirty_group_dirty_type_id`  = 1
                AND `dirty_group_remove_user_id` = 0
            GROUP BY
                `id`
            ORDER BY
                `name` ASC
        )
        ";
        $groups = $dbLm->select($sql);
        return view('livemachines/tech/list', [
            'title'       => 'Список справочников - Adoxa',
            'description' => '',
            'groups'      => $groups,
        ]);
    }

    /**
     * Get data for AJAX
     */
    public function tech_data_ajax(Request $request)
    {
        $dbLm = DB::connection('livemachines');
        $pdo = $dbLm->getPdo();

        if ($request->get('group_id') == 'none')
        {
            $whereGroup = "AND `dirty_param_dirty_group_id` = 0";
        }
        else
        {
            $groupId    = (int)$request->get('group_id');
            $whereGroup = ($groupId > 0) ? "AND `dirty_param_dirty_group_id` = ".$pdo->quote((int)$groupId) : "";
            //$innerGroup = ($groupId > 0) ? "INNER JOIN `dirty_param` ON (`dirty_param_name_id` = `dirty_param_dirty_param_name_id` AND `dirty_param_dirty_type_id` = `dirty_param_name_dirty_type_id` AND `dirty_param_remove_user_id` = 0 AND `dirty_param_dirty_group_id` = ".$pdo->quote((int)$groupId).")" : "";
        }
        
        if (config('app.debug')) {
            usleep(200000);
        }
        
        $sql =
        "
        SELECT
            `dirty_param_name_id`    as `paramNameId`,
            `dirty_param_name_value` as `paramName`,
            GROUP_CONCAT(DISTINCT IF(`dirty_group_name` IS NOT NULL, `dirty_group_name`, '-') SEPARATOR '<br><br>') as `groups`,
            GROUP_CONCAT(DISTINCT `dirty_file_name`  SEPARATOR '<br>') as `files`

            #COUNT(DISTINCT `dirty_file_id`) as `fileCount`
        FROM `dirty_param_name`
            LEFT JOIN `dirty_param` ON (`dirty_param_name_id` = `dirty_param_dirty_param_name_id` AND `dirty_param_dirty_type_id` = `dirty_param_name_dirty_type_id` AND `dirty_param_remove_user_id` = 0)
            LEFT JOIN `dirty_file` ON (`dirty_file_id` = `dirty_param_dirty_file_id`)
            LEFT JOIN `dirty_group` ON (`dirty_group_id` = `dirty_param_dirty_group_id` AND `dirty_group_dirty_type_id` = `dirty_param_name_dirty_type_id`)
        WHERE 1
            AND `dirty_param_name_dirty_type_id` = 1
            #AND `dirty_param_name_value` NOT REGEXP '[а-яА-Яa-zA-Z]'
            AND (dirty_file_id IS NULL OR `dirty_file_remove_user_id` = 0)
            {$whereGroup}
        GROUP BY
            `paramNameId`
        ";
        
        $data = $dbLm->select($sql);
        
        return response()->json(['data' => $data]);
    }

    /**
     * Remove the specified resource from storage.
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
            $dbLm = DB::connection('livemachines');
            
            // Получаем основную информацию о параметре
            $sql = "
                SELECT
                    `dirty_param_name_id` as `id`,
                    `dirty_param_name_value` as `name`,
                    `dirty_param_name_dirty_type_id` as `type_id`
                FROM `dirty_param_name`
                WHERE `dirty_param_name_id` = ?
                    AND `dirty_param_name_dirty_type_id` = 1
            ";
            
            $param = $dbLm->selectOne($sql, [$id]);
            
            if (!$param) {
                return response()->json([
                    'success' => false,
                    'message' => 'Параметр не найден'
                ], 404);
            }
            
            // Получаем группы, к которым привязан параметр
            $groupsSql = "
                SELECT
                    `dirty_group_id`   as `id`,
                    `dirty_group_name` as `name`
                FROM `dirty_param`
                INNER JOIN `dirty_group` ON (`dirty_group_id` = `dirty_param_dirty_group_id` AND `dirty_group_remove_date` = 0)
                WHERE 1
                    AND `dirty_param_dirty_param_name_id` = ?
                    AND `dirty_param_dirty_type_id`       = 1
                    AND `dirty_param_remove_date`         = 0
                GROUP BY
                    `dirty_group_id`
                ORDER BY
                    `name` ASC
            ";
            
            $groups = $dbLm->select($groupsSql, [$id]);

            // ЗАГЛУШКА
            if (config('app.debug')) {
                usleep(500000);
            }
            
            // Получаем единицы измерения и значения
            $valuesSql = "
                SELECT
                   `dirty_param_unit_id`      as `unit_id`,
                    `dirty_param_unit_value`  as `unit_name`,
                    `dirty_param_value_id`    as `value_id`,
                    `dirty_param_value_value` as `value`,
                    '' as `value_text`,
                    0  as `file_id`
                FROM `dirty_param`
                    INNER JOIN `dirty_param_unit`  ON (`dirty_param_unit_id`  = `dirty_param_dirty_param_unit_id`  AND `dirty_param_unit_dirty_type_id`  = `dirty_param_dirty_type_id`)
                    LEFT  JOIN `dirty_param_value` ON (`dirty_param_value_id` = `dirty_param_dirty_param_value_id` AND `dirty_param_value_dirty_type_id` = `dirty_param_dirty_type_id`)
                WHERE 1
                    AND `dirty_param_dirty_param_name_id` = ?
                    AND `dirty_param_dirty_type_id`       = 1
                    AND `dirty_param_remove_date`         = 0
                GROUP BY
                    `unit_id`
            ";
            
            $values = $dbLm->select($valuesSql, [$id]);
            
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
     * Получить справочники для формы редактирования (РЫБА)
     */
    public function tech_get_references()
    {
        // ВРЕМЕННЫЕ СТАТИЧНЫЕ ДАННЫЕ - ЗАМЕНИТЕ ПОТОМ НА РЕАЛЬНЫЕ

        try {
            $dbLm = DB::connection('livemachines');

            $groupsSql = "
                SELECT
                    `dirty_group_id`   as `id`,
                    `dirty_group_name` as `name`
                FROM `dirty_group`
                WHERE 1
                    AND `dirty_group_dirty_type_id` = 1
                    AND `dirty_group_remove_date`   = 0
                GROUP BY
                    `id`
                ORDER BY
                    `name` ASC
            ";
            
            $groups = $dbLm->select($groupsSql);

            $unitsSql = "
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
            ";
            
            $units = $dbLm->select($unitsSql);

        } catch(\Exeption $e) {
            Log::error('Error getting edit data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки данных: ' . $e->getMessage()
            ], 500);
        }
        
        //$units = [
        //    ['id' => 1, 'name' => 'шт', 'type' => 'integer'],
        //    ['id' => 2, 'name' => 'мм', 'type' => 'float'],
        //    ['id' => 3, 'name' => 'см', 'type' => 'float'],
        //    ['id' => 4, 'name' => 'м', 'type' => 'float'],
        //    ['id' => 5, 'name' => 'кг', 'type' => 'float'],
        //    ['id' => 6, 'name' => 'т', 'type' => 'float'],
        //    ['id' => 7, 'name' => 'л', 'type' => 'float'],
        //    ['id' => 8, 'name' => 'кВт', 'type' => 'float'],
        //    ['id' => 9, 'name' => 'л.с.', 'type' => 'float'],
        //    ['id' => 10, 'name' => 'об/мин', 'type' => 'integer'],
        //    ['id' => 11, 'name' => 'А', 'type' => 'float'],
        //    ['id' => 12, 'name' => 'В', 'type' => 'float'],
        //    ['id' => 13, 'name' => 'нет', 'type' => 'boolean'],
        //    ['id' => 14, 'name' => 'есть/нет', 'type' => 'boolean'],
        //    ['id' => 15, 'name' => 'текст', 'type' => 'text'],
        //];
        
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
        // ЗАГЛУШКА
        if (config('app.debug')) {
            usleep(500000);
        }

        return response()->json([
            'success' => true,
            'message' => 'Параметр успешно обновлен'
        ]);

        try {
            $dbLm = DB::connection('livemachines');
            $dbLm->beginTransaction();
            
            // Валидация
            $request->validate([
                'name' => 'required|string|max:255',
                'groups' => 'array',
                'values' => 'array'
            ]);
            
            // Обновляем название параметра
            $updateSql = "
                UPDATE `dirty_param_name`
                SET `dirty_param_name_value` = ?
                WHERE `dirty_param_name_id` = ?
                    AND `dirty_param_name_dirty_type_id` = 1
            ";
            
            $dbLm->update($updateSql, [$request->name, $id]);
            
            // Здесь будет логика обновления групп и значений
            // Пока просто заглушка
            
            $dbLm->commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Параметр успешно обновлен'
            ]);
            
        } catch (\Exception $e) {
            $dbLm->rollBack();
            
            Log::error('Error updating param: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления: ' . $e->getMessage()
            ], 500);
        }
    }

    // ---------------------
































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
