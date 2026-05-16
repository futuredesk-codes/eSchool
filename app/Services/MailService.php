<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class MailService
{
    /**
     * Send email with error handling
     */
    public static function send($view, $data, $callback)
    {
        try {
            Mail::send($view, $data, $callback);
            return true;
        } catch (Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage());
            
            // Log the email content for debugging
            Log::info('Failed email details', [
                'view' => $view,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Send email with fallback to log
     */
    public static function sendWithFallback($view, $data, $callback)
    {
        $sent = self::send($view, $data, $callback);
        
        if (!$sent) {
            // Log the email content instead of sending
            Log::info('Email logged instead of sent', [
                'view' => $view,
                'data' => $data,
                'timestamp' => now()
            ]);
        }
        
        return $sent;
    }

    /**
     * Check if email is properly configured
     */
    public static function isEmailConfigured()
    {
        try {
            $mailConfig = config('mail.default');
            $mailer = config("mail.mailers.{$mailConfig}");
            
            if ($mailConfig === 'log') {
                return true; // Log driver is always available
            }
            
            if ($mailConfig === 'smtp') {
                return !empty(config('mail.mailers.smtp.username')) && 
                       !empty(config('mail.mailers.smtp.password'));
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
} 