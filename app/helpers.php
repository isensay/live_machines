<?php

if (!function_exists('getErrorTitle')) {
    function getErrorTitle(int $status): string
    {
        return trans('errors')['errors'][$status]['title'] ?? 'Error';
    }
}

if (!function_exists('getErrorText')) {
    function getErrorText(int $status): string
    {
        return trans('errors')['errors'][$status]['text'] ?? 'An error occurred';
    }
}

if (!function_exists('getErrorImage')) {
    function getErrorImage(int $status): string
    {
        return "/source/base/images/errors/{$status}.svg";
    }
}