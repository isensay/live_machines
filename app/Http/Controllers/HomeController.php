<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display the home page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        //$currentLocale = app()->getLocale();
        //dump($currentLocale);
        return view('home', [
            'title'       => 'Adoxa - Главная страница',
            'description' => 'Добро пожаловать в Adoxa - ваш новый проект на Laravel',
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
