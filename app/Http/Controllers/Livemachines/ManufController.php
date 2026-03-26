<?php

/**
 * Контроллер для управления справочником производителей
 */

namespace App\Http\Controllers\Livemachines;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Livemachines\ManufModel;
use App\Models\Livemachines\CountryModel;


class ManufController extends Controller {
    private $dbConnection;
    private $manufModel;
    private $countryModel;

    /**
     * Подключение к БД и инициализация моделей
     */
    public function __construct() {
        $this->dbConnection = DB::connection('livemachines');
        $this->manufModel   = new ManufModel([],   $this->dbConnection);
        $this->countryModel = new CountryModel([], $this->dbConnection);
    }

    /**
     * Основная страница
     */
    public function index() {
        return view('livemachines/manuf', [
            'title'     => 'Справочник единиц измерения',
            'countries' => $this->countryModel->get_list()['data']
        ]);
    }

    /**
     * Получение списка
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
        $result = $this->manufModel->get_list($search, $start, $length, $orderColumn, $orderDir);
        
        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $result['total'],
            'recordsFiltered' => $result['total'],
            'data'            => $result['data']
        ]);
    }

    /**
     * Создание записи (сохранение)
     */
    public function create(Request $request) {
        // Искусственная задержка (для режима разработки)
        if (config('app.debug')) {
            usleep(500000);
        }

        // Валидируем и подготавливаем входные данные
        $validAndPrepareData = $this->validate_and_prepare($request, 'new');

        if (is_array($validAndPrepareData)) {
            $name    = $validAndPrepareData['name'];
            $country = $validAndPrepareData['country'];
        } else {
            return response()->json([
                'success' => false,
                'message' => $validAndPrepareData
            ]);
        }

        // Создаем запись
        $result = $this->manufModel->create($name, $country);

        if ($result === true) {
            return response()->json([
                'success' => true,
                'message' => ''
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $result
            ]);
        }
    }

    /**
     * Получение данных для редактирования (загрузка информации в окно)
     */
    public function edit(Request $request, int $id) {
        // Искусственная задержка (для режима разработки)
        if (config('app.debug')) {
            usleep(500000);
        }

        // Проверяем, это создание нового параметра?
        $isNew = $request->get('new') === 'true' || $id === null;

        if ($isNew) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id'      => null,
                    'name'    => '',
                    'country' => 0,
                ]
            ]);
        }

        // Валидация
        $request->validate(['id' => 'integer']);

        // Получаем информацию
        $info = $this->manufModel->get_info_from_id($id);

        if (!$info) {
            return response()->json([
                'success' => false,
                'message' => 'Параметр не найден'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'      => $info->id,
                'name'    => $info->name,
                'country' => $info->country
            ]
        ]);
    }

    /**
     * Сохранение изменений имеющейся записи (сохранение)
     */
    public function update(Request $request, $id) {
        // Искусственная задержка (для режима разработки)
        if (config('app.debug')) {
            usleep(500000);
        }

        // Валидируем и подготавливаем входные данные
        $validAndPrepareData = $this->validate_and_prepare($request, $id);

        if (is_array($validAndPrepareData)) {
            $name    = $validAndPrepareData['name'];
            $country = $validAndPrepareData['country'];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: '.$validAndPrepareData
            ]);
        }

        // Проверяем что есть такая страна
        if ($country > 0) {
            $dbCountry = $this->countryModel->get_info_from_id($country);
            if (!$dbCountry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка: страна не найдена'
                ]);
            }
        }

        // Обновляем даные
        $result = $this->manufModel->set($id, $name, $country);

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
     * Удаление записи
     */
    public function remove(int $id) {
        // Искусственная задержка (для режима разработки)
        if (config('app.debug')) {
            usleep(500000);
        }

        $result = $this->manufModel->remove($id);

        if ($result === true) {
            return response()->json([
                'success' => true,
                'message' => 'Запись успешно удалена',
                'id' => $id
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result
        ], 500);
    }

    /**
     * Валидация и подготовка входных данных
     */
    private function validate_and_prepare($request, $id = null) {
        Log::debug($request);
        if ($id == 'new') {
            $id = 0;
        } elseif (is_numeric($id) && (int)$id > 0) {
            $id = (int)$id;
        } else {
            return 'Неверный идентификатор параметра';
        }

        // Очищаем от пробелов входные данные
        $request->merge([
            'name' => trim($request->name ?? '')
        ]);

        // Валидация
        $request->validate([
            'name'    => 'required|string|max:255',
            'country' => 'integer',
        ]);

        // Наименование
        $name = $request->name ?? '';
        $name = preg_replace('/\s+/', ' ', $name);

        // Страна
        $country = (int)$request->country;

        return ['name' => $name, 'country' => $country];
    }
}
