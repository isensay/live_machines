<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MacIpFix
{
    public function handle(Request $request, Closure $next): Response
    {
        // Только для локальной разработки на Mac
        if (app()->environment('local')) {
            $this->fixMacIp($request);
        }

        return $next($request);
    }

    protected function fixMacIp(Request $request): void
    {
        $currentIp = $request->ip();
        
        // Список IP которые Docker Desktop использует на Mac
        $dockerMacIps = [
            '169.150.247.40',
            '192.168.65.1',    // Docker Desktop default
            '172.17.0.1',      // Docker bridge
            '172.18.0.1',      // Docker bridge
        ];

        // Если это Docker IP на Mac, используем специальную логику
        if (in_array($currentIp, $dockerMacIps)) {
            // Вариант 1: Использовать localhost
            $request->server->set('REMOTE_ADDR', '127.0.0.1');
            
            // Вариант 2: Получить внешний IP через API (раскомментируйте если нужно)
            // $externalIp = $this->getExternalIp();
            // if ($externalIp) {
            //     $request->server->set('REMOTE_ADDR', $externalIp);
            // }
        }
    }

    protected function getExternalIp(): ?string
    {
        try {
            $ip = @file_get_contents('https://api.ipify.org');
            return $ip ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
}