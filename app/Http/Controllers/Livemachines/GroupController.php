<?php

/**
 * Контроллер для управления справочником групп
 * Группы имеют следующие типы:
 * 1 - Технические характеристики
 * 2 - Комплектации
 */

namespace App\Http\Controllers\Livemachines;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Livemachines\GroupModel;


class GroupController extends Controller {
    private $dbConnection;
    private $groupModel;

    /**
     * Подключение к БД и инициализация моделей
     */
    public function __construct() {
        $this->dbConnection = DB::connection('livemachines');
        $this->groupModel = new GroupModel([], $this->dbConnection);
    }

    /**
     * Основная страница
     */
    public function index() {
        return view('livemachines/group', [
            'title' => 'Справочник групп параметров'
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

        // Тип
        $typeId = (int)$request->get('type_id', 0);

        // Параметры DataTable
        $draw        = $request->get('draw');
        $start       = (int)$request->get('start', 0);
        $length      = (int)$request->get('length', 10);
        $search      = $request->get('search')['value'] ?? '';
        $orderColumn = $request->get('order')[0]['column'] ?? 0;
        $orderDir    = $request->get('order')[0]['dir'] ?? 'asc';

        // Получаем список стран
        $result = $this->groupModel->get_list($typeId, false, $search, $start, $length, $orderColumn, $orderDir);
        
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
            $typeId = $validAndPrepareData['typeId'];
            $name   = $validAndPrepareData['name'];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: '.$validAndPrepareData
            ]);
        }

        // Проверяем есть ли уже такая запись
        $id = $this->groupModel->get_id_from_name($typeId, $name);

        if (is_numeric($id)) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: запись уже существует'
            ]);
        }

        // Создаем группу
        $id = $this->groupModel->get_id_from_name($typeId, $name, true);

        if (is_numeric($id)) {
            return response()->json([
                'success' => true,
                'message' => '',
                'group' => [
                    'id'   => $id,
                    'name' => mb_strtoupper($name)
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: '.$id
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

        // Тип
        $typeId = (int)$request->get('type_id', 0);

        Log::debug('type_id: '.$typeId);

        // Проверяем, это создание нового параметра?
        $isNew = $request->get('new') === 'true' || $id === null;

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
        $info = $this->groupModel->get_info_from_id($id);

        if (!$info) {
            return response()->json([
                'success' => false,
                'message' => 'Параметр не найден'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'   => $info->id,
                'name' => $info->name,
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
            $name = $validAndPrepareData['name'];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: '.$validAndPrepareData
            ]);
        }

        // Обновляем даные
        $result = $this->groupModel->edit($id, $name);

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

        $result = $this->groupModel->remove($id);

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
            'type_id' => 'integer|in:1,2',
            'name'    => 'required|string|max:255',
        ]);

        // Тип
        $typeId = (int)$request->type_id ?? 0;

        // Наименование
        $name = $request->name ?? '';
        $name = preg_replace('/\s+/', ' ', $name);

        return ['typeId' => $typeId, 'name' => $name];
    }
}
