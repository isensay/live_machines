<?php

/**
 * Контроллер для управления справочником стран
 */

namespace App\Http\Controllers\Livemachines;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Livemachines\CountryModel;


class CountryController extends Controller {
    private $techParam = null;
    private $dbConnection;
    private $paramTypeId = 1; // Технические характеристики
    private $countryModel;

    public function __construct() {
        $this->dbConnection = DB::connection('livemachines');
        $this->countryModel = new CountryModel([], $this->dbConnection, $this->paramTypeId);
    }

    /**
     * Список стран (основная страница)
     */
    public function index() {
        return view('livemachines/country', [
            'title' => 'Справочник стран'
        ]);
    }

    /**
     * AJAX -> JSON
     * Получение списка стран
     */
    public function data_ajax(Request $request) {
        // Искусственная задержка (для режима разработки)
        if (config('app.debug')) {
            usleep(500000);
        }

        // Параметры DataTable
        $draw        = $request->get('draw');
        $start       = (int)$request->get('start', 0);
        $length      = (int)$request->get('length', 10);
        $search      = $request->get('search')['value'] ?? '';
        $orderColumn = $request->get('order')[0]['column'] ?? 0;
        $orderDir    = $request->get('order')[0]['dir'] ?? 'asc';

        // Получаем список стран
        $result = $this->countryModel->get_list($search, $start, $length, $orderColumn, $orderDir);
        
        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $result['total'],
            'recordsFiltered' => $result['total'],
            'data'            => $result['data']
        ]);
    }

    /**
     * Получить данные для редактирования
     */
    public function edit_data(Request $request, int $countryId) {
        // Искусственная задержка (для режима разработки)
        if (config('app.debug')) {
            usleep(500000);
        }

        // Проверяем, это создание нового параметра?
        $isNew = $request->get('new') === 'true' || $countryId === null;

        if ($isNew) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id'   => null,
                    'name' => '',
                ]
            ]);
        }

        // Валидация
        $request->validate([
            'id' => 'integer',
        ]);

        // Получаем информацию
        $country = $this->countryModel->get_info_from_id($countryId);

        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Параметр не найден'
            ], 404);
        }

        // ЗАГЛУШКА
        return response()->json([
            'success' => true,
            'data' => [
                'id'   => $country->id,
                'name' => $country->name,
            ]
        ]);

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
     * Валидация и подготовка входных данных
     */
    private function validate_and_prepare($request, $countryId = null) {
        Log::debug($request);

        // Искусственная задержка (для режима разработки)
        if (config('app.debug')) {
            usleep(500000);
        }

        if ($countryId == 'new') {
            $countryId = 0;
        } elseif (is_numeric($countryId) && (int)$countryId > 0) {
            $countryId = (int)$countryId;
        } else {
            return 'Неверный идентификатор параметра';
        }

        // Очищаем от пробелов входные данные
        $request->merge([
            'name' => trim($request->name ?? '')
        ]);

        // Валидация
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $countryName = $request->name ?? '';
        $countryName = preg_replace('/\s+/', ' ', $countryName);

        return ['name' => $countryName];
    }

    /**
     * Сохранение изменений имеющейся записи
     */
    public function update(Request $request, $countryId) {
        // Искусственная задержка (для режима разработки)
        if (config('app.debug')) {
            usleep(500000);
        }

        // Валидируем и подготавливаем входные данные
        $validAndPrepareData = $this->validate_and_prepare($request, $countryId);

        if (is_array($validAndPrepareData)) {
            $countryName = $validAndPrepareData['name'];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: '.$validAndPrepareData
            ]);
        }

        // Обновляем данные
        $result = $this->countryModel->set($countryId, $countryName);

        if ($result === true) {
            return response()->json([
                'success' => true,
                'message' => ''
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: '.$result
            ]);
        }

        if (is_string($validAndPrepareData)) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $validAndPrepareData
            ]);
        }

        // Обновляем данные
        $result = $this->countryModel->set($validAndPrepareData['name']);

        if ($result === true) {
            return response()->json([
                'success' => true,
                'message' => ''
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: '.$result
            ]);
        }
    }

    /**
     * Удаление страны
     */
    public function destroy(int $id) {
        // Искусственная задержка (для режима разработки)
        if (config('app.debug')) {
            usleep(500000);
        }

        $result = $this->countryModel->remove($id);

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
}
