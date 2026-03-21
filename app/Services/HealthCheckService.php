<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class HealthCheckService
{
    /**
     * Получить все показатели здоровья системы
     */
    public function getAllMetrics(): array
    {
        $disk = $this->getDiskMetrics();
        $memory = $this->getMemoryMetrics();
        $redis = $this->getRedisMetrics();
        $ssl = $this->getSslMetrics();
        
        return [
            'disk' => $disk,
            'memory' => $memory,
            'redis' => $redis,
            'ssl_certificate' => $ssl,
            'timestamp' => now()->toIso8601String(),
            'server_time' => now()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone'),
        ];
    }

    /**
     * Метрики диска
     */
    public function getDiskMetrics(): array
    {
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsed = $diskTotal - $diskFree;
        
        // Преобразуем байты в ГБ
        $totalGb = round($diskTotal / (1024 * 1024 * 1024), 2);
        $usedGb = round($diskUsed / (1024 * 1024 * 1024), 2);
        $freeGb = round($diskFree / (1024 * 1024 * 1024), 2);
        
        // Проценты
        $usedPercentage = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : 0;
        $freePercentage = $diskTotal > 0 ? round(($diskFree / $diskTotal) * 100, 2) : 0;
        
        return [
            'total_gb' => $totalGb,
            'used_gb' => $usedGb,
            'free_gb' => $freeGb,
            'used_percentage' => $usedPercentage,
            'free_percentage' => $freePercentage,
            'status' => $this->getDiskStatus($usedPercentage),
            'message' => $this->getDiskMessage($usedPercentage),
        ];
    }

    /**
     * Метрики оперативной памяти
     */
    public function getMemoryMetrics(): array
    {
        $memoryData = $this->getSystemMemory();
        
        // Преобразуем КБ в ГБ
        $totalGb = round($memoryData['total'] / (1024 * 1024), 2);
        $usedGb = round($memoryData['used'] / (1024 * 1024), 2);
        $freeGb = round($memoryData['free'] / (1024 * 1024), 2);
        
        return [
            'total_gb' => $totalGb,
            'used_gb' => $usedGb,
            'free_gb' => $freeGb,
            'used_percentage' => $memoryData['used_percentage'],
            'free_percentage' => $memoryData['free_percentage'],
            'status' => $this->getMemoryStatus($memoryData['used_percentage']),
            'message' => $this->getMemoryMessage($memoryData['used_percentage']),
        ];
    }

    /**
     * Метрики Redis
     */
    public function getRedisMetrics(): array
    {
        try {
            // Проверяем, доступен ли Redis
            if (!$this->isRedisAvailable()) {
                return [
                    'total_gb' => 0,
                    'used_gb' => 0,
                    'free_gb' => 0,
                    'used_percentage' => 0,
                    'free_percentage' => 0,
                    'status' => 'error',
                    'message' => 'Redis не доступен или не настроен',
                    'available' => false,
                ];
            }
            
            // Получаем информацию о Redis
            $redisInfo = Redis::connection()->info();
            
            // Максимальная память Redis (в байтах)
            $maxMemory = $this->parseRedisMemory($redisInfo['maxmemory'] ?? '0');
            
            // Использованная память (в байтах)
            $usedMemory = $redisInfo['used_memory'] ?? 0;
            
            // Если maxmemory = 0, значит лимита нет
            if ($maxMemory == 0) {
                // Используем системную память как лимит
                $systemMemory = $this->getSystemMemory();
                $maxMemory = $systemMemory['total'] * 1024; // КБ -> байты
                $usedPercentage = round(($usedMemory / $maxMemory) * 100, 2);
                $freePercentage = round(100 - $usedPercentage, 2);
                $totalGb = round($maxMemory / (1024 * 1024 * 1024), 2);
                
                return [
                    'total_gb' => $totalGb,
                    'used_gb' => round($usedMemory / (1024 * 1024 * 1024), 2),
                    'free_gb' => round(($maxMemory - $usedMemory) / (1024 * 1024 * 1024), 2),
                    'used_percentage' => $usedPercentage,
                    'free_percentage' => $freePercentage,
                    'status' => $this->getRedisStatus($usedPercentage),
                    'message' => $this->getRedisMessage($usedPercentage, true),
                    'available' => true,
                    'maxmemory_unlimited' => true,
                ];
            }
            
            // Вычисляем проценты
            $usedPercentage = round(($usedMemory / $maxMemory) * 100, 2);
            $freePercentage = round(100 - $usedPercentage, 2);
            $totalGb = round($maxMemory / (1024 * 1024 * 1024), 2);
            
            return [
                'total_gb' => $totalGb,
                'used_gb' => round($usedMemory / (1024 * 1024 * 1024), 2),
                'free_gb' => round(($maxMemory - $usedMemory) / (1024 * 1024 * 1024), 2),
                'used_percentage' => $usedPercentage,
                'free_percentage' => $freePercentage,
                'status' => $this->getRedisStatus($usedPercentage),
                'message' => $this->getRedisMessage($usedPercentage, false),
                'available' => true,
                'maxmemory_unlimited' => false,
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to get Redis metrics: ' . $e->getMessage());
            return [
                'total_gb' => 0,
                'used_gb' => 0,
                'free_gb' => 0,
                'used_percentage' => 0,
                'free_percentage' => 0,
                'status' => 'error',
                'message' => 'Ошибка подключения к Redis: ' . $e->getMessage(),
                'available' => false,
            ];
        }
    }

    /**
     * Метрики SSL сертификата
     */
    public function getSslMetrics(?string $hostname = null, ?int $port = 443): array
    {
        $hostname = $hostname ?? request()->getHost();
        
        try {
            $certificate = $this->getCertificate($hostname, $port);
            
            if (!$certificate) {
                return [
                    'valid' => false,
                    'expiry_date' => null,
                    'days_remaining' => 0,
                    'percentage_remaining' => 0,
                    'status' => 'error',
                    'message' => 'Не удалось получить сертификат для ' . $hostname,
                    'hostname' => $hostname,
                ];
            }
            
            $parsed = openssl_x509_parse($certificate);
            
            if (!$parsed || !isset($parsed['validFrom_time_t'], $parsed['validTo_time_t'])) {
                return [
                    'valid' => false,
                    'expiry_date' => null,
                    'days_remaining' => 0,
                    'percentage_remaining' => 0,
                    'status' => 'error',
                    'message' => 'Не удалось распарсить сертификат',
                    'hostname' => $hostname,
                ];
            }
            
            $validFrom = $parsed['validFrom_time_t'];
            $validTo = $parsed['validTo_time_t'];
            $now = time();
            
            // Проверяем, действителен ли сертификат
            $isValid = ($now >= $validFrom && $now <= $validTo);
            
            // Вычисляем дни до истечения
            $daysRemaining = floor(($validTo - $now) / (60 * 60 * 24));
            $daysTotal = floor(($validTo - $validFrom) / (60 * 60 * 24));
            
            // Процент оставшегося времени
            $percentageRemaining = 0;
            if ($daysTotal > 0 && $now >= $validFrom) {
                $percentageRemaining = round(($daysRemaining / $daysTotal) * 100, 2);
            } elseif ($now < $validFrom) {
                $percentageRemaining = 100;
            }
            
            // Форматируем дату
            $expiryDate = date('Y-m-d H:i:s', $validTo);
            
            return [
                'valid' => $isValid,
                'hostname' => $hostname,
                'issued_to' => $parsed['subject']['CN'] ?? 'Unknown',
                'issued_by' => $parsed['issuer']['CN'] ?? 'Unknown',
                'valid_from' => date('Y-m-d H:i:s', $validFrom),
                'expiry_date' => $expiryDate,
                'days_remaining' => $daysRemaining,
                'percentage_remaining' => $percentageRemaining,
                'status' => $this->getSslStatus($daysRemaining, $percentageRemaining),
                'message' => $this->getSslMessage($daysRemaining, $isValid),
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to get SSL certificate: ' . $e->getMessage());
            return [
                'valid' => false,
                'expiry_date' => null,
                'days_remaining' => 0,
                'percentage_remaining' => 0,
                'status' => 'error',
                'message' => 'Ошибка получения сертификата: ' . $e->getMessage(),
                'hostname' => $hostname,
            ];
        }
    }

    /**
     * Получить системную память
     */
    private function getSystemMemory(): array
    {
        // Для Linux систем
        if (PHP_OS_FAMILY === 'Linux') {
            $memInfo = file_get_contents('/proc/meminfo');
            if ($memInfo !== false) {
                preg_match('/MemTotal:\s+(\d+)\s+kB/', $memInfo, $totalMatch);
                preg_match('/MemAvailable:\s+(\d+)\s+kB/', $memInfo, $availableMatch);
                
                if (isset($totalMatch[1])) {
                    $total = (float) $totalMatch[1]; // в КБ
                    $available = isset($availableMatch[1]) ? (float) $availableMatch[1] : 0;
                    $used = $total - $available;
                    $usedPercentage = $total > 0 ? round(($used / $total) * 100, 2) : 0;
                    $freePercentage = $total > 0 ? round(($available / $total) * 100, 2) : 0;
                    
                    return [
                        'total' => $total, // КБ
                        'used' => $used,
                        'free' => $available,
                        'used_percentage' => $usedPercentage,
                        'free_percentage' => $freePercentage,
                    ];
                }
            }
        }
        
        // Для Windows систем
        if (PHP_OS_FAMILY === 'Windows') {
            $output = [];
            exec('wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /value', $output);
            
            $total = 0;
            $free = 0;
            
            foreach ($output as $line) {
                if (str_contains($line, 'TotalVisibleMemorySize')) {
                    $total = (float) explode('=', $line)[1];
                }
                if (str_contains($line, 'FreePhysicalMemory')) {
                    $free = (float) explode('=', $line)[1];
                }
            }
            
            $used = $total - $free;
            $usedPercentage = $total > 0 ? round(($used / $total) * 100, 2) : 0;
            $freePercentage = $total > 0 ? round(($free / $total) * 100, 2) : 0;
            
            return [
                'total' => $total,
                'used' => $used,
                'free' => $free,
                'used_percentage' => $usedPercentage,
                'free_percentage' => $freePercentage,
            ];
        }
        
        // Fallback
        return [
            'total' => 0,
            'used' => 0,
            'free' => 0,
            'used_percentage' => 0,
            'free_percentage' => 0,
        ];
    }

    /**
     * Получить сертификат для хоста
     */
    private function getCertificate(string $hostname, int $port)
    {
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ]);
        
        $client = @stream_socket_client(
            'ssl://' . $hostname . ':' . $port,
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$client) {
            return null;
        }
        
        $params = stream_context_get_params($client);
        fclose($client);
        
        if (!isset($params['options']['ssl']['peer_certificate'])) {
            return null;
        }
        
        return $params['options']['ssl']['peer_certificate'];
    }

    /**
     * Проверить доступность Redis
     */
    private function isRedisAvailable(): bool
    {
        try {
            Redis::connection()->ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Парсить строку памяти Redis
     */
    private function parseRedisMemory($memoryString): int
    {
        if ($memoryString === '0' || $memoryString === '') {
            return 0;
        }
        
        if (is_numeric($memoryString)) {
            return (int) $memoryString;
        }
        
        // Парсим форматы типа "1GB", "512MB"
        if (preg_match('/(\d+)(GB|MB|KB)/i', $memoryString, $matches)) {
            $value = (int) $matches[1];
            $unit = strtoupper($matches[2]);
            
            switch ($unit) {
                case 'GB':
                    return $value * 1024 * 1024 * 1024;
                case 'MB':
                    return $value * 1024 * 1024;
                case 'KB':
                    return $value * 1024;
                default:
                    return $value;
            }
        }
        
        return 0;
    }

    // ========== СТАТУСЫ И СООБЩЕНИЯ ==========
    
    private function getDiskStatus(float $usedPercentage): string
    {
        if ($usedPercentage >= 95) return 'critical';
        if ($usedPercentage >= 85) return 'warning';
        if ($usedPercentage >= 75) return 'attention';
        return 'ok';
    }
    
    private function getDiskMessage(float $usedPercentage): string
    {
        if ($usedPercentage >= 95) return 'КРИТИЧЕСКИ! Место на диске заканчивается!';
        if ($usedPercentage >= 85) return 'Очень мало свободного места, требуется освобождение';
        if ($usedPercentage >= 75) return 'Свободного места мало, рекомендуется освободить';
        return 'Достаточно свободного места на диске';
    }
    
    private function getMemoryStatus(float $usedPercentage): string
    {
        if ($usedPercentage >= 95) return 'critical';
        if ($usedPercentage >= 85) return 'warning';
        if ($usedPercentage >= 75) return 'attention';
        return 'ok';
    }
    
    private function getMemoryMessage(float $usedPercentage): string
    {
        if ($usedPercentage >= 95) return 'КРИТИЧЕСКИ! Не хватает оперативной памяти!';
        if ($usedPercentage >= 85) return 'Мало оперативной памяти, требуется увеличение';
        if ($usedPercentage >= 75) return 'Оперативной памяти используется много, следите за нагрузкой';
        return 'Достаточно свободной оперативной памяти';
    }
    
    private function getRedisStatus(float $usedPercentage): string
    {
        if ($usedPercentage >= 90) return 'critical';
        if ($usedPercentage >= 75) return 'warning';
        if ($usedPercentage >= 60) return 'attention';
        return 'ok';
    }
    
    private function getRedisMessage(float $usedPercentage, bool $unlimited): string
    {
        if ($unlimited) {
            return 'Redis без ограничения памяти (использует системную память)';
        }
        
        if ($usedPercentage >= 90) return 'КРИТИЧЕСКИ! Redis почти заполнен!';
        if ($usedPercentage >= 75) return 'Redis использует много памяти, требуется увеличение лимита';
        if ($usedPercentage >= 60) return 'Память Redis активно используется';
        return 'Памяти Redis достаточно';
    }
    
    private function getSslStatus(int $daysRemaining, float $percentageRemaining): string
    {
        if ($daysRemaining <= 0) return 'expired';
        if ($daysRemaining <= 7) return 'critical';
        if ($daysRemaining <= 30) return 'warning';
        if ($daysRemaining <= 90) return 'attention';
        return 'ok';
    }
    
    private function getSslMessage(int $daysRemaining, bool $isValid): string
    {
        if (!$isValid) return 'Сертификат истек или недействителен!';
        if ($daysRemaining <= 0) return 'Сертификат истек! Срочно продлите!';
        if ($daysRemaining <= 7) return "Сертификат истекает через {$daysRemaining} дней! СРОЧНО!";
        if ($daysRemaining <= 30) return "Сертификат истекает через {$daysRemaining} дней, требуется продление";
        if ($daysRemaining <= 90) return "Сертификат истекает через {$daysRemaining} дней, запланируйте продление";
        return "Сертификат действителен, истекает через {$daysRemaining} дней";
    }
}