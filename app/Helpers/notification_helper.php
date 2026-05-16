<?php

use App\Models\User;
use App\Models\Settings;
use Illuminate\Support\Facades\Log;

function sendSimpleNotification($user, $title, $body, $type, $image, $userinfo)
{

    $FcmToken1 = User::where('fcm_id', '!=', '')->whereIn('id', $user)->where('device_type', '=' ,'android')->get()->pluck('fcm_id');
    $FcmToken2 = User::where('fcm_id', '!=', '')->whereIn('id', $user)->where('device_type', '=' ,'ios')->get()->pluck('fcm_id');
    $device_type = User::whereIn('id', $user)->pluck('device_type');

    $project_id = Settings::select('message')->where('type', 'project_id')->pluck('message')->first();
    // $sender_id = Settings::select('message')->where('type', 'sender_id')->pluck('message')->first();
    $url = 'https://fcm.googleapis.com/v1/projects/' . $project_id . '/messages:send';


    $access_token = getAccessToken();

    if($type == 'chat'){
        $userDetails = $userinfo;
        $userinfo = json_encode($userDetails);

        $notification_data = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            "title" => $title,
            "body" => $body,
            "type" => $type,
            "image" => $image,
            "sender_info" =>  $userinfo
        ];


    }elseif ($type == 'fees-due') {
        $notification_data = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            "title" => $title,
            "body" => $body,
            "type" => $type,
            "image" => $image,
            "child_id" =>  $userinfo
        ];
    }
    else{
        $notification_data = [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            "title" => $title,
            "body" => $body,
            "type" => $type,
            "image" => $image
        ];
    }

    if ($device_type->contains('android')) {
        $androidFcmTokens = $FcmToken1->toArray();
        foreach ($androidFcmTokens as $token) {
            $message1 = [
                "message"=>[
                "token" => $token,
                "data" => $notification_data
                ]
            ];
            $data1 = json_encode($message1);

            sendFCMCurl($url, $access_token, $data1);
        }

    }

    if ($device_type->contains('ios')) {
        $iosFcmTokens = $FcmToken2->toArray();

        foreach ($iosFcmTokens as $token) {

            $message2 = [
                "message"=>[
                    "token" => $token,
                    "notification" => [
                        "title" => $title,
                        "body" => $body,
                    ],
                    "data" => $notification_data
                ]
            ];

            $data2 = json_encode($message2);
            // Send notification to iOS users
            sendFCMCurl($url, $access_token, $data2);
        }
    }
}

function sendNotificationToTopic($title, $body, $type, $image, $userinfo) {
    $project_id = Settings::select('message')->where('type', 'project_id')->pluck('message')->first();
    // $sender_id = Settings::select('message')->where('type', 'sender_id')->pluck('message')->first();
    $url = 'https://fcm.googleapis.com/v1/projects/' . $project_id . '/messages:send';

    $access_token = getAccessToken();

    // If access token is null, log error and return without sending notifications
    if (!$access_token) {
        Log::error('Cannot send FCM notifications: Access token is null');
        return;
    }

    $notification_data = [
        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        "title" => $title,
        "body" => $body,
        "type" => $type,
        "image" => $image
    ];

    $topicMap = [
        "all"      => ["allAndroid", "allIOS"],
        "students" => ["studentsAndroid", "studentsIOS"],
        "parents"  => ["parentsAndroid", "parentsIOS"],
        "teachers" => ["teachersAndroid", "teachersIOS"],
    ];

    foreach ($topicMap[$userinfo] as $topic) {
        Log::info("topic: " . $topic);
        if (str_contains($topic, 'Android')) {
            Log::info("topic android: " . $topic);
            $message1 = [
                "message" => [
                    "topic" => $topic,
                    "data" => $notification_data
                ]
            ];
            $data1 = json_encode($message1);
            sendFCMCurl($url, $access_token, $data1);
        }

        if (str_contains($topic, 'IOS')) {
            Log::info("topic ios: " . $topic);
            $message2 = [
                "message" => [
                    "topic" => $topic,
                    "notification" => [
                        "title" => $title,
                        "body" => $body,
                    ],
                    "data" => $notification_data
                ]
            ];
            $data2 = json_encode($message2);
            sendFCMCurl($url, $access_token, $data2);
        }
    }
}


function sendFCMCurl($url, $access_token, $Data) {
    try {
        if (!$access_token) {
            Log::error('Cannot send FCM notification: Access token is null');
            return false;
        }

        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        // Disabling SSL Certificate support temporarily
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $Data);

        // Execute post
        $result = curl_exec($ch);

        if ($result == FALSE) {
            $error = curl_error($ch);
            Log::error('FCM Curl failed: ' . $error);
            curl_close($ch);
            return false;
        }

        // Close connection
        curl_close($ch);

        return true;
    } catch (\Exception $e) {
        Log::error('FCM notification error: ' . $e->getMessage());
        return false;
    }
}

function getAccessToken() {
    try {
        $file_name = Settings::select('message')->where('type', 'service_account_file')->pluck('message')->first();

        if (!$file_name) {
            Log::error('Firebase service account file not configured');
            return null;
        }

        $file_path = base_path('public/storage/' . $file_name);

        if (!file_exists($file_path)) {
            Log::error('Firebase service account file not found: ' . $file_path);
            return null;
        }

        // Check if Google Client class exists
        if (!class_exists('Google\Client')) {
            Log::error('Google Client library not installed. Please install: composer require google/apiclient');
            return null;
        }

        $client = new \Google\Client();
        $client->setAuthConfig($file_path);
        $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);
        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

        return $accessToken;
    } catch (\Exception $e) {
        Log::error('Error getting Firebase access token: ' . $e->getMessage());
        return null;
    }
}
