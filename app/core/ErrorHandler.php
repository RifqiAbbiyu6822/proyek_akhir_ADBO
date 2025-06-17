<?php
class ErrorHandler {
    public static function handleError($errno, $errstr, $errfile, $errline) {
        $error = [
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ];
        
        self::logError($error);
        
        if (ini_get('display_errors')) {
            self::displayError($error);
        }
        
        return true;
    }

    public static function handleException($exception) {
        $error = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        self::logError($error);
        
        if (ini_get('display_errors')) {
            self::displayError($error);
        }
    }

    private static function logError($error) {
        $logMessage = date('Y-m-d H:i:s') . " - Error: {$error['type']} - {$error['message']} in {$error['file']} on line {$error['line']}\n";
        if (isset($error['trace'])) {
            $logMessage .= "Stack trace:\n{$error['trace']}\n";
        }
        
        error_log($logMessage, 3, APPROOT . '/logs/error.log');
    }

    private static function displayError($error) {
        if (php_sapi_name() === 'cli') {
            echo "Error: {$error['message']} in {$error['file']} on line {$error['line']}\n";
            if (isset($error['trace'])) {
                echo "Stack trace:\n{$error['trace']}\n";
            }
        } else {
            require_once APPROOT . '/views/errors/error.php';
        }
    }
} 