<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory())
            ->withServiceAccount(config('firebase.credentials'));

        $this->messaging = $factory->createMessaging();
    }

    public function sendNotification(int $userId, string $title, string $body)
    {
        $user = User::find($userId);
        if (!$user || !$user->fcm_token) {
            return false;
        }

        $message = CloudMessage::withTarget('token', $user->fcm_token)
            ->withNotification(Notification::create($title, $body));

        try {
            $this->messaging->send($message);
            return true;
        } catch (\Exception $e) {
            // log error maybe?
            return false;
        }
    }
}