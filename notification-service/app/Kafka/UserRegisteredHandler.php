<?php

namespace App\Kafka;

use Junges\Kafka\Contracts\KafkaConsumerMessageHandler;
use Junges\Kafka\Message\ConsumedMessage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\UserRegisteredNotification;

class UserRegisteredHandler implements KafkaConsumerMessageHandler
{
    public function handle(ConsumedMessage $message): void
    {
        $data = $message->getBody();

        if (!isset($data['email'], $data['name'])) {
            logger()->warning('Invalid message format', $data);
            return;
        }

        Notification::route('mail', $data['email'])
            ->notify(new UserRegisteredNotification($data['name']));
    }
}
