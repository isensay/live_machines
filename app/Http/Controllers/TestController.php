<?php

namespace App\Http\Controllers;

use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TestController extends Controller
{
    /**
     * Display all active tests
     */
    public function activeTests()
    {
        // Все активные записи
        $activeTests = Test::active()->get();
        
        return response()->json([
            'success' => true,
            'data' => $activeTests->map(function($test) {
                return [
                    'id' => $test->id,
                    'name' => $test->name,
                    'time' => $test->formatted_time,
                    'status' => $test->status,
                    'status_name' => $test->status_name,
                    'is_active' => $test->is_active,
                ];
            })
        ]);
    }

    /**
     * Search tests by name
     */
    public function searchByName(Request $request)
    {
        $name = $request->input('name', 'test');
        
        // Поиск по имени
        $tests = Test::whereName($name)->get();
        
        return response()->json([
            'success' => true,
            'search_term' => $name,
            'data' => $tests->map(function($test) {
                return [
                    'id' => $test->id,
                    'name' => $test->name,
                    'time' => $test->formatted_time,
                    'status_name' => $test->status_name,
                ];
            })
        ]);
    }

    /**
     * Get recent tests (last 7 days)
     */
    public function recentTests()
    {
        // Записи за последние 7 дней
        $recentTests = Test::lastDays(7)->get();
        
        return response()->json([
            'success' => true,
            'data' => $recentTests->map(function($test) {
                return [
                    'id' => $test->id,
                    'name' => $test->name,
                    'time' => $test->formatted_time,
                    'date_only' => $test->date_only,
                    'status_name' => $test->status_name,
                ];
            })
        ]);
    }

    /**
     * Get first test with all getters
     */
    public function firstTest()
    {
        // Использование геттеров
        $test = Test::first();
        
        if (!$test) {
            return response()->json([
                'success' => false,
                'message' => 'No tests found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $test->id,
                'name' => $test->name,
                'time' => $test->time->timestamp, // оригинальный timestamp
                'formatted_time' => $test->formatted_time, // '26.10.2024 15:30:00'
                'date_only' => $test->date_only, // только дата
                'status' => $test->status, // число
                'status_name' => $test->status_name, // 'Активный'
                'is_active' => $test->is_active, // true/false
            ]
        ]);
    }

    /**
     * Create a new test
     */
    public function createTest(Request $request)
    {
        $now = Carbon::now();

        echo $now; 

        dd($now);

        //$request->validate([
        //    'name' => 'required|string|max:64',
        //    'time' => 'required',
        //    'status' => 'required|integer|in:0,1,2',
        //]);

        //$test = Test::create([
        //    'name' => $request->name,
        //    'time' => $request->time,
        //    'status' => $request->status,
        //]);

        $test = Test::create([
            'name' => 'teeeeest',
            'time' => time(),
            'status' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test created successfully',
            'data' => [
                'id' => $test->id,
                'name' => $test->name,
                'formatted_time' => $test->formatted_time,
                'status_name' => $test->status_name,
            ]
        ], 201);
    }

    /**
     * Get all tests with pagination
     */
    public function index()
    {
        $tests = Test::orderBy('id', 'desc')->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => $tests->map(function($test) {
                return [
                    'id' => $test->id,
                    'name' => $test->name,
                    'formatted_time' => $test->formatted_time,
                    'status_name' => $test->status_name,
                    'is_active' => $test->is_active,
                ];
            }),
            'pagination' => [
                'current_page' => $tests->currentPage(),
                'last_page' => $tests->lastPage(),
                'per_page' => $tests->perPage(),
                'total' => $tests->total(),
            ]
        ]);
    }
}