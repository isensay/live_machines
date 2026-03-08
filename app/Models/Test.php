<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    // Явно указываем имя таблицы
    protected $table = 'test';
    
    // Отключаем timestamps
    public $timestamps = false;
    
    protected $fillable = ['name', 'time', 'status'];
    
    protected $casts = [
        'status' => 'integer',
    ];

    // ========== ГЕТТЕРЫ ==========
    
    public function getNameAttribute($value)
    {
        return ucfirst($value);
    }
    
    public function getTimeAttribute($value)
    {
        return \Carbon\Carbon::createFromTimestamp($value);
    }
    
    public function getFormattedTimeAttribute()
    {
        return $this->time->format('d.m.Y H:i:s');
    }
    
    public function getDateOnlyAttribute()
    {
        return $this->time->format('d.m.Y');
    }
    
    public function getStatusNameAttribute()
    {
        return match($this->status) {
            0 => 'Неактивный',
            1 => 'Активный',
            2 => 'Ожидает',
            default => 'Неизвестно'
        };
    }
    
    public function getIsActiveAttribute()
    {
        return $this->status === 1;
    }

    // ========== СЕТТЕРЫ ==========
    
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtolower(trim($value));
    }
    
    public function setTimeAttribute($value)
    {
        if (is_string($value)) {
            $this->attributes['time'] = strtotime($value);
        } elseif ($value instanceof \Carbon\Carbon) {
            $this->attributes['time'] = $value->timestamp;
        } else {
            $this->attributes['time'] = $value;
        }
    }
    
    public function setStatusAttribute($value)
    {
        if (is_string($value)) {
            $this->attributes['status'] = match(strtolower($value)) {
                'inactive' => 0,
                'active' => 1,
                'pending' => 2,
                default => (int)$value
            };
        } else {
            $this->attributes['status'] = $value;
        }
    }
    
    // ========== SCOPE МЕТОДЫ ==========
    
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    
    public function scopeWhereName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }
    
    public function scopeLastDays($query, $days = 7)
    {
        $timestamp = time() - ($days * 24 * 60 * 60);
        return $query->where('time', '>=', $timestamp);
    }
}