<?php

/**
 * Контроллер для работы со справочниками параметров, ед.измерения и значений комплектаций
 */

namespace App\Http\Controllers\Livemachines;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Livemachines\ParamModel;
use App\Models\Livemachines\GroupModel;

class CompController extends Controller {
    private $techParam = null;
    private $dbConnection;
    private $paramTypeId = 2; // Комплектации

    public function __construct() {
        $this->dbConnection = DB::connection('livemachines');
        $this->paramModel   = new ParamModel([], $this->dbConnection, $this->paramTypeId);
        $this->groupModel   = new GroupModel([], $this->dbConnection, $this->paramTypeId);
    }

    /**
     * Страница с техническими характеристиками
     */
    public function list() {
        return view('livemachines/comp', [
            'title'  => 'Справочник комплектаций',
            'groups' => $this->groupModel->get_groups(),
        ]);
    }

    /**
     * AJAX -> JSON
     * Получение списка технических параметров
     */
    public function data_ajax(Request $request) {
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
        $result = $this->paramModel->get_list($groupId, $additional, $start, $length, $search, $orderColumn, $orderDir);
        
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
    public function destroy(int $id) {
        $result = $this->paramModel->remove($id);

        if ($result === true) {
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
        } else {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $result
                ], 500);
            }
            
            return redirect()
                ->route('lm_tech.list')
                ->with('error', $result);
        }
    }

    /**
     * Получить данные для редактирования
     */
    public function edit_data(Request $request, $paramNameId = null) {
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

            $param = $this->paramModel->get_info_from_id($paramNameId); // Получаем основную информацию о параметре
            
            if (!$param) {
                return response()->json([
                    'success' => false,
                    'message' => 'Параметр не найден'
                ], 404);
            }
            
            $groupLinks = $this->paramModel->get_param_group_links($paramNameId, $additional); // Получаем привязки к группам с информацией о файлах
            $checked    = $this->paramModel->get_param_checked($paramNameId);                  // Получаем дополнительную информацию о параметре (checked)
            $values     = $this->paramModel->get_units_and_values($paramNameId, $additional);  // Получаем все значения с привязкой к файлам
            $paramFiles = $this->paramModel->get_param_files($paramNameId, $additional);       // Получаем список всех файлов для параметра
            
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
    public function get_references() {
        try {
            $groups = $this->groupModel->get_groups(0, false); // Получение списка всех групп технических характеристик
            $units  = $this->paramModel->get_units();          // Получение списка всех единиц измерения
            $files  = $this->paramModel->get_files();          // Получение списка всех файлов
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
    private function validate_and_prepare($request, $paramNameId = null) {
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
            $paramInfo = $this->paramModel->get_info_from_id($paramNameId);
            $newParamNameId = $paramNameId;
            if (!$paramInfo) return 'Параметр не найден';

            // Редактирование существующего параметра
            $paramInfo = $this->paramModel->get_info_from_id($paramNameId);
            if (!$paramInfo) return 'Параметр не найден';
            
            // Проверяем, не занято ли новое название другим параметром
            $existingParam = $this->paramModel->get_info_from_name($paramName, false);
            
            if ($existingParam && $existingParam->id != $paramNameId) {
                // Название уже принадлежит другому параметру - объединяем
                $newParamNameId = $existingParam->id;
            } else {
                // Название свободно или принадлежит текущему параметру
                $newParamNameId = $paramNameId;
            }
        } else {
            $paramInfo      = $this->paramModel->get_info_from_name($paramName, true);
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
    public function create(Request $request)
    {
        // Валидируем и подготавливаем входные данные
        $validAndPrepareData = $this->validate_and_prepare($request, 'new');

        if (is_string($validAndPrepareData)) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $validAndPrepareData
            ]);
        }

        // Обновляем данные
        $result = $this->paramModel->create(
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
    public function update(Request $request, $paramNameId) {
        // Валидируем и подготавливаем входные данные
        $validAndPrepareData = $this->validate_and_prepare($request, $paramNameId);

        if (is_string($validAndPrepareData)) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $validAndPrepareData
            ]);
        }

        // Обновляем данные
        $result = $this->paramModel->set(
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
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $name = $request->name ?? '';
        $name = preg_replace('/\s+/', ' ', trim($name));
        $name = trim($name);

        if (mb_strlen($name) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Не указано название группы'
            ]);
        }

        $groupId = $this->groupModel->create($name);

        if (is_numeric($groupId)) {
            return response()->json([
                'success' => true,
                'message' => 'Группа успешно создана',
                'group' => [
                    'id'   => $groupId,
                    'name' => $name
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $groupId
            ]);
        }
    }
}
