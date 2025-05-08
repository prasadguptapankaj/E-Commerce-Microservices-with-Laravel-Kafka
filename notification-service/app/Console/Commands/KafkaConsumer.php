<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\SendOrderStatusNotification;
use App\Notifications\UserRegisteredNotification;
use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;

class KafkaConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consumes Kafka messages and handles notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Listening for Kafka messages...');

        Kafka::consumer()
            ->subscribe(['user.registered', 'order.placed', 'payment.completed', 'user.loggedin'])
            ->withHandler(function(\Junges\Kafka\Contracts\ConsumerMessage $message, \Junges\Kafka\Contracts\MessageConsumer $consumer) {
                $topic = $message->getTopicName();
                
                switch ($topic) {
                    case 'user.registered':
                        $payload = $message->getBody();
                
                        if($payload){
                            if (isset($payload['email'])) {
                                $user = User::where('email', $payload['email'])->first();
                                if ($user) {
                                    $user->notify(new UserRegisteredNotification());
                                }
                            }
                        }
                        break;
                    case 'order.placed':
                        $payload = $message->getBody();
                
                        if($payload){
                            if (isset($payload['user_id'])) {
                                $user = User::where('id', $payload['user_id'])->first();
                                if ($user) {
                                    $user->notify(new SendOrderStatusNotification());
                                }
                            }
                        }
                        break;
                    case 'payment.completed':
                        // handle payment
                        break;
                }

                
            })
            ->withOptions([
                'compression.codec' => 'gzip' // or remove this line entirely
            ])
            ->build()
            ->consume();
    }
}
