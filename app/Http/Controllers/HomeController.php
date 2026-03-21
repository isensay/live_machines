<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HealthCheckService;

class HomeController extends Controller
{
    protected HealthCheckService $healthService;

    public function __construct(HealthCheckService $healthService) {
        $this->healthService = $healthService;
    }

    /**
     * Главная страница
     */
    public function index() {
        $metrics = $this->healthService->getAllMetrics();

        //dump($metrics);

        //$currentLocale = app()->getLocale();
        //dump($currentLocale);
        return view('home', [
            'title'       => 'Adoxa - Главная страница',
            'description' => 'Добро пожаловать в Adoxa - ваш новый проект на Laravel',
            'system' => [
                'diskTotal' => round($metrics['disk']['total_gb'], 0),
                'diskUsed'  => round($metrics['disk']['used_gb'], 0),

                'memoryTotal' => round($metrics['memory']['total_gb'], 0),
                'memoryUsed'  => round($metrics['memory']['used_gb'], 0),

                'redisTotal' => round($metrics['redis']['total_gb'], 1),
                'redisUsed'  => round($metrics['redis']['used_gb'], 1),

                'sslExpiryDate'    => ($metrics['ssl_certificate']['expiry_date'] === null) ? 'дата не определена' : $metrics['ssl_certificate']['expiry_date'],
                'sslDaysRemaining' => $metrics['ssl_certificate']['days_remaining'],
                'sslHostName'      => $metrics['ssl_certificate']['hostname'],
            ],
            'features'    => [
                'Laravel '.app()->version(),
                'PHP '.PHP_VERSION,
                'MySQL 8.0',
                'Redis',
                'Docker',
                'Nginx'
            ]
        ]);
    }
}
