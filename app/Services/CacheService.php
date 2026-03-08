<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Универсальный метод кэширования с поддержкой:
     * - тегов
     * - сжатия (с adjustable уровнем)
     * - версионирования (опционально)
     * - логов и статистики
     *
     * @param string $baseKey
     * @param array|string $tags
     * @param callable $callback
     * @param string|null $versionKey
     * @param int $ttl
     * @param int $compressionLevel 0 = без сжатия, 1-9 = уровень gzcompress
     * @return mixed
     */
    public static function remember(
        string $baseKey,
        array|string $tags,
        callable $callback,
        ?string $versionKey = null,
        int $ttl = 3600,
        int $compressionLevel = 6
    ) {
        $tags = (array) $tags;

        // Определяем версию
        $version = $versionKey ? Cache::get($versionKey, 1) : 1;
        $key = $baseKey . '_v' . $version;

        // Пытаемся взять из кэша
        $cached = Cache::tags($tags)->get($key);
        if ($cached !== null) {
            $data = self::decompress($cached);
            Log::debug('[Cache HIT]', [
                'key' => $key,
                'tags' => $tags,
                'size' => self::formatBytes(strlen($cached)),
            ]);
            return $data;
        }

        // Берём свежие данные
        $data = $callback();

        // Сохраняем (с возможным сжатием)
        $stored = self::compress($data, $compressionLevel);
        Cache::tags($tags)->put($key, $stored, $ttl);

        // Логируем (сравниваем размеры, если сжатие включено)
        $originalSize = strlen(serialize($data));
        $storedSize = strlen($stored);

        if ($compressionLevel > 0) {
            Log::info('[Cache MISS with compression]', [
                'key' => $key,
                'tags' => $tags,
                'level' => $compressionLevel,
                'original' => self::formatBytes($originalSize),
                'stored' => self::formatBytes($storedSize),
                'ratio' => round($originalSize / max(1, $storedSize), 2) . 'x',
            ]);
        } else {
            Log::info('[Cache MISS (no compression)]', [
                'key' => $key,
                'tags' => $tags,
                'size' => self::formatBytes($storedSize),
            ]);
        }

        return $data;
    }

    /**
     * Сжать данные (если уровень > 0)
     */
    private static function compress($data, int $level): string
    {
        $serialized = serialize($data);

        if ($level <= 0) {
            return $serialized; // без сжатия
        }

        return gzcompress($serialized, min(9, $level));
    }

    /**
     * Распаковать данные (автоматически определяет, сжаты ли они)
     */
    private static function decompress(string $data)
    {
        // Проверяем, похоже ли на сжатые данные (gzcompress начинается с 0x78)
        if (strlen($data) > 2 && ord($data[0]) == 0x78 && in_array(ord($data[1]), [0x01, 0x5E, 0x9C, 0xDA])) {
            return unserialize(gzuncompress($data));
        }

        // Не сжато
        return unserialize($data);
    }

    /**
     * Увеличить версию для конкретного ключа (сброс кэша)
     */
    public static function incrementVersion(string $versionKey): int
    {
        $new = Cache::increment($versionKey);
        Log::info("[Cache] Version incremented: {$versionKey} → {$new}");
        return $new;
    }

    /**
     * Получить текущую версию
     */
    public static function getVersion(string $versionKey): int
    {
        return Cache::get($versionKey, 1);
    }

    /**
     * Очистить кэш по тегам
     */
    public static function clearTags(array|string $tags): void
    {
        Cache::tags($tags)->flush();
        Log::info('[Cache] Cleared by tags', ['tags' => (array) $tags]);
    }

    /**
     * Форматировать байты в человекочитаемый вид
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Метод для получения размера
     */
    public static function getKeyInfo($key)
    {
        $redis = Cache::getRedis();
        $value = $redis->get($key);
        
        if (!$value) return null;
        
        $storedSize = strlen($value);
        $info = [
            'key' => $key,
            'stored_size' => $storedSize,
            'stored_size_human' => self::formatBytes($storedSize),
            'is_compressed' => false,
            'original_size' => $storedSize,
            'original_size_human' => self::formatBytes($storedSize),
            'ratio' => 1,
        ];
        
        try {
            $uncompressed = unserialize(gzuncompress($value));
            $originalSize = strlen(serialize($uncompressed));
            $info['is_compressed'] = true;
            $info['original_size'] = $originalSize;
            $info['original_size_human'] = self::formatBytes($originalSize);
            $info['ratio'] = round($originalSize / $storedSize, 2);
        } catch (\Exception $e) {
            // Не сжато
        }
        
        return $info;
    }
}