<?php

/*
    ЭТО НЕ РАБОЧИЙ МОДУЛЬ
    СЮДА ДОБАВЛЯЮ ЧТОБЫ НЕ ПОТЕРЯТЬ ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ
*/

namespace App\Http\Controllers\Livemachines;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\CacheService; // ДЛЯ ПОДКЛЮЧЕНИЯ КЭШИРОВАНИЯ В REDIS SQL-РЕЗУЛЬТАТОВ С ВОЗМОЖНОСТЬЮ СЖАТИЯ ДАННЫХ

class Utils extends Controller
{
    // Пример проверки текущего времени на сервере
    public function test_time()
    {
        echo '<pre>';
        echo 'PHP timezone: '    . date_default_timezone_get() . "\n";
        echo 'Current time: '    . date('Y-m-d H:i:s') . "\n";
        echo 'Server timezone: ' . exec('date +%Z') . "\n";
        echo '</pre>';
        exit;
    }

    // Пример кэширования данных SQL-запроса
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
        
        // Маппинг колонок для сортировки
        $columns = [
            0 => 'paramName',
            1 => 'groups',
            2 => 'files',
        ];
        
        // КЛЮЧ КЭША
        $cacheKey = 'tech_data_' . md5(
            $groupId . '_' . 
            $search . '_' . 
            $orderColumn . '_' . 
            $orderDir . '_' .
            $start . '_' . 
            $length
        );
        
        $tags       = ['livemachines', 'tech_data'];
        $versionKey = 'dirty_tables_version';

        //CacheService::clearTags(['livemachines', 'tech_data']); // ДЛЯ ОЧИСТКИ КЭША
        
        $result = CacheService::remember(
            $cacheKey,
            $tags,
            function () use ($groupId, $search, $orderColumn, $orderDir, $start, $length, $columns) {
                
                $dbLm = DB::connection('livemachines');
                $pdo = $dbLm->getPdo();
                
                // Условие по группе
                if ($groupId == 'none') {
                    $sqlWhereGroup = "AND `dirty_param_dirty_group_id` = 0";
                } else {
                    $groupIdInt = (int)$groupId;
                    $sqlWhereGroup = ($groupIdInt > 0) ? "AND `dirty_param_dirty_group_id` = ".$pdo->quote($groupIdInt) : "";
                }

                // Условие по поиску
                $sqlWhereSearch = "";
                if (!empty($search)) {
                    $minFulltextLength = 3; // Минимальная длина для FULLTEXT (По умолчанию MySQL имеет параметр ft_min_word_len = 4 (для MyISAM) или innodb_ft_min_token_size = 3)
                    
                    // Удаляем пробелы и считаем длину первого слова
                    $firstWord  = trim(explode(' ', $search)[0]);
                    $wordLength = mb_strlen($firstWord);
                    
                    if ($wordLength >= $minFulltextLength) {
                        $searchTerm = addcslashes($search, '+-<>()~*"');
                        $sqlWhereSearch = "AND MATCH(`dirty_param_name_value`) AGAINST('{$searchTerm}' IN BOOLEAN MODE)";
                    } else {
                        $searchTerm = $pdo->quote('%' . $search . '%');
                        $sqlWhereSearch = "AND `dirty_param_name_value` LIKE {$searchTerm}";
                    }
                }

                // Сортировка
                if (isset($columns[$orderColumn])) {
                    $orderField = $columns[$orderColumn];
                    $sqlSort    = " ORDER BY `{$orderField}` {$orderDir}";
                } else {
                    $sqlSort = "";
                }
                
                // ===== БАЗОВЫЙ ЗАПРОС =====
                $baseSql = "
                    SELECT
                        SQL_CALC_FOUND_ROWS
                        `dirty_param_name_id`    as `paramNameId`,
                        `dirty_param_name_value` as `paramName`,
                        GROUP_CONCAT(DISTINCT IF(`dirty_group_name` IS NOT NULL, `dirty_group_name`, '-') SEPARATOR '<br><br>') as `groups`,
                        GROUP_CONCAT(DISTINCT `dirty_file_name`  SEPARATOR '<br>') as `files`
                    FROM `dirty_param_name`
                        LEFT JOIN `dirty_param` ON (1
                            AND `dirty_param_name_id`        = `dirty_param_dirty_param_name_id` 
                            AND `dirty_param_dirty_type_id`  = `dirty_param_name_dirty_type_id` 
                            AND `dirty_param_remove_user_id` = 0
                        )
                        LEFT JOIN `dirty_file`  ON (`dirty_file_id` = `dirty_param_dirty_file_id`)
                        LEFT JOIN `dirty_group` ON (1
                            AND `dirty_param_dirty_group_id` = `dirty_group_id`
                            AND `dirty_group_dirty_type_id`  = `dirty_param_name_dirty_type_id`
                        )
                    WHERE 1
                        AND `dirty_param_name_dirty_type_id` = 1
                        AND (`dirty_file_id` IS NULL OR `dirty_file_remove_user_id` = 0)
                        {$sqlWhereGroup}
                        {$sqlWhereSearch}
                    GROUP BY `paramNameId`
                    {$sqlSort}
                    LIMIT {$start}, {$length}
                ";
                
                // Выполняем финальный запрос
                $data = $dbLm->select($baseSql);

                $filteredResult = $dbLm->selectOne("SELECT FOUND_ROWS() as `total`");
                $totalRecords   = $filteredResult->total ?? 0;
                
                return [
                    'data'  => $data,
                    'total' => $totalRecords,
                ];
                
            },
            $versionKey,
            3600,
            6
        );
        
        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $result['total'],
            'recordsFiltered' => $result['total'],
            'data' => $result['data']
        ]);
    }
}