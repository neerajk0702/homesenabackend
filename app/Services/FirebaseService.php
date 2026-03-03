<?php
namespace App\Services; 

use Illuminate\Support\Facades\Http;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(config('services.firebase.credentials'));

        $this->messaging = $factory->createMessaging();
    }

    // public function sendNotification($deviceToken, $title, $body, $data = [])
    // {
    //     $message = CloudMessage::withTarget('token', $deviceToken)
    //         ->withNotification(Notification::create($title, $body))
    //         ->withData($data);

    //     return $this->messaging->send($message);
    // }

    public function send($token, $title, $message)
    {
        $serverKey = env('FIREBASE_SERVER_KEY');

        return Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type'  => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $message,
            ],
        ]);
    }
}