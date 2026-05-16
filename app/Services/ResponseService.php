<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\NoReturn;
use Throwable;

class ResponseService {
    /**
     * @param $permission
     * @return Application|RedirectResponse|Redirector|true
     */
    public static function noPermissionThenRedirect($permission) {
        if (!Auth::user()->can($permission)) {
            return redirect(route('home'))->withErrors([
                'message' => trans("You Don't have enough permissions")
            ])->send();
        }
        return true;
    }

    /**
     * @param $permission
     * @return true
     */
    public static function noPermissionThenSendJson($permission) {
        if (!Auth::user()->can($permission)) {
            self::errorResponse("You Don't have enough permissions");
        }
        return true;
    }

    /**
     * @param $role
     * @return Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector|true
     */
    // Check user role
    public static function noRoleThenRedirect($role) {
        if (!Auth::user()->hasRole($role)) {
            return redirect(route('home'))->withErrors([
                'message' => trans("You Don't have enough permissions")
            ])->send();
        }
        return true;
    }

    /**
     * @param array $role
     * @return bool|Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector
     */
    public static function noAnyRoleThenRedirect(array $role) {
        if (!Auth::user()->hasAnyRole($role)) {
            return redirect(route('home'))->withErrors([
                'message' => trans("You Don't have enough permissions")
            ])->send();
        }
        return true;
    }

    //    /**
    //     * @param $role
    //     * @return true
    //     */
    //    public static function noRoleThenSendJson($role)
    //    {
    //        if (!Auth::user()->hasRole($role)) {
    //            self::errorResponse("You Don't have enough permissions");
    //        }
    //        return true;
    //    }

    /**
     * @param $feature
     * @return RedirectResponse|true
     */
    // Check Feature
    public static function noFeatureThenRedirect($feature) {
        if (Auth::user()->school_id && !app(FeaturesService::class)->hasFeature($feature)) {
            return redirect()->back()->withErrors([
                'message' => trans('Purchase') . " " . trans($feature) . " " . trans("to Continue using this functionality")
            ])->send();
        }
        return true;
    }

    public static function noFeatureThenSendJson($feature) {
        if (Auth::user()->school_id && !app(FeaturesService::class)->hasFeature($feature)) {
            self::errorResponse(trans('Purchase') . " " . trans($feature) . " " . trans("to Continue using this functionality"));
        }
        return true;
    }

    /**
     * If User don't have any of the permission that is specified in Array then Redirect will happen
     * @param array $permissions
     * @return RedirectResponse|true
     */
    public static function noAnyPermissionThenRedirect(array $permissions) {
        if (!Auth::user()->canany($permissions)) {
            return redirect()->back()->withErrors([
                'message' => trans("You Don't have enough permissions")
            ])->send();
        }
        return true;
    }

    /**
     * If User don't have any of the permission that is specified in Array then Json Response will be sent
     * @param array $permissions
     * @return true
     */
    public static function noAnyPermissionThenSendJson(array $permissions) {
        if (!Auth::user()->canany($permissions)) {
            self::errorResponse("You Don't have enough permissions");
        }
        return true;
    }

    /**
     * @param string $message
     * @param $data
     * @param array $customData
     * @param $code
     * @return void
     */
    #[NoReturn] public static function successResponse(string $message = "Success", $data = null, array $customData = array(), $code = null) {
        response()->json(array_merge([
            'error'   => false,
            'message' => trans($message),
            'data'    => $data,
            'code'    => $code ?? 200
        ], $customData))->send();
        exit();
    }

    /**
     * @param string $message
     * @param $url
     * @return Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector
     */
    public static function successRedirectResponse(string $message = "success", $url = null)
    {
        return isset($url) ? redirect($url)->with([
            'success' => trans($message)
        ])->send() : redirect()->back()->with([
            'success' => trans($message)
        ])->send();
    }

    /**
     *
     * @param string $message - Pass the Translatable Field
     * @param null $data
     * @param null $code
     * @param null $e
     * @return void
     */
    #[NoReturn] public static function errorResponse(string $message = 'Error Occurred', $data = null, $code = null, $e = null) {
        if ($e) {
            self::logErrorResponse($e);
            self::logCurlRequest();
        }

        response()->json([
            'error'   => true,
            'message' => trans($message),
            'data'    => $data,
            'code'    => $code ?? config('constants.RESPONSE_CODE.EXCEPTION_ERROR'),
            'details' => (!empty($e) && is_object($e)) ? $e->getMessage() . ' --> ' . $e->getFile() . ' At Line : ' . $e->getLine() : ''
        ])->send();
        exit();
    }

    /**
     * @param string $message
     * @param $url
     * @return Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector
     */
    public static function errorRedirectResponse($url = null, string $message = 'Error Occurred') {
        return (($url != null) ? redirect($url) : redirect()->back())->withErrors([
            'message' => trans($message)
        ])->send();
    }

    /**
     * @param string $message
     * @param null $data
     * @param null $code
     * @return void
     */
    #[NoReturn] public static function warningResponse(string $message = 'Error Occurred', $data = null, $code = null) {
        response()->json([
            'error'   => false,
            'warning' => true,
            'code'    => $code,
            'message' => trans($message),
            'data'    => $data,
        ])->send();
        exit();
    }


    /**
     * @param string $message
     * @param null $data
     * @return void
     */
    #[NoReturn] public static function validationError(string $message = 'Error Occurred', $data = null) {
        self::errorResponse($message, $data, config('constants.RESPONSE_CODE.VALIDATION_ERROR'));
    }

    /**
     * @param Throwable|Exception $e
     * @param string $logMessage
     * @param string $responseMessage
     * @param bool $jsonResponse
     * @return void
     */
    public static function logErrorResponse(Throwable|Exception $e) {
        report($e);
        $token = request()->bearerToken();

        Log::error($e->getMessage() . '---> ' . $e->getFile() . ' At Line : ' . $e->getLine() . "\n\n" . request()->method() . " : " . request()->fullUrl() . "\nToken : " . $token . "\nParams : ", request()->all());
    }

    public static function logCurlRequest() {
        $request = request();
        // Log::error("CURL Request:\n", $request->all());
        $method  = strtoupper($request->method());
        $url     = $request->fullUrl();
        $headers = [];

        foreach ($request->headers->all() as $key => $values) {
            foreach ($values as $value) {
                $headers[] = "-H '" . $key . ": " . $value . "'";
            }
        }

        $data = '';

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // Multipart (e.g., file upload)
            $parts = [];
            foreach ($request->input() as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if (is_array($v)) {
                            foreach ($v as $kk => $vv) {
                                $parts[] = "-F '{$key}[{$k}][{$kk}]={$vv}'";
                            }
                        } else {
                            $parts[] = "-F '{$key}[{$k}]={$v}'";
                        }
                    }
                } else {
                    $parts[] = "-F '{$key}={$value}'";
                }
            }

            foreach ($request->files->all() as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if (is_array($v)) {
                            foreach ($v as $kk => $vv) {
                                $parts[] = "-F '{$key}[{$k}][{$kk}]={$vv}'";
                            }
                        } else {
                            $parts[] = "-F '{$key}[{$k}]={$v}'";
                        }
                    }
                } else {
                    $parts[] = "-F '{$key}={$value}'";
                }
            }


            $data = " " . implode(" \\\n  ", $parts);
        }

        $curl = "curl -X {$method} '" . $url . "' \\\n  " . implode(" \\\n  ", $headers) . $data;

        Log::error("CURL Request:\n" . $curl);
    }
}
