<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\HealthCheckService;
use App\Models\Livemachines\FileModel;
use App\Models\Livemachines\GroupModel;
use App\Models\Livemachines\ParamModel;
use App\Models\Livemachines\ModelModel;
use App\Models\Livemachines\ManufModel;

class HomeController extends Controller
{
    protected HealthCheckService $healthService;

    private $dbConnection;
    private $fileModel;
    private $groupModel;
    private $techModel;
    private $compModel;
    private $modelModel;
    private $manufModel;

    public function __construct(HealthCheckService $healthService) {
        $this->healthService = $healthService;

        $this->dbConnection = DB::connection('livemachines');
        $this->fileModel    = new FileModel([], $this->dbConnection);
        $this->groupModel   = new GroupModel([], $this->dbConnection, 1);
        $this->techModel    = new ParamModel([], $this->dbConnection, 1);
        $this->compModel    = new ParamModel([], $this->dbConnection, 2);
        $this->modelModel   = new ModelModel([], $this->dbConnection);
        $this->manufModel   = new ManufModel([], $this->dbConnection);
    }

    /**
     * Главная страница
     */
    public function index() {
        $metrics = $this->healthService->getAllMetrics();

        $manufs = $this->manufModel->get_list()['data'];

        //dump($metrics);

        //$currentLocale = app()->getLocale();
        //dump($currentLocale);
        return view('home', [
            // Заголовки
            'title'       => 'Главная страница',
            'description' => '',

            // Статистика по системе
            'system' => [
                'diskTotal'     => round($metrics['disk']['total_gb'], 0),
                'diskUsed'      => round($metrics['disk']['used_gb'], 1),
                'diskUsedPer'   => round($metrics['disk']['used_percentage'], 0),
                'memoryTotal'   => round($metrics['memory']['total_gb'], 0),
                'memoryUsed'    => round($metrics['memory']['used_gb'], 1),
                'memoryUsedPer' => round($metrics['memory']['used_percentage'], 0),
                'redisTotal'    => round($metrics['redis']['total_mb'], 0),
                'redisUsed'     => round($metrics['redis']['used_mb'], 1),
                'redisUsedPer'  => round($metrics['redis']['used_percentage'], 1),
                'mysqlTotal'    => round($metrics['mysql']['disk_usage']['total_gb'], 0),
                'mysqlUsed'     => round($metrics['mysql']['total_databases']['size_mb'], 1),
                'mysqlUsedPer'  => round(($metrics['mysql']['total_databases']['size_gb'] / $metrics['mysql']['disk_usage']['total_gb']) * 100, 1),
            ],

            // Список производителей
            'manufs' => $manufs,

            // Статистика 
            'stat' => [
                'file'  => $this->fileModel->get_list()['total'],
                'group' => $this->groupModel->get_list()['total'],
                'tech'  => $this->techModel->get_list('all', -1, 0, 1, '', 'paramName', 'asc')['total'],
                'comp'  => $this->compModel->get_list('all', -1, 0, 1, '', 'paramName', 'asc')['total'],
                'model' => $this->modelModel->get_list('', 0, 1, 'name', 'asc')['total'],
                'manuf' => count($manufs)
            ],

            // Информация о системе
            'features' => [
                'Laravel ' . app()->version(),
                'PHP ' . PHP_VERSION,
                'MySQL 8.0',
                'Redis',
                'Docker',
                'Nginx'
            ]
        ]);
    }
}
