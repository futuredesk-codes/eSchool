<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Session;
use App\Services\ResponseService;
use Illuminate\Support\Facades\File;
use Throwable;

class Controller extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function readLanguageFile() {
        try {
            //    https://medium.com/@serhii.matrunchyk/using-laravel-localization-with-javascript-and-vuejs-23064d0c210e
            header('Content-Type: text/javascript');
            //        $labels = Cache::remember('lang.js', 3600, static function () {
            //            $lang = app()->getLocale();
            $lang = Session::get('language');
            //            $lang = app()->getLocale();
            $test = $lang->code ?? "en";
            $files = resource_path('lang/' . $test . '.json');
            //            return File::get($files);
            //        });]
            echo ('window.languageLabels = ' . File::get($files));
            // http_response_code(200);
            exit();
        } catch (Throwable $th) {
            ResponseService::errorResponse($th->getMessage());
        }
    }
}
