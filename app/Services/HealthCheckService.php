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
                    'used_gb' => 0,
                    'free_gb' => 0,
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
     * Метрики MySQL
     */
    public function getMysqlMetrics(): array
    {
        try {
            // Проверяем подключение к MySQL
            DB::connection()->getPdo();
            
            // Получаем информацию о MySQL
            $mysqlInfo = $this->getMysqlInfo();
            
            // Получаем размер базы данных
            $databaseSize = $this->getDatabaseSize();
            
            // Получаем лимиты MySQL
            $limits = $this->getMysqlLimits();
            
            // Получаем статистику подключений
            $connections = $this->getMysqlConnections();
            
            // Получаем статус запросов
            $queries = $this->getMysqlQueries();
            
            // Рассчитываем проценты использования
            $maxConnections = $limits['max_connections'] ?? 100;
            $currentConnections = $connections['current'] ?? 0;
            $connectionPercentage = $maxConnections > 0 ? round(($currentConnections / $maxConnections) * 100, 2) : 0;
            
            // Размер базы данных в ГБ
            $totalGb = round($databaseSize / (1024 * 1024 * 1024), 2);
            $usedGb = $totalGb;
            $freeGb = 0; // MySQL не имеет встроенного лимита, используем диск
            
            // Получаем свободное место на диске MySQL
            $diskMetrics = $this->getMysqlDiskMetrics();
            
            return [
                // Основная информация
                'version' => $mysqlInfo['version'] ?? 'Unknown',
                'server' => $mysqlInfo['server'] ?? 'Unknown',
                'host' => config('database.connections.mysql.host', 'localhost'),
                'database' => config('database.connections.mysql.database', 'Unknown'),
                
                // Размер базы данных
                'total_gb' => $totalGb,
                'used_gb' => $usedGb,
                'free_gb' => $diskMetrics['free_gb'],
                'database_size_gb' => $totalGb,
                'database_size_mb' => round($databaseSize / (1024 * 1024), 2),
                
                // Проценты использования
                'used_percentage' => $diskMetrics['used_percentage'],
                'free_percentage' => $diskMetrics['free_percentage'],
                
                // Подключения
                'connections' => [
                    'current' => $currentConnections,
                    'max' => $maxConnections,
                    'percentage' => $connectionPercentage,
                    'threads_connected' => $connections['threads_connected'] ?? 0,
                    'threads_running' => $connections['threads_running'] ?? 0,
                ],
                
                // Статистика запросов
                'queries' => [
                    'total' => $queries['total'] ?? 0,
                    'per_second' => $queries['per_second'] ?? 0,
                    'slow_queries' => $queries['slow_queries'] ?? 0,
                ],
                
                // Лимиты
                'limits' => $limits,
                
                // Диск MySQL
                'disk' => $diskMetrics,
                
                // Статус
                'status' => $this->getMysqlStatus($diskMetrics['used_percentage'], $connectionPercentage),
                'message' => $this->getMysqlMessage($diskMetrics['used_percentage'], $connectionPercentage),
                'available' => true,
                
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to get MySQL metrics: ' . $e->getMessage());
            return [
                'total_gb' => 0,
                'used_gb' => 0,
                'free_gb' => 0,
                'used_percentage' => 0,
                'free_percentage' => 0,
                'status' => 'error',
                'message' => 'Ошибка подключения к MySQL: ' . $e->getMessage(),
                'available' => false,
                'version' => 'Unknown',
            ];
        }
    }

    /**
     * Получить информацию о MySQL
     */
    private function getMysqlInfo(): array
    {
        try {
            $version = DB::selectOne('SELECT VERSION() as version');
            $server = DB::selectOne('SELECT @@hostname as hostname, @@port as port');
            
            return [
                'version' => $version->version ?? 'Unknown',
                'server' => ($server->hostname ?? 'localhost') . ':' . ($server->port ?? '3306'),
            ];
        } catch (\Exception $e) {
            return ['version' => 'Unknown', 'server' => 'Unknown'];
        }
    }

    /**
     * Получить размер базы данных
     */
    private function getDatabaseSize(): float
    {
        try {
            $database = config('database.connections.mysql.database');
            
            $result = DB::selectOne("
                SELECT 
                    ROUND(SUM(data_length + index_length), 2) as size
                FROM information_schema.tables 
                WHERE table_schema = ?
            ", [$database]);
            
            return $result->size ?? 0;
            
        } catch (\Exception $e) {
            Log::error('Failed to get database size: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Получить лимиты MySQL
     */
    private function getMysqlLimits(): array
    {
        try {
            $maxConnections = DB::selectOne("SHOW VARIABLES LIKE 'max_connections'");
            $maxAllowedPacket = DB::selectOne("SHOW VARIABLES LIKE 'max_allowed_packet'");
            $waitTimeout = DB::selectOne("SHOW VARIABLES LIKE 'wait_timeout'");
            $innodbBufferPool = DB::selectOne("SHOW VARIABLES LIKE 'innodb_buffer_pool_size'");
            
            return [
                'max_connections' => (int)($maxConnections->Value ?? 100),
                'max_allowed_packet_mb' => round(($maxAllowedPacket->Value ?? 0) / (1024 * 1024), 2),
                'wait_timeout_seconds' => (int)($waitTimeout->Value ?? 28800),
                'innodb_buffer_pool_mb' => round(($innodbBufferPool->Value ?? 0) / (1024 * 1024), 2),
            ];
        } catch (\Exception $e) {
            return [
                'max_connections' => 100,
                'max_allowed_packet_mb' => 0,
                'wait_timeout_seconds' => 0,
                'innodb_buffer_pool_mb' => 0,
            ];
        }
    }

    /**
     * Получить статистику подключений MySQL
     */
    private function getMysqlConnections(): array
    {
        try {
            $threads = DB::selectOne("SHOW STATUS LIKE 'Threads_connected'");
            $running = DB::selectOne("SHOW STATUS LIKE 'Threads_running'");
            $maxUsed = DB::selectOne("SHOW STATUS LIKE 'Max_used_connections'");
            
            $current = $threads->Value ?? 0;
            $runningCount = $running->Value ?? 0;
            $maxUsedConnections = $maxUsed->Value ?? 0;
            
            return [
                'current' => (int)$current,
                'threads_connected' => (int)$current,
                'threads_running' => (int)$runningCount,
                'max_used' => (int)$maxUsedConnections,
            ];
        } catch (\Exception $e) {
            return ['current' => 0, 'threads_connected' => 0, 'threads_running' => 0];
        }
    }

    /**
     * Получить статистику запросов MySQL
     */
    private function getMysqlQueries(): array
    {
        try {
            $questions = DB::selectOne("SHOW STATUS LIKE 'Questions'");
            $uptime = DB::selectOne("SHOW STATUS LIKE 'Uptime'");
            $slowQueries = DB::selectOne("SHOW STATUS LIKE 'Slow_queries'");
            
            $totalQueries = $questions->Value ?? 0;
            $uptimeSeconds = $uptime->Value ?? 1;
            $slowQueriesCount = $slowQueries->Value ?? 0;
            
            return [
                'total' => (int)$totalQueries,
                'per_second' => round($totalQueries / $uptimeSeconds, 2),
                'slow_queries' => (int)$slowQueriesCount,
                'slow_queries_percentage' => $totalQueries > 0 ? round(($slowQueriesCount / $totalQueries) * 100, 4) : 0,
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'per_second' => 0, 'slow_queries' => 0];
        }
    }

    /**
     * Получить метрики диска для MySQL
     */
    private function getMysqlDiskMetrics(): array
    {
        try {
            $dataDir = DB::selectOne('SHOW VARIABLES LIKE "datadir"');
            $dataDirPath = $dataDir->Value ?? '/var/lib/mysql/';
            
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
    
    private function getMysqlStatus(float $diskPercentage, float $connectionPercentage): string
    {
        if ($diskPercentage >= 95) return 'critical';
        if ($diskPercentage >= 85) return 'warning';
        if ($diskPercentage >= 75 || $connectionPercentage >= 85) return 'attention';
        return 'ok';
    }
    
    private function getMysqlMessage(float $diskPercentage, float $connectionPercentage): string
    {
        if ($diskPercentage >= 95) return 'КРИТИЧЕСКИ! Место на диске MySQL заканчивается!';
        if ($diskPercentage >= 85) return 'Мало места на диске MySQL, требуется освобождение';
        if ($diskPercentage >= 75) return 'Места на диске MySQL мало, рекомендуется увеличить';
        if ($connectionPercentage >= 85) return 'Много активных подключений к MySQL';
        return 'MySQL работает нормально';
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