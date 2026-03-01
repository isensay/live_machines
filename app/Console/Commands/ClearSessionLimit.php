<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ClearSessionLimit extends Command
{
    protected $signature = 'session:clear-limit {ip? : Specific IP address}';
    protected $description = 'Clear session limit for IP address';

    public function handle()
    {
        $ip = $this->argument('ip') ?: $this->ask('Enter IP address');

        if (!$ip) {
            $this->error('IP address is required');
            return 1;
        }

        $keys = [
            "session_counter:{$ip}",
            "session_limit:{$ip}",
        ];

        $cleared = 0;
        foreach ($keys as $key) {
            if (Redis::del($key)) {
                $this->info("Cleared (Redis): {$key}");
                $cleared++;
            }
        }

        if ($cleared > 0) {
            $this->info("Session limit cleared for IP: {$ip}");
        } else {
            $this->warn("No session limits found for IP: {$ip}");
        }

        return 0;
    }
}