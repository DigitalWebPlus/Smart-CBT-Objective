<?php

namespace App\Services;

class NotificationService {

    private static $createMessage = 'Created Successfully';
    private static $updateMessage = 'Updated Successfully';
    private static $deleteMessage = 'Deleted Successfully';
    private static $errorMessage = 'Something went wrong';
    private static $successMessage = 'Successful';

    private static function flash(string $type, string $message): void
    {
        $notifications = session()->get('app_notifications', []);
        $notifications[] = [
            'type' => $type,
            'message' => $message,
        ];
        session()->flash('app_notifications', $notifications);
    }

    static function CREATED($message = null){
        self::flash('success', $message ?? self::$createMessage);
    }

    static function UPDATED($message = null){
        self::flash('success', $message ?? self::$updateMessage);
    }

    static function DELETED($message = null){
        self::flash('success', $message ?? self::$deleteMessage);
    }

    static function ERROR($message = null){
        self::flash('error', $message ?? self::$errorMessage);
    }

    static function SUCCESS($message = null){
        self::flash('success', $message ?? self::$successMessage);
    }

}
