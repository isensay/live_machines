<?php

/**
 * Контроллер для управления справочником групп
 */

namespace App\Http\Controllers\Livemachines;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Livemachines\GroupModel;


class GroupController extends Controller {
    private $techParam = null;
    private $dbConnection;
    private $groupModel;

    public function __construct() {
        $this->dbConnection = DB::connection('livemachines');
        $this->groupModel = new GroupModel([], $this->dbConnection);
    }

    /**
     * Список стран (основная страница)
     */
    public function index() {
        return view('livemachines/group', [
            'title' => 'Справочник групп параметров'
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
     * Валидация и подготовка входных данных
     */
    private function validate_and_prepare($request, $groupId = null) {
        if ($groupId == 'new') {
            $groupId = 0;
        } elseif (is_numeric($groupId) && (int)$groupId > 0) {
            $groupId = (int)$groupId;
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
        $groupName = $request->name ?? '';
        $groupName = preg_replace('/\s+/', ' ', $groupName);

        return ['typeId' => $typeId, 'name' => $groupName];
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
            $typeId    = $validAndPrepareData['typeId'];
            $groupName = $validAndPrepareData['name'];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: '.$validAndPrepareData
            ]);
        }

        // Проверяем есть ли уже такая запись
        $groupId = $this->groupModel->get_id_from_name($typeId, $groupName);

        if (is_numeric($groupId)) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: запись уже существует'
            ]);
        }

        // Создаем группу
        $groupId = $this->groupModel->get_id_from_name($typeId, $groupName, true);

        if (is_numeric($groupId)) {
            return response()->json([
                'success' => true,
                'message' => '',
                'group' => [
                    'id'   => $groupId,
                    'name' => mb_strtoupper($groupName)
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: '.$groupId
            ]);
        }
    }

    /**
     * Получить данные для редактирования
     */
    public function edit(Request $request, int $groupId) {
        // Искусственная задержка (для режима разработки)
        if (config('app.debug')) {
            usleep(500000);
        }

        // Тип
        $typeId = (int)$request->get('type_id', 0);

        Log::debug('type_id: '.$typeId);

        // Проверяем, это создание нового параметра?
        $isNew = $request->get('new') === 'true' || $groupId === null;

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
        $group = $this->groupModel->get_info_from_id($groupId);

        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => 'Параметр не найден'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'   => $group->id,
                'name' => $group->name,
            ]
        ]);
    }

    /**
     * Сохранение изменений имеющейся записи
     */
    public function update(Request $request, $groupId) {
        // Искусственная задержка (для режима разработки)
        if (config('app.debug')) {
            usleep(500000);
        }

        // Валидируем и подготавливаем входные данные
        $validAndPrepareData = $this->validate_and_prepare($request, $groupId);

        if (is_array($validAndPrepareData)) {
            $groupName = $validAndPrepareData['name'];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: '.$validAndPrepareData
            ]);
        }

        // Обновляем даные
        $result = $this->groupModel->edit($groupId, $groupName);

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
     * Удаление
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



    
}
