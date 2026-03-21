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
     * Получение списка стран
     */
    public function data(Request $request) {
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
     * Валидация и подготовка входных данных
     */
    private function validate_and_prepare($request, $countryId = null) {
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
     * Создание новой записи
     */
    public function create(Request $request) {
        // Искусственная задержка (для режима разработки)
        if (config('app.debug')) {
            usleep(500000);
        }

        // Валидируем и подготавливаем входные данные
        $validAndPrepareData = $this->validate_and_prepare($request, 'new');

        if (is_array($validAndPrepareData)) {
            $countryName = $validAndPrepareData['name'];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: '.$validAndPrepareData
            ]);
        }

        // Проверяем есть ли уже такая запись
        $countryId = $this->countryModel->get_id_from_name($countryName);

        if (is_numeric($countryId)) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: запись уже существует'
            ]);
        }

        // Добавляем новую запись
        $result = $this->countryModel->create($countryName);

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
     * Получить данные для редактирования
     */
    public function edit(Request $request, int $countryId) {
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
        $request->validate(['id' => 'integer']);

        // Получаем информацию
        $country = $this->countryModel->get_info_from_id($countryId);

        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Параметр не найден'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'   => $country->id,
                'name' => $country->name,
            ]
        ]);
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

        // Обновляем даные
        $result = $this->countryModel->edit($countryId, $countryName);

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
