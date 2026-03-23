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
        $mysql = $this->getMysqlMetrics();
        $ssl = $this->getSslMetrics();
        
        return [
            'disk' => $disk,
            'memory' => $memory,
            'redis' => $redis,
            'mysql' => $mysql,
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
        
        $totalGb = round($diskTotal / (1024 * 1024 * 1024), 2);
        $usedGb = round($diskUsed / (1024 * 1024 * 1024), 2);
        $freeGb = round($diskFree / (1024 * 1024 * 1024), 2);
        
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
            if (!$this->isRedisAvailable()) {
                return [
                    'total_gb' => 0,
                    'total_mb' => 0,
                    'used_gb' => 0,
                    'used_mb' => 0,
                    'free_gb' => 0,
                    'free_mb' => 0,
                    'used_percentage' => 0,
                    'free_percentage' => 0,
                    'status' => 'error',
                    'message' => 'Redis не доступен или не настроен',
                    'available' => false,
                ];
            }
            
            $redisInfo = Redis::connection()->info();
            
            $maxMemory = $this->parseRedisMemory($redisInfo['maxmemory'] ?? '0');
            $usedMemory = $redisInfo['used_memory'] ?? 0;
            
            if ($maxMemory == 0) {
                $systemMemory = $this->getSystemMemory();
                $maxMemory = $systemMemory['total'] * 1024;
                $usedPercentage = round(($usedMemory / $maxMemory) * 100, 2);
                $freePercentage = round(100 - $usedPercentage, 2);
                $totalGb = round($maxMemory / (1024 * 1024 * 1024), 2);
                $totalMb = round($maxMemory / (1024 * 1024), 2);
                $usedGb = round($usedMemory / (1024 * 1024 * 1024), 2);
                $usedMb = round($usedMemory / (1024 * 1024), 2);
                $freeGb = round(($maxMemory - $usedMemory) / (1024 * 1024 * 1024), 2);
                $freeMb = round(($maxMemory - $usedMemory) / (1024 * 1024), 2);
                
                return [
                    'total_gb' => $totalGb,
                    'total_mb' => $totalMb,
                    'used_gb' => $usedGb,
                    'used_mb' => $usedMb,
                    'free_gb' => $freeGb,
                    'free_mb' => $freeMb,
                    'used_percentage' => $usedPercentage,
                    'free_percentage' => $freePercentage,
                    'status' => $this->getRedisStatus($usedPercentage),
                    'message' => $this->getRedisMessage($usedPercentage, true),
                    'available' => true,
                    'maxmemory_unlimited' => true,
                ];
            }
            
            $usedPercentage = round(($usedMemory / $maxMemory) * 100, 2);
            $freePercentage = round(100 - $usedPercentage, 2);
            $totalGb = round($maxMemory / (1024 * 1024 * 1024), 2);
            $totalMb = round($maxMemory / (1024 * 1024), 2);
            $usedGb = round($usedMemory / (1024 * 1024 * 1024), 2);
            $usedMb = round($usedMemory / (1024 * 1024), 2);
            $freeGb = round(($maxMemory - $usedMemory) / (1024 * 1024 * 1024), 2);
            $freeMb = round(($maxMemory - $usedMemory) / (1024 * 1024), 2);
            
            return [
                'total_gb' => $totalGb,
                'total_mb' => $totalMb,
                'used_gb' => $usedGb,
                'used_mb' => $usedMb,
                'free_gb' => $freeGb,
                'free_mb' => $freeMb,
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
                'total_mb' => 0,
                'used_gb' => 0,
                'used_mb' => 0,
                'free_gb' => 0,
                'free_mb' => 0,
                'used_percentage' => 0,
                'free_percentage' => 0,
                'status' => 'error',
                'message' => 'Ошибка подключения к Redis: ' . $e->getMessage(),
                'available' => false,
            ];
        }
    }

    /**
     * Метрики MySQL
     */
    public function getMysqlMetrics(): array
    {
        try {
            // Проверяем подключение к MySQL
            DB::connection()->getPdo();
            
            // Получаем размер всех баз данных
            $totalSize = $this->getTotalDatabasesSize();
            
            // Получаем размер базы данных livemachines
            $livemachinesSize = $this->getDatabaseSize('livemachines');
            
            // Получаем информацию о диске, где хранятся базы данных MySQL
            $diskMetrics = $this->getMysqlDiskMetrics();
            
            // Общий размер в ГБ и МБ
            $totalSizeGb = round($totalSize / (1024 * 1024 * 1024), 2);
            $totalSizeMb = round($totalSize / (1024 * 1024), 2);
            
            // Размер livemachines в ГБ и МБ
            $livemachinesSizeGb = round($livemachinesSize / (1024 * 1024 * 1024), 2);
            $livemachinesSizeMb = round($livemachinesSize / (1024 * 1024), 2);
            
            return [
                // Размер основной БД + БД livemachines
                'total_databases' => [
                    'size_gb' => $totalSizeGb,
                    'size_mb' => $totalSizeMb,
                    'size_bytes' => $totalSize,
                ],
                'livemachines_database' => [
                    'size_gb' => $livemachinesSizeGb,
                    'size_mb' => $livemachinesSizeMb,
                    'size_bytes' => $livemachinesSize,
                ],
                // Процент использования диска MySQL
                'disk_usage' => [
                    'used_percentage' => $diskMetrics['used_percentage'],
                    'free_percentage' => $diskMetrics['free_percentage'],
                    'total_gb' => $diskMetrics['total_gb'],
                    'used_gb' => $diskMetrics['used_gb'],
                    'free_gb' => $diskMetrics['free_gb'],
                ],
                'status' => $this->getMysqlStatus($diskMetrics['used_percentage']),
                'message' => $this->getMysqlMessage($diskMetrics['used_percentage']),
                'available' => true,
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to get MySQL metrics: ' . $e->getMessage());
            return [
                'total_databases' => [
                    'size_gb' => 0,
                    'size_mb' => 0,
                    'size_bytes' => 0,
                ],
                'livemachines_database' => [
                    'size_gb' => 0,
                    'size_mb' => 0,
                    'size_bytes' => 0,
                ],
                'disk_usage' => [
                    'used_percentage' => 0,
                    'free_percentage' => 0,
                    'total_gb' => 0,
                    'used_gb' => 0,
                    'free_gb' => 0,
                ],
                'status' => 'error',
                'message' => 'Ошибка подключения к MySQL: ' . $e->getMessage(),
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
            
            $isValid = ($now >= $validFrom && $now <= $validTo);
            $daysRemaining = floor(($validTo - $now) / (60 * 60 * 24));
            $daysTotal = floor(($validTo - $validFrom) / (60 * 60 * 24));
            
            $percentageRemaining = 0;
            if ($daysTotal > 0 && $now >= $validFrom) {
                $percentageRemaining = round(($daysRemaining / $daysTotal) * 100, 2);
            } elseif ($now < $validFrom) {
                $percentageRemaining = 100;
            }
            
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
     * Получить общий размер всех баз данных
     */
    private function getTotalDatabasesSize(): float
    {
        try {
            $result = DB::selectOne("
                SELECT 
                    ROUND(SUM(data_length + index_length), 2) as size
                FROM information_schema.tables 
                WHERE table_schema NOT IN ('information_schema', 'performance_schema', 'mysql', 'sys')
            ");
            
            return $result->size ?? 0;
            
        } catch (\Exception $e) {
            Log::error('Failed to get total databases size: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Получить размер конкретной базы данных
     */
    private function getDatabaseSize(string $databaseName): float
    {
        try {
            $result = DB::selectOne("
                SELECT 
                    ROUND(SUM(data_length + index_length), 2) as size
                FROM information_schema.tables 
                WHERE table_schema = ?
            ", [$databaseName]);
            
            return $result->size ?? 0;
            
        } catch (\Exception $e) {
            Log::error("Failed to get database size for {$databaseName}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Получить метрики диска для MySQL
     */
    private function getMysqlDiskMetrics(): array
    {
        try {
            // Получаем путь к директории данных MySQL
            $dataDir = DB::selectOne('SHOW VARIABLES LIKE "datadir"');
            $dataDirPath = $dataDir->Value ?? '/var/lib/mysql/';
            
            // Определяем корневой диск/раздел
            if (PHP_OS_FAMILY === 'Windows') {
                $rootPath = substr($dataDirPath, 0, 2) . '\\';
            } else {
                $rootPath = $this->getMountPoint($dataDirPath);
            }
            
            $diskTotal = disk_total_space($rootPath);
            $diskFree = disk_free_space($rootPath);
            $diskUsed = $diskTotal - $diskFree;
            
            $totalGb = round($diskTotal / (1024 * 1024 * 1024), 2);
            $usedGb = round($diskUsed / (1024 * 1024 * 1024), 2);
            $freeGb = round($diskFree / (1024 * 1024 * 1024), 2);
            
            $usedPercentage = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : 0;
            $freePercentage = $diskTotal > 0 ? round(($diskFree / $diskTotal) * 100, 2) : 0;
            
            return [
                'total_gb' => $totalGb,
                'used_gb' => $usedGb,
                'free_gb' => $freeGb,
                'used_percentage' => $usedPercentage,
                'free_percentage' => $freePercentage,
                'data_directory' => $dataDirPath,
            ];
        } catch (\Exception $e) {
            return [
                'total_gb' => 0,
                'used_gb' => 0,
                'free_gb' => 0,
                'used_percentage' => 0,
                'free_percentage' => 0,
                'data_directory' => 'Unknown',
            ];
        }
    }

    /**
     * Получить системную память
     */
    private function getSystemMemory(): array
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $memInfo = file_get_contents('/proc/meminfo');
            if ($memInfo !== false) {
                preg_match('/MemTotal:\s+(\d+)\s+kB/', $memInfo, $totalMatch);
                preg_match('/MemAvailable:\s+(\d+)\s+kB/', $memInfo, $availableMatch);
                
                if (isset($totalMatch[1])) {
                    $total = (float) $totalMatch[1];
                    $available = isset($availableMatch[1]) ? (float) $availableMatch[1] : 0;
                    $used = $total - $available;
                    $usedPercentage = $total > 0 ? round(($used / $total) * 100, 2) : 0;
                    $freePercentage = $total > 0 ? round(($available / $total) * 100, 2) : 0;
                    
                    return [
                        'total' => $total,
                        'used' => $used,
                        'free' => $available,
                        'used_percentage' => $usedPercentage,
                        'free_percentage' => $freePercentage,
                    ];
                }
            }
        }
        
        return [
            'total' => 0,
            'used' => 0,
            'free' => 0,
            'used_percentage' => 0,
            'free_percentage' => 0,
        ];
    }

    /**
     * Получить точку монтирования для пути (Linux)
     */
    private function getMountPoint(string $path): string
    {
        $path = realpath($path);
        if (!$path) {
            return '/';
        }
        
        $mounts = file('/proc/mounts', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$mounts) {
            return '/';
        }
        
        $bestMatch = '/';
        
        foreach ($mounts as $mount) {
            $parts = preg_split('/\s+/', $mount);
            if (count($parts) < 2) {
                continue;
            }
            
            $mountPoint = $parts[1];
            
            if (strpos($path, $mountPoint) === 0 && strlen($mountPoint) > strlen($bestMatch)) {
                $bestMatch = $mountPoint;
            }
        }
        
        return $bestMatch;
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
    
    private function getMysqlStatus(float $usedPercentage): string
    {
        if ($usedPercentage >= 95) return 'critical';
        if ($usedPercentage >= 85) return 'warning';
        if ($usedPercentage >= 75) return 'attention';
        return 'ok';
    }
    
    private function getMysqlMessage(float $usedPercentage): string
    {
        if ($usedPercentage >= 95) return 'КРИТИЧЕСКИ! Место на диске MySQL заканчивается!';
        if ($usedPercentage >= 85) return 'Мало места на диске MySQL, требуется освобождение';
        if ($usedPercentage >= 75) return 'Места на диске MySQL мало, рекомендуется увеличить';
        return 'Достаточно места на диске MySQL';
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