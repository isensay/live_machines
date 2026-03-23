<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\HealthCheckService;
use App\Models\Livemachines\ParamModel;
use App\Models\Livemachines\ModelModel;
use App\Models\Livemachines\ManufModel;

class HomeController extends Controller
{
    protected HealthCheckService $healthService;

    private $dbConnection;
    private $techModel;
    private $compModel;
    private $modelModel;
    private $manufModel;

    public function __construct(HealthCheckService $healthService) {
        $this->healthService = $healthService;

        $this->dbConnection = DB::connection('livemachines');
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

        $manufs = $this->manufModel->get_list();

        //dump($metrics);

        //$currentLocale = app()->getLocale();
        //dump($currentLocale);
        return view('home', [
            'title'       => 'Главная страница',
            'description' => 'Добро пожаловать в Adoxa - ваш новый проект на Laravel',
            'system' => [
                'diskTotal'        => round($metrics['disk']['total_gb'], 0),
                'diskUsed'         => round($metrics['disk']['used_gb'], 0),
                'memoryTotal'      => round($metrics['memory']['total_gb'], 0),
                'memoryUsed'       => round($metrics['memory']['used_gb'], 0),
                'redisTotal'       => round($metrics['redis']['total_gb'], 1),
                'redisUsed'        => round($metrics['redis']['used_gb'], 1),
                'mysqlTotal'       => round($metrics['mysql']['database_size_gb'], 1),
                'mysqlUsed'        => round($metrics['mysql']['used_percentage'], 1),
                //'sslExpiryDate'    => ($metrics['ssl_certificate']['expiry_date'] === null) ? 'дата не определена' : date('d.m.Y в H:i', strtotime($metrics['ssl_certificate']['expiry_date'])),
                //'sslDaysRemaining' => $metrics['ssl_certificate']['days_remaining']
            ],
            'manufs' => $manufs,

            'stat' => [
                'tech'  => $this->techModel->get_list('all', -1, 0, 1, '', 'paramName', 'asc')['total'],
                'comp'  => $this->compModel->get_list('all', -1, 0, 1, '', 'paramName', 'asc')['total'],
                'model' => $this->modelModel->get_list('', 0, 1, 'name', 'asc')['total'],
                'manuf' => count($manufs)
            ],

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
