<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class UnblockIp extends Command
{
    protected $signature = 'unblock:ip {ip}';
    protected $description = 'Unblock IP address from login attempts';

    public function handle()
    {
        $ip = $this->argument('ip');
        
        // Все возможные ключи блокировки
        $keys = [
            "login_attempts:{$ip}",
            "login_attempts:{$ip}:blocked", 
            "login_blocked:{$ip}",
            "throttle:login:{$ip}", // на случай если используется встроенный throttle
        ];
        
        $cleared = 0;
        foreach ($keys as $key) {
            if (Cache::has($key)) {
                Cache::forget($key);
                $cleared++;
                $this->line("✅ Очищен ключ: {$key}");
            }
        }
        
        if ($cleared > 0) {
            $this->info("✅ Блокировка снята для IP: {$ip} (очищено ключей: {$cleared})");
        } else {
            $this->info("ℹ️  Для IP: {$ip} не найдено ключей блокировки");
        }
        
        return 0;
    }
}